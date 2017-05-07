<?php
/*
Plugin Name: Debug wp_redirect
Plugin URI: http://scottkclark.com/
Description: Outputs information about each wp_redirect call done on the front of a site
Version: 1.1
Author: Scott Kingsley Clark
Author URI: http://scottkclark.com/
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

$debug_disabled = false;
$debug_admin = false;

// Allow disabling of debugging entirely (default: disabled)
if ( ! defined( 'DEBUG_WP_REDIRECT' ) || ! DEBUG_WP_REDIRECT ) {
	$debug_disabled = true;
}

// Allow debugging of admin (default: disabled)
if ( defined( 'DEBUG_WP_REDIRECT_ADMIN' ) && DEBUG_WP_REDIRECT_ADMIN ) {
	$debug_admin = true;
}

if ( ! $debug_disabled && ( ! is_admin() || $debug_admin )  ) {
	add_action( 'wp_redirect', 'debug_wp_redirect', 10, 2 );
}

/**
 * Output debug backtrace of wp_redirect() in a readable format.
 *
 * @param string $location The path to redirect to.
 * @param int    $status   Status code to use.
 *
 * @since 1.0
 */
function debug_wp_redirect( $location, $status ) {

	$logged_in = __( 'No', 'debug-wp-redirect' );

	if ( is_user_logged_in() ) {
		$logged_in = __( 'Yes', 'debug-wp-redirect' );
	}

	$stats = array(
		__( 'Location', 'debug-wp-redirect' )       => $location,
		__( 'Status', 'debug-wp-redirect' )         => $status,
		__( 'User Logged In', 'debug-wp-redirect' ) => $logged_in,
	);

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
	unset( $backtrace[0] );
	unset( $backtrace[1] );
	unset( $backtrace[2] );

	$debug_backtrace = debug_wp_redirect_backtrace( $backtrace );

	printf(
		'<h2>%s</h2>' . "\n"
		. '%s' . "\n",
		esc_html__( 'Backtrace', 'debug-wp-redirect' ),
		$debug_backtrace
	);

}

/**
 * Parse debug_backtrace() array information and return in a readable format.
 *
 * @param array $backtrace
 *
 * @return string
 *
 * @since 1.0
 */
function debug_wp_redirect_backtrace( $backtrace ) {

	if ( ! empty( $backtrace ) ) {
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

		$debug_backtrace = implode( "\n", $debug_backtrace );
	} else {
		$debug_backtrace = sprintf(
			'<em>%s</em>',
			esc_html__( 'There was an unknown PHP issue with the backtrace of wp_redirect() using debug_backtrace()', 'debug-wp-redirect' )
		);
	}

	return $debug_backtrace;

}