<?php
/**
 * Custom Permalinks Updates.
 *
 * @package CustomPermalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send details(URL, CP version, WP Version etc) to Custom Permalinks.
 */
class Custom_Permalinks_Updates {
	/**
	 * Check Whether Plugin gets activated or deactivated.
	 *
	 * @var string
	 */
	private $method = 'install';

	/**
	 * Call function to send details.
	 *
	 * @param string $action Whether plugin activating or deactivating.
	 */
	public function __construct( $action ) {
		if ( $action && 'deactivate' === $action ) {
			$this->method = 'uninstall';
		}

		$this->update_version_details();
	}

	/**
	 * Fetch site details and sent it to CP.
	 *
	 * @since 1.6.0
	 * @access private
	 *
	 * @return void
	 */
	private function update_version_details() {
		$admin_email = get_bloginfo( 'admin_email' );
		$request_url = 'https://www.custompermalinks.com/plugin-update/';
		$site_name   = get_bloginfo( 'name' );
		$site_url    = get_bloginfo( 'wpurl' );
		$wp_version  = get_bloginfo( 'version' );

		$updates = array(
			'action'         => $this->method,
			'admin_email'    => $admin_email,
			'plugin_version' => CUSTOM_PERMALINKS_VERSION,
			'site_name'      => $site_name,
			'site_url'       => $site_url,
			'wp_version'     => $wp_version,
		);

		// Performs an HTTP request using the POST method.
		wp_remote_post(
			$request_url,
			array(
				'method' => 'POST',
				'body'   => $updates,
			)
		);
	}
}
