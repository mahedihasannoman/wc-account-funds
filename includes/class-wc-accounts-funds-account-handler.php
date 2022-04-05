<?php
defined( 'ABSPATH' ) || exit;

class WC_Accounts_Funds_Account_Handler {
	/**
	 * Class constructor
	 * @since 1.0.0
	 */
	public function __construct() {

		//register the end point so that we can see the page
		add_filter( 'woocommerce_get_query_vars', array( $this, 'wc_accounts_funds_add_query_vars' ) );
		add_filter( 'woocommerce_endpoint_accounts-funds_title', array( $this, 'wc_accounts_funds_change_endpoint_title' ) );

		//adds tab/page into my account page
		add_filter( 'woocommerce_account_menu_items', array( $this, 'wc_account_funds_customer_account_add_menu_items' ) );
		add_action( 'woocommerce_account_accounts-funds_endpoint', array( $this, 'wc_accounts_funds_endpoint_content' ) );
		
		// Account Funds tab data.
		add_action( 'wc_accounts_funds_content', array( $this, 'wc_accounts_funds_tab_content' ) );
		add_action( 'wc_accounts_fund_transfer_content', array( $this, 'wc_accounts_fund_transfer_tab_content' ) );
		add_action( 'wc_accounts_funds_referrals', array( $this, 'wc_accounts_funds_referrals_tab_content' ) );
		add_action( 'wc_accounts_funds_transactions', array( $this, 'wc_accounts_funds_transactions_tab_content' ) );

		//handle accounts topup
		add_action( 'wp', array( $this, 'wc_accounts_funds_topup_handler' ) );
		//handle account transfer
		add_action( 'wp', array( $this, 'wc_accounts_fund_transfer_handler' ) );
		//handle referrals visit
		add_action( 'wp', array( $this, 'init_referral_visit' ), 105 );
		// handle referrals for signup
		add_action('user_register', array($this, 'referring_signup'));

		add_action('wp_loaded', array($this, 'load_referral'));

	}

	/**
	 * Adds endpoint into query vars.
	 *
	 * @param $query_vars
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 */
	public function wc_accounts_funds_add_query_vars( $query_vars ) {
		$query_vars['accounts-funds'] = 'accounts-funds';

		return $query_vars;
	}

	/**
	 * Changes the page title on account funds page.
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function wc_accounts_funds_change_endpoint_title() {
		return __( 'Account Funds', 'wc-accounts-funds' );
	}

	/**
	 * Add account funds menu-item in user account
	 *
	 * @param $menu_items
	 *
	 * @return array  $menu_items
	 * @since 1.0.0
	 */
	public function wc_account_funds_customer_account_add_menu_items( $menu_items ) {
		//inserting after downloads
		$menu_item_key   = 'accounts-funds';
		$menu_item_value = __( 'Accounts Funds', 'wc-accounts-funds' );

		$add_before_index = array_search( 'downloads', array_keys( $menu_items ), true );
		if ( false === $add_before_index ) {
			$menu_items[ $menu_item_key ] = $menu_item_value;
		} else {
			$add_before_index ++;
			$menu_items = array_merge( array_slice( $menu_items, 0, intval( $add_before_index ) ), array( $menu_item_key => $menu_item_value ), array_slice( $menu_items, $add_before_index ) );
		}

		return $menu_items;
	}

	/**
	 * My accounts end point basic content
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_endpoint_content() {
		wc_print_notices(); 
		global $wp;
		$show_account_top_up = get_option( 'wc_af_apply_top_up' );
		$show_account_referrals = get_option( 'wc_af_allow_referrals' );
		$enable_fund_transfer = get_option( 'wc_af_allow_fund_transfer' );
		$topup_url = esc_url(wc_get_endpoint_url( 'accounts-funds', 'topup', wc_get_page_permalink('myaccount')));
		$fund_transfer_url = esc_url(wc_get_endpoint_url( 'accounts-funds', 'transfer', wc_get_page_permalink('myaccount')));
		$referrals_url = esc_url(wc_get_endpoint_url( 'accounts-funds', 'referrals', wc_get_page_permalink('myaccount')));
		$transaction_url = esc_url(wc_get_endpoint_url( 'accounts-funds', 'transactions', wc_get_page_permalink('myaccount')));
		?>
			<div class="wc_account_funds_main_container">
				<div class="wc_account_funds_sidebar">
					<ul>
						<?php if ( $show_account_top_up == 'yes' ): ?>
						<li class="wc_account_funds_menu">	
							<a href="<?php echo $topup_url; ?>" ><span class="dashicons dashicons-plus-alt"></span><p><?php echo __( 'Topup', 'wc-accounts-funds' ); ?></p></a>
						</li>
						<?php endif; ?>

						<?php if ( $enable_fund_transfer == 'yes' ): ?>
						<li class="wc_account_funds_menu">	
							<a href="<?php echo $fund_transfer_url; ?>" ><span class="dashicons dashicons-randomize"></span><p><?php echo __( 'Fund Transfer', 'wc-accounts-funds' ); ?></p></a>
						</li>
						<?php endif; ?>
						
						<?php if ( $show_account_referrals == 'yes' ): ?>
						<li class="wc_account_funds_menu">	
							<a href="<?php echo $referrals_url; ?>" ><span class="dashicons dashicons-groups"></span><p><?php echo __( 'Referrals', 'wc-accounts-funds' ); ?></p></a>
						</li>
						<?php endif; ?>
						
						<li class="wc_account_funds_menu">	
							<a href="<?php echo $transaction_url; ?>" ><span class="dashicons dashicons-list-view"></span><p><?php echo __( 'Transactions', 'wc-accounts-funds' ); ?></p></a>
						</li>
						

					</ul>
				</div>
				<div class="wc_account_funds_content">
					<div class="wc-accounts-funds-content-heading">
						<h3 class="wc-accounts-funds-content-h3"><?php echo __( 'Balance', 'wc-accounts-funds' ); ?></h3>
						<p class="wc-accounts-funds-price"><?php echo get_user_accounts_funds() ?></p>
					</div>
					<div class="wc_account_funds_clear_both"></div>
					<hr>
					<?php 
					if ( isset( $wp->query_vars['accounts-funds'] ) && ! empty( $wp->query_vars['accounts-funds'] ) && $wp->query_vars['accounts-funds'] == 'transfer' && $enable_fund_transfer == 'yes' ){

						do_action( 'wc_accounts_fund_transfer_content' );

					} elseif ( isset( $wp->query_vars['accounts-funds'] ) && ! empty( $wp->query_vars['accounts-funds'] ) && $wp->query_vars['accounts-funds'] == 'topup' && $show_account_top_up == 'yes' ) {

						$this->customer_accounts_funds_topup();

					} elseif ( isset( $wp->query_vars['accounts-funds'] ) && ! empty( $wp->query_vars['accounts-funds'] ) && $wp->query_vars['accounts-funds'] == 'transactions' ) {

						do_action( 'wc_accounts_funds_transactions' );

					} elseif ( isset( $wp->query_vars['accounts-funds'] ) && ! empty( $wp->query_vars['accounts-funds'] ) && $wp->query_vars['accounts-funds'] == 'referrals' && $show_account_referrals == 'yes' ) {

						do_action( 'wc_accounts_funds_referrals' );

					} else {

						do_action( 'wc_accounts_funds_content' );

					}
					?>
				</div>
			</div>
		<?php
	}

	/**
	 * My accounts tab content
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_tab_content() {
		$show_account_top_up = get_option( 'wc_af_apply_top_up' );
		$enable_fund_transfer = get_option( 'wc_af_allow_fund_transfer' );
		$accounts_deposits = $this->get_customer_deposits();
		if ( $accounts_deposits ) { ?>
            <h3><?php echo __( 'Recent Account Funds Deposit', 'wc-accounts-funds' ); ?></h3>
            <table class="shop_table shop_table_responsive customer_accounts_deposits">
                <thead>
                    <tr>
                        <th class="order-number">Order No</th>
                        <th class="order-date">Order Date</th>
                        <th class="order-total">Order Status</th>
                        <th class="order-status">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ( is_array( $accounts_deposits ) && count( $accounts_deposits ) ) {
                        foreach ( $accounts_deposits as $single_deposit ) {
                            $order       = wc_get_order( $single_deposit->ID );
                            $order_items = $order->get_items();
                            $amount      = 0;
                            foreach ( $order_items as $item ) {
                                $product = null;
                                if ( $item->is_type( 'line_item' ) ) {
                                    $product = $item->get_product();
                                }
                                if ( $product->is_type( 'cashback' ) ) {
                                    $amount += $order->get_line_total( $item );
                                }
                            } ?>
                        <tr class="order">
                            <td class="order-number">
                                <a href="<?php echo $order->get_view_order_url(); ?>">#<?php echo $order->get_order_number(); ?></a>
                            </td>
                            <td class="order-date">
								<?php echo date( 'F j, Y', $order->get_date_created()->getOffsetTimestamp() ); ?>
                            </td>
                            <td class="order-status">
								<?php echo wc_get_order_status_name( $order->get_status() ); ?>
                            </td>
                            <td class="order-total"
                                data-title="Amount Funded"><?php echo ! empty( $amount ) ? wc_price( $amount ) : wc_price( $order->get_total() ); ?>
                        </tr>
						<?php
					}
				}
				?>
                </tbody>
            </table>
			<?php
		}
	}

	/**
	 * Render referrals tab
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function wc_accounts_funds_referrals_tab_content() {
		$referral_url = esc_url(add_query_arg( 'wcaf_ref', get_current_user_id(), home_url() ) );
		$main_page_url = esc_url(wc_get_endpoint_url( 'accounts-funds', '' , wc_get_page_permalink('myaccount')));
		$referral_visitor_count = get_user_meta( get_current_user_id(), '_wc_account_funds_referring_visitor', true ) ? get_user_meta( get_current_user_id(), '_wc_account_funds_referring_visitor', true ) : 0;
		$referring_signup_count = get_user_meta( get_current_user_id(), '_wc_account_funds_referring_signup', true ) ? get_user_meta( get_current_user_id(), '_wc_account_funds_referring_signup', true ) : 0;
		$referring_earning = get_user_meta( get_current_user_id(), '_wc_account_funds_referring_earning', true ) ? get_user_meta( get_current_user_id(), '_wc_account_funds_referring_earning', true ) : 0;
		?>
		<h3><?php echo esc_html( __( 'Referrals', 'wc-accounts-funds' ) ); ?> <a href="<?php echo $main_page_url ?>"><span class="dashicons dashicons-editor-break"></span></a></h3>
		<?php echo __( 'Your referral URL is:', 'wc-accounts-funds' ) ?> <input type="text" id="wcaf_ref_url" readonly value="<?php echo $referral_url; ?>" /> <button id="wcaf_copy" title="<?php echo __( 'Copy to Clipboard', 'wc-accounts-funds' ) ?>" ><?php echo __( 'Copy', 'wc-accounts-funds' ) ?></button>
		<br>
		<br>
		<h3><?php _e('Statistics', 'wc-accounts-funds'); ?></h3>
		<div class="wc_accounts_funds_referral_statistics_container">
			<table class="wc_accounts_funds_referral_statistics_table">
				<thead>
					<tr>
						<th><?php _e('Referring Visitors', 'wc-accounts-funds'); ?></th>
						<th><?php _e('Referring Signups', 'wc-accounts-funds'); ?></th>
						<th><?php _e('Total Earnings', 'wc-accounts-funds'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo $referral_visitor_count; ?></td>
						<td><?php echo $referring_signup_count; ?></td>
						<td><?php echo wc_price( $referring_earning ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render transaction tab content
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function wc_accounts_funds_transactions_tab_content() {
		$main_page_url = esc_url(wc_get_endpoint_url( 'accounts-funds', '' , wc_get_page_permalink('myaccount')));
		?>
		<h3><?php echo esc_html( __( 'Transactions', 'wc-accounts-funds' ) ); ?> <a href="<?php echo $main_page_url ?>"><span class="dashicons dashicons-editor-break"></span></a></h3>
		<?php
		do_action( 'woo_wallet_before_transaction_details_content' );
		?>
		<table id="wc-accounts-funds-transaction-details" class="table"></table>
		<?php do_action( 'woo_wallet_after_transaction_details_content' );
	}
	
	/**
	 * Fund transfer tab content
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function wc_accounts_fund_transfer_tab_content() {
		$min_transfer_amount = get_option( 'wc_af_min_transfer_amount' );
		$main_page_url = esc_url(wc_get_endpoint_url( 'accounts-funds', '' , wc_get_page_permalink('myaccount')));
		?>

		<form method="post">

			<h3><?php echo esc_html( __( 'Account Fund Transfer', 'wc-accounts-funds' ) ); ?> <a href="<?php echo $main_page_url ?>"><span class="dashicons dashicons-editor-break"></span></a></h3>

			<p class="form-row">
				<label for="account_fund_transfer_email"><?php echo esc_html( __( 'Whom to transfer (Email)', 'wc-accounts-funds' ) ); ?></label>
				<input type="email" name="account_fund_transfer_email" id="account_fund_transfer_email" value="<?php echo ( isset( $_POST['account_fund_transfer_email'] ) ? esc_attr( $_POST['account_fund_transfer_email'] ) : '' ); ?>" required />
			</p>
			
			<p class="form-row">
				<label for="account_fund_transfer_amount"><?php echo esc_html( __( 'Amount', 'wc-accounts-funds' ) ); ?></label>
				<input type="number" name="account_fund_transfer_amount" id="account_fund_transfer_amount" step="0.01"
						value="<?php echo ( isset( $_POST['account_fund_transfer_amount'] ) ? esc_attr( $_POST['account_fund_transfer_amount'] ) : esc_attr( $min_transfer_amount ) ); ?>"
						min="<?php echo esc_attr( $min_transfer_amount ); ?>"
						/>
			</p>

			<p class="form-row">
				<label for="account_fund_transfer_details"><?php echo esc_html( __( 'What\'s this for', 'wc-accounts-funds' ) ); ?></label>
				<textarea name="account_fund_transfer_details" id="account_fund_transfer_details"><?php echo ( isset( $_POST['account_fund_transfer_details'] ) ? esc_attr( $_POST['account_fund_transfer_details'] ) : '' ); ?></textarea>
			</p>

			<p class="form-row">
				<input type="hidden" name="wc_accounts_fund_transfer" value="true">
				<input type="submit" class="button" name="submit_transfer"
						value="<?php echo esc_html( __( 'Proceed to Transfer', 'wc-accounts-funds' ) ); ?>">
			</p>

			<p class="description">
				<?php echo sprintf( __( "Minimum transfer amount: %s", 'wc-accounts-funds' ), wc_price( $min_transfer_amount ) ); ?>
			</p>

			<?php wp_nonce_field( 'wc-accounts-fund-transfer' ); ?>
		</form>

		<?php
	}

	/**
	 * Show top up form in accounts page
	 * @since 1.0.0
	 */
	public function customer_accounts_funds_topup() {
		//todo already added topup in cart
		$main_page_url = esc_url(wc_get_endpoint_url( 'accounts-funds', '' , wc_get_page_permalink('myaccount')));
		$min_topup_amount = get_option( 'wc_af_top_up_min' );
		$max_topup_amount = get_option( 'wc_af_top_up_max' );
		?>
        <form method="post">
            <h3>
                <label for="account_topup_amount"><?php echo esc_html( __( 'Recharge Account Funds', 'wc-accounts-funds' ) ); ?><a href="<?php echo $main_page_url ?>"><span class="dashicons dashicons-editor-break"></span></a></label>
            </h3>
            <p class="form-row form-row-first">
                <input type="number" name="account_topup_amount" id="account_topup_amount" step="0.01"
                       value="<?php echo esc_attr( $min_topup_amount ); ?>"
                       min="<?php echo esc_attr( $min_topup_amount ); ?>"
                       max="<?php echo esc_attr( $max_topup_amount ); ?>">
            </p>
            <p class="form-row">
                <input type="hidden" name="wc_accounts_funds_topup" value="true">
                <input type="submit" class="button" name="submit_topup"
                       value="<?php echo esc_html( __( 'Account Top up', 'wc-accounts-funds' ) ); ?>">
            </p>

            <p class="description">
				<?php echo sprintf( __( "Minimum top up amount: %s Maximum Top up amount %s", 'wc-accounts-funds' ), wc_price( $min_topup_amount ), wc_price( $max_topup_amount ) ); ?>
            </p>
			<?php wp_nonce_field( 'wc-accounts-funds-topup' ); ?>
        </form>
		<?php
	}

	/**
	 * Show fund transfer
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function customer_accounts_funds_transfer() {
		$url = esc_url(wc_get_endpoint_url( 'accounts-funds', 'transfer', wc_get_page_permalink('myaccount')));
		?>
		<h3><?php echo esc_html( __( 'Account Fund Transfer', 'wc-accounts-funds' ) ); ?></h3>
		<a href="<?php echo $url; ?>" class="button"><?php echo esc_html( __( 'Transfer Fund', 'wc-accounts-funds' ) ); ?></a>
		<br>
		<br>
		<?php
	}

	/**
	 * Manage account funds topup operation
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_topup_handler() {
		if ( isset( $_POST['submit_topup'] ) ) {
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wc-accounts-funds-topup' ) ) {
				return;
			}

			if ( ! isset( $_POST['wc_accounts_funds_topup'] ) ) {
				return;
			}
			$min_topup_amount     = get_option( 'wc_af_top_up_min' );
			$max_topup_amount     = get_option( 'wc_af_top_up_max' );
			$account_topup_amount = isset( $_POST['account_topup_amount'] ) ? $_POST['account_topup_amount'] : 0;
			if ( $account_topup_amount < $min_topup_amount ) {
				wc_add_notice( sprintf( __( 'The minimum amount of top up is %s', 'wc-accounts-funds' ), wc_price( $min_topup_amount ) ), 'error' );

				return;
			} elseif ( $account_topup_amount > $max_topup_amount ) {
				wc_add_notice( sprintf( __( 'The maximum amount of top up is %s', 'wc-accounts-funds' ), wc_price( $min_topup_amount ) ), 'error' );

				return;
			}
			$topup_product_id = get_option( 'wc_accounts_funds_topup_product' );
			$product          = wc_get_product( $topup_product_id );
			if ( $product ) {
				WC()->cart->empty_cart();
				WC()->cart->add_to_cart( $product->get_id(), true, 0, 0, array( 'top_up_amount' => $account_topup_amount ) );
				wp_safe_redirect( wc_get_cart_url() );
				exit();
			}
		}

	}

	/**
	 * Handle fund transfer
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function wc_accounts_fund_transfer_handler() {
		if ( isset( $_POST['submit_transfer'] ) ) {
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wc-accounts-fund-transfer' ) ) {
				return;
			}

			if ( ! isset( $_POST['wc_accounts_fund_transfer'] ) ) {
				return;
			}

			$min_transfer_amount = get_option( 'wc_af_min_transfer_amount' );
			$is_valid = true;
			if ( ! isset( $_POST['account_fund_transfer_email'] ) || empty( $_POST['account_fund_transfer_email'] ) ) {
				wc_add_notice( __( 'Please provide a valid email address whom to transfer', 'wc-accounts-funds' ) , 'error' );
				$is_valid = false;
			}
			if ( isset( $_POST['account_fund_transfer_email'] ) && ! empty( $_POST['account_fund_transfer_email'] ) && ! filter_var( $_POST['account_fund_transfer_email'], FILTER_VALIDATE_EMAIL )) {
				wc_add_notice( __( 'Email address is not vaild. Please provide a valid email address', 'wc-accounts-funds' ) , 'error' );
				$is_valid = false;
			}
			if ( isset( $_POST['account_fund_transfer_email'] ) && ! empty( $_POST['account_fund_transfer_email'] ) && ! get_user_by( 'email', $_POST['account_fund_transfer_email'] ) ) {
				wc_add_notice( __( 'There is no account associated with this email.', 'wc-accounts-funds' ) , 'error' );
				$is_valid = false;
			}
			$transfer_amount = isset( $_POST['account_fund_transfer_amount'] ) ? (int)$_POST['account_fund_transfer_amount'] : 0;

			if ( $min_transfer_amount > $transfer_amount ){
				wc_add_notice( sprintf( __( 'The minimum amount of transfer is %s', 'wc-accounts-funds' ), wc_price( $min_transfer_amount ) ) , 'error' );
				$is_valid = false;
			}
			$account_fund_transfer_details = sanitize_text_field( $_POST['account_fund_transfer_details'] );

			$transfer_charge_type = get_option( 'wc_af_transfer_charge_type' );
			$transfer_charge_amount = get_option( 'wc_af_transfer_charge_amount' );
			$current_user = get_current_user_id();
			$user_current_balance =  (int)get_user_accounts_funds( $current_user, false );
			if ( $transfer_charge_type == 'fixed' ){
				$charge_amount = $transfer_charge_amount;
			} elseif ( $transfer_charge_type == 'percentage' ) {
				$charge_amount = round( ( $transfer_amount * $transfer_charge_amount ) / 100, 2 );
			}
			if ( $user_current_balance < ( $transfer_amount + $charge_amount ) ) {
				wc_add_notice( __( 'You do not have sufficient fund.', 'wc-accounts-funds' ) , 'error' );
				$is_valid = false;
			}
			$main_page_url = esc_url(wc_get_endpoint_url( 'accounts-funds', wc_get_page_permalink('myaccount')));
			if ( $is_valid ) {
				$account_fund_transfer_email = isset( $_POST['account_fund_transfer_email'] ) ? sanitize_email($_POST['account_fund_transfer_email']) : '';
				$transfer_to = get_user_by( 'email', $account_fund_transfer_email );
				wc_accounts_funds_add_funds( $transfer_to->ID, $transfer_amount, $account_fund_transfer_details );
				$details = sprintf( __( 'Fund transfer to %s', 'wc-accounts-funds' ), $account_fund_transfer_email );
				wc_accounts_funds_remove_funds($current_user, ( $transfer_amount + $charge_amount ), $details);
				wc_add_notice( __( 'Fund transfer has been successfull', 'wc-accounts-funds' ), 'success' );
				wp_safe_redirect( $main_page_url );
				exit;
			}

		}
	}

	/**
	 * Get accounts funds deposits data
	 * @since 1.0.0
	 */
	public function get_customer_deposits() {
		$deposited_products = get_posts(
			array(
				'numberposts' => 10,
				'post_type'   => 'shop_order',
				'post_status' => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
				'meta_query'  => array(
					array(
						'key'   => '_customer_user',
						'value' => get_current_user_id(),
					),
					array(
						'key'   => '_accounts_funds_deposited',
						'value' => 1,
					)
				),
			)
		);

		return $deposited_products;
	}

	/**
	 * Handle referrals
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_referral() {
		if (isset($_GET['wcaf_ref']) && !empty($_GET['wcaf_ref'])) {
            if (!headers_sent() && did_action('wp_loaded') ) {
                wc_setcookie('wc_account_funds_referral', $_GET['wcaf_ref'], time() + DAY_IN_SECONDS);
            }
        }
	}

	/**
	 * Get referral user
	 * 
	 * @since 1.0.0
	 *
	 * @return bool|object $user
	 */
	public function get_referral_user() {
        if (isset($_COOKIE['wc_account_funds_referral'])) {
            $wc_account_funds_referral = $_COOKIE['wc_account_funds_referral'];
            $user = get_user_by('ID', $wc_account_funds_referral);
            if ($user->ID === get_current_user_id()) {
                return false;
            }
            return apply_filters('wc_account_funds_referral_user', $user);
        }
        return false;
	}
	
	/**
	 * Handle referral visit
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_referral_visit() {
		$referral_visit_amount = get_option( 'wc_af_referring_visitors_amount' );
		$enable_referrals = get_option( 'wc_af_allow_referrals' );
		if ( $referral_visit_amount && (int)$referral_visit_amount > 0 && $enable_referrals == 'yes' && $this->get_referral_user() ) {
			$referral_user = $this->get_referral_user();
			if(apply_filters('wc_account_funds_restrict_referral_visit_by_cookie', isset($_COOKIE['wc_account_funds_referral_visit_credited_'. $referral_user->ID]))){
                return;
			}
			$referral_visitor_count = get_user_meta($referral_user->ID, '_wc_account_funds_referring_visitor', true) ? get_user_meta($referral_user->ID, '_wc_account_funds_referring_visitor', true) : 0;
			$referring_earning = get_user_meta($referral_user->ID, '_wc_account_funds_referring_earning', true) ? get_user_meta($referral_user->ID, '_wc_account_funds_referring_earning', true) : 0;
			$details = __('Balance credited for referring a visitor', 'wc-accounts-funds');
			wc_accounts_funds_add_funds($referral_user->ID, $referral_visit_amount,$details);
			update_user_meta($referral_user->ID, '_wc_account_funds_referring_visitor', $referral_visitor_count + 1);
			update_user_meta($referral_user->ID, '_wc_account_funds_referring_earning', $referring_earning + $referral_visit_amount);
			wc_setcookie('wc_account_funds_referral_visit_credited_' . $referral_user->ID, true, time() + DAY_IN_SECONDS);
		}
	}

	/**
	 * Handle referrals for signup
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function referring_signup() {
		$referral_signup_amount = get_option( 'wc_af_referring_signups_amount' );
		$enable_referrals = get_option( 'wc_af_allow_referrals' );
		if ( $referral_signup_amount && (int)$referral_signup_amount > 0 && $enable_referrals == 'yes' && $this->get_referral_user() ) {
			$referral_user = $this->get_referral_user();
			$referring_signup_count = get_user_meta($referral_user->ID, '_wc_account_funds_referring_signup', true) ? get_user_meta($referral_user->ID, '_wc_account_funds_referring_signup', true) : 0;
			$referring_earning = get_user_meta($referral_user->ID, '_wc_account_funds_referring_earning', true) ? get_user_meta($referral_user->ID, '_wc_account_funds_referring_earning', true) : 0;
			$details = __('Balance credited for referring a signup', 'wc-accounts-funds');
			wc_accounts_funds_add_funds($referral_user->ID, $referral_signup_amount,$details);
			update_user_meta($referral_user->ID, '_wc_account_funds_referring_signup', $referring_signup_count + 1);
			update_user_meta($referral_user->ID, '_wc_account_funds_referring_earning', $referring_earning + $referral_signup_amount);
		}
	}

}

new WC_Accounts_Funds_Account_Handler();
