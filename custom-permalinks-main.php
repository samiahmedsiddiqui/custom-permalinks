<?php
/**
 * @package CustomPermalinks\Main
 */

// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! function_exists( 'add_action' ) || ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

define( 'CUSTOM_PERMALINKS_PLUGIN_VERSION', '1.2.17' );

if ( ! defined( 'CUSTOM_PERMALINKS_PATH' ) ) {
	define( 'CUSTOM_PERMALINKS_PATH', plugin_dir_path( CUSTOM_PERMALINKS_FILE ) );
}

if ( ! defined( 'CUSTOM_PERMALINKS_BASENAME' ) ) {
	define( 'CUSTOM_PERMALINKS_BASENAME', plugin_basename( CUSTOM_PERMALINKS_FILE ) );
}

require_once( CUSTOM_PERMALINKS_PATH . 'frontend/class-custom-permalinks-frontend.php' );
$custom_permalinks_frontend = new Custom_Permalinks_Frontend();
$custom_permalinks_frontend->init();

require_once( CUSTOM_PERMALINKS_PATH . 'frontend/class-custom-permalinks-form.php' );
$custom_permalinks_form = new Custom_Permalinks_Form();
$custom_permalinks_form->init();

if ( is_admin() ) {
	require_once( CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-admin.php' );
	new Custom_Permalinks_Admin();
}

/**
 * Add textdomain hook for translation
 */
function custom_permalinks_translation_capability() {
	load_plugin_textdomain( 'custom-permalinks', FALSE,
		basename( dirname( CUSTOM_PERMALINKS_FILE ) ) . '/languages/'
	);
}
add_action( 'plugins_loaded', 'custom_permalinks_translation_capability' );
