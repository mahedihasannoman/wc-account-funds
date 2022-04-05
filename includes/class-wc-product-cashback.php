<?php
defined( 'ABSPATH' ) || exit();

class WC_Product_Cashback extends WC_Product_Simple {

	/**
	 * class constructor
	 *
	 * @param $product
	 *
	 * @since 1.0.0
	 */
	public function __construct( $product  ) {
		parent::__construct( $product );
		$this->product_type = $this->get_type();
	}

	/**
	 * Get type of product
	 * @since 1.0.0
	*/
	public function get_type() {
		return 'cashback';
	}
}