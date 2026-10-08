<?php
/**
 * Tests for the frontend URL helpers.
 *
 * @package CustomPermalinks
 */

/**
 * Frontend helper tests.
 */
class Test_Frontend_Helpers extends Custom_Permalinks_TestCase {

	/**
	 * URLs with and without a trailing page number.
	 *
	 * @return array[]
	 */
	public function data_remove_page_number() {
		return array(
			'trailing slash'    => array( 'custom-news/page/2/', 'custom-news/' ),
			'no trailing slash' => array( 'custom-news/page/2', 'custom-news' ),
			'no page number'    => array( 'custom-news/', 'custom-news/' ),
			'page in the slug'  => array( 'page/about/', 'page/about/' ),
		);
	}

	/**
	 * The trailing page number is removed.
	 *
	 * @dataProvider data_remove_page_number
	 *
	 * @param string $url      URL to clean.
	 * @param string $expected Expected URL.
	 */
	public function test_remove_page_number( $url, $expected ) {
		$this->assertSame( $expected, $this->frontend()->remove_page_number( $url ) );
	}

	/**
	 * The filter keeps the page number in the URL.
	 */
	public function test_remove_page_number_can_be_disabled() {
		add_filter( 'custom_permalinks_disable_remove_page_number', '__return_true' );

		$this->assertSame( 'custom-news/page/2/', $this->frontend()->remove_page_number( 'custom-news/page/2/' ) );
	}

	/**
	 * Double slashes are collapsed without touching the protocol.
	 */
	public function test_remove_double_slash() {
		$this->assertSame(
			'https://example.org/custom/path/',
			$this->frontend()->remove_double_slash( 'https://example.org//custom//path/' )
		);
	}
}
