<?php
/**
 * Plugin Name: Custom Permalinks
 * Plugin URI: https://wordpress.org/plugins/custom-permalinks/
 * Description: Set custom permalinks on a per-post basis
 * Version: 1.4.0
 * Author: Sami Ahmed Siddiqui
 * Author URI: https://www.custompermalinks.com/
 * Donate link: https://www.paypal.me/yasglobal
 * License: GPLv3
 *
 * Text Domain: custom-permalinks
 * Domain Path: /languages/
 *
 * @package CustomPermalinks
 */

/**
 *  Custom Permalinks - Update Permalinks of Post/Pages and Categories
 *  Copyright 2008-2018 Sami Ahmed Siddiqui <sami.siddiqui@yasglobal.com>
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

// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) {
  echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
  exit;
}

class Custom_Permalinks {

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->setup_constants();
    $this->includes();

    add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
  }

  /**
   * Setup plugin constants
   *
   * @access private
   * @since 1.2.18
   * @return void
   */
  private function setup_constants() {
    if ( ! defined( 'CUSTOM_PERMALINKS_FILE' ) ) {
      define( 'CUSTOM_PERMALINKS_FILE', __FILE__ );
    }

    if ( ! defined( 'CUSTOM_PERMALINKS_PLUGIN_VERSION' ) ) {
      define( 'CUSTOM_PERMALINKS_PLUGIN_VERSION', '1.4.0' );
    }

    if ( ! defined( 'CUSTOM_PERMALINKS_PATH' ) ) {
      define( 'CUSTOM_PERMALINKS_PATH', plugin_dir_path( CUSTOM_PERMALINKS_FILE ) );
    }

    if ( ! defined( 'CUSTOM_PERMALINKS_BASENAME' ) ) {
      define( 'CUSTOM_PERMALINKS_BASENAME', plugin_basename( CUSTOM_PERMALINKS_FILE ) );
    }
  }

  /**
   * Include required files
   *
   * @access private
   * @since 1.2.18
   * @return void
   */
  private function includes() {
    require_once(
      CUSTOM_PERMALINKS_PATH . 'frontend/class-custom-permalinks-frontend.php'
    );
    $cp_frontend = new Custom_Permalinks_Frontend();
    $cp_frontend->init();

    require_once(
      CUSTOM_PERMALINKS_PATH . 'frontend/class-custom-permalinks-form.php'
    );
    $cp_form = new Custom_Permalinks_Form();
    $cp_form->init();

    if ( is_admin() ) {
      require_once(
        CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-admin.php'
      );
      new Custom_Permalinks_Admin();

      register_activation_hook( CUSTOM_PERMALINKS_FILE, array( 'Custom_Permalinks', 'plugin_activate' ) );
    }
  }

  /**
   * Loads the plugin language files
   *
   * @access public
   * @since 1.2.22
   * @return void
   */
  public static function plugin_activate() {
    $role = get_role( 'administrator' );
    if ( ! empty( $role ) ) {
      $role->add_cap( 'cp_view_post_permalinks' );
      $role->add_cap( 'cp_view_category_permalinks' );
    }

    add_role(
      'custom_permalinks_manager',
      __( 'Custom Permalinks Manager' ),
      array(
        'cp_view_post_permalinks'     => true,
        'cp_view_category_permalinks' => true
      )
    );
  }

  /**
   * Loads the plugin language files
   *
   * @access public
   * @since 1.2.18
   * @return void
   */
  public function load_textdomain() {
    $current_version = get_option( 'custom_permalinks_plugin_version', -1 );
    if ( -1 === $current_version || CUSTOM_PERMALINKS_PLUGIN_VERSION < $current_version ) {
      Custom_Permalinks::plugin_activate();
      update_option( 'custom_permalinks_plugin_version', CUSTOM_PERMALINKS_PLUGIN_VERSION );
    }
    load_plugin_textdomain( 'custom-permalinks', FALSE,
      basename( dirname( CUSTOM_PERMALINKS_FILE ) ) . '/languages/'
    );
  }
}

new Custom_Permalinks();
