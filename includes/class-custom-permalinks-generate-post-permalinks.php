<?php
/**
 * Main class to Generate Post Permalinks.
 *
 * @package CustomPermalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that generates post types permalinks based on the settings.
 */
final class Custom_Permalinks_Generate_Post_Permalinks {

	/**
	 * Generate Post Permalink.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @return bool
	 */
	public function generate( $post_id, $post ) {
		$permalink_structure = '';
		$post_types_settings = get_option( 'custom_permalinks_post_types_settings', array() );
		if ( isset( $post_types_settings[ $post->post_type ] ) ) {
			$permalink_structure = esc_attr(
				$post_types_settings[ $post->post_type ]
			);
		}

		// Return if permalink structure is not defined in the Plugin Settings.
		if ( empty( $permalink_structure ) ) {
			return false;
		}

		$_REQUEST['custom_permalink'] = $this->replace_post_type_tags(
			$post_id,
			$post,
			$permalink_structure
		);

		if ( 'publish' === $post->post_status ) {
			// Delete to prevent generating permalink on updating the post.
			delete_post_meta( $post_id, 'custom_permalink_regenerate_status' );
		} else {
			// Make it 1 to keep generating permalink on updating the post.
			update_post_meta( $post_id, 'custom_permalink_regenerate_status', 1 );
		}

		return true;
	}

	/**
	 * Replace the tags with the respective value on generating the Permalink
	 * for the Post types.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param int    $post_id     Post ID.
	 * @param object $post        The post object.
	 * @param string $replace_tag Structure which is used to create permalink.
	 *
	 * @return string permalink after replacing the appropriate tags with their values.
	 */
	private function replace_post_type_tags( $post_id, $post, $replace_tag ) {
		$date         = new DateTime( $post->post_date );
		$replacements = array(
			'%year%'     => $date->format( 'Y' ),
			'%monthnum%' => $date->format( 'm' ),
			'%day%'      => $date->format( 'd' ),
			'%hour%'     => $date->format( 'H' ),
			'%minute%'   => $date->format( 'i' ),
			'%second%'   => $date->format( 's' ),
			'%post_id%'  => $post_id,
			'%author%'   => get_the_author_meta( 'user_login', $post->post_author ),
			'%title%'    => sanitize_title( $post->post_title ),
		);

		foreach ( $replacements as $tag => $value ) {
			if ( false !== strpos( $replace_tag, $tag ) ) {
				$replace_tag = str_replace( $tag, $value, $replace_tag );
			}
		}

		// Handle %postname%.
		if ( false !== strpos( $replace_tag, '%postname%' ) ) {
			$post_name   = $this->get_current_post_slug( $post );
			$replace_tag = str_replace( '%postname%', $post_name, $replace_tag );
		}

		// Handle %parent_postname%.
		if ( false !== strpos( $replace_tag, '%parent_postname%' ) ) {
			$parent      = $this->get_post_parents_slug( $post_id, $post->post_type, 'immediate' );
			$replace_tag = str_replace( '%parent_postname%', $parent . '/' . $post_name, $replace_tag );
		}

		// Handle %parents_postnames%.
		if ( false !== strpos( $replace_tag, '%parents_postnames%' ) ) {
			$parents     = $this->get_post_parents_slug( $post_id, $post->post_type, 'all' );
			$replace_tag = str_replace( '%parents_postnames%', $parents . $post_name, $replace_tag );
		}

		// Handle %category%.
		if ( false !== strpos( $replace_tag, '%category%' ) ) {
			$category    = $this->get_primary_category_slug( $post_id );
			$replace_tag = str_replace( '%category%', $category, $replace_tag );
		}

		// Handle %ctax_parent_TAXONOMY_NAME%.
		if ( false !== strpos( $replace_tag, '%ctax_parent_' ) ) {
			preg_match_all( '/%ctax_parent_([^%]+)%/', $replace_tag, $matches );
			if ( isset( $matches[1], $matches[1][0] ) ) {
				$category    = $this->get_taxonomy_slug( $post_id, $matches[1][0], 'immediate' );
				$replace_tag = str_replace( "%ctax_parent_{$matches[1][0]}%", $category, $replace_tag );
			}
		}

		// Handle %ctax_parents_TAXONOMY_NAME%.
		if ( false !== strpos( $replace_tag, '%ctax_parents_' ) ) {
			preg_match_all( '/%ctax_parents_([^%]+)%/', $replace_tag, $matches );
			if ( isset( $matches[1], $matches[1][0] ) ) {
				$category    = $this->get_taxonomy_slug( $post_id, $matches[1][0], 'all' );
				$replace_tag = str_replace( "%ctax_parents_{$matches[1][0]}%", $category, $replace_tag );
			}
		}

		// Handle %ctax_TAXONOMY_NAME%.
		if ( false !== strpos( $replace_tag, '%ctax_' ) ) {
			preg_match_all( '/%ctax_([^%]+)%/', $replace_tag, $matches );
			if ( isset( $matches[1], $matches[1][0] ) ) {
				$category    = $this->get_taxonomy_slug( $post_id, $matches[1][0], 'abc' );
				$replace_tag = str_replace( "%ctax_{$matches[1][0]}%", $category, $replace_tag );
			}
		}

		// Handle custom tags.
		if ( false !== strpos( $replace_tag, '%custom_permalinks_' ) ) {
			preg_match_all( '/%custom_permalinks_([^%]+)%/', $replace_tag, $matches );
			if ( isset( $matches[1] ) ) {
				foreach ( $matches[1] as $match ) {
					$custom_tag_value = apply_filters( 'custom_permalinks_post_permalink_tag', $match, $post->post_type, $post );
					$custom_tag_value = wp_strip_all_tags( $custom_tag_value );
					$replace_tag      = str_replace( "%custom_permalinks_{$match}%", $custom_tag_value, $replace_tag );
				}
			}
		}

		return $replace_tag;
	}

	/**
	 * Get current post slug.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param object $post The post object.
	 *
	 * @return string
	 */
	private function get_current_post_slug( $post ) {
		$current_post_name = '';
		if ( ! empty( $post->post_name ) ) {
			$current_post_name = $post->post_name;
		} else {
			$current_post_name = sanitize_title( $post->post_title );
			if ( ! empty( $current_post_name ) ) {
				$this->update_post_name( $post_id, $current_post_name );
			}
		}

		return $current_post_name;
	}

	/**
	 * Get immediate parent or all parents slug of a post.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $post_type   The post type.
	 * @param string $parent_type Immediate parent or all parents.
	 *
	 * @return string
	 */
	private function get_post_parents_slug( $post_id, $post_type, $parent_type ) {
		$parents = get_ancestors( $post_id, $post_type, 'post_type' );
		$slugs   = '';

		if ( is_array( $parents ) && ! empty( $parents ) ) {
			if ( 'immediate' === $parent_type ) {
				$parent = get_post( $parents[0] );
				if ( isset( $parent->post_name ) && ! empty( $parent->post_name ) ) {
					$slugs = $parent->post_name;
				}
			} elseif ( 'all' === $parent_type ) {
				$reverse_parents = array_reverse( $parents );
				foreach ( $reverse_parents as $parent_id ) {
					$parent = get_post( $parent_id );
					if ( isset( $parent->post_name ) && ! empty( $parent->post_name ) ) {
						$slugs .= $parent->post_name . '/';
					}
				}
			}
		}

		return $slugs;
	}

	/**
	 * Usort term comparison.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param WP_Term $a First term object.
	 * @param WP_Term $b Second term object.
	 *
	 * @return bool
	 */
	private function usort_term_comparison( $a, $b ) {
		return $a->term_id - $b->term_id;
	}

	/**
	 * Get primary category slug for %category%.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	private function get_primary_category_slug( $post_id ) {
		$categories = get_the_category( $post_id );
		$slug       = '';
		if ( ! empty( $categories ) && is_array( $categories ) ) {
			usort( $categories, array( $this, 'usort_term_comparison' ) );
			$term = $categories[0];
			$slug = $term->slug;

			if ( $term->parent ) {
				$parent = get_term( $term->parent );
				if ( $parent ) {
					$slug = $parent->slug . '/' . $slug;
				}
			}
		}

		return $slug;
	}

	/**
	 * Get taxonomy slug with optional parent traversal.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $taxonomy    Taxonomy name.
	 * @param string $parent_type Whether to include immediate parent or all or none.
	 *
	 * @return string
	 */
	private function get_taxonomy_slug( $post_id, $taxonomy, $parent_type ) {
		$slug  = '';
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( is_wp_error( $terms ) || empty( $terms ) || ! is_array( $terms ) ) {
			return $slug;
		}

		usort( $terms, array( $this, 'usort_term_comparison' ) );
		$selected = $terms[0];

		// Use primary term if found.
		if ( class_exists( 'WPSEO_Primary_Term' ) ) {
			$wpseo_primary_term = new WPSEO_Primary_Term( $taxonomy, $post_id );
			$primary_term       = $wpseo_primary_term->get_primary_term();

			if ( ! empty( $primary_term ) ) {
				foreach ( $terms as $term ) {
					if ( $term->term_id === $primary_term ) {
						$selected = $term;
						break;
					}
				}
			}
		}

		$slug = $selected->slug;
		if ( $selected->parent ) {
			if ( 'all' === $parent_type ) {
				$parents = get_ancestors( $selected->term_id, $taxonomy, 'taxonomy' );
				if ( ! empty( $parents ) ) {
					$reversed_parents = array_reverse( $parents );
					$parents_slugs    = array_map(
						function ( $pid ) {
							$term      = get_term( $pid );
							$term_slug = '';
							if ( is_object( $term, $term->slug ) ) {
								$term_slug = $term->slug;
							}

							return $term_slug;
						},
						$reversed_parents
					);

					$slug = implode( '/', $parents_slugs ) . '/' . $slug;
				}
			} elseif ( 'immediate' === $parent_type ) {
				$parent = get_term( $selected->parent );
				if ( $parent ) {
					$slug = $parent->slug . '/' . $slug;
				}
			}
		}

		return $slug;
	}

	/**
	 * Set `post_name` for the posts who doesn't have that.
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_name Post name which needs to be set.
	 */
	private function update_post_name( $post_id, $post_name ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_name' => $post_name,
			),
			array(
				'id' => $post_id,
			),
			array(
				'%s',
			),
			array(
				'%d',
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}
