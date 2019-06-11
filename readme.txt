=== Custom Permalinks ===
Contributors: sasiddiqui, michaeltyson
Donate link: https://www.paypal.me/yasglobal
Tags: permalink, url, link, address, custom, redirect, custom post type, GDPR, GDPR Compliant
Requires at least: 2.6
Tested up to: 5.2
Stable tag: 1.5.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html

Set custom permalinks on a per-post, per-tag or per-category basis.

== Description ==

Lay out your site the way *you* want it. Set the URL of any post, page, tag or category to anything you want.
Old permalinks will redirect properly to the new address. Custom Permalinks gives you ultimate control
over your site structure.

Be warned: *This plugin is not a replacement for WordPress's built-in permalink system*. Check your WordPress
administration's "Permalinks" settings page first, to make sure that this doesn't already meet your needs.

This plugin is only useful for assigning custom permalinks for *individual* posts, pages, tags or categories.
It will not apply whole permalink structures or automatically apply a category's custom permalink to the posts
within that category.

> If anyone wants the different Structure Tags for their Post types or use symbols in the URLs So, use the [Permalinks Customizer](https://wordpress.org/plugins/permalinks-customizer/) which is a fork of this plugin and contains the enhancement of this plugin.

== Privacy Policy ==

This plugin doesn't collect/store any user related information.

To have any kind of further query please feel free to [contact us](https://www.custompermalinks.com/contact-us/).

== Filters ==

The plugin provides some filter which may be used according to your needs.

To exclude any Permalink to processed with the plugin so, just add the filter looks like this:
`
function yasglobal_xml_sitemap_url( $permalink ) {
  if ( false !== strpos( $permalink, 'sitemap.xml' )) {
    return '__true';
  }
  return;
}
add_filter( 'custom_permalinks_request_ignore', 'yasglobal_xml_sitemap_url' );
`

To exclude permalink from any post type so, just add the filter looks like this:
`
function yasglobal_exclude_post_types( $post_type ) {
  if ( $post_type == 'custompost' ) {
    return '__true';
  }
  return '__false';
}
add_filter( 'custom_permalinks_exclude_post_type', 'yasglobal_exclude_post_types');
`

Note: custom_permalinks_exclude_post_type doesn't work on the post's permalink which has been created previously.

To remove the like query to being work just add this line in your theme's functions.php:
`
add_filter( 'cp_remove_like_query', '__return_false');
`

Note: Use `custom_permalinks_like_query` filter if the URLs doesn't work for you after upgrading to `v1.2.9`

To add `PATH_INFO` in `$_SERVER` Variable just add this line in your theme's functions.php:
`
add_filter( 'custom_permalinks_path_info', '__return_true');
`

=== Thanks for the Support ===

I do not always provide active support for the Custom Permalinks plugin on the WordPress.org forums, as I have prioritized the email support.
One-on-one email support is available to people who bought [Custom Permalinks Premium](https://www.custompermalinks.com/#pricing-section) only.

=== Bug reports ===

Bug reports for Custom Permalinks are [welcomed on GitHub](https://github.com/yasglobal/custom-permalinks). Please note GitHub is not a support forum, and issues that aren't properly qualified as bugs will be closed.

== Installation ==

This process defines you the steps to follow either you are installing through WordPress or Manually from FTP.

**From within WordPress**

1. Visit 'Plugins > Add New'
2. Search for Custom Permalinks
3. Activate Custom Permalinks from your Plugins page.

**Manually**

1. Upload the `custom-permalinks` folder to the `/wp-content/plugins/` directory
2. Activate Custom Permalinks through the 'Plugins' menu in WordPress

== Changelog ==

= 1.5.1 - Jun 11, 2019 =

  * Bugs
    * Added additional check for [Call to undefined function pll_current_language](https://wordpress.org/support/topic/call-to-undefined-function-pll_current_language/)

= 1.5.0 - Jun 04, 2019 =

  * Bugs
    * [Issue with Polylang Setting Hide Default Language](https://wordpress.org/support/topic/issue-with-polylang-setting-hide-default-language/)
    * Change deprecated actions with appropriate actions

= 1.4.0 - Nov 08, 2018 =

  * Enhancement
    * Added Support for Gutenberg
    * Set meta_keys to be protected to stop duplication in Custom Fields

= 1.3.0 - June 07, 2018 =

  * Enhancement
    * [Conflict with WPML](https://wordpress.org/support/topic/conflict-with-wpml-17/)
    * Avoid appending slashes and use trailingslashit instead

= 1.2.24 - May 31, 2018 =

  * Bug
    * [FATAL ERROR when the administrator role not found](https://wordpress.org/support/topic/fatal-error-on-update-15/)

= 1.2.23 - May 22, 2018 =

  * Enhancement
    * Added Privacy Policy Content for WordPress 4.9.6 and higher.

= Earlier versions =

  * For the changelog of earlier versions, please refer to the separate changelog.txt file.
