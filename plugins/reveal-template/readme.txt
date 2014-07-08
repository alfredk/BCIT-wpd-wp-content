=== Reveal Template ===
Contributors: coffee2code
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6ARCFJ9TX3522
Tags: template, theme, debug, presentation, template, design, widget, shortcode, coffee2code
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.6
Tested up to: 3.8
Stable tag: 3.0

Reveal the theme template file used to render the displayed page, via the footer, widget, shortcode, and/or template tag.


== Description ==

Designers and developers know that it can sometimes be confusing and frustrating to determine the exact template being utilized to render the currently displayed page in WordPress. Sometimes page or category specific templates exist, or a page/post has been set by the post author to use a particular template, or the current theme doesn't employ certain templates causing WordPress to fall back to others.

This plugin relieves the aggravation by assisting designers and developers by displaying the template being used to render the currently displayed page in WordPress. This can be shown in one or more of the following methods:

= Site footer =

By default, the theme-relative path to the theme template file used to render the page is shown in the site's footer. The settings page for the plugin, at 'Design' -> 'Reveal Template', allows you to control and configure this particular method. Note: this only works if the theme follows the recommended practice of calling the `wp_footer()` template tag) at some point.

= Widget =

A widget named "Reveal Template" is made available which can be used to display the theme template file used to render the page in any widget location.

= Shortcode =

A shortcode called 'revealtemplate' is made available which can be used in post/page content to display the theme template file used to render the page. Currently it supports two possible attributes:

* 'admin' : Can either be 1 or 0 to indicate if the template name should be revealed to admins only. 1 means to admins only, 0 to all visitors. The default is 1.
* 'type' : The template path types. Must be one of 'absolute', 'filename', 'relative', or 'theme-relative'. Read the documentation for more information on what each of these mean.

Examples: `[revealtemplate type="absolute"]`, `[revealtemplate type="filename" admin="0"]`

= Template Tag =

A template tag is also provided which can be used to display the theme template file used to render the page.

`<?php c2c_reveal_template(); ?>`

By default, `c2c_reveal_template()` will echo the template name. To simply retrieve the template filename rather than displaying it:

`<?php $template = c2c_reveal_template( false ); ?>`

The template tag also takes a second argument which be can be one of the following: absolute, relative, theme-relative, filename. This determines the path style you'd like reported. If not specified, it uses the default defined in the plugin's settings page.

Examples of path types:

* "absolute" : /usr/local/www/yoursite/wp-content/themes/yourtheme/single.php
* "relative" : wp-content/themes/yourtheme/single.php
* "theme-relative" : yourtheme/single.php
* "filename" : single.php

This plugin is primarily intended to be activated on an as-needed basis.

Links: [Plugin Homepage](http://coffee2code.com/wp-plugins/reveal-template/) | [Plugin Directory Page](http://wordpress.org/plugins/reveal-template/) | [Author Homepage](http://coffee2code.com)


== Installation ==

1. Whether installing or updating, whether this plugin or any other, it is always advisable to back-up your data before starting
1. Unzip `reveal-template.zip` inside the `/wp-content/plugins/` directory for your site (or install via the built-in WordPress plugin installer)
1. Activate the plugin through the 'Plugins' admin menu in WordPress
1. Optionally customize the plugin's settings by clicking the plugin's 'Settings' link next to its 'Deactivate' link (still on the Plugins page), or click on the 'Design' -> 'Reveal Template' link, to go to the plugin's admin settings page. Or use the 'Reveal Template' widget. Or use the shortcode.


== Screenshots ==

1. The plugin's settings page.
2. The 'Reveal Template' widget.


== Template Tags ==

The plugin provides one template tag for use in your theme templates, functions.php, or plugins.

= Functions =

* `<?php function c2c_reveal_template( $echo = true, $template_path_type = '' ) ?>`
Formats for output the template path info for the currently rendered template.

= Arguments =

* `$echo` (bool)
Optional. Echo the template info? Default is true.

* `$template_path_type` (string)
Optional. The style of the template's path for return. Accepts: 'absolute', 'relative', 'theme-relative', 'filename'. Default is '', which causes the function to use the template path type configured via the plugin's settings page.

= Examples =

* `<?php //Output the current template
c2c_reveal_template( true, 'theme-relative' );
?>`

* `<?php // Retrieve the value for use in code, so don't display/echo it.
$current_template = c2c_reveal_template( false, 'filename' );
if ( $current_template == 'category-12.php' ) {
   // Do something here
}
?>`


== Changelog ==

= 3.0 (2013-12-28) =
* Add 'Reveal Template' widget
* Add widget framework 008
* Add 'revealtemplate' shortcode
* Fix to recognize proper template names for hooks, 'front_page' and 'comments_popup' (they need underscores)
* Change arguments for object method reveal() (will break code using this method directly)
* Enhance reveal() with 'format' args option to permit custom format string to be directly sent
* Enhance reveal() with 'format_from_settings' args option to permit use of the format string defined via settings even if not being shown in footer
* Enhance reveal() with 'admin_only' args option to control if output should be echoed for just admins or not
* Enhance reveal() with 'return' args option to allow not returning a value for the function if the user isn't permitted to view the output
* Add optional $args argument to c2c_reveal_template() to feed into the identical arg for reveal()
* Add reveal_to_current_user() to contain logic for determining if the current user can be shown the template name/path
* Add get_template_path_types() to allow fetching of the recognized template path types and their descriptions
* Add reveal_in_footer() as function hooked to wp_footer (configures use of reveal() for the wp_footer context)
* Changed default template path type to 'theme-relative'
* Make class variable $instance private
* Add get_instance() class method to obtain singleton instance, creating one if it doesn't exist
* Add get_default_template_path_type() class method to obtain default template path type
* Remove long deprecated reveal_template(); use c2c_reveal_template() instead if you aren't already
* Update plugin framework to 036
* Add unit tests
* For options_page_description(), match method signature of parent class
* Note compatibility through WP 3.8+
* Drop compatibility with version of WP older than 3.6
* Update copyright date (2014)
* Add banner
* Add 'Screenshots' section to readme.txt
* Add screenshot of widget
* Updated existing screenshot
* Regenerate .pot
* Numerous readme.txt text and formatting tweaks
* Change donate link

= 2.3 =
* When set to echo or display in footer, only do so for logged in users with the 'update_themes' capability
* Recognize 'frontpage' and 'index' templates
* Fix recognition of 'commentspopup' template
* Update plugin framework to 035
* Discontinue use of explicit pass-by-reference for objects
* Add check to prevent execution of code if file is directly accessed
* Regenerate .pot
* Re-license as GPLv2 or later (from X11)
* Add 'License' and 'License URI' header tags to readme.txt and plugin file
* Note compatibility through WP 3.5+
* Update copyright date (2013)
* Minor code reformatting (spacing)
* Remove ending PHP close tag
* Move screenshot into repo's assets directory

= 2.2 =
* Update plugin framework to 031
* Remove support for 'c2c_reveal_template' global
* Note compatibility through WP 3.3+
* Drop support for versions of WP older than 3.1
* Move .pot into lang/
* Regenerate .pot
* Add 'Domain Path' directive to top of main plugin file
* Update screenshot for WP 3.3
* Add link to plugin directory page to readme.txt
* Update copyright date (2012)

= 2.1 =
* Update plugin framework to v023
* Save a static version of itself in class variable $instance
* Deprecate use of global variable $c2c_reveal_template to store instance
* Explicitly declare all functions as public
* Add __construct(), activation(), and uninstall()
* Note compatibility through WP 3.2+
* Drop compatibility with versions of WP older than 3.0
* Minor code formatting changes (spacing)
* Add plugin homepage and author links in description in readme.txt

= 2.0.4 =
* Fix bug with theme-relative template path output showing parent theme path instead of child theme path

= 2.0.3 =
* Update plugin framework to version 021
* Explicitly declare all class functions public
* Delete plugin options upon uninstallation
* Note compatibility through WP 3.1+
* Update copyright date (2011)

= 2.0.2 =
* Update plugin framework to version 017

= 2.0.1 =
* Update plugin framework to version 016
* Fix template tag name references in readme.txt to use renamed function name
* Add Template Tags section to readme.txt

= 2.0 =
* Re-implementation by extending C2C_Plugin_013, which among other things adds support for:
    * Reset of options to default values
    * Better sanitization of input values
    * Offload of core/basic functionality to generic plugin framework
    * Additional hooks for various stages/places of plugin operation
    * Easier localization support
* Full localization support
* Add c2c_reveal_template()
* Deprecate reveal_template() in favor of c2c_reveal_template() (but retain for backward compatibility)
* Rename class from 'RevealTemplate' to 'c2c_RevealTemplate'
* Remove docs from top of plugin file (all that and more are in readme.txt)
* Change description
* Add package info to top of plugin file
* Add PHPDoc documentation
* Note compatibility with WP 2.9+, 3.0+
* Drop support for versions of WP older than 2.8
* Minor tweaks to code formatting (spacing)
* Add Changelog and Upgrade Notice sections to readme.txt
* Update copyright date
* Remove trailing whitespace in header docs
* Update screenshot
* Add .pot file

= 1.0.1 =
* Check for 'manage_options' instead of 'edit_posts' permission in order to edit settings
* Use plugins_url() instead of hard-coding path
* Tweak readme tags and donate link
* Note compatibility with WP 2.8+

= 1.0 =
* Initial release


== Upgrade Notice ==

= 3.0 =
Major update: added widget, shortcode, and unit tests; changed default initialization; removed deprecated reveal_template() template tag; compatibility now for WP 3.6-3.8+. Potential for incompatibility if the plugin is used by other code. (Normal users won't be affected.)

= 2.3 =
Recommended update. Highlights: only show in footer for admins; added support for 'front_page' and 'index' templates; updated plugin framework; noted WP 3.5+ compatibility; and more.

= 2.2 =
Recommended update. Highlights: updated plugin framework; noted compatibility with WP 3.3+; dropped compatibility with versions of WP older than 3.1.

= 2.1 =
Recommended update.  Noted WP 3.2 compatibility; dropped support for versions of WP older than 3.0; updated plugin framework; deprecate global variable.

= 2.0.4 =
Bugfix release: fixed bug with theme-relative template path output showing parent theme path instead of child theme path

= 2.0.3 =
Minor release: updated underlying plugin framework; noted compatibility with WP 3.1+ and updated copyright date.

= 2.0.2 =
Minor update.  Updated plugin framework to latest version (017).

= 2.0.1 =
Minor update.  Fixed and expanded readme.txt.  Updated plugin framework to latest version (016).

= 2.0 =
Recommended update. Highlights: re-implementation; full localization support; deprecated reveal_template() (use c2c_reveal_template() instead); misc non-functionality changes; verified WP 3.0 compatibility.
