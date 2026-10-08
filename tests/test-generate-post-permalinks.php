<?php
/**
 * Tests for generating permalinks from the post type structure.
 *
 * @package CustomPermalinks
 */

/**
 * Permalink generation tests.
 */
class Test_Generate_Post_Permalinks extends Custom_Permalinks_TestCase {

	/**
	 * Generate a permalink for a post with the given structure.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $structure Permalink structure for posts.
	 *
	 * @return string|false Generated permalink.
	 */
	private function generate( $post_id, $structure ) {
		update_option( 'custom_permalinks_post_types_settings', array( 'post' => $structure ) );

		$generator = new Custom_Permalinks_Generate_Post_Permalinks();

		return $generator->generate( $post_id, get_post( $post_id ) );
	}

	/**
	 * Create a three-level category tree whose names differ from the slugs.
	 *
	 * @return int Term ID of the deepest category.
	 */
	private function create_category_tree() {
		$top    = self::factory()->category->create(
			array(
				'name' => 'Top Level',
				'slug' => 'top',
			)
		);
		$middle = self::factory()->category->create(
			array(
				'name'   => 'Middle Level',
				'slug'   => 'middle',
				'parent' => $top,
			)
		);

		return self::factory()->category->create(
			array(
				'name'   => 'Leaf Level',
				'slug'   => 'leaf',
				'parent' => $middle,
			)
		);
	}

	/**
	 * Without a structure for the post type nothing is generated.
	 */
	public function test_returns_false_without_structure() {
		$post_id = self::factory()->post->create();

		$this->assertFalse( $this->generate( $post_id, '' ) );
	}

	/**
	 * Date and post name tags are replaced.
	 */
	public function test_replaces_date_and_postname_tags() {
		$post_id = self::factory()->post->create(
			array(
				'post_name' => 'hello-post',
				'post_date' => '2024-03-05 10:20:30',
			)
		);

		$this->assertSame(
			'2024/03/05/hello-post/',
			$this->generate( $post_id, '%year%/%monthnum%/%day%/%postname%/' )
		);
	}

	/**
	 * All parent categories are included by slug.
	 */
	public function test_ctax_parents_uses_term_slugs() {
		$post_id = self::factory()->post->create(
			array(
				'post_name'     => 'hello-post',
				'post_category' => array( $this->create_category_tree() ),
			)
		);

		$this->assertSame(
			'top/middle/leaf/hello-post/',
			$this->generate( $post_id, '%ctax_parents_category%/%postname%/' )
		);
	}

	/**
	 * All parent categories are included by name when the `_name` tag is used.
	 */
	public function test_ctax_parents_name_uses_term_names() {
		$post_id = self::factory()->post->create(
			array(
				'post_name'     => 'hello-post',
				'post_category' => array( $this->create_category_tree() ),
			)
		);

		$this->assertSame(
			'top-level/middle-level/leaf-level/hello-post/',
			$this->generate( $post_id, '%ctax_parents_category_name%/%postname%/' )
		);
	}

	/**
	 * Only the immediate parent category is included.
	 */
	public function test_ctax_parent_uses_immediate_parent_only() {
		$post_id = self::factory()->post->create(
			array(
				'post_name'     => 'hello-post',
				'post_category' => array( $this->create_category_tree() ),
			)
		);

		$this->assertSame(
			'middle/leaf/hello-post/',
			$this->generate( $post_id, '%ctax_parent_category%/%postname%/' )
		);
	}

	/**
	 * Custom tags are filled in through the filter.
	 */
	public function test_custom_tag_uses_filter() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'hello-post' ) );

		add_filter(
			'custom_permalinks_post_permalink_tag',
			function ( $tag ) {
				return 'region' === $tag ? 'europe' : $tag;
			}
		);

		$this->assertSame(
			'europe/hello-post/',
			$this->generate( $post_id, '%custom_permalinks_region%/%postname%/' )
		);
	}
}
