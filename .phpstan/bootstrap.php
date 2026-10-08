<?php
/**
 * Constants and third-party functions PHPStan cannot discover on its own.
 *
 * @package CustomPermalinks
 */

define( 'CUSTOM_PERMALINKS_FILE', dirname( __DIR__ ) . '/custom-permalinks.php' );
define( 'CUSTOM_PERMALINKS_BASENAME', 'custom-permalinks/custom-permalinks.php' );
define( 'CUSTOM_PERMALINKS_PATH', dirname( __DIR__ ) . '/' );
define( 'CUSTOM_PERMALINKS_VERSION', '0.0.0' );

/**
 * Polylang: returns the home URL in the given language.
 *
 * @param string $lang Language code.
 *
 * @return string
 */
function pll_home_url( $lang = '' ) {
	return '';
}
