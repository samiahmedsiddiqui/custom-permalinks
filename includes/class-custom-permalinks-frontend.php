<?php
/**
 * Custom Permalinks Frontend.
 *
 * @package CustomPermalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that passes custom link, parse the requested URL and redirect.
 */
class Custom_Permalinks_Frontend {
	/**
	 * Make it `true` when `parse_request()` succeeded to make performance better.
	 *
	 * @var bool
	 */
	private $parse_request_status = false;

	/**
	 * The query string, if any, via which the page is accessed otherwise empty.
	 *
	 * @var string
	 */
	private $query_string_uri = '';

	/**
	 * Preserve the URL for later use in parse_request.
	 *
	 * @var string
	 */
	private $registered_url = '';

	/**
	 * The URI which is given in order to access this page. Default empty.
	 *
	 * @var string
	 */
	private $request_uri = '';

	/**
	 * Initialize WordPress Hooks.
	 *
	 * @since 1.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function init() {
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			$this->query_string_uri = sanitize_url(
				wp_unslash( $_SERVER['QUERY_STRING'] )
			);
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$this->request_uri = sanitize_url(
				wp_unslash( $_SERVER['REQUEST_URI'] )
			);
		}

		add_action( 'template_redirect', array( $this, 'make_redirect' ), 5 );

		add_filter( 'request', array( $this, 'parse_request' ) );
		add_filter( 'oembed_request_post_id', array( $this, 'oembed_request' ), 10, 2 );
		add_filter( 'post_link', array( $this, 'custom_post_link' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'custom_post_link' ), 10, 2 );
		add_filter( 'page_link', array( $this, 'custom_page_link' ), 10, 2 );
		add_filter( 'term_link', array( $this, 'custom_term_link' ), 10, 2 );
		add_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );

		// WPSEO Filters.
		add_filter(
			'wpseo_canonical',
			array( $this, 'fix_canonical_double_slash' ),
			20,
			1
		);
	}

	/**
	 * Replace double slash `//` with single slash `/`.
	 *
	 * @since 1.6.0
	 * @access public
	 *
	 * @param string $permalink URL in which `//` needs to be replaced with `/`.
	 *
	 * @return string URL with single slash.
	 */
	public function remove_double_slash( $permalink = '' ) {
		$protocol = '';
		if ( 0 === strpos( $permalink, 'http://' )
			|| 0 === strpos( $permalink, 'https://' )
		) {
			$split_protocol = explode( '://', $permalink );
			if ( 1 < count( $split_protocol ) ) {
				$protocol  = $split_protocol[0] . '://';
				$permalink = str_replace( $protocol, '', $permalink );
			}
		}

		$permalink = str_replace( '//', '/', $permalink );
		$permalink = $protocol . $permalink;

		return $permalink;
	}

	/**
	 * Use `wpml_permalink` to add language information to permalinks and
	 * resolve language switcher issue if found.
	 *
	 * @since 1.6.0
	 * @access public
	 *
	 * @param string $permalink     Custom Permalink.
	 * @param string $language_code The language to convert the URL into.
	 *
	 * @return string permalink with language information.
	 */
	public function wpml_permalink_filter( $permalink, $language_code ) {
		$custom_permalink   = $permalink;
		$trailing_permalink = trailingslashit( home_url() ) . $custom_permalink;
		if ( $language_code ) {
			$permalink = apply_filters(
				'wpml_permalink',
				$trailing_permalink,
				$language_code
			);
			$site_url  = site_url();
			$wpml_href = str_replace( $site_url, '', $permalink );
			if ( 0 === strpos( $wpml_href, '//' ) ) {
				if ( 0 !== strpos( $wpml_href, '//' . $language_code . '/' ) ) {
					$permalink = $site_url . '/' . $language_code . '/' . $custom_permalink;
				}
			}
		} else {
			$permalink = apply_filters( 'wpml_permalink', $trailing_permalink );
		}

		return $permalink;
	}

	/**
	 * Search a permalink in the posts table and return its result.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @param string $requested_url Requested URL.
	 *
	 * @return object|null Containing Post ID, Permalink, Post Type, and Post status
	 *                     if URL matched otherwise returns null.
	 */
	private function query_post( $requested_url ) {
		global $wpdb;

		$cache_name = 'cp$_' . str_replace( '/', '-', $requested_url ) . '_#cp';
		$posts      = wp_cache_get( $cache_name, 'custom_permalinks' );

		if ( ! $posts ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$posts = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT p.ID, pm.meta_value, p.post_type, p.post_status ' .
					" FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON (pm.post_id = p.ID) " .
					" WHERE pm.meta_key = 'custom_permalink' " .
					' AND (pm.meta_value = %s OR pm.meta_value = %s) ' .
					" AND p.post_status != 'trash' AND p.post_type != 'nav_menu_item' " .
					" ORDER BY FIELD(post_status,'publish','private','pending','draft','auto-draft','inherit')," .
					" FIELD(post_type,'post','page') LIMIT 1",
					$requested_url,
					$requested_url . '/'
				)
			);

			$remove_like_query = apply_filters( 'cp_remove_like_query', '__true' );
			if ( ! $posts && '__true' === $remove_like_query ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$posts = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT p.ID, pm.meta_value, p.post_type, p.post_status FROM $wpdb->posts AS p " .
						" LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE " .
						" meta_key = 'custom_permalink' AND meta_value != '' AND " .
						' ( LOWER(meta_value) = LEFT(LOWER(%s), LENGTH(meta_value)) OR ' .
						'   LOWER(meta_value) = LEFT(LOWER(%s), LENGTH(meta_value)) ) ' .
						"  AND post_status != 'trash' AND post_type != 'nav_menu_item'" .
						' ORDER BY LENGTH(meta_value) DESC, ' .
						" FIELD(post_status,'publish','private','pending','draft','auto-draft','inherit')," .
						" FIELD(post_type,'post','page'), p.ID ASC LIMIT 1",
						$requested_url,
						$requested_url . '/'
					)
				);
			}

			wp_cache_set( $cache_name, $posts, 'custom_permalinks' );
		}

		return $posts;
	}

	/**
	 * Search a permalink in the posts table with respect to WPML language for
	 * different domain per language.
	 *
	 * @since 2.5.0
	 * @access private
	 *
	 * @param string $requested_url Requested URL.
	 * @param string $language_code Language code.
	 *
	 * @return object|null Containing Post ID, Permalink, Post Type, and Post status
	 *                     if URL matched otherwise returns null.
	 */
	private function query_post_language( $requested_url, $language_code = null ) {
		global $wpdb;

		$cache_name   = 'cp$' . $language_code . '_' . str_replace( '/', '-', $requested_url ) . '_#cp';
		$matched_post = wp_cache_get( $cache_name, 'custom_permalinks' );

		if ( null === $language_code ) {
			return null;
		}

		if ( ! $matched_post ) {
			$matched_post = array();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$posts = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT p.ID, pm.meta_value, p.post_type, p.post_status ' .
					" FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON (pm.post_id = p.ID) " .
					" WHERE pm.meta_key = 'custom_permalink' " .
					' AND (pm.meta_value = %s OR pm.meta_value = %s) ' .
					" AND p.post_status != 'trash' AND p.post_type != 'nav_menu_item' " .
					" ORDER BY FIELD(post_status,'publish','private','pending','draft','auto-draft','inherit')," .
					" FIELD(post_type,'post','page')",
					$requested_url,
					$requested_url . '/'
				)
			);

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $check_data ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$post_lang = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT * FROM $wpdb->postmeta AS pm " .
							" WHERE pm.meta_key = 'custom_permalink_language' " .
							' AND pm.post_id = %d AND pm.meta_value = %s',
							$check_data->ID,
							$language_code
						)
					);

					if ( $post_lang ) {
						$matched_post[] = $check_data;
						break;
					}
				}
			}
		}

		wp_cache_set( $cache_name, $matched_post, 'custom_permalinks' );

		return $matched_post;
	}

	/**
	 * Check conditions if it matches then return true to stop processing the
	 * particular query like for sitemaps.
	 *
	 * @since 2.1.0
	 * @access private
	 *
	 * @param array $query Requested Query.
	 *
	 * @return bool Whether to process the query or not.
	 */
	private function exclude_query_proccess( $query ) {
		$exclude = false;

		/*
		 * Return Query for Sitemap pages.
		 */
		if ( isset( $query )
			&& (
				( isset( $query['sitemap'] ) && ! empty( $query['sitemap'] ) )
				|| (
					isset( $query['seopress_sitemap'] )
					&& ! empty( $query['seopress_sitemap'] )
				)
				|| (
					isset( $query['seopress_cpt'] )
					&& ! empty( $query['seopress_cpt'] )
				)
				|| (
					isset( $query['seopress_sitemap_xsl'] )
					&& 1 === (int) $query['seopress_sitemap_xsl']
				)
			)
		) {
			$exclude = true;
		}

		return $exclude;
	}

	/**
	 * Filter to rewrite the query if we have a matching post.
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @param array $query The array of requested query variables.
	 *
	 * @return array the URL which has to be parsed.
	 */
	public function parse_request( $query ) {
		global $wpdb;

		if ( isset( $_SERVER['REQUEST_URI'] )
			&& $_SERVER['REQUEST_URI'] !== $this->request_uri
		) {
			$this->request_uri = sanitize_url(
				wp_unslash( $_SERVER['REQUEST_URI'] )
			);
		}

		/*
		 * Return Query for Sitemap pages.
		 */
		$stop_query = $this->exclude_query_proccess( $query );
		if ( $stop_query ) {
			// Making it true to avoid redirect if query doesn't needs to be processed.
			$this->parse_request_status = true;
			return $query;
		}

		/*
		 * First, search for a matching custom permalink, and if found generate the
		 * corresponding original URL.
		 */
		$original_url = null;

		// Get request URI, strip parameters and /'s.
		$url     = wp_parse_url( get_bloginfo( 'url' ) );
		$url     = isset( $url['path'] ) ? $url['path'] : '';
		$request = ltrim( substr( $this->request_uri, strlen( $url ) ), '/' );
		$pos     = strpos( $request, '?' );
		if ( $pos ) {
			$request = substr( $request, 0, $pos );
		}

		if ( ! $request ) {
			return $query;
		}

		$ignore = apply_filters( 'custom_permalinks_request_ignore', $request );

		if ( '__true' === $ignore ) {
			return $query;
		}

		if ( defined( 'POLYLANG_VERSION' ) ) {
			$cp_form = new Custom_Permalinks_Form();
			$request = $cp_form->check_conflicts( $request );
		}

		$current_language  = '';
		$different_domain  = false;
		$found_permalink   = '';
		$permalink_matched = false;
		$request_no_slash  = preg_replace( '@/+@', '/', trim( $request, '/' ) );

		if ( class_exists( 'SitePress' ) ) {
			$wpml_lang_format = apply_filters(
				'wpml_setting',
				0,
				'language_negotiation_type'
			);

			// Different domain per language.
			if ( 2 === intval( $wpml_lang_format ) ) {
				$current_language = apply_filters( 'wpml_current_language', null );
				$different_domain = true;
			}
		}

		// Different domain per language.
		if ( class_exists( 'SitePress' )
			&& $different_domain
			&& ! empty( $current_language )
		) {
			$posts = $this->query_post_language( $request_no_slash, $current_language );

			// Backward compatibility.
			if ( ! $posts ) {
				$posts = $this->query_post( $request_no_slash );
			}
		} else {
			$posts = $this->query_post( $request_no_slash );
		}

		if ( $posts ) {
			/*
			 * A post matches our request. Preserve this URL for later use. If it's
			 * the same as the permalink (no extra stuff).
			 */
			if ( trim( $posts[0]->meta_value, '/' ) === $request_no_slash ) {
				$this->registered_url = $request;
				$permalink_matched    = true;
			}

			$found_permalink = $posts[0]->meta_value;
			if ( 'draft' === $posts[0]->post_status
				|| 'pending' === $posts[0]->post_status
			) {
				if ( 'page' === $posts[0]->post_type ) {
					$original_url = '?page_id=' . $posts[0]->ID;
				} else {
					$original_url = '?post_type=' . $posts[0]->post_type . '&p=' . $posts[0]->ID;
				}
			} else {
				$post_meta = trim( strtolower( $posts[0]->meta_value ), '/' );
				if ( 'page' === $posts[0]->post_type ) {
					$get_original_url = $this->original_page_link( $posts[0]->ID );
					$original_url     = preg_replace(
						'@/+@',
						'/',
						str_replace(
							$post_meta,
							$get_original_url,
							strtolower( $request_no_slash )
						)
					);
				} else {
					$get_original_url = $this->original_post_link( $posts[0]->ID );
					$original_url     = preg_replace(
						'@/+@',
						'/',
						str_replace(
							$post_meta,
							$get_original_url,
							strtolower( $request_no_slash )
						)
					);
				}
			}
		}

		if ( null === $original_url
			|| ( null !== $original_url && ! $permalink_matched )
		) {
			// See if any terms have a matching permalink.
			$table = get_option( 'custom_permalink_table' );
			if ( $table ) {
				$term_permalink = false;
				foreach ( array_keys( $table ) as $permalink ) {
					$perm_length = strlen( $permalink );
					if ( ! $term_permalink
						&& null !== $original_url
						&& trim( $permalink, '/' ) !== $request_no_slash
					) {
						continue;
					}

					if ( substr( $request_no_slash, 0, $perm_length ) === $permalink
						|| substr( $request_no_slash . '/', 0, $perm_length ) === $permalink
					) {
						$term           = $table[ $permalink ];
						$term_permalink = true;

						/*
						 * Preserve this URL for later if it's the same as the
						 * permalink (no extra stuff).
						 */
						if ( trim( $permalink, '/' ) === $request_no_slash ) {
							$this->registered_url = $request;
						}

						$found_permalink = $permalink;
						$term_link       = $this->original_term_link( $term['id'] );
						$original_url    = str_replace(
							trim( $permalink, '/' ),
							$term_link,
							trim( $request, '/' )
						);
					}
				}
			}
		}

		$this->parse_request_status = false;
		if ( null !== $original_url ) {
			$this->parse_request_status = true;

			/*
			 * Allow redirect function to work if permalink is not exactly matched
			 * with the requested URL. Like Trailing slash (Requested URL doesn't
			 * contain trailing slash but permalink has trailing slash or vice versa)
			 * and letter-case issue etc.
			 */
			if ( ! empty( $found_permalink ) && $found_permalink !== $request ) {
				$this->parse_request_status = false;
			}

			$original_url = str_replace( '//', '/', $original_url );
			$pos          = strpos( $this->request_uri, '?' );
			if ( false !== $pos ) {
				$query_vars = substr( $this->request_uri, $pos + 1 );
				if ( false === strpos( $original_url, '?' ) ) {
					$original_url .= '?' . $query_vars;
				} else {
					$original_url .= '&' . $query_vars;
				}
			}

			/*
			 * Now we have the original URL, run this back through WP->parse_request,
			 * in order to parse parameters properly. We set `$_SERVER` variables to
			 * fool the function.
			 */
			$_SERVER['REQUEST_URI'] = '/' . ltrim( $original_url, '/' );
			$path_info              = apply_filters(
				'custom_permalinks_path_info',
				'__false'
			);
			if ( '__false' !== $path_info ) {
				$_SERVER['PATH_INFO'] = '/' . ltrim( $original_url, '/' );
			}

			$_SERVER['QUERY_STRING'] = '';
			$pos                     = strpos( $original_url, '?' );
			if ( false !== $pos ) {
				$_SERVER['QUERY_STRING'] = substr( $original_url, $pos + 1 );
			}

			$old_values  = array();
			$query_array = array();
			if ( isset( $_SERVER['QUERY_STRING'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				parse_str( wp_unslash( $_SERVER['QUERY_STRING'] ), $query_array );
			}

			if ( is_array( $query_array ) && count( $query_array ) > 0 ) {
				foreach ( $query_array as $key => $value ) {
					$old_values[ $key ] = '';
					// phpcs:disable WordPress.Security.NonceVerification.Recommended
					if ( isset( $_REQUEST[ $key ] ) ) {
						// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$old_values[ $key ] = wp_unslash( $_REQUEST[ $key ] );
					}
					// phpcs:enable WordPress.Security.NonceVerification.Recommended

					$_GET[ $key ]     = $value;
					$_REQUEST[ $key ] = $value;
				}
			}

			// Re-run the filter, now with original environment in place.
			remove_filter( 'request', array( $this, 'parse_request' ) );
			global $wp;
			if ( isset( $wp->matched_rule ) ) {
				$wp->matched_rule = null;
			}
			$wp->parse_request();
			$query = $wp->query_vars;
			add_filter( 'request', array( $this, 'parse_request' ) );

			// Restore values.
			$_SERVER['REQUEST_URI']  = $this->request_uri;
			$_SERVER['QUERY_STRING'] = $this->query_string_uri;
			foreach ( $old_values as $key => $value ) {
				$_REQUEST[ $key ] = $value;
			}
		}

		return $query;
	}

	/**
	 * Filters the determined post ID and change it if we have a matching URL in CP.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param int    $post_id    Post ID or 0.
	 * @param string $oembed_url The requested URL.
	 *
	 * @return int Post ID or 0.
	 */
	public function oembed_request( $post_id, $oembed_url ) {
		global $wpdb;

		/*
		 * First, search for a matching custom permalink, and if found
		 * generate the corresponding original URL.
		 */
		$original_url = null;
		$oembed_url   = str_replace( home_url(), '', $oembed_url );

		// Get request URI, strip parameters and /'s.
		$url     = wp_parse_url( get_bloginfo( 'url' ) );
		$url     = isset( $url['path'] ) ? $url['path'] : '';
		$request = ltrim( substr( $oembed_url, strlen( $url ) ), '/' );
		$pos     = strpos( $request, '?' );
		if ( $pos ) {
			$request = substr( $request, 0, $pos );
		}

		if ( ! $request ) {
			return $post_id;
		}

		$ignore = apply_filters( 'custom_permalinks_request_ignore', $request );

		if ( '__true' === $ignore ) {
			return $post_id;
		}

		if ( defined( 'POLYLANG_VERSION' ) ) {
			$cp_form = new Custom_Permalinks_Form();
			$request = $cp_form->check_conflicts( $request );
		}
		$request_no_slash = preg_replace( '@/+@', '/', trim( $request, '/' ) );
		$posts            = $this->query_post( $request_no_slash );

		if ( $posts && $posts[0]->ID && $posts[0]->ID > 0 ) {
			$post_id = $posts[0]->ID;
		}

		return $post_id;
	}

	/**
	 * Action to redirect to the custom permalink.
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @return void
	 */
	public function make_redirect() {
		global $wpdb;

		/*
		 * If `parse_request()` succeeded then early return to make performance
		 * better.
		 */
		if ( $this->parse_request_status ) {
			return;
		}

		if ( isset( $_SERVER['REQUEST_URI'] )
			&& $_SERVER['REQUEST_URI'] !== $this->request_uri
		) {
			$this->request_uri = sanitize_url(
				wp_unslash( $_SERVER['REQUEST_URI'] )
			);
		}

		$custom_permalink   = '';
		$original_permalink = '';

		// Get request URI, strip parameters.
		$url     = wp_parse_url( get_bloginfo( 'url' ) );
		$url     = isset( $url['path'] ) ? $url['path'] : '';
		$request = ltrim( substr( $this->request_uri, strlen( $url ) ), '/' );
		$pos     = strpos( $request, '?' );
		if ( $pos ) {
			$request = substr( $request, 0, $pos );
		}

		/*
		 * Disable redirects to be processed if filter returns `true`.
		 *
		 * @since 1.7.0
		 */
		$avoid_redirect = apply_filters(
			'custom_permalinks_avoid_redirect',
			$request
		);

		if ( is_bool( $avoid_redirect ) && $avoid_redirect ) {
			return;
		}

		if ( defined( 'POLYLANG_VERSION' ) ) {
			$cp_form = new Custom_Permalinks_Form();
			$request = $cp_form->check_conflicts( $request );
		}
		$request_no_slash = preg_replace( '@/+@', '/', trim( $request, '/' ) );
		$posts            = $this->query_post( $request_no_slash );

		if ( ! isset( $posts[0]->ID ) || ! isset( $posts[0]->meta_value )
			|| empty( $posts[0]->meta_value )
		) {
			global $wp_query;

			/*
			 * If the post/tag/category we're on has a custom permalink, get it
			 * and check against the request.
			 */
			if ( ( is_single() || is_page() ) && ! empty( $wp_query->post ) ) {
				$post             = $wp_query->post;
				$custom_permalink = get_post_meta(
					$post->ID,
					'custom_permalink',
					true
				);
				if ( 'page' === $post->post_type ) {
					$original_permalink = $this->original_page_link( $post->ID );
				} else {
					$original_permalink = $this->original_post_link( $post->ID );
				}
			} elseif ( is_tag() || is_category() ) {
				$the_term           = $wp_query->get_queried_object();
				$custom_permalink   = $this->term_permalink( $the_term->term_id );
				$original_permalink = $this->original_term_link( $the_term->term_id );
			}
		} else {
			$custom_permalink = $posts[0]->meta_value;
			if ( 'page' === $posts[0]->post_type ) {
				$original_permalink = $this->original_page_link( $posts[0]->ID );
			} else {
				$original_permalink = $this->original_post_link( $posts[0]->ID );
			}
		}

		$custom_length = strlen( $custom_permalink );
		if ( $custom_permalink
			&& (
				substr( $request, 0, $custom_length ) !== $custom_permalink
				|| $request === $custom_permalink . '/'
			)
		) {
			// Request doesn't match permalink - redirect.
			$url             = $custom_permalink;
			$original_length = strlen( $original_permalink );

			if ( substr( $request, 0, $original_length ) === $original_permalink
				&& trim( $request, '/' ) !== trim( $original_permalink, '/' )
			) {
				// This is the original link; we can use this URL to derive the new one.
				$url = preg_replace(
					'@//*@',
					'/',
					str_replace(
						trim( $original_permalink, '/' ),
						trim( $custom_permalink, '/' ),
						$request
					)
				);
				$url = preg_replace( '@([^?]*)&@', '\1?', $url );
			}

			// Append any query compenent.
			$url .= strstr( $this->request_uri, '?' );

			wp_safe_redirect( home_url() . '/' . $url, 301 );
			exit( 0 );
		}
	}

	/**
	 * Filter to replace the post permalink with the custom one.
	 *
	 * @access public
	 *
	 * @param string $permalink Default WordPress Permalink of Post.
	 * @param object $post Post Details.
	 *
	 * @return string customized Post Permalink.
	 */
	public function custom_post_link( $permalink, $post ) {
		$custom_permalink = get_post_meta( $post->ID, 'custom_permalink', true );
		if ( $custom_permalink ) {
			$post_type = 'post';
			if ( isset( $post->post_type ) ) {
				$post_type = $post->post_type;
			}

			$language_code = apply_filters(
				'wpml_element_language_code',
				null,
				array(
					'element_id'   => $post->ID,
					'element_type' => $post_type,
				)
			);

			$permalink = $this->wpml_permalink_filter(
				$custom_permalink,
				$language_code
			);
		} else {
			if ( class_exists( 'SitePress' ) ) {
				$wpml_lang_format = apply_filters(
					'wpml_setting',
					0,
					'language_negotiation_type'
				);

				// Different languages in directories.
				if ( 1 === intval( $wpml_lang_format ) ) {
					$get_original_url = $this->original_post_link( $post->ID );
					$permalink        = $this->remove_double_slash( $permalink );
					if ( strlen( $get_original_url ) === strlen( $permalink ) ) {
						$permalink = $get_original_url;
					}
				}
			}
		}

		$permalink = $this->remove_double_slash( $permalink );

		return $permalink;
	}

	/**
	 * Filter to replace the page permalink with the custom one.
	 *
	 * @access public
	 *
	 * @param string $permalink Default WordPress Permalink of Page.
	 * @param int    $page      Page ID.
	 *
	 * @return string customized Page Permalink.
	 */
	public function custom_page_link( $permalink, $page ) {
		$custom_permalink = get_post_meta( $page, 'custom_permalink', true );
		if ( $custom_permalink ) {
			$language_code = apply_filters(
				'wpml_element_language_code',
				null,
				array(
					'element_id'   => $page,
					'element_type' => 'page',
				)
			);

			$permalink = $this->wpml_permalink_filter(
				$custom_permalink,
				$language_code
			);
		} else {
			if ( class_exists( 'SitePress' ) ) {
				$wpml_lang_format = apply_filters(
					'wpml_setting',
					0,
					'language_negotiation_type'
				);

				// Different languages in directories.
				if ( 1 === intval( $wpml_lang_format ) ) {
					$get_original_url = $this->original_page_link( $page );
					$permalink        = $this->remove_double_slash( $permalink );
					if ( strlen( $get_original_url ) === strlen( $permalink ) ) {
						$permalink = $get_original_url;
					}
				}
			}
		}

		$permalink = $this->remove_double_slash( $permalink );

		return $permalink;
	}

	/**
	 * Filter to replace the term permalink with the custom one.
	 *
	 * @access public
	 *
	 * @param string $permalink Term link URL.
	 * @param object $term      Term object.
	 *
	 * @return string customized Term Permalink.
	 */
	public function custom_term_link( $permalink, $term ) {
		if ( isset( $term ) ) {
			if ( isset( $term->term_id ) ) {
				$custom_permalink = $this->term_permalink( $term->term_id );
			}

			if ( $custom_permalink ) {
				$language_code = null;
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

				$permalink = $this->wpml_permalink_filter(
					$custom_permalink,
					$language_code
				);
			} elseif ( isset( $term->term_id ) ) {
				if ( class_exists( 'SitePress' ) ) {
					$wpml_lang_format = apply_filters(
						'wpml_setting',
						0,
						'language_negotiation_type'
					);

					// Different languages in directories.
					if ( 1 === intval( $wpml_lang_format ) ) {
						$get_original_url = $this->original_term_link(
							$term->term_id
						);
						$permalink        = $this->remove_double_slash( $permalink );
						if ( strlen( $get_original_url ) === strlen( $permalink ) ) {
							$permalink = $get_original_url;
						}
					}
				}
			}
		}

		$permalink = $this->remove_double_slash( $permalink );

		return $permalink;
	}

	/**
	 * Remove the post_link and user_trailingslashit filter to get the original
	 * permalink of the default and custom post type and apply right after that.
	 *
	 * @access public
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string Original Permalink for Posts.
	 */
	public function original_post_link( $post_id ) {
		remove_filter( 'post_link', array( $this, 'custom_post_link' ) );
		remove_filter( 'post_type_link', array( $this, 'custom_post_link' ) );

		$post_file_path = ABSPATH . '/wp-admin/includes/post.php';
		include_once $post_file_path;

		list( $permalink, $post_name ) = get_sample_permalink( $post_id );
		$permalink                     = str_replace(
			array( '%pagename%', '%postname%' ),
			$post_name,
			$permalink
		);
		$permalink                     = ltrim( str_replace( home_url(), '', $permalink ), '/' );

		add_filter( 'post_link', array( $this, 'custom_post_link' ), 10, 3 );
		add_filter( 'post_type_link', array( $this, 'custom_post_link' ), 10, 2 );

		return $permalink;
	}

	/**
	 * Remove the page_link and user_trailingslashit filter to get the original
	 * permalink of the page and apply right after that.
	 *
	 * @access public
	 *
	 * @param int $post_id Page ID.
	 *
	 * @return string Original Permalink for the Page.
	 */
	public function original_page_link( $post_id ) {
		remove_filter( 'page_link', array( $this, 'custom_page_link' ) );
		remove_filter(
			'user_trailingslashit',
			array( $this, 'custom_trailingslash' )
		);

		$post_file_path = ABSPATH . '/wp-admin/includes/post.php';
		include_once $post_file_path;

		list( $permalink, $post_name ) = get_sample_permalink( $post_id );
		$permalink                     = str_replace(
			array( '%pagename%', '%postname%' ),
			$post_name,
			$permalink
		);
		$permalink                     = ltrim( str_replace( home_url(), '', $permalink ), '/' );

		add_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );
		add_filter( 'page_link', array( $this, 'custom_page_link' ), 10, 2 );

		return $permalink;
	}

	/**
	 * Remove the term_link and user_trailingslashit filter to get the original
	 * permalink of the Term and apply right after that.
	 *
	 * @since 1.6.0
	 * @access public
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return string Original Permalink for Posts.
	 */
	public function original_term_link( $term_id ) {
		remove_filter( 'term_link', array( $this, 'custom_term_link' ) );
		remove_filter(
			'user_trailingslashit',
			array( $this, 'custom_trailingslash' )
		);

		$term      = get_term( $term_id );
		$term_link = get_term_link( $term );

		add_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );
		add_filter( 'term_link', array( $this, 'custom_term_link' ), 10, 2 );

		if ( is_wp_error( $term_link ) ) {
			return '';
		}

		$original_permalink = ltrim( str_replace( home_url(), '', $term_link ), '/' );

		return $original_permalink;
	}

	/**
	 * Filter to handle trailing slashes correctly.
	 *
	 * @access public
	 *
	 * @param string $url_string URL with or without a trailing slash.
	 *
	 * @return string Adds/removes a trailing slash based on the permalink structure.
	 */
	public function custom_trailingslash( $url_string ) {
		remove_filter(
			'user_trailingslashit',
			array( $this, 'custom_trailingslash' )
		);

		$trailingslash_string = $url_string;
		$url                  = wp_parse_url( get_bloginfo( 'url' ) );

		if ( isset( $url['path'] ) ) {
			$request = substr( $url_string, strlen( $url['path'] ) );
		} else {
			$request = $url_string;
		}

		$request = ltrim( $request, '/' );

		add_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );

		if ( trim( $request ) ) {
			if ( trim( $this->registered_url, '/' ) === trim( $request, '/' ) ) {
				if ( '/' === $url_string[0] ) {
					$trailingslash_string = '/';
				} else {
					$trailingslash_string = '';
				}

				if ( isset( $url['path'] ) ) {
					$trailingslash_string .= trailingslashit( $url['path'] );
				}

				$trailingslash_string .= $this->registered_url;
			}
		}

		return $trailingslash_string;
	}

	/**
	 * Get permalink for term.
	 *
	 * @access public
	 *
	 * @param int $term_id Term id.
	 *
	 * @return bool Term link.
	 */
	public function term_permalink( $term_id ) {
		$table = get_option( 'custom_permalink_table' );
		if ( $table ) {
			foreach ( $table as $link => $info ) {
				if ( $info['id'] === $term_id ) {
					return $link;
				}
			}
		}

		return false;
	}

	/**
	 * Fix double slash issue with canonical of Yoast SEO specially with WPML.
	 *
	 * @since 1.6.0
	 * @access public
	 *
	 * @param string $canonical The canonical.
	 *
	 * @return string the canonical after removing double slash if exist.
	 */
	public function fix_canonical_double_slash( $canonical ) {
		$canonical = $this->remove_double_slash( $canonical );

		return $canonical;
	}
}
