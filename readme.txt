=== Custom Permalinks ===
Contributors: sasiddiqui
Tags: permalink, url, link, address, redirect
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 6.8
Stable tag: 3.1.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A powerful WordPress plugin for full URL control. Set custom permalinks, auto-redirects, and use dynamic tags for ideal site structure and SEO.

== Description ==

You want to take control of your WordPress site's URLs? The **Custom Permalinks** plugin gives you the power to set unique, custom URLs for any post, page, tag, or category. This means you can design your site's structure exactly how you envision it, rather than being limited by WordPress's default settings. When you set a custom permalink, the original post URL will be automatically redirected to your new, customized URL.

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
* **%custom_permalinks_TAG_NAME%**: Developers have the flexibility to define their own custom tags(replace `_TAG_NAME` with your desired name). To ensure these tags resolve to the correct permalinks, simply apply the `custom_permalinks_post_permalink_tag` filter.

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

== Changelog ==

= 3.1.1 - Sep 19, 2025 =

* Bugs:
  * [PHP 8.x Warnings Admin](https://wordpress.org/support/topic/php-8-x-warnings-admin/)
	* [Polylang Redirect Issue](https://wordpress.org/support/topic/polylang-redirect-issue-2/)
	* [Lost password link conflict](https://wordpress.org/support/topic/lost-password-link-conflict/)
	* [Custom Permalinks is conflicting with Gravity View plugin](https://wordpress.org/support/topic/custom-permalinks-is-conflicting-with-gravity-view-plugin/)

= 3.1.0 - Aug 01, 2025 =

**Permalink Generation:**

  * Resolved an issue where the `custom_permalinks_generate_post_permalink` function was creating duplicate permalinks with appended numbers when triggered programmatically. This ensures a unique, clean URL every time.
  * Updated the `sanitize_text_field` function to prevent the truncation of permalink tags, such as `%category%` and `%day%`, guaranteeing your custom permalink structure remains intact.

**Performance:**

  * The `save_post` hook will now only run on public post types. This change prevents unnecessary processing on post types like "Menus", which can significantly improve performance on sites with complex configurations.

**Maintenance:**

  * Improved internationalization (I18N) support.
  * Fixed a bug where the cache group was not properly clearing when using the flush button.

**Language:**

  * Updated the plugin with the latest language packs.

= 3.0.1 - Jul 22, 2025 =

Fix PHP warning on `url_to_postid()` filter.

= 3.0.0 - Jul 22, 2025 =

This release of Custom Permalinks brings significant enhancements to post type permalink management, introduces new customization options, and refines the overall user and developer experience.

**Added**

  * **Post Type Permalink Structures:** Introduced robust functionality to define custom permalink structures for each public Post Type directly within the plugin settings. This allows for automatic URL generation based on predefined tags upon content creation, offering greater flexibility while still allowing manual edits.
  * **New Available Permalink Tags:** Expanded the list of dynamic tags that can be used in permalink structures, including:
    * `%parent_postname%`: For immediate parent page slugs.
    * `%parents_postnames%`: For all parent page slugs.
    * `%title%`: A dynamic slug that updates with post title changes (until published or manually edited).
    * `%ctax_parent_TAXONOMY_NAME%`: For immediate parent custom taxonomy slugs.
    * `%ctax_parents_TAXONOMY_NAME%`: For all parent custom taxonomy slugs.
    * `%custom_permalinks_TAG_NAME%`: Allows developers to define and resolve their own custom tags.
  * **WP All Import Compatibility:** Added support to generate/update permalinks when importing posts using the WP All Import plugin.
  * **New Filter Examples:** Included clear code examples for `custom_permalinks_post_permalink_tag` to set custom values from ACF fields, and for programmatically generating permalinks for single posts and entire post types.

**Improved**

  * **Post Caching:** Enhanced post caching mechanisms and optimized cache deletion upon updates for better performance.
  * **Permalink Retrieval:** Improved logic to allow fetching posts against customized permalinks.
  * **Filter Documentation:** Refined existing filter descriptions and improved code formatting for clarity.
  * **Plugin Purpose Clarity:** Updated documentation to explicitly state that original post URLs will automatically redirect to the customized URLs, ensuring seamless transitions.

= Earlier versions =

  * For the changelog of earlier versions, please refer to the separate changelog.txt file.
