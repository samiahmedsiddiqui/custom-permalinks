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
	 * Whether the URI contains /page/{number} or not. Default false.
	 *
	 * @var int
	 */
	private $is_paged = 0;

	/**
	 * Skip `postid_to_customized_permalink()` when `true`.
	 *
	 * @var bool
	 */
	public static $skip_url_to_postid = false;

	/**
	 * Return links unchanged from the custom link filters when `true`, while
	 * the original links are built.
	 *
	 * @var bool
	 */
	private static $skip_custom_links = false;

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
			// Kept as-is: only used to restore $_SERVER after parse_request.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$this->query_string_uri = $_SERVER['QUERY_STRING'];
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
		add_filter( 'url_to_postid', array( $this, 'postid_to_customized_permalink' ), 10, 1 );
		add_filter( 'term_link', array( $this, 'custom_term_link' ), 10, 2 );
		add_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );
		add_filter( 'get_comment_link', array( $this, 'custom_comment_link' ), 10, 2 );
		add_filter( 'get_comments_pagenum_link', array( $this, 'custom_comments_pagenum_link' ) );

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
	 * Removes the trailing /page/{number} segment from a URL if it exists.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url URL that may contain a pagination segment.
	 *
	 * @return string Cleaned URL without the trailing /page/{number}.
	 */
	public function remove_page_number( $url ) {
		$this->is_paged = 0;
		if ( ! is_string( $url ) ) {
			return $url;
		}

		/**
		 * Keep the trailing /page/{number} segment in the requested URL.
		 *
		 * @since 3.3.0
		 *
		 * @param bool   $disable Whether to keep the pagination segment. Default false.
		 * @param string $url     URL that may contain a pagination segment.
		 */
		if ( true === apply_filters( 'custom_permalinks_disable_remove_page_number', false, $url ) ) {
			return $url;
		}

		if ( preg_match( '/\/page\/(\d+)\/?$/', $url, $matches ) ) {
			$has_trailing_slash = false;
			if ( '/' === substr( $url, -1 ) ) {
				$has_trailing_slash = true;
			}

			if ( isset( $matches[1] ) && 1 < $matches[1] ) {
				$this->is_paged = (int) $matches[1];
			}

			$url = preg_replace( '/\/page\/\d+\/?$/', '', $url );
			if ( $has_trailing_slash && '/' !== substr( $url, -1 ) ) {
				$url .= '/';
			}
		}

		return $url;
	}

	/**
	 * Appends the /page/{number} segment removed by `remove_page_number()`.
	 *
	 * @since 3.3.0
	 *
	 * @param string $url URL without the pagination segment.
	 *
	 * @return string URL with the pagination segment if the request was paged.
	 */
	private function add_page_number( $url ) {
		if ( 0 >= $this->is_paged ) {
			return $url;
		}

		if ( '/' === substr( $url, -1 ) ) {
			return $url . 'page/' . $this->is_paged . '/';
		}

		return $url . '/page/' . $this->is_paged;
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
			// Start from the unfiltered home so the current language isn't already baked into the URL.
			$permalink = apply_filters(
				'wpml_permalink',
				trailingslashit( set_url_scheme( get_option( 'home' ) ) ) . $custom_permalink,
				$language_code
			);

			// A non-string return here would trip a PHP warning below and corrupt a REST API JSON response.
			if ( ! is_string( $permalink ) || '' === $permalink ) {
				$permalink = $trailing_permalink;
			}

			// Unfiltered home, so repairs also work when WordPress lives in a subdirectory.
			$home_url      = untrailingslashit( set_url_scheme( get_option( 'home' ) ) );
			$wpml_href     = str_replace( $home_url, '', $permalink );
			$language_path = $this->wpml_language_directory( $language_code, $home_url );

			// No directory to repair for a hidden default language or a per-language domain.
			if ( '' !== $language_path ) {
				// Collapse a duplicated language directory, e.g. `/de/de/slug`, back to a single `/{lang}/` prefix.
				$duplicate_prefix = '/' . $language_path . '/' . $language_path . '/';
				if ( 0 === strpos( $wpml_href, $duplicate_prefix ) ) {
					$permalink = $home_url . '/' . $language_path . '/' . substr( $wpml_href, strlen( $duplicate_prefix ) );
				}

				if ( 0 === strpos( $wpml_href, '//' ) ) {
					if ( 0 !== strpos( $wpml_href, '//' . $language_path . '/' ) ) {
						$permalink = $home_url . '/' . $language_path . '/' . $custom_permalink;
					}
				}
			}
		} else {
			$permalink = apply_filters( 'wpml_permalink', $trailing_permalink );
			if ( ! is_string( $permalink ) || '' === $permalink ) {
				$permalink = $trailing_permalink;
			}
		}

		return $permalink;
	}

	/**
	 * Get the URL directory WPML serves a language under, which can differ
	 * from its code (e.g. `de-de` for `de`).
	 *
	 * @since 3.3.0
	 * @access private
	 *
	 * @param string $language_code WPML language code.
	 * @param string $home_url      Unfiltered home URL, without a trailing slash.
	 *
	 * @return string Language directory, or empty string if the language has none.
	 */
	private function wpml_language_directory( $language_code, $home_url ) {
		$language_home = apply_filters(
			'wpml_permalink',
			trailingslashit( $home_url ),
			$language_code
		);

		if ( ! is_string( $language_home ) || 0 !== strpos( $language_home, $home_url ) ) {
			return '';
		}

		$language_path = trim( substr( $language_home, strlen( $home_url ) ), '/' );

		// Collapse a duplicated directory, e.g. `de/de`, to a single one.
		$segments = explode( '/', $language_path );
		if ( 2 === count( $segments ) && $segments[0] === $segments[1] ) {
			$language_path = $segments[0];
		}

		return $language_path;
	}

	/**
	 * Resolve the WPML-translated post/page for a permalink lookup.
	 *
	 * @since 3.2.0
	 * @access private
	 *
	 * @param int    $element_id   Post/Page ID being linked to.
	 * @param string $element_type Element type (`post`, `page`, or a custom post type).
	 *
	 * @return array Translated (or original) post/page ID and its custom permalink.
	 */
	private function wpml_translated_permalink( $element_id, $element_type ) {
		$custom_permalink = get_post_meta( $element_id, 'custom_permalink', true );

		if ( class_exists( 'SitePress' ) && ! is_admin() ) {
			$current_language = apply_filters( 'wpml_current_language', null );
			$default_language = apply_filters( 'wpml_default_language', null );
			$element_language = apply_filters(
				'wpml_element_language_code',
				null,
				array(
					'element_id'   => $element_id,
					'element_type' => $element_type,
				)
			);

			// Only swap links to the original, so a translation keeps its own URL.
			if ( ! $element_language || $element_language !== $default_language ) {
				return array( $element_id, $custom_permalink );
			}

			$translated_id = apply_filters(
				'wpml_object_id',
				$element_id,
				$element_type,
				true,
				$current_language
			);

			if ( $translated_id && (int) $translated_id !== (int) $element_id ) {
				$translated_permalink = get_post_meta( $translated_id, 'custom_permalink', true );
				if ( $translated_permalink ) {
					$element_id       = $translated_id;
					$custom_permalink = $translated_permalink;
				}
			}
		}

		return array( $element_id, $custom_permalink );
	}

	/**
	 * Get the current WPML/Polylang language code, regardless of negotiation type.
	 *
	 * @since 3.2.0
	 * @access private
	 *
	 * @return string Current language code, or empty string if none.
	 */
	private function current_language() {
		$current_language = '';

		if ( class_exists( 'SitePress' ) ) {
			$current_language = apply_filters( 'wpml_current_language', null );
		} elseif ( defined( 'POLYLANG_VERSION' ) && function_exists( 'pll_current_language' ) ) {
			$current_language = pll_current_language();
		}

		return $current_language ? $current_language : '';
	}

	/**
	 * Search a permalink in the posts table, preferring a post matching the
	 * current language, and falling back to a language-agnostic lookup.
	 *
	 * @since 3.2.0
	 * @access private
	 *
	 * @param string $requested_url Requested URL.
	 *
	 * @return object[]|null Rows containing Post ID, Permalink, Post Type, and Post
	 *                       status if URL matched otherwise returns null.
	 */
	private function query_post_current_language( $requested_url ) {
		$current_language = $this->current_language();
		$posts            = null;

		if ( ! empty( $current_language ) ) {
			$posts = $this->query_post_language( $requested_url, $current_language );
		}

		if ( ! $posts ) {
			$posts = $this->query_post( $requested_url );
		}

		// Permalinks saved under an English locale are stored percent-encoded.
		$encoded_url = utf8_uri_encode( $requested_url );
		if ( ! $posts && $encoded_url !== $requested_url ) {
			$posts = $this->query_post_current_language( $encoded_url );
		}

		return $posts;
	}

	/**
	 * Search a permalink in the posts table and return its result.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @param string $requested_url Requested URL.
	 *
	 * @return object[]|null Rows containing Post ID, Permalink, Post Type, and Post
	 *                       status if URL matched otherwise returns null.
	 */
	private function query_post( $requested_url ) {
		global $wpdb;

		$cache_name = 'cp$_' . str_replace( '/', '-', $requested_url ) . '_#cp';
		$posts      = wp_cache_get( $cache_name, 'custom_permalinks' );

		if ( false === $posts ) {
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

			// Cache permalink for 24 hours.
			wp_cache_set( $cache_name, $posts, 'custom_permalinks', 86400 );
		}

		return $posts;
	}

	/**
	 * Get a post's language from WPML/Polylang, falling back to the stored meta.
	 *
	 * @since 3.3.0
	 * @access private
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Post type.
	 *
	 * @return string Language code, or empty string if none.
	 */
	private function post_language( $post_id, $post_type ) {
		// The meta can be missing or stale, e.g. saved before Polylang assigned the language.
		$language_code = apply_filters(
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $post_id,
				'element_type' => $post_type,
			)
		);

		if ( ! $language_code && function_exists( 'pll_get_post_language' ) ) {
			$language_code = pll_get_post_language( $post_id );
		}

		if ( ! $language_code ) {
			$language_code = get_post_meta( $post_id, 'custom_permalink_language', true );
		}

		return $language_code ? $language_code : '';
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
	 * @return object[]|null Rows containing Post ID, Permalink, Post Type, and Post
	 *                       status if URL matched otherwise returns null.
	 */
	private function query_post_language( $requested_url, $language_code = null ) {
		global $wpdb;

		$cache_name   = 'cp$' . $language_code . '_' . str_replace( '/', '-', $requested_url ) . '_#cp';
		$matched_post = wp_cache_get( $cache_name, 'custom_permalinks' );

		if ( null === $language_code ) {
			return null;
		}

		if ( false === $matched_post ) {
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
					if ( $this->post_language( $check_data->ID, $check_data->post_type ) === $language_code ) {
						$matched_post[] = $check_data;
						break;
					}
				}
			}
		}

		// Cache permalink for 24 hours.
		wp_cache_set( $cache_name, $matched_post, 'custom_permalinks', 86400 );

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

		// Browsers percent-encode non-ASCII paths; match them decoded.
		$request = rawurldecode( $request );
		$request = $this->remove_page_number( $request );
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

		$found_permalink   = '';
		$permalink_matched = false;
		$request_no_slash  = preg_replace( '@/+@', '/', trim( $request, '/' ) );
		$posts             = $this->query_post_current_language( $request_no_slash );

		if ( $posts ) {
			$found_permalink = rawurldecode( $posts[0]->meta_value );

			/*
			 * A post matches our request. Preserve this URL for later use. If it's
			 * the same as the permalink (no extra stuff).
			 */
			if ( trim( $found_permalink, '/' ) === $request_no_slash ) {
				$this->registered_url = $request;
				$permalink_matched    = true;
			}

			if ( 'draft' === $posts[0]->post_status
				|| 'pending' === $posts[0]->post_status
			) {
				if ( 'page' === $posts[0]->post_type ) {
					$original_url = '?page_id=' . $posts[0]->ID;
				} else {
					$original_url = '?post_type=' . $posts[0]->post_type . '&p=' . $posts[0]->ID;
				}
			} else {
				$post_meta = trim( strtolower( $found_permalink ), '/' );
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

		if ( null === $original_url || ! $permalink_matched ) {
			// See if any terms have a matching permalink.
			$table = get_option( 'custom_permalink_table' );
			if ( $table ) {
				$term_permalink = false;
				foreach ( $table as $table_key => $term ) {
					$permalink   = rawurldecode( $table_key );
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

				/*
				 * Force redirect if requested permalink and found permalink only
				 * differs by trailing slash.
				 */
				$permalink_without_trailing = rtrim( $found_permalink, '/' );
				if ( $permalink_without_trailing === $request
					|| $permalink_without_trailing . '/' === $request
				) {
					$avoid_redirect = apply_filters( 'custom_permalinks_avoid_redirect', $request );
					if ( ! is_bool( $avoid_redirect ) || ! $avoid_redirect ) {
						// Append any query component.
						$this->safe_redirect(
							$this->add_page_number( $found_permalink )
								. strstr( $this->request_uri, '?' )
						);

						return $query;
					}
				}
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

			if ( ! empty( $query_array ) ) {
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
			if ( 0 < $this->is_paged ) {
				$query['paged'] = $this->is_paged;
			}

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
	 * Trigger safe redirect.
	 *
	 * @since 3.1.2
	 * @access private
	 *
	 * @param string $url Relative URL.
	 *
	 * @return void
	 */
	private function safe_redirect( $url ) {
		/*
		 * Prevent caches from storing the redirect, as it carries the visitor's
		 * query string (e.g. UTM parameters) which caches may not key on.
		 */
		nocache_headers();

		$home_url = home_url();
		if ( '/' === substr( $home_url, -1 ) ) {
			wp_safe_redirect( $home_url . $url, 301 );
		} else {
			wp_safe_redirect( $home_url . '/' . $url, 301 );
		}

		exit( 0 );
	}

	/**
	 * Redirect a request for the original permalink to the custom one,
	 * preserving any extra path (e.g. a WooCommerce endpoint) after it.
	 *
	 * @since 3.2.0
	 * @access private
	 *
	 * @param string $request            Requested path with query string stripped.
	 * @param string $custom_permalink   Custom permalink, or empty if none set.
	 * @param string $original_permalink Default/original permalink.
	 *
	 * @return void
	 */
	private function redirect_to_custom_permalink( $request, $custom_permalink, $original_permalink ) {
		if ( ! $custom_permalink ) {
			return;
		}

		// Compare against the decoded request.
		$custom_permalink   = rawurldecode( $custom_permalink );
		$original_permalink = rawurldecode( $original_permalink );
		$custom_length      = strlen( $custom_permalink );
		if ( substr( $request, 0, $custom_length ) === $custom_permalink
			&& $request !== $custom_permalink . '/'
		) {
			// Already on the custom permalink, optionally with an endpoint appended.
			return;
		}

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

		// Append any query component.
		$url  = $this->add_page_number( $url );
		$url .= strstr( $this->request_uri, '?' );
		$this->safe_redirect( $url );
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

		// Get request URI, strip parameters.
		$url     = wp_parse_url( get_bloginfo( 'url' ) );
		$url     = isset( $url['path'] ) ? $url['path'] : '';
		$request = ltrim( substr( $this->request_uri, strlen( $url ) ), '/' );
		$pos     = strpos( $request, '?' );
		if ( $pos ) {
			$request = substr( $request, 0, $pos );
		}

		// Browsers percent-encode non-ASCII paths; match them decoded.
		$request = rawurldecode( $request );
		$request = $this->remove_page_number( $request );
		if ( ! $request ) {
			return;
		}

		/*
		 * Disable redirects to be processed if filter returns `true`.
		 *
		 * @since 1.7.0
		 */
		$avoid_redirect = apply_filters( 'custom_permalinks_avoid_redirect', $request );
		if ( is_bool( $avoid_redirect ) && $avoid_redirect ) {
			return;
		}

		$custom_permalink   = '';
		$original_permalink = '';

		remove_filter( 'url_to_postid', array( $this, 'postid_to_customized_permalink' ) );
		$get_post_id = url_to_postid( $this->request_uri );
		add_filter( 'url_to_postid', array( $this, 'postid_to_customized_permalink' ), 10, 1 );

		// Redirect original post permalink.
		if ( ! empty( $get_post_id ) ) {
			$custom_permalink = get_post_meta( $get_post_id, 'custom_permalink', true );
			if ( $custom_permalink ) {
				$original_permalink = 'page' === get_post_type( $get_post_id )
					? $this->original_page_link( $get_post_id )
					: $this->original_post_link( $get_post_id );

				$this->redirect_to_custom_permalink(
					$request,
					$custom_permalink,
					$original_permalink
				);
			}
		} else {
			if ( defined( 'POLYLANG_VERSION' ) ) {
				$cp_form = new Custom_Permalinks_Form();
				$request = $cp_form->check_conflicts( $request );
			}

			$request_no_slash = preg_replace( '@/+@', '/', trim( $request, '/' ) );
			$posts            = $this->query_post_current_language( $request_no_slash );

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
					$the_term = $wp_query->get_queried_object();
					if ( isset( $the_term, $the_term->term_id ) ) {
						$custom_permalink   = $this->term_permalink( $the_term->term_id );
						$original_permalink = $this->original_term_link( $the_term->term_id );
					}
				}
			}

			$this->redirect_to_custom_permalink(
				$request,
				$custom_permalink,
				$original_permalink
			);
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
		if ( self::$skip_custom_links ) {
			return $permalink;
		}

		$post_type = 'post';
		if ( isset( $post->post_type ) ) {
			$post_type = $post->post_type;
		}

		list( $post_id, $custom_permalink ) = $this->wpml_translated_permalink(
			$post->ID,
			$post_type
		);

		if ( $custom_permalink ) {
			$language_code = apply_filters(
				'wpml_element_language_code',
				null,
				array(
					'element_id'   => $post_id,
					'element_type' => $post_type,
				)
			);

			$permalink = $this->wpml_permalink_filter(
				$custom_permalink,
				$language_code
			);
		} elseif ( class_exists( 'SitePress' ) ) {
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
		if ( self::$skip_custom_links ) {
			return $permalink;
		}

		list( $page, $custom_permalink ) = $this->wpml_translated_permalink(
			$page,
			'page'
		);

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
		} elseif ( class_exists( 'SitePress' ) ) {
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

		$permalink = $this->remove_double_slash( $permalink );

		return $permalink;
	}

	/**
	 * Match the comment page segment's trailing slash to the post's custom
	 * permalink.
	 *
	 * @since 3.3.0
	 * @access private
	 *
	 * @param string $url  Comment URL.
	 * @param mixed  $post Post ID or object the comment URL belongs to.
	 *
	 * @return string Comment URL with the trailing slash adjusted.
	 */
	private function comment_page_trailingslash( $url, $post ) {
		global $wp_rewrite;

		$post = get_post( $post );
		if ( ! $post || ! is_string( $url ) || empty( $wp_rewrite->comments_pagination_base ) ) {
			return $url;
		}

		list( , $custom_permalink ) = $this->wpml_translated_permalink(
			$post->ID,
			$post->post_type
		);

		if ( ! $custom_permalink ) {
			return $url;
		}

		$trailing_slash = '/' === substr( $custom_permalink, -1 ) ? '/' : '';
		$pattern        = '@(/' . preg_quote( $wp_rewrite->comments_pagination_base, '@' ) . '-\d+)/?(?=[?#]|$)@';

		return preg_replace( $pattern, '$1' . $trailing_slash, $url );
	}

	/**
	 * Filter to keep the comment link in line with the custom permalink.
	 *
	 * @since 3.3.0
	 * @access public
	 *
	 * @param string     $comment_link The comment permalink.
	 * @param WP_Comment $comment      The comment object.
	 *
	 * @return string Comment link.
	 */
	public function custom_comment_link( $comment_link, $comment ) {
		if ( ! isset( $comment->comment_post_ID ) ) {
			return $comment_link;
		}

		return $this->comment_page_trailingslash(
			$comment_link,
			$comment->comment_post_ID
		);
	}

	/**
	 * Filter to keep the comments pagination link in line with the custom
	 * permalink.
	 *
	 * @since 3.3.0
	 * @access public
	 *
	 * @param string $result The comments page link.
	 *
	 * @return string Comments page link.
	 */
	public function custom_comments_pagenum_link( $result ) {
		return $this->comment_page_trailingslash( $result, get_post() );
	}

	/**
	 * Fetch default permalink against the customized permalink.
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @param string $permalink URL Permalink to check.
	 *
	 * @return string Default Permalink or the same permalink if not found.
	 */
	public function postid_to_customized_permalink( $permalink ) {
		if ( self::$skip_url_to_postid ) {
			return $permalink;
		}

		$customized_permalink = ltrim( $permalink, '/' );
		if ( defined( 'POLYLANG_VERSION' ) ) {
			$cp_form              = new Custom_Permalinks_Form();
			$customized_permalink = $cp_form->check_conflicts( $customized_permalink );
		}

		$customized_permalink = preg_replace( '@/+@', '/', trim( rawurldecode( $customized_permalink ), '/' ) );
		$posts                = $this->query_post_current_language( $customized_permalink );
		if ( is_array( $posts ) && ! empty( $posts ) ) {
			if ( 'draft' === $posts[0]->post_status
				|| 'pending' === $posts[0]->post_status
			) {
				if ( 'page' === $posts[0]->post_type ) {
					$original_url = '?page_id=' . $posts[0]->ID;
				} else {
					$original_url = '?post_type=' . $posts[0]->post_type . '&p=' . $posts[0]->ID;
				}
			} else {
				$post_meta = trim( strtolower( rawurldecode( $posts[0]->meta_value ) ), '/' );
				if ( 'page' === $posts[0]->post_type ) {
					$get_original_url = $this->original_page_link( $posts[0]->ID );
					$original_url     = preg_replace(
						'@/+@',
						'/',
						str_replace(
							$post_meta,
							$get_original_url,
							strtolower( $customized_permalink )
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
							strtolower( $customized_permalink )
						)
					);
				}
			}

			$permalink = $original_url;
		}

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
		if ( self::$skip_custom_links ) {
			return $permalink;
		}

		if ( isset( $term ) ) {
			$custom_permalink = '';
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
	 * Get the original permalink of the default and custom post types, without
	 * the custom permalink filters.
	 *
	 * @access public
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string Original Permalink for Posts.
	 */
	public function original_post_link( $post_id ) {
		$skip_custom_links       = self::$skip_custom_links;
		self::$skip_custom_links = true;

		$post_file_path = ABSPATH . '/wp-admin/includes/post.php';
		include_once $post_file_path;

		list( $permalink, $post_name ) = get_sample_permalink( $post_id );
		$permalink                     = str_replace(
			array( '%pagename%', '%postname%' ),
			$post_name,
			$permalink
		);
		$permalink                     = ltrim( str_replace( home_url(), '', $permalink ), '/' );

		self::$skip_custom_links = $skip_custom_links;

		return $permalink;
	}

	/**
	 * Get the original permalink of the page, without the custom permalink
	 * filters.
	 *
	 * @access public
	 *
	 * @param int $post_id Page ID.
	 *
	 * @return string Original Permalink for the Page.
	 */
	public function original_page_link( $post_id ) {
		$skip_custom_links       = self::$skip_custom_links;
		self::$skip_custom_links = true;

		$post_file_path = ABSPATH . '/wp-admin/includes/post.php';
		include_once $post_file_path;

		list( $permalink, $post_name ) = get_sample_permalink( $post_id );
		$permalink                     = str_replace(
			array( '%pagename%', '%postname%' ),
			$post_name,
			$permalink
		);
		$permalink                     = ltrim( str_replace( home_url(), '', $permalink ), '/' );

		self::$skip_custom_links = $skip_custom_links;

		return $permalink;
	}

	/**
	 * Get the original permalink of the term, without the custom permalink
	 * filters.
	 *
	 * @since 1.6.0
	 * @access public
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return string Original Permalink for Posts.
	 */
	public function original_term_link( $term_id ) {
		$skip_custom_links       = self::$skip_custom_links;
		self::$skip_custom_links = true;

		$term      = get_term( $term_id );
		$term_link = get_term_link( $term );

		self::$skip_custom_links = $skip_custom_links;

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
		if ( self::$skip_custom_links ) {
			return $url_string;
		}

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
	 * @return string|false Term link, or false if the term has no custom permalink.
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
