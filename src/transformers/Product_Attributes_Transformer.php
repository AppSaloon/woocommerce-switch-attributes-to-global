<?php

namespace appsaloon\wcstga\transformers;

use appsaloon\wcstga\lib\Message_Log;
use appsaloon\wcstga\controllers\Attribute_Controller;
use appsaloon\wcstga\lib\Helper;
use Exception;
use WC_Product_Attribute;
use WC_Product_Variable;

class Product_Attributes_Transformer
{

    /**
     * @var WC_Product_Variable
     */
    private $product;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var Attribute_Controller
     */
    private $attributeController;
    /**
     * @var Message_Log
     */
    private $messageLog;

    /**
     * ProductAttributesTransformer constructor.
     * @param int $productId
     * @param Message_Log $messageLog
     * @throws Exception
     */
    public function __construct(int $productId, Message_Log $messageLog)
    {
        $product = wc_get_product($productId);
        if(!is_a($product, WC_Product_Variable::class)) {
            throw new Exception('Not a variable product but a ' . get_class($product) . ' -- skipping');
        }
        $this->product = $product;
        $this->messageLog = $messageLog;
        $this->attributes = $this->product->get_attributes();
    }

    /**
     * Returns true when the attribute is not global
     *
     * @return bool
     */
    public function hasInvalidAttributes()
    {
        /**
         * @var $taxonomy string
         * @var $productAttribute WC_Product_Attribute
         */
        foreach (Helper::generator($this->attributes) as $taxonomy => $productAttribute) {
            // It's a product attribute
            if (!empty($productAttribute['value'])) {
                return true;
            }
        }

        // It's a global attribute
        return false;
    }

    /**
     * Transform product attribute into global attribute
     *
     * @throws Exception
     */
    public function transformAttributes()
    {
        /**
         * @var $taxonomy string
         * @var $ProductAttribute WC_Product_Attribute
         */
        foreach (Helper::generator($this->attributes) as $taxonomy => $productAttribute) {
            $attributeController = new Attribute_Controller(
                $this->product,
                $this->messageLog,
                $taxonomy,
                $productAttribute
            );
            $attributeController->transform_product_attribute_to_global();
        }
    }
}