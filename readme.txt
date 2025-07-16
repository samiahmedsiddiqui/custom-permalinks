=== Custom Permalinks ===
Contributors: sasiddiqui
Tags: permalink, url, link, address, redirect
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 6.8
Stable tag: 2.8.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Custom Permalinks gives you complete control over your WordPress site's URLs, letting you create custom, user-friendly permalinks for any post, page, category, or tag with automatic redirects for old links.

== Description ==

You want to take control of your WordPress site's URLs? The **Custom Permalinks** plugin gives you the power to set unique, custom URLs for any post, page, tag, or category. This means you can design your site's structure exactly how you envision it, rather than being limited by WordPress's default settings.

=== Key Features ===

* **Individual Permalink Control**: Assign unique URLs to any post, page, tag, or category.
* **Site Structure Control**: Gain ultimate control over how your site's URLs are organized.
* **Post Type Permalink Structures (v3.0.0+)**: Define custom permalink structures for each public Post Type using predefined tags, automatically generating URLs upon content creation. You can still manually edit any permalink. If left empty, default settings will apply.

=== Getting Started: Plugin Settings ===

You can configure Custom Permalinks by navigating to **Settings \> Custom Permalinks** in your WordPress Dashboard.

=== Available Tags for Permalink Structures ===

When setting up your custom permalink structures, you can use a variety of tags that will dynamically populate the URL. Here's a breakdown of what's available:

* **%year%**: The year of the post in four digits, eg: 2025
* **%monthnum%**: Month the post was published, in two digits, eg: 01
* **%day%**: Day the post was published in two digits, eg: 02
* **%hour%**: Hour of the day, the post was published, eg: 15
* **%minute%**: Minute of the hour, the post was published, eg: 43
* **%second%**: Second of the minute, the post was published, eg: 33
* **%post_id%**: The unique ID of the post, eg: 123
* **%category%**: A clean version of the category name (its slug). Nested sub-categories will appear as nested directories in the URL..
* **%author%**: A sanitized version of the post author’s name.
* **%postname%**: A clean version of the post or page title (its slug). For example, "This Is A Great Post\!" becomes `this-is-a-great-post` in the URL.
* **%parent_postname%**: Similar to `%postname%`, but uses the immediate parent page's slug if a parent is selected.
* **%parents_postnames%**: Similar to `%postname%`, but includes all parent page slugs if parents are selected.
* **%title%**: The title of the post, converted to a slug. For example, "This Is A Great Post\!" becomes `this-is-a-great-post`. Unlike `%postname%` which is set once, `%title%` automatically updates in the permalink if the post title changes (unless the post is published or the permalink is manually edited).
* **%ctax_TAXONOMY_NAME%**: A clean version of a custom taxonomy's name. Replace `TAXONOMY_NAME` with the actual taxonomy name. You can also provide a default slug for when no category/taxonomy is selected by using `??` (e.g., `%ctax_type??sales%` will use "sales" as a default).
* **%ctax_parent_TAXONOMY_NAME%**: Similar to `%ctax_TAXONOMY_NAME%`, but includes the immediate parent category/tag slug in the URL if a parent is selected.
* **%ctax_parents_TAXONOMY_NAME%**: Similar to `%ctax_TAXONOMY_NAME%`, but includes all parent category/tag slugs in the URL if parents are selected.
* **%custom_permalinks_posttype_tag%**:  This tag allows developers to define its value using a filter.

**Important Note:** For new posts, Custom Permalinks will keep updating the permalink while the post is in draft mode, assuming a structure is defined in the plugin settings. Once the post is published or its permalink is manually updated, the plugin will stop automatic updates for that specific post.

=== Advanced Customization: Filters ===

Custom Permalinks provides several filters for developers to fine-tune its behavior.

* **Set Custom Value in Post Type Permalink (`custom_permalinks_posttype_tag`):** Replace the `%custom_permalinks_posttype_tag%` with your own custom value (e.g., from a custom field).

`
/**
 * Append custom string in the URL.
 *
 * @param object $post The post object.
 *
 * @return string text which can be replaced with the custom tag.
 */
function yasglobal_custom_posttype_tag( $post ) {
  return sanitize_title( $post->post_title ) . '-from-sami';
}
add_filter( 'custom_permalinks_posttype_tag', 'yasglobal_custom_posttype_tag', 10, 1 );
`

* **Add `PATH_INFO` in `$_SERVER` Variable (`custom_permalinks_path_info`):**

`
add_filter( 'custom_permalinks_path_info', '__return_true' );
`

* **Disable All Redirects (`custom_permalinks_avoid_redirect`):**

`
function yasglobal_avoid_redirect( $permalink ) {
  return true;
}
add_filter( 'custom_permalinks_avoid_redirect', 'yasglobal_avoid_redirect' );
`

* **Disable Specific Redirects (`custom_permalinks_avoid_redirect`):** Prevent a particular permalink from being redirected.

`
function yasglobal_avoid_redirect( $permalink ) {
  // Replace 'testing-hello-world/' with the permalink you want to avoid
  if ( 'testing-hello-world/' === $permalink ) {
    return true;
  }

  return false;
}
add_filter( 'custom_permalinks_avoid_redirect', 'yasglobal_avoid_redirect' );
`

* **Exclude Permalink from Processing (`custom_permalinks_request_ignore`):** Skip processing for certain permalinks (useful for XML sitemaps, etc.).

`
function yasglobal_xml_sitemap_url( $permalink ) {
  if ( false !== strpos( $permalink, 'sitemap.xml' ) ) {
    return '__true';
  }

	return;
}
add_filter( 'custom_permalinks_request_ignore', 'yasglobal_xml_sitemap_url' );
`

* **Exclude Post Type from Custom Permalink Form (`custom_permalinks_exclude_post_type`):** Remove the custom permalink form from a specific post type.

`
function yasglobal_exclude_post_types( $post_type ) {
  // Replace 'custompost' with your post type name
  if ( 'custompost' === $post_type ) {
    return '__true';
  }

  return '__false';
}
add_filter( 'custom_permalinks_exclude_post_type', 'yasglobal_exclude_post_types' );
`

* **Exclude Specific Posts from Custom Permalink Form (`custom_permalinks_exclude_posts`):** Remove the custom permalink form from individual posts based on criteria like ID.

`
function yasglobal_exclude_posts( $post ) {
  if ( 1557 === $post->ID ) {
    return true;
  }

  return false;
}
add_filter( 'custom_permalinks_exclude_posts', 'yasglobal_exclude_posts' );
`

* **Allow Accented Letters in Permalinks (`custom_permalinks_allow_accents`):**

`
function yasglobal_permalink_allow_accents() {
  return true;
}
add_filter( 'custom_permalinks_allow_accents', 'yasglobal_permalink_allow_accents' );
`

* **Allow Uppercase Letters in Permalinks (`custom_permalinks_allow_caps`):**

`
function yasglobal_allow_uppercaps() {
  return true;
}
add_filter( 'custom_permalinks_allow_caps', 'yasglobal_allow_uppercaps' );
`

* **Allow Redundant Hyphens in Permalinks (`custom_permalinks_redundant_hyphens`):**

`
function yasglobal_redundant_hyphens() {
  return true;
}
add_filter( 'custom_permalinks_redundant_hyphens', 'yasglobal_redundant_hyphens' );
`

* **Manipulate Permalink Before Saving (`custom_permalink_before_saving`):** Make changes to a permalink before it's saved (e.g., ensure a trailing slash).

`
function yasglobal_permalink_before_saving( $permalink, $post_id ) {
  // Check trialing slash in the permalink.
  if ( '/' !== substr( $permalink, -1 ) ) {
    // If permalink doesn't contain trialing slash then add one.
	  $permalink .= '/';
  }

  return $permalink;
}
add_filter( 'custom_permalink_before_saving', 'yasglobal_permalink_before_saving', 10, 2 );
`

* **Remove `like` Query (`cp_remove_like_query`):** Disable the `like` query functionality.

`
add_filter( 'cp_remove_like_query', '__return_false' );
`

*Note: Use `custom_permalinks_like_query` if URLs don't work after upgrading to v1.2.9.*

=== Need Help or Found a Bug? ===

  * **Support:** For one-on-one email support, consider purchasing [Custom Permalinks Premium](https://www.custompermalinks.com/#pricing-section). While some basic support may be provided on the WordPress.org forums, email support is prioritized for premium users.
  * **Bug Reports:** If you encounter a bug, please report it on [GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks). Make sure to provide complete information to reproduce the issue. GitHub is for bug reports, not general support questions.

If you experience any site-breaking issues after upgrading, please report them on the [WordPress Forum](https://wordpress.org/support/plugin/custom-permalinks/) or [GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks) with detailed information. You can always revert to an older version by downloading it from [https://wordpress.org/plugins/custom-permalinks/advanced/](https://wordpress.org/plugins/custom-permalinks/advanced/).

== Installation ==
You have two ways to install Custom Permalinks:

#### From within WordPress

1.  Go to **Plugins \> Add New** in your WordPress dashboard.
2.  Search for "Custom Permalinks".
3.  Click "Install Now" and then "Activate" the plugin from your Plugins page.

#### Manually via FTP

1.  Download the `custom-permalinks` folder.
2.  Upload the `custom-permalinks` folder to your `/wp-content/plugins/` directory.
3.  Activate Custom Permalinks through the "Plugins" menu in your WordPress dashboard.

== Changelog ==

= 2.8.0 - Apr 29, 2025 =

* Bug:
  * Resolved pagination issue with custom permalinks (now supports /page/{number} format correctly).
* Enhancements:
	* Added compatibility with Polylang 3.7.
	* Metabox is now hidden for post types that are not publicly queryable.

= 2.7.0 - Aug 20, 2024 =

* Bug
  * [Passing null to parameter string is deprecated](https://github.com/samiahmedsiddiqui/custom-permalinks/pull/86)
  * [Fix PHP warning with empty permalink on new page/post](https://github.com/samiahmedsiddiqui/custom-permalinks/pull/87)
	* [Authenticated(Editor+) Stored Cross-Site Scripting](https://github.com/samiahmedsiddiqui/custom-permalinks/pull/96)
* Enhancement:
	* [Improve I18N](https://github.com/samiahmedsiddiqui/custom-permalinks/pull/72)

= 2.6.0 - Aug 15, 2024 =

* Feature Additions:
  * Compatibility with PolyLang Plugin

= 2.5.2 - Feb 14, 2023 =

* Bug
  * [Error in new update](https://wordpress.org/support/topic/error-in-new-update-3/)

= 2.5.1 - Feb 14, 2023 =

* Bug
  * [“http//” is added in front of permalinks](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/71)

= 2.5.0 - Jan 02, 2023 =

* Bugs
  * [Retreiving info from installed plugin (GDPR)](https://wordpress.org/support/topic/retreiving-info-from-installed-plugin-gdpr/)
* Enhancement
	* Same permalink with WPML different domain

= 2.4.0 - Nov 26, 2021 =

* Bugs
  * [filter for leading special characters](https://wordpress.org/support/topic/filter-for-leading-special-characters/)
  * [“search Permalinks” button doesn’t work. (part2)](https://wordpress.org/support/topic/search-permalinks-button-doesnt-work-part2/)
  * [PHP 8 errors on first visit of Taxonomy Permalinks tab](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/59)
  * [Notice: Undefined variable: site_url in custom-permalinks/admin/class-custom-permalinks-post-types-table.php on line 306](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/56)
* Enhancements
  * [Pending Post Preview Link](https://wordpress.org/support/topic/pending-post-preview-link/)

= Earlier versions =

  * For the changelog of earlier versions, please refer to the separate changelog.txt file.
