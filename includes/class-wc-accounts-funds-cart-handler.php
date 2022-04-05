<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Accounts_Funds_Cart_handler {
	public $partial_payment;
	public $give_discount;

	/**
	 * Class constructor
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->partial_payment = get_option( 'wc_af_allow_partial_payments', 'yes' );
		$this->give_discount   = get_option( 'wc_af_apply_discounts_enable', 'yes' );

		//notice for partial payment in cart and checkout
		add_action( 'woocommerce_before_cart', array( $this, 'accounts_funds_use_notice' ), 6 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'accounts_funds_use_notice' ), 6 );
		//use account funds for payment
		add_action( 'wp', array( $this, 'maybe_use_account_funds' ) );

		//use accounts funds for payment
		add_action( 'woocommerce_review_order_before_order_total', array( $this, 'cart_display_used_funds' ) );
		add_action( 'woocommerce_cart_totals_before_order_total', array( $this, 'cart_display_used_funds' ) );
		add_filter( 'woocommerce_cart_total', array( $this, 'cart_display_total' ) );
		add_filter( 'woocommerce_calculated_total', array( $this, 'cart_calculated_total' ) );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'cart_calculate_totals_from_session' ), 99 );

		//discount settings when using partial accounts funds and accounts funds as payment gateway
		add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'wc_accounts_funds_discount_data' ), 10, 2 );
		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'wc_accounts_funds_get_discount_amount' ), 10, 5 );
		//change coupon message and coupon label and coupon html
		add_filter( 'woocommerce_coupon_message', array( $this, 'wc_accounts_funds_discount_coupon_message' ), 10, 3 );
		add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'wc_accounts_funds_discount_coupon_label' ) );
		add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'wc_accounts_funds_discount_coupon_html' ), 10, 2 );

		//for topup and cashback products
		add_action( 'woocommerce_cashback_add_to_cart', array( $this, 'cashback_add_to_cart' ) );
		add_filter( 'woocommerce_add_cart_item', array( $this, 'add_topup_to_cart' ), 10, 1 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 3 );


	}

	/**
	 * Showing a notice for paying using accounts funds
	 * @since 1.0.0
	 */
	public function accounts_funds_use_notice() {
		if ( $this->partial_payment === 'no' || self::using_accounts_funds() || ! self::user_can_apply_funds() ) {
			return;
		}
		if ( $this->partial_payment == 'yes' && ( get_option( 'wc_af_restricts_payments' ) == 'yes' ) ) {
			$minimum_funds_available  = floatval( get_option( 'wc_af_minimum_account_funds' ) );
			$available_accounts_funds = get_user_accounts_funds( get_current_user_id(), false );
			if ( $minimum_funds_available > $available_accounts_funds ) {
				return;
			}
		}
		if ( self::cart_having_topup_product() ) {
			return;
		}
		if ( get_user_accounts_funds( get_current_user_id(), false ) <= 0 ) {
			return;
		}

		$message = '<div class="woocommerce-info wc-accounts-funds-apply-notice">';
		$message .= '<form class="wc-accounts-funds-apply" method="post">';
		$message .= '<input type="submit" class="button wc-account-funds-apply-button" name="wc_accounts_funds_apply" value="' . __( 'Pay by Account Funds', 'wc-accounts-funds' ) . '" />';
		$message .= sprintf( __( 'You have <strong>%s</strong> worth of funds on your account.', 'wc-accounts-funds' ), get_user_accounts_funds() );
		if ( 'yes' === get_option( 'wc_af_apply_discounts_enable' ) ) {
			$message .= '<br/><em>' . sprintf( __( 'Use account funds and get a %s discount on your order.', 'wc-account-funds' ), self::calculate_discount_amount() ) . '</em>';
		}
		$message .= '</form>';
		$message .= '</div>';

		echo $message;


	}

	/**
	 * Calculate discount amount
	 * @return string $discount_amount
	 * @since 1.0.0
	 */
	public static function calculate_discount_amount() {
		$discount_amount = get_option( 'wc_af_discount_amount' );
		$discount_type   = get_option( 'wc_af_discount_type' );
		if ( $discount_type == 'fixed_price' ) {
			return wc_price( $discount_amount );
		} else {
			return;
		}
	}

	/**
	 * Use accounts funds for payment
	 * @since 1.0.0
	 */
	public function maybe_use_account_funds() {
		if ( $this->partial_payment == 'no' ) {
			return;
		}
		if ( WC()->cart ) {
			if ( self::cart_having_topup_product() ) {
				return;
			}
		}

		if ( isset( $_POST['wc_accounts_funds_apply'] ) && self::user_can_apply_funds() ) {
			WC()->session->set( 'use-accounts-funds', true );
		}

		if ( ! empty( $_GET['remove_accounts_funds'] ) ) {
			WC()->session->set( 'use-accounts-funds', false );
			WC()->session->set( 'used-accounts-funds', false );
			wp_redirect( esc_url( remove_query_arg( 'remove_accounts_funds' ) ) );
			exit;
		}

		//todo apply discounts when applying accounts funds
		if ( $this->using_accounts_funds() ) {
			$this->apply_accounts_funds_discount();
		}

	}


	/**
	 * Check if user can actually apply funds to cart
	 * @return bool
	 * @since 1.0.0
	 */
	public static function user_can_apply_funds() {
		$apply_partial_payments = get_option( 'wc_af_allow_partial_payments' );
		$can_apply_funds        = ( $apply_partial_payments == 'yes' ) ? true : false;

		if ( ! is_user_logged_in() || self::cart_contains_cashback() || self::cart_contains_subscription() ) {
			$can_apply_funds = false;
		}

		if ( ! get_user_accounts_funds( get_current_user_id(), false ) ) {
			$can_apply_funds = false;
		}

		return $can_apply_funds;

	}

	/**
	 * Check if the cart having any cash back or top up products
	 * @return bool
	 * @since 1.0.0
	 */
	public static function cart_contains_cashback() {
		if ( WC()->cart instanceof WC_Cart ) {
			foreach ( WC()->cart->get_cart() as $single_item ) {
				if ( $single_item['data']->is_type( 'cashback' ) || $single_item['data']->is_type( 'topup' ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * check if cart is having any subscription product
	 * @return bool
	 * @since 1.0.0
	 */
	public static function cart_contains_subscription() {
		return ( WC()->cart instanceof WC_Cart && class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() );
	}

	/**
	 * Check if the accounts funds in using right now and set session
	 * @since 1.0.0
	 */
	public static function using_accounts_funds() {
		return ! is_null( WC()->session ) && WC()->session->get( 'use-accounts-funds' ) && self::user_can_apply_funds();
	}

	/**
	 * Show amounts of funds used for payment
	 * @since 1.0.0
	 */
	public function cart_display_used_funds() {
		if ( $this->using_accounts_funds() ) {
			$accounts_funds_amount = self::used_accounts_funds_amount();
			if ( $accounts_funds_amount > 0 ) {
				?>
                <tr class="order-discount account-funds-discount">
                    <th><?php _e( 'Account Funds', 'wc-accounts-funds' ); ?></th>
                    <td>-<?php echo wc_price( $accounts_funds_amount ); ?> <a
                                href="<?php echo esc_url( add_query_arg( 'remove_accounts_funds', true, get_permalink( is_cart() ? wc_get_page_id( 'cart' ) : wc_get_page_id( 'checkout' ) ) ) ); ?>"><?php _e( '[Remove]', 'wc-accounts-funds' ); ?></a>
                    </td>
                </tr>
				<?php
			}
		}
	}

	/**
	 * Amount of funds being applied
	 * @return float
	 * @since 1.0.0
	 */
	public static function used_accounts_funds_amount() {
		return WC()->session->get( 'used-accounts-funds' );
	}

	/**
	 * Calculate cart total
	 *
	 * @param string $total
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function cart_display_total( $total ) {
		if ( self::using_accounts_funds() ) {
			return wc_price( WC()->cart->total );
		}

		return $total;
	}

	/**
	 * Calculated cart total
	 *
	 * @param $total
	 *
	 * @return mixed
	 * @since 1.0.0
	 */
	public function cart_calculated_total( $total ) {
		if ( self::using_accounts_funds() ) {
			$accounts_funds_amount = min( $total, get_user_accounts_funds( get_current_user_id(), false ) );
			$total                 = $total - $accounts_funds_amount;
			WC()->session->set( 'used-accounts-funds', $accounts_funds_amount );
		}

		return $total;
	}

	/**
	 * Calculate totals from sessions and remove the discount code
	 * @since 1.0.0
	 */
	public function cart_calculate_totals_from_session() {
		if ( self::accounts_funds_payment_gateway_apply() ) {
			$this->apply_accounts_funds_discount();
			WC()->cart->calculate_totals();
		} elseif ( self::using_accounts_funds() && self::get_accounts_funds_discount_code() && WC()->cart->has_discount( self::get_accounts_funds_discount_code() ) ) {
			WC()->cart->remove_coupon( self::get_accounts_funds_discount_code() );
		}
	}

	/**
	 * prepare discount code
	 *
	 * @param $code
	 * @param $data
	 *
	 * @return array $data
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_discount_data( $data, $code ) {
		if ( is_admin() ) {
			return $data;
		}

		if ( 'no' == $this->give_discount || strtolower( $code ) != self::get_accounts_funds_discount_code() ) {
			return $data;
		}

		if ( self::cart_having_topup_product() ) {
			return $data;
		}

		//generated data
		$data = array(
			'id'                         => true,
			'type'                       => ( get_option( 'wc_af_discount_type' ) == 'fixed_price' ) ? 'fixed_cart' : 'percent',
			'discount_type'              => ( get_option( 'wc_af_discount_type' ) == 'fixed_price' ) ? 'fixed_cart' : 'percent',
			'amount'                     => floatval( get_option( 'wc_af_discount_amount' ) ),
			'coupon_amount'              => floatval( get_option( 'wc_af_discount_amount' ) ),
			'individual_use'             => false,
			'product_ids'                => array(),
			'exclude_product_ids'        => array(),
			'usage_limit'                => '',
			'usage_count'                => '',
			'expiry_date'                => '',
			'apply_before_tax'           => 'yes',
			'free_shipping'              => false,
			'product_categories'         => array(),
			'exclude_product_categories' => array(),
			'exclude_sale_items'         => false,
			'minimum_amount'             => '',
			'maximum_amount'             => '',
			'customer_email'             => '',
		);

		return $data;
	}

	/**
	 * Get unique discounted code and returns if set
	 * @since 1.0.0
	 */
	public static function get_accounts_funds_discount_code() {
		return WC()->session->get( 'wc_accounts_funds_discounted_code' );
	}

	/**
	 * Get coupon discount amount when discount is in percentage
	 *
	 * @param float $discount
	 * @param float $discounting_amount
	 * @param object $cart_item
	 * @param bool $single
	 * @param WC_Coupon $coupon
	 *
	 * @return float $discount
	 * @since 1.0.0
	 */

	public function wc_accounts_funds_get_discount_amount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
		$discount_code   = $coupon->get_code();
		$discount_amount = $coupon->get_amount();
		if ( $this->give_discount == 'no' || strtolower( $discount_code ) != self::get_accounts_funds_discount_code() ) {
			return $discount;
		}

		if ( get_option( 'wc_af_discount_type' ) == 'percentage' ) {
			if ( get_user_accounts_funds( get_current_user_id(), false ) < WC()->cart->subtotal_ex_tax ) {
				$discount_percent = get_user_accounts_funds( get_current_user_id(), false ) / WC()->cart->subtotal_ex_tax;
			} else {
				$discount_percent = 1;
			}

			$discount *= $discount_percent;
		}

		return $discount;
	}

	/**
	 * Apply discount funds in cart
	 * @since 1.0.0
	 */
	public function apply_accounts_funds_discount() {
		//check if discount is already applied and return
		if ( ! WC()->cart ) {
			return;
		} elseif ( get_option( 'wc_af_apply_discounts_enable' ) == 'no' ) {
			return;
		} elseif ( WC()->cart->has_discount( self::get_accounts_funds_discount_code() ) ) {
			return;
		} elseif ( ! self::user_can_apply_funds() && ! self::accounts_funds_payment_gateway_apply() ) {
			return;
		}
		$discount_code = self::wc_accounts_funds_generate_discount_code();
		WC()->cart->add_discount( $discount_code );


	}

	/**
	 * Check if the payment gateway is chosen as wc_account_funds
	 * @since 1.0.0
	 */
	public function accounts_funds_payment_gateway_apply() {
		$available_payment_gateways = WC()->payment_gateways->get_available_payment_gateways();

		return ( isset( $available_payment_gateways['wc_account_funds'] ) && $available_payment_gateways['wc_account_funds']->chosen ) || ( ! empty( $_POST['payment_method'] ) && $_POST['payment_method'] == 'wc_account_funds' );
	}

	/**
	 * Generate discount code
	 * @return string $discount_code
	 * @since 1.0.0
	 */
	public static function wc_accounts_funds_generate_discount_code() {
		$discount_code = sprintf( 'accounts_funds_discount_%s_%s', get_current_user_id(), mt_rand() );
		WC()->session->set( 'wc_accounts_funds_discounted_code', $discount_code );

		return $discount_code;
	}

	/**
	 * Change default coupon applied message to custom message
	 *
	 * @param $message
	 * @param $message_code
	 * @param Object $coupon the WC_Coupon
	 *
	 * @return string $message
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_discount_coupon_message( $message, $message_code, $coupon ) {
		if ( $this->give_discount == 'no' ) {
			return $message;
		}

		$discount_code = $coupon->get_code();
		if ( WC_Coupon::WC_COUPON_SUCCESS == $message_code && self::get_accounts_funds_discount_code() == $discount_code ) {
			$message = ! empty( get_option( 'wc_af_discount_msg' ) ) ? get_option( 'wc_af_discount_msg' ) : __( 'Applied discount for paying with accounts funds', 'wc-accounts-funds' );
		}

		return $message;
	}

	/**
	 * Add a label for discount coupon
	 *
	 * @param string $label
	 *
	 * @return string $label
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_discount_coupon_label( $label ) {
		if ( $this->give_discount == 'no' ) {
			return $label;
		}
		$label = ! empty( get_option( 'wc_af_discount_label' ) ) ? get_option( 'wc_af_discount_label' ) : __( 'Discount Label', 'wc-accounts-funds' );

		return $label;

	}

	/**
	 * Remove anchor from the coupon html
	 *
	 * @param string $html
	 * @param $coupon
	 *
	 * @return string $html
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_discount_coupon_html( $html, $coupon ) {
		if ( $this->give_discount == 'no' ) {
			return $html;
		}
		$discount_code = $coupon->get_code();
		if ( self::get_accounts_funds_discount_code() == $discount_code ) {
			$html = current( explode( '<a', $html ) );
		}

		return $html;

	}

	/**
	 * Showing add to cart
	 * @since 1.0.0
	 */
	public function cashback_add_to_cart() {
		woocommerce_simple_add_to_cart();
	}

	/**
	 * Adjust the price
	 *
	 * @param mixed $cart_item
	 *
	 * @return array cart item
	 */
	public function add_topup_to_cart( $cart_item ) {
		if ( ! empty( $cart_item['top_up_amount'] ) ) {
			$cart_item['data']->set_price( $cart_item['top_up_amount'] );
			$cart_item['variation'] = array();
		}

		return $cart_item;
	}

	/**
	 * Get data from the session and add to the cart item's meta
	 *
	 * @param mixed $cart_item
	 * @param mixed $values
	 * @param $cart_item_key
	 *
	 * @return array cart item
	 */
	public function get_cart_item_from_session( $cart_item, $values, $cart_item_key ) {
		if ( ! empty( $values['top_up_amount'] ) ) {
			$cart_item['top_up_amount'] = $values['top_up_amount'];
			$cart_item                  = $this->add_topup_to_cart( $cart_item );
		}

		return $cart_item;
	}

	/**
	 * Check if the cart having top up product
	 * @return bool
	 * @since 1.0.0
	 */
	public static function cart_having_topup_product() {
		$cart_items        = WC()->cart->get_cart();
		$top_up_product_id = '';
		foreach ( $cart_items as $single ) {
			$top_up_product_id = $single['data']->get_ID();
		}
		if ( $top_up_product_id == get_option( 'wc_accounts_funds_topup_product' ) ) {
			return true;
		}

		return false;
	}
}

new WC_Accounts_Funds_Cart_handler();
