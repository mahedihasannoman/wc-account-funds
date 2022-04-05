<?php
defined( 'ABSPATH' ) || exit();

class WC_Accounts_Funds_Settings_Help extends WC_Settings_Page {

	/**
	 * constructor.
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->id    = 'help';
		$this->label = __( 'Help', 'wc-accounts-funds' );
		add_filter( 'wc_accounts_funds_settings_tabs_array', array( $this, 'add_settings_page' ), 99 );
		add_action( 'wc_accounts_funds_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'wc_accounts_funds_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Output function
	 * @since 1.0.0
	*/
	public function output() {
		$GLOBALS['hide_save_button'] = true;
		include dirname( __DIR__ ) . '/views/html-admin-help.php';
	}

	/**
	 * save function
	 * @since 1.0.0
	 */
	public function save() {

	}
}

return new WC_Accounts_Funds_Settings_Help();
