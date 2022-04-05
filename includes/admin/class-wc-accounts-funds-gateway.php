<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Account_Funds_Gateway class
 * Extends WC_Payment_Gateway
 */
class WC_Account_Funds_Gateway extends WC_Payment_Gateway {

	/**
	 * class Constructor
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->id           = 'wc_account_funds';
		$this->method_title = __( 'WC Accounts Funds', 'wc-accounts-funds' );
		$this->title        = __( 'WC Accounts Funds', 'wc-accounts-funds' );
		$this->has_fields   = true;
		//$this->icon               = apply_filters( 'woocommerce_custom_gateway_icon', 'wc-accounts-funds' );
		$this->has_fields         = false;
		$this->method_description = __( 'Payment gateway for logged in user\'s account funds.', 'wc-accounts-funds' );
		//load the form fields
		$this->init_form_fields();
		$this->init_settings();
		//Get Settings
		$this->enabled      = $this->get_option( 'enabled' );
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		//update payment method description when it discounts enable from settings
		if ( get_option( 'wc_af_apply_discounts_enable' ) == 'yes' ) {
			$discount_type     = get_option( 'wc_af_discount_type' );
			$discount_amount   = ( $discount_type == 'fixed_price' ) ? wc_price( get_option( 'wc_af_discount_amount' ) ) : get_option( 'wc_af_discount_amount' ) . ' % ';
			$this->description .= sprintf( __( ' Use accounts funds as a payment method and get discount amount of %s', 'wc-accounts-funds' ), $discount_amount );
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		//todo support for woocommerce subscription plugin

	}

	/**
	 * Form fields for the payment method
	 * @since 1.0.0
	 *
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable/Disable', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable', 'wc-accounts-funds' ),
				'default' => 'yes',
			),
			'title'        => array(
				'title'       => __( 'Title', 'wc-accounts-funds' ),
				'type'        => 'text',
				'description' => __( 'Payment method title that the customer will see on your checkout', 'wc-accounts-funds' ),
				'default'     => __( 'Account Funds', 'wc-accounts-funds' ),
				'desc_tip'    => true
			),
			'description'  => array(
				'title'       => __( 'Description', 'wc-accounts-funds' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout', 'wc-accounts-funds' ),
				'default'     => __( 'Pay with Account Funds', 'wc-accounts-funds' ),
				'desc_tip'    => true
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'wc-accounts-funds' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout', 'wc-accounts-funds' ),
				'default'     => __( 'Pay with Account Funds', 'wc-accounts-funds' ),
				'desc_tip'    => true,
			)
		);
	}

	/**
	 * Check if the payment method is available for using
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_available() {
		$is_available = ( 'yes' === $this->enabled );

		if ( WC()->cart ) {
			$available_accounts_funds = get_user_accounts_funds( null, false );
			$cart_total               = $this->get_order_total();
			if ( $available_accounts_funds < $cart_total ) {
				$is_available = false;
			} elseif ( WC_Accounts_Funds_Cart_handler::cart_contains_cashback() ) {
				$is_available = false;
			} elseif ( WC_Accounts_Funds_Cart_handler::using_accounts_funds() ) {
				$is_available = false;
			}elseif(WC_Accounts_Funds_Cart_handler::cart_having_topup_product()){
				$is_available = false;
			} else {
				$is_available = true;
			}
		}

		return $is_available;
	}

	/**
	 * Process payment
	 *
	 * @param int $order_id Order ID
	 *
	 * @return array
	 * @throws WC_Data_Exception
	 * @since 1.0.0
	 */
	public function process_payment( $order_id ) {
		$order           = wc_get_order( $order_id );
		$current_user_id = $order->get_customer_id();
		$cart_total      = $order->get_total();

		//check if the user is logged in. If the user is not logged in then this will not happen
		if ( ! is_user_logged_in() ) {
			wc_add_notice( __( 'You are not logged in right now. Without logged in this payment method will not work', 'wc-accounts-funds' ), 'error' );

			return;
		}

		//check available funds to process the payment
		$available_accounts_funds = get_user_meta( $current_user_id, 'wc_user_account_funds', true );
		if ( $cart_total > $available_accounts_funds ) {
			wc_add_notice( __( 'Your accounts funds balance is insufficient for payment. Please select other payment method', 'wc-accounts-funds' ), 'error' );

			return;
		}

		//remove funds from customer accounts funds
		update_post_meta( $order_id, '_accounts_funds_used', $cart_total );
		update_post_meta( $order, '_accounts_funds_removed', 1 );
		$order->set_total( 0 );

		//make the payment complete
		$order->payment_complete();

		//clear the cart
		WC()->cart->empty_cart();

		//Return to thank you page
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}

}
