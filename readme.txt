=== Debug wp_redirect ===
Contributors: sc0ttkclark
Donate link: https://www.scottkclark.com/
Tags: wp_redirect, debug, redirects
Requires at least: 2.1
Tested up to: 4.8
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

What the.. Where'd that wp_redirect come from?

== Description ==

*Important:* It is not recommended you leave debugging enabled when you're done, the debug information exposes file paths of files as well as PHP arguments passed into functions from the PHP debug_backtrace() which may contain sensitive information.

For those times when you have a lot of plugins and theme functions interacting and you just need to figure out what / where it's redirecting.

This plugin outputs information about each wp_redirect call done on the front of a site.

To enable redirect debugging on a site, add this to your wp-config.php file:

`define( 'DEBUG_WP_REDIRECT', true );`

To enable redirect debugging in the admin dashboard of a site, add this to your wp-config.php file:

`define( 'DEBUG_WP_REDIRECT_ADMIN', true );`

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Changelog ==

= 1.1 =
* Default for plugin is not to output unless `DEBUG_WP_REDIRECT` is defined and set to true
* Updated plugin to allow for translations of text
* Cleaned up debugging output

= 1.0 =
* Just a simple wp_redirect debug plugin, nothing fancy to see here