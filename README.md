# Custom Permalinks

Lay out your site the way *you* want it. Set the URL of any post, page, tag or category to 
anything you want. Old permalinks will redirect properly to the new address. Custom 
Permalinks give you ultimate control over your site structure.

> :warning: *This plugin is not a replacement for WordPress's built-in permalink system*. 
> Check your WordPress administration's "Permalinks" settings page first, to make sure that 
> this doesn't already meet your needs.

This plugin is only useful for assigning custom permalinks for *individual* posts, pages, 
tags or categories. It will not apply whole permalink structures or automatically apply a 
category's custom permalink to the posts within that category.

> :information_source: If anyone wants the different Structure Tags for their Post Types or 
> use symbols in the URLs So, use the 
> [Permalinks Customizer](https://wordpress.org/plugins/permalinks-customizer/) which is a
> fork of this plugin and contains the enhancement of this plugin.

## Unsupported Characters

Following characters are no longer allowed in the permalinks. 

|      |                |     |     |
|------|----------------|-----|-----|
| `<`  | `>`            | `{` | `}` |
| `\|` | <code>`</code> | `^` | `\` |
| `(`  | `)`            | `[` | `]` |

> :information_source: Permalinks created previously using any of these characters will not
> be affected in anyway. However, new permalinks will not support the use of these 
> characters  as they are not considered to be safe.

## Privacy Policy

This plugin only collects the following information.

1.  Administration Email Address (Only the email that is set in the WordPress setting)
2.  Plugin version
3.  Site Title
4.  WordPress Address (URL)
5.  WordPress version

All this information gets collected when the plugin is installed or updated.

To have any kind of query please feel free to 
[contact us](https://www.custompermalinks.com/contact-us/).

## Filters

### Add `PATH_INFO` in `$_SERVER` Variable

```php
add_filter( 'custom_permalinks_path_info', '__return_true' );
```

### Disable redirects

To disable complete redirects functionality provided by this plugin, add the filter that looks 
like this:

```php
function yasglobal_avoid_redirect( $permalink )
{
    return true;
}
add_filter( 'custom_permalinks_avoid_redirect', 'yasglobal_avoid_redirect' );
```

### Disable specific redirects

To disable any specfic redirect to be processed by this plugin, add the filter that looks like this:

```php
function yasglobal_avoid_redirect( $permalink )
{
    // Replace 'testing-hello-world/' with the permalink you want to avoid
    if ( 'testing-hello-world/' === $permalink ) {
        return true;
    }

    return false;
}
add_filter( 'custom_permalinks_avoid_redirect', 'yasglobal_avoid_redirect' );
```

### Exclude permalink to be processed

To exclude any Permalink to be processed by the plugin, add the filter that looks like this:

```php
function yasglobal_xml_sitemap_url( $permalink )
{
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
function yasglobal_exclude_post_types( $post_type )
{
    // Replace 'custompost' with your post type name
    if ( 'custompost' === $post_type ) {
        return '__true';
    }

    return '__false';
}
add_filter( 'custom_permalinks_exclude_post_type', 'yasglobal_exclude_post_types' );
```

### Exclude Posts

To exclude custom permalink **form**  from any posts (based on ID, Template, etc), add the
filter that looks like this:

```php
function yasglobal_exclude_posts( $post )
{
    if ( 1557 === $post->ID ) {
        return true;
    }

    return false;
}
add_filter( 'custom_permalinks_exclude_posts', 'yasglobal_exclude_posts' );
```

### Remove `like` query

To remove `like` query to being work, add below-mentioned line in your theme 
`functions.php`:

```php
add_filter( 'cp_remove_like_query', '__return_false' );
```

Note: Use `custom_permalinks_like_query` filter if the URLs doesn't works for you after 
upgrading to `v1.2.9`.

## Thanks for the Support

I do not always provide active support for the Custom Permalinks plugin on the 
WordPress.org forums, as I have prioritized the email support. One-on-one email support 
is available to people who bought 
[Custom Permalinks Premium](https://www.custompermalinks.com/#pricing-section) only.

## Bug reports

Bug reports for Custom Permalinks are 
[welcomed on GitHub](https://github.com/samiahmedsiddiqui/custom-permalinks). Please note 
GitHub is not a support forum, and issues that aren't properly qualified as bugs will be closed.

## Installation

This process defines you the steps to follow either you are installing through WordPress 
or Manually from FTP.

## From within WordPress

1.  Visit 'Plugins > Add New'
2.  Search for Custom Permalinks
3.  Activate Custom Permalinks from your Plugins page.

## Manually

1.  Upload the `custom-permalinks` folder to the `/wp-content/plugins/` directory
2.  Activate Custom Permalinks through the 'Plugins' menu in WordPress
