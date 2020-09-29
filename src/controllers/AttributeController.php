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

		// skip this if the taxonomy is a global attribute
		if ( strpos( $taxonomy, 'pa_' ) === 0 ) {
			return true;
		}

		$this->create_or_get_unique_taxonomy();

		//Helper::debug( $this->product->get_id() );
		//Helper::debug( [ "taxonomy" => $this->taxonomy, "newtaxonomy" => $this->newTaxonomy ] );

		$attribute = $this->get_or_create_attribute();

		if ( is_wp_error( $attribute ) ) {
			return $attribute;
		}

		// retrieve existing product attributes
		$attributes = $this->product->get_attributes();

		// remove old custom taxonomy
		unset( $attributes[ $this->taxonomy ] );

		// adding new global taxonomy
		$attributes[ $this->newTaxonomy ] = $attribute;

		//Helper::debug( 'Attributes' );
		//Helper::debug( $attributes );

		// update product attributes
		$this->product->set_attributes( $attributes );

		$this->product->save();

		// update product variations post meta
		if ( $this->product->is_type( 'variable' ) ) {
			$result = $this->update_post_meta_attribute();

			if( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
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
	 *
	 * @return \WP_Error
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

			if ( $term instanceof \WP_Term ) {
				add_post_meta( $product_variation_id, $newTaxonomy, $term->slug );
			} else {
				return new \WP_Error('500', 'Product data is not valid');
			}
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
		$taxonomy_name = substr( $this->newTaxonomy, 3 );
		//Helper::debug( [ 'taxonomy_attribute_name' => $taxonomy_name ] );

		$query = $this->wpdb->prepare( "select attribute_id from {$this->wpdb->prefix}woocommerce_attribute_taxonomies where attribute_name = %s",
			$taxonomy_name );

		//Helper::debug( [ 'query attribute id' => $query ] );
		$attribute_id = $this->wpdb->get_var( $query );

		//Helper::debug( [ 'query result attribute id' => $attribute_id ] );

		return $attribute_id;
	}

	private function get_attribute_name_by_label( $label ) {
		$query = $this->wpdb->prepare( "SELECT attribute_name FROM {$this->wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_label=%s",
			$label );

		return $this->wpdb->get_var( $query );
	}

	/**
	 * Updates taxonomy variable if the taxonomy does not match with the taxonomy in productAttribute
	 *
	 * @since 1.0.0
	 */
	private function create_or_get_unique_taxonomy() {
		/*Helper::debug( [
			"productAttribute name" => $this->productAttribute->get_name(),
		] );*/

		$taxonomy_slug_to_compare = $this->get_attribute_name_by_label( $this->productAttribute->get_name() );
		//Helper::debug( [ 'attribute_name' => $taxonomy_slug_to_compare ] );

		// Attribute name matched in the database
		if ( $taxonomy_slug_to_compare !== null ) {
			//Helper::debug( [ "taxonomy match" => $taxonomy_slug_to_compare ] );

			$this->newTaxonomy = 'pa_' . $taxonomy_slug_to_compare;

			return;
		}

		// create new taxonomy with preferred slug and name
		$i                   = 0;
		$preferred_base_slug = 'pa_' . $this->taxonomy;
		if ( empty( $this->taxonomy ) ) {
			$preferred_base_slug .= 'asterisk';
			//Helper::debug( [ 'asterisk' => $preferred_base_slug, 'asterisk label' => $this->productAttribute->get_name() ] );
		}
		$preferred_slug = false;
		while ( $preferred_slug === false ) {
			$preferred_slug = $preferred_base_slug;
			if ( $i > 0 ) {
				$preferred_slug .= '-' . $i;
			}
			if ( taxonomy_exists( $preferred_slug ) ) {
				$preferred_slug = false;
				$i ++;
			}
		}

		wc_create_attribute( array( 'name' => $this->productAttribute->get_name(), 'slug' => $preferred_slug ) );

		$newTaxonomy = register_taxonomy(
			$preferred_slug,
			array( 'product' ),
			array(
				'labels'       => array(
					'name' => $this->productAttribute->get_name(),
				),
				'hierarchical' => true,
				'show_ui'      => false,
				'query_var'    => true,
				'rewrite'      => false,
			)
		);

		global $wc_product_attributes;

		// Set product attributes global.
		$wc_product_attributes[ $preferred_slug ] = $newTaxonomy;

		$this->newTaxonomy = $preferred_slug;
	}
}