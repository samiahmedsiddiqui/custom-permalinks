<?php
/**
 * CustomPermalinks Uninstall
 *
 * Deletes Option and Post Permalinks on uninstalling the Plugin.
 *
 * @package CustomPermalinks
 * @since 1.2.18
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_post_meta_by_key( 'custom_permalink' );
delete_option( 'custom_permalink_table' );

$wp_role = get_role( 'administrator' );
if ( ! empty( $wp_role ) ) {
	$wp_role->remove_cap( 'cp_view_post_permalinks' );
	$wp_role->remove_cap( 'cp_view_category_permalinks' );
}

$wp_role = get_role( 'custom_permalinks_manager' );
if ( ! empty( $wp_role ) ) {
	$wp_role->remove_cap( 'cp_view_post_permalinks' );
	$wp_role->remove_cap( 'cp_view_category_permalinks' );

	remove_role( 'custom_permalinks_manager' );
}

// Clear any cached data that has been removed.
wp_cache_flush();
