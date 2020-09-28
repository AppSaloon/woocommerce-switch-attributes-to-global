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
		$this->product             = wc_get_product( $productid );
		$this->attributeController = new AttributeController($this->product);

		$this->attributes = $this->product->get_attributes();

		return $this;
	}

	/**
	 * Returns true when the attribute is not global
	 *
	 * @return bool
	 */
	public function hasWrongAttributes() {
		foreach ( Helper::generator( $this->attributes ) as $taxonomy => $productAttribute ) {

			if ( ! empty( $productAttribute['value'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Transform product attribute into global attribute
	 *
	 * @return boolean|\WP_Error
	 */
	public function transformAttributes() {
		/**
		 * @var $ProductAttribute \WC_Product_Attribute
		 */
		foreach ( Helper::generator( $this->attributes ) as $taxonomy => $productAttribute ) {
			// transforms only product attributes and not global attributes
			if ( ! empty( $productAttribute['value'] ) ) {
				$result = $this->attributeController->transform_product_attribute_to_global( $taxonomy, $productAttribute );

				if( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		return true;
	}
}