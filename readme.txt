=== Debug wp_redirect() ===
Contributors: sc0ttkclark
Donate link: https://www.scottkclark.com/
Tags: wp_redirect, debug, redirects
Requires at least: 4.5
Tested up to: 5.7.2
Requires PHP: 5.6
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

What the.. Where'd that redirect come from? This plugin helps to uncover redirects as they happen.

== Description ==

*Important:* It is not recommended you leave debugging enabled when you're done, the debug information exposes file paths of files as well as PHP arguments passed into functions from the PHP `debug_backtrace()` which may contain sensitive information.

This is useful for those times when you have a lot of plugins and theme functions interacting and you just need to figure out what / where it's redirecting.

This plugin outputs information about each `wp_redirect()` call done on the front of a site.

= Usage: Enabling with the setting =

You can enable redirect debugging by going to `Settings > Debug wp_redirect()` on your site or in your network settings (if network activated). You have the option to enable debugging for frontend requests and/or admin dashboard requests. You also have the ability to only show debugging if the person is logged in or is an admin.

If you encounter problems with this plugin blocking redirects you need for logging in or being able to disable the redirect, simply rename the plugin or define this in your wp-config.php file: `define( 'DEBUG_WP_REDIRECT', false );`

= Usage: Enabling through wp-config.php =

You can define constants in your wp-config.php file to enable redirect handling for frontend / admin dashboard requests. Constants override any options set in the admin settings page.

* To enable redirect debugging on the frontend of a site: `define( 'DEBUG_WP_REDIRECT', true );`
* To enable redirect debugging in the admin dashboard of a site: `define( 'DEBUG_WP_REDIRECT_ADMIN', true );`
* To only show redirect debugging to logged in admins of a site: `define( 'DEBUG_WP_REDIRECT_LOGGED_IN_ADMIN', true );`
* To only show redirect debugging to logged in users of a site: `define( 'DEBUG_WP_REDIRECT_LOGGED_IN', true );`

= Usage: Enabling debugging through PHP in your own code =

You can programmatically enable/disable debugging when you have your own code you want to start/stop debugging after a certain point.

These functions will start/stop debugging the redirects whether it's on a frontend or admin dashboard request.

* Enable debugging: `debug_wp_redirect_enable()`
* Disable debugging: `debug_wp_redirect_disable()`

= Contribute to make this plugin better =

You can help to make this plugin better through [GitHub](https://github.com/sc0ttkclark/debug-wp-redirect) or [sponsor my time](https://github.com/sponsors/sc0ttkclark).

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Changelog ==

= 2.0 - June 4th, 2021 =
* Implemented new functions `debug_wp_redirect_enable()` and `debug_wp_redirect_disable()` to easily turn debugging on programmatically.
* Added new functionality to allow showing debugging only to those who are logged in.
* Added settings and network settings pages for the plugin so it's easier to configure.
* Now requiring PHP 5.6+
* Updated compatibility with WordPress 5.7.1

= 1.1 =
* Default for plugin is not to output unless `DEBUG_WP_REDIRECT` is defined and set to true
* Updated plugin to allow for translations of text
* Cleaned up debugging output

= 1.0 =
* Just a simple wp_redirect debug plugin, nothing fancy to see here