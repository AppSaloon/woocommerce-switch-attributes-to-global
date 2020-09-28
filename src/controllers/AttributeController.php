<?php

namespace Appsaloon\Processor\Controllers;

use Appsaloon\Processor\Lib\Helper;
use PHPUnit\TextUI\Help;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Attribute;

class AttributeController {

	/**
	 * @var WC_Product_Variable
	 */
	private $product;

	/**
	 * @var string
	 */
	private $taxonomy;

	/**
	 * @var string
	 */
	private $newTaxonomy;

	/**
	 * @var WC_Product_Attribute
	 */
	private $productAttribute;

	/**
	 * @var \QM_DB|\wpdb
	 */
	private $wpdb;

	/**
	 * AttributeController constructor.
	 *
	 * @param  WC_Product|WC_Product_Variable|WC_Product_Simple  $product
	 *
	 */
	public function __construct( $product ) {
		global $wpdb;

		$this->product = $product;
		$this->wpdb    = $wpdb;
	}

	/**
	 * Set Product attributes
	 *
	 * @param  string  $taxonomy
	 * @param  \WC_Product_Attribute  $productAttribute
	 *
	 * @return boolean|\WP_Error $product
	 *
	 * @version 1.0.3
	 * @since 1.0.0
	 */
	public function transform_product_attribute_to_global( $taxonomy, $productAttribute ) {
		$this->taxonomy         = $taxonomy;
		$this->productAttribute = $productAttribute;

		// taxonomy name update
		// this adds pa_ prefix to custom product attribute
		// so the taxonomy name matches the global attribute name rule
		$this->newTaxonomy = 'pa_' . $this->taxonomy;

		// retrieve old attribute information
		$attribute_id = wc_attribute_taxonomy_id_by_name( $this->taxonomy );

		// taxonomy does not exist so create it
		if ( 0 === $attribute_id ) {
			wc_create_attribute( array( 'name' => $productAttribute->get_taxonomy() ) );
			$result = $this->register_attribute( $taxonomy );

			if( is_wp_error( $result ) ) {
				$result->errors[500][0] .= ' (Taxonomy: ' . $taxonomy . ')';
				return $result;
			}
		}

		$attribute = $this->get_or_create_attribute();

		if ( is_wp_error( $attribute ) ) {
			return $attribute;
		}

		Helper::debug( $this->product->get_id() );
		Helper::debug( 'Taxonomy' );
		Helper::debug( $this->newTaxonomy );
		// retrieve existing product attributes
		$attributes = $this->product->get_attributes();

		// remove old custom taxonomy
		unset( $attributes[ $this->taxonomy ] );

		// adding new global taxonomy
		$attributes[ $this->newTaxonomy ] = $attribute;

		Helper::debug( 'Attributes' );
		Helper::debug( $attributes );

		// update product attributes
		$this->product->set_attributes( $attributes );

		$this->product->save();

		//@todo kunnen we dit ergens anders gebruiken??
		if ( $this->product->is_type( 'variable' ) ) {
			$this->update_post_meta_attribute();
		}

		return true;
	}

	/**
	 * Register taxonomy in global variable wc_product_attributes
	 *
	 * @param $attribute_name
	 *
	 * @since 1.0.0
	 */
	private function register_attribute( $attribute_name ) {
		global $wc_product_attributes;

		// Register as taxonomy while importing.
		$taxonomy_name = wc_attribute_taxonomy_name( $attribute_name );

		if( empty( $taxonomy_name ) ) {
			return new \WP_Error('500', 'Taxonomy name is not valid');
		}

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
	 * Generates an attribute to use in WooCommerce
	 *
	 * @return \WC_Product_Attribute|\WP_Error
	 *
	 * @since 1.0.0
	 */
	private function get_or_create_attribute() {
		$terms = explode( ' | ', $this->productAttribute['value'] );

		$term_ids = array();
		foreach ( $terms as $term ) {
			$newTerm = $this->create_term( $term );

			if ( is_wp_error( $newTerm ) ) {
				return $newTerm;
			}
			$term_ids[] = $newTerm;
			// update post meta
		}

		return $this->create_product_attribute( $terms );
	}

	/**
	 * Creates product attribute
	 *
	 * @param $options
	 *
	 * @return \WC_Product_Attribute
	 *
	 * @since 1.0.0
	 */
	private function create_product_attribute( $options ) {
		$attribute = new \WC_Product_Attribute();

		$attribute->set_id( $this->get_attribute_id() );
		$attribute->set_name( $this->newTaxonomy );
		$attribute->set_options( $options );
		$attribute->set_visible( $this->productAttribute->get_visible() );
		$attribute->set_variation( $this->productAttribute->get_variation() );

		return $attribute;
	}

	/**
	 * Creates new term and returns term_id
	 * or
	 * Gets existing term and return term_id
	 *
	 * @param $term
	 *
	 * @return int|\WP_Error
	 *
	 * @since 1.0.0
	 */
	private function create_term( $term ) {
		$term = wp_insert_term( $term, $this->newTaxonomy );

		if ( is_wp_error( $term ) ) {
			if ( isset( $term->error_data['term_exists'] ) ) {
				$term_id = $term->error_data['term_exists'];
			} else {
				return $term;
			}
		} else {
			$term_id = $term['term_id'];
		}

		return $term_id;
	}

	/**
	 * Updates post meta values for product variations
	 *
	 * Ex: attribute_artikelcode -> attribute_pa_artikelcode
	 *
	 * @since 1.0.0
	 */
	private function update_post_meta_attribute() {
		$prefix      = 'attribute_';
		$taxonomy    = $prefix . $this->taxonomy;
		$newTaxonomy = $prefix . $this->newTaxonomy;

		// get all variations
		foreach ( $this->product->get_children() as $product_variation_id ) {
			$meta_value = get_post_meta( $product_variation_id, $taxonomy, true );

			if ( empty( $meta_value ) ) {
				continue;
			}

			delete_post_meta( $product_variation_id, $taxonomy );


			$term = get_term_by( 'name', $meta_value, $this->newTaxonomy );

			Helper::debug( 'Taxonomy' );
			Helper::debug( $this->newTaxonomy  );
			Helper::debug( 'Term'  );
			Helper::debug( $term  );

			add_post_meta( $product_variation_id, $newTaxonomy, $term->slug );
		}
	}

	/**
	 * Get WooCommerce attribute ID by taxonomy name
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	private function get_attribute_id() {
		$query        = $this->wpdb->prepare( "select attribute_id from {$this->wpdb->prefix}woocommerce_attribute_taxonomies where attribute_name = %s",
			$this->taxonomy );
		$attribute_id = $this->wpdb->get_var( $query );

		return $attribute_id;
	}
}