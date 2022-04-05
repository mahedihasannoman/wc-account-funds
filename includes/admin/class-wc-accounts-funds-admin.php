<?php
defined( 'ABSPATH' ) || exit();

class WC_Accounts_Funds_Admin {
    
    /**
     * WC_Accounts_Funds_Admin constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'init', array( __CLASS__, 'includes' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        //create user meta fields
        add_action( 'show_user_profile', array( $this, 'user_account_funds_meta_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'user_account_funds_meta_fields' ) );
        //update user meta fields
        add_action( 'personal_options_update', array( $this, 'save_user_account_funds_meta_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_account_funds_meta_fields' ) );
        //add new column in users table
        add_filter( 'manage_users_columns', array( $this, 'wc_accounts_funds_manage_users_table_columns' ) );
        add_filter( 'manage_users_custom_column', array( $this, 'wc_accounts_funds_manage_users_table_custom_column' ), 10, 3 );
    }
    
    /**
     * Include any classes we need within admin.
     *
     * @since 1.0.0
     */
    public static function includes() {
        require_once dirname( __FILE__ ) . '/class-wc-accounts-funds-admin-settings.php';
        require_once dirname( __FILE__ ) . '/class-wc-accounts-funds-admin-menus.php';
        require_once dirname( __FILE__ ) . '/screen/class-wc-accounts-funds-cashback-screen.php';
        require_once dirname( __FILE__ ) . '/screen/class-wc-accounts-funds-admin-screen.php';
        require_once dirname( __FILE__ ) . '/class-wc-accounts-funds-product-admin.php';
    }
    
    /**
     * Enqueue admin related assets
     *
     * @param $hook
     *
     * @since 1.0.0
     */
    public function admin_scripts( $hook ) {
        if ( ! wc_account_funds()->is_wc_active() ) {
            return;
        }
        
        $css_url    = wc_account_funds()->plugin_url() . '/assets/css';
        $js_url     = wc_account_funds()->plugin_url() . '/assets/js';
        $vendor_url = wc_account_funds()->plugin_url() . '/assets/vendors';
        $version    = wc_account_funds()->get_version();
        
        wp_enqueue_style( 'jquery-ui-style' );
        wp_enqueue_style( 'wc-af-select2', $vendor_url . '/select2/select2.css', array(), $version );
        wp_enqueue_script( 'wc-af-select2', $vendor_url . '/select2/select2.js', array( 'jquery' ), $version, true );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'admin-style', $css_url . '/wc-accounts-funds-admin.css', array(), $version );
        wp_enqueue_script( 'admin-script', $js_url . '/wc-accounts-funds-admin.js', array( 'jquery', ), $version, true );
        
    }
    
    /**
     * Create a meta field for account funds
     *
     * @param $user
     *
     * @since 1.0.0
     */
    public function user_account_funds_meta_fields( $user ) {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            $account_funds = get_user_meta( $user->ID, 'wc_user_account_funds', true );
            $account_funds = $account_funds ? $account_funds : 0;
            echo sprintf( __( '<h3>User Account Funds</h3>', 'wc-accounts-funds' ) ); ?>
            <table class="form-table" id="account-funds">
                <tbody>
                <tr>
                    <th><label for="user_account_funds"><?php echo __( 'Available Account funds', 'wc-accounts-funds' ); ?></label></th>
                    <td><input type="text" name="user_account_funds" id="user_account_funds"
                               value="<?php echo $account_funds; ?>" class="regular-text">
                        <p class="description"><?php echo __( 'Available account funds for purchasing items', 'wc-accounts-funds' ); ?></p>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        }
    }
    
    /**
     * Update user meta fields
     *
     * @param $user_id
     *
     * @since 1.0.0
     */
    public function save_user_account_funds_meta_fields( $user_id ) {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            if ( isset( $_POST['user_account_funds'] ) ) {
                $updated_funds = floatval( wc_clean( $_POST['user_account_funds'] ) );
                $updated_funds = ( $updated_funds <= 0 ) ? 0 : $updated_funds;
                update_user_meta( $user_id, 'wc_user_account_funds', $updated_funds );
            }
        }
    }
    
    /**
     * Add accounts_fund column
     *
     * @param array $columns
     *
     * @return array $columns
     * @since 1.0.0
     */
    public function wc_accounts_funds_manage_users_table_columns( $columns ) {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            $columns['accounts_funds'] = __( 'User Account Funds', 'wc-accounts-funds' );
        }
        
        return $columns;
    }
    
    /**
     * Populate custom column values
     *
     * @param string $output
     * @param string $column_name
     * @param int    $user_id
     *
     * @return string $string
     * @since 1.0.0
     */
    public function wc_accounts_funds_manage_users_table_custom_column( $output, $column_name, $user_id ) {
        if ( $column_name == 'accounts_funds' ) {
            $account_funds = get_user_meta( $user_id, 'wc_user_account_funds', true ) ? get_user_meta( $user_id, 'wc_user_account_funds', true ) : 0;
            $output        = wc_price( $account_funds );
        }
        
        return $output;
    }
    
}

new WC_Accounts_Funds_Admin();
