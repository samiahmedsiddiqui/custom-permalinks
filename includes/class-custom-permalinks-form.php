<?php
/**
 * Custom Permalinks Form.
 *
 * @package CustomPermalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that shows permalink form on edit post/category and saves it.
 */
class Custom_Permalinks_Form {
	/**
	 * JS file suffix extension.
	 *
	 * @var string
	 */
	private $js_file_suffix = '.min.js';

	/**
	 * Decide whether to show metabox or override WordPress default Permalink box.
	 *
	 * @var int
	 */
	private $permalink_metabox = 0;

	/**
	 * Initialize WordPress Hooks.
	 *
	 * @since 1.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function init() {
		/*
		 * JS file suffix (version number with extension).
		 */
		$this->js_file_suffix = '-' . CUSTOM_PERMALINKS_VERSION . '.min.js';

		add_action( 'add_meta_boxes', array( $this, 'permalink_edit_box' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
		add_action( 'delete_post', array( $this, 'delete_permalink' ), 10 );
		add_action( 'category_add_form', array( $this, 'term_options' ) );
		add_action( 'category_edit_form', array( $this, 'term_options' ) );
		add_action( 'post_tag_add_form', array( $this, 'term_options' ) );
		add_action( 'post_tag_edit_form', array( $this, 'term_options' ) );
		add_action( 'created_term', array( $this, 'save_term' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'save_term' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'delete_term_permalink' ), 10, 3 );
		add_action( 'rest_api_init', array( $this, 'rest_edit_form' ) );
		add_action(
			'update_option_page_on_front',
			array( $this, 'static_homepage' ),
			10,
			2
		);

		add_filter(
			'get_sample_permalink_html',
			array( $this, 'sample_permalink_html' ),
			10,
			2
		);
		add_filter( 'is_protected_meta', array( $this, 'protect_meta' ), 10, 2 );
	}

	/**
	 * Initialize WordPress Hooks.
	 *
	 * @since 1.6.0
	 * @access private
	 *
	 * @param object $post WP Post Object.
	 *
	 * return bool false Whether to show Custom Permalink form or not.
	 */
	private function exclude_custom_permalinks( $post ) {
		$args               = array(
			'public' => true,
		);
		$exclude_post_types = apply_filters(
			'custom_permalinks_exclude_post_type',
			$post->post_type
		);

		/*
		 * Exclude custom permalink `form` from any post(s) if filter returns `true`.
		 *
		 * @since 1.6.0
		 */
		$exclude_posts     = apply_filters(
			'custom_permalinks_exclude_posts',
			$post
		);
		$public_post_types = get_post_types( $args, 'objects' );

		if ( isset( $this->permalink_metabox ) && 1 === $this->permalink_metabox ) {
			$check_availability = true;
		} elseif ( 'attachment' === $post->post_type ) {
			$check_availability = true;
		} elseif ( intval( get_option( 'page_on_front' ) ) === $post->ID ) {
			$check_availability = true;
		} elseif ( ! isset( $public_post_types[ $post->post_type ] ) ) {
			$check_availability = true;
		} elseif ( '__true' === $exclude_post_types ) {
			$check_availability = true;
		} elseif ( is_bool( $exclude_posts ) && $exclude_posts ) {
			$check_availability = true;
		} else {
			$check_availability = false;
		}

		return $check_availability;
	}

	/**
	 * Register meta box(es).
	 *
	 * @since 1.4.0
	 * @access public
	 *
	 * @return void
	 */
	public function permalink_edit_box() {
		add_meta_box(
			'custom-permalinks-edit-box',
			__( 'Custom Permalinks', 'custom-permalinks' ),
			array( $this, 'meta_edit_form' ),
			null,
			'normal',
			'high',
			array(
				'__back_compat_meta_box' => false,
			)
		);
	}

	/**
	 * Set the meta_keys to protected which is created by the plugin.
	 *
	 * @since 1.4.0
	 * @access public
	 *
	 * @param bool   $protected Whether the key is protected or not.
	 * @param string $meta_key Meta key.
	 *
	 * @return bool `true` for the custom_permalink key.
	 */
	public function protect_meta( $protected, $meta_key ) {
		if ( 'custom_permalink' === $meta_key ) {
			$protected = true;
		}

		return $protected;
	}

	/**
	 * Sanitize given string to make it standard URL. It's a copy of default
	 * `sanitize_title_with_dashes` function with few changes.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @param string      $permalink     String that needs to be sanitized.
	 * @param string|null $language_code Language code.
	 *
	 * @return string Sanitized permalink.
	 */
	private function sanitize_permalink( $permalink, $language_code ) {
		/*
		 * Add Capability to allow Accents letter (if required). By default, It is
		 * disabled.
		 */
		$check_accents_filter = apply_filters( 'custom_permalinks_allow_accents', false );

		/*
		 * Add Capability to allow Capital letter (if required). By default, It is
		 * disabled.
		 */
		$check_caps_filter = apply_filters( 'custom_permalinks_allow_caps', false );

		$allow_accents = false;
		$allow_caps    = false;

		if ( is_bool( $check_accents_filter ) && $check_accents_filter ) {
			$allow_accents = $check_accents_filter;
		}

		if ( is_bool( $check_caps_filter ) && $check_caps_filter ) {
			$allow_caps = $check_caps_filter;
		}

		if ( ! $allow_accents ) {
			$permalink = remove_accents( $permalink );
		}

		if ( empty( $language_code ) ) {
			$language_code = get_locale();
		}

		$permalink = wp_strip_all_tags( $permalink );
		// Preserve escaped octets.
		$permalink = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $permalink );
		// Remove percent signs that are not part of an octet.
		$permalink = str_replace( '%', '', $permalink );
		// Restore octets.
		$permalink = preg_replace( '|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $permalink );

		if ( 'en' === $language_code || strpos( $language_code, 'en_' ) === 0 ) {
			if ( seems_utf8( $permalink ) ) {
				if ( ! $allow_accents ) {
					if ( function_exists( 'mb_strtolower' ) ) {
						if ( ! $allow_caps ) {
							$permalink = mb_strtolower( $permalink, 'UTF-8' );
						}
					}
					$permalink = utf8_uri_encode( $permalink );
				}
			}
		}

		if ( ! $allow_caps ) {
			$permalink = strtolower( $permalink );
		}

		// Convert &nbsp, &ndash, and &mdash to hyphens.
		$permalink = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $permalink );
		// Convert &nbsp, &ndash, and &mdash HTML entities to hyphens.
		$permalink = str_replace( array( '&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;' ), '-', $permalink );

		// Strip these characters entirely.
		$permalink = str_replace(
			array(
				// Soft hyphens.
				'%c2%ad',
				// &iexcl and &iquest.
				'%c2%a1',
				'%c2%bf',
				// Angle quotes.
				'%c2%ab',
				'%c2%bb',
				'%e2%80%b9',
				'%e2%80%ba',
				// Curly quotes.
				'%e2%80%98',
				'%e2%80%99',
				'%e2%80%9c',
				'%e2%80%9d',
				'%e2%80%9a',
				'%e2%80%9b',
				'%e2%80%9e',
				'%e2%80%9f',
				// Bullet.
				'%e2%80%a2',
				// Copy, &reg, &deg, HORIZONTAL ELLIPSIS, and &trade.
				'%c2%a9',
				'%c2%ae',
				'%c2%b0',
				'%e2%80%a6',
				'%e2%84%a2',
				// Acute accents.
				'%c2%b4',
				'%cb%8a',
				'%cc%81',
				'%cd%81',
				// Grave accent, macron, caron.
				'%cc%80',
				'%cc%84',
				'%cc%8c',
			),
			'',
			$permalink
		);

		// Convert &times to 'x'.
		$permalink = str_replace( '%c3%97', 'x', $permalink );
		// Kill entities.
		$permalink = preg_replace( '/&.+?;/', '', $permalink );

		// Avoid removing characters of other languages like persian etc.
		if ( 'en' === $language_code || strpos( $language_code, 'en_' ) === 0 ) {
			// Allow Alphanumeric and few symbols only.
			if ( ! $allow_caps ) {
				$permalink = preg_replace( '/[^%a-z0-9 \.\/_-]/', '', $permalink );
			} else {
				// Allow Capital letters.
				$permalink = preg_replace( '/[^%a-zA-Z0-9 \.\/_-]/', '', $permalink );
			}
		} else {
			$reserved_chars = array(
				'(',
				')',
				'[',
				']',
			);
			$unsafe_chars   = array(
				'<',
				'>',
				'{',
				'}',
				'|',
				'`',
				'^',
				'\\',
			);

			$permalink = str_replace( $reserved_chars, '', $permalink );
			$permalink = str_replace( $unsafe_chars, '', $permalink );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
			$permalink = urlencode( $permalink );
			// Replace encoded slash input with slash.
			$permalink = str_replace( '%2F', '/', $permalink );

			$replace_hyphen = array( '%20', '%2B', '+' );
			$split_path     = explode( '%3F', $permalink );
			if ( 1 < count( $split_path ) ) {
				// Replace encoded space and plus input with hyphen.
				$replaced_path = str_replace( $replace_hyphen, '-', $split_path[0] );
				$replaced_path = preg_replace( '/(\-+)/', '-', $replaced_path );
				$permalink     = str_replace(
					$split_path[0],
					$replaced_path,
					$permalink
				);
			} else {
				// Replace encoded space and plus input with hyphen.
				$permalink = str_replace( $replace_hyphen, '-', $permalink );
				$permalink = preg_replace( '/(\-+)/', '-', $permalink );
			}
		}

		$permalink = preg_replace( '/\s+/', '-', $permalink );
		$permalink = preg_replace( '|-+|', '-', $permalink );
		$permalink = str_replace( '-/', '/', $permalink );
		$permalink = str_replace( '/-', '/', $permalink );

		/*
		 * Avoid trimming hyphens if filter returns `false`.
		 *
		 * @since 2.4.0
		 */
		$trim_hyphen = apply_filters( 'custom_permalinks_redundant_hyphens', false );
		if ( ! is_bool( $trim_hyphen ) || ! $trim_hyphen ) {
			$permalink = trim( $permalink, '-' );
		}

		return $permalink;
	}

	/**
	 * Save per-post options.
	 *
	 * @access public
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( ! isset( $_REQUEST['_custom_permalinks_post_nonce'] )
			&& ! isset( $_REQUEST['custom_permalink'] )
		) {
			return;
		}

		$action = 'custom-permalinks_' . $post_id;
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! wp_verify_nonce( $_REQUEST['_custom_permalinks_post_nonce'], $action ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash

		$cp_frontend   = new Custom_Permalinks_Frontend();
		$original_link = $cp_frontend->original_post_link( $post_id );

		if ( ! empty( $_REQUEST['custom_permalink'] )
			&& $_REQUEST['custom_permalink'] !== $original_link
		) {
			$language_code = apply_filters(
				'wpml_element_language_code',
				null,
				array(
					'element_id'   => $post_id,
					'element_type' => $post->post_type,
				)
			);

			$permalink = $this->sanitize_permalink(
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				wp_unslash( $_REQUEST['custom_permalink'] ),
				$language_code
			);
			$permalink = apply_filters(
				'custom_permalink_before_saving',
				$permalink,
				$post_id
			);

			update_post_meta( $post_id, 'custom_permalink', $permalink );
			if ( null !== $language_code ) {
				update_post_meta( $post_id, 'custom_permalink_language', $language_code );
			} else {
				delete_metadata( 'post', $post_id, 'custom_permalink_language' );
			}
		}
	}

	/**
	 * Delete Post Permalink.
	 *
	 * @access public
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function delete_permalink( $post_id ) {
		delete_metadata( 'post', $post_id, 'custom_permalink' );
	}

	/**
	 * Result Permalink HTML Form for classic editor.
	 *
	 * @since 1.6.0
	 * @access private
	 *
	 * @param object $post WP Post Object.
	 *
	 * @return string Permalink Form HTML.
	 */
	private function get_permalink_html( $post ) {
		$cp_frontend = new Custom_Permalinks_Frontend();
		$post_id     = $post->ID;
		$permalink   = get_post_meta( $post_id, 'custom_permalink', true );

		if ( 'page' === $post->post_type ) {
			$original_permalink = $cp_frontend->original_page_link( $post_id );
			$view_post          = __( 'View Page', 'custom-permalinks' );
		} else {
			$post_type_name   = '';
			$post_type_object = get_post_type_object( $post->post_type );
			if ( is_object( $post_type_object ) && isset( $post_type_object->labels )
				&& isset( $post_type_object->labels->singular_name )
			) {
				$post_type_name = ' ' . $post_type_object->labels->singular_name;
			} elseif ( is_object( $post_type_object )
				&& isset( $post_type_object->label )
			) {
				$post_type_name = ' ' . $post_type_object->label;
			}

			$original_permalink = $cp_frontend->original_post_link( $post_id );
			$view_post          = __( 'View', 'custom-permalinks' ) . $post_type_name;
		}

		ob_start();

		$this->get_permalink_form(
			$permalink,
			$original_permalink,
			$post_id,
			false,
			$post->post_name
		);

		$content = ob_get_contents();
		ob_end_clean();

		if ( 'trash' !== $post->post_status ) {
			$home_url = trailingslashit( home_url() );
			if ( isset( $permalink ) && ! empty( $permalink ) ) {
				$view_post_link = $home_url . $permalink;
			} else {
				if ( 'draft' === $post->post_status
					|| 'pending' === $post->post_status
				) {
					$view_post      = 'Preview';
					$view_post_link = $home_url . '?';
					if ( 'page' === $post->post_type ) {
						$view_post_link .= 'page_id';
					} elseif ( 'post' === $post->post_type ) {
						$view_post_link .= 'p';
					} else {
						$view_post_link .= 'post_type=' . $post->post_type . '&p';
					}
					$view_post_link .= '=' . $post_id . '&preview=true';
				} else {
					$view_post_link = $home_url . $original_permalink;
				}
			}

			$content .= ' <span id="view-post-btn">' .
				'<a href="' . $view_post_link . '" class="button button-medium" target="_blank">' . $view_post . '</a>' .
			'</span><br>';
		}

		return '<strong>' . __( 'Permalink:', 'custom-permalinks' ) . '</strong> ' . $content;
	}

	/**
	 * Result Permalink HTML Form for Gutenberg.
	 *
	 * @since 2.6.0
	 * @access private
	 *
	 * @param object $post WP Post Object.
	 *
	 * @return void.
	 */
	private function get_permalink_meta_html( $post ) {
		$cp_frontend = new Custom_Permalinks_Frontend();
		$post_id     = $post->ID;
		$permalink   = get_post_meta( $post_id, 'custom_permalink', true );

		if ( 'page' === $post->post_type ) {
			$original_permalink = $cp_frontend->original_page_link( $post_id );
			$view_post          = __( 'View Page', 'custom-permalinks' );
		} else {
			$post_type_name   = '';
			$post_type_object = get_post_type_object( $post->post_type );
			if ( is_object( $post_type_object ) && isset( $post_type_object->labels )
				&& isset( $post_type_object->labels->singular_name )
			) {
				$post_type_name = ' ' . $post_type_object->labels->singular_name;
			} elseif ( is_object( $post_type_object )
				&& isset( $post_type_object->label )
			) {
				$post_type_name = ' ' . $post_type_object->label;
			}

			$original_permalink = $cp_frontend->original_post_link( $post_id );
			$view_post          = __( 'View', 'custom-permalinks' ) . $post_type_name;
		}

		echo '<strong>' . esc_html__( 'Permalink:', 'custom-permalinks' ) . '</strong> ';

		$this->get_permalink_form(
			$permalink,
			$original_permalink,
			$post_id,
			false,
			$post->post_name
		);

		if ( 'trash' !== $post->post_status ) {
			$home_url = trailingslashit( home_url() );
			if ( isset( $permalink ) && ! empty( $permalink ) ) {
				$view_post_link = $home_url . $permalink;
			} else {
				if ( 'draft' === $post->post_status
					|| 'pending' === $post->post_status
				) {
					$view_post      = 'Preview';
					$view_post_link = $home_url . '?';
					if ( 'page' === $post->post_type ) {
						$view_post_link .= 'page_id';
					} elseif ( 'post' === $post->post_type ) {
						$view_post_link .= 'p';
					} else {
						$view_post_link .= 'post_type=' . $post->post_type . '&p';
					}
					$view_post_link .= '=' . $post_id . '&preview=true';
				} else {
					$view_post_link = $home_url . $original_permalink;
				}
			}
			?>

			<span id="view-post-btn">
				<a href="<?php echo esc_url( $view_post_link ); ?>" class="button button-medium" target="_blank">
					<?php echo esc_html( $view_post ); ?>
				</a>
			</span>
			<br>
			<style>.editor-post-permalink,.cp-permalink-hidden{display:none;}</style>

			<?php
		}
	}

	/**
	 * Per-post/page options (WordPress > 2.9).
	 *
	 * @access public
	 *
	 * @param string $html WP Post Permalink HTML.
	 * @param int    $post_id Post ID.
	 *
	 * @return string Edit Form string.
	 */
	public function sample_permalink_html( $html, $post_id ) {
		$post = get_post( $post_id );

		$disable_cp              = $this->exclude_custom_permalinks( $post );
		$this->permalink_metabox = 1;
		if ( $disable_cp ) {
			return $html;
		}

		return $this->get_permalink_html( $post );
	}

	/**
	 * Adds the Permalink Edit Meta box for the user with validating the
	 * PostTypes to make compatibility with Gutenberg.
	 *
	 * @access public
	 *
	 * @param object $post WP Post Object.
	 *
	 * @return void
	 */
	public function meta_edit_form( $post ) {
		$disable_cp = $this->exclude_custom_permalinks( $post );
		if ( $disable_cp ) {
			wp_enqueue_script(
				'custom-permalinks-form',
				plugins_url(
					'/assets/js/script-form' . $this->js_file_suffix,
					CUSTOM_PERMALINKS_FILE
				),
				array(),
				CUSTOM_PERMALINKS_VERSION,
				true
			);

			return;
		}

		$screen = get_current_screen();
		if ( 'add' === $screen->action ) {
			echo '<input value="add" type="hidden" name="custom-permalinks-add" id="custom-permalinks-add" />';
		}

		$this->get_permalink_meta_html( $post, true );
	}

	/**
	 * Per-category/tag options.
	 *
	 * @access public
	 *
	 * @param object|string $tag Current taxonomy term object for Edit form
	 *                           otherwise the taxonomy slug.
	 *
	 * @return void
	 */
	public function term_options( $tag ) {
		$permalink          = '';
		$original_permalink = '';

		if ( is_object( $tag ) && isset( $tag->term_id ) ) {
			$cp_frontend = new Custom_Permalinks_Frontend();
			if ( $tag->term_id ) {
				$permalink          = $cp_frontend->term_permalink( $tag->term_id );
				$original_permalink = $cp_frontend->original_term_link(
					$tag->term_id
				);
			}

			$this->get_permalink_form( $permalink, $original_permalink, $tag->term_id );
		} else {
			$this->get_permalink_form( $permalink, $original_permalink, $tag );
		}

		// Move the save button to above this form.
		wp_enqueue_script( 'jquery' );
		?>
		<script type="text/javascript">
		jQuery(document).ready(function() {
			var button = jQuery('#custom_permalink_form').parent().find('.submit');
			button.remove().insertAfter(jQuery('#custom_permalink_form'));
		});
		</script>
		<?php
	}

	/**
	 * Helper function to render form.
	 *
	 * @access private
	 *
	 * @param string     $permalink Permalink which is created by the plugin.
	 * @param string     $original  Permalink which set by WordPress.
	 * @param int|string $id        Post ID for Posts, Pages and custom post
	 *                              types, Term ID for Taxonomy Edit form and
	 *                              taxonomy slug in case of term add.
	 * @param bool       $render_containers Shows Post/Term Edit.
	 * @param string     $postname          Post Name.
	 *
	 * @return void
	 */
	private function get_permalink_form( $permalink, $original, $id,
		$render_containers = true, $postname = ''
	) {
		$encoded_permalink = htmlspecialchars( urldecode( $permalink ) );
		$home_url          = trailingslashit( home_url() );

		if ( $render_containers ) {
			wp_nonce_field(
				'custom-permalinks_' . $id,
				'_custom_permalinks_term_nonce',
				false,
				true
			);
		} else {
			wp_nonce_field(
				'custom-permalinks_' . $id,
				'_custom_permalinks_post_nonce',
				false,
				true
			);
		}
		?>

		<input value="<?php echo esc_url( $home_url ); ?>" type="hidden" name="custom_permalinks_home_url" id="custom_permalinks_home_url" />
		<input value="<?php echo esc_attr( $encoded_permalink ); ?>" type="hidden" name="custom_permalink" id="custom_permalink" />

		<?php
		if ( $render_containers ) :
			?>
			<table class="form-table" id="custom_permalink_form">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Custom Permalink', 'custom-permalinks' ); ?>
					</th>
					<td>
			<?php
		endif;

		if ( '' === $permalink ) {
			$original = $this->check_conflicts( $original );
		}

		if ( $permalink ) {
			$post_slug            = htmlspecialchars( urldecode( $permalink ) );
			$original_encoded_url = htmlspecialchars( urldecode( $original ) );
		} else {
			$post_slug            = htmlspecialchars( urldecode( $original ) );
			$original_encoded_url = $post_slug;
		}

		wp_enqueue_script(
			'custom-permalinks-form',
			plugins_url(
				'/assets/js/script-form' . $this->js_file_suffix,
				CUSTOM_PERMALINKS_FILE
			),
			array(),
			CUSTOM_PERMALINKS_VERSION,
			true
		);

		echo esc_url( $home_url ) .
		'<span id="editable-post-name" title="Click to edit this part of the permalink">';

		if ( isset( $postname ) && '' !== $postname ) :
			?>

			<input type="hidden" id="new-post-slug" class="text" value="<?php echo esc_attr( $postname ); ?>" />

			<?php
		endif;

		$field_style = 'width: 250px;';
		if ( ! $permalink ) {
			$field_style .= ' color: #ddd;';
		}
		?>

		<input type="hidden" id="original-permalink" value="<?php echo esc_attr( $original_encoded_url ); ?>" />
		<input type="text" id="custom-permalinks-post-slug" class="text" value="<?php echo esc_attr( $post_slug ); ?>" style="<?php echo esc_attr( $field_style ); ?>" />
		</span>

		<?php
		if ( $render_containers ) :
			?>
			<br />
			<small>
				<?php esc_html_e( 'Leave blank to disable', 'custom-permalinks' ); ?>
			</small>
			</td>
			</tr>
			</table>
			<?php
		endif;
	}

	/**
	 * Save term (common to tags and categories).
	 *
	 * @since 1.6.0
	 * @access public
	 *
	 * @param string $term_id Term ID.
	 *
	 * @return void
	 */
	public function save_term( $term_id ) {
		$term = get_term( $term_id );

		if ( ! isset( $_REQUEST['_custom_permalinks_term_nonce'] )
			&& ! isset( $_REQUEST['custom_permalink'] )
		) {
			return;
		}

		$action1 = 'custom-permalinks_' . $term_id;
		$action2 = 'custom-permalinks_' . $term->taxonomy;
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( ! wp_verify_nonce( $_REQUEST['_custom_permalinks_term_nonce'], $action1 )
			&& ! wp_verify_nonce( $_REQUEST['_custom_permalinks_term_nonce'], $action2 )
		) {
			return;
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash

		if ( isset( $term ) && isset( $term->taxonomy ) ) {
			$taxonomy_name = $term->taxonomy;
			if ( 'category' === $taxonomy_name || 'post_tag' === $taxonomy_name ) {
				if ( 'post_tag' === $taxonomy_name ) {
					$taxonomy_name = 'tag';
				}

				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$new_permalink = ltrim(
					stripcslashes( $_REQUEST['custom_permalink'] ),
					'/'
				);
				// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash

				if ( empty( $new_permalink ) || '' === $new_permalink ) {
					return;
				}

				$cp_frontend   = new Custom_Permalinks_Frontend();
				$old_permalink = $cp_frontend->original_term_link( $term_id );
				if ( $new_permalink === $old_permalink ) {
					return;
				}

				$this->delete_term_permalink( $term_id );

				$language_code = '';
				if ( isset( $term->term_taxonomy_id ) ) {
					$term_type = 'category';
					if ( isset( $term->taxonomy ) ) {
						$term_type = $term->taxonomy;
					}

					$language_code = apply_filters(
						'wpml_element_language_code',
						null,
						array(
							'element_id'   => $term->term_taxonomy_id,
							'element_type' => $term_type,
						)
					);
				}

				$permalink = $this->sanitize_permalink( $new_permalink, $language_code );
				$table     = get_option( 'custom_permalink_table' );

				if ( ! is_array( $table ) ) {
					$table = array();
				}

				if ( $permalink && ! array_key_exists( $permalink, $table ) ) {
					$table[ $permalink ] = array(
						'id'   => $term_id,
						'kind' => $taxonomy_name,
						'slug' => $term->slug,
					);
				}

				update_option( 'custom_permalink_table', $table );
			}
		}
	}

	/**
	 * Delete term.
	 *
	 * @access public
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return void
	 */
	public function delete_term_permalink( $term_id ) {
		$table = get_option( 'custom_permalink_table' );
		if ( $table ) {
			foreach ( $table as $link => $info ) {
				if ( $info['id'] === (int) $term_id ) {
					unset( $table[ $link ] );
					break;
				}
			}
		}

		update_option( 'custom_permalink_table', $table );
	}

	/**
	 * Check Conflicts and resolve it (e.g: Polylang) UPDATED for Polylang
	 * hide_default setting.
	 *
	 * @since 1.2.0
	 * @access public
	 *
	 * @param string $requested_url Original permalink.
	 *
	 * @return string requested URL by removing the language/ from it if exist.
	 */
	public function check_conflicts( $requested_url = '' ) {
		if ( '' === $requested_url ) {
			return;
		}

		// Check if the Polylang Plugin is installed so, make changes in the URL.
		if ( defined( 'POLYLANG_VERSION' ) ) {
			$polylang_config = get_option( 'polylang' );
			if ( 1 === $polylang_config['force_lang'] ) {
				if ( false !== strpos( $requested_url, 'language/' ) ) {
					$requested_url = str_replace( 'language/', '', $requested_url );
				}

				/*
				 * Check if `hide_default` is `true` and the current language is not
				 * the default. Otherwise remove the lang code from the URL.
				 */
				if ( 1 === $polylang_config['hide_default'] ) {
					$current_language = '';
					if ( function_exists( 'pll_current_language' ) ) {
						// Get current language.
						$current_language = pll_current_language();
					}

					// Get default language.
					$default_language = $polylang_config['default_lang'];
					if ( $current_language !== $default_language ) {
						$remove_lang = ltrim( strstr( $requested_url, '/' ), '/' );
						if ( '' !== $remove_lang ) {
							return $remove_lang;
						}
					}
				} else {
					$remove_lang = ltrim( strstr( $requested_url, '/' ), '/' );
					if ( '' !== $remove_lang ) {
						return $remove_lang;
					}
				}
			}
		}

		return $requested_url;
	}

	/**
	 * Refresh Permalink using AJAX Call.
	 *
	 * @since 1.6.0
	 * @access public
	 *
	 * @param object $data Contains post id with some default REST Values.
	 *
	 * @return void
	 */
	public function refresh_meta_form( $data ) {
		if ( isset( $data['id'] ) && is_numeric( $data['id'] ) ) {
			$post                               = get_post( $data['id'] );
			$all_permalinks                     = array();
			$all_permalinks['custom_permalink'] = get_post_meta(
				$data['id'],
				'custom_permalink',
				true
			);

			if ( ! $all_permalinks['custom_permalink'] ) {
				if ( 'draft' === $post->post_status
					|| 'pending' === $post->post_status
				) {
					$view_post_link = '?';
					if ( 'page' === $post->post_type ) {
						$view_post_link .= 'page_id';
					} elseif ( 'post' === $post->post_type ) {
						$view_post_link .= 'p';
					} else {
						$view_post_link .= 'post_type=' . $post->post_type . '&p';
					}
					$view_post_link .= '=' . $data['id'] . '&preview=true';

					$all_permalinks['preview_permalink'] = $view_post_link;
				}
			} else {
				$all_permalinks['custom_permalink'] = htmlspecialchars(
					urldecode(
						$all_permalinks['custom_permalink']
					)
				);
			}

			$cp_frontend = new Custom_Permalinks_Frontend();
			if ( 'page' === $post->post_type ) {
				$all_permalinks['original_permalink'] = $cp_frontend->original_page_link(
					$data['id']
				);
			} else {
				$all_permalinks['original_permalink'] = $cp_frontend->original_post_link(
					$data['id']
				);
			}

			echo wp_json_encode( $all_permalinks );
			exit;
		}
	}

	/**
	 * Added Custom Endpoints for refreshing the permalink.
	 *
	 * @since 1.6.0
	 * @access public
	 *
	 * @return void
	 */
	public function rest_edit_form() {
		register_rest_route(
			'custom-permalinks/v1',
			'/get-permalink/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'refresh_meta_form' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => function( $pid ) {
							return is_numeric( $pid );
						},
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Delete the permalink for the page selected as the static Homepage.
	 *
	 * @since 1.6.0
	 * @access public
	 *
	 * @param int $prev_homepage_id Page ID of previously set Front Page.
	 * @param int $new_homepage_id  Page ID of current Front Page.
	 *
	 * @return void
	 */
	public function static_homepage( $prev_homepage_id, $new_homepage_id ) {
		if ( $prev_homepage_id !== $new_homepage_id ) {
			$this->delete_permalink( $new_homepage_id );
		}
	}
}
