# Custom Permalinks

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/custom-permalinks?label=stable%20version)](https://wordpress.org/plugins/custom-permalinks/)
[![Tested up to](https://img.shields.io/wordpress/v/custom-permalinks?label=tested%20up%20to)](https://wordpress.org/plugins/custom-permalinks/)
[![WordPress Active Installs](https://img.shields.io/wordpress/plugin/installs/custom-permalinks)](https://wordpress.org/plugins/custom-permalinks/)
[![WordPress Rating](https://img.shields.io/wordpress/plugin/rating/custom-permalinks)](https://wordpress.org/support/plugin/custom-permalinks/reviews/)
[![License: GPLv3](https://img.shields.io/badge/license-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)

Take full control of your WordPress site's URLs. **Custom Permalinks** lets you set a unique, custom URL for any post, page, tag, or category — instead of being limited to WordPress's default permalink structure. When you set a custom permalink, the original URL automatically redirects to your new one, so nothing breaks.

## Table of Contents

- [Key Features](#key-features)
- [Installation](#installation)
- [Getting Started: Plugin Settings](#getting-started-plugin-settings)
- [Available Tags for Permalink Structures](#available-tags-for-permalink-structures)
- [Frequently Asked Questions](#frequently-asked-questions)
- [Advanced Customization and Filters](#advanced-customization-and-filters)
- [Need Help or Found a Bug?](#need-help-or-found-a-bug)

## Key Features

* **Individual Permalink Control**: Assign unique URLs to any post, page, tag, or category.
* **Site Structure Control**: Gain ultimate control over how your site's URLs are organized.
* **Post Type Permalink Structures (v3.0.0+)**: Define custom permalink structures for each public Post Type using predefined tags, automatically generating URLs upon content creation. You can still manually edit any permalink. If left empty, default settings will apply.
* **Automatic Redirects**: Old URLs keep working — visitors and search engines are redirected to the new custom permalink automatically.

## Installation

You have two ways to install Custom Permalinks:

#### From within WordPress

1.  Go to **Plugins \> Add New** in your WordPress dashboard.
2.  Search for "Custom Permalinks".
3.  Click "Install Now" and then "Activate" the plugin from your Plugins page.

#### Manually via FTP

1.  Download the `custom-permalinks` folder.
2.  Upload the `custom-permalinks` folder to your `/wp-content/plugins/` directory.
3.  Activate Custom Permalinks through the "Plugins" menu in your WordPress dashboard.

## Getting Started: Plugin Settings

You can configure Custom Permalinks by navigating to **Settings \> Custom Permalinks** in your WordPress Dashboard.

To set a permalink for an individual post, page, category, or tag, edit that item and look for the **Custom Permalink** field near the top (or in the sidebar) of the editor screen — enter the URL path you want and save.

### Available Tags for Permalink Structures

When setting up your custom permalink structures, you can use a variety of tags that will dynamically populate the URL. Here's a breakdown of what's available:

| Tag Name | Description |
| ----------- | ------------- |
| `%year%` | The year of the post in four digits, eg: 2025 |
| `%monthnum%` | Month the post was published, in two digits, eg: 01 |
| `%day%` | Day the post was published in two digits, eg: 02 |
| `%hour%` | Hour of the day, the post was published, eg: 15 |
| `%minute%` | Minute of the hour, the post was published, eg: 43 |
| `%second%` | Second of the minute, the post was published, eg: 33 |
| `%post_id%` | The unique ID of the post, eg: 123 |
| `%category%` | A clean version of the category name (its slug). Nested sub-categories will appear as nested directories in the URL. |
| `%author%` | A sanitized version of the post author’s name. |
| `%postname%` | A clean version of the post or page title (its slug). For example, "This Is A Great Post\!" becomes `this-is-a-great-post` in the URL. |
| `%parent_postname%` | Similar to `%postname%`, but uses the immediate parent page's slug if a parent is selected. |
| `%parents_postnames%` | Similar to `%postname%`, but includes all parent page slugs if parents are selected. |
| `%title%` | The title of the post, converted to a slug. For example, "This Is A Great Post\!" becomes `this-is-a-great-post`. Unlike `%postname%` which is set once, `%title%` automatically updates in the permalink if the post title changes (unless the post is published or the permalink is manually edited). |
| `%ctax_TAXONOMY_NAME%` | A clean version of a custom taxonomy term's **slug**. Replace `TAXONOMY_NAME` with the actual taxonomy name. You can also provide a default slug when no term is selected using `??` (e.g., `%ctax_type??sales%`). |
| `%ctax_TAXONOMY_NAME_name%` | The custom taxonomy term's **name** (instead of its slug). Replace `TAXONOMY_NAME` with the actual taxonomy name. Supports a default value using `??` when no term is selected (e.g., `%ctax_type_name??Sales%`). |
| `%ctax_parent_TAXONOMY_NAME%` | Similar to `%ctax_TAXONOMY_NAME%`, but includes the immediate parent term's slug in the URL if a parent is selected. |
| `%ctax_parent_TAXONOMY_NAME_name%` | Similar to `%ctax_TAXONOMY_NAME_name%`, but includes the immediate parent term's **name** if a parent is selected. |
| `%ctax_parents_TAXONOMY_NAME%` | Similar to `%ctax_TAXONOMY_NAME%`, but includes all parent term slugs in the URL if parents are selected. |
| `%ctax_parents_TAXONOMY_NAME_name%` | Similar to `%ctax_TAXONOMY_NAME_name%`, but includes all parent term **names** if parents are selected. |
| `%custom_permalinks_TAG_NAME%` | Developers have the flexibility to define their own custom tags(replace `_TAG_NAME` with your desired name). To ensure these tags resolve to the correct permalinks, simply apply the `custom_permalinks_post_permalink_tag` filter. |

**Important Note:** For new posts, Custom Permalinks will keep updating the permalink while the post is in draft mode, assuming a structure is defined in the plugin settings. Once the post is published or its permalink is manually updated, the plugin will stop automatic updates for that specific post.

## Frequently Asked Questions

#### What happens to the original URL when I set a custom permalink?

Custom Permalinks automatically redirects the original (default) URL to your new custom permalink, so existing links, bookmarks, and search engine rankings keep working.

#### Will this affect my site's SEO?

It shouldn't hurt it — since the original URL redirects to the new one, search engines and existing backlinks continue to resolve correctly. Using clean, descriptive custom permalinks can also make your URLs more readable, which is generally good for SEO.

#### Can I set custom permalinks for categories and tags, not just posts and pages?

Yes. Custom Permalinks supports posts, pages, any public custom post type, and categories/tags (and other custom taxonomy terms).

#### Does it work with custom post types, like WooCommerce products?

Yes. You can set an individual custom permalink on any public custom post type, or define an automatic permalink structure for the entire post type from **Settings \> Custom Permalinks**.

#### Is Custom Permalinks compatible with WPML or Polylang?

Yes, the plugin is compatible with both WPML and Polylang, including translated posts that each have their own custom permalink.

#### Can I let a non-administrator manage permalinks?

Yes. The plugin adds a dedicated **Custom Permalinks Manager** role (and matching capabilities) so you can let specific non-admin users view or edit permalinks without giving them full administrator access.

#### Can I use accented letters, uppercase letters, or extra hyphens in my permalinks?

By default, Custom Permalinks sanitizes permalinks the same way WordPress core does (lowercase, no accents, no repeated hyphens). Developers can lift these restrictions with the `custom_permalinks_allow_accents`, `custom_permalinks_allow_caps`, and `custom_permalinks_redundant_hyphens` filters — see [Advanced Customization and Filters](#advanced-customization-and-filters) below.

#### A page builder's front-end preview (Cornerstone, etc.) breaks or loses its editing mode on pages with a custom permalink

Some page builders load their live/front-end preview in an iframe using a `POST` request carrying special values the builder needs (for example Cornerstone's `cs_preview_time`). If that page also has a custom permalink, Custom Permalinks may redirect the request, and since redirects are followed as `GET`, the builder's `POST` data is lost and the preview breaks.

You can tell Custom Permalinks to skip its redirect for that request with the `custom_permalinks_avoid_redirect` filter, added to your theme or a site-specific plugin:

```php
add_filter( 'custom_permalinks_avoid_redirect', function( $permalink ) {
	if ( isset( $_POST['cs_preview_time'] ) ) { // Adjust to match the field your builder sends.
		return true;
	}
	return false;
} );
```

#### What happens to my custom permalinks if I deactivate or uninstall the plugin?

Deactivating the plugin keeps all your saved custom permalinks in the database — reactivating it restores them immediately. **Uninstalling** (deleting) the plugin permanently removes all saved custom permalinks and plugin settings, and your URLs will revert to WordPress' default permalink structure.

#### My custom permalink isn't saving, or the page isn't redirecting — what should I check?

* Make sure **Settings \> Permalinks** isn't set to "Plain".
* Confirm no other SEO/redirection plugin (Yoast, RankMath, Redirection, etc.) has a conflicting rule for the same URL.
* Check that the permalink isn't already used by another post — Custom Permalinks won't apply a duplicate URL.
* Still stuck? See [Need Help or Found a Bug?](#need-help-or-found-a-bug) below.

## Advanced Customization and Filters

Custom Permalinks offers developers a robust set of filters and actions to precisely control its behavior. This section outlines how to leverage these features for tasks like generating permalinks programmatically and fine-tuning URL structures.

---

### Setting a Custom Value in Your Post Type Permalink

Let's say you have a custom post type named "Press" and you want to include the year and month from an ACF (Advanced Custom Fields) date field directly in its permalink.

If your post type's permalink structure is `about/newsroom/press-releases/%custom_permalinks_year%/%custom_permalinks_month%/%postname%/`, here's how you can achieve that with a custom value:

```php
/**
 * Add ACF field year and month in the permalink of the "Press" post type.
 *
 * @param string $custom_tag Custom tag name.
 * @param string $post_type  Post type from where it is called.
 * @param object $post       The post object.
 *
 * @return string Custom tag value which needs to be used.
 */
function custom_permalinks_post_permalink_tag( $custom_tag, $post_type, $post ) {
	$custom_tag_value = $custom_tag;
	if ( 'press' === $post_type &&
		( 'year' === $custom_tag || 'month' === $custom_tag )
	) {
		// Replace the field name 'press_date'.
		$press_date = get_field( 'press_date', $post->ID );
		if ( ! empty( $press_date ) ) {
			$date = new DateTime( $press_date );
		} else {
			$date = new DateTime( $post->post_date );
		}

		if ( 'year' === $custom_tag ) {
			$custom_tag_value = $date->format( 'Y' );
		} else {
			$custom_tag_value = $date->format( 'm' );
		}
	}

	return $custom_tag_value;
}
add_filter( 'custom_permalinks_post_permalink_tag', 'custom_permalinks_post_permalink_tag', 10, 3 );
```

This `custom_permalinks_post_permalink_tag` filter allows you to dynamically insert values into your permalink structure. The example retrieves the `year` and `month` from your ACF date field named `press_date` and inserts them where `%custom_permalinks_year%` and `%custom_permalinks_month%` are defined in your permalink structure. You can integrate this code into your theme's `functions.php` file or a custom plugin to retrieve those values and return them for the respective permalink tags.

---

### Manipulate Permalink Before Saving

Make changes to a permalink string just before it's saved to the database. This is useful for enforcing specific formatting, like ensuring a trailing slash:

```php
function custom_permalinks_permalink_before_saving( $permalink, $post_id, $language_code ) {
	// Check for a trailing slash in the permalink.
	if ( '/' !== substr( $permalink, -1 ) ) {
		// If the permalink doesn't have a trailing slash, add one.
		$permalink .= '/';
	}

	return $permalink;
}
add_filter( 'custom_permalink_before_saving', 'custom_permalinks_permalink_before_saving', 10, 3 );
```

---

### Allow Accented Letters in Permalinks

Enable the use of accented characters in permalinks:

```php
add_filter( 'custom_permalinks_allow_accents', '__return_true' );
```

---

### Allow Uppercase Letters in Permalinks

Enable the use of uppercase characters in permalinks:

```php
add_filter( 'custom_permalinks_allow_caps', '__return_true' );
```

---

### Allow Redundant Hyphens in Permalinks

Allow permalinks to contain redundant hyphens (e.g., --):

```php
add_filter( 'custom_permalinks_redundant_hyphens', '__return_true' );
```

---

### Generating Custom Permalinks

This section outlines how to generate custom permalinks for your WordPress posts, either individually or for an entire post type. This functionality allows site owners and developers to programmatically create or regenerate permalinks without manual updates.

#### Generate Permalink for a Single Post ID

To generate a custom permalink for a specific post, you can use the following action hook, replacing `$post_id` with the actual ID of your post:

```php
do_action( 'custom_permalinks_generate_post_permalink', $post_id );
```

This simple line of code triggers the permalink generation process for the specified post.

#### Generate Permalinks for an Entire Post Type

For bulk updates or after structural changes, you might need to regenerate permalinks for all posts within a specific post type. Here's an example demonstrating how to do this for a custom post type called `product`.

```php
/**
 * Generates custom permalinks for all posts within a specified post type.
 *
 * @param string $post_type The post type to generate permalinks for.
 */
function custom_permalinks_generate_permalinks_for_post_type( $post_type ) {
	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1, // Get all posts of this type.
		'post_status'    => 'publish', // Only published posts.
		'fields'         => 'ids', // Only retrieve post IDs for efficiency.
	);

	$posts = get_posts( $args );
	if ( $posts ) {
		foreach ( $posts as $post_id ) {
			do_action( 'custom_permalinks_generate_post_permalink', $post_id );
		}
		echo "Permalinks for all '{$post_type}' posts have been regenerated.";
	} else {
		echo "No '{$post_type}' posts found to regenerate permalinks for.";
	}
}

// Example usage: Call the function to regenerate permalinks for the 'product' post type.
custom_permalinks_generate_permalinks_for_post_type( 'product' );
```

This PHP function iterates through all published posts of the specified `$post_type` and applies the `do_action` hook to each, ensuring their permalinks are regenerated. You can integrate this code into your theme's `functions.php` file or a custom plugin, typically running it as a one-time process or via an administrative trigger.

---

### Exclude Post Type from Custom Permalink Form

Remove the custom permalink settings form from the edit screen of a specific post type:

```php
function custom_permalinks_exclude_post_types( $post_type ) {
	// Replace 'custompost' with the name of the post type you want to exclude.
	if ( 'custompost' === $post_type ) {
		return '__true';
	}

	return '__false';
}
add_filter( 'custom_permalinks_exclude_post_type', 'custom_permalinks_exclude_post_types' );
```

---

### Exclude Specific Posts from Custom Permalink Form

Remove the custom permalink settings form from individual posts based on criteria like their ID:

```php
function custom_permalinks_exclude_posts( $post ) {
	// Replace '1557' with the ID of the post you want to exclude.
	if ( 1557 === $post->ID ) {
		return true;
	}

	return false;
}
add_filter( 'custom_permalinks_exclude_posts', 'custom_permalinks_exclude_posts' );
```

---

### Exclude Permalink from Processing

Skip processing for specific permalinks, which can be useful for URLs like XML sitemaps:

```php
function custom_permalinks_xml_sitemap_url( $permalink ) {
	if ( false !== strpos( $permalink, 'sitemap.xml' ) ) {
		// Use '__true' to specifically ignore this permalink.
		return '__true';
	}

	// Return null (or nothing) to allow default processing.
	return;
}
add_filter( 'custom_permalinks_request_ignore', 'custom_permalinks_xml_sitemap_url' );
```

---

### Add `PATH_INFO` to `$_SERVER` Variable

Enable `PATH_INFO` to be added to the `$_SERVER` superglobal variable:

```php
add_filter( 'custom_permalinks_path_info', '__return_true' );
```

---

### Disable All Redirects

To prevent Custom Permalinks from performing any redirects:

```php
function custom_permalinks_avoid_redirect( $permalink ) {
	// Always return true to disable all redirects.
	return true;
}
add_filter( 'custom_permalinks_avoid_redirect', 'custom_permalinks_avoid_redirect' );
```

---

### Disable Specific Redirects

Prevent a particular permalink from being redirected by checking its value:

```php
function custom_permalinks_avoid_redirect( $permalink ) {
	// Replace 'testing-hello-world/' with the permalink you want to exclude from redirection.
	if ( 'testing-hello-world/' === $permalink ) {
		return true;
	}
	return false;
}
add_filter( 'custom_permalinks_avoid_redirect', 'custom_permalinks_avoid_redirect' );
```

---

### Disable `like` Query

Disable the `like` query functionality, which can impact URL matching in some specific scenarios:

```php
add_filter( 'cp_remove_like_query', '__return_false' );
```

*Note: Use `cp_remove_like_query` if URLs don't work after upgrading to v1.2.9.*

---

### For Assistance:

* **Premium Users:** If you need assistance implementing these filters, please don't hesitate to reach out to us via our [Premium contact support](https://www.custompermalinks.com/contact-us/).
* **Other Users:** You can also directly reach out to the plugin author via [LinkedIn](https://www.linkedin.com/in/sami-ahmed-siddiqui/).

## Need Help or Found a Bug?

* **Support:** For one-on-one email support, consider purchasing [Custom Permalinks Premium](https://www.custompermalinks.com/#pricing-section). While some basic support may be provided on the WordPress.org forums, email support is prioritized for premium users.
* **Bug Reports:** If you encounter a bug, please report it on [GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks). Make sure to provide complete information to reproduce the issue. GitHub is for bug reports, not general support questions.

If you experience any site-breaking issues after upgrading, please report them on the [WordPress Forum](https://wordpress.org/support/plugin/custom-permalinks/) or [GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks) with detailed information. You can always revert to an older version by downloading it from [https://wordpress.org/plugins/custom-permalinks/advanced/](https://wordpress.org/plugins/custom-permalinks/advanced/).
