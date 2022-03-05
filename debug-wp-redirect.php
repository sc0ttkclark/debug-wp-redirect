<?php
/*
Plugin Name: Debug wp_redirect()
Plugin URI: https://www.scottkclark.com/
Description: Stops and outputs information about redirects done on the front of a site and in the admin area with wp_redirect() and wp_safe_redirect().
Version: 2.0.1
Author: Scott Kingsley Clark
Author URI: https://www.scottkclark.com/
Text Domain: debug-wp-redirect

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

use Debug_WP_Redirect\Settings;

define( 'DEBUG_WP_REDIRECT_PLUGIN_FILE', __FILE__ );
define( 'DEBUG_WP_REDIRECT_PLUGIN_DIR', __DIR__ );

if ( debug_wp_redirect_is_enabled() ) {
	debug_wp_redirect_enable();
}

include_once DEBUG_WP_REDIRECT_PLUGIN_DIR . '/class-settings.php';

Settings::instance();

/**
 * Enable the wp_redirect debugging.
 *
 * @since 2.0
 */
function debug_wp_redirect_enable() {
	if ( has_filter( 'wp_redirect', 'debug_wp_redirect' ) ) {
		return;
	}

	add_filter( 'wp_redirect', 'debug_wp_redirect', 10, 2 );
}

/**
 * Disable the wp_redirect debugging.
 *
 * @since 2.0
 */
function debug_wp_redirect_disable() {
	remove_filter( 'wp_redirect', 'debug_wp_redirect' );
}

/**
 * Determine whether a user is allowed to see wp_redirect debugging.
 *
 * @since 2.0
 *
 * @return bool Whether a user is allowed to see wp_redirect debugging.
 */
function debug_wp_redirect_is_user_allowed() {
	// Check if we need to only debug if logged in as an admin (default: disabled).
	$logged_in_admin_check = 1 === (int) get_option( 'debug_wp_redirect_enable_logged_in_admin', 0 );

	// Check network option.
	if ( ! $logged_in_admin_check && is_multisite() ) {
		$logged_in_admin_check = 1 === (int) get_site_option( 'debug_wp_redirect_enable_logged_in_admin', 0 );
	}

	// Check constant.
	if ( defined( 'DEBUG_WP_REDIRECT_LOGGED_IN_ADMIN' ) ) {
		$logged_in_admin_check = DEBUG_WP_REDIRECT_LOGGED_IN_ADMIN;
	}

	// Check if we need them to be logged in as an admin to debug, but they are not logged in or not an admin.
	if ( $logged_in_admin_check ) {
		return function_exists( 'is_user_logged_in' ) && is_user_logged_in() && current_user_can( 'manage_options' );
	}

	// Check if we need to only debug if logged in (default: disabled).
	$logged_in_check = 1 === (int) get_option( 'debug_wp_redirect_enable_logged_in', 0 );

	// Check network option.
	if ( ! $logged_in_check && is_multisite() ) {
		$logged_in_check = 1 === (int) get_site_option( 'debug_wp_redirect_enable_logged_in', 0 );
	}

	// Check constant.
	if ( defined( 'DEBUG_WP_REDIRECT_LOGGED_IN' ) ) {
		$logged_in_check = DEBUG_WP_REDIRECT_LOGGED_IN;
	}

	// Check if we need them to be logged in to debug, but they are not logged in.
	return ! $logged_in_check || ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() );
}

/**
 * Determine whether the wp_redirect debugging is enabled.
 *
 * @since 2.0
 *
 * @return bool Whether the wp_redirect debugging is enabled.
 */
function debug_wp_redirect_is_enabled() {
	// Check if we need them to be logged in to debug, but they are not logged in.
	if ( ! debug_wp_redirect_is_user_allowed() ) {
		return false;
	}

	if ( is_admin() ) {
		// Allow debugging of admin dashboard requests (default: disabled).
		$debugging = 1 === (int) get_option( 'debug_wp_redirect_enable_admin', 0 );

		// Check network option.
		if ( ! $debugging && is_multisite() ) {
			$debugging = 1 === (int) get_site_option( 'debug_wp_redirect_enable_admin', 0 );
		}

		// Check constant.
		if ( defined( 'DEBUG_WP_REDIRECT_ADMIN' ) ) {
			$debugging = DEBUG_WP_REDIRECT_ADMIN;
		}
	} else {
		// Allow debugging of frontend requests (default: disabled).
		$debugging = 1 === (int) get_option( 'debug_wp_redirect_enable_frontend', 0 );

		// Check network option.
		if ( ! $debugging && is_multisite() ) {
			$debugging = 1 === (int) get_site_option( 'debug_wp_redirect_enable_frontend', 0 );
		}

		// Check constant.
		if ( defined( 'DEBUG_WP_REDIRECT' ) ) {
			$debugging = DEBUG_WP_REDIRECT;
		}
	}

	/**
	 * Allow filtering whether the wp_redirect debugging is enabled.
	 *
	 * @since 2.0
	 *
	 * @param bool $debugging Whether the wp_redirect debugging is enabled.
	 */
	return apply_filters( 'debug_wp_redirect_is_enabled', $debugging );
}

/**
 * Output debug backtrace of wp_redirect() in a readable format.
 *
 * @since 1.0
 *
 * @param string $location The path to redirect to.
 * @param int    $status   Status code to use.
 */
function debug_wp_redirect( $location, $status ) {
	// Skip debugging on certain pages.
	if ( is_admin() ) {
		// Skip debugging if we are on our own settings page.
		if ( ! empty( $_POST['option_page'] ) && 'debug-wp-redirect-settings-group' === $_POST['option_page'] ) {
			return $location;
		}

		// On plugin activate/deactivate.
		if ( ! empty( $_GET['action'] ) && in_array( $_GET['action'], [ 'activate', 'deactivate' ], true ) ) {
			return $location;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// Skip certain screens.
		if ( $screen && in_array( $screen->id, [ 'settings-network' ], true ) ) {
			return $location;
		}
	}

	$logged_in = __( 'No', 'debug-wp-redirect' );

	if ( is_user_logged_in() ) {
		$logged_in = __( 'Yes', 'debug-wp-redirect' );
	}

	$stats = [
		__( 'Location', 'debug-wp-redirect' )       => $location,
		__( 'Status', 'debug-wp-redirect' )         => $status,
		__( 'User Logged In', 'debug-wp-redirect' ) => $logged_in,
	];

	if ( is_user_logged_in() ) {
		$stats[ __( 'User ID', 'debug-wp-redirect' ) ] = get_current_user_id();
	}

	printf(
		'<h1>%s</h1>' . "\n",
		esc_html__( 'Debug WP Redirect', 'debug-wp-redirect' )
	);

	foreach ( $stats as $stat => $value ) {
		printf(
			'<h2>%s</h2>' . "\n"
			. '<p>%s</p>' . "\n",
			esc_html( $stat ),
			esc_html( $value )
		);
	}

	$backtrace = debug_backtrace();

	// Take out everything after wp_redirect runs
	unset( $backtrace[0], $backtrace[1], $backtrace[2] );

	$debug_backtrace = debug_wp_redirect_backtrace( $backtrace );

	printf(
		'<h2>%s</h2>' . "\n"
		. '%s' . "\n",
		esc_html__( 'Backtrace', 'debug-wp-redirect' ),
		$debug_backtrace
	);

	printf(
		'<h3>%1$s: Debug wp_redirect()</h3>',
		esc_html__( 'This output is coming from the debugging tool', 'debug-wp-redirect' )
	);

	// Maybe show the link to manage the settings.
	if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
		printf(
			'<p><a href="%1$s">%2$s &raquo;</a></p>',
			esc_url( admin_url( 'options-general.php?page=debug-wp-redirect' ) ),
			esc_html__( 'Go to the Dashboard > Settings > Debug wp_redirect() to disable this output', 'debug-wp-redirect' )
		);
	}
}

/**
 * Parse debug_backtrace() array information and return in a readable format.
 *
 * @since 1.0
 *
 * @param array $backtrace The backtrace information.
 *
 * @return string The backtrace output.
 */
function debug_wp_redirect_backtrace( $backtrace ) {
	if ( empty( $backtrace ) ) {
		return sprintf(
			'<em>%s</em>',
			esc_html__( 'There was an unknown PHP issue with the backtrace of wp_redirect() using debug_backtrace()', 'debug-wp-redirect' )
		);
	}

	$debug_backtrace = array(
		'<ul>',
	);

	$level = 1;

	foreach ( $backtrace as $function ) {
		$debug_backtrace[] = "\t" . '<li>';

		$debug_backtrace[] = sprintf(
			"\t\t" . '<strong>%s</strong>' . "\n",
			sprintf(
				esc_html__( 'Level %d', 'debug-wp-redirect' ),
				$level
			)
		);

		$debug_backtrace[] = "\t\t" . '<ul>';

		$stats = array();

		if ( isset( $function['file'] ) ) {
			$stats[ __( 'File', 'debug-wp-redirect' ) ] = $function['file'];
		}

		if ( isset( $function['line'] ) ) {
			$stats[ __( 'Line', 'debug-wp-redirect' ) ] = sprintf(
				'#%s',
				$function['line']
			);
		}

		if ( isset( $function['class'] ) ) {
			$stats[ __( 'Class', 'debug-wp-redirect' ) ] = $function['class'];
		}

		if ( isset( $function['object'] ) ) {
			if ( is_object( $function['object'] ) ) {
				$function['object'] = get_class( $function['object'] );
			}

			$stats[ __( 'Object', 'debug-wp-redirect' ) ] = $function['object'];
		}

		if ( isset( $function['type'] ) ) {
			$stats[ __( 'Type', 'debug-wp-redirect' ) ] = $function['type'];
		}

		$stats[ __( 'Function', 'debug-wp-redirect' ) ] = $function['function'];

		foreach ( $stats as $stat => $value ) {
			$debug_backtrace[] = sprintf(
				"\t\t\t" . '<li><strong>%s</strong>: %s</li>' . "\n",
				esc_html( $stat ),
				esc_html( $value )
			);
		}

		ob_start();
		var_dump( $function['args'] );
		$args_value = ob_get_clean();

		$debug_backtrace[] = sprintf(
			"\t\t\t" . '<li><strong>%s</strong>:' . "\n"
			. "\t\t\t\t" . '<pre>' . "\n"
			. '%s'
			. '</pre>' . "\n"
			. "\t\t\t" . '</li>' . "\n",
			esc_html__( 'Function Arguments', 'debug-wp-redirect' ),
			$args_value
		);

		$debug_backtrace[] = "\t\t" . '</ul>';
		$debug_backtrace[] = "\t" . '</li>';

		$level++;
	}

	$debug_backtrace[] = '</ul>';

	return implode( "\n", $debug_backtrace );
}
