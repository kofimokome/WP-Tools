<?php

require_once __DIR__ . '/../vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	$plugin_dir = sys_get_temp_dir() . '/wp-tools-tests-' . uniqid();
	mkdir( $plugin_dir, 0777, true );
	define( 'WP_PLUGIN_DIR', $plugin_dir );
}

Brain\Monkey\setUp();
