<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_Admin
{

    /**
     * Initializes WordPress hooks.
     */
    function __construct()
    {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_filter( 'plugin_action_links_' . CUSTOM_PERMALINKS_BASENAME,
            array( $this, 'settings_link' )
        );
        add_action( 'admin_init', array( $this, 'privacy_policy' ) );
    }

    /**
     * Added Pages in Menu for Settings.
     *
     * @since 1.2.0
     * @access public
     */
    public function admin_menu()
    {
        add_menu_page( 'Custom Permalinks', 'Custom Permalinks',
            'cp_view_post_permalinks', 'cp-post-permalinks',
            array( $this,'post_permalinks_page' ), 'dashicons-admin-links'
        );
        add_submenu_page( 'cp-post-permalinks', 'PostTypes Permalinks',
            'PostTypes Permalinks', 'cp_view_post_permalinks',
            'cp-post-permalinks', array( $this, 'post_permalinks_page' )
        );
        add_submenu_page( 'cp-post-permalinks', 'Taxonomies Permalinks',
            'Taxonomies Permalinks', 'cp_view_category_permalinks',
            'cp-category-permalinks', array( $this, 'taxonomy_permalinks_page' )
        );
        add_submenu_page( 'cp-post-permalinks', 'About Custom Permalinks', 'About',
            'install_plugins', 'cp-about-plugins', array( $this, 'about_plugin' )
        );
    }

    /**
     * Calls another Function which shows the PostTypes Permalinks Page.
     *
     * @since 1.2.0
     * @access public
     */
    public function post_permalinks_page()
    {
        require_once CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-posttypes.php';
        new Custom_Permalinks_PostTypes();

        add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
    }

    /**
     * Calls another Function which shows the Taxonomies Permalinks Page.
     *
     * @since 1.2.0
     * @access public
     */
    public function taxonomy_permalinks_page()
    {
        require_once CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-taxonomies.php';
        new Custom_Permalinks_Taxonomies();

        add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
    }

    /**
     * Add About Plugins Page.
     *
     * @since 1.2.11
     * @access public
     */
    public function about_plugin()
    {
        require_once CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-about.php';
        new Custom_Permalinks_About();

        add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
    }

    /**
     * Add Plugin Support and Follow Message in the footer of Admin Pages.
     *
     * @since 1.2.11
     * @access public
     *
     * @return string Shows version, website link and twitter.
     */
    public function admin_footer_text()
    {
        $footer_text = sprintf(
            __( 'Custom Permalinks version %s by <a href="%s" title="Sami Ahmed Siddiqui Company Website" target="_blank">Sami Ahmed Siddiqui</a> - <a href="%s" title="Support forums" target="_blank">Support forums</a> - Follow on Twitter: <a href="%s" title="Follow Sami Ahmed Siddiqui on Twitter" target="_blank">Sami Ahmed Siddiqui</a>',
                'custom-permalinks'
            ),
            CUSTOM_PERMALINKS_PLUGIN_VERSION, 'https://www.yasglobal.com/',
            'https://wordpress.org/support/plugin/custom-permalinks',
            'https://twitter.com/samisiddiqui91'
        );

        return $footer_text;
    }

    /**
     * Add About and Premium Settings Page Link on the Plugin Page under the
     * Plugin Name.
     *
     * @since 1.2.11
     * @access public
     *
     * @param array $links Contains the Plugin Basic Link (Activate/Deactivate/Delete).
     *
     * @return array Plugin Basic Links and added some custome link for Settings,
     * Contact, and About.
     */
    public function settings_link( $links )
    {
        $about = sprintf( __(
                '<a href="%s" title="About">About</a>', 'custom-permalinks'
            ),
            'admin.php?page=cp-about-plugins'
        );
        $premium_support = sprintf( __(
                '<a href="%s" title="Premium Support" target="_blank">Premium Support</a>',
                'custom-permalinks'
            ),
            'https://www.custompermalinks.com/#pricing-section'
        );
        $contact = sprintf( __(
                '<a href="%s" title="Contact" target="_blank">Contact</a>',
                'custom-permalinks'
            ),
            'https://www.custompermalinks.com/contact-us/'
        );
        array_unshift( $links, $contact );
        array_unshift( $links, $premium_support );
        array_unshift( $links, $about );

        return $links;
    }

    /**
     * Add Privacy Policy about the Plugin.
     *
     * @since 1.2.23
     * @access public
     */
    public function privacy_policy()
    {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }

        $content = sprintf(
            __(
                'This plugin collect information about the site like URL, WordPress version etc.' .
                ' This plugin doesn\'t collect any user related information.' .
                ' To have any kind of further query please feel free to' .
                ' <a href="%s" target="_blank">contact us</a>.',
                'custom-permalinks'
            ),
            'https://www.custompermalinks.com/contact-us/'
        );

        wp_add_privacy_policy_content( 'Custom Permalinks',
            wp_kses_post( wpautop( $content, false ) )
        );
    }
}
