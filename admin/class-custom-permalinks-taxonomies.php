<?php
/**
 * Custom Permalinks Taxonomies.
 *
 * @package CustomPermalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomies Permalinks table class.
 */
final class Custom_Permalinks_Taxonomies {
	/**
	 * Sort the terms array in desc order using term id.
	 *
	 * @since 1.2.0
	 * @access public
	 *
	 * @param int $comp1 Value 1.
	 * @param int $comp2 Value 2.
	 *
	 * @return int
	 */
	public static function sort_array( $comp1, $comp2 ) {
		return $comp2['id'] - $comp1['id'];
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
		$total_taxonomies = wp_cache_get( 'total_taxonomies_result', 'custom_permalinks' );
		if ( ! $total_taxonomies ) {
			$search_taxonomy  = array();
			$taxonomy_table   = get_option( 'custom_permalink_table' );
			$total_taxonomies = 0;

			if ( is_array( $taxonomy_table ) ) {
				// phpcs:disable WordPress.Security.NonceVerification.Recommended
				// Include search in total results.
				if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
					$search_value = ltrim(
						sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ),
						'/'
					);
					$search_value = str_replace( '/', '\/', $search_value );
					foreach ( $taxonomy_table as $key => $value ) {
						if ( preg_match( '/' . $search_value . '/', $key ) ) {
							$search_taxonomy[ $key ] = $value;
						}
					}

					$taxonomy_table = $search_taxonomy;
				}
				// phpcs:enable WordPress.Security.NonceVerification.Recommended

				$total_taxonomies = count( $taxonomy_table );
			}

			wp_cache_set( 'total_taxonomies_result', $total_taxonomies, 'custom_permalinks' );
		}

		return $total_taxonomies;
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
		$taxonomies = wp_cache_get( 'taxonomies_results', 'custom_permalinks' );
		if ( ! $taxonomies ) {
			$page_offset     = ( $page_number - 1 ) * $per_page;
			$taxonomy_table  = get_option( 'custom_permalink_table' );
			$all_taxonomies  = $taxonomy_table;
			$search_taxonomy = array();
			$taxonomies      = array();

			if ( is_array( $all_taxonomies ) ) {
				// phpcs:disable WordPress.Security.NonceVerification.Recommended
				// Include search in total results.
				if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
					$search_value = ltrim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ), '/' );
					$search_value = str_replace( '/', '\/', $search_value );
					foreach ( $taxonomy_table as $key => $value ) {
						if ( preg_match( '/' . $search_value . '/', $key ) ) {
							$search_taxonomy[ $key ] = $value;
						}
					}

					$all_taxonomies = $search_taxonomy;
				}
				// phpcs:enable WordPress.Security.NonceVerification.Recommended

				uasort( $all_taxonomies, array( 'Custom_Permalinks_Taxonomies', 'sort_array' ) );
				$pager_limit = -1;
				$skip_count  = -1;
				foreach ( $all_taxonomies as $permalink => $info ) {
					++$skip_count;
					if ( $skip_count < $page_offset ) {
						continue;
					}

					++$pager_limit;
					if ( $pager_limit >= $per_page ) {
						break;
					}

					$taxonomies[] = array(
						'ID'        => $info['id'],
						'permalink' => $permalink,
						'type'      => $info['kind'],
					);
				}
			}

			wp_cache_set( 'taxonomies_results', $taxonomies, 'custom_permalinks' );
		}

		return $taxonomies;
	}
}
