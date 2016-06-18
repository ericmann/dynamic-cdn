<?php
/**
 * Plugin Name: Dynamic CDN
 * Plugin URI:  https://jumping-duck.com
 * Description: Dynamic CDN for front-end assets.
 * Version:     0.3.0
 * Author:      Eric Mann
 * Author URI:  https://eamann.com
 * License:     GPLv2+
 */

/**
 * Copyright (c) 2016 Eric Mann <eric@eamann.com>
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

// Useful global constants

define( 'DYNCDN_VERSION', '0.3.0' );
define( 'DYNCDN_URL',     plugin_dir_url( __FILE__ ) );
define( 'DYNCDN_PATH',    dirname( __FILE__ ) . '/' );

// Requires
require_once DYNCDN_PATH . 'php/class.dynamic_cdn.php';

function dynamic_cdn_init() {
	Dynamic_CDN::factory();

	/**
	 * Allow other plugins (i.e. mu-plugins) to hook in and populate the CDN domain array.
	 */
	do_action( 'dynamic_cdn_first_loaded' );

	Dynamic_CDN::factory()->init();
}
add_action( 'init', 'dynamic_cdn_init' );
