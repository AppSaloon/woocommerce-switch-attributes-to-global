<?php

namespace Appsaloon\Processor\Processors;

use Appsaloon\Processor\Lib\Helper;
use Appsaloon\Processor\Transformers\ProductAttributesTransformer;
use PHPUnit\TextUI\Help;

class ProductProcessor {

	public $productId;

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
		$this->productId = $this->getProductId( $offset );

		if ( empty( $this->productId ) ) {
			return new \WP_Error( '500', 'Product not found.' );
		}

		$productTransformer = ( new ProductAttributesTransformer() )->setProduct( $this->productId );

		if ( $productTransformer->hasWrongAttributes() ) {
			$transformed = $productTransformer->transformAttributes();

			if ( is_wp_error( $transformed ) ) {
				return $transformed;
			}
		}

		return $this->productId;
	}

	public function getTotalProducts() {
		$query = "SELECT count(*) 
					FROM " . $this->wpdb->posts . " as wp
					WHERE post_type = 'product'";

		return $this->wpdb->get_var( $query );
	}

	public function getProductId( $offset ) {
		$query = $this->wpdb->prepare(
			"SELECT ID FROM " . $this->wpdb->posts . " WHERE post_type='product' ORDER BY ID ASC LIMIT %d,1", $offset
		);

		return $this->wpdb->get_var( $query );
	}
}