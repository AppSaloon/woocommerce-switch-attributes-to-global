<?php

namespace Appsaloon\Processor\Transformers;

use Appsaloon\Processor\Controllers\AttributeController;
use Appsaloon\Processor\Lib\Helper;

class ProductAttributesTransformer {

	/**
	 * @var \WC_Product
	 */
	private $product;

	private $attributes;

	/**
	 * @var AttributeController
	 */
	private $attributeController;

	public function setProduct( $productid ) {
		$this->attributeController = new AttributeController();
		$this->product             = wc_get_product( $productid );

		$this->attributes = $this->product->get_attributes();

		return $this;
	}

	public function hasWrongAttributes() {
		foreach ( Helper::generator( $this->attributes ) as $taxonomy => $ProductAttribute ) {
			if ( ! empty( $ProductAttribute->get_options() ) ) {
				return true;
			}
		}

		return false;
	}


	public function transformAttributes() {
		foreach ( Helper::generator( $this->attributes ) as $taxonomy => $ProductAttribute ) {
			// get options
			$this->debug( $ProductAttribute->get_options() );
			$options = $ProductAttribute->get_options();

			if ( ! empty( $options ) ) {
				$this->attributeController->set_product_attributes( $this->product, array( $taxonomy => $options ) );

				// update children - post meta
				$this->update_children_post_meta();
			}
		}

		return new \WP_Error( '500', print_r( $this->attributes, true ) );
	}

	private function generate_global_attribute( $taxonomy, $attribute_value ) {

	}

	private function update_children_post_meta() {

	}

	private function assign_to_product( $attribute ) {

	}

	private function debug( $var ) {
		return new \WP_Error( '500', print_r( $var, true ) );
	}
}