<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WC_Accounts_Funds_List_Table extends WP_List_Table {
	public $per_page = 20;
	public $items;
	public $total_count;
	public $base_url;

	public function __construct( $args = array() ) {
		parent::__construct( [
			'singular' => __( 'Account Fund', 'wc-account-funds' ),
			'plural'   => __( 'Accounts Funds', 'wc-account-funds' ),
			'ajax'     => false
		] );
	}
	public function get_columns() {
		$columns = array(
			'display_name'   => __( 'Username', 'wc-account-funds' ),
			'name'           => __( 'Name', 'wc-account-funds' ),
			'user_email'     => __( 'Email', 'wc-account-funds' ),
			'accounts_funds' => __( 'Account Funds', 'wc-account-funds' ),
		);

		return $columns;
	}

	/**
	 * Sortable columns
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_sortable_columns() {

		$shortable = array(
			'display_name'   => array( 'display_name', false ),
			'user_email'     => array( 'user_email', false ),
			'accounts_funds' => array( 'accounts_funds', false ),
		);

		return $shortable;
	}

	/**
	 * Default columns
	 *
	 * @param object $item
	 * @param string $column_name
	 *
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'display_name':
				echo empty( $item['display_name'] ) ? '&mdash;' : $item['display_name'];
				break;
			case 'name':
				echo empty( $item['name'] ) ? '&mdash;' : $item['name'];
				break;
			case 'user_email':
				echo empty( $item['user_email'] ) ? '&mdash;' : $item['user_email'];
				break;
			case 'accounts_funds':
				echo empty($item['accounts_funds']) ? '&mdash;' : wc_price($item['accounts_funds']);
				break;
			default:
				return ! empty( $item->$column_name ) ? $item->$column_name : '&mdash;';
				break;
		}
	}


	/**
	 * Prepare the items for the table to process
	 * @return Void
	 * @since 1.0.0
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$sortable              = $this->get_sortable_columns();
		$per_page              = $this->per_page;
		$hidden                = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$data                  = $this->get_results();
		$total_items           = $this->get_total();
		$this->items           = $data;

		$this->set_pagination_args( array(
		  'total_items' => $total_items,
		  'per_page'    => $per_page,
		  'total_pages' => ceil( $total_items / $per_page )
		) );

	}

	/**
	 * prepare results for the list table
	 * @since 1.0.0
	 */
	public function get_results() {
		global $wpdb;
		$per_page = $this->per_page;
		$order_by = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'id';
		$order    = isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'ASC';
		$search   = isset( $_REQUEST['s'] ) ? wp_unslash( trim($_REQUEST['s'] )) : '';

		$args = array(
			'number' => $per_page,
			'page'     => isset( $_GET['paged'] ) ? $_GET['paged'] : 1,
			'offset' => ($this->get_pagenum() - 1) * $per_page,
			'orderby'  => $order_by,
			'order'    => $order,
			'search' => $search
		);
		if('' !==  $args['search']){
			$args['search'] = '*'.$args['search'].'*';
		}
		if(isset($_REQUEST['role'])){
			$args['role'] = $_REQUEST['role'];
		}

		if ( array_key_exists( $order_by, $this->get_sortable_columns() ) && 'cost_label' != $order_by ) {
			$args['orderby'] = $order_by;
		}
		$this->total_count = $this->wc_accounts_funds_get_all_accounts_funds( $args, true );

		return $this->wc_accounts_funds_get_all_accounts_funds( $args );

	}

	/**
	 * Get accounts funds
	 *
	 * @param $args
	 * @param $count
	 *
	 * @return array|Object
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_get_all_accounts_funds( $args = array(), $count = false ) {
		$user_search = new WP_User_Query($args);
		$user_data = array();

		foreach($user_search->get_results() as $single_user){
			$user_info = get_userdata($single_user->ID);
			$user_data[] = array(
			  'ID' => $single_user->ID,
			  'display_name' => $single_user->data->user_login,
			  'name'=> ($user_info->first_name != '') ? $user_info->first_name.' '. $user_info->last_name : $single_user->data->display_name,
			  'user_email' => $single_user->data->user_email,
			  'accounts_funds' => get_user_accounts_funds($single_user->ID,false)
			);
		}

		return $user_data;
	}

	/**
	 * Get all items from the table
	 * @since 1.0.0
	*/
	public function get_total(){
		$wp_user_search = new WP_User_Query(array('number'=>-1));
		return $wp_user_search->get_total();

	}

	/**
	 * Get views for the table
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_views() {
		global $role;
		$site_roles = wp_roles();
		$page_url = 'admin.php?page=wc-accounts-funds';
		$account_users = count_users();

		$total_users  = $account_users['total_users'];
		$available_roles = $account_users['avail_roles'];


		$link = !empty($_REQUEST['role']) ? $_REQUEST['role'] : 'all';
		$link_attributes = ($link==='all') ? 'class="current" aria-current="page"': '';

		$role_links = array();
		$role_links['all'] = "<a href='$page_url'$link_attributes>".sprintf('All <span class="count">(%s)</span>',$total_users).'</a>';

		foreach($site_roles->get_names() as $single_role=>$name){
			if(!isset($available_roles[$single_role])){
				continue;
			}
			$link_attributes = ($link == $single_role) ? 'class="current" aria-current="page"' : '';

			$name = translate_user_role($name);
			$name = sprintf(__('%1$s <span class="count">(%2$s)</span>','wc-accounts-funds'),$name,$available_roles[$single_role]);
			$role_links[$single_role] = "<a href='".esc_url(add_query_arg('role',$single_role,$page_url))."'$link_attributes>$name</a>";
		}

		if(!empty($available_roles['none'])){
			$link_attributes = '';
			if('none'===$role){
				$link_attributes = 'class="current" aria-current="page"';
			}
			$name = __('Without Role','');
			$name = sprintf(__('%1$s <span class="count">(%2$s)</span>','wc-accounts-funds'),$name,$available_roles[$single_role]);
			$role_links['none'] = "<a href='".esc_url(add_query_arg('role',$single_role,$page_url))."'$link_attributes>$name</a>";

		}

		return $role_links;
	}





}