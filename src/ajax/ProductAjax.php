<?php

namespace Appsaloon\Processor\Ajax;

use Appsaloon\Processor\Processors\ProductProcessor;

class ProductAjax {

	public function register() {
		add_action( 'wp_ajax_product_attributes', array( $this, 'check_product_attributes' ) );
	}

	public function check_product_attributes() {
		$error        = false;
		$message      = '';
		$errorMessage = '';

		$offset = (int) sanitize_text_field( $_POST['offset'] );

		if ( ! is_int( $offset ) ) {
			wp_send_json_error( 'offset is not integer' );
			exit;
		}

		$productProcesser = ProductProcessor::instance();
		$processedMessage = $productProcesser->checkProduct( $offset );

		if ( is_wp_error( $processedMessage ) ) {
			$error        = true;
			$errorMessage = $processedMessage->get_error_message();
		} else {
			$message = sprintf( __( 'Product Id %s is processed!' ), $processedMessage );
		}

		wp_send_json( array(
			'message'      => $message,
			'error'        => $error,
			'errorMessage' => $errorMessage,
		) );
		die;
	}
}