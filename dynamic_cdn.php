<?php
/**
 * Plugin Name: Dynamic CDN
 * Plugin URI:  http://10up.com
 * Description: Dynamic CDN for front-end assets.
 * Version:     0.2.1
 * Author:      10up
 * Author URI:  http://10up.com
 * License:     GPLv2+
 */

/**
 * Copyright (c) 2014 10up (email : sales@10up.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'DYNCDN_VERSION', '0.2.0' );
define( 'DYNCDN_URL',     plugin_dir_url( __FILE__ ) );
define( 'DYNCDN_PATH',    dirname( __FILE__ ) . '/' );

// Requires
require_once DYNCDN_PATH . 'class.dynamic_cdn.php';

function dynamic_cdn_init() {
	Dynamic_CDN::factory();

	// Allow other plugins (i.e. mu-plugins) to hook in and populate the CDN domain array.
	do_action( 'dynamic_cdn_first_loaded' );

	Dynamic_CDN::factory()->init();
}
add_action( 'init', 'dynamic_cdn_init' );
