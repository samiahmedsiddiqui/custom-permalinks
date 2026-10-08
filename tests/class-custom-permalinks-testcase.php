<?php
/**
 * Base test case with helpers shared by the integration tests.
 *
 * @package CustomPermalinks
 */

/**
 * Base test case.
 */
abstract class Custom_Permalinks_TestCase extends WP_UnitTestCase {

	/**
	 * Use pretty permalinks and turn redirects into exceptions so they don't exit.
	 */
	public function set_up() {
		parent::set_up();

		$this->set_permalink_structure( '/%postname%/' );

		// Taxonomies only get pretty links if registered with permalinks on.
		create_initial_taxonomies();
		flush_rewrite_rules( false );

		delete_option( 'custom_permalink_table' );

		add_filter(
			'wp_redirect',
			function ( $location ) {
				throw new Custom_Permalinks_Redirect_Exception( $location ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Never output.
			}
		);
	}

	/**
	 * Get the frontend instance the plugin registered its hooks with.
	 *
	 * @return Custom_Permalinks_Frontend
	 */
	protected function frontend() {
		global $wp_filter;

		foreach ( $wp_filter['template_redirect']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] )
					&& $callback['function'][0] instanceof Custom_Permalinks_Frontend
				) {
					return $callback['function'][0];
				}
			}
		}

		$this->fail( 'Custom_Permalinks_Frontend is not hooked into template_redirect.' );
	}

	/**
	 * Set a custom permalink for a post.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $permalink Custom permalink, without the home URL.
	 */
	protected function set_post_permalink( $post_id, $permalink ) {
		update_post_meta( $post_id, 'custom_permalink', $permalink );
	}

	/**
	 * Set a custom permalink for a term.
	 *
	 * @param WP_Term $term      Term.
	 * @param string  $permalink Custom permalink, without the home URL.
	 */
	protected function set_term_permalink( $term, $permalink ) {
		$table = get_option( 'custom_permalink_table', array() );

		$table[ $permalink ] = array(
			'id'   => $term->term_id,
			'kind' => 'category' === $term->taxonomy ? 'category' : 'tag',
		);

		update_option( 'custom_permalink_table', $table );
	}

	/**
	 * Request a URL and return where the plugin redirects it to.
	 *
	 * @param string $url URL to request.
	 *
	 * @return string|null Redirect location, or null if there was no redirect.
	 */
	protected function get_redirect( $url ) {
		try {
			$this->go_to( $url );
			$this->frontend()->make_redirect();
		} catch ( Custom_Permalinks_Redirect_Exception $e ) {
			return $e->getMessage();
		}

		return null;
	}
}
