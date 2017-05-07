<?php
/*
Plugin Name: Debug wp_redirect
Plugin URI: http://scottkclark.com/
Description: Outputs information about each wp_redirect call done on the front of a site
Version: 1.0
Author: Scott Kingsley Clark
Plugin URI: http://scottkclark.com/

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

if ( !is_admin() )
    add_action( 'wp_redirect', 'debug_wp_redirect', 10, 2 );

function debug_wp_redirect ( $location, $status ) {
    echo "<h4>Debug WP Redirect</h4>\n";
    echo "<strong>Location:</strong> {$location}<br />\n";
    echo "<strong>Status:</strong> {$status}<br />\n";

    echo "<strong>Backtrace:</strong><br />\n";

    $backtrace = debug_backtrace();

    // Take out everything after wp_redirect runs
    unset( $backtrace[ 0 ] );
    unset( $backtrace[ 1 ] );
    unset( $backtrace[ 2 ] );
    unset( $backtrace[ 3 ] );

    debug_wp_redirect_backtrace( $backtrace );
}

function debug_wp_redirect_backtrace ( $backtrace ) {
    if ( !empty( $backtrace ) ) {
        echo "<ul>\n";

        foreach ( $backtrace as $function ) {
            echo "\t<li>\n";

            if ( isset( $function[ 'file' ] ) )
                echo "\t\t<strong>File:</strong> {$function['file']}<br />\n";

            if ( isset( $function[ 'line' ] ) )
                echo "\t\t<strong>Line:</strong> #{$function['line']}<br />\n";

            if ( isset( $function[ 'class' ] ) )
                echo "\t\t<strong>Class:</strong> #{$function['class']}<br />\n";

            if ( isset( $function[ 'object' ] ) )
                echo "\t\t<strong>Object:</strong> #{$function['object']}<br />\n";

            if ( isset( $function[ 'type' ] ) )
                echo "\t\t<strong>Type:</strong> #{$function['type']}<br />\n";

            echo "\t\t<strong>Function:</strong> {$function['function']}<br />\n";

            echo "\t\t<strong>Arguments:</strong>\n";
            echo "<pre>\n";
            var_dump( $function[ 'args' ] );
            echo "</pre>\n";

            echo "\t</li>\n";
        }

        echo "</ul>\n";
    }
    else
        echo "<em>No backtrace</em>\n";
}