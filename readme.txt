=== Custom Permalinks ===
Contributors: sasiddiqui, michaeltyson
Tags: permalink, url, link, address, custom, redirect, custom post type, GDPR, GDPR Compliant
Requires at least: 2.6
Tested up to: 5.5
Stable tag: 1.7.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html

Set custom permalinks on a per-post, per-tag or per-category basis.

== Description ==

Lay out your site the way *you* want it. Set the URL of any post, page, tag or category to anything you want. Old permalinks will redirect properly to the new address. Custom Permalinks give you ultimate control over your site structure.

> Be warned: *This plugin is not a replacement for WordPress's built-in permalink system*. Check your WordPress administration's "Permalinks" settings page first, to make sure that this doesn't already meet your needs.

This plugin is only useful for assigning custom permalinks for *individual* posts, pages, tags or categories. It will not apply whole permalink structures or automatically apply a category's custom permalink to the posts within that category.

> If anyone wants the different Structure Tags for their Post types or use symbols in the URLs So, use the [Permalinks Customizer](https://wordpress.org/plugins/permalinks-customizer/) which is a fork of this plugin and contains the enhancement of this plugin.

=== Unsupported Characters ===

Following characters are no longer allowed in the permalinks.

* `<`
* `>`
* `{`
* `}`
* `|`
* <code>`</code>
* `^`
* `\`
* `(`
* `)`
* `[`
* `]`

> Permalinks created previously using any of these characters will not be affected in anyway. However, new permalinks will not support the use of these characters as they are not considered to be safe.

== Privacy Policy ==

This plugin only collects the following information.

1. Administration Email Address (Only the email that is set in the WordPress setting)
2. Plugin version
3. Site Title
4. WordPress Address (URL)
5. WordPress version

All this information gets collected when the plugin is installed or updated.

To have any kind of query please feel free to [contact us](https://www.custompermalinks.com/contact-us/).

== Filters ==

=== Add `PATH_INFO` in `$_SERVER` Variable ===

`
add_filter( 'custom_permalinks_path_info', '__return_true' );
`

=== Disable redirects ===

To disable complete redirects functionality provided by this plugin, add the filter that looks like this:

`
function yasglobal_avoid_redirect( $permalink )
{
    return true;
}
add_filter( 'custom_permalinks_avoid_redirect', 'yasglobal_avoid_redirect' );
`

=== Disable specific redirects ===

To disable any specific redirect to be processed by this plugin, add the filter that looks like this:

`
function yasglobal_avoid_redirect( $permalink )
{
    // Replace 'testing-hello-world/' with the permalink you want to avoid
    if ( 'testing-hello-world/' === $permalink ) {
        return true;
    }

    return false;
}
add_filter( 'custom_permalinks_avoid_redirect', 'yasglobal_avoid_redirect' );
`

=== Exclude permalink to be processed ===

To exclude any Permalink to be processed by the plugin, add the filter that looks like this:

`
function yasglobal_xml_sitemap_url( $permalink )
{
    if ( false !== strpos( $permalink, 'sitemap.xml' ) ) {
        return '__true';
    }

    return;
}
add_filter( 'custom_permalinks_request_ignore', 'yasglobal_xml_sitemap_url' );
`

=== Exclude Post Type ===

To remove custom permalink **form** from any post type, add the filter that looks like this:

`
function yasglobal_exclude_post_types( $post_type )
{
    // Replace 'custompost' with your post type name
    if ( 'custompost' === $post_type ) {
        return '__true';
    }

    return '__false';
}
add_filter( 'custom_permalinks_exclude_post_type', 'yasglobal_exclude_post_types' );
`

=== Exclude Posts ===

To exclude custom permalink **form**  from any posts (based on ID, Template, etc), add the filter that looks like this:

`
function yasglobal_exclude_posts( $post )
{
    if ( 1557 === $post->ID ) {
        return true;
    }

    return false;
}
add_filter( 'custom_permalinks_exclude_posts', 'yasglobal_exclude_posts' );
`

=== Remove `like` query ===

To remove `like` query to being work, add below-mentioned line in your theme `functions.php`:
`
add_filter( 'cp_remove_like_query', '__return_false' );
`

Note: Use `custom_permalinks_like_query` filter if the URLs doesn't works for you after upgrading to `v1.2.9`.

=== Thanks for the Support ===

I do not always provide active support for the Custom Permalinks plugin on the WordPress.org forums, as I have prioritized the email support.
One-on-one email support is available to people who bought [Custom Permalinks Premium](https://www.custompermalinks.com/#pricing-section) only.

=== Bug reports ===

Bug reports for Custom Permalinks are [welcomed on GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks). Please note GitHub is not a support forum, and issues that aren't properly qualified as bugs will be closed.

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

= 1.7.1 - Aug 30, 2020 =

  * Bugs
    * Fix PHP notice (start reporting with WordPress 5.5)

= 1.7.0 - Aug 20, 2020 =

  * Bugs
    * [Paged NextGen Galleries Broken with Custom Permalinks 1.62](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/38)
    * [custom permalink issue with weglot](https://wordpress.org/support/topic/custom-permalink-issue-with-weglot/)
    * [Permalinks reverting back to old ones](https://wordpress.org/support/topic/permalinks-reverting-back-to-old-ones/)
    * [A problem with wrongly deleted permalinks](https://wordpress.org/support/topic/a-problem-with-wrongly-deleted-permalinks/)
  * Enhancements
    * [Enhanced security through characters restriction](https://github.com/samiahmedsiddiqui/custom-permalinks#unsupported-characters)
    * Introduce filter to disable specific redirect(s) or complete functionality
    * Automatic replacement of upper case letters to lower case

= 1.6.2 - Aug 10, 2020 =

  * Bugs
    * Forgot to update the version in CSS and JS files in `v1.6.1`

= 1.6.1 - Aug 10, 2020 =

  * Bugs
    * Avoid caching issue by adding version as suffix in CSS and JS files

= 1.6.0 - Aug 08, 2020 =

  * Bugs
    * [Undefined index and undefined variable error](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/28)
    * [count(): Parameter must be an array or an object](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/27)
    * Fix double slash from the permalink form
    * [use 'view_item' label for previewing custom post types](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/31)
    * [Fix PHP 7.4 issues](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/32)
    * Fix Yoast Canonical double slash issue
    * [Replacing category_link with term_link](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/34)
    * [Bug with WPML and Use directory for default language](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/36)
    * Fix Static Homepage redirect issue
  * Enhancements
    * Improved Gutenberg Support
    * Added compatibility for WPML language switcher
    * Add filter to exclude Custom Permalinks for certain posts (based on Post IDs, template, etc)
    * Optimized Code

= Earlier versions =

  * For the changelog of earlier versions, please refer to the separate changelog.txt file.
