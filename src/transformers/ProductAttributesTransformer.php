<?php

namespace Appsaloon\Processor\Transformers;

use Appsaloon\Processor\Lib\MessageLog;
use Appsaloon\Processor\Controllers\AttributeController;
use Appsaloon\Processor\Lib\Helper;
use Exception;
use WC_Product_Attribute;
use WC_Product_Variable;

class ProductAttributesTransformer
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
     * @var AttributeController
     */
    private $attributeController;
    /**
     * @var MessageLog
     */
    private $messageLog;

    /**
     * ProductAttributesTransformer constructor.
     * @param int $productId
     * @param MessageLog $messageLog
     * @throws Exception
     */
    public function __construct(int $productId, MessageLog $messageLog)
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
            $attributeController = new AttributeController(
                $this->product,
                $this->messageLog,
                $taxonomy,
                $productAttribute
            );
            $attributeController->transform_product_attribute_to_global();
        }
    }
}