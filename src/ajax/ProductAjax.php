<?php

namespace Appsaloon\Processor\Ajax;

use Appsaloon\Processor\Processors\ProductProcessor;

class ProductAjax {

	public function register() {
		add_action( 'wp_ajax_product_attributes', array( $this, 'check_product_attributes' ) );
	}

	public function check_product_attributes() {
		$error        = false;
		$complete     = false;
		$message      = '';
		$errorMessage = '';

		$offset = (int) sanitize_text_field( $_POST['offset'] );
		$max    = (int) sanitize_text_field( $_POST['max'] );
		$max = 1;

		if ( ! is_int( $offset ) ) {
			wp_send_json_error( 'offset is not integer' );
			exit;
		}

		//$offset += 1;

		$productProcesser = ProductProcessor::instance();
		$product_id       = $productProcesser->checkProduct( $offset );

		if ( is_wp_error( $product_id ) ) {
			$error        = true;
			$errorMessage = $product_id->get_error_message();
		} else {
			$message = sprintf( __( 'Product Id %s is processed!' ), $product_id );
		}

		$offset ++;

		if ( $offset >= $max ) {
			$complete = true;
		}

		wp_send_json( array(
			'value'        => $offset,
			'message'      => $message,
			'procent'      => floor( ( $offset / $max ) * 100 ),
			'complete'     => $complete,
			'error'        => $error,
			'errorMessage' => $errorMessage,
		) );
		die;
	}
}