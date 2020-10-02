<?php

namespace appsaloon\wcstga\ajax;

use appsaloon\wcstga\lib\Message_Log;
use appsaloon\wcstga\processors\Product_Processor;
use Exception;

/**
 * Class Product_Ajax
 * @package appsaloon\wcstga\ajax
 */
class Product_Ajax
{

    /**
     * @var Message_Log
     */
    private $messageLog;
    /**
     * @var bool
     */
    private $hasError = false;
    /**
     * @var string|null
     */
    private $productId;

    /**
     * ProductAjax constructor.
     */
    public function __construct()
    {
        $this->messageLog = new Message_Log();
    }

    public function register()
    {
        add_action('wp_ajax_product_attributes', array($this, 'check_product_attributes'));
    }

    public function check_product_attributes()
    {
        $offset = (int)sanitize_text_field($_POST['offset']);

        if (!is_int($offset)) {
            $this->send_response();
        }

        $this->productId = Product_Processor::getProductId($offset);
//        $this->productId = 3741;

        if (empty($this->productId)) {
            $this->messageLog->add_message('Product not found.');
            $this->send_response();
        }

        try {
            $productProcesser = new Product_Processor($this->productId, $this->messageLog);
            $productProcesser->processProduct();
            $message = sprintf(__('Product Id %s is processed!'), $this->productId);
            $this->messageLog->add_message($message);
        } catch (Exception $exception) {
            $this->hasError = true;
            $this->messageLog->add_message($exception->getMessage());
        }

        $this->send_response();
    }

    private function send_response()
    {
        wp_send_json(array(
            'productId' => $this->productId,
            'messages' => $this->messageLog->get_messages(),
            'error' => $this->hasError,
        ));
        exit;
    }
}