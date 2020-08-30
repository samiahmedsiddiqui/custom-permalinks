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

// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) {
    echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
    exit;
}

class Custom_Permalinks
{

    /**
     * Class constructor.
     */
    public function __construct()
    {
        if ( ! defined( 'CUSTOM_PERMALINKS_FILE' ) ) {
            define( 'CUSTOM_PERMALINKS_FILE', __FILE__ );
        }

        if ( ! defined( 'CUSTOM_PERMALINKS_PLUGIN_VERSION' ) ) {
            define( 'CUSTOM_PERMALINKS_PLUGIN_VERSION', '1.7.1' );
        }

        if ( ! defined( 'CUSTOM_PERMALINKS_PATH' ) ) {
            define( 'CUSTOM_PERMALINKS_PATH', plugin_dir_path( __FILE__ ) );
        }

        if ( ! defined( 'CUSTOM_PERMALINKS_BASENAME' ) ) {
            define( 'CUSTOM_PERMALINKS_BASENAME', plugin_basename( __FILE__ ) );
        }

        $this->includes();

        add_action( 'plugins_loaded', array( $this, 'check_loaded_plugins' ) );
    }

    /**
     * Include required files.
     *
     * @since 1.2.18
     * @access private
     */
    private function includes()
    {
        $cp_files_path = array(
            'admin'    => CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-admin.php',
            'form'     => CUSTOM_PERMALINKS_PATH . 'frontend/class-custom-permalinks-form.php',
            'frontend' => CUSTOM_PERMALINKS_PATH . 'frontend/class-custom-permalinks-frontend.php',
        );

        require_once $cp_files_path['frontend'];
        require_once $cp_files_path['form'];

        $cp_frontend = new Custom_Permalinks_Frontend();
        $cp_frontend->init();

        $cp_form = new Custom_Permalinks_Form();
        $cp_form->init();

        if ( is_admin() ) {
            include_once $cp_files_path['admin'];
            new Custom_Permalinks_Admin();

            register_activation_hook( CUSTOM_PERMALINKS_FILE,
                array( 'Custom_Permalinks', 'add_roles' )
            );

            register_activation_hook( CUSTOM_PERMALINKS_FILE,
                array( 'Custom_Permalinks', 'activate_details' )
            );

            register_deactivation_hook( CUSTOM_PERMALINKS_FILE,
                array( 'Custom_Permalinks', 'deactivate_details' )
            );
        }
    }

    /**
     * Add role for the view post and category permalinks and by default assign
     * it to the administrator if administrator role exist.
     *
     * @since 1.2.22
     * @access public
     */
    public static function add_roles()
    {
        $admin_role      = get_role( 'administrator' );
        $cp_role         = get_role( 'custom_permalinks_manager' );
        $current_version = get_option( 'custom_permalinks_plugin_version', -1 );

        if ( ! empty( $admin_role ) ) {
            $admin_role->add_cap( 'cp_view_post_permalinks' );
            $admin_role->add_cap( 'cp_view_category_permalinks' );
        }

        if ( empty( $cp_role ) ) {
            add_role( 'custom_permalinks_manager', __( 'Custom Permalinks Manager' ),
                array(
                    'cp_view_post_permalinks'     => true,
                    'cp_view_category_permalinks' => true
                )
            );
        }
    }

    /**
     * Sent details when plugin gets activated / updated ans set installed
     * version in options table.
     *
     * @since 1.6.1
     * @access public
     */
    public static function activate_details()
    {
        require_once CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-updates.php';
        new Custom_Permalinks_Updates( 'activate' );

        update_option( 'custom_permalinks_plugin_version',
            CUSTOM_PERMALINKS_PLUGIN_VERSION
        );
    }

    /**
     * Sent details when plugin gets deactivated.
     *
     * @since 1.6.1
     * @access public
     */
    public function deactivate_details()
    {
        require_once CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-updates.php';
        new Custom_Permalinks_Updates( 'deactivate' );
    }

    /**
     * Check if role not exist then call the function to add it. Update site
     * details if plugin gets updated. Also, loads the plugin language files to
     * support different languages.
     *
     * @since 1.2.18
     * @access public
     */
    public function check_loaded_plugins()
    {
        if ( is_admin() ) {
            $current_version = get_option( 'custom_permalinks_plugin_version', -1 );

            if ( -1 === $current_version
                || $current_version < CUSTOM_PERMALINKS_PLUGIN_VERSION
            ) {
                Custom_Permalinks::activate_details();
                Custom_Permalinks::add_roles();
            }
        }

        load_plugin_textdomain( 'custom-permalinks', FALSE,
            basename( dirname( CUSTOM_PERMALINKS_FILE ) ) . '/languages/'
        );
    }
}

new Custom_Permalinks();
