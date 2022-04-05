<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Accounts_Funds_Checkout_handler {

	/**
	 * Class constructor
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_update_order_meta' ), 10, 2 );
		add_filter( 'woocommerce_order_item_needs_processing', array( $this, 'checkout_order_item_needs_processing' ), 10, 2 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'wc_accounts_funds_increase_user_funds' ) );
		//remove funds from the customer accounts
		add_action( 'woocommerce_payment_complete', array( $this, 'maybe_remove_accounts_funds' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_remove_accounts_funds' ) );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'maybe_remove_accounts_funds' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_remove_accounts_funds' ) );
		//refund the accounts funds when cancelled or refunded order
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'maybe_refund_accounts_funds' ) );
		add_action( 'woocommerce_order_refunded', array( $this, 'maybe_refund_accounts_funds' ) );
		//order item totals changed
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'accounts_get_order_item_totals' ), 10, 2 );

		//admin order totals update
		add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'accounts_funds_add_after_order_total' ) );
		add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'accounts_funds_remove_from_recalculation' ), 10, 2 );

	}

	/**
	 * Increase user funds when cashback products used
	 *
	 * @param $order_id
	 *
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_increase_user_funds( $order_id ) {
		$order         = wc_get_order( $order_id );
		$ordered_items = $order->get_items();
		$customer_id   = $order->get_user_id();

		if ( get_current_user_id() != $customer_id ) {
			return;
		}

		if ( $customer_id && ! get_post_meta( $order_id, '_accounts_funds_deposited', true ) ) {
			foreach ( $ordered_items as $item ) {
				$product = $item->get_product();
				if ( ! is_a( $product, 'WC_Product' ) ) {
					continue;
				}

				$account_funds = 0;
				if ( $product->is_type( 'cashback' ) ) {
					$account_funds = $item['line_subtotal'];
				} elseif ( $product->get_id() == get_option( 'wc_accounts_funds_topup_product' ) ) {
					$account_funds = $item['line_subtotal'];
				} else {
					continue;
				}
				$details = sprintf( __( 'Balance credited for topup #%s', 'wc-accounts-funds' ), $order_id);
				wc_accounts_funds_add_funds( $customer_id, $account_funds, $details );
				$order->add_order_note( sprintf( __( 'Customer account funds gets amount %s', 'wc-accounts-funds' ), wc_price( $account_funds ) ) );
				update_post_meta( $order_id, '_accounts_funds_deposited', 1 );
			}
		}


	}

	/**
	 * Remove user funds when an order is created
	 *
	 * @param $order_id
	 * @param $posted
	 *
	 * @since 1.0.0
	 */
	public function checkout_update_order_meta( $order_id, $posted ) {
		if ( $posted['payment_method'] != 'wc_account_funds' && WC_Accounts_Funds_Cart_handler::using_accounts_funds() ) {
			$used_accounts_funds = WC_Accounts_Funds_Cart_handler::used_accounts_funds_amount();
			update_post_meta( $order_id, '_accounts_funds_used', $used_accounts_funds );
			update_post_meta( $order_id, '_accounts_funds_removed', 0 );
		}
	}

	/**
	 * Filters the order items if cash back or top-up items exists for processing.
	 *
	 * @param $processing
	 * @param WC_Product $product Product object.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function checkout_order_item_needs_processing( $processing, $product ) {
		if ( $product->is_type( 'cashback' ) || $product->is_type( 'topup' ) ) {
			$processing = false;
		}

		return $processing;
	}

	/**
	 * Try to remove customer accounts funds
	 *
	 * @param $order_id
	 *
	 * @since 1.0.0
	 */
	public function maybe_remove_accounts_funds( $order_id ) {
		if ( WC()->session !== null ) {
			WC()->session->set( 'use-accounts-funds', false );
			WC()->session->set( 'used-accounts-funds', false );
		}

		$order                  = wc_get_order( $order_id );
		$customer_id            = $order->get_user_id();
		$removed_accounts_funds = get_post_meta( $order_id, '_accounts_funds_removed', true );

		if ( $customer_id && ! $removed_accounts_funds ) {
			if ( $used_accounts_funds = get_post_meta( $order_id, '_accounts_funds_used', true ) ) {
				wc_accounts_funds_remove_funds( $customer_id, $used_accounts_funds );
				$order->add_order_note( sprintf( __( 'Removed account funds amount of %s from customer #%d', 'wc-accounts-funds' ), wc_price( $used_accounts_funds ), $customer_id ) );
			}
			update_post_meta( $order_id, '_accounts_funds_removed', 1 );
		}

	}

	/**
	 * Refund to the accounts funds when a order cancelled
	 *
	 * @param $order_id
	 *
	 * @since 1.0.0
	 */
	public function maybe_refund_accounts_funds( $order_id ) {
		$order       = wc_get_order( $order_id );
		$customer_id = $order->get_customer_id();
		if ( $used_accounts_funds = get_post_meta( $order_id, '_accounts_funds_used', true ) ) {
			wc_accounts_funds_add_funds( $customer_id, $used_accounts_funds );
			$order->add_order_note( sprintf( __( 'Account funds restored by amount of %s to customer #%d', 'wc-accounts-funds' ), wc_price( $used_accounts_funds ), $customer_id ) );
		}

	}

	/**
	 * Order total items display
	 *
	 * @param $rows
	 * @param WC_Order $order
	 *
	 * @return mixed $rows
	 * @since 1.0.0
	 */
	public function accounts_get_order_item_totals( $rows, $order ) {
		$order_id = $order->get_id();
		if ( $funds_used = get_post_meta( $order_id, '_accounts_funds_used', true ) ) {
			$rows['accounts_funds_used'] = array(
				'label' => __( 'Accounts Funds Used:', 'wc-accounts-funds' ),
				'value' => wc_price( $funds_used )
			);
		}

		return $rows;
	}

	/**
	 * Add rows in edit order to show accounts_funds_used and order total
	 *
	 * @param $order_id
	 *
	 * @since 1.0.0
	 */
	public function accounts_funds_add_after_order_total( $order_id ) {
		$accounts_funds_used = get_post_meta( $order_id, '_accounts_funds_used', true );
		if ( $accounts_funds_used <= 0 ) {
			return;
		}

		$order = wc_get_order( $order_id );
		?>
        <tr>
            <td class="label"><?php echo esc_html( __( 'Accounts Funds Used', 'wc-accounts-funds' ) ); ?></td>
            <td width="1%"></td>
            <td class="total"><?php echo wc_price( $accounts_funds_used ); ?></td>
        </tr>
        <tr>
            <td class="label"><?php echo esc_html( __( 'Order Total after Account Funds Used', 'wc-accounts-funds' ) ); ?></td>
            <td width="1%"></td>
            <td class="total"><?php echo wc_price( $order->get_total() ); ?>
            </td>
        </tr>
		<?php
	}

	/**
	 * Adjust total amount with accounts funds
	 *
	 * @param $taxes
	 * @param WC_Order $order
	 *
	 * @throws WC_Data_Exception
	 * @since 1.0.0
	 */
	public function accounts_funds_remove_from_recalculation( $taxes, $order ) {
		$accounts_funds_used = get_post_meta( $order->get_id(), '_accounts_funds_used', true );
		$total               = floatval( $order->get_total() ) - floatval( $accounts_funds_used );
		$order->set_total( round( $total, wc_get_price_decimals() ) );
	}


}

new WC_Accounts_Funds_Checkout_handler();
