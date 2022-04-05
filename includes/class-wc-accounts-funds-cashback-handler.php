<?php
defined( 'ABSPATH' ) || exit();

class WC_Accounts_Funds_Cashback_handler {
	/**
	 * Define variables
	 */
	public $allow_cashback;
	public $cashback_conditions;
	public $cashback_type;


	/**
	 * class constructor
	 */
	public function __construct() {
		$this->allow_cashback      = get_option( 'wc_af_allow_cash_back' );
		$this->cashback_conditions = get_option( 'wc_af_multiple_cashback_conditions' );
		$this->cashback_type       = get_option( 'wc_af_cashback_type' );

		add_action( 'woocommerce_order_status_completed', array( $this, 'cashback_after_completed_order' ) );
	}

	/**
	 * Cashback after order completion
	 *
	 * @param $order_id WC_Order
	 *
	 * @since 1.0.0
	 */
	public function cashback_after_completed_order( $order_id ) {
		if ( is_user_logged_in() ) {
			$order = wc_get_order( $order_id );
			if ( $this->allow_cashback == 'yes' ) {
				if ( $this->cashback_conditions == 'cart' ) {
					$cashback_amount = $this->calculate_cashback_for_cart( $order_id );
					$details = sprintf( __( 'Cashback after complete order #%s', 'wc-accounts-funds' ), $order->get_user_id());
					wc_accounts_funds_add_funds( $order->get_user_id(), $cashback_amount, $details );
					$order->add_order_note( sprintf( __( '#%s Customer gets a cash back amount of %s', 'wc-accounts-funds' ), $order->get_user_id(), wc_price( $cashback_amount ) ) );
				} elseif ( $this->cashback_conditions == 'product' ) {
					$cashback_amount = $this->calculate_cashback_for_product( $order_id );
					$details = sprintf( __( 'Cashback after complete order #%s', 'wc-accounts-funds' ), $order->get_user_id());
					wc_accounts_funds_add_funds( $order->get_user_id(), $cashback_amount, $details );
					$order->add_order_note( sprintf( __( '#%s Customer gets a cash back amount of %s', 'wc-accounts-funds' ), $order->get_user_id(), wc_price( $cashback_amount ) ) );
				}
			}

			//for cashback rules
			if ( $rule = $this->is_valid_cashback_rules( $order->get_total() ) ) {
				if ( $rule->cashback_for == 'cart' ) {
					$cashback_amount = $this->calculate_cashback_for_cart_by_cashback_rules( $rule, $order );
					$details = sprintf( __( 'Cashback after complete order #%s', 'wc-accounts-funds' ), $order->get_user_id());
					wc_accounts_funds_add_funds( $order->get_user_id(), $cashback_amount, $details );
					$order->add_order_note( sprintf( __( '#%s Customer gets a cash back amount of %s', 'wc-accounts-funds' ), $order->get_user_id(), wc_price( $cashback_amount ) ) );
				} elseif ( $rule->cashback_for == 'product' ) {
					$cashback_amount = $this->calculate_cashback_for_product_by_cashback_rules( $rule, $order );
					$details = sprintf( __( 'Cashback after complete order #%s', 'wc-accounts-funds' ), $order->get_user_id());
					wc_accounts_funds_add_funds( $order->get_user_id(), $cashback_amount, $details );
					$order->add_order_note( sprintf( __( '#%s Customer gets a cash back amount of %s', 'wc-accounts-funds' ), $order->get_user_id(), wc_price( $cashback_amount ) ) );
				}
			}
		}
	}

	/**
	 * Check if any cashback rules matched
	 * 
	 * @since 1.0.0
	 * 
	 * @param int $order_total
	 *
	 * @return object|bool
	 */
	public function is_valid_cashback_rules( $order_total ) {
		global $wpdb;
		$table_name  = $wpdb->prefix . 'cashback_rules';
		$rule = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE 1 and `price_from` <= %d and `price_to` >= %d and `status` = '%s'", $order_total, $order_total, 'publish' ) );
		if( ! empty( $rule ) ) {
			return $rule;
		}
		return false;
	}

	/**
	 * Calculate cash back for cart
	 *
	 * @param $order_id
	 *
	 * @return int $cashback_amount
	 * @since 1.0.0
	 */
	public function calculate_cashback_for_cart( $order_id ) {
		$cashback_amount = 0;
		$order           = wc_get_order( $order_id );
		if ( $this->cashback_type == 'fixed' ) {
			$cashback_amount = floatval( get_option( 'wc_af_cashback_amount' ) );
		} elseif ( $this->cashback_type == 'percentage' ) {
			$cart_total      = $order->get_total();
			$cashback_amount = ( $cart_total * floatval( get_option( 'wc_af_cashback_amount' ) ) ) / 100;
		}

		return $cashback_amount;
	}

	/**
	 * Calculate cashback rules cash back for cart 
	 *
	 * @param object $order
	 * @param object $rule
	 *
	 * @return int $cashback_amount
	 * @since 1.0.0
	 */
	public function calculate_cashback_for_cart_by_cashback_rules( $rule, $order ) {
		$cashback_amount = 0;
		if ( $rule->cashback_type == 'fixed' ) {
			$cashback_amount = floatval( $rule->amount );
		} elseif ( $rule->cashback_type == 'percentage' ) {
			$cart_total      = $order->get_total();
			$cashback_amount = ( $cart_total * floatval( $rule->amount ) ) / 100;
		}
		return $cashback_amount;
	}

	/**
	 * Calculate cash back for product
	 *
	 * @param $order_id
	 *
	 * @return float|int
	 * @since 1.0.0
	 */
	public function calculate_cashback_for_product( $order_id ) {
		$cashback_amount = 0;
		$order           = wc_get_order( $order_id );

		$order_items = $order->get_items();
		foreach ( $order_items as $item_id => $item ) {
			$item_data        = $item->get_data();
			$product_id       = $item_data['variation_id'] ? $item_data['variation_id'] : $item_data['product_id'];
			$product          = wc_get_product( $product_id );
			$product_quantity = $item_data['quantity'];

			//calculate product price cart when tax avaialable or not
			if ( get_option( 'woocommerce_tax_display_cart' ) == 'incl' ) {
				$product_price = wc_get_price_including_tax( $product );
			} else {
				$product_price = wc_get_price_excluding_tax( $product );
			}

			$product_wise_cashback_enable = get_post_meta( $product->get_id(), '_is_apply_cash_back', true );
			//check if the product label cashback enabled or not and then calculate accordingly
			if ( isset( $product_wise_cashback_enable ) && $product_wise_cashback_enable == 'yes' ) {
				$product_wise_cashback_type   = get_post_meta( $product->get_id(), '_cashback_type', true );
				$product_wise_cashback_amount = get_post_meta( $product->get_id(), '_cashback_amount', true );
				if ( $product_wise_cashback_type == 'fixed' ) {
					$cashback_amount += $product_wise_cashback_amount * $product_quantity;
				} elseif ( $product_wise_cashback_type == 'percentage' ) {
					$product_wise_cashback_calculate = ( $product_price * $product_wise_cashback_amount ) / 100;
					$cashback_amount                 += $product_wise_cashback_calculate * $product_quantity;
				}
			} else {
				//product label cashback is disbled so the cashback calculation will be global rule wise
				$global_cashback_type   = get_option( 'wc_af_cashback_type' );
				$global_cashback_amount = get_option( 'wc_af_cashback_amount' );
				if ( $global_cashback_type == 'percentage' ) {
					$cashback_amount_calculate = ( $product_price * $global_cashback_amount ) / 100;
					$cashback_amount           += $cashback_amount_calculate * $product_quantity;
				} elseif ( $global_cashback_amount == 'fixed' ) {
					$cashback_amount += $global_cashback_amount * $product_quantity;
				}
			}
		}

		return $cashback_amount;

	}

	/**
	 * Calculate cash back for product
	 *
	 * @param object order
	 * @param object $rule
	 *
	 * @return float|int
	 * @since 1.0.0
	 */
	public function calculate_cashback_for_product_by_cashback_rules( $rule, $order ) {
		$cashback_amount = 0;
		$order_items = $order->get_items();
		foreach ( $order_items as $item_id => $item ) {
			$item_data        = $item->get_data();
			$product_id       = $item_data['variation_id'] ? $item_data['variation_id'] : $item_data['product_id'];
			$product          = wc_get_product( $product_id );
			$product_quantity = $item_data['quantity'];
			//calculate product price cart when tax avaialable or not
			if ( get_option( 'woocommerce_tax_display_cart' ) == 'incl' ) {
				$product_price = wc_get_price_including_tax( $product );
			} else {
				$product_price = wc_get_price_excluding_tax( $product );
			}
			$product_wise_cashback_type   = $rule->cashback_type;
			$product_wise_cashback_amount = $rule->amount;
			if ( $product_wise_cashback_type == 'fixed' ) {
				$cashback_amount += $product_wise_cashback_amount * $product_quantity;
			} elseif ( $product_wise_cashback_type == 'percentage' ) {
				$product_wise_cashback_calculate = ( $product_price * $product_wise_cashback_amount ) / 100;
				$cashback_amount                 += $product_wise_cashback_calculate * $product_quantity;
			}
		}
		return $cashback_amount;
	}
}

new WC_Accounts_Funds_Cashback_handler();
