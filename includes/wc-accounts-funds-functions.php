<?php
/**
 * @insert or update cash back rules
 *
 * @param $args
 *
 * @return int|WP_Error
 * @since 1.0.0
 */
function wc_accounts_funds_insert_cashback_rules( $args ) {
	global $wpdb;
	$update      = false;
	$args        = apply_filters( 'wc_account_funds_insert_cashback_args', $args );
	$item_before = '';
	$table_name  = $wpdb->prefix . 'cashback_rules';
	$id          = ! empty( $args['id'] ) ? absint( $args['id'] ) : '';
	if ( isset( $args['id'] ) && ! empty( $args['id'] ) ) {
		$id          = (int) $args['id'];
		$update      = true;
		$item_before = (array) wc_account_funds_get_cashback_rules( $id );
		if ( is_null( $item_before ) ) {
			return new WP_Error( 'invalid-action', __( 'Could not find any rules to update', 'wc-accounts-funds' ) );
		}

		$args = array_merge( $item_before, $args );
	}

	$data = array(
		'id'            => ! empty( $args['id'] ) ? intval( $args['id'] ) : '',
		'cashback_type' => ! empty( $args['cashback_type'] ) ? sanitize_key( $args['cashback_type'] ) : 'fixed',
		'price_from'    => ! empty( $args['price_from'] ) ? sanitize_text_field( $args['price_from'] ) : '',
		'price_to'      => ! empty( $args['price_to'] ) ? sanitize_text_field( $args['price_to'] ) : '',
		'amount'        => ! empty( $args['amount'] ) ? intval( $args['amount'] ) : '',
		'cashback_for'  => ! empty( $args['cashback_for'] ) ? sanitize_key( $args['cashback_for'] ) : 'cart',
		'status'        => ! empty( $args['status'] ) ? sanitize_key( $args['status'] ) : 'publish'
	);

	if ( empty( $args['price_from'] ) ) {
		wc_add_notice( __( 'cart Price from is required', '' ), 'error' );

		return new WP_Error( 'empty-content', __( 'Cart Price From is required', 'wc-accounts-funds' ) );
	}

	if ( empty( $args['price_to'] ) ) {
		//todo add notice class and notice element
		return new WP_Error( 'empty-content', __( 'Cart Price To is required', 'wc-accounts-funds' ) );
	}

	if ( empty( $args['amount'] ) ) {
		//todo add notice class and notice element
		return new WP_Error( 'empty-content', __( 'Cash back amount  is required', 'wc-accounts-funds' ) );
	}

	$data  = wp_unslash( $data );

	if ( $update ) {
		$where = array( 'id' => $id );
		do_action( 'wc_accounts_funds_pre_cashback_rules_update', $id, $data );
		if ( false === $wpdb->update( $table_name, $data, $where ) ) {
			return new WP_Error( 'db_update_error', __( 'Could not update cash-back rules in database', 'wc-accounts-funds' ), $wpdb->last_error );
		}
		do_action( 'wc_accounts_funds_cashback_rules_update', $id, $data, $item_before );
	} else {
		do_action( 'wc_accounts_funds_pre_cashback_rules_insert', $id, $data );
		if ( false === $wpdb->insert( $table_name, $data ) ) {
			return new WP_Error( 'db_insert_error', __( 'Could not insert cash-back rules in database', 'wc-accounts-funds' ), $wpdb->last_error );
		}

		$id = (int) $wpdb->insert_id;
		do_action( 'wc_accounts_funds_cashback_rules_insert', $id, $data );
	}

	return $id;
}

/**
 * @get single cash back rules
 *
 * @param $id
 *
 * @return object|null
 * @since 1.0.0
 */
function wc_account_funds_get_cashback_rules( $id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'cashback_rules';

	return $wpdb->get_row( $wpdb->prepare( "select * from $table_name where id=%s", $id ) );
}

/**
 * get user funds
 *
 * @param null $user_id
 * @param bool $formatted
 * @param int $excluded_order_id
 *
 * @return int|mixed|string
 * @since 1.0.0
 */
function get_user_accounts_funds( $user_id = null, $formatted = true, $excluded_order_id = 0 ) {
	$current_user_id = $user_id ? $user_id : get_current_user_id();

	if ( ! empty( $current_user_id ) ) {
		$accounts_funds = get_user_meta( $current_user_id, 'wc_user_account_funds', true );

		//account for pending orders
		$wc_accounts_orders_with_pending_orders = get_posts( array(
			'post_type'   => 'shop_order',
			'post_status' => array_keys( wc_get_order_statuses() ),
			'numberposts' => - 1,
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'keys'  => '_customer_user',
					'value' => $current_user_id
				),
				array(
					'key'   => '_accounts_funds_removed',
					'value' => '0',
				),
				array(
					'key'     => '_accounts_funds_used',
					'value'   => '0',
					'compare' => '>'
				)
			),
		) );

		foreach ( $wc_accounts_orders_with_pending_orders as $single_order_id ) {
			if ( null != WC()->session && ! empty( WC()->session->order_awaiting_payment ) && $single_order_id == WC()->session->order_awaiting_payment ) {
				continue;
			}
			if ( $excluded_order_id === $single_order_id ) {
				continue;
			}
			$accounts_funds = $accounts_funds - floatval( get_post_meta( $single_order_id, '_funds_used', true ) );
		}

	} else {
		$accounts_funds = 0;

	}

	return $formatted ? wc_price( $accounts_funds ) : $accounts_funds;

}

/**
 * Add funds to accounts when customer purchase a cash-back product
 *
 * @param $user_id
 * @param $amount
 *
 * @since 1.0.0
 * 
 * @return bool|int
 */
function wc_accounts_funds_add_funds( $user_id, $amount, $details='' ) {
	global $wpdb;
	if( ! $user_id ){
		return;
	}
	if ( $amount < 0 ) {
		$amount = 0;
	}
	$accounts_funds = ! empty( get_user_meta( $user_id, 'wc_user_account_funds', true ) ) ? get_user_meta( $user_id, 'wc_user_account_funds', true ) : 0;
	$accounts_funds += floatval( $amount );
	$accounts_funds = apply_filters( 'wc_accounts_funds_add_funds', $accounts_funds, $user_id, $amount );
	if ( $wpdb->insert( "{$wpdb->base_prefix}wcaf_transactions", apply_filters( 'wc_accounts_funds_transactions_args', array( 'blog_id' => get_current_blog_id(), 'user_id' => $user_id, 'type' => 'credit', 'amount' => $amount, 'balance' => $accounts_funds, 'currency' => get_woocommerce_currency(), 'details' => $details, 'date' => current_time('mysql'), 'created_by' => get_current_user_id() ), array( '%d', '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%d' ) ) ) ) {
		$transaction_id = $wpdb->insert_id;
		update_user_meta( $user_id, 'wc_user_account_funds', $accounts_funds );
		return $transaction_id;
	}
	return false;
}

/**
 * Remove funds from user accounts when a user pay using accounts funds
 *
 * @param $user_id
 * @param $amount
 *
 * @since 1.0.0
 */
function wc_accounts_funds_remove_funds( $user_id, $amount, $details='' ) {
	global $wpdb;
	if( ! $user_id ){
		return;
	}
	if ( $amount < 0 ) {
		$amount = 0;
	}

	$available_accounts_funds = ! empty( get_user_meta( $user_id, 'wc_user_account_funds', true ) ) ? get_user_meta( $user_id, 'wc_user_account_funds', true ) : 0;
	$paid_funds               = $amount;
	$remaining_funds          = $available_accounts_funds - floatval( $paid_funds );
	$remaining_funds          = apply_filters( 'wc_accounts_funds_remove_funds', $remaining_funds, $user_id, $amount );

	if ( $wpdb->insert( "{$wpdb->base_prefix}wcaf_transactions", apply_filters( 'wc_accounts_funds_transactions_args', array( 'blog_id' => get_current_blog_id(), 'user_id' => $user_id, 'type' => 'debit', 'amount' => $amount, 'balance' => $remaining_funds, 'currency' => get_woocommerce_currency(), 'details' => $details, 'date' => current_time('mysql'), 'created_by' => get_current_user_id() ), array( '%d', '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%d' ) ) ) ) {
		$transaction_id = $wpdb->insert_id;
		update_user_meta( $user_id, 'wc_user_account_funds', $remaining_funds );
		return $transaction_id;
	}
	return false;
	
}

/**
 * Send email to customers when they gets credits in their account
 *
 * @param $email_subject
 * @param $message
 * @param $user_id
 *
 * @since 1.0.0
 */
function wc_accounts_funds_sent_credit_email($email_subject,$message,$user_id){
	global $woocommerce;
	$user_data = get_userdata($user_id);
	$user_email = $user_data->user_email;
	$mailer = $woocommerce->mailer();
	$message =$mailer->wrap_message($email_subject,$message);
	$headers = apply_filters( 'woocommerce_email_headers', '', 'rewards_message' );
	$mailer->send($user_email,$email_subject,$message,$headers,array());
}

/**
 * Check if user already reviewed this product
 * 
 * @since 1.0.0
 *
 * @param int $userid
 * @param int $product_id
 * 
 * @return bool
 */
function wc_accounts_funds_is_user_reviewed( $userid, $product_id ){
	$all_reviews = get_user_meta( $userid, 'wc_account_funds_reviews', true );
	if( $all_reviews && ! empty( $all_reviews ) && is_array( $all_reviews ) ){
		if( in_array( $product_id, $all_reviews ) ){
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Save user review for a product
 * 
 * @since 1.0.0
 *
 * @param int $userid
 * @param int $product_id
 * 
 * @return bool
 */
function wc_accounts_funds_add_user_review( $userid, $product_id ){
	$all_reviews = get_user_meta( $userid, 'wc_account_funds_reviews', true );
	if( $all_reviews && ! empty( $all_reviews ) && is_array( $all_reviews ) ){
		$all_reviews[] = $product_id;
		update_user_meta( $userid, 'wc_account_funds_reviews', $all_reviews );
		return true;
	} else {
		$all_reviews = array();
		$all_reviews[] = $product_id;
		update_user_meta( $userid, 'wc_account_funds_reviews', $all_reviews );
		return true;
	}
}

