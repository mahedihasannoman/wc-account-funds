<?php
defined( 'ABSPATH' ) || exit();

class WC_Accounts_Funds_Installer {
	/**
	 * Installer constructor.
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'init', array( '__CLASS__', 'maybe_install' ) );
		//add_action( 'init', array( '__CLASS__', 'create_cashback_cpt' ) );
	}
	
	/**
	 * Installation possible?
	 *
	 * @return boolean
	 * @since  1.0.0
	 *
	 */
	private static function can_install() {
		return ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) && ! defined( 'IFRAME_REQUEST' ) && ! 'yes' === get_transient( 'wc_accounts_funds_installing' );
	}
	
	/**
	 * Installation needed?
	 *
	 * @return boolean
	 * @since  1.1.6
	 *
	 */
	private static function should_install() {
		return empty(get_option('woocommerceaccountsfunds_version')) && empty('wc_accounts_funds_version');
	}

	/**
	 * Install plugin
	 * @since 1.0.0
	 */
	public static function maybe_install() {
		if(self::can_install() && self::should_install()){
			self::install();
		}
		
	}

	/**
	 * Install functions
	 * @since 1.0.0
	 */
	public static function install() {
		if(!is_blog_installed()){
			return;
		}
		
		//Running for the first time?Set a transient now. Used in 'can_install' to prevent race conditions
		set_transient('wc_accounts_funds_installing','yes',10);
		
		//Creates tables
		self::create_tables();
		
		//setup transient actions
		if(false === wp_next_scheduled('wc_accounts_funds_daily_event')){
			wp_schedule_event(time(),'daily','wc_accounts_funds_daily_event');
		}
		
		//setup topup product
		self::create_topup_product();
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 * @since 1.0.0
	 */
	private static function create_tables() {
		global $wpdb;
		$wpdb->hide_errors();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( self::get_schema() );
	}

	/**
	 * Get table schema
	 * @return array
	 * @since 1.0.0
	 */
	private static function get_schema() {
		global $wpdb;
        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
		}

		$tables = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}cashback_rules(
			id bigint(20) NOT NULL AUTO_INCREMENT,
		   cashback_type varchar(50) DEFAULT 'fixed',
		   price_from bigint(20) NOT NULL,
		   price_to bigint(20) NOT NULL DEFAULT 0,
		   amount bigint(20) NOT NULL  DEFAULT 0,
		   cashback_for varchar(50) DEFAULT NULL,
		   status varchar(50) DEFAULT 'publish',
		   PRIMARY KEY  (id),
		   key price_from (price_from),
		   key price_to (price_to),
		   key cashback_type (cashback_type),
		   key amount (amount),
		   key status (status)
		   ) $collate;
		CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}wcaf_transactions (
            transaction_id BIGINT UNSIGNED NOT NULL auto_increment,
            blog_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            type varchar(200 ) NOT NULL,
            amount DECIMAL( 10,2 ) NOT NULL,
            balance DECIMAL( 10,2 ) NOT NULL,
            currency varchar(20 ) NOT NULL,
            details longtext NULL,
            created_by BIGINT UNSIGNED NOT NULL DEFAULT 1,
            deleted tinyint(1 ) NOT NULL DEFAULT 0,
            date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (transaction_id ),
            KEY user_id (user_id )
        ) $collate;
        CREATE TABLE {$wpdb->base_prefix}wcaf_transaction_meta (
            meta_id BIGINT UNSIGNED NOT NULL auto_increment,
            transaction_id BIGINT UNSIGNED NOT NULL,
            meta_key varchar(255) default NULL,
            meta_value longtext NULL,
            PRIMARY KEY  (meta_id ),
            KEY transaction_id (transaction_id ),
            KEY meta_key (meta_key(32 ) )
        ) $collate;";

		return $tables;
	}

	/**
	 * Create top up product
	 * @since 1.0.0
	*/
	public static function create_topup_product() {
		
		$topup_product_id = get_option( 'wc_accounts_funds_topup_product' );
		if ( ! wc_get_product( $topup_product_id ) ) {
			self::create_new_product();
		}
	}

	private static function create_new_product() {
		$product_args = array(
			'post_title'   => __( 'Topup Product', 'wc-accounts-funds' ),
			'post_status'  => 'public',
			'post_type'    => 'product',
			'post_content' => stripslashes( __( 'Account topup product for recharging account funds.', 'wc-accounts-funds' ) ),
			'post_author'  => 1
		);

		$topup_product_id = wp_insert_post( $product_args );

		if ( ! is_wp_error( $topup_product_id ) ) {
			$topup_product = wc_get_product( $topup_product_id );
			wp_set_object_terms( $topup_product_id, 'simple', 'product_type' );
			update_post_meta( $topup_product_id, '_stock_status', 'instock' );
			update_post_meta( $topup_product_id, 'total_sales', '0' );
			update_post_meta( $topup_product_id, '_downloadable', 'no' );
			update_post_meta( $topup_product_id, '_virtual', 'yes' );
			update_post_meta( $topup_product_id, '_regular_price', 1 );
			update_post_meta( $topup_product_id, '_sale_price', '' );
			update_post_meta( $topup_product_id, '_purchase_note', '' );
			update_post_meta( $topup_product_id, '_featured', 'no' );
			update_post_meta( $topup_product_id, '_weight', '' );
			update_post_meta( $topup_product_id, '_length', '' );
			update_post_meta( $topup_product_id, '_width', '' );
			update_post_meta( $topup_product_id, '_height', '' );
			update_post_meta( $topup_product_id, '_sku', '' );
			update_post_meta( $topup_product_id, '_product_attributes', array() );
			update_post_meta( $topup_product_id, '_sale_price_dates_from', '' );
			update_post_meta( $topup_product_id, '_sale_price_dates_to', '' );
			update_post_meta( $topup_product_id, '_price', '' );
			update_post_meta( $topup_product_id, '_sold_individually', 'yes' );
			update_post_meta( $topup_product_id, '_manage_stock', 'no' );
			update_post_meta( $topup_product_id, '_backorders', 'no' );
			update_post_meta( $topup_product_id, '_stock', '' );

			$topup_product->set_reviews_allowed( false );
			$topup_product->set_catalog_visibility( 'hidden' );
			$topup_product->save();

			update_option( 'wc_accounts_funds_topup_product', $topup_product_id );
		}
	}


}

WC_Accounts_Funds_Installer::init();
