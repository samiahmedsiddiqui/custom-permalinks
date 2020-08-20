<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_Frontend
{

    /*
     * The query string, if any, via which the page is accessed otherwise empty.
     */
    private $query_string_uri = '';

    /*
     * The URI which is given in order to access this page. Default empty.
     */
    private $request_uri = '';

    /**
     * Initialize WordPress Hooks.
     *
     * @since 1.2.0
     * @access public
     */
    public function init()
    {
        if ( isset( $_SERVER['QUERY_STRING'] ) ) {
            $this->query_string_uri = $_SERVER['QUERY_STRING'];
        }

        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $this->request_uri = $_SERVER['REQUEST_URI'];
        }

        add_action( 'template_redirect', array( $this, 'make_redirect' ), 5 );

        add_filter( 'request', array( $this, 'parse_request' ) );
        add_filter( 'post_link', array( $this, 'custom_post_link' ), 10, 2 );
        add_filter( 'post_type_link', array( $this, 'custom_post_link' ), 10, 2 );
        add_filter( 'page_link', array( $this, 'custom_page_link' ), 10, 2 );
        add_filter( 'term_link', array( $this, 'custom_term_link' ), 10, 2 );
        add_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );

        // WPSEO Filters
        add_filter( 'wpseo_canonical',
            array( $this, 'fix_canonical_double_slash' ), 20, 1
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
    public function remove_double_slash( $permalink = '' )
    {
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
     * @param string $permalink Custom Permalink.
     * @param string $language_code The language to convert the url into.
     *
     * @return string permalink with language information.
     */
    public function wpml_permalink_filter( $permalink = '', $language_code )
    {
        $custom_permalink   = $permalink;
        $trailing_permalink = trailingslashit( home_url() ) . $custom_permalink;
        if ( $language_code ) {
            $permalink = apply_filters( 'wpml_permalink', $trailing_permalink,
                $language_code
            );
            $site_url  = site_url();
            $wpml_href = str_replace( $site_url, '', $permalink );
            if ( 0 === strpos( $wpml_href, '//' ) ) {
                if ( 0 !== strpos( $wpml_href, '//' . $language_code  . '/' ) ) {
                    $permalink = $site_url . '/' . $language_code  . '/' . $custom_permalink;
                }
            }
        } else {
            $permalink = apply_filters( 'wpml_permalink', $trailing_permalink );
        }

        return $permalink;
    }

    /**
     * Filter to rewrite the query if we have a matching post.
     *
     * @since 0.1.0
     * @access public
     *
     * @param string $query Requested URL.
     *
     * @return string the URL which has to be parsed.
     */
    public function parse_request( $query )
    {
        global $wpdb;
        global $_CPRegisteredURL;

        if ( isset( $_SERVER['REQUEST_URI'] )
            && $_SERVER['REQUEST_URI'] !== $this->request_uri
        ) {
            $this->request_uri = $_SERVER['REQUEST_URI'];
        }

        /*
         * First, search for a matching custom permalink, and if found
         * generate the corresponding original URL
         */
        $original_url = NULL;

        // Get request URI, strip parameters and /'s
        $url     = parse_url( get_bloginfo( 'url' ) );
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
            $cp_file_path  = CUSTOM_PERMALINKS_PATH;
            $cp_file_path .= 'frontend/class-custom-permalinks-form.php';
            include_once $cp_file_path;

            $cp_form = new Custom_Permalinks_Form();
            $request = $cp_form->check_conflicts( $request );
        }
        $request_no_slash = preg_replace( '@/+@','/', trim( $request, '/' ) );

        $sql = $wpdb->prepare( "SELECT p.ID, pm.meta_value, p.post_type, p.post_status " .
                " FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON (pm.post_id = p.ID) " .
                " WHERE pm.meta_key = 'custom_permalink' " .
                " AND (pm.meta_value = '%s' OR pm.meta_value = '%s') " .
                " AND p.post_status != 'trash' AND p.post_type != 'nav_menu_item' " .
                " ORDER BY FIELD(post_status,'publish','private','pending','draft','auto-draft','inherit')," .
                " FIELD(post_type,'post','page') LIMIT 1", $request_no_slash, $request_no_slash . "/" );

        $posts = $wpdb->get_results( $sql );

        $remove_like_query = apply_filters( 'cp_remove_like_query', '__true' );
        if ( ! $posts && '__true' === $remove_like_query ) {
            $sql = $wpdb->prepare( "SELECT p.ID, pm.meta_value, p.post_type, p.post_status FROM $wpdb->posts AS p " .
                    " LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE " .
                    " meta_key = 'custom_permalink' AND meta_value != '' AND " .
                    " ( LOWER(meta_value) = LEFT(LOWER('%s'), LENGTH(meta_value)) OR " .
                    "   LOWER(meta_value) = LEFT(LOWER('%s'), LENGTH(meta_value)) ) " .
                    "  AND post_status != 'trash' AND post_type != 'nav_menu_item'" .
                    " ORDER BY LENGTH(meta_value) DESC, " .
                    " FIELD(post_status,'publish','private','pending','draft','auto-draft','inherit')," .
                    " FIELD(post_type,'post','page'), p.ID ASC LIMIT 1",
                    $request_no_slash, $request_no_slash . "/" );

            $posts = $wpdb->get_results( $sql );
        }

        if ( $posts ) {
            /*
             * A post matches our request. Preserve this url for later
             * if it's the same as the permalink (no extra stuff).
             */
            if ( $request_no_slash == trim( $posts[0]->meta_value, '/' ) ) {
                $_CPRegisteredURL = $request;
            }

            if ( 'draft' === $posts[0]->post_status ) {
                if ( 'page' === $posts[0]->post_type ) {
                    $original_url = '?page_id=' . $posts[0]->ID;
                } else {
                    $original_url = '?post_type=' . $posts[0]->post_type . '&p=' . $posts[0]->ID;
                }
            } else {
                $post_meta = trim( strtolower( $posts[0]->meta_value ), '/' );
                if ( 'page' === $posts[0]->post_type ) {
                    $get_original_url = $this->original_page_link( $posts[0]->ID );
                    $original_url     = preg_replace( '@/+@', '/',
                        str_replace( $post_meta, $get_original_url,
                            strtolower( $request_no_slash )
                        )
                    );
                } else {
                    $get_original_url = $this->original_post_link( $posts[0]->ID );
                    $original_url     = preg_replace( '@/+@', '/',
                        str_replace( $post_meta, $get_original_url,
                            strtolower( $request_no_slash )
                        )
                    );
                }
            }
        }

        if ( NULL === $original_url ) {
            // See if any terms have a matching permalink
            $table = get_option( 'custom_permalink_table' );
            if ( ! $table ) {
                return $query;
            }

            foreach ( array_keys( $table ) as $permalink ) {
                if ( $permalink === substr( $request_no_slash, 0, strlen( $permalink ) )
                    || $permalink === substr( $request_no_slash . '/', 0, strlen( $permalink ) )
                ) {
                    $term = $table[$permalink];

                    /*
                     * Preserve this url for later if it's the same as the
                     * permalink (no extra stuff)
                     */
                    if ( $request_no_slash === trim( $permalink, '/' ) ) {
                        $_CPRegisteredURL = $request;
                    }

                    $term_link    = $this->original_term_link( $term['id'] );
                    $original_url = str_replace( trim( $permalink, '/' ),
                        $term_link, trim( $request, '/' )
                    );
                }
            }
        }

        if ( NULL !== $original_url ) {
            $original_url = str_replace( '//', '/', $original_url );

            $pos = strpos( $this->request_uri, '?' );
            if ( false !== $pos ) {
                $query_vars    = substr( $this->request_uri, $pos + 1 );
                $original_url .= ( strpos( $original_url, '?' ) === false ? '?' : '&' ) . $query_vars;
            }

            /*
             * Now we have the original URL, run this back through WP->parse_request,
             * in order to parse parameters properly.
             * We set $_SERVER variables to fool the function.
             */
            $_SERVER['REQUEST_URI'] = '/' . ltrim( $original_url, '/' );
            $path_info = apply_filters( 'custom_permalinks_path_info', '__false' );
            if ( '__false' !== $path_info ) {
                $_SERVER['PATH_INFO'] = '/' . ltrim( $original_url, '/' );
            }

            $_SERVER['QUERY_STRING'] = '';
            $pos = strpos( $original_url, '?' );
            if ( false !== $pos ) {
                $_SERVER['QUERY_STRING'] = substr( $original_url, $pos + 1 );
            }

            parse_str( $_SERVER['QUERY_STRING'], $query_array );
            $old_values = array();
            if ( is_array( $query_array ) ) {
                foreach ( $query_array as $key => $value ) {
                    $old_values[$key] = '';
                    if ( isset( $_REQUEST[$key] ) ) {
                        $old_values[$key] = $_REQUEST[$key];
                    }
                    $_REQUEST[$key] = $_GET[$key] = $value;
                }
            }

            // Re-run the filter, now with original environment in place
            remove_filter( 'request', array( $this, 'parse_request' ) );
            global $wp;
            if ( isset( $wp->matched_rule ) ) {
                $wp->matched_rule = NULL;
            }
            $wp->parse_request();
            $query = $wp->query_vars;
            add_filter( 'request', array( $this, 'parse_request' ) );

            // Restore values
            $_SERVER['REQUEST_URI']  = $this->request_uri;
            $_SERVER['QUERY_STRING'] = $this->query_string_uri;
            foreach ( $old_values as $key => $value ) {
                $_REQUEST[$key] = $value;
            }
        }

        return $query;
    }

    /**
     * Action to redirect to the custom permalink.
     *
     * @since 0.1.0
     * @access public
     */
    public function make_redirect()
    {
        global $wpdb;

        if ( isset( $_SERVER['REQUEST_URI'] )
            && $_SERVER['REQUEST_URI'] !== $this->request_uri
        ) {
            $this->request_uri = $_SERVER['REQUEST_URI'];
        }

        $custom_permalink   = '';
        $original_permalink = '';

        // Get request URI, strip parameters
        $url     = parse_url( get_bloginfo( 'url' ) );
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
        $avoid_redirect = apply_filters( 'custom_permalinks_avoid_redirect',
            $request
        );

        if ( is_bool( $avoid_redirect ) && $avoid_redirect ) {
            return;
        }

        if ( defined( 'POLYLANG_VERSION' ) ) {
            $cp_file_path  = CUSTOM_PERMALINKS_PATH;
            $cp_file_path .= 'frontend/class-custom-permalinks-form.php';
            include_once $cp_file_path;

            $cp_form = new Custom_Permalinks_Form();
            $request = $cp_form->check_conflicts( $request );
        }
        $request_no_slash = preg_replace( '@/+@','/', trim( $request, '/' ) );

        $sql = $wpdb->prepare( "SELECT p.ID, pm.meta_value, p.post_type, p.post_status " .
                " FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON (pm.post_id = p.ID) " .
                " WHERE pm.meta_key = 'custom_permalink' " .
                " AND (pm.meta_value = '%s' OR pm.meta_value = '%s') " .
                " AND p.post_status != 'trash' AND p.post_type != 'nav_menu_item' " .
                " ORDER BY FIELD(post_status,'publish','private','draft','auto-draft','inherit')," .
                " FIELD(post_type,'post','page') LIMIT 1", $request_no_slash, $request_no_slash . "/" );

        $posts = $wpdb->get_results( $sql );

        $remove_like_query = apply_filters( 'cp_remove_like_query', '__true' );
        if ( ! $posts && '__false' !== $remove_like_query ) {
            $sql = $wpdb->prepare( "SELECT p.ID, pm.meta_value, p.post_type, p.post_status FROM $wpdb->posts AS p " .
                    " LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE " .
                    " meta_key = 'custom_permalink' AND meta_value != '' AND " .
                    " ( LOWER(meta_value) = LEFT(LOWER('%s'), LENGTH(meta_value)) OR " .
                    "   LOWER(meta_value) = LEFT(LOWER('%s'), LENGTH(meta_value)) ) " .
                    "  AND post_status != 'trash' AND post_type != 'nav_menu_item'" .
                    " ORDER BY LENGTH(meta_value) DESC, " .
                    " FIELD(post_status,'publish','private','draft','auto-draft','inherit')," .
                    " FIELD(post_type,'post','page'), p.ID ASC LIMIT 1",
                    $request_no_slash, $request_no_slash . "/" );

            $posts = $wpdb->get_results( $sql );
        }

        if ( ! isset( $posts[0]->ID ) || ! isset( $posts[0]->meta_value )
            || empty( $posts[0]->meta_value )
        ) {
            global $wp_query;

            /*
             * If the post/tag/category we're on has a custom permalink, get it
             * and check against the request.
             */
            if ( ( is_single() || is_page() ) && ! empty( $wp_query->post ) ) {
                $post = $wp_query->post;
                $custom_permalink = get_post_meta( $post->ID,
                    'custom_permalink', true
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

        if ( $custom_permalink
            && (
                substr( $request, 0, strlen( $custom_permalink ) ) != $custom_permalink
                || $request == $custom_permalink . '/'
            )
        ) {
            // Request doesn't match permalink - redirect
            $url = $custom_permalink;

            if ( substr( $request, 0, strlen( $original_permalink ) ) == $original_permalink
                && trim( $request, '/' ) != trim( $original_permalink, '/' )
            ) {
                // This is the original link; we can use this url to derive the new one
                $url = preg_replace( '@//*@', '/', str_replace(
                        trim( $original_permalink, '/' ),
                        trim( $custom_permalink, '/' ), $request
                ) );
                $url = preg_replace( '@([^?]*)&@', '\1?', $url );
            }

            // Append any query compenent
            $url .= strstr( $this->request_uri, '?' );

            wp_safe_redirect( home_url() . '/' . $url, 301 );
            exit(0);
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
    public function custom_post_link( $permalink, $post )
    {
        $custom_permalink = get_post_meta( $post->ID, 'custom_permalink', true );
        if ( $custom_permalink ) {
            $post_type = 'post';
            if ( isset( $post->post_type ) ) {
                $post_type = $post->post_type;
            }

            $language_code = apply_filters( 'wpml_element_language_code', null,
                array(
                    'element_id'   => $post->ID,
                    'element_type' => $post_type
                )
            );

            $permalink = $this->wpml_permalink_filter( $custom_permalink,
                $language_code
            );
        } else {
            if ( class_exists( 'SitePress' ) ) {
                $wpml_lang_format = apply_filters( 'wpml_setting', 0,
                    'language_negotiation_type'
                );

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
     * @param int $page Page ID.
     *
     * @return string customized Page Permalink.
     */
    public function custom_page_link( $permalink, $page )
    {
        $custom_permalink = get_post_meta( $page, 'custom_permalink', true );
        if ( $custom_permalink ) {
            $language_code = apply_filters( 'wpml_element_language_code', null,
                array(
                    'element_id'   => $page,
                    'element_type' => 'page'
                )
            );

            $permalink = $this->wpml_permalink_filter( $custom_permalink,
                $language_code
            );
        } else {
            if ( class_exists( 'SitePress' ) ) {
                $wpml_lang_format = apply_filters( 'wpml_setting', 0,
                    'language_negotiation_type'
                );

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
     * @param object $term Term object.
     *
     * @return string customized Term Permalink.
     */
    public function custom_term_link( $permalink, $term )
    {
        if ( isset( $term ) ) {
            if ( isset( $term->term_id ) ) {
                $custom_permalink = $this->term_permalink( $term->term_id );
            }

            if ( $custom_permalink ) {
                $language_code = NULL;
                if ( isset( $term->term_taxonomy_id ) ) {
                    $term_type = 'category';
                    if ( isset( $term->taxonomy ) ) {
                        $term_type = $term->taxonomy;
                    }

                    $language_code = apply_filters( 'wpml_element_language_code',
                        null, array(
                            'element_id'   => $term->term_taxonomy_id,
                            'element_type' => $term_type
                        )
                    );
                }

                $permalink = $this->wpml_permalink_filter( $custom_permalink,
                    $language_code
                );
            } elseif ( isset( $term->term_id ) ) {
                if ( class_exists( 'SitePress' ) ) {
                    $wpml_lang_format = apply_filters( 'wpml_setting', 0,
                        'language_negotiation_type'
                    );

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
    public function original_post_link( $post_id )
    {
        remove_filter( 'post_link', array( $this, 'custom_post_link' ) );
        remove_filter( 'post_type_link', array( $this, 'custom_post_link' ) );

        $post_file_path = ABSPATH . '/wp-admin/includes/post.php';
        require_once $post_file_path;

        list( $permalink, $post_name ) = get_sample_permalink( $post_id );
        $permalink = str_replace( array( '%pagename%','%postname%' ), $post_name,
            $permalink
        );
        $permalink = ltrim( str_replace( home_url(), '', $permalink ), '/' );

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
    public function original_page_link( $post_id )
    {
        remove_filter( 'page_link', array( $this, 'custom_page_link' ) );
        remove_filter( 'user_trailingslashit',
            array( $this, 'custom_trailingslash' )
        );

        $post_file_path = ABSPATH . '/wp-admin/includes/post.php';
        require_once $post_file_path;

        list( $permalink, $post_name ) = get_sample_permalink( $post_id );
        $permalink = str_replace( array( '%pagename%','%postname%' ), $post_name,
            $permalink
        );
        $permalink = ltrim( str_replace( home_url(), '', $permalink ), '/' );

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
    public function original_term_link( $term_id )
    {
        remove_filter( 'term_link', array( $this, 'custom_term_link' ) );
        remove_filter( 'user_trailingslashit',
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
    public function custom_trailingslash( $url_string )
    {
        global $_CPRegisteredURL;

        remove_filter( 'user_trailingslashit',
            array( $this, 'custom_trailingslash' )
        );

        $trailingslash_string = $url_string;
        $url = parse_url( get_bloginfo( 'url' ) );

        if ( isset( $url['path'] ) ) {
            $request = substr( $url_string, strlen( $url['path'] ) );
        } else {
            $request = $url_string;
        }

        $request = ltrim( $request, '/' );

        add_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );

        if ( trim( $request ) ) {
            if ( trim( $_CPRegisteredURL, '/' ) == trim( $request, '/' ) ) {
                if ( '/' === $url_string[0] ) {
                    $trailingslash_string = '/';
                } else {
                    $trailingslash_string = '';
                }

                if ( isset( $url['path'] ) ) {
                    $trailingslash_string .= trailingslashit( $url['path'] );
                }

                $trailingslash_string .= $_CPRegisteredURL;
            }
        }

        return $trailingslash_string;
    }

    /**
     * Get permalink for term.
     *
     * @access public
     *
     * @return bool Term link.
     */
    public function term_permalink( $term_id )
    {
        $table = get_option( 'custom_permalink_table' );
        if ( $table ) {
            foreach ( $table as $link => $info ) {
                if ( $info['id'] == $term_id ) {
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
    public function fix_canonical_double_slash( $canonical )
    {
        $canonical = $this->remove_double_slash( $canonical );

        return $canonical;
    }
}
