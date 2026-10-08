<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package CustomPermalinks
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php. Run the tests through `npm run test:php`." . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

require_once "{$_tests_dir}/includes/functions.php";

tests_add_filter(
	'muplugins_loaded',
	function () {
		require dirname( __DIR__ ) . '/custom-permalinks.php';
	}
);

require "{$_tests_dir}/includes/bootstrap.php";

require __DIR__ . '/class-custom-permalinks-redirect-exception.php';
require __DIR__ . '/class-custom-permalinks-testcase.php';
