<?php

namespace Appsaloon\Processor\Processors;

use Appsaloon\Processor\Lib\MessageLog;
use Appsaloon\Processor\Transformers\ProductAttributesTransformer;
use Exception;

/**
 * Class ProductProcessor
 * @package Appsaloon\Processor\Processors
 */
class ProductProcessor
{

    /**
     * @var int
     */
    public $productId;
    /**
     * @var MessageLog
     */
    private $messageLog;

    /**
     * ProductProcessor constructor.
     * @param int $productId
     * @param MessageLog $messageLog
     */
    public function __construct(int $productId, MessageLog $messageLog)
    {
        $this->productId = $productId;
        $this->messageLog = $messageLog;
    }

    /**
     * @throws Exception
     */
    public function processProduct()
    {
        $productTransformer = new ProductAttributesTransformer($this->productId, $this->messageLog);

        if ($productTransformer->hasInvalidAttributes()) {
            $productTransformer->transformAttributes();
        } else {
            $this->messageLog->add_message('Product has no invalid attributes');
        }
    }

    /**
     * @return string|null
     */
    public static function getTotalProducts()
    {
        global $wpdb;
        $query = "SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_type = 'product'";

        return $wpdb->get_var($query);
    }

    /**
     * @param $offset
     * @return string|null
     */
    public static function getProductId($offset)
    {
        global $wpdb;
        $query = "SELECT ID FROM " . $wpdb->posts . " WHERE post_type='product' ORDER BY ID LIMIT %d,1";
        $query = $wpdb->prepare($query, $offset);

        return $wpdb->get_var($query);
    }
}