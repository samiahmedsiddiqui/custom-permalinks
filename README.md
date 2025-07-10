# Custom Permalinks

> :information_source: In case of found any site breaking issue after upgrading to the latest version then please report the issue on [WordPress Forum](https://wordpress.org/support/plugin/custom-permalinks/) OR [GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks) with complete information to reproduce the issue and move back to the old version. You can download any of the old version from here: https://wordpress.org/plugins/custom-permalinks/advanced/

Lay out your site the way *you* want it. Set the URL of any post, page, tag or category to anything you want. Old permalinks will redirect properly to the new address. Custom Permalinks give you ultimate control over your site structure.

> :warning: *This plugin is not a replacement for WordPress's built-in permalink system*. Check your WordPress administration's "Permalinks" settings page first, to make sure that this doesn't already meet your needs.

This plugin is only useful for assigning custom permalinks for *individual* posts, pages, tags or categories. It will not apply whole permalink structures or automatically apply a category's custom permalink to the posts within that category.

## Custom Permalinks Settings

You can configure the plugin by navigating to the **Settings** under **Custom Permalinks** menu from the WordPress Dashboard.

### Available Tags

You can find all the available tags which are currently supported by the **Custom Permalinks**.

| Tag Name | Description |
| ----------- | ------------- |
| %year% | The year of the post in four digits, eg: 2025 |
| %monthnum% | Month the post was published, in two digits, eg: 01 |
| %day% | Day the post was published in two digits, eg: 02 |
| %hour% | Hour of the day, the post was published, eg: 15 |
| %minute% | Minute of the hour, the post was published, eg: 43 |
| %second% | Second of the minute, the post was published, eg: 33 |
| %post_id% | The unique ID of the post, eg: 123 |
| %postname% | A sanitized version of the title of the post (post slug field on Edit Post/Page panel). <br><br>Eg: “This Is A Great Post!” becomes this-is-a-great-post in the URI. |
| %category% | A sanitized version of the category name (category slug field on New/Edit Category panel). <br><br>Nested sub-categories appear as nested directories in the URI. |
| %author% | A sanitized version of the post author’s name. |
| `%parent_postname%` | This tag is similar as `%postname%`. <br><br>Only the difference is that it prepends the **Immediate Parent Page post slug** with the **actual page post slug** in the URI *if any parent page is selected*. |
| `%parents_postnames%` | This tag is similar as `%postname%`. <br><br>Only the difference is that it prepends all the **Parents Page post slugs** with the **actual page post slug** in the URI *if any parent page is selected*. |
| `%ctax_TAXONOMY_NAME%` | A sanitized version of the custom taxonomy name where the **TAXONOMY_NAME** needs to be replaced with the actual taxonomy name. <br><br>If you want to provide the default slug which is used when the category/taxonomy doesn't be selected so, make sure to provide default name/slug which looks like this: `<%ctax_typey??sales%>`. Value which is written between the `??` and `%>` is used as default slug. |
| `%ctax_parent_TAXONOMY_NAME%` | This tag is similar as `<%ctax_TAXONOMY_NAME%>`. <br><br>Only the difference is that it prepends the **Immediate Parent Slug** in the URI *if any parent category/tag is selected*. |
| `%ctax_parents_TAXONOMY_NAME%` | This tag is similar as `<%ctax_TAXONOMY_NAME%>`. <br><br>Only the difference is that it prepends all the **Parents Slug** in the URI *if any parent category/tag is selected*. |
| `%custom_permalinks_posttype_tag%` | Permits a theme or plugin developer define the tag value using a [filter](#set-custom-value-in-posttype-permalink) |

## Filters

### Set Custom value in Post Type Permalink

This filter allow to replace the custom tag with your desired value. It can be any custom field value or anything else.

```
/**
 * set the text which replace the custom tag from the permalink.
 *
 * @param object $post The post object.
 *
 * @return string text which can be replaced with the custom tag.
 */
function yasglobal_custom_posttype_tag( $post ) {
  return sanitize_title( $post->post_title ) . '-from-sami';
}
add_filter( 'custom_permalinks_posttype_tag', 'yasglobal_custom_posttype_tag', 10, 1 );
```

### Add `PATH_INFO` in `$_SERVER` Variable

```php
add_filter( 'custom_permalinks_path_info', '__return_true' );
```

### Disable Redirects

To disable complete redirects functionality provided by this plugin, add the filter that looks like this:

```php
function yasglobal_avoid_redirect( $permalink ) {
  return true;
}
add_filter( 'custom_permalinks_avoid_redirect', 'yasglobal_avoid_redirect' );
```

### Disable Particular Redirects

To disable any specific redirect to be processed by this plugin, add the filter that looks like this:

```php
function yasglobal_avoid_redirect( $permalink ) {
  // Replace 'testing-hello-world/' with the permalink you want to avoid
  if ( 'testing-hello-world/' === $permalink ) {
    return true;
  }

  return false;
}
add_filter( 'custom_permalinks_avoid_redirect', 'yasglobal_avoid_redirect' );
```

### Exclude Permalink to be processed

To exclude any Permalink to be processed by the plugin, add the filter that looks like this:

```php
function yasglobal_xml_sitemap_url( $permalink ) {
  if ( false !== strpos( $permalink, 'sitemap.xml' ) ) {
    return '__true';
  }

  return;
}
add_filter( 'custom_permalinks_request_ignore', 'yasglobal_xml_sitemap_url' );
```

### Exclude Post Type

To remove custom permalink **form** from any post type, add the filter that looks like this:

```php
function yasglobal_exclude_post_types( $post_type ) {
  // Replace 'custompost' with your post type name
  if ( 'custompost' === $post_type ) {
    return '__true';
  }

  return '__false';
}
add_filter( 'custom_permalinks_exclude_post_type', 'yasglobal_exclude_post_types' );
```

### Exclude Posts

To exclude custom permalink **form** from any posts (based on ID, Template, etc), add the filter that looks like this:

```php
function yasglobal_exclude_posts( $post ) {
  if ( 1557 === $post->ID ) {
    return true;
  }

  return false;
}
add_filter( 'custom_permalinks_exclude_posts', 'yasglobal_exclude_posts' );
```

### Allow Accents Letters

To allow accents letters, please add below-mentioned line in your theme `functions.php`:

```php
function yasglobal_permalink_allow_accents() {
  return true;
}
add_filter( 'custom_permalinks_allow_accents', 'yasglobal_permalink_allow_accents' );
```

### Allow Uppercase Letters

To allow uppercase letters/words, please add below-mentioned line in your theme `functions.php`:

```php
function yasglobal_allow_uppercaps() {
  return true;
}
add_filter( 'custom_permalinks_allow_caps', 'yasglobal_allow_uppercaps' );
```

### Allow Redundant Hyphens

To allow redundant hyphens, please add below-mentioned line in your theme `functions.php`:

```php
function yasglobal_redundant_hyphens() {
  return true;
}
add_filter( 'custom_permalinks_redundant_hyphens', 'yasglobal_redundant_hyphens' );
```

### Manipulate Permalink Before Saving

To make changes in permalink before saving, please use `custom_permalink_before_saving` filter. Here is an example to see how it works.

```php
function yasglobal_permalink_before_saving( $permalink, $post_id ) {
  // Check trialing slash in the permalink.
  if ( substr( $permalink, -1 ) !== '/' ) {
    // If permalink doesn't contain trialing slash then add one.
    $permalink .= '/';
  }

  return $permalink;
}
add_filter( 'custom_permalink_before_saving', 'yasglobal_permalink_before_saving', 10, 2 );
```

### Remove `like` Query

To remove `like` query to being work, add below-mentioned line in your theme `functions.php`:

```php
add_filter( 'cp_remove_like_query', '__return_false' );
```

Note: Use `custom_permalinks_like_query` filter if the URLs doesn't works for you after upgrading to `v1.2.9`.

## Thanks for the Support

I do not always provide active support for the Custom Permalinks plugin on the WordPress.org forums, as I have prioritized the email support. One-on-one email support is available to people who bought [Custom Permalinks Premium](https://www.custompermalinks.com/#pricing-section) only.

## Bug reports

Bug reports for Custom Permalinks are [welcomed on GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks). Please note GitHub is not a support forum, and issues that aren't properly qualified as bugs will be closed.

## Installation

This process defines you the steps to follow either you are installing through WordPress or Manually from FTP.

## From within WordPress

1.  Visit 'Plugins > Add New'
2.  Search for Custom Permalinks
3.  Activate Custom Permalinks from your Plugins page.

## Manually

1.  Upload the `custom-permalinks` folder to the `/wp-content/plugins/` directory
2.  Activate Custom Permalinks through the 'Plugins' menu in WordPress
