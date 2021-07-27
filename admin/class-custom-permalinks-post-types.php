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
	 * Returns the count of records in the database.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return null|int
	 */
	public static function total_permalinks() {
		global $wpdb;

		$total_posts = wp_cache_get( 'total_posts_result', 'custom_permalinks' );
		if ( ! $total_posts ) {
			$sql_query = "
				SELECT COUNT(p.ID) FROM $wpdb->posts AS p
				LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id)
				WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != ''
			";

			// Include search in total results.
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				$sql_query .= ' AND ' . $wpdb->prepare( " pm.meta_value LIKE '%%%s%%'", $_REQUEST['s'] );
			}

			$total_posts = $wpdb->get_var( $sql_query );

			wp_cache_set( 'total_posts_result', $total_posts, 'custom_permalinks' );
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

		$posts = wp_cache_get( 'post_type_results', 'custom_permalinks' );
		if ( ! $posts ) {
			$page_offset = ( $page_number - 1 ) * $per_page;
			$order_by    = 'p.ID';
			$order       = null;
			$sql_query   = "
				SELECT p.ID, p.post_title, p.post_type, pm.meta_value
					FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id)
				WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != ''
			";

			// Include search in total results.
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				$sql_query .= ' AND ' . $wpdb->prepare( " pm.meta_value LIKE '%%%s%%'", $_REQUEST['s'] );
			}

			// Sort the items.
			switch ( isset( $_REQUEST['orderby'] ) ? strtolower( $_REQUEST['orderby'] ) : '' ) {
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

			switch ( isset( $_REQUEST['order'] ) ? strtolower( $_REQUEST['order'] ) : '' ) {
				case 'asc':
					$order = 'ASC';
					break;

				case 'desc':
				default:
					$order = 'DESC';
					break;
			}

			$sql_query .= " ORDER BY $order_by $order LIMIT $page_offset, $per_page";
			$posts      = $wpdb->get_results( $sql_query );

			wp_cache_set( 'post_type_results', $posts, 'custom_permalinks' );
		}

		return $posts;
	}
}
