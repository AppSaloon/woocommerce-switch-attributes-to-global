<?php

namespace Appsaloon\Processor\Settings;

use Appsaloon\Processor\Processors\ProductProcessor;

class ProcessorSettings {

	public function register() {
		add_action( 'admin_menu', function () {
			$progress_page = add_management_page(
				'Transform WC product attributes',
				'Transform WC attributes',
				'manage_options',
				'transform_wc_attributes',
				array( $this, 'transform_page_html' ),
				10 );


			add_action( 'load-' . $progress_page, array( $this, 'enqueue_scripts' ) );
		} );


	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'progress-style', AP_URL . 'assets/css/progress.css', '', AP_VERSION );

		wp_register_script( 'progress_script', AP_URL . 'assets/js/progress.js', array(), AP_VERSION, true );

		$object = array(
			'action' => 'product_attributes',
			'message' => array(
				'error' => __('Something went wrong during the ajax call.')
			)
		);

		wp_localize_script( 'progress_script', 'ap_progress', $object );

		wp_enqueue_script( 'progress_script' );

	}

	public function transform_page_html() {
		$max = ProductProcessor::getTotalProducts();

		include AP_DIR . 'templates/backend/ProgressBar.php';
	}


}