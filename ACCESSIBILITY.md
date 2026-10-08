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

From an automated check of the plugin's screens with [axe-core](https://github.com/dequelabs/axe-core) 4.14 against WCAG 2.2 AA (October 2026, version 3.3.0):

* **Post Types Permalinks and Taxonomies Permalinks:** the checkbox on each row has no accessible label, so screen readers announce it without saying which item it selects (WCAG 4.1.2).
* **Post Types Settings:** the structure field for each post type has no programmatically associated label (WCAG 4.1.2), and the structure tag buttons don't have enough text contrast (WCAG 1.4.3).
* **Category, tag, and term screens:** the Custom Permalink field has no programmatically associated label (WCAG 4.1.2).

The editor's Custom Permalinks box passed the check. The About screen hasn't been checked yet.

Automated checks only find some kinds of problems, so there may be others. These are planned to be fixed in an upcoming release.

## Reporting a barrier

If something in Custom Permalinks is hard or impossible to use with your setup, please let us know:

* Open a [bug report](https://github.com/samiahmedsiddiqui/custom-permalinks/issues/new?template=bug_report.yml) and start the title with "Accessibility:", or
* Use the [contact form](https://www.custompermalinks.com/contact-us/) if you'd rather not use GitHub.

It helps to include the screen or field, what you expected and what happened, and your browser and any assistive technology with their versions.
