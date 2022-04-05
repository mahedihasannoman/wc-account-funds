<?php
defined( 'ABSPATH' ) || exit();

/**
 * WC_Accounts_funds_Settings_General
 * @since 1.0.0
 */
class WC_Accounts_funds_Settings_General extends WC_Settings_Page {

	/**
	 * Constructor
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = __( 'General', 'wc-accounts-funds' );

		add_filter( 'wc_accounts_funds_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'wc_accounts_funds_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'wc_accounts_funds_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get settings array
	 * @return array
	 * @since 1.0.0
	 */
	public function get_settings() {
		global $woocommerce, $wp_roles;
		$settings = array(
			[
				'title' => __( 'Discount Settings', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how discounts will work.', 'wc-accounts-funds' ),
				'id'    => 'section_account_discount'
			],
			[
				'title'   => __( 'Apply Discounts', 'wc-accounts-funds' ),
				'id'      => 'wc_af_apply_discounts_enable',
				'desc'    => __( 'Apply discount when customer will buy a product using account funds', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'title'   => __( 'Discount Type', 'wc-accounts-funds' ),
				'id'      => 'wc_af_discount_type',
				'desc'    => __( 'Select type of discount will apply when paying using account funds', 'wc-accounts-funds' ),
				'type'    => 'select',
				'default' => 'no',
				'options' => array(
					'fixed_price' => __( 'Fixed Price', 'wc-accounts-funds' ),
					'percentage'  => __( 'Percentage', 'wc-accounts-funds' )
				),
				'class'   => 'wc-af-field wc-af-select2'
			],
			[
				'title' => __( 'Discount Amount', 'wc-accounts-funds' ),
				'id'    => 'wc_af_discount_amount',
				'desc'  => __( 'Amount of discounts will apply when checkout. Only numbers will take, don\'t give percentage sign', 'wc-accounts-funds' ),
				'type'  => 'number',
				'class' => 'wc-af-field wc-af-text'
			],
			[
				'title'   => __( 'Custom Discount Message', 'wc-accounts-funds' ),
				'id'      => 'wc_af_discount_msg',
				'desc'    => __( 'Custom Discount message when discount applied in cart. This will override the default \'Coupon Applied Successfully\' message', 'wc-accounts-funds' ),
				'type'    => 'text',
				'class'   => 'wc-af-field wc-af-text',
				'default' => __( 'Discount applied for paying with accounts funds', 'wc-accounts-funds' ),
			],
			[
				'title'   => __( 'Custom Discount Label', 'wc-accounts-funds' ),
				'id'      => 'wc_af_discount_label',
				'desc'    => __( 'Custom Discount label when discount applied in cart.', 'wc-accounts-funds' ),
				'type'    => 'text',
				'class'   => 'wc-af-field wc-af-text',
				'default' => __( 'Discount Coupon', 'wc-accounts-funds' ),
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_account_discount'
			],
			[
				'title' => __( 'Funding Settings', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how account funding will work.', 'wc-accounts-funds' ),
				'id'    => 'section_account_funding'
			],
			[
				'title'   => __( 'Apply Top Up', 'wc-accounts-funds' ),
				'id'      => 'wc_af_apply_top_up',
				'desc'    => __( 'Enable / Disable top up for customers own account', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'title' => __( 'Minimum amount', 'wc-accounts-funds' ),
				'id'    => 'wc_af_top_up_min',
				'desc'  => __( 'Minimum amounts of top up. Can\'t top up below this amount', 'wc-accounts-funds' ),
				'type'  => 'number',
				'class' => 'wc-af-field wc-af-text'
			],
			[
				'title' => __( 'Maximum amount', 'wc-accounts-funds' ),
				'id'    => 'wc_af_top_up_max',
				'desc'  => __( 'Maximum amounts of top up. Can\'t top up over this amount', 'wc-accounts-funds' ),
				'type'  => 'number',
				'class' => 'wc-af-field wc-af-text'
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_account_funding'
			],
			[
				'title' => __( 'Fund Transfer Settings', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how account fund transfer will work.', 'wc-accounts-funds' ),
				'id'    => 'section_account_fund_transfer'
			],
			[
				'title'   => __( 'Allow Fund Transfer', 'wc-accounts-funds' ),
				'id'      => 'wc_af_allow_fund_transfer',
				'desc'    => __( 'If checked user will be able to transfer fund to another user.', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'title' 	=> __( 'Minimum Transfer Amount', 'wc-accounts-funds' ),
				'id'   		=> 'wc_af_min_transfer_amount',
				'desc'  	=> __( 'Enter minimum transfer amount', 'wc-accounts-funds' ),
				'type'  	=> 'number',
				'default'	=> 0,
				'class' 	=> 'wc-af-field wc-af-text'
			],
			[
				'title' 	=> __( 'Transfer charge type', 'wc-accounts-funds' ),
				'id'   		=> 'wc_af_transfer_charge_type',
				'desc'  	=> __( 'Select transfer charge type percentage or fixed', 'wc-accounts-funds' ),
				'type'  	=> 'select',
				'options' => array(
					'fixed' => __( 'Fixed', 'wc-accounts-funds' ),
					'percentage'  => __( 'Percentage', 'wc-accounts-funds' )
				),
				'class'   => 'wc-af-field wc-af-select2'
			],
			[
				'title' 	=> __( 'Transfer charge Amount', 'wc-accounts-funds' ),
				'id'   		=> 'wc_af_transfer_charge_amount',
				'desc'  	=> __( 'Enter transfer charge amount', 'wc-accounts-funds' ),
				'type'  	=> 'number',
				'default'	=> 0,
				'class' 	=> 'wc-af-field wc-af-text'
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_account_fund_transfer'
			],
			[
				'title' => __( 'Referrals', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how account fund referrals will work.', 'wc-accounts-funds' ),
				'id'    => 'section_account_fund_referrals'
			],
			[
				'title'   => __( 'Enable Referrals', 'wc-accounts-funds' ),
				'id'      => 'wc_af_allow_referrals',
				'desc'    => __( 'If checked user will be able to refer other people in order to get funds.', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_account_fund_referrals'
			],
			[
				'title' => __( 'Referring Visitors', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how account fund will work for referring visitors.', 'wc-accounts-funds' ),
				'id'    => 'section_account_fund_referring_visitors'
			],
			[
				'title' 	=> __( 'Amount', 'wc-accounts-funds' ),
				'id'   		=> 'wc_af_referring_visitors_amount',
				'desc'  	=> __( 'Enter amount which will be credited to the user account for daily visits', 'wc-accounts-funds' ),
				'type'  	=> 'number',
				'default'	=> 0,
				'class' 	=> 'wc-af-field wc-af-text'
			],
			[
				'title' 	=> __( 'Description', 'wc-accounts-funds' ),
				'id'   		=> 'wc_af_referring_visitors_description',
				'type'  	=> 'textarea',
				'default'	=> 'Balance credited for referring a visitor',
				'class' 	=> 'wc-af-field wc-af-text'
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_account_fund_referring_visitors'
			],
			[
				'title' => __( 'Referring Signups', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how account fund will work for referring signups.', 'wc-accounts-funds' ),
				'id'    => 'section_account_fund_referring_signups'
			],
			[
				'title' 	=> __( 'Amount', 'wc-accounts-funds' ),
				'id'   		=> 'wc_af_referring_signups_amount',
				'desc'  	=> __( 'Enter amount which will be credited to the user account for daily signup', 'wc-accounts-funds' ),
				'type'  	=> 'number',
				'default'	=> 0,
				'class' 	=> 'wc-af-field wc-af-text'
			],
			[
				'title' 	=> __( 'Description', 'wc-accounts-funds' ),
				'id'   		=> 'wc_af_referring_signups_description',
				'type'  	=> 'textarea',
				'default'	=> 'Balance credited for referring a signup',
				'class' 	=> 'wc-af-field wc-af-text'
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_account_fund_referring_signups'
			],
			[
				'title' => __( 'Partial Payment Settings', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how partial payments will work.', 'wc-accounts-funds' ),
				'id'    => 'section_account_partial_payment'
			],
			[
				'title'   => __( 'Allow Partial Payment', 'wc-accounts-funds' ),
				'id'      => 'wc_af_allow_partial_payments',
				'desc'    => __( 'Allow customers to pay partial using account funds and pay difference via normal payment gateway', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'title'   => __( 'Restricts payments', 'wc-accounts-funds' ),
				'id'      => 'wc_af_restricts_payments',
				'desc'    => __( 'Check to restrict customer partial payments if accounts have less amount than minimum', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'title' => __( 'Minimum funds for partial payment', 'wc-accounts-funds' ),
				'id'    => 'wc_af_minimum_account_funds',
				'desc'  => __( 'Minimum funds for paying from account funds', 'wc-accounts-funds' ),
				'type'  => 'number',
				'class' => 'wc-af-field wc-af-text',

			],
			[
				'type' => 'sectionend',
				'id'   => 'section_account_partial_payment'
			],
			[
				'title' => __( 'CashBack Settings', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how cash back will work .', 'wc-accounts-funds' ),
				'id'    => 'section_account_cashback_settings'
			],
			[
				'title'   => __( 'Allow Cash back', 'wc-accounts-funds' ),
				'id'      => 'wc_af_allow_cash_back',
				'desc'    => __( 'Allow customers to  get cash back when they buy a cash back enabled product', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
//			[
//				'title'   => __( 'Multiple Cashback conditions', 'wc-accounts-funds' ),
//				'id'      => 'wc_af_check_multiple_cashback',
//				'desc'    => __( 'Select if customer is able to get multiple cash back or not', 'wc-accounts-funds' ),
//				'type'    => 'select',
//				'options' => array(
//					'yes' => __( 'Yes', 'wc-accounts-funds' ),
//					'no'  => __( 'No', 'wc-accounts-funds' )
//				),
//				'default' => 'no',
//				'class'   => 'wc-af-field wc-af-select'
//			],
			[
				'title'   => __( 'Cash back conditions', 'wc-accounts-funds' ),
				'id'      => 'wc_af_multiple_cashback_conditions',
				'type'    => 'select',
				'options' => array(
					'product' => __( 'Product specific cash back', 'wc-accounts-funds' ),
					'cart'    => __( 'Cart cash back', 'wc-accounts-funds' )
				),
				'class'   => 'wc-af-field wc-af-select'
			],
			[
				'title' => __('Cahshback Type','wc-accounts-funds'),
				'id' => 'wc_af_cashback_type',
				'type' => 'select',
				'options'=> array(
					'fixed' => __('Fixed Amount','wc-accounts-funds'),
					'percentage' => __('Percentage','wc-accounts-funds'),
				),
				'class' => 'wc-af-field wc-af-select',
			],
			[
				'title' => __('Cashback amount','wc-accounts-funds'),
				'id' => 'wc_af_cashback_amount',
				'type' => 'text',
				'default' => 10,
				'desc' => __('Cashback amount . Only give number no percentage','wc-accounts-funds'),
				'class' => 'wc-af-field wc-af-text'
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_account_cashback_settings'
			],

		);

		return apply_filters( 'wc_accounts_funds_get_settings_fields', $settings );
	}

	/**
	 * Save settings
	 * @since 1.0.0
	 */
	public function save() {
		$settings = $this->get_settings();
		WC_Accounts_Funds_Admin_Settings::save_fields( $settings );
	}
}

return new WC_Accounts_funds_Settings_General();