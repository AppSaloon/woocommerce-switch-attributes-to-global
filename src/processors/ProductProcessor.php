<?php

namespace Appsaloon\Processor\Processors;

use Appsaloon\Processor\Transformers\ProductAttributesTransformer;

class ProductProcessor {

	/**
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Instance where the ProductProcessor object gets saved
	 *
	 * @var null|ProductProcessor
	 */
	private static $instance = null;

	/**
	 * @return ProductProcessor|null
	 */
	public static function instance() {
		// Check if instance is already exists
		if ( self::$instance == null ) {
			self::$instance = new ProductProcessor();
		}

		return self::$instance;
	}

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
	}

	/**
	 * @param $offset
	 *
	 * @return string|void|null|\WP_Error
	 */
	public function checkProduct( $offset ) {
		$productId = $this->getProductId( $offset );

		$productTransformer = ( new ProductAttributesTransformer() )->setProduct( $productId );

		if( $productTransformer->hasWrongAttributes() ) {
			$transformed = $productTransformer->transformAttributes();

			if( is_wp_error( $transformed ) ) {
				return $transformed;
			}
		}

		return $productId;
	}

	public function getTotalProducts() {
		$query = "SELECT count(*) FROM " . $this->wpdb->posts . " WHERE post_type = 'product'";

		return $this->wpdb->get_var( $query );
	}

	public function getProductId( $offset ) {
		$query = "SELECT ID FROM " . $this->wpdb->posts . " WHERE post_type='product' ORDER BY ID ASC LIMIT " . $offset . ",1";

		return $this->wpdb->get_var( $query );
	}
}