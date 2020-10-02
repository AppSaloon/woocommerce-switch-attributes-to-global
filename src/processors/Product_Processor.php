<?php

namespace appsaloon\wcstga\processors;

use appsaloon\wcstga\lib\Message_Log;
use appsaloon\wcstga\transformers\Product_Attributes_Transformer;
use Exception;

/**
 * Class Product_Processor
 * @package appsaloon\wcstga\processors
 */
class Product_Processor
{

    /**
     * @var int
     */
    public $productId;
    /**
     * @var Message_Log
     */
    private $messageLog;

    /**
     * ProductProcessor constructor.
     * @param int $productId
     * @param Message_Log $messageLog
     */
    public function __construct(int $productId, Message_Log $messageLog)
    {
        $this->productId = $productId;
        $this->messageLog = $messageLog;
    }

    /**
     * @throws Exception
     */
    public function processProduct()
    {
        $productTransformer = new Product_Attributes_Transformer($this->productId, $this->messageLog);

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