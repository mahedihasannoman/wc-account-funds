<?php
defined( 'ABSPATH' ) || exit;

class WC_Accounts_Funds_Product_Admin {

	/**
	 * Class constructor
	 * @since 1.0.0
	 *
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'wc_accounts_funds_product_admin_init' ) );
		add_filter( 'product_type_selector', array( $this, 'wc_accounts_funds_register_product_type' ) );
		add_action( 'woocommerce_process_product_meta_cashback', array( $this, 'wc_account_funds_process_product_cashback' ), 10 );

		//metabox for cashback rules
		//for simple products
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'wc_accounts_funds_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'wc_accounts_funds_product_write_panel' ) );
		add_filter( 'woocommerce_process_product_meta', array( __CLASS__, 'wc_accounts_funds_product_save_data' ) );

		//for variable products add metabox to variations
		add_action( 'woocommerce_variation_options', array( __CLASS__, 'wc_accounts_funds_add_variation_enable_checkbox' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'wc_accounts_funds_save_variation_meta' ), 10, 2 );
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'wc_accounts_funds_add_variation_fields' ), 10, 3 );

	}

	/**
	 * Admin Init function
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_product_admin_init() {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			add_action( 'woocommerce_product_write_panels', array( $this, 'wc_accounts_funds_cashback_product_write_panel' ) );
		} else {
			add_action( 'woocommerce_product_data_panels', array( $this, 'wc_accounts_funds_cashback_product_write_panel' ) );
		}
	}

	/**
	 * Register cashback_products as product_type
	 *
	 * @param $types
	 *
	 * @return mixed $product_types
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_register_product_type( $types ) {
		$types['cashback'] = __( 'Cash Back Products', 'wc-accounts-funds' );

		return $types;
	}

	/**
	 * Save cash back product
	 *
	 * @param $post_id
	 *
	 * @since 1.0.0
	 */
	public function wc_account_funds_process_product_cashback( $post_id ) {
		update_post_meta( $post_id, '_virtual', 'yes' );
	}

	/**
	 * Hide virtual and downloadable checkbox
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_cashback_product_write_panel() {
		?>
        <script type="text/javascript">
            jQuery('.show_if_simple').addClass('show_if_cashback');
            jQuery('#_virtual, #_downloadable').closest('label').addClass('hide_if_cashback');
        </script>
		<?php
	}

	/**
	 * Product tabs
	 *
	 * @param $tabs
	 *
	 * @return array $tabs
	 * @since 1.0.0
	 */
	public static function wc_accounts_funds_product_data_tab( $tabs ) {
		$tabs['wc_accounts_funds'] = array(
			'label'    => __( 'Account Funds', '' ),
			'target'   => 'wc_accounts_funds_data',
			'class'    => array( 'show_if_simple' ),
			'priority' => 11
		);

		return $tabs;
	}

	/**
	 * Add metaboxes in cashback options
	 * @since 1.0.0
	 */
	public static function wc_accounts_funds_product_write_panel() {
		global $post, $woocommerce; ?>
        <div id="wc_accounts_funds_data" class="panel woocommerce_options_panel show_if_simple" style="padding-bottom: 50px; display:none;">
			<?php
			woocommerce_wp_checkbox( array(
				'id'            => '_is_apply_cash_back',
				'label'         => __( 'Provide cashback', 'wc-accounts-funds' ),
				'description'   => __( 'Enable this if you want to provide cashback for this product', 'wc-accounts-funds' ),
				'value'         => get_post_meta( $post->ID, '_is_apply_cash_back', true ),
				'wrapper_class' => 'options_group',
				'desc_tip'      => true,
			) );

			$cashback_type = get_post_meta( $post->ID, '_cashback_type', true );
			woocommerce_wp_select( array(
				'id'            => '_cashback_type',
				'type'          => 'select2',
				'label'         => __( 'Cashback Type', 'wc-accounts-funds' ),
				'description'   => __( 'Select cashback type fixed or percentage', 'wc-accounts-funds' ),
				'value'         => $cashback_type,
				'wrapper_class' => 'options_group',
				'desc_tip'      => true,
				'options'       => array(
					'fixed'      => __( "Fixed", 'wc-accounts-funds' ),
					'percentage' => __( 'Percentage', 'wc-accounts-funds' ),
				),
			) );

			$cashback_amount = get_post_meta( $post->ID, '_cashback_amount', true );
			woocommerce_wp_text_input(
				array(
					'id'            => '_cashback_amount',
					'label'         => __( 'Cashback amount', 'wc-accounts-funds' ),
					'description'   => __( 'The amount of cash back will the applied to the item', 'wc-accounts-funds' ),
					'value'         => ! empty( $cashback_amount ) ? $cashback_amount : 0,
					'type'          => 'number',
					'wrapper_class' => 'options_group',
					'desc_tip'      => true,

				) );

			?>
        </div>
		<?php
	}

	/**
	 * save product metabox data
	 * @since 1.0.0
	 */
	public static function wc_accounts_funds_product_save_data() {
		global $post;
		$status          = isset( $_POST['_is_apply_cash_back'] ) ? 'yes' : 'no';
		$cashback_amount = isset( $_POST['_cashback_amount'] ) ? sanitize_text_field( $_POST['_cashback_amount'] ) : '';
		$cashback_type   = isset( $_POST['_cashback_type'] ) ? sanitize_key( $_POST['_cashback_type'] ) : '';
		update_post_meta( $post->ID, '_is_apply_cash_back', $status );
		update_post_meta( $post->ID, '_cashback_amount', $cashback_amount );
		update_post_meta( $post->ID, '_cashback_type', $cashback_type );
	}

	/**
	 * Add checkbox for enable / disable cashback
	 *
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 *
	 * @since 1.0.0
	 */
	public static function wc_accounts_funds_add_variation_enable_checkbox( $loop, $variation_data, $variation ) {
		$saved = get_post_meta( $variation->ID, '_is_apply_cash_back', true ); ?>
        
        <label class="tips" data-tip="<?php esc_html_e( 'Enable this option if this is a cashback enabled.', 'wc-accounts-funds' ); ?>">
			<?php esc_html_e( 'Enable Cashback', 'wc-accounts-funds' ); ?>
            <input type="checkbox" name="variable_cashback_enabled[<?php echo esc_attr( $loop ); ?>]" <?php checked( $saved, 'yes' ); ?> class="checkbox variable_cashback_enabled">
        </label>
		<?php
	}

	/**
	 * Variation metabox
	 *
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 *
	 * @since 1.0.0
	 */
	public static function wc_accounts_funds_add_variation_fields( $loop, $variation_data, $variation ) {
		$cashback_meta = get_post_meta( $variation->ID, '_is_apply_cash_back', true );
		?>
        
        <div class="wc-accounts-funds-variation-settings" style="display: <?php echo $cashback_meta == 'yes' ? 'block' : 'none'; ?>">
			<?php
			echo sprintf( '<p class="wc-accounts-funds-settings-title">%s</p>', __( 'Cashback Settings', 'wc-accounts-funds' ) );
			$cashback_type   = get_post_meta( $variation->ID, '_cashback_type', true );
			$cashback_amount = get_post_meta( $variation->ID, '_cashback_amount', true );
			woocommerce_wp_select(
				array(
					'id'          => "_cashback_type{$loop}",
					'name'        => "_cashback_type[{$loop}]",
					'label'       => __( 'Cashback Type', '' ),
					'description' => __( 'Select cashback type fixed or percentage', 'wc-accounts-funds' ),
					'value'       => $cashback_type,
					'options'     => array(
						'fixed'      => __( "Fixed", 'wc-accounts-funds' ),
						'percentage' => __( 'Percentage', 'wc-accounts-funds' ),
					),
					'desc_tip'    => true,
					'type'        => 'select2',
				)
			);
			woocommerce_wp_text_input(
				array(
					'id'          => "_cashback_amount{$loop}",
					'name'        => "_cashback_amount[{$loop}]",
					'label'       => __( "Cashback Amount", 'wc-accounts-funds' ),
					'description' => __( 'The amount of cash back will the applied to the item', 'wc-accounts-funds' ),
					'value'       => ! empty( $cashback_amount ) ? $cashback_amount : 0,
					'desc_tip'    => true,
				)
			);
			?>
        </div>
		<?php

	}

	/**
	 * Save variation meta data
	 *
	 * @param $variation_id
	 * @param $loop
	 *
	 * @since 1.0.0
	 */
	public static function wc_accounts_funds_save_variation_meta( $variation_id, $loop ) {
		if ( ! empty( $_REQUEST['variable_cashback_enabled'][ $loop ] ) && $_REQUEST['variable_cashback_enabled'][ $loop ] == 'on' ) {
			update_post_meta( $variation_id, '_is_apply_cash_back', 'yes' );
		} else {
			update_post_meta( $variation_id, '_is_apply_cash_back', 'no' );
		}
		$cashback_amount = isset( $_REQUEST['_cashback_amount'][ $loop ] ) ? sanitize_text_field( $_REQUEST['_cashback_amount'][ $loop ] ) : '';
		$cashback_type   = isset( $_REQUEST['_cashback_type'][ $loop ] ) ? sanitize_key( $_REQUEST['_cashback_type'][ $loop ] ) : '';
		update_post_meta( $variation_id, '_cashback_amount', $cashback_amount );
		update_post_meta( $variation_id, '_cashback_type', $cashback_type );
	}

}

new WC_Accounts_Funds_Product_Admin();
