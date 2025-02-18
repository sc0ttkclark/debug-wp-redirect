<?php
/*
Plugin Name: Debug wp_redirect()
Plugin URI: https://www.scottkclark.com/
Description: Stops and outputs information about redirects done on the front of a site and in the admin area with wp_redirect() and wp_safe_redirect().
Version: 2.2
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

/**
 * Here are a few examples of things you can define in wp-config.php.
 *
 * You need at least one of these:
 * define( 'DEBUG_WP_REDIRECT_ADMIN', true ); // Enable debugging for the dashboard area.
 * define( 'DEBUG_WP_REDIRECT', true ); // Enable debugging for frontend.
 *
 * And then you can limit who sees the debugging messages with at least one of these:
 * define( 'DEBUG_WP_REDIRECT_LOGGED_IN_ADMIN', true ); // Enable debugging only for admin users.
 * define( 'DEBUG_WP_REDIRECT_LOGGED_IN', true ); // Enable debugging only for logged in users (not needed if you enable DEBUG_WP_REDIRECT_LOGGED_IN_ADMIN).
 * define( 'DEBUG_WP_REDIRECT_LOGGED_IN_USER_ID', '12345,555' ); // Enable debugging only for specific logged in user IDs (comma-separated) (the DEBUG_WP_REDIRECT_LOGGED_IN_ADMIN and DEBUG_WP_REDIRECT_LOGGED_IN not needed).
 *
 * You can also choose to log the redirect information in the headers instead of stopping the redirect.
 * define( 'DEBUG_WP_REDIRECT_HEADERS_ONLY', true ); // Log the redirect information in the headers only.
 */

define( 'DEBUG_WP_REDIRECT_PLUGIN_FILE', __FILE__ );
define( 'DEBUG_WP_REDIRECT_PLUGIN_DIR', __DIR__ );

// Maybe enable WP Redirect debugging if the settings are enabled (if enabled for all users).
if ( debug_wp_redirect_is_enabled() ) {
    debug_wp_redirect_enable();
}

/**
 * Maybe enable WP Redirect debugging if the settings are enabled (for logged in users).
 *
 * @since 2.101
 */
function debug_wp_redirect_maybe_load() {
    if ( debug_wp_redirect_is_enabled() ) {
        debug_wp_redirect_enable();
    }
}

add_action( 'plugins_loaded', 'debug_wp_redirect_maybe_load' );

// Allow for debug-wp-redirect.php to be loaded via a single file mu-plugin
if ( file_exists( DEBUG_WP_REDIRECT_PLUGIN_DIR . '/class-settings.php' ) ) {
    include_once DEBUG_WP_REDIRECT_PLUGIN_DIR . '/class-settings.php';

    Settings::instance();
}

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
	// Check if we need to only debug if specific users that are logged in (default: none).
	$logged_in_user_id_check = get_option( 'debug_wp_redirect_enable_logged_in_user_id', '' );

	// Check network option.
	if ( ! $logged_in_user_id_check && is_multisite() ) {
		$logged_in_user_id_check = get_site_option( 'debug_wp_redirect_enable_logged_in_user_id', '' );
	}

	// Check constant.
	if ( defined( 'DEBUG_WP_REDIRECT_LOGGED_IN_USER_ID' ) ) {
		$logged_in_user_id_check = DEBUG_WP_REDIRECT_LOGGED_IN_USER_ID;
	}

	// Check if we need them to be logged in as an admin to debug, but they are not logged in or not an admin.
	if ( ! empty( $logged_in_user_id_check ) ) {
		$logged_in_user_id_check = explode( ',', $logged_in_user_id_check );
		$logged_in_user_id_check = array_map( 'absint', $logged_in_user_id_check );
		$logged_in_user_id_check = array_filter( $logged_in_user_id_check );

		if ( ! empty( $logged_in_user_id_check ) ) {
			if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
				return false;
			}

			return in_array( (int) get_current_user_id(), $logged_in_user_id_check, true );
		}
	}

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

	if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
		$logged_in = __( 'Yes', 'debug-wp-redirect' );
	}

	$stats = [
		__( 'Location', 'debug-wp-redirect' )       => $location,
		__( 'Status', 'debug-wp-redirect' )         => $status,
		__( 'User Logged In', 'debug-wp-redirect' ) => $logged_in,
	];

	if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
		$stats[ __( 'User ID', 'debug-wp-redirect' ) ] = get_current_user_id();
	}

	$backtrace = debug_backtrace();

	// Take out everything after wp_redirect runs
	unset( $backtrace[0], $backtrace[1], $backtrace[2] );

	// Log the redirect information in the headers only.
	if ( defined( 'DEBUG_WP_REDIRECT_HEADERS_ONLY' ) && DEBUG_WP_REDIRECT_HEADERS_ONLY ) {
		foreach ( $stats as $stat => $value ) {
			header( sprintf( 'X-Debug-WP-Redirect-%s: %s', sanitize_title( $stat ), wp_strip_all_tags( $value ) ) );
		}

		$debug_backtrace = debug_wp_redirect_backtrace_headers( $backtrace );

		foreach ( $debug_backtrace as $key => $value ) {
			header( sprintf( 'X-Debug-WP-Redirect-Backtrace-%s: %s', sanitize_title( $key ), wp_strip_all_tags( $value ) ) );
		}

		return $location;
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
	if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
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
	$parsed_data = debug_wp_redirect_backtrace_data( $backtrace );

	if ( empty( $parsed_data ) ) {
		return sprintf(
			'<em>%s</em>',
			esc_html__( 'There was an unknown PHP issue with the backtrace of wp_redirect() using debug_backtrace()', 'debug-wp-redirect' )
		);
	}

	$debug_backtrace = array(
		'<ul>',
	);

	foreach ( $parsed_data as $level => $backtrace_level ) {
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

		if ( isset( $backtrace_level['file'] ) ) {
			$stats[ __( 'File', 'debug-wp-redirect' ) ] = $backtrace_level['file'];
		}

		if ( isset( $backtrace_level['line'] ) ) {
			$stats[ __( 'Line', 'debug-wp-redirect' ) ] = sprintf(
				'#%s',
				$backtrace_level['line']
			);
		}

		if ( isset( $backtrace_level['class'] ) ) {
			$stats[ __( 'Class', 'debug-wp-redirect' ) ] = $backtrace_level['class'];
		}

		if ( isset( $backtrace_level['object'] ) ) {
			if ( is_object( $backtrace_level['object'] ) ) {
				$backtrace_level['object'] = get_class( $backtrace_level['object'] );
			}

			$stats[ __( 'Object', 'debug-wp-redirect' ) ] = $backtrace_level['object'];
		}

		if ( isset( $backtrace_level['type'] ) ) {
			$stats[ __( 'Type', 'debug-wp-redirect' ) ] = $backtrace_level['type'];
		}

		$stats[ __( 'Function', 'debug-wp-redirect' ) ] = $backtrace_level['function'];

		foreach ( $stats as $stat => $value ) {
			$debug_backtrace[] = sprintf(
				"\t\t\t" . '<li><strong>%s</strong>: %s</li>' . "\n",
				esc_html( $stat ),
				esc_html( $value )
			);
		}

		ob_start();
		var_dump( $backtrace_level['args'] );
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
	}

	$debug_backtrace[] = '</ul>';

	return implode( "\n", $debug_backtrace );
}

/**
 * Parse debug_backtrace() array information and return in a format for headers.
 *
 * @since 2.2
 *
 * @param array $backtrace The backtrace information.
 *
 * @return array The backtrace headers.
 */
function debug_wp_redirect_backtrace_headers( $backtrace ): array {
	$parsed_data = debug_wp_redirect_backtrace_data( $backtrace );

	$headers = [];

	if ( empty( $parsed_data ) ) {
		$headers['no-backtrace'] = 1;

		return $headers;
	}

	foreach ( $parsed_data as $level => $backtrace_level ) {
		$header_key = 'level-' . str_pad($level, 2, '0', STR_PAD_LEFT);

		$header_value = [];

		if ( isset( $backtrace_level['file'] ) ) {
			$header_value[] = $backtrace_level['file'];
		}

		if ( isset( $backtrace_level['line'] ) ) {
			$header_value[] = sprintf(
				'#%s',
				$backtrace_level['line']
			);
		}

		if ( isset( $backtrace_level['class'] ) ) {
			$header_value[] = 'class[' . $backtrace_level['class'] . ']';
		}

		if ( isset( $backtrace_level['object'] ) && is_object( $backtrace_level['object'] ) ) {
			$header_value[] = 'object[' . get_class( $backtrace_level['object'] ) . ']';
		}

		if ( isset( $backtrace_level['type'] ) ) {
			$header_value[] = 'type[' . $backtrace_level['type'] . ']';
		}

		$header_value[] = 'function[' . $backtrace_level['function'] . ']';

		$headers[ $header_key ] = implode( ' ', $header_value );
	}

	return $headers;
}

/**
 * Parse debug_backtrace() array information.
 *
 * @since 2.2
 *
 * @param array $backtrace The backtrace information.
 *
 * @return array The backtrace parsed data.
 */
function debug_wp_redirect_backtrace_data( $backtrace ): array {
	$parsed_data = [];

	if ( empty( $backtrace ) ) {
		return $parsed_data;
	}

	$level = 1;

	foreach ( $backtrace as $function ) {
		$backtrace_level = [];

		if ( isset( $function['file'] ) ) {
			$backtrace_level['file'] = $function['file'];
		}

		if ( isset( $function['line'] ) ) {
			$backtrace_level['line'] = $function['line'];
		}

		if ( isset( $function['class'] ) ) {
			$backtrace_level['class'] = $function['class'];
		}

		if ( isset( $function['object'] ) && is_object( $function['object'] ) ) {
			$backtrace_level['object'] = get_class( $function['object'] );
		}

		if ( isset( $function['type'] ) ) {
			$backtrace_level['type'] = $function['type'];
		}

		$backtrace_level['function'] = $function['function'];

		$backtrace_level['args'] = $function['args'];

		$parsed_data[ $level ] = $backtrace_level;

		$level++;
	}

	return $parsed_data;
}
