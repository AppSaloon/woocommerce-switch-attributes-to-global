<?php
/*
Plugin Name: Appsaloon Processor
Plugin URI: https://appsaloon.be
Description: Show WooCommerce variable products variations as table with filters and sorting instead of normal dropdowns.
Author: AppSaloon
Author URI: https://appsaloon.be
Text Domain: appsaloon-processor
Domain Path: /languages/
Tags: woocommerce, product variations, list of product variations, filter product variations
Requires PHP: 7.0
Requires at least: 5.0
Tested up to: 5.3
Stable tag: 1.0.0
Version: 1.0.0
*/

define( 'AP_DIR', __DIR__ . DIRECTORY_SEPARATOR );

define( 'AP_URL', plugin_dir_url( __FILE__ ) );

define( 'AP_VERSION', '1.0.0' );

require __DIR__ . '/vendor/autoload.php';

use Appsaloon\Processor\Settings\ProcessorSettings;

use Appsaloon\Processor\Ajax\ProductAjax;

(new ProcessorSettings())->register();

(new ProductAjax())->register();