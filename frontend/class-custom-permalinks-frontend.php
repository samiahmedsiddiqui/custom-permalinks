<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_Frontend {

  /**
   * Initialize WordPress Hooks.
   *
   * @since 1.2.0
   * @access public
   */
  public function init() {
    add_action( 'template_redirect', array( $this, 'make_redirect' ), 5 );

    add_filter( 'request', array( $this, 'parse_request' ) );
    add_filter( 'post_link', array( $this, 'custom_post_link' ), 10, 2 );
    add_filter( 'post_type_link', array( $this, 'custom_post_link' ), 10, 2 );
    add_filter( 'page_link', array( $this, 'custom_page_link' ), 10, 2 );
    add_filter( 'term_link', array( $this, 'custom_term_link' ), 10, 2 );
    add_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );

    // WPSEO Filters
    add_filter( 'wpseo_canonical', array( $this, 'fix_canonical_double_slash' ), 20, 1 );
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
  public function parse_request( $query ) {
    global $wpdb;
    global $_CPRegisteredURL;

    /*
     * First, search for a matching custom permalink, and if found
     * generate the corresponding original URL
     */
    $original_url = NULL;

    // Get request URI, strip parameters and /'s
    $url     = parse_url( get_bloginfo( 'url' ) );
    $url     = isset( $url['path'] ) ? $url['path'] : '';
    $request = ltrim( substr( $_SERVER['REQUEST_URI'], strlen( $url ) ), '/' );
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
      require_once(
        CUSTOM_PERMALINKS_PATH . 'frontend/class-custom-permalinks-form.php'
      );
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

      if ( 'draft' == $posts[0]->post_status ) {
        if ( 'page' == $posts[0]->post_type ) {
          $original_url = '?page_id=' . $posts[0]->ID;
        } else {
          $original_url = '?post_type=' . $posts[0]->post_type . '&p=' . $posts[0]->ID;
        }
      } else {
        $post_meta = trim( strtolower( $posts[0]->meta_value ), '/' );
        if ( $posts[0]->post_type == 'page' ) {
          $get_original_url = $this->original_page_link( $posts[0]->ID );
          $original_url     = preg_replace( '@/+@', '/',
            str_replace( $post_meta, $get_original_url, strtolower( $request_no_slash ) )
          );
        } else {
          $get_original_url = $this->original_post_link( $posts[0]->ID );
          $original_url     = preg_replace( '@/+@', '/',
            str_replace( $post_meta, $get_original_url, strtolower( $request_no_slash ) )
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
          || $permalink === substr( $request_no_slash . '/', 0, strlen( $permalink ) ) ) {
          $term = $table[$permalink];

          // Preserve this url for later if it's the same as the permalink (no extra stuff)
          if ( $request_no_slash === trim( $permalink, '/' ) ) {
            $_CPRegisteredURL = $request;
          }

          $term_link    = $this->original_term_link( $term['id'] );
          $original_url = str_replace(
            trim( $permalink, '/' ), $term_link, trim( $request, '/' )
          );
        }
      }
    }

    if ( NULL !== $original_url ) {
      $original_url = str_replace( '//', '/', $original_url );

      $pos = strpos( $_SERVER['REQUEST_URI'], '?' );
      if ( false !== $pos ) {
        $query_vars    = substr( $_SERVER['REQUEST_URI'], $pos + 1 );
        $original_url .= ( strpos( $original_url, '?' ) === false ? '?' : '&' ) . $query_vars;
      }

      /*
       * Now we have the original URL, run this back through WP->parse_request,
       * in order to parse parameters properly.
       * We set $_SERVER variables to fool the function.
       */
      $old_request_uri  = $_SERVER['REQUEST_URI'];
      $old_query_string = '';
      if ( isset( $_SERVER['QUERY_STRING'] ) ) {
        $old_query_string = $_SERVER['QUERY_STRING'];
      }
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
      $_SERVER['REQUEST_URI']  = $old_request_uri;
      $_SERVER['QUERY_STRING'] = $old_query_string;
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
  public function make_redirect() {
    global $wpdb;

    $custom_permalink   = '';
    $original_permalink = '';

    // Get request URI, strip parameters
    $url     = parse_url( get_bloginfo( 'url' ) );
    $url     = isset( $url['path'] ) ? $url['path'] : '';
    $request = ltrim( substr( $_SERVER['REQUEST_URI'], strlen( $url ) ), '/' );
    $pos     = strpos( $request, '?' );
    if ( $pos ) {
      $request = substr( $request, 0, $pos );
    }

    if ( defined( 'POLYLANG_VERSION' ) ) {
      require_once(
        CUSTOM_PERMALINKS_PATH . 'frontend/class-custom-permalinks-form.php'
      );
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
      || empty( $posts[0]->meta_value ) ) {
      global $wp_query;

      /*
       * If the post/tag/category we're on has a custom permalink, get it and
       * check against the request.
       */
      if ( ( is_single() || is_page() ) && ! empty( $wp_query->post ) ) {
        $post = $wp_query->post;
        $custom_permalink = get_post_meta( $post->ID, 'custom_permalink', true );
        if ( $post->post_type == 'page' ) {
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
      if ( 'page' == $posts[0]->post_type ) {
        $original_permalink = $this->original_page_link( $posts[0]->ID );
      } else {
        $original_permalink = $this->original_post_link( $posts[0]->ID );
      }
    }

    if ( $custom_permalink
      && ( substr( $request, 0, strlen( $custom_permalink ) ) != $custom_permalink
      || $request == $custom_permalink . '/' ) ) {

      // Request doesn't match permalink - redirect
      $url = $custom_permalink;

      if ( substr( $request, 0, strlen( $original_permalink ) ) == $original_permalink
        && trim( $request, '/' ) != trim( $original_permalink, '/' ) ) {
        // This is the original link; we can use this url to derive the new one
        $url = preg_replace( '@//*@', '/', str_replace( trim( $original_permalink, '/' ), trim( $custom_permalink, '/' ), $request ) );
        $url = preg_replace( '@([^?]*)&@', '\1?', $url );
      }

      // Append any query compenent
      $url .= strstr( $_SERVER['REQUEST_URI'], '?' );

      wp_redirect( home_url() . '/' . $url, 301 );
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
  public function custom_post_link( $permalink, $post ) {
    $custom_permalink = get_post_meta( $post->ID, 'custom_permalink', true );
    if ( $custom_permalink ) {
      $post_type     = isset( $post->post_type ) ? $post->post_type : 'post';
      $language_code = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $post->ID, 'element_type' => $post_type ) );
      if ( $language_code ) {
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink, $language_code );
      } else {
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink );
      }
    }

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
  public function custom_page_link( $permalink, $page ) {
    $custom_permalink = get_post_meta( $page, 'custom_permalink', true );
    if ( $custom_permalink ) {
      $language_code = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $page, 'element_type' => 'page' ) );
      if ( $language_code ) {
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink, $language_code );
      } else {
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink );
      }
    }

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
  public function custom_term_link( $permalink, $term ) {
    if ( isset( $term ) ) {
      if ( isset( $term->term_id ) ) {
        $custom_permalink = $this->term_permalink( $term->term_id );
      }

      if ( $custom_permalink ) {
        if ( isset( $term->term_taxonomy_id ) ) {
          $term_type = 'category';
          if ( isset( $term->taxonomy ) ) {
            $term_type = $term->taxonomy;
          }
          $language_code = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $term->term_taxonomy_id, 'element_type' => $term_type ) );
          return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink, $language_code );
        } else {
          return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink );
        }
      }
    }

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
    remove_filter( 'post_link', array( $this, 'custom_post_link' ), 10, 3 );
    remove_filter( 'post_type_link', array( $this, 'custom_post_link' ), 10, 2 );

    require_once ABSPATH . '/wp-admin/includes/post.php';
    list( $permalink, $post_name ) = get_sample_permalink( $post_id );
    $permalink = str_replace( array( '%pagename%','%postname%' ), $post_name, $permalink );
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
  public function original_page_link( $post_id ) {
    remove_filter( 'page_link', array( $this, 'custom_page_link' ), 10, 2 );
    remove_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );

    require_once ABSPATH . '/wp-admin/includes/post.php';
    list( $permalink, $post_name ) = get_sample_permalink( $post_id );
    $permalink = str_replace( array( '%pagename%','%postname%' ), $post_name, $permalink );
    $permalink = ltrim( str_replace( home_url(), '', $permalink ), '/' );

    add_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );
    add_filter( 'page_link', array( $this, 'custom_page_link' ), 10, 2 );

    return $permalink;
  }

  /**
   * Remove the term_link and user_trailingslashit filter to get the original
   * permalink of the Term and apply right after that.
   *
   * @since 2.0.0
   * @access public
   *
   * @param int $term_id Term ID.
   *
   * @return string Original Permalink for Posts.
   */
  public function original_term_link( $term_id ) {
    remove_filter( 'term_link', array( $this, 'custom_term_link' ), 10, 2 );
    remove_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );

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
    global $_CPRegisteredURL;

    remove_filter( 'user_trailingslashit', array( $this, 'custom_trailingslash' ) );

    $trailingslash_string = $url_string;
    $url                  = parse_url( get_bloginfo( 'url' ) );

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
  public function term_permalink( $term_id ) {
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
   * @since 2.0.0
   * @access public
   *
   * @param string $canonical The canonical.
   *
   * @return string the canonical after removing double slash if exist.
   */
  public function fix_canonical_double_slash( $canonical ) {
    $protocol = '';
    if ( 0 === strpos( $canonical, 'http://' ) ||
      0 === strpos( $canonical, 'https://' )
    ) {
      $split_protocol = explode( '://', $canonical );
      if ( 1 < count( $split_protocol ) ) {
        $protocol = $split_protocol[0] . '://';
        $canonical = str_replace( $protocol, '', $canonical );
      }
    }

    $canonical = str_replace( '//', '/', $canonical );
    $canonical = $protocol . $canonical;

    return $canonical;
  }
}
