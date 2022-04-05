<?php
/**
 * Plugin Name: WC Account Funds
 * Plugin URI:  https://www.braintum.com
 * Description: WC Account Funds is an addon plugin for Woocommerce that allows customers to deposit funds into their accounts, and use them to purchase items in your store.
 * Version:     1.0.0
 * Author:      Md. Mahedi Hasan
 * Author URI:  https://www.braintum.com
 * Donate link: https://www.braintum.com/contact
 * License:     GPLv2+
 * Text Domain: wc-accounts-funds
 * Domain Path: /i18n/languages/
 * Tested up to: 5.9
 * WC requires at least: 3.0.0
 * WC tested up to: 3.8.0
 */

/**
 * Copyright (c) 2019 braintum (email : mahedi@braintum.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// don't call the file directly
defined( 'ABSPATH' ) || exit();

/**
 * WC_Accounts_Funds class.
 *
 * @class WC_Accounts_Funds contains everything for the plugin.
 */
class WC_Accounts_Funds {
	/**
	 * WC_Accounts_Funds Version
	 * @var string
	 * @since 1.0.0
	 */
	public $version = '1.0.0';

	/**
	 * This plugin's instance
	 *
	 * @var WC_Accounts_Funds The one true WC_Accounts_Funds
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * Main WC_Accounts_Funds Instance
	 *
	 * Insures that only one instance of WC_Accounts_Funds exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @return WC_Accounts_Funds The one true WC_Accounts_Funds
	 * @since 1.0.0
	 * @static var array $instance
	 */
	public static function init() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WC_Accounts_Funds ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Return plugin version.
	 *
	 * @return string
	 * @since 1.0.0
	 * @access public
	 **/
	public function get_version() {
		return $this->version;
	}

	/**
	 * Plugin URL getter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin path getter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Plugin base path name getter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function plugin_basename() {
		return plugin_basename( __FILE__ );
	}

	/**
	 * Initialize plugin for localization
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'wc-accounts-funds', false, plugin_basename( __FILE__ ) . '/i18n/languages/' );
	}

	/**
	 * Determines if the wc active.
	 *
	 * @return bool
	 * @since 1.0.0
	 *
	 */
	public function is_wc_active() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		return is_plugin_active( 'woocommerce/woocommerce.php' ) == true;
	}

	/**
	 * WooCommerce plugin dependency notice
	 * @since 1.0.0
	 */
	public function wc_missing_notice() {
		if ( ! $this->is_wc_active() ) {
			$message = sprintf( __( '<strong>WC Accounts Funds</strong> requires <strong>WooCommerce</strong> installed and activated. Please Install %s WooCommerce. %s', 'wc-accounts-funds' ),
				'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">', '</a>' );
			echo sprintf( '<div class="notice notice-error"><p>%s</p></div>', $message );
		}
	}

	/**
	 * Define constant if not already defined
	 *
	 * @param string $name
	 * @param string|bool $value
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 * @return void
	 * @since 1.0.0
	 */

	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc-accounts-funds' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access protected
	 * @return void
	 * @since 1.0.0
	 */

	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc-accounts-funds' ), '1.0.0' );
	}

	/**
	 * WC_Accounts_Funds constructor.
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->define_constants();
		register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );

		add_action( 'woocommerce_loaded', array( $this, 'init_plugin' ) );
		add_action( 'admin_notices', array( $this, 'wc_missing_notice' ) );

	}

	/**
	 * Define all constants
	 * @return void
	 * @since 1.0.0
	 */
	public function define_constants() {
		$this->define( 'WC_ACCOUNT_FUNDS_PLUGIN_VERSION', $this->version );
		$this->define( 'WC_ACCOUNT_FUNDS_PLUGIN_FILE', __FILE__ );
		$this->define( 'WC_ACCOUNT_FUNDS_PLUGIN_DIR', dirname( __FILE__ ) );
		//$this->define( 'WC_ACCOUNT_FUNDS_PLUGIN_INC_DIR', dirname( __FILE__ ) . '/includes' );
	}

	/**
	 * Activate plugin.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function activate_plugin() {
		require_once dirname( __FILE__ ) . '/includes/class-wc-accounts-funds-installer.php';
		WC_Accounts_Funds_Installer::install();
	}

	/**
	 * Deactivate plugin.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function deactivate_plugin() {

	}

	/**
	 * Load the plugin when WooCommerce loaded.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function init_plugin() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 * @since 1.0.0
	 */
	public function includes() {
		require_once dirname( __FILE__ ) . '/includes/class-wc-account-funds-query.php';
		require_once dirname( __FILE__ ) . '/includes/wc-accounts-funds-functions.php';
		require_once dirname( __FILE__ ) . '/includes/class-wc-product-cashback.php';
		require_once dirname( __FILE__ ) . '/includes/class-wc-accounts-funds-cart-handler.php';
		require_once dirname( __FILE__ ) . '/includes/class-wc-accounts-funds-checkout-handler.php';
		require_once dirname( __FILE__ ) . '/includes/class-wc-accounts-funds-account-handler.php';
		require_once dirname( __FILE__ ) . '/includes/class-wc-accounts-funds-cashback-handler.php';
		require_once dirname( __FILE__ ) . '/includes/action-functions.php';
		require_once dirname(__FILE__).'/includes/class-wc-accounts-funds-cron.php';
		if ( is_admin() ) {
			require_once dirname( __FILE__ ) . '/includes/admin/class-wc-accounts-funds-admin.php';
		}
		do_action( 'wc_accounts_funds__loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'localization_setup' ) );
		add_action( 'plugins_loaded', array( $this, 'gateway_initialization' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_payment_gateway' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style_files' ) );
		//add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), - 1 );
	}


	/**
	 * When WP has loaded all plugins.
	 *
	 * This ensures `plugins_loaded` is called only after all other plugins
	 * are loaded, to avoid issues caused by plugin directory naming changing
	 *
	 * @since 1.0.0
	 */
	public function on_plugins_loaded() {
		do_action( 'wc_accounts_funds__loaded' );
	}

	/**
	 * Initialize new payment gateway for account funds
	 * @since 1.0.0
	 */
	public function gateway_initialization() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}
		include_once dirname( __FILE__ ) . '/includes/admin/class-wc-accounts-funds-gateway.php';
	}

	/**
	 * Registers the new payment gateway with woocommerce
	 * @input $methods
	 * return array
	 *
	 * @param $methods
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function register_payment_gateway( $methods ) {
		$methods[] = 'WC_Account_Funds_Gateway';

		return $methods;
	}

	/**
	 * Enqueue style and scripts files for frontend
	 * @since 1.0.0
	 */
	public function enqueue_style_files() {
		wp_enqueue_style( 'account-funds-frontend', wc_account_funds()->plugin_url() . '/assets/css/wc-accounts-funds.css', array(), wc_account_funds()->get_version() );
		wp_enqueue_script( 'frontend-script', wc_account_funds()->plugin_url() . '/assets/js/wc-accounts-funds.js', array( 'jquery', ), wc_account_funds()->get_version(), true );
	}


}

/**
 * The main function responsible for returning the one true WC Accounts Funds
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return WC_Accounts_Funds
 * @since 1.0.0
 */
function wc_account_funds() {
	return WC_Accounts_Funds::init();
}

//lets go.
wc_account_funds();