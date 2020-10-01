<?php

namespace Appsaloon\Processor\Controllers;

use Appsaloon\Processor\Lib\MessageLog;
use Exception;
use WC_Product_Variable;
use WC_Product_Attribute;
use WP_Error;
use WP_Term;

class AttributeController
{

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
     * @var MessageLog
     */
    private $messageLog;

    /**
     * AttributeController constructor.
     *
     * @param WC_Product_Variable $product
     * @param MessageLog $messageLog
     * @param string $taxonomy
     * @param WC_Product_Attribute $productAttribute
     */
    public function __construct(
        WC_Product_Variable $product,
        MessageLog $messageLog,
        string $taxonomy,
        WC_Product_Attribute $productAttribute
    )
    {
        $this->product = $product;
        $this->messageLog = $messageLog;
        $this->taxonomy = $taxonomy;
        $this->productAttribute = $productAttribute;
    }

    /**
     * Set Product attributes
     *
     * @throws Exception
     * @since 1.0.0
     * @version 1.0.3
     */
    public function transform_product_attribute_to_global()
    {
        $this->messageLog->add_message('taxonomy: ' . $this->taxonomy);
        // skip this if the taxonomy is a global attribute
        if (strpos($this->taxonomy, 'pa_') === 0) {
            $this->messageLog->add_message('taxonomy is already global');
            return;
        }

        $this->create_or_get_unique_taxonomy();

        //Helper::debug( $this->product->get_id() );
        //Helper::debug( [ "taxonomy" => $this->taxonomy, "newtaxonomy" => $this->newTaxonomy ] );

        $attribute = $this->get_or_create_attribute();

        $this->messageLog->add_message('attribute id: ' . $attribute->get_id());
        $this->messageLog->add_message('attribute name: ' . $attribute->get_name());
        $this->messageLog->add_message('attribute taxonomy: ' . $attribute->get_taxonomy());

        // retrieve existing product attributes
        $attributes = $this->product->get_attributes();

        // remove old custom taxonomy
        unset($attributes[$this->taxonomy]);

        // adding new global taxonomy
        $attributes[$this->newTaxonomy] = $attribute;

        //Helper::debug( 'Attributes' );
        //Helper::debug( $attributes );

        // update product attributes
        $this->product->set_attributes($attributes);

        $this->product->save();

        // update product variations post meta
        $this->update_post_meta_attribute();
    }

    /**
     * Generates an attribute to use in WooCommerce
     *
     * @return WC_Product_Attribute
     *
     * @throws Exception
     * @since 1.0.0
     */
    private function get_or_create_attribute()
    {
        // @TODO: how does this function GET or create an attribute? it only creates.

        if(empty($this->productAttribute['value'])) {
            throw new Exception('Empty value for product attribute: ' . $this->productAttribute->get_name());
        }

        $terms = explode(' | ', $this->productAttribute['value']);
        $terms = array_unique($terms);

//        $term_ids = array();
        foreach ($terms as $term) {
            $this->messageLog->add_message('term: ' . $term);
            $newTerm = $this->create_term($term);

            if (is_wp_error($newTerm)) {
                $this->messageLog->add_message('could not create term: ' . $newTerm->get_error_message());
                throw new Exception($newTerm->get_error_message());
            }
//            $term_ids[] = $newTerm;
            // update post meta
        }

        return $this->create_product_attribute($terms);
    }

    /**
     * Creates product attribute
     *
     * @param $options
     *
     * @return WC_Product_Attribute
     *
     * @since 1.0.0
     */
    private function create_product_attribute($options)
    {
        $attribute = new WC_Product_Attribute();

        $attribute->set_id($this->get_attribute_id());
        $attribute->set_name($this->newTaxonomy);
        $attribute->set_options($options);
        $attribute->set_visible($this->productAttribute->get_visible());
        $attribute->set_variation($this->productAttribute->get_variation());

        return $attribute;
    }

    /**
     * Creates new term and returns term_id
     * or
     * Gets existing term and return term_id
     *
     * @param $term
     *
     * @return int|WP_Error
     *
     * @since 1.0.0
     */
    private function create_term($term)
    {
        $term = wp_insert_term($term, $this->newTaxonomy);

        if (is_wp_error($term)) {
            if (isset($term->error_data['term_exists'])) {
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
     * @throws Exception
     * @since 1.0.0
     *
     */
    private function update_post_meta_attribute()
    {
        $prefix = 'attribute_';
        $taxonomy = $prefix . $this->taxonomy;
        $newTaxonomy = $prefix . $this->newTaxonomy;

        $this->messageLog->add_message('upma taxonomy: ' . $taxonomy);
        $this->messageLog->add_message('upma newTaxonomy: ' . $newTaxonomy);

        // get all variations
        foreach ($this->product->get_children() as $product_variation_id) {
            $this->messageLog->add_message('upma product variation id: ' . $product_variation_id);
            $meta_value = get_post_meta($product_variation_id, $taxonomy, true);

            if (empty($meta_value)) {
                continue;
            }

            delete_post_meta($product_variation_id, $taxonomy);

            $this->messageLog->add_message('upma get term by name ' . $meta_value);
            $term = get_term_by('name', $meta_value, $this->newTaxonomy);

            if ($term instanceof WP_Term) {
                add_post_meta($product_variation_id, $newTaxonomy, $term->slug);
            } else {
                throw new Exception('Could not create metadata for variation id: ' . $product_variation_id);
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
    private function get_attribute_id()
    {
        global $wpdb;

        $taxonomy_name = substr($this->newTaxonomy, 3);
        //Helper::debug( [ 'taxonomy_attribute_name' => $taxonomy_name ] );

        $query = "select attribute_id from {$wpdb->prefix}woocommerce_attribute_taxonomies where attribute_name=%s";
        $query = $wpdb->prepare($query, $taxonomy_name);

        //Helper::debug( [ 'query attribute id' => $query ] );
        return $wpdb->get_var($query);
    }

    private function get_attribute_name_by_label($label)
    {
        global $wpdb;
        $query = "SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_label=%s";
        $query = $wpdb->prepare($query, $label);

        return $wpdb->get_var($query);
    }

    /**
     * Updates taxonomy variable if the taxonomy does not match with the taxonomy in productAttribute
     *
     * @since 1.0.0
     */
    private function create_or_get_unique_taxonomy()
    {
        $this->messageLog->add_message('attribute name: ' . $this->productAttribute->get_name());

        try {
            // Attribute name matched in the database
            $this->newTaxonomy = 'pa_' . $this->get_attribute_name_by_label_case_sensitive($this->productAttribute->get_name());
            $this->messageLog->add_message('attribute name existed in DB');

            return;
        } catch(Exception $exception) {

        }

        // create new taxonomy with preferred slug and name
        $i = 0;
        $preferred_base_slug = 'pa_' . $this->taxonomy;
        if (empty($this->taxonomy)) {
            $preferred_base_slug .= 'asterisk';
            //Helper::debug( [ 'asterisk' => $preferred_base_slug, 'asterisk label' => $this->productAttribute->get_name() ] );
        }
        $preferred_slug = false;
        while ($preferred_slug === false) {
            $preferred_slug = $preferred_base_slug;
            if ($i > 0) {
                $preferred_slug .= '-' . $i;
            }
            if (taxonomy_exists($preferred_slug)) {
                $preferred_slug = false;
                $i++;
            }
        }
        $this->messageLog->add_message('preferred slug: ' . $preferred_slug);

        wc_create_attribute(array('name' => $this->productAttribute->get_name(), 'slug' => $preferred_slug));

        $newTaxonomy = register_taxonomy(
            $preferred_slug,
            array('product'),
            array(
                'labels' => array(
                    'name' => $this->productAttribute->get_name(),
                ),
                'hierarchical' => true,
                'show_ui' => false,
                'query_var' => true,
                'rewrite' => false,
            )
        );

        global $wc_product_attributes;

        // Set product attributes global.
        $wc_product_attributes[$preferred_slug] = $newTaxonomy;

        $this->newTaxonomy = $preferred_slug;
    }

    /**
     * @param string $label
     * @return string
     * @throws Exception
     */
    private function get_attribute_name_by_label_case_sensitive(string $label): string {
        $attributeTaxonomies = wc_get_attribute_taxonomies();
        foreach($attributeTaxonomies as $attributeTaxonomy) {
            if($attributeTaxonomy->attribute_label === $label) {
                return $attributeTaxonomy->attribute_name;
            }
        }
        throw new Exception(('attribute taxonomy does not exist'));
    }
}