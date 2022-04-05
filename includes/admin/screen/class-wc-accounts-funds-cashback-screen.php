<?php
defined( 'ABSPATH' ) || exit();

class WC_Accounts_Funds_Admin_Cahback_Screen {
	/**
	 * Init actions.
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'admin_post_wc_accounts_funds_edit_cashback', array( __CLASS__, 'action_edit_cashback' ) );
	}

	/**
	 * @since 1.0.0
	 */
	public static function action_edit_cashback() {
		global $wpdb;
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc_accounts_funds_edit_cashback' ) ) {
			wp_die( 'No, Cheating' );
		}

		

		$id     = ! empty( $_POST['id'] ) ? intval( $_POST['id'] ) : '';
		$posted = array(
			'id'            => ! empty( $_POST['id'] ) ? intval( $_POST['id'] ) : '',
			'cashback_type' => ! empty( $_POST['cashback_type'] ) ? sanitize_key( $_POST['cashback_type'] ) : 'fixed',
			'price_from'    => ! empty( $_POST['price_from'] ) ? sanitize_text_field( $_POST['price_from'] ) : '',
			'price_to'      => ! empty( $_POST['price_to'] ) ? sanitize_text_field( $_POST['price_to'] ) : '',
			'amount'        => ! empty( $_POST['amount'] ) ? intval( $_POST['amount'] ) : '',
			'cashback_for'  => ! empty( $_POST['cashback_for'] ) ? sanitize_key( $_POST['cashback_for'] ) : 'cart',
			'status'        => ! empty( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : 'publish'
		);


		$created = wc_accounts_funds_insert_cashback_rules( $posted );

		$redirect_args = array(
			'page'   => 'wc-accounts-funds-cashback-rules',
			'action' => empty( $id ) ? 'add' : 'edit'
		);
		if ( ! empty( $id ) ) {
			$redirect_args['id'] = $id;
		}

		if ( is_wp_error( $created ) ) {
			//todo notice
			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
			exit();
		}

		//todo notice
		wp_safe_redirect( add_query_arg( array( 'page' => $redirect_args['page'] ), admin_url( 'admin.php' ) ) );
		exit();

	}

	/**
	 * Conditionally Render view
	 * @since 1.0.0
	 */
	public static function output() {
		$action = isset( $_GET['action'] ) && ! empty( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		if ( in_array( $action, [ 'add', 'edit' ] ) ) {
			self::render_add( $action );
		} else {
			self::render_table();
		}
	}

	/**
	 * Render list table.
	 * @since 1.0.0
	 */
	public static function render_table() {
		require dirname( __DIR__ ) . '/tables/class-wc-accounts-funds-cashback-rules-list-table.php';
		$table = new WC_Accounts_Funds_Cashback_Rules_List_Table();
		//$doaction = $table->current_action();
		$table->prepare_items();

		?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
				<?php _e( 'Cashback Rules', 'wc-accounts-funds' ); ?>
            </h1>
            <a href="<?php echo admin_url( 'admin.php?page=wc-accounts-funds-cashback-rules&action=add' ) ?>" class="add-cashback-title page-title-action">
				<?php _e( 'Add Cashback Rule', 'wc-accounts-funds' ) ?>
            </a>
            <hr class="wp-header-end">

            <form id="wc-accounts-field-list" method="get">
				<?php
				$table->search_box( __( 'Search', 'wc-accounts-funds' ), 'search' );
				$table->display();
				?>
                <input type="hidden" name="page" value="wc-accounts-funds-cashback-rules">
            </form>
        </div>
		<?php
	}

	/**
	 * Render add or edit view.
	 *
	 * @param $action string
	 *
	 * @since 1.0.0
	 */
	public static function render_add( $action ) {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( empty( $id ) && 'edit' == $action ) {
			wp_redirect( add_query_arg( [ 'action' => 'add' ], remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'id' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
			exit;
		}
		$update                  = false;
		$item                    = array(
			'cashback_type' => 'fixed',
			'price_from'    => '',
			'price_to'      => '',
			'amount'        => '',
			'cashback_for'  => '',
			'status'        => 'publish',

		);
		$existing_cashback_rules = wc_account_funds_get_cashback_rules( $id );
		$existing_cashback_rules = (array) $existing_cashback_rules;

		//todo get edit values and prepare them for edit
		if ( ! empty( $id ) && ( is_array( $existing_cashback_rules ) && count( $existing_cashback_rules ) ) ) {
			$item   = array(
				'cashback_type' => $existing_cashback_rules['cashback_type'],
				'price_from'    => $existing_cashback_rules['price_from'],
				'price_to'      => $existing_cashback_rules['price_to'],
				'amount'        => $existing_cashback_rules['amount'],
				'cashback_for'  => $existing_cashback_rules['cashback_for'],
				'status'        => $existing_cashback_rules['status'],
			);
			$update = true;
		}
		?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
				<?php if ( $update ): _e( 'Update Cashback Rule', 'wc-accounts-funds' ); else: _e( 'Add Cashback Rule', 'wc-accounts-funds' ) ; endif; ?>
            </h1>
            <a href="<?php echo esc_url( remove_query_arg( array( 'action', 'id' ) ) ); ?>" class="page-title-action">
				<?php _e( 'Back', 'wc-accounts-funds' ); ?>
            </a>
            <hr class="wp-header-end">
	        
            <form action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label for="cashback_type"><?php esc_html_e( 'CashBack Type', 'wc-accounts-funds' ); ?></label>
                            </th>
                            <td>
                                <select name="cashback_type" id="cashback_type" class="regular-text wc_af_select">
									<?php
										$cashback_type = array(
										'fixed'      => __( 'Fixed', 'wc-accounts-funds' ),
										'percentage' => __( 'Percentage', 'wc-accounts-funds' ));
										foreach ( $cashback_type as $key => $option ) {
											echo sprintf( '<option value="%s" %s>%s</option>', $key, selected( $key, $item['cashback_type'] ), $option );
										} ?>
                                </select>
                                <p class="description"><?php esc_html_e( 'Cashback Type.', 'wc-accounts-funds' ); ?></p>
                            </td>
                        </tr>
                        <tr>
	                        <th>
	                            <label for="price_from"><?php esc_html_e( 'Cart Price From', 'wc-accounts-funds' ); ?></label>
	                        </th>
	                        <td>
								<?php echo sprintf( '<input name="price_from" id="price_from" class="regular-text" type="number" value="%s" autocomplete="off" required>', absint( $item['price_from'] ) ); ?>
	                            <p class="description"><?php esc_html_e( 'Cart price from where the cashback starts.', 'wc-accounts-funds' ); ?></p>
	                        </td>
                        </tr>
	                    <tr>
	                        <th>
	                            <label for="price_to"><?php esc_html_e( 'Cart Price To', 'wc-accounts-funds' ); ?></label>
	                        </th>
	                        <td>
								<?php echo sprintf( '<input name="price_to" id="price_to" class="regular-text" type="number" value="%s" autocomplete="off" required="required">', absint( $item['price_to'] ) ); ?>
	                            <p class="description"><?php esc_html_e( 'Cart price to where the cashback ends.', 'wc-accounts-funds' ); ?></p>
	                        </td>
	                    </tr>
	                    <tr>
	                        <th>
	                            <label for="amount"><?php esc_html_e( 'Cash Back Amount', 'wc-accounts-funds' ); ?></label>
	                        </th>
	                        <td>
								<?php echo sprintf( '<input name="amount" id="amount" class="regular-text" type="number" value="%s" autocomplete="off" required="required">', absint( $item['amount'] ) ); ?>
	                            <p class="description"><?php esc_html_e( 'Cashback amount. Only enter number', 'wc-accounts-funds' ); ?></p>
	                        </td>
	                    </tr>
	                    <tr>
	                        <th>
	                            <label for="cashback_for"><?php esc_html_e( 'CashBack For', 'wc-accounts-funds' ); ?></label>
	                        </th>
	                        <td>
	                            <select name="cashback_for" id="cashback_for" class="regular-text wc_af_select"
	                                    required="required">
									<?php
									$cashback_for = array(
										'cart'    => __( 'Cart', 'wc-accounts-funds' ),
										'product' => __( 'Product', 'wc-accounts-funds' ),
									);
									foreach ( $cashback_for as $key => $option ) {
										echo sprintf( '<option value="%s" %s>%s</option>', $key, selected( $key, $item['cashback_for'] ), $option );
									}
									?>
	                            </select>
	                            <p class="description"><?php esc_html_e( 'Cashback for.', 'wc-accounts-funds' ); ?></p>
	                        </td>
	                    </tr>
	                    <tr>
	                        <th>
	                            <label for="status"><?php esc_html_e( 'CashBack Status', 'wc-accounts-funds' ); ?></label>
	                        </th>
	                        <td>
	                            <select name="status" id="status" class="regular-text wc_af_select">
									<?php
									$status = array(
										'publish' => __( 'Publish', 'wc-accounts-funds' ),
										'draft'   => __( 'Draft', 'wc-accounts-funds' ),
									);
									foreach ( $status as $key => $option ) {
										echo sprintf( '<option value="%s" %s>%s</option>', $key, selected( $key, $item['status'] ), $option );
									}
									?>
	                            </select>
	                            <p class="description"><?php esc_html_e( 'Cashback Status.', 'wc-accounts-funds' ); ?></p>
	                        </td>
	                    </tr>
	                    <tr>
	                        <td></td>
	                        <td>
	                            <p class="submit">
	                                <input type="hidden" name="action" value="wc_accounts_funds_edit_cashback">
									<?php
									wp_nonce_field( 'wc_accounts_funds_edit_cashback' );
									if ( $update ):
										echo sprintf( '<input type="hidden" name="id" value=%d>', $id );
										submit_button( __( 'Update Cashback Rule', 'wc-accounts-funds' ) );
									else:
										submit_button( __( 'Add Cashback Rule', 'wc-accounts-funds' ) );
									endif;
									?>
	                            </p>
	                        </td>
	                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
		<?php

	}

}

WC_Accounts_Funds_Admin_Cahback_Screen::init();