# Accessibility

## Our commitment

Custom Permalinks aims for its admin screens to meet the [Web Content Accessibility Guidelines (WCAG) 2.2](https://www.w3.org/TR/WCAG22/) at level AA, and follows the [WordPress Accessibility Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/). Where possible it uses WordPress's own admin markup and components, so it inherits WordPress's accessibility work.

Accessibility problems are treated as bugs.

## Scope

This covers the interface the plugin adds to the WordPress admin:

* The **Custom Permalinks** box in the post and page editor.
* The **Custom Permalink** field on category, tag, and custom taxonomy term screens.
* The **Post Types Permalinks** and **Taxonomies Permalinks** lists.
* The **Post Types Settings** screen.
* The **About** screen.

The plugin adds nothing visible to your site's front end; it only changes URLs and redirects. Front-end accessibility depends on your theme and other plugins.

## Supported environments

* WordPress 5.9 or later, with the browsers [WordPress supports](https://make.wordpress.org/core/handbook/best-practices/browser-support/).
* Keyboard use and assistive technologies, such as screen readers, that work with the WordPress admin.

The plugin hasn't yet been tested with specific screen readers. Reports from people who use them are especially welcome.

## Known limitations

An automated check of all the plugin's screens with [axe-core](https://github.com/dequelabs/axe-core) 4.14 against WCAG 2.2 AA (October 2026) finds no issues as of version 3.3.1. Earlier versions had unlabeled checkboxes and fields, and low-contrast text on Post Types Settings and About; see the changelog.

Automated checks only find some kinds of problems, and the plugin hasn't yet been tested with screen readers, so there may be others. Please report anything you run into.

## Reporting a barrier

If something in Custom Permalinks is hard or impossible to use with your setup, please let us know:

* Open a [bug report](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/new?template=bug_report.yml) and start the title with "Accessibility:", or
* Use the [contact form](https://www.custompermalinks.com/contact-us/) if you'd rather not use GitHub.

It helps to include the screen or field, what you expected and what happened, and your browser and any assistive technology with their versions.
