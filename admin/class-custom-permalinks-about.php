<?php
/**
 * Custom Permalinks About.
 *
 * @package CustomPermalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate about page HTML.
 */
class Custom_Permalinks_About {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->more_plugins();
	}

	/**
	 * Print HTML for Custom Permalinks About Page.
	 *
	 * @since 1.2.11
	 * @access private
	 *
	 * @return void
	 */
	private function more_plugins() {
		$img_src = plugins_url( '/assets/images', CUSTOM_PERMALINKS_FILE )
		?>

		<div class="wrap">
			<div class="float">
				<h1>
					<?php
						esc_html_e(
							// translators: After `v` there will be a Plugin version.
							// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
							'Custom Permalinks v' . CUSTOM_PERMALINKS_VERSION,
							'custom-permalinks'
						);
					?>
				</h1>
				<div class="tagline">
					<p>
						<?php
						esc_html_e(
							'Thank you for choosing Custom Permalinks! I hope that your experience with our plugin for updating permalinks is quick and easy. I am trying to make it more feasible for you and provide capabilities in it.',
							'custom-permalinks'
						);
						?>
					</p>
					<p>
						<?php
						esc_html_e(
							'To support future development and to help make it even better please leave a',
							'custom-permalinks'
						);
						?>
						<a href="https://wordpress.org/support/plugin/custom-permalinks/reviews/?rate=5#new-post" title="Custom Permalinks Rating" target="_blank">
						<?php
						esc_html_e( '5-star', 'custom-permalinks' );
						?>
						</a>
						<?php
						esc_html_e(
							'rating with a nice message to me :)',
							'custom-permalinks'
						);
						?>
						</p>
					</div>
				</div>

				<div class="float">
					<img src="<?php echo esc_url( $img_src . '/custom-permalinks.svg' ); ?>" alt="<?php esc_html_e( 'Custom Permalinks', 'custom-permalinks' ); ?>" title="<?php esc_html_e( 'Custom Permalinks', 'custom-permalinks' ); ?>" />
				</div>

				<div class="product">
				<h2>
					<?php esc_html_e( 'More from Sami Ahmed Siddiqui', 'custom-permalinks' ); ?>
				</h2>
				<span>
				<?php
				esc_html_e(
					'Our List of Plugins provides the services which help you to prevent your site from XSS Attacks, Brute force attack, change absolute paths to relative, increase your site visitors by adding Structured JSON Markup and so on.',
					'custom-permalinks'
				);
				?>
				</span>

				<div class="box recommended">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/prevent-xss-vulnerability.png' ); ?>" alt="<?php esc_html_e( 'Prevent XSS Vulnerability', 'custom-permalinks' ); ?>" title="<?php esc_html_e( 'Prevent XSS Vulnerability', 'custom-permalinks' ); ?>" style="transform:scale(1.5)" />
					</div>

					<h3>
						<?php
						esc_html_e( 'Prevent XSS Vulnerability', 'custom-permalinks' );
						?>
					</h3>
					<p>
						<?php
						esc_html_e(
							'Secure your site from the XSS Attacks so, your users won\'t lose any kind of information or not redirected to any other site by visiting your site with the malicious code in the URL or so. In this way, users can open their site URLs without any hesitation.',
							'custom-permalinks'
						);
						?>
					</p>
					<a href="https://wordpress.org/plugins/prevent-xss-vulnerability/" class="checkout-button" title="<?php esc_html_e( 'Prevent XSS Vulnerability', 'custom-permalinks' ); ?>" target="_blank">
						<?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
					</a>
				</div>

				<div class="box recommended">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/http-auth.svg' ); ?>" alt="<?php esc_html_e( 'HTTP Auth', 'custom-permalinks' ); ?>" title="<?php esc_html_e( 'HTTP Auth', 'custom-permalinks' ); ?>" />
					</div>

					<h3>
						<?php esc_html_e( 'HTTP Auth', 'custom-permalinks' ); ?>
					</h3>
					<p>
						<?php
						esc_html_e(
							'Allows you apply HTTP Auth on your site. You can apply Http Authentication all over the site or only the admin pages. It helps to stop crawling on your site while on development or persist the Brute Attacks by locking the Admin Pages.',
							'custom-permalinks'
						);
						?>
					</p>
					<a href="https://wordpress.org/plugins/http-auth/" class="checkout-button" title="<?php esc_html_e( 'HTTP Auth', 'custom-permalinks' ); ?>" target="_blank">
						<?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
					</a>
				</div>

				<div class="box">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/make-paths-relative.svg' ); ?>" alt="<?php esc_html_e( 'Make Paths Relative', 'custom-permalinks' ); ?>" title="<?php esc_html_e( 'Make Paths Relative', 'custom-permalinks' ); ?>" />
					</div>

					<h3>
						<?php esc_html_e( 'Make Paths Relative', 'custom-permalinks' ); ?>
					</h3>
					<p>
						<?php
						esc_html_e(
							'Convert the paths(URLs) to relative instead of absolute. You can make Post, Category, Archive, Image URLs and Script and Style src as per your requirement. You can choose which you want to be relative from the settings Page.',
							'custom-permalinks'
						);
						?>
					</p>
					<a href="https://wordpress.org/plugins/make-paths-relative/" class="checkout-button" title="<?php esc_html_e( 'Make Paths Relative', 'custom-permalinks' ); ?>" target="_blank">
						<?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
					</a>
				</div>

				<div class="box">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/schema-for-article.svg' ); ?>" alt="<?php esc_html_e( 'SCHEMA for Article', 'custom-permalinks' ); ?>" title="<?php esc_html_e( 'SCHEMA for Article', 'custom-permalinks' ); ?>" />
					</div>

					<h3>
						<?php esc_html_e( 'SCHEMA for Article', 'custom-permalinks' ); ?>
					</h3>
					<p>
						<?php
						esc_html_e(
							'Simply the easiest solution to add valid schema.org as a JSON script in the head of blog posts or articles. You can choose the schema either to show with the type of Article or NewsArticle from the settings page.',
							'custom-permalinks'
						);
						?>
					</p>
					<a href="https://wordpress.org/plugins/schema-for-article/" class="checkout-button" title="<?php esc_html_e( 'SCHEMA for Article', 'custom-permalinks' ); ?>" target="_blank">
						<?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
					</a>
				</div>

				<div class="box">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/remove-links-and-scripts.svg' ); ?>" alt="<?php esc_html_e( 'Remove Links and Scripts', 'custom-permalinks' ); ?>" title="<?php esc_html_e( 'Remove Links and Scripts', 'custom-permalinks' ); ?>" />
					</div>

					<h3>
						<?php esc_html_e( 'Remove Links and Scripts', 'custom-permalinks' ); ?>
					</h3>
					<p>
						<?php
						esc_html_e(
							'It removes some meta data from the WordPress header so, your header keeps clean of useless information like shortlink, rsd_link, wlwmanifest_link, emoji_scripts, wp_embed, wp_json, emoji_styles, generator and so on.',
							'custom-permalinks'
						);
						?>
					</p>
					<a href="https://wordpress.org/plugins/remove-links-and-scripts/" class="checkout-button" title="<?php esc_html_e( 'Remove Links and Scripts', 'custom-permalinks' ); ?>" target="_blank">
						<?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
					</a>
				</div>

				<div class="box">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/media-post-permalink.png' ); ?>" style="transform:scale(1.5)" alt="<?php esc_html_e( 'Media Post Permalink', 'custom-permalinks' ); ?>" title="<?php esc_html_e( 'Media Post Permalink', 'custom-permalinks' ); ?>" />
					</div>

					<h3>
						<?php esc_html_e( 'Media Post Permalink', 'custom-permalinks' ); ?>
					</h3>
					<p>
						<?php
						esc_html_e(
							'On uploading  any image, let\'s say services.png, WordPress creates the attachment post with the permalink of /services/ and doesn\'t allow you to use that permalink to point your page. In this case, we come up with this great solution.',
							'custom-permalinks'
						);
						?>
					</p>
					<a href="https://wordpress.org/plugins/media-post-permalink/" class="checkout-button" title="<?php esc_html_e( 'Media Post Permalink', 'custom-permalinks' ); ?>" target="_blank">
						<?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
					</a>
				</div>

				<div class="box">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/json-structuring-markup.svg' ); ?>" alt="<?php esc_html_e( 'JSON Structuring Markup', 'custom-permalinks' ); ?>" title="<?php esc_html_e( 'JSON Structuring Markup', 'custom-permalinks' ); ?>" />
					</div>

					<h3>
						<?php esc_html_e( 'JSON Structuring Markup', 'custom-permalinks' ); ?>
					</h3>
					<p>
						<?php
						esc_html_e(
							'Simply the easiest solution to add valid schema.org as a JSON script in the head of posts and pages. It provides you multiple SCHEMA types like Article, News Article, Organization and Website Schema.',
							'custom-permalinks'
						);
						?>
					</p>
					<a href="https://wordpress.org/plugins/json-structuring-markup/" class="checkout-button" title="<?php esc_html_e( 'JSON Structuring Markup', 'custom-permalinks' ); ?>" target="_blank">
						<?php esc_html_e( 'Check it out', 'custom-permalinks' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}
