<?php
defined( 'ABSPATH' ) || exit();

class WC_Accounts_Funds_Admin_Menus {

	/**
	 * WC_Accounts_Funds_Admin_Menus constructor.
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_pages' ) );
	}

	/**
	 * Register pages.
	 * @since 1.0.0
	 */
	public function register_pages() {
		$role = 'manage_woocommerce';

		add_menu_page(
			__( 'Account Funds', 'wc-accounts-funds' ),
			__( 'Account Funds', 'wc-accounts-funds' ),
			$role,
			'wc-accounts-funds',
			array( __CLASS__, 'main_page' ),
			'dashicons-awards',
			'55'
		);
		add_submenu_page(
			'wc-accounts-funds',
			__( 'Account Funds', 'wc-accounts-funds' ),
			__( 'Account Funds', 'wc-accounts-funds' ),
			$role,
			'wc-accounts-funds',
			array( 'WC_Accounts_Funds_Admin_Screen', 'output' )
		);
		add_submenu_page(
			'wc-accounts-funds',
			__( 'Cashback Rules', 'wc-accounts-funds' ),
			__( 'Cashback Rules', 'wc-accounts-funds' ),
			$role,
			'wc-accounts-funds-cashback-rules',
			array( 'WC_Accounts_Funds_Admin_Cahback_Screen', 'output' )
		);

		add_submenu_page(
			'wc-accounts-funds',
			__( 'Settings', 'wc-accounts-funds' ),
			__( 'Settings', 'wc-accounts-funds' ),
			$role,
			'wc-accounts-funds-settings',
			array( 'WC_Accounts_Funds_Admin_Settings', 'output' )
		);


	}

	/**
	 * main page
	 * @since 1.0.0
	 */
	public static function main_page() {

	}

}

new WC_Accounts_Funds_Admin_Menus();

