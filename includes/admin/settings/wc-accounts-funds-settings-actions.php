<?php
defined( 'ABSPATH' ) || exit();

/**
 * WC_Accounts_funds_Settings_General
 * @since 1.0.0
 */
class WC_Accounts_funds_Settings_Actions extends WC_Settings_Page {

	/**
	 * Constructor
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->id    = 'action';
		$this->label = __( 'Action Settings', 'wc-accounts-funds' );

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
				'title' => __( 'New User Registration Settings', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how credit will work when a new user registered', 'wc-accounts-funds' ),
				'id'    => 'section_new_user_registation'
			],
			[
				'title'   => __( 'Enable/Disable', 'wc-accounts-funds' ),
				'id'      => 'wc_af_apply_credit_registration',
				'desc'    => __( 'Enable auto credit when a new user registered', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'title'   => __( 'Credit Amount', 'wc-accounts-funds' ),
				'id'      => 'wc_af_registration_credit_amount',
				'desc'    => __( 'Amount of funds which will deposit into a user wallet registered.', 'wc-accounts-funds' ),
				'type'    => 'text',
				'default' => '10',
				'class'   => 'wc-af-field wc-af-text'
			],
			[
				'title'      => __( 'Email Subject', 'wc-accounts-funds' ),
				'id'          => 'wc_af_registration_credit_email_subject',
				'desc' => __( 'Email subject for email', 'wc-accounts-funds' ),
				'type'        => 'text',
				'default'     => __( 'New User Registration Credit', 'wc-accounts-funds' ),
				'class'       => 'wc-af-field wc-af-text'
			],
			[
				'title'      => __( 'Email Message', 'wc-accounts-funds' ),
				'id'          => 'wc_af_registration_credit_desc',
				'placeholder' => __( 'Credit for new customer registration', 'wc-accounts-funds' ),
				'type'        => 'textarea',
				'default'     => __( 'Credit for new customer registration', 'wc-accounts-funds' ),
				'class'       => 'wc-af-field wc-af-textarea'
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_new_user_registation'
			],
			[
				'title' => __( 'Woocommerce Product Review Settings', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how credit will work when a user reviews a product', 'wc-accounts-funds' ),
				'id'    => 'section_woocommerce_product_review'
			],
			[
				'title'   => __( 'Enable/Disable', 'wc-accounts-funds' ),
				'id'      => 'wc_af_apply_product_review',
				'desc'    => __( 'Enable auto credit when a user reviews your product', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'title'   => __( 'Credit Amount', 'wc-accounts-funds' ),
				'id'      => 'wc_af_review_credit_amount',
				'desc'    => __( 'Amount of funds which will deposit into a user wallet when user gives a review.', 'wc-accounts-funds' ),
				'type'    => 'text',
				'default' => '10',
				'class'   => 'wc-af-field wc-af-text'
			],
			[
				'title'      => __( 'Email Subject', 'wc-accounts-funds' ),
				'id'          => 'wc_af_review_credit_email_subject',
				'desc' => __( 'Email subject for email', 'wc-accounts-funds' ),
				'type'        => 'text',
				'default'     => __( 'Credit for product review', 'wc-accounts-funds' ),
				'class'       => 'wc-af-field wc-af-text'
			],
			[
				'title'       => __( 'Email Message', 'wc-accounts-funds' ),
				'id'          => 'wc_af_review_credit_desc',
				'type'        => 'textarea',
				'default'     => __( 'Credit transfer for reviewing product', 'wc-accounts-funds' ),
				'placeholder' => __( 'Credit transfer for reviewing product', 'wc-accounts-funds' ),
				'class'       => 'wc-af-field wc-af-textarea',
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_woocommerce_product_review'
			],
			[
				'title' => __( 'Daily Visits Settings', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how credit will work in daily visits', 'wc-accounts-funds' ),
				'id'    => 'section_daily_visits'
			],
			[
				'title'   => __( 'Enable/Disable', 'wc-accounts-funds' ),
				'id'      => 'wc_af_apply_daily_visits',
				'desc'    => __( 'Enable auto credit for daily visits', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'title'   => __( 'Credit Amount', 'wc-accounts-funds' ),
				'id'      => 'wc_af_daily_visits_credit_amount',
				'desc'    => __( 'Amount of funds which will deposit into a user wallet when customer revisits in shop.', 'wc-accounts-funds' ),
				'type'    => 'text',
				'default' => '10',
				'class'   => 'wc-af-field wc-af-text',
			],
			[
				'title'   => __( 'Exclude User', 'wc-accounts-funds' ),
				'id'      => 'wc_af_daily_visits_exclude_users',
				'desc'    => __( 'Exclude user roles for getting the fund', 'wc-accounts-funds' ),
				'type'    => 'multiselect',
				'options' => $this->get_user_roles(),
				'class'   => 'wc-af-field wc-af-select wc-af-select2',
			],
			[
			  'title' => __('Day Interval','wc-accounts-funds'),
			  'id' => 'wc_af_daily_visits_interval',
			  'desc' => __('Interval when needed more than one day. Default is one-day','wc-accounts-funds'),
			  'type' => 'text',
			  'default' => '1',
			  'class' => 'wc-af-field wc-af-text'
			],
			[
				'title'      => __( 'Email Subject', 'wc-accounts-funds' ),
				'id'          => 'wc_af_daily_visits_credit_email_subject',
				'desc' => __( 'Email subject for email', 'wc-accounts-funds' ),
				'type'        => 'text',
				'default'     => __( 'Credit for daily visits', 'wc-accounts-funds' ),
				'class'       => 'wc-af-field wc-af-text'
			],
			[
				'title'       => __( 'Email Message', 'wc-accounts-funds' ),
				'id'          => 'wc_af_daily_visits_credit_desc',
				'type'        => 'textarea',
				'default'     => __( 'Credit transfer for daily visits', 'wc-accounts-funds' ),
				'placeholder' => __( 'Credit transfer for daily visits', 'wc-accounts-funds' ),
				'class'       => 'wc-af-field wc-af-textarea'
			],
			
			[
				'type' => 'sectionend',
				'id'   => 'section_daily_visits'
			],
			/*
			[
				'title' => __( 'Referral Settings', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects how credit will work when using referrals', 'wc-accounts-funds' ),
				'id'    => 'section_referrals'
			],
			[
				'title'   => __( 'Enable/Disable', 'wc-accounts-funds' ),
				'id'      => 'wc_af_apply_referrals',
				'desc'    => __( 'Enable auto credit for referrals', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'no',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'title'   => __( 'Credit Amount', 'wc-accounts-funds' ),
				'id'      => 'wc_af_referral_credit_amount',
				'desc'    => __( 'Amount of funds which will deposit into a user wallet when a new user signs up using referral.', 'wc-accounts-funds' ),
				'type'    => 'text',
				'default' => '10',
				'class'   => 'wc-af-field wc-af-text',
			],
			[
				'title'   => __( 'Referral Limits', 'wc-accounts-funds' ),
				'id'      => 'wc_af_referral_limits',
				'desc'    => __( 'Limits of account funds transfer', '' ),
				'type'    => 'select',
				'options' => array(
					'0'     => __( 'No Limit', 'wc-accounts-funds' ),
					'day'   => __( 'Per Day', 'wc-accounts-funds' ),
					'week'  => __( 'Per Week', 'wc-accounts-funds' ),
					'month' => __( 'Per Month', 'wc-accounts-funds' ),
				),
				'class'   => 'wc-af-field wc-af-select wc-af-select2'
			],
			[
				'title'       => __( 'Description', 'wc-accounts-funds' ),
				'id'          => 'wc_af_referral_credit_description',
				'default'     => __( 'Balance credited for user referrals', 'wc-accounts-funds' ),
				'placeholder' => __( 'Balance credited for user referrals', 'wc-accounts-funds' ),
				'type'        => 'textarea',
				'class'       => 'wc-af-field wc-af-textarea',
			],
			[
				'title'   => __( 'Referral Links format', 'wc-accounts-funds' ),
				'id'      => 'wc_af_referral_links_format',
				'type'    => 'select',
				'options' => array(
					'id'       => __( 'Numeric Referral id', 'wc-accounts-funds' ),
					'username' => __( 'Username as referral id', 'wc-accounts-funds' ),
				),
				'class'   => 'wc-af-field wc-af-select wc-af-select2'
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_referrals'
			],
			*/


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

	/**
	 * Get All registered user roles
	 * return array
	 * @since 1.0.0
	 */
	public function get_user_roles() {
		global $wp_roles;
		$roles = $wp_roles->get_names();

		return $roles;
	}
}

return new WC_Accounts_funds_Settings_Actions();