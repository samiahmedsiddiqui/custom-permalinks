<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_Updates
{

    /*
     * Check Whether Plugin gets activated or deactived.
     */
    private $method = 'install';

    /**
     * Initializes WordPress hooks.
     */
    function __construct( $action )
    {
        if ( $action && 'deactivate' === $action) {
            $this->method = 'uninstall';
        }
        $this->update_version_details();
    }

    /**
     * Fetch site details and sent it to CP.
     *
     * @since 1.6.0
     * @access private
     */
    private function update_version_details()
    {
        if ( true === function_exists( 'curl_init' ) ) {
            $admin_email = get_bloginfo( 'admin_email' );
            $curl_url    = 'https://www.custompermalinks.com/plugin-update/';
            $site_name   = get_bloginfo( 'name' );
            $site_url    = get_bloginfo( 'wpurl' );
            $wp_version  = get_bloginfo( 'version' );

            $updates = array(
                'action'         => $this->method,
                'admin_email'    => $admin_email,
                'plugin_version' => CUSTOM_PERMALINKS_PLUGIN_VERSION,
                'site_name'      => $site_name,
                'site_url'       => $site_url,
                'wp_version'     => $wp_version,
            );

            // Create a connection
            $curl_conn = curl_init( $curl_url );

            // Generate URL-encoded query string
            $encoded_data = http_build_query( $updates, '', '&' );

            // Setting options
            curl_setopt( $curl_conn, CURLOPT_POST, 1 );
            curl_setopt( $curl_conn, CURLOPT_POSTFIELDS, $encoded_data );
            curl_setopt( $curl_conn, CURLOPT_RETURNTRANSFER, TRUE );

            // Execute the given cURL session
            curl_exec( $curl_conn );

            // Closes a cURL session and frees all resources
            curl_close( $curl_conn );
        }
    }
}
