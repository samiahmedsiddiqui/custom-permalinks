<?php
/**
 * Custom Permalinks Post Types.
 *
 * @package CustomPermalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Types Permalinks table class.
 */
final class Custom_Permalinks_Post_Types {
	/**
	 * Build a cache key from the list query and the last permalink change.
	 *
	 * @since 3.3.0
	 * @access private
	 *
	 * @param string $name Cache name.
	 * @param array  $args Query arguments which affect the result.
	 *
	 * @return string
	 */
	private static function get_cache_key( $name, $args = array() ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		foreach ( array( 's', 'orderby', 'order' ) as $param ) {
			$args[ $param ] = isset( $_REQUEST[ $param ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $param ] ) ) : '';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $name . '_' . md5( wp_json_encode( $args ) ) . '_' . wp_cache_get_last_changed( 'custom_permalinks' );
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return null|int
	 */
	public static function total_permalinks() {
		global $wpdb;

		$cache_name  = self::get_cache_key( 'total_posts_result' );
		$total_posts = wp_cache_get( $cache_name, 'custom_permalinks' );
		if ( false === $total_posts ) {
			$sql_query = "
				SELECT COUNT(p.ID) FROM $wpdb->posts AS p
				LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id)
				WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != ''
			";

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			// Include search in total results.
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				$search_value = ltrim(
					sanitize_text_field(
						wp_unslash( $_REQUEST['s'] )
					),
					'/'
				);
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$total_posts = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(p.ID) FROM {$wpdb->posts} AS p
							LEFT JOIN {$wpdb->postmeta} AS pm ON (p.ID = pm.post_id)
							WHERE pm.meta_key = 'custom_permalink'
								AND pm.meta_value != ''
								AND pm.meta_value LIKE %s",
						'%' . $wpdb->esc_like( $search_value ) . '%'
					)
				);
				// phpcs:enable WordPress.Security.NonceVerification.Recommended
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$total_posts = $wpdb->get_var(
					"SELECT COUNT(p.ID) FROM {$wpdb->posts} AS p
						LEFT JOIN {$wpdb->postmeta} AS pm ON (p.ID = pm.post_id)
						WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != ''"
				);
			}

			wp_cache_set( $cache_name, $total_posts, 'custom_permalinks', 60 );
		}

		return $total_posts;
	}

	/**
	 * Retrieve permalink's data from the database.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param int $per_page    Maximum Results needs to be shown on the page.
	 * @param int $page_number Current page.
	 *
	 * @return array Title, Post Type and Permalink set using this plugin.
	 */
	public static function get_permalinks( $per_page = 20, $page_number = 1 ) {
		global $wpdb;

		$cache_name = self::get_cache_key( 'post_type_results', array( $per_page, $page_number ) );
		$posts      = wp_cache_get( $cache_name, 'custom_permalinks' );
		if ( false === $posts ) {
			$page_offset = ( $page_number - 1 ) * $per_page;
			$order_by    = 'p.ID';
			$order       = null;

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			// Sort the items.
			switch ( isset( $_REQUEST['orderby'] ) ? strtolower( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) ) : '' ) {
				case 'title':
					$order_by = 'p.post_title';
					break;

				case 'type':
					$order_by = 'p.post_type';
					break;

				case 'permalink':
					$order_by = 'pm.meta_value';
					break;
			}

			switch ( isset( $_REQUEST['order'] ) ? strtolower( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) : '' ) {
				case 'asc':
					$order = 'ASC';
					break;

				case 'desc':
				default:
					$order = 'DESC';
					break;
			}

			// Include search in total results.
			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				$search_value = ltrim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ), '/' );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$posts = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT p.ID, p.post_title, p.post_type FROM {$wpdb->posts} AS p
						LEFT JOIN {$wpdb->postmeta} AS pm ON (p.ID = pm.post_id)
						WHERE pm.meta_key = 'custom_permalink'
							AND pm.meta_value != ''
							AND pm.meta_value LIKE %s
						ORDER BY %2s %3s LIMIT %d, %d",
						'%' . $wpdb->esc_like( $search_value ) . '%',
						$order_by,
						$order,
						$page_offset,
						$per_page
					),
					ARRAY_A
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$posts = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT p.ID, p.post_title, p.post_type FROM {$wpdb->posts} AS p
						LEFT JOIN {$wpdb->postmeta} AS pm ON (p.ID = pm.post_id)
						WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != ''
						ORDER BY %1s %2s LIMIT %d, %d",
						$order_by,
						$order,
						$page_offset,
						$per_page
					),
					ARRAY_A
				);
			}
			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			wp_cache_set( $cache_name, $posts, 'custom_permalinks', 60 );
		}

		return $posts;
	}
}
