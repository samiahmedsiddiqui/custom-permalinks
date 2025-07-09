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
		$current_user_id = get_current_user_id();
		$message         = '';
		$nonce_action    = 'custom-permalinks_post_types_settings_' . $current_user_id;
		$nonce_name      = '_custom-permalinks_post_types_settings';
		$saved_data      = filter_input_array( INPUT_POST );
		$tags_page_url   = 'https://github.com/samiahmedsiddiqui/custom-permalinks#custom-tags-for-posttypes';

		if ( isset( $saved_data[ $nonce_name ] )
			&& wp_verify_nonce( $saved_data[ $nonce_name ], $nonce_action )
		) {
			$update_settings = array();
			foreach ( $saved_data['post_type'] as $key => $value ) {
				$update_settings[ $key ] = esc_html( $value );
			}

			$is_updated = update_option( 'custom_permalinks_post_types_settings', $update_settings, false );
			if ( $is_updated ) {
				if ( isset( $saved_data['save_changes_flush_cache'] ) ) {
					// Remove rewrite rules and then recreate rewrite rules.
					flush_rewrite_rules();

					$message = __( 'Post Types Permalinks Settings are updated and cache cleared.', 'custom-permalinks' );
				} else {
					$message = __( 'Post Types Permalinks Settings are updated.', 'custom-permalinks' );
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

			<?php if ( ! empty( $message ) ) : ?>
				<div id="message" class="updated notice notice-success is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
				</div>
			<?php endif; ?>

			<div>
				<p>
					<?php
					echo wp_kses(
						_e(
							'You can set up <strong>permalinks</strong> differently for each <strong>Post Type</strong>, or keep them the same. If you don\'t define a specific permalink structure for a <strong>Post Type</strong>, it will automatically use the structure you\'ve set on your <strong>WordPress Permalink</strong> page.',
							'custom-permalinks'
						),
						array( 'strong' => array() )
					);
					?>
				</p>
				<p>
					<?php
					echo wp_kses(
						_e(
							'To customize your permalinks, make sure to review the available <strong>Structure Tags</strong> and apply the relevant ones to your <strong>Post Types</strong> as needed.',
							'custom-permalinks'
						),
						array( 'strong' => array() )
					);
					?>
				</p>

				<p>
					<?php esc_html_e( 'You can review all available options on this page: ', 'custom-permalinks' ); ?>

					<a href="<?php echo esc_url( $tags_page_url ); ?>" target="_blank"><?php echo esc_url( $tags_page_url ); ?></a>
				</p>
			</div>

			<form enctype="multipart/form-data" method="POST">
				<?php wp_nonce_field( $nonce_action, $nonce_name ); ?>

				<table class="form-table">
					<?php
					$post_types_settings = get_option( 'custom_permalinks_post_types_settings', array() );
					foreach ( $post_types as $post_type_name => $single ) :
						if ( 'page' !== $post_type_name
							&& 'post' !== $post_type_name
							&& ( ! $single->publicly_queryable || ! $single->rewrite )
						) {
							continue;
						}

						$excluded_post_type = apply_filters(
							'custom_permalinks_exclude_post_type',
							$post_type_name
						);
						if ( '__true' === $excluded_post_type ) {
							continue;
						}

						$post_setting = '';
						if ( isset( $post_types_settings[ $post_type_name ] ) ) {
							$post_setting = $post_types_settings[ $post_type_name ];
						}
						?>

						<tr valign="top">
							<th scope="row"><?php echo esc_html( $single->labels->name ); ?></th>
							<td>
								<?php echo esc_url( site_url() ); ?>/<input type="text" name="post_type[<?php echo esc_attr( $post_type_name ); ?>]" value="<?php echo esc_attr( $post_setting ); ?>" class="regular-text" />
							</td>
						</tr>

					<?php endforeach; ?>
				</table>

				<?php submit_button( 'Save Changes', 'primary', 'save_changes' ); ?>
				<?php submit_button( 'Save Changes and Flush cache', 'secondary', 'save_changes_flush_cache' ); ?>
			</form>
		</div>
		<?php
	}
}
