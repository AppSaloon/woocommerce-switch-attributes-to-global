<?php

namespace Appsaloon\Processor\Controllers;

class AttributeController {

	/**
	 * @var \WC_Product
	 */
	private $product;

	private $wpdb;

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
	}

	/**
	 * Set Product attributes
	 *
	 * @param  \WC_Product  $product
	 * @param  array  $atts
	 *
	 * @return \WC_Product $product
	 *
	 * @version 1.0.3
	 * @since 1.0.0
	 */
	public function set_product_attributes( $product, $atts ) {
		$this->product = $product;
		$attributes    = array();

		foreach ( $atts as $taxonomy => $terms ) {
			$attribute_id = wc_attribute_taxonomy_id_by_name( $taxonomy );

			if ( 0 === $attribute_id ) {
				wc_create_attribute( array( 'name' => $taxonomy ) );
				$this->register_attribute( $taxonomy );
			}

			$taxonomy = 'pa_' . $taxonomy;

			/**
			 * continue to next attribute if terms is empty
			 */
			if ( empty( $terms ) ) {
				continue;
			}

			/**
			 * Check if it has more than one term
			 */
			if ( is_array( $terms ) ) {
				$attributes[] = $this->get_attribute_multiple_options( $taxonomy, $terms, true );
			} elseif ( strpos( $terms, ',' ) !== false ) {
				$attributes[] = $this->get_attribute_multiple_options( $taxonomy, $terms );
			} else {
				$attributes[] = $this->get_attribute( $taxonomy, $terms );
			}
		}

		$product->set_attributes( $attributes );

		return $product;
	}

	private function register_attribute( $attribute_name ) {
		global $wc_product_attributes;

		// Register as taxonomy while importing.
		$taxonomy_name = wc_attribute_taxonomy_name( $attribute_name );
		register_taxonomy(
			$taxonomy_name,
			apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_' . $taxonomy_name,
				array(
					'labels'       => array(
						'name' => $attribute_name,
					),
					'hierarchical' => true,
					'show_ui'      => false,
					'query_var'    => true,
					'rewrite'      => false,
				)
			)
		);

		// Set product attributes global.
		$wc_product_attributes = array();

		foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
			$wc_product_attributes[ wc_attribute_taxonomy_name( $taxonomy->attribute_name ) ] = $taxonomy;
		}
	}

	/**
	 * Creates new term and returns term_id
	 * or
	 * Gets existing term and return term_id
	 *
	 * @param $taxonomy
	 * @param $term
	 *
	 * @return null
	 *
	 * @since 1.0.0
	 */
	private function create_term( $taxonomy, $term ) {
		$term = wp_insert_term( $term, $taxonomy );

		if ( is_wp_error( $term ) ) {
			if ( isset( $term->error_data['term_exists'] ) ) {
				$term_id = $term->error_data['term_exists'];
			} else {
				$term_id = null;
			}
		} else {
			$term_id = $term['term_id'];
		}

		return $term_id;
	}

	/**
	 * Generates an attribute to use in WooCommerce
	 *
	 * @param $taxonomy
	 * @param $term
	 *
	 * @return \WC_Product_Attribute
	 *
	 * @since 1.0.0
	 */
	private function get_attribute( $taxonomy, $term ) {
		$term_id = $this->create_term( $taxonomy, $term );

		$attribute = new \WC_Product_Attribute();

		$attribute->set_id( $this->get_attribute_id( $taxonomy ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( array( $term ) );
		$attribute->set_visible( true );
		$attribute->set_variation( false );

		return $attribute;
	}

	/**
	 * Generates an attribute with multiple options
	 *
	 * @param $taxonomy
	 * @param $terms
	 *
	 * @return \WC_Product_Attribute
	 *
	 * @since 1.0.0
	 */
	private function get_attribute_multiple_options( $taxonomy, $terms, $is_array = false ) {
		if ( $is_array === false ) {
			$terms = explode( ',', $terms );
		}

		$term_ids = array();
		foreach ( $terms as $term ) {
			$term_ids[] = $this->create_term( $taxonomy, $term );
		}

		$attribute = new \WC_Product_Attribute();

		$attribute->set_id( $this->get_attribute_id( $taxonomy ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( $terms );
		$attribute->set_visible( true );
		$attribute->set_variation( false );

		return $attribute;
	}

	/**
	 * Get WooCommerce attribute ID by taxonomy name
	 *
	 * @param $taxonomy
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	private function get_attribute_id( $taxonomy ) {
		$taxonomy     = str_replace( 'pa_', '', $taxonomy );
		$query        = "select attribute_id from {$this->wpdb->prefix}woocommerce_attribute_taxonomies where attribute_name LIKE '%$taxonomy%'";
		$attribute_id = $this->wpdb->get_var( $query );

		return $attribute_id;
	}
}