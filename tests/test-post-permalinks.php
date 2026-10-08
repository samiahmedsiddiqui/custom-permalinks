<?php
/**
 * Tests for post and page custom permalinks.
 *
 * @package CustomPermalinks
 */

/**
 * Post and page permalink tests.
 */
class Test_Post_Permalinks extends Custom_Permalinks_TestCase {

	/**
	 * The post link uses the custom permalink.
	 */
	public function test_post_link_uses_custom_permalink() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'original-post' ) );
		$this->set_post_permalink( $post_id, 'custom/post-path/' );

		$this->assertSame( home_url( '/custom/post-path/' ), get_permalink( $post_id ) );
	}

	/**
	 * The page link uses the custom permalink.
	 */
	public function test_page_link_uses_custom_permalink() {
		$page_id = self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'original-page',
			)
		);
		$this->set_post_permalink( $page_id, 'custom/page-path/' );

		$this->assertSame( home_url( '/custom/page-path/' ), get_permalink( $page_id ) );
	}

	/**
	 * Requesting the custom permalink loads the post.
	 */
	public function test_custom_permalink_request_loads_post() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'original-post' ) );
		$this->set_post_permalink( $post_id, 'custom/post-path/' );

		$this->go_to( home_url( '/custom/post-path/' ) );

		$this->assertTrue( is_single() );
		$this->assertSame( $post_id, get_queried_object_id() );
	}

	/**
	 * Requesting the custom permalink loads the page.
	 */
	public function test_custom_permalink_request_loads_page() {
		$page_id = self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'original-page',
			)
		);
		$this->set_post_permalink( $page_id, 'custom/page-path/' );

		$this->go_to( home_url( '/custom/page-path/' ) );

		$this->assertTrue( is_page() );
		$this->assertSame( $page_id, get_queried_object_id() );
	}

	/**
	 * A percent-encoded request matches a custom permalink saved unencoded.
	 */
	public function test_percent_encoded_request_matches_unencoded_permalink() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'original-post' ) );
		$this->set_post_permalink( $post_id, 'привет-мир/' );

		$this->go_to( home_url( '/' . rawurlencode( 'привет-мир' ) . '/' ) );

		$this->assertTrue( is_single() );
		$this->assertSame( $post_id, get_queried_object_id() );
	}

	/**
	 * Requesting the original permalink redirects to the custom one.
	 */
	public function test_original_permalink_redirects_to_custom_permalink() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'original-post' ) );
		$this->set_post_permalink( $post_id, 'custom/post-path/' );

		$this->assertSame(
			home_url( '/custom/post-path/' ),
			$this->get_redirect( home_url( '/original-post/' ) )
		);
	}

	/**
	 * Requesting the custom permalink doesn't redirect.
	 */
	public function test_custom_permalink_request_does_not_redirect() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'original-post' ) );
		$this->set_post_permalink( $post_id, 'custom/post-path/' );

		$this->assertNull( $this->get_redirect( home_url( '/custom/post-path/' ) ) );
	}

	/**
	 * The query string survives the redirect to the custom permalink.
	 */
	public function test_redirect_keeps_query_string() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'original-post' ) );
		$this->set_post_permalink( $post_id, 'custom/post-path/' );

		$this->assertSame(
			home_url( '/custom/post-path/?utm_source=test' ),
			$this->get_redirect( home_url( '/original-post/?utm_source=test' ) )
		);
	}

	/**
	 * Posts without a custom permalink keep the default one.
	 */
	public function test_post_without_custom_permalink_is_unchanged() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'plain-post' ) );

		$this->assertSame( home_url( '/plain-post/' ), get_permalink( $post_id ) );
		$this->assertNull( $this->get_redirect( home_url( '/plain-post/' ) ) );
	}

	/**
	 * Deleting the post removes its custom permalink.
	 */
	public function test_deleting_post_removes_custom_permalink() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'original-post' ) );
		$this->set_post_permalink( $post_id, 'custom/post-path/' );

		wp_delete_post( $post_id, true );

		$this->assertSame( 0, url_to_postid( home_url( '/custom/post-path/' ) ) );
	}
}
