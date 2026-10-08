<?php
/**
 * Tests for term custom permalinks.
 *
 * @package CustomPermalinks
 */

/**
 * Term permalink tests.
 */
class Test_Term_Permalinks extends Custom_Permalinks_TestCase {

	/**
	 * Category with a custom permalink and two posts, one per archive page.
	 *
	 * @var WP_Term
	 */
	private $category;

	/**
	 * Create the category and its posts.
	 */
	public function set_up() {
		parent::set_up();

		update_option( 'posts_per_page', 1 );

		$this->category = self::factory()->category->create_and_get( array( 'slug' => 'news' ) );
		self::factory()->post->create_many( 2, array( 'post_category' => array( $this->category->term_id ) ) );

		$this->set_term_permalink( $this->category, 'custom-news/' );
	}

	/**
	 * The term link uses the custom permalink.
	 */
	public function test_term_link_uses_custom_permalink() {
		$this->assertSame( home_url( '/custom-news/' ), get_term_link( $this->category ) );
	}

	/**
	 * Requesting the custom permalink loads the category archive.
	 */
	public function test_custom_permalink_request_loads_category() {
		$this->go_to( home_url( '/custom-news/' ) );

		$this->assertTrue( is_category() );
		$this->assertSame( $this->category->term_id, get_queried_object_id() );
	}

	/**
	 * Paged requests on the custom permalink load the requested page.
	 */
	public function test_paged_custom_permalink_request_loads_page() {
		$this->go_to( home_url( '/custom-news/page/2/' ) );

		$this->assertTrue( is_category() );
		$this->assertSame( 2, (int) get_query_var( 'paged' ) );
	}

	/**
	 * Requesting the original term link redirects to the custom one.
	 */
	public function test_original_term_link_redirects_to_custom_permalink() {
		$this->assertSame(
			home_url( '/custom-news/' ),
			$this->get_redirect( home_url( '/category/news/' ) )
		);
	}

	/**
	 * The page number survives the redirect to the custom permalink.
	 */
	public function test_redirect_keeps_page_number() {
		$this->assertSame(
			home_url( '/custom-news/page/2/' ),
			$this->get_redirect( home_url( '/category/news/page/2/' ) )
		);
	}

	/**
	 * Deleting the term removes its custom permalink.
	 */
	public function test_deleting_term_removes_custom_permalink() {
		wp_delete_term( $this->category->term_id, 'category' );

		$this->assertArrayNotHasKey( 'custom-news/', (array) get_option( 'custom_permalink_table', array() ) );
	}
}
