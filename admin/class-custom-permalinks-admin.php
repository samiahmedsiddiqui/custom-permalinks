<?php
/**
 * Custom Permalinks Admin.
 *
 * @package CustomPermalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create admin menu, add privacy policy etc.
 */
class Custom_Permalinks_Admin {
	/**
	 * Css file suffix extension.
	 *
	 * @var string
	 */
	private $css_file_suffix = '.min.css';

	/**
	 * Initializes WordPress hooks.
	 */
	public function __construct() {
		/*
		 * Css file suffix (version number with extension).
		 */
		$this->css_file_suffix = '-' . CUSTOM_PERMALINKS_VERSION . '.min.css';

		add_action( 'admin_init', array( $this, 'privacy_policy' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'init', array( $this, 'allow_redirection' ) );

		add_filter(
			'plugin_action_links_' . CUSTOM_PERMALINKS_BASENAME,
			array( $this, 'settings_link' )
		);
	}

	/**
	 * Added Pages in Menu for Settings.
	 *
	 * @since 1.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_menu_page(
			'Custom Permalinks',
			'Custom Permalinks',
			'cp_view_post_permalinks',
			'cp-post-permalinks',
			array( $this, 'post_permalinks_page' ),
			'dashicons-admin-links'
		);
		$post_permalinks_hook     = add_submenu_page(
			'cp-post-permalinks',
			'Post Types Permalinks',
			'Post Types Permalinks',
			'cp_view_post_permalinks',
			'cp-post-permalinks',
			array( $this, 'post_permalinks_page' )
		);
		$taxonomy_permalinks_hook = add_submenu_page(
			'cp-post-permalinks',
			'Taxonomies Permalinks',
			'Taxonomies Permalinks',
			'cp_view_category_permalinks',
			'cp-taxonomy-permalinks',
			array( $this, 'taxonomy_permalinks_page' )
		);
		$about_page               = add_submenu_page(
			'cp-post-permalinks',
			'About Custom Permalinks',
			'About',
			'install_plugins',
			'cp-about-plugins',
			array( $this, 'about_plugin' )
		);

		add_action(
			'load-' . $post_permalinks_hook,
			'Custom_Permalinks_Post_Types_Table::instance'
		);
		add_action(
			'load-' . $taxonomy_permalinks_hook,
			'Custom_Permalinks_Taxonomies_Table::instance'
		);
		add_action(
			'admin_print_styles-' . $about_page . '',
			array( $this, 'add_about_style' )
		);
	}

	/**
	 * Add about page style.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_about_style() {
		wp_enqueue_style(
			'custom-permalinks-about-style',
			plugins_url(
				'/assets/css/about-plugins' . $this->css_file_suffix,
				CUSTOM_PERMALINKS_FILE
			),
			array(),
			CUSTOM_PERMALINKS_VERSION
		);
	}

	/**
	 * Calls another Function which shows the Post Types Permalinks Page.
	 *
	 * @since 1.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function post_permalinks_page() {
		Custom_Permalinks_Post_Types_Table::output();

		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Calls another Function which shows the Taxonomies Permalinks Page.
	 *
	 * @since 1.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function taxonomy_permalinks_page() {
		Custom_Permalinks_Taxonomies_Table::output();

		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Add About Plugins Page.
	 *
	 * @since 1.2.11
	 * @access public
	 *
	 * @return void
	 */
	public function about_plugin() {
		include_once CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-about.php';
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
	public function admin_footer_text() {
		$cp_footer_text = __( 'Custom Permalinks version', 'custom-permalinks' ) .
		' ' . CUSTOM_PERMALINKS_VERSION . ' ' .
		__( 'by', 'custom-permalinks' ) .
		' <a href="https://www.yasglobal.com/" target="_blank">' .
			__( 'Sami Ahmed Siddiqui', 'custom-permalinks' ) .
		'</a>' .
		' - ' .
		'<a href="https://wordpress.org/support/plugin/custom-permalinks" target="_blank">' .
			__( 'Support forums', 'custom-permalinks' ) .
		'</a>' .
		' - ' .
		'Follow on Twitter:' .
		' <a href="https://twitter.com/samisiddiqui91" target="_blank">' .
			__( 'Sami Ahmed Siddiqui', 'custom-permalinks' ) .
		'</a>';

		return $cp_footer_text;
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
	 * @return array Plugin Basic Links and added some customer link for Settings,
	 * Contact, and About.
	 */
	public function settings_link( $links ) {
		$about_link   = '<a href="admin.php?page=cp-about-plugins" target="_blank">' .
			__( 'About', 'custom-permalinks' ) .
		'</a>';
		$support_link = '<a href="https://www.custompermalinks.com/#pricing-section" target="_blank">' .
			__( 'Premium Support', 'custom-permalinks' ) .
		'</a>';
		$contact_link = '<a href="https://www.custompermalinks.com/contact-us/" target="_blank">' .
			__( 'Contact', 'custom-permalinks' ) .
		'</a>';

		array_unshift( $links, $contact_link );
		array_unshift( $links, $support_link );
		array_unshift( $links, $about_link );

		return $links;
	}

	/**
	 * Add Privacy Policy about the Plugin.
	 *
	 * @since 1.2.23
	 * @access public
	 *
	 * @return void
	 */
	public function privacy_policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$cp_privacy = esc_html__(
			'This plugin collect information about the site like URL, WordPress version etc. This plugin doesn\'t collect any user related information. To have any kind of further query please feel free to',
			'custom-permalinks'
		);
		$cp_privacy = $cp_privacy .
		' <a href="https://www.custompermalinks.com/contact-us/" target="_blank">' .
			esc_html__( 'contact us', 'custom-permalinks' ) .
		'</a>';

		wp_add_privacy_policy_content(
			'Custom Permalinks',
			wp_kses_post( wpautop( $cp_privacy, false ) )
		);
	}

	/**
	 * Buffer the output to allow redirection, even if the website starts to send
	 * output to the browser.
	 *
	 * @since 2.4.0
	 * @access public
	 *
	 * @return void
	 */
	public function allow_redirection() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['_custom_permalinks_post_nonce'], $_REQUEST['_custom_permalinks_taxonomy_nonce'] ) ) {
			ob_start();
		}
	}
}

new Custom_Permalinks_Admin();
