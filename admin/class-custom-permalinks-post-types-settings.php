<?php
/**
 * Define permalinks structure for each post type.
 *
 * @package CustomPermalinks
 */

/**
 * Post Types Permalinks Settings page.
 *
 * @since 3.0.0
 */
class Custom_Permalinks_Post_Types_Settings {
	/**
	 * Initializes WordPress hooks.
	 */
	public function __construct() {
		$this->post_settings();
	}

	/**
	 * Shows the main Settings Page Where user can provide different Permalink
	 * Structure for their Post Types.
	 *
	 * @since 3.0.0
	 */
	private function post_settings() {
		$current_user_id      = get_current_user_id();
		$custom_tag_error     = array();
		$notifications        = array();
		$message_type         = 'updated';
		$nonce_action         = 'custom-permalinks_post_types_settings_' . $current_user_id;
		$nonce_name           = '_custom-permalinks_post_types_settings';
		$saved_data           = filter_input_array( INPUT_POST );
		$settings_error       = array();
		$tags_page_url        = 'https://github.com/samiahmedsiddiqui/custom-permalinks#available-tags';
		$taxonomy_error       = array();
		$update_post_settings = array();

		if ( isset( $saved_data[ $nonce_name ] )
			&& wp_verify_nonce( $saved_data[ $nonce_name ], $nonce_action )
		) {
			$update_post_settings = array();
			foreach ( $saved_data['post_type'] as $key => $value ) {
				$key   = sanitize_text_field( $key );
				$value = sanitize_text_field( $value );

				if ( false !== strpos( $value, 'ctax_TAXONOMY_NAME' )
					|| false !== strpos( $value, 'ctax_parent_TAXONOMY_NAME' )
					|| false !== strpos( $value, 'ctax_parents_TAXONOMY_NAME' )
				) {
					$settings_error[] = $key;
					$taxonomy_error[] = $key;
				} elseif ( false !== strpos( $value, 'custom_permalinks_TAG_NAME' ) ) {
					$custom_tag_error[] = $key;
					$settings_error[]   = $key;
				}

				$update_post_settings[ $key ] = str_replace( '//', '/', $value );
			}

			if ( ! empty( $settings_error ) ) {
				$message_type = 'error';
				if ( 1 === count( $taxonomy_error ) ) {
					$notifications[] = __( 'UPDATE FAILED: Replace "TAXONOMY_NAME" with the valid taxonomy name in the highlighted input field.', 'custom-permalinks' );
				} elseif ( 1 < count( $taxonomy_error ) ) {
					$notifications[] = __( 'UPDATE FAILED: Replace "TAXONOMY_NAME" with the valid taxonomy name in the highlighted input fields.', 'custom-permalinks' );
				}

				if ( 1 === count( $custom_tag_error ) ) {
					$notifications[] = __( 'UPDATE FAILED: Replace "TAG_NAME" with the valid tag name (string) in the highlighted input field.', 'custom-permalinks' );
				} elseif ( 1 < count( $custom_tag_error ) ) {
					$notifications[] = __( 'UPDATE FAILED: Replace "TAG_NAME" with the valid tag name (string) in the highlighted input fields.', 'custom-permalinks' );
				}
			} else {
				$is_updated = update_option( 'custom_permalinks_post_types_settings', $update_post_settings, false );
				if ( isset( $saved_data['save_changes_flush_cache'] ) ) {
					// Remove rewrite rules and then recreate rewrite rules.
					flush_rewrite_rules();

					$notifications[] = __( 'Post Types Permalinks Settings are updated and cache cleared.', 'custom-permalinks' );
				} else {
					$notifications[] = __( 'Post Types Permalinks Settings are updated.', 'custom-permalinks' );
				}
			}
		}

		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Post Types Permalinks Settings', 'custom-permalinks' ); ?>
			</h1>

			<?php
			if ( ! empty( $notifications ) ) :
				foreach ( $notifications as $notify ) :
					?>

					<div id="message" class="<?php echo esc_attr( $message_type ); ?> notice notice-success is-dismissible">
						<p><?php echo esc_html( $notify ); ?></p>
					</div>

					<?php
				endforeach;
			endif;
			?>

			<div class="notice notice-info">
				<p>
					<strong>
						<?php
						esc_html_e(
							'Customize Permalinks for Each Post Type',
							'custom-permalinks'
						);
						?>
					</strong>
				</p>

				<p>
					<?php
					esc_html_e(
						'You have the flexibility to set unique permalink structures for each Post Type — or stick with a unified format. If you don’t define a custom structure, WordPress will automatically fall back to the default structure configured in your',
						'custom-permalinks'
					);
					?>

					<a href="options-permalink.php" target="_blank">
						<?php esc_html_e( ' Permalink Settings', 'custom-permalinks' ); ?>
					</a>.
				</p>

				<p>
				<?php
					echo wp_kses(
						_e(
							'Make use of <strong>Structure Tags</strong> to craft URLs that are meaningful and optimized. Apply them thoughtfully to your Post Types for better clarity, organization, and SEO.',
							'custom-permalinks'
						),
						array( 'strong' => array() )
					);
				?>
				</p>

				<p>
					<a href="<?php echo esc_url( $tags_page_url ); ?>" class="button button-primary" target="_blank">
						<?php esc_html_e( 'View Permalink Tags & Documentation', 'custom-permalinks' ); ?>
					</a>
				</p>
			</div>

			<form method="post" action="" enctype="multipart/form-data" id="custom-permalinks-post-settings">
				<?php wp_nonce_field( $nonce_action, $nonce_name ); ?>

				<table class="form-table">
					<?php
					$post_types_count = 0;
					if ( ! empty( $update_post_settings ) && ! empty( $settings_error ) ) {
						$post_types_settings = $update_post_settings;
					} else {
						$post_types_settings = get_option( 'custom_permalinks_post_types_settings', array() );
					}
					foreach ( $post_types as $post_type_name => $single ) :
						if ( 'page' !== $post_type_name
							&& 'post' !== $post_type_name
							&& ( ! $single->publicly_queryable || ! $single->rewrite )
						) {
							continue;
						}

						$excluded_post_type = apply_filters( 'custom_permalinks_exclude_post_type', $post_type_name );
						if ( '__true' === $excluded_post_type ) {
							continue;
						}

						++$post_types_count;

						$tr_class = '';
						if ( 1 === $post_types_count ) {
							$tr_class = 'active-row';
						}

						$post_setting = '';
						if ( isset( $post_types_settings[ $post_type_name ] ) ) {
							$post_setting = $post_types_settings[ $post_type_name ];
						}

						$input_classes = 'regular-text post-settings-input';
						if ( ! empty( $settings_error ) && in_array( $post_type_name, $settings_error, true ) ) {
							$input_classes .= ' error';
						}
						?>

						<tr valign="top" class="<?php echo esc_attr( $tr_class ); ?>">
							<th scope="row"><?php echo esc_html( $single->labels->name ); ?></th>
							<td>
								<?php echo esc_url( site_url() ); ?>/
								<input type="text" name="post_type[<?php echo esc_attr( $post_type_name ); ?>]" value="<?php echo esc_attr( $post_setting ); ?>" class="<?php echo esc_attr( $input_classes ); ?>" />
							</td>
						</tr>

						<?php if ( 1 === $post_types_count ) : ?>
							<tr valign="top" class="permalink-tags">
								<th class="pd-b-0" scope="row">Available tags:</th>
								<td class="pd-b-0">
									<ul role="list" class="avaliable-tag">
										<li><button type="button" class="button button-secondary" data-name="%year%">year</button></li>
										<li><button type="button" class="button button-secondary" data-name="%monthnum%">monthnum</button></li>
										<li><button type="button" class="button button-secondary" data-name="%day%">day</button></li>
										<li><button type="button" class="button button-secondary" data-name="%hour%">hour</button></li>
										<li><button type="button" class="button button-secondary" data-name="%minute%">minute</button></li>
										<li><button type="button" class="button button-secondary" data-name="%second%">second</button></li>
										<li><button type="button" class="button button-secondary" data-name="%post_id%">post_id</button></li>
										<li><button type="button" class="button button-secondary" data-name="%category%">category</button></li>
										<li><button type="button" class="button button-secondary" data-name="%author%">author</button></li>
										<li><button type="button" class="button button-secondary" data-name="%postname%">postname</button></li>
										<li><button type="button" class="button button-secondary" data-name="%parent_postname%">parent_postname</button></li>
										<li><button type="button" class="button button-secondary" data-name="%parents_postnames%">parents_postnames</button></li>
										<li><button type="button" class="button button-secondary" data-name="%title%">title</button></li>
										<li><button type="button" class="button button-secondary" data-name="%ctax_TAXONOMY_NAME%">ctax_TAXONOMY_NAME</button></li>
										<li><button type="button" class="button button-secondary" data-name="%ctax_parent_TAXONOMY_NAME%">ctax_parent_TAXONOMY_NAME</button></li>
										<li><button type="button" class="button button-secondary" data-name="%ctax_parents_TAXONOMY_NAME%">ctax_parents_TAXONOMY_NAME</button></li>
										<li><button type="button" class="button button-secondary" data-name="%custom_permalinks_TAG_NAME%">custom_permalinks_TAG_NAME</button></li>
									</ul>
								</td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</table>

				<?php submit_button( 'Save Changes', 'primary', 'save_changes' ); ?>
				<?php submit_button( 'Save Changes and Flush cache', 'secondary', 'save_changes_flush_cache' ); ?>
			</form>
		</div>
		<?php
	}
}
