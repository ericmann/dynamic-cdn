<?php
/**
 * Plugin Name: Dynamic CDN
 * Plugin URI:  https://eamann.com
 * Description: Dynamic CDN for front-end assets.
 * Version:     0.4.0
 * Author:      Eric Mann
 * Author URI:  https://eamann.com
 * License:     GPLv2+
 */

/**
 * Copyright (c) 2016-2017 Eric Mann <eric@eamann.com>
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

define( 'DYNCDN_VERSION', '0.4.0' );
define( 'DYNCDN_URL',     plugin_dir_url( __FILE__ ) );
define( 'DYNCDN_PATH',    dirname( __FILE__ ) . '/' );
define( 'DYNCDN_INC',     DYNCDN_PATH . 'includes/' );

// Include Files
require_once DYNCDN_INC . 'classes/DomainManager.php';
require_once DYNCDN_INC . 'functions/core.php';

// Activation/Deactivation
register_activation_hook(   __FILE__, '\EAMann\Dynamic_CDN\Core\activate'   );
register_deactivation_hook( __FILE__, '\EAMann\Dynamic_CDN\Core\deactivate' );

// Bootstrap
EAMann\Dynamic_CDN\Core\setup();
