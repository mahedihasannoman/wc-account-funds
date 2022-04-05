<?php
defined( 'ABSPATH' ) || exit();

class WC_Accounts_Funds_Admin_Screen {
	public static function output() {
		self::render_table();
	}

	public static function render_table() {
		require dirname( __DIR__ ) . '/tables/class-wc-accounts-funds-list-table.php';
		$table = new WC_Accounts_Funds_List_Table();
		$table->prepare_items();
		?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
				<?php _e( 'Accounts Funds', 'wc-accounts-funds' );
	            ?>
            </h1>
            <hr class="wp-header-end">
			<?php $table->views();?>
            <form id="posts-filter" method="post">
				<?php
				$table->search_box( __( 'Search Users', 'wc-accounts-funds' ), 'search_id' );
				//$table->views();
				$table->display(); ?>
                <input type="hidden" name="page" value="wc-accounts-funds-list">
            </form>
        </div>
		<?php
	}
}