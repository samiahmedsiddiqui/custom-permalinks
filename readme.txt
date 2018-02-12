=== Custom Permalinks ===
Contributors: sasiddiqui, michaeltyson
Donate link: https://www.paypal.me/yasglobal
Tags: permalink, url, link, address, custom, redirect, custom post type
Requires at least: 2.6
Tested up to: 4.9
Stable tag: 1.2.17
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html

Set custom permalinks on a per-post, per-tag or per-category basis.

== Description ==

Lay out your site the way *you* want it. Set the URL of any post, page, tag or category to anything you want.
Old permalinks will redirect properly to the new address.  Custom Permalinks gives you ultimate control
over your site structure.

Be warned: *This plugin is not a replacement for WordPress's built-in permalink system*. Check your WordPress
administration's "Permalinks" settings page first, to make sure that this doesn't already meet your needs.

This plugin is only useful for assigning custom permalinks for *individual* posts, pages, tags or categories.
It will not apply whole permalink structures, or automatically apply a category's custom permalink to the posts
within that category.

> If anyone wants the different Structure Tags for their Post types or use symbols in the URLs So, use the [Permalinks Customizer](https://wordpress.org/plugins/permalinks-customizer/) which is a fork of this plugin and contains the enhancement of this plugin.

== Filters ==

Plugin provides some filter which maybe used according to your needs.

To exclude any Permalink to processed with the plugin so, just add the filter looks like this:
`
function check_xml_sitemap_url( $permalink ) {
  if ( false !== strpos( $permalink, 'sitemap.xml' )) {
    return '__true';
  }
  return;
}
add_filter( 'custom_permalinks_request_ignore', 'check_xml_sitemap_url' );
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

Note: custom_permalinks_exclude_post_type doesn't work on the posts permalink which has been created previously.

To make the like query works as it was before so, just add this line in your theme's functions.php:
`
add_filter( 'custom_permalinks_like_query', '__return_true');
`

Note: Use `custom_permalinks_like_query` filter if the URLs doesn't works for you after upgrading to v1.2.9

To add `PATH_INFO` in `$_SERVER` Variable just add this line in your theme's functions.php:
`
add_filter( 'custom_permalinks_path_info', '__return_true');
`

=== Thanks for the Support ===

I does not always provide active support for the Custom Permalinks plugin on the WordPress.org forums, as i have prioritize the email support.
One-on-one email support is available to people who bought [Custom Permalinks Premium](https://www.custompermalinks.com/#pricing-section) only.

=== Bug reports ===

Bug reports for Custom Permalinks are [welcomed on GitHub](https://github.com/yasglobal/custom-permalinks). Please note GitHub is not a support forum, and issues that aren't properly qualified as bugs will be closed.

== Installation ==

This process defines you the steps to follow either you are installing through WordPress or Manually from FTP.

**From within WordPress**

1. Visit 'Plugins > Add New'
2. Search for Custom Permalinks
3. Activate Custom Permalinks from your Plugins page.
4. Go to "after activation" below.

**Manually**

1. Upload the `custom-permalinks` folder to the `/wp-content/plugins/` directory
2. Activate Custom Permalinks through the 'Plugins' menu in WordPress
3. Go to "after activation" below.

== Changelog ==

= 1.2.17 =

  * Fixed Pagination Issue on Comments
  * Optimize Post Pager Query


= 1.2.16 =

  * Added compatibility with Tasty Recipes Plugin

= 1.2.15 =

  * Added filter which can be used to add PATH_INFO in $_SERVER Variable

= 1.2.14 =

  * Enhancement
    * Added PATH_INFO in $_SERVER Variable

  * Bugs
    * $this variable issue on static method

= 1.2.12 =

  * Fixed translation path and pager content issue

= 1.2.11 =

  * Fixed pager issue

= 1.2.10 =

  * Fixed Redirect Issue of Child Pages

= 1.2.9 =

  * Enhancements
    * Added Filter to enable the like query
  * Bugs
    * PHP error displayed on all pages using custom permalinks
    * Removed LIKE Query in default. It only works if the site uses PolyLang,
      AMP Plugins or separately enabled using the provided filter.

= 1.2.8 =

  * Add Order by in request query

= 1.2.7 =

  * Fixed Parse Error

= 1.2.6 =

  * Enhancements
    * Added Filter to Exclude Post types
  * Bugs
    * Fixed Query Issue on parse_request
    * Resolving Issues with Cornerstone

= Earlier versions =

  * For the changelog of earlier versions, please refer to the separate changelog.txt file.
