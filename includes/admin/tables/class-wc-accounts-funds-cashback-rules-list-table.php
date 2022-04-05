<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WC_Accounts_Funds_Cashback_Rules_List_Table extends WP_List_Table {
	public $per_page = 20;
	public $items;
	public $total_count;
	public $base_url;

	public function __construct( $args = array() ) {
		parent::__construct( [
			'singular' => __( 'Cashback Rule', 'wc-account-funds' ),
			'plural'   => __( 'Cashback Rules', 'wc-account-funds' ),
			'ajax'     => false
		] );
		$this->process_bulk_action();
	}

	public function get_columns() {

		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'cashback_type' => __( 'Cashback Type', 'wc-account-funds' ),
			'price_from'    => __( 'Price From', 'wc-account-funds' ),
			'price_to'      => __( 'Price To', 'wc-account-funds' ),
			'amount'        => __( 'Amount', 'wc-account-funds' ),
			'cashback_for'  => __( 'Cashback For', 'wc-account-funds' ),
			'status'        => __( 'status', 'wc-account-funds' ),
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
			'cashback_type' => array( 'cashback_type', false ),
			'price_from'    => array( 'price_from', false ),
			'price_to'      => array( 'price_to', false ),
			'amount'        => array( 'amount', false ),
			'cashback_for'  => array( 'cashback_for', false ),
			'status'        => array( 'status', false ),
		);

		return $shortable;
	}

	/**
	 * Default columns
	 *
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'cashback_type':
				echo empty( $item->cashback_type ) ? '&mdash;' : $item->cashback_type;
				break;
			case 'price_from':
				echo empty( $item->price_from ) ? '&mdash;' : $item->price_from;
				break;
			case 'price_to':
				echo empty( $item->price_to ) ? '&mdash;' : $item->price_to;
				break;
			case 'amount':
				echo empty( $item->amount ) ? '&mdash;' : $item->amount;
				break;
			case 'cashback_for':
				echo empty( $item->cashback_for ) ? '&mdash;' : $item->cashback_for;
				break;
			case 'status':
				echo empty( $item->status ) ? '&mdash;' : $item->status;
				break;
			default:
				return ! empty( $item->$column_name ) ? $item->$column_name : '&mdash;';
				break;
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	protected function column_cb( $item ) {
		return "<input type='checkbox' name='ids[]' id='{$item->id}' value='{$item->id}' />";
	}

	/**
	 * column pattern
	 *
	 * @param $item
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	function column_cashback_type( $item ) {
		$row_actions           = array();
		$edit_url              = add_query_arg( [
			'action' => 'edit',
			'id'     => $item->id
		], admin_url( 'admin.php?page=wc-accounts-funds-cashback-rules' ) );
		$delete_url            = add_query_arg( [
			'action' => 'delete',
			'id'     => $item->id
		], admin_url( 'admin.php?page=wc-accounts-funds-cashback-rules' ) );
		$row_actions['edit']   = sprintf( '<a href="%1$s">%2$s</a>', $edit_url, __( 'Edit', 'wc-account-funds' ) );
		$cashback_type         = strtoupper( $item->cashback_type );

		return sprintf( '%1$s %2$s', $cashback_type, $this->row_actions( $row_actions ) );
	}

	/**
	 * Get bulk actions
	 *
	 * since 1.0.0
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'wc-account-funds' )
		);

		return $actions;
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
		$total_items           = $this->total_count;
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
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : null;

		$args = array(
			'per_page' => $this->per_page,
			'page'     => isset( $_GET['paged'] ) ? $_GET['paged'] : 1,
			'orderby'  => $order_by,
			'order'    => $order,
			'search'   => $search
		);

		if ( array_key_exists( $order_by, $this->get_sortable_columns() ) && 'cost_label' != $order_by ) {
			$args['orderby'] = $order_by;
		}
		$this->total_count = $this->wc_accounts_funds_get_cashback_rules( $args, true );

		return $this->wc_accounts_funds_get_cashback_rules( $args );

	}

	/**
	 * Get cashback rules
	 *
	 * @param $args
	 * @param $count
	 *
	 * @return array|Object
	 * @since 1.0.0
	 */
	public function wc_accounts_funds_get_cashback_rules( $args = array(), $count = false ) {
		global $wpdb;
		$query_fields  = '';
		$query_from    = '';
		$query_where   = '';
		$query_orderby = '';
		$query_limit   = '';

		$default = array(
			'include'        => array(),
			'exclude'        => array(),
			'status'         => '',
			'search'         => '',
			'orderby'        => "id",
			'order'          => 'ASC',
			'fields'         => 'all',
			'per_page'       => 20,
			'page'           => 1,
			'offset'         => 0,
			'search_columns' => array( 'cashback_type', 'price_from', 'price_to', 'amount', 'status' ),
		);

		$args        = wp_parse_args( $args, $default );
		$table_name  = $wpdb->prefix . 'cashback_rules';
		$query_from  = "FROM $table_name";
		$query_where = 'WHERE 1=1';

		//fields
		if ( is_array( $args['fields'] ) ) {
			$args['fields'] = array_unique( $args['fields'] );

			$query_fields = array();
			foreach ( $args['fields'] as $field ) {
				$field          = 'id' === $field ? 'id' : sanitize_key( $field );
				$query_fields[] = "$table_name.$field";
			}
			$query_fields = implode( ',', $query_fields );
		} elseif ( 'all' == $args['fields'] ) {
			$query_fields = "*";
		} else {
			$query_fields = "id";
		}

		$search = '';
		if ( isset( $args['search'] ) ) {
			$search = trim( $args['search'] );
		}
		if ( $search ) {
			$searches = array();
			$cols     = array_map( 'sanitize_key', $args['search_columns'] );
			$like = $wpdb->esc_like( $search );
			foreach ( $cols as $col ) {
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like );
			}

			$query_where .= ' AND (' . implode( ' OR ', $searches ) . ')';
		}

		//ordering
		$order         = isset( $args['order'] ) ? esc_sql( strtoupper( $args['order'] ) ) : 'asc';
		$order_by      = esc_sql( $args['orderby'] );
		$query_orderby = sprintf( " ORDER BY %s %s ", $order_by, $order );

		// limit
		if ( isset( $args['per_page'] ) && $args['per_page'] > 0 ) {
			if ( $args['offset'] ) {
				$query_limit = $wpdb->prepare( 'LIMIT %d, %d', $args['offset'], $args['per_page'] );
			} else {
				$query_limit = $wpdb->prepare( 'LIMIT %d, %d', $args['per_page'] * ( $args['page'] - 1 ), $args['per_page'] );
			}
		}

		if ( $count ) {
			return $wpdb->get_var( "SELECT count($table_name.id) $query_from $query_where" );
		}

		$request = "SELECT $query_fields $query_from $query_where $query_orderby $query_limit";
		if ( is_array( $args['fields'] ) || 'all' == $args['fields'] ) {

			return $wpdb->get_results( $request );
		}

		return $wpdb->get_col( $request );
	}

	/**
	 * process the bulk delete
	 * @since 1.0.0
	 */
	public function process_bulk_action() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		$ids = isset( $_GET['ids'] ) ? $_GET['ids'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'intval', $ids );

		foreach ( $ids as $id ) {
			if ( 'delete' === $this->current_action() ) {
				$this->delete_cashback_rules( $id );
			}
		}
	}

	/**
	 * Delete cashback rules function
	 *
	 * @param $id
	 *
	 * @return BOOL
	 * @since 1.0.0
	 */
	public function delete_cashback_rules( $id ) {
		global $wpdb;
		$id         = absint( $id );
		$table_name = $wpdb->prefix . 'cashback_rules';
		$get_row    = $wpdb->get_row( "SELECT * FROM $table_name WHERE id=$id" );

		if ( is_null( $get_row ) ) {
			return false;
		}

		do_action( 'wc_accounts_funds_cashback_rules_delete', $id, $get_row );
		if ( false == $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) ) ) {
			return false;
		}
		do_action( 'wc_accounts_funds_cashback_rules_delete', $id, $get_row );

		return true;
	}


}