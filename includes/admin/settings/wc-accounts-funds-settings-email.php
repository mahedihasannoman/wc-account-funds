<?php
defined( 'ABSPATH' ) || exit();

/**
 * WC_Accounts_funds_Settings_General
 * @since 1.0.0
 */
class WC_Accounts_funds_Settings_Email extends WC_Settings_Page {

	/**
	 * Constructor
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->id    = 'email';
		$this->label = __( 'Email Settings', 'wc-accounts-funds' );

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
				'title' => __( 'Email Settings', 'wc-accounts-funds' ),
				'type'  => 'title',
				'desc'  => __( 'The following options affects email options will work.', 'wc-accounts-funds' ),
				'id'    => 'section_account_email'
			],
			[
				'title'   => __( 'Enable Email Features', 'wc-accounts-funds' ),
				'id'      => 'wc_af_apply_emails_enable',
				'desc'    => __( 'If checked, send an email to the customers when their balance is below the threshold', 'wc-accounts-funds' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'class'   => 'wc-af-field wc-af-checkbox'
			],
			[
				'title' => __( 'Fund Limit', 'wc-accounts-funds' ),
				'id'    => 'wc_af_user_fund_limit',
				'desc'  => __( 'Lowest limit of fund. When fund is below the limit the customer will get email', 'wc-accounts-funds' ),
				'type'  => 'number',
				'class' => 'wc-af-field wc-af-text'
			],
			[
				'title'   => __( 'Email Subject', 'wc-accounts-funds' ),
				'id'      => 'wc_af_user_email_subject',
				'desc'    => __( 'You can use following placeholders in email subject.<ul><li><code>{site_title}</code> for website title</li><li><code>{user_funds}</code> for user\'s current balance</li><li><code>{customer_email}</code> for customer\'s email address</li><li><code>{customer_name}</code> for customer\'s name</li></ul> ', 'wc-accounts-funds' ),
				'default' => __( '{site_title} Your account funds are running low', 'wc-accounts-funds' ),
				'type'    => 'text',
				'class'   => 'wc-af-field wc-af-text'
			],
			[
				'title'   => __( 'Email Heading', 'wc-accounts-funds' ),
				'id'      => 'wc_af_user_email_heading',
				'desc'    => __( 'You can use following placeholders in email subject.<ul><li><code>{site_title}</code> for website title</li><li><code>{user_funds}</code> for user\'s current balance</li><li><code>{customer_email}</code> for customer\'s email address</li><li><code>{customer_name}</code> for customer\'s name</li></ul> ', 'wc-accounts-funds' ),
				'default' => __( 'Visit {site_title} to refill your funds', 'wc-accounts-funds' ),
				'type'    => 'text',
				'class'   => 'wc-af-field wc-af-text'
			],
			[
				'title'   => __( 'Email Content', 'wc-accounts-funds' ),
				'id'      => 'wc_af_user_email_content',
				'type'    => 'textarea',
				'default' => __( 'Dear {customer_name}, you are going to run out of funds on {site_title} (your available funds: {user_funds}). Top up your account to be able to make new orders quickly and forget about card numbers and codes.. 
				Best regards {site_title}', 'wc-accounts-funds' ),
				'class'   => 'wc-af-field wc-af-textarea'
			],
			[
				'type' => 'sectionend',
				'id'   => 'section_account_email'
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

return new WC_Accounts_funds_Settings_Email();