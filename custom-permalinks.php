<?php
/**
 * Plugin Name: Custom Permalinks
 * Plugin URI: https://www.custompermalinks.com/
 * Description: Set custom permalinks on a per-post basis.
 * Version: 2.5.2
 * Requires at least: 2.6
 * Requires PHP: 5.6
 * Author: Sami Ahmed Siddiqui
 * Author URI: https://www.linkedin.com/in/sami-ahmed-siddiqui/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Text Domain: custom-permalinks
 * Domain Path: /languages/
 *
 * @package CustomPermalinks
 */

/**
 *  Custom Permalinks - Update Permalinks of Post/Pages and Categories
 *  Copyright 2008-2023 Sami Ahmed Siddiqui <sami.siddiqui@yasglobal.com>
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
require_once plugin_dir_path( CUSTOM_PERMALINKS_FILE ) . 'includes/class-custom-permalinks.php';
