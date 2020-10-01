<?php

namespace Appsaloon\Processor\Ajax;

use Appsaloon\Processor\Lib\MessageLog;
use Appsaloon\Processor\Processors\ProductProcessor;
use Exception;

/**
 * Class ProductAjax
 * @package Appsaloon\Processor\Ajax
 */
class ProductAjax
{

    /**
     * @var MessageLog
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
        $this->messageLog = new MessageLog();
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

        $this->productId = ProductProcessor::getProductId($offset);
//        $this->productId = 3741;

        if (empty($this->productId)) {
            $this->messageLog->add_message('Product not found.');
            $this->send_response();
        }

        try {
            $productProcesser = new ProductProcessor($this->productId, $this->messageLog);
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