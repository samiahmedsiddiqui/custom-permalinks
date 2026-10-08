=== Custom Permalinks ===
Contributors: sasiddiqui
Tags: permalink, custom url, slug, redirect, seo
Requires at least: 5.9
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 3.3.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A powerful WordPress plugin for full URL control. Set custom permalinks, auto-redirects, and use dynamic tags for ideal site structure and SEO.

== Description ==

You want to take control of your WordPress site's URLs? The **Custom Permalinks** plugin gives you the power to set unique, custom URLs for any post, page, tag, or category. This means you can design your site's structure exactly how you envision it, rather than being limited by WordPress's default settings. When you set a custom permalink, the original post URL will be automatically redirected to your new, customized URL.

=== Key Features ===

* **A custom URL for any content**: Set a unique permalink for any post, page, public custom post type (e.g. WooCommerce products), category, tag, or custom taxonomy term.
* **Permalink structures per post type**: Define a structure for each public post type from tags like `%year%`, `%category%`, or your custom taxonomies. New content gets its permalink automatically, and you can still edit any permalink by hand.
* **Automatic redirects**: The original URL redirects to the custom permalink, so existing links, bookmarks, and search rankings keep working.
* **Multilingual**: Works with WPML and Polylang, with a separate custom permalink for each translation.
* **URLs in any language**: Permalinks in non-Latin scripts, such as Arabic, Thai, or Cyrillic, work as you type them.
* **All permalinks in one place**: The Post Types Permalinks and Taxonomies Permalinks screens list every custom permalink, with search and bulk delete.
* **A role for permalink managers**: The Custom Permalinks Manager role lets non-administrators view and edit permalinks.
* **Developer friendly**: Filters and actions to add your own tags, generate permalinks in code, and control sanitizing and redirects.

=== Getting Started: Plugin Settings ===

Custom Permalinks adds a **Custom Permalinks** menu to your WordPress dashboard:

* **Post Types Permalinks** and **Taxonomies Permalinks**: Every custom permalink on your site, with search and bulk delete.
* **Post Types Settings**: A permalink structure for each public post type, built from the tags below. Leave a structure empty to keep WordPress's default for that post type.

To set a permalink for an individual post, page, category, or tag, edit that item and look for the **Custom Permalink** field near the top (or in the sidebar) of the editor screen — enter the URL path you want and save.

=== Available Tags for Permalink Structures ===

When setting up your custom permalink structures, you can use a variety of tags that will dynamically populate the URL. Here's a breakdown of what's available:

* **%year%**: The year of the post in four digits, eg: 2025
* **%monthnum%**: Month the post was published, in two digits, eg: 01
* **%day%**: Day the post was published in two digits, eg: 02
* **%hour%**: Hour of the day, the post was published, eg: 15
* **%minute%**: Minute of the hour, the post was published, eg: 43
* **%second%**: Second of the minute, the post was published, eg: 33
* **%post_id%**: The unique ID of the post, eg: 123
* **%category%**: A clean version of the category name (its slug). Nested sub-categories will appear as nested directories in the URL.
* **%author%**: The post author's username (login name). Note that this makes usernames visible in your URLs.
* **%postname%**: A clean version of the post or page title (its slug). For example, "This Is A Great Post\!" becomes `this-is-a-great-post` in the URL.
* **%parent_postname%**: Similar to `%postname%`, but uses the immediate parent page's slug if a parent is selected.
* **%parents_postnames%**: Similar to `%postname%`, but includes all parent page slugs if parents are selected.
* **%title%**: The title of the post, converted to a slug. For example, "This Is A Great Post\!" becomes `this-is-a-great-post`. Unlike `%postname%` which is set once, `%title%` automatically updates in the permalink if the post title changes (unless the post is published or the permalink is manually edited).
* **%ctax_TAXONOMY_NAME%**: A clean version of a custom taxonomy's name. Replace `TAXONOMY_NAME` with the actual taxonomy name.
* **%ctax_TAXONOMY_NAME_name%**: The custom taxonomy term's name (instead of its slug). Replace `TAXONOMY_NAME` with the actual taxonomy name.
* **%ctax_parent_TAXONOMY_NAME%**: Similar to `%ctax_TAXONOMY_NAME%`, but includes the immediate parent category/tag slug in the URL if a parent is selected.
* **%ctax_parent_TAXONOMY_NAME_name%**: Similar to `%ctax_TAXONOMY_NAME_name%`, but includes the immediate parent term's name if a parent is selected.
* **%ctax_parents_TAXONOMY_NAME%**: Similar to `%ctax_TAXONOMY_NAME%`, but includes all parent category/tag slugs in the URL if parents are selected.
* **%ctax_parents_TAXONOMY_NAME_name%**: Similar to `%ctax_TAXONOMY_NAME_name%`, but includes all parent term names if parents are selected.
* **%custom_permalinks_TAG_NAME%**: Developers have the flexibility to define their own custom tags (replace `_TAG_NAME` with your desired name). To ensure these tags resolve to the correct permalinks, simply apply the `custom_permalinks_post_permalink_tag` filter.

**Important Note:** For new posts, Custom Permalinks will keep updating the permalink while the post is in draft mode, assuming a structure is defined in the plugin settings. Once the post is published or its permalink is manually updated, the plugin will stop automatic updates for that specific post.

=== Custom Permalinks: Fine-Tuning with Filters ===

Custom Permalinks offers a range of **filters** that empower developers to precisely control its behavior. You can explore all available filters, complete with example code snippets, in our [GitHub repository](https://github.com/samiahmedsiddiqui/custom-permalinks).

**For Assistance:**

* **Premium Users:** If you need assistance implementing these filters, please don't hesitate to reach out to us via our [Premium contact support](https://www.custompermalinks.com/contact-us/).
* **Other Users:** You can also directly reach out to the plugin author via [LinkedIn](https://www.linkedin.com/in/sami-ahmed-siddiqui/).

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

== Frequently Asked Questions ==

= What happens to the original URL when I set a custom permalink? =

Custom Permalinks automatically redirects the original (default) URL to your new custom permalink, so existing links, bookmarks, and search engine rankings keep working.

= Will this affect my site's SEO? =

It shouldn't hurt it — since the original URL redirects to the new one, search engines and existing backlinks continue to resolve correctly. Using clean, descriptive custom permalinks can also make your URLs more readable, which is generally good for SEO.

= Can I set custom permalinks for categories and tags, not just posts and pages? =

Yes. Custom Permalinks supports posts, pages, any public custom post type, and categories/tags (and other custom taxonomy terms).

= Does it work with custom post types, like WooCommerce products? =

Yes. You can set an individual custom permalink on any public custom post type, or define an automatic permalink structure for the entire post type from **Custom Permalinks \> Post Types Settings**.

= Is Custom Permalinks compatible with WPML or Polylang? =

Yes, the plugin is compatible with both WPML and Polylang, including translated posts that each have their own custom permalink.

= Can I use non-English characters in my permalinks? =

Yes. Permalinks in non-Latin scripts, such as Arabic, Thai, Cyrillic, or Chinese, work as you type them. Browsers request these URLs percent-encoded, and Custom Permalinks matches them either way.

= Where do the plugin's translations come from? =

From [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/custom-permalinks/). WordPress downloads them automatically for your site's language. If the plugin shows in English on a translated site, update the translations from **Dashboard \> Updates**. You can also help translate it there.

= Can I let a non-administrator manage permalinks? =

Yes. The plugin adds a dedicated **Custom Permalinks Manager** role (and matching capabilities) so you can let specific non-admin users view or edit permalinks without giving them full administrator access.

= Can I use accented letters, uppercase letters, or extra hyphens in my permalinks? =

By default, Custom Permalinks sanitizes permalinks the same way WordPress core does (lowercase, no accents, no repeated hyphens). Developers can lift these restrictions with the `custom_permalinks_allow_accents`, `custom_permalinks_allow_caps`, and `custom_permalinks_redundant_hyphens` filters. See the [Advanced Customization and Filters section on GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks#advanced-customization-and-filters).

= A page builder's front-end preview (Cornerstone, etc.) breaks or loses its editing mode on pages with a custom permalink =

Some page builders load their live/front-end preview in an iframe using a `POST` request carrying special values the builder needs (for example Cornerstone's `cs_preview_time`). If that page also has a custom permalink, Custom Permalinks may redirect the request, and since redirects are followed as `GET`, the builder's `POST` data is lost and the preview breaks.

You can tell Custom Permalinks to skip its redirect for that request with the `custom_permalinks_avoid_redirect` filter, added to your theme or a site-specific plugin:

`
add_filter( 'custom_permalinks_avoid_redirect', function( $permalink ) {
	if ( isset( $_POST['cs_preview_time'] ) ) { // Adjust to match the field your builder sends.
		return true;
	}
	return false;
} );
`

See the [Advanced Customization and Filters section on GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks#advanced-customization-and-filters) for this and other available filters.

= What happens to my custom permalinks if I deactivate or uninstall the plugin? =

Deactivating the plugin keeps all your saved custom permalinks in the database — reactivating it restores them immediately. Uninstalling (deleting) the plugin permanently removes all saved custom permalinks and plugin settings, and your URLs will revert to WordPress' default permalink structure.

= My custom permalink isn't saving, or the page isn't redirecting — what should I check? =

* Make sure **Settings \> Permalinks** isn't set to "Plain".
* Confirm no other SEO/redirection plugin (Yoast, RankMath, Redirection, etc.) has a conflicting rule for the same URL.
* Check that the permalink isn't already used by another post — Custom Permalinks won't apply a duplicate URL.
* Still stuck? See "Need Help or Found a Bug?" above, or reach out via [GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks) or [Premium support](https://www.custompermalinks.com/contact-us/).

== Screenshots ==

1. Set a custom permalink for any post or page from the Custom Permalinks box in the editor.
2. Post Types Permalinks lists every post, page, and custom post type with a custom permalink, with search and bulk delete.
3. Taxonomies Permalinks lists every category, tag, and custom taxonomy term with a custom permalink.
4. Post Types Settings: build a permalink structure for each post type from the available tags.
5. Set a custom permalink for a category, tag, or custom taxonomy term on its edit screen.

== Changelog ==

= 3.3.1 - Oct 8, 2026 =

* Bug:
  * Fixed the editor's View Post and preview links not updating, or the wrong links being updated, after saving a custom permalink when the post's previous URL contained characters such as `?`, `+` or `(` (e.g. `?p=123` for drafts).
  * Hardened the editor script to only update links with an http(s) URL on the site's own domain.
* Accessibility:
  * Added labels for screen readers to the checkboxes in Post Types Permalinks and Taxonomies Permalinks, the structure fields in Post Types Settings, and the Custom Permalink field on category, tag, and term screens.
  * Raised the text contrast of the structure tags in Post Types Settings and of the plugin descriptions on the About screen, and underlined links in its text.

= 3.3.0 - Oct 8, 2026 =

**Changes to be aware of:**
  * Requires WordPress 5.9 and PHP 7.4 or later, the oldest versions the plugin is now tested on. Sites on older versions keep 3.2.1.
  * Translations now come from [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/custom-permalinks/) instead of files bundled with the plugin. It covers every bundled language and adds Serbian. Sites that installed the plugin outside wordpress.org, or turned off automatic translation updates, show English until the language pack is installed from Dashboard → Updates.
  * WPML translations now always resolve to their own custom permalink. Previously they returned [another language's custom permalink](https://wordpress.org/support/topic/no-input-field-in-the-metabox-for-a-wpml-translation/) depending on the active (admin) language, which listed wrong URLs in Post Types Permalinks and in SEO plugins' indexables and XML sitemaps (e.g. Yoast SEO).
  * `%ctax_parents_TAXONOMY_NAME_name%` now uses the parent terms' names, as documented, instead of their slugs. Permalinks generated from now on change where a parent term's name differs from its slug; saved permalinks are not changed.

**Redirects and URLs:**
  * Fixed [custom permalinks in non-Latin scripts](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/61) (e.g. Thai, Arabic, Cyrillic) returning a 404 or redirecting in an infinite loop, as the browser requests them percent-encoded while they are saved unencoded (or vice versa, depending on the site language).
  * Fixed the trailing-slash redirect and the redirect to the custom permalink dropping the page number (e.g. `/news/page/2` redirected to `/news/` instead of `/news/page/2/`).
  * Added the `custom_permalinks_disable_remove_page_number` filter to keep the `/page/{number}` segment in the requested URL, for [custom archive pages whose pagination doesn't advance](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/110).
  * Fixed the [query string being corrupted](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/82) (e.g. `foo=bar` became `http://foo=bar`) after a custom permalink was resolved.
  * Fixed [comment links](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/80) on paginated comments (e.g. `/my-post/comment-page-2/`) not following the custom permalink's trailing slash, so a custom permalink without a trailing slash no longer gets comment URLs with one, and vice versa.

**WPML and Polylang:**
  * Fixed the [WPML language switcher](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/98) linking back to the current language instead of the translation when languages are in directories (e.g. `/en/`, `/de/`) and the translation uses the same custom permalink.
  * Fixed WPML language directories that differ from the language code (e.g. `/de-de/` for `de`, as in WPML 5.0) not being repaired when duplicated, and the default language getting its directory added to a permalink when "hide the default language directory" is on.
  * Fixed English custom permalinks not being lowercased and cleaned of special characters with WPML 5.0's region-based language codes (e.g. `en-us`).
  * Fixed [Polylang translations sharing the same custom permalink](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/64) (e.g. `/en/technology` and `/zh/technology`) redirecting to the other language when the translation's stored language was missing or out of date.

**Admin screens:**
  * Fixed [Post Types and Taxonomies Permalinks pagination](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/101) showing the first page's results on every page, ignoring sorting and search, when a persistent object cache (e.g. Redis or Memcached) is enabled. The lists now also refresh right after a permalink is added, changed or deleted.
  * Fixed [bulk delete and search](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/75) in Post Types and Taxonomies Permalinks failing with a "headers already sent" warning instead of redirecting.
  * Fixed short titles and permalinks being padded with leading spaces in the [Post Types and Taxonomies Permalinks lists](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/77).
  * Fixed every post of a type showing and saving the same custom permalink when a theme or plugin runs a custom loop in the admin without resetting the global post.
  * Fixed the original permalink showing as the custom permalink in the edit screen's permalink box and in the block editor, and being compared against when saving a post or term.
  * Fixed a fatal error on WordPress 5.9 and 6.0 when saving the Post Types Permalinks settings with the cache flush option, as `wp_cache_flush_group()` needs WordPress 6.1. Object caches that can't flush a single group now get a full cache flush.
  * Fixed the "Custom Permalinks Manager" role name not being translatable.
  * Fixed Post Types Settings showing the WordPress address instead of the site address before each structure, which is wrong when WordPress is installed in a subdirectory.

**Compatibility:**
  * Fixed WooCommerce notices about accessing order data directly on order screens, as the permalink form read post fields from the `WC_Order` object.
  * Fixed posts updated by WP All Import being handled as new posts, which turned permalink regeneration back on and could replace their custom permalink with the post type's structure.
  * Fixed the `seems_utf8()` deprecation notice on WordPress 6.9 and later.

= Earlier versions =

  * For the changelog of earlier versions, please refer to the separate changelog.txt file.

== Upgrade Notice ==

= 3.3.1 =
Recommended update: fixes the editor's View Post and preview links not updating after changing a custom permalink, and improves accessibility of the admin screens with screen reader labels and better text contrast.

= 3.3.0 =
Recommended update: fixes non-Latin permalinks returning 404s or redirect loops, WPML and Polylang permalink issues, admin list pagination with object caches, and a fatal error on WordPress 6.0. Requires WordPress 5.9 and PHP 7.4.

= 3.2.1 =
Recommended update: fixes redirects dropping the query string (e.g. UTM parameters) and an infinite loop that could exhaust server memory when saving a permalink nested under another custom permalink.

= 3.2.0 =
Recommended update: fixes custom permalinks not saving as removed, WooCommerce My Account redirect loops, WPML/Polylang translations resolving to the wrong post, and adds name-based custom taxonomy tags.
