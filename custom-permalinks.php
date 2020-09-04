<?php
/**
 * Plugin Name: Custom Permalinks
 * Plugin URI: https://wordpress.org/plugins/custom-permalinks/
 * Description: Set custom permalinks on a per-post basis
 * Version: 1.7.1
 * Author: Sami Ahmed Siddiqui
 * Author URI: https://www.custompermalinks.com/
 * License: GPLv3
 *
 * Text Domain: custom-permalinks
 * Domain Path: /languages/
 *
 * @package CustomPermalinks
 */

/**
 *  Custom Permalinks - Update Permalinks of Post/Pages and Categories
 *  Copyright 2008-2020 Sami Ahmed Siddiqui <sami.siddiqui@yasglobal.com>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.

 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CUSTOM_PERMALINKS_FILE' ) ) {
	define( 'CUSTOM_PERMALINKS_FILE', __FILE__ );
}

// Include the main Custom Permalinks class.
include_once plugin_dir_path( CUSTOM_PERMALINKS_FILE ) . 'includes/class-custom-permalinks.php';
new Custom_Permalinks();
