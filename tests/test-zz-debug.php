<?php
/**
 * Temporary diagnostics.
 *
 * @package CustomPermalinks
 */

// phpcs:ignoreFile

class Test_ZZ_Debug extends Custom_Permalinks_TestCase {

	private function dump( $label, $value ) {
		fwrite( STDERR, "\n[DEBUG] {$label}: " . var_export( $value, true ) . "\n" );
	}

	private function state() {
		$ref = new ReflectionClass( $this->frontend() );
		$out = array();
		foreach ( array( 'parse_request_status', 'request_uri', 'registered_url', 'is_paged' ) as $prop ) {
			if ( $ref->hasProperty( $prop ) ) {
				$p = $ref->getProperty( $prop );
				$p->setAccessible( true );
				$out[ $prop ] = $p->getValue( $this->frontend() );
			}
		}
		return $out;
	}

	public function test_debug_post_request() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'original-post' ) );
		$this->set_post_permalink( $post_id, 'custom/post-path/' );

		$this->dump( 'original_post_link', $this->frontend()->original_post_link( $post_id ) );
		$this->dump( 'get_permalink', get_permalink( $post_id ) );
		$this->dump( 'post', get_post( $post_id )->post_status . ' ' . get_post( $post_id )->post_name );

		try {
			$this->go_to( home_url( '/custom/post-path/' ) );
		} catch ( Exception $e ) {
			$this->dump( 'exception', get_class( $e ) . ' ' . $e->getMessage() );
		}

		$this->dump( 'query_vars', $GLOBALS['wp']->query_vars );
		$this->dump( 'matched_rule', $GLOBALS['wp']->matched_rule );
		$this->dump( 'is_404/is_single/is_page', array( is_404(), is_single(), is_page() ) );
		$this->dump( 'queried', get_queried_object_id() );
		$this->dump( 'state', $this->state() );
		$this->assertTrue( true );
	}

	public function test_debug_term_redirect() {
		update_option( 'posts_per_page', 1 );
		$cat = self::factory()->category->create_and_get( array( 'slug' => 'news' ) );
		self::factory()->post->create_many( 2, array( 'post_category' => array( $cat->term_id ) ) );
		$this->set_term_permalink( $cat, 'custom-news/' );

		$this->dump( 'table', get_option( 'custom_permalink_table' ) );
		$this->dump( 'term_permalink', $this->frontend()->term_permalink( $cat->term_id ) );
		$this->dump( 'original_term_link', $this->frontend()->original_term_link( $cat->term_id ) );

		try {
			$this->go_to( home_url( '/category/news/' ) );
		} catch ( Exception $e ) {
			$this->dump( 'go_to exception', get_class( $e ) . ' ' . $e->getMessage() );
		}

		$this->dump( 'query_vars', $GLOBALS['wp']->query_vars );
		$this->dump( 'is_404/is_category', array( is_404(), is_category() ) );
		$this->dump( 'state before redirect', $this->state() );
		$this->dump( 'REQUEST_URI', $_SERVER['REQUEST_URI'] );
		$this->dump( 'url_to_postid', url_to_postid( $_SERVER['REQUEST_URI'] ) );

		try {
			$this->frontend()->make_redirect();
			$this->dump( 'redirect', 'none' );
		} catch ( Exception $e ) {
			$this->dump( 'redirect', get_class( $e ) . ' ' . $e->getMessage() );
		}
		$this->assertTrue( true );
	}
}
