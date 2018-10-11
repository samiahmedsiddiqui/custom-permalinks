<?php
/**
 * @package CustomPermalinks\Frontend
 */

class Custom_Permalinks_Frontend {

  /**
   * Initialize WordPress Hooks
   *
   * @access public
   * @since 1.2
   * @return void
   */
  public function init() {
    add_filter( 'request', array( $this, 'parse_request' ), 10, 1 );

    add_action( 'template_redirect', array( $this, 'make_redirect' ), 5 );

    add_filter( 'post_link',
      array( $this, 'custom_permalinks_post_link' ), 10, 2
    );
    add_filter( 'post_type_link',
      array( $this, 'custom_permalinks_post_link' ), 10, 2
    );
    add_filter( 'page_link',
      array( $this, 'custom_permalinks_page_link' ), 10, 2
    );

    add_filter(
      'tag_link', array( $this, 'custom_permalinks_term_link' ), 10, 2
    );
    add_filter( 'category_link',
      array( $this, 'custom_permalinks_term_link' ), 10, 2
    );

    add_filter( 'user_trailingslashit',
      array( $this, 'custom_permalinks_trailingslash' ), 10, 2
    );
  }

  /**
   * Filter to rewrite the query if we have a matching post
   *
   * @access public
   * @since 0.1
   * @return void
   */
  public function parse_request( $query ) {
    global $wpdb;
    global $_CPRegisteredURL;

    // First, search for a matching custom permalink,
    // and if found, generate the corresponding original URL

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
      $request = $cp_form->custom_permalinks_check_conflicts( $request );
    }
    $request_noslash = preg_replace( '@/+@','/', trim( $request, '/' ) );

    $sql = $wpdb->prepare( "SELECT p.ID, pm.meta_value, p.post_type, p.post_status " .
            " FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON (pm.post_id = p.ID) " .
            " WHERE pm.meta_key = 'custom_permalink' " .
            " AND (pm.meta_value = '%s' OR pm.meta_value = '%s') " .
            " AND p.post_status != 'trash' AND p.post_type != 'nav_menu_item' " .
            " ORDER BY FIELD(post_status,'publish','private','pending','draft','auto-draft','inherit')," .
            " FIELD(post_type,'post','page') LIMIT 1", $request_noslash, $request_noslash . "/" );

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
              $request_noslash, $request_noslash . "/" );

      $posts = $wpdb->get_results( $sql );
    }

    if ( $posts ) {
      // A post matches our request
      // Preserve this url for later if it's the same as the permalink (no extra stuff)
      if ( $request_noslash == trim( $posts[0]->meta_value, '/' ) ) {
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
          $get_original_url = $this->custom_permalinks_original_page_link( $posts[0]->ID );
          $original_url = preg_replace( '@/+@', '/',
            str_replace( $post_meta, $get_original_url, strtolower( $request_noslash ) )
          );
        } else {
          $get_original_url = $this->custom_permalinks_original_post_link( $posts[0]->ID );
          $original_url = preg_replace( '@/+@', '/',
            str_replace( $post_meta, $get_original_url, strtolower( $request_noslash ) )
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
        if ( $permalink == substr( $request_noslash, 0, strlen( $permalink ) )
          || $permalink == substr( $request_noslash . '/', 0, strlen( $permalink ) ) ) {
          $term = $table[$permalink];

          // Preserve this url for later if it's the same as the permalink (no extra stuff)
          if ( $request_noslash == trim( $permalink, '/' ) ) {
            $_CPRegisteredURL = $request;
          }

          if ( 'category' == $term['kind'] ) {
            $category_link = $this->custom_permalinks_original_category_link( $term['id'] );
          } else {
            $category_link = $this->custom_permalinks_original_tag_link( $term['id'] );
          }

          $original_url = str_replace(
            trim( $permalink, '/' ), $category_link, trim( $request, '/' )
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

      // Now we have the original URL, run this back through WP->parse_request,
      // in order to parse parameters properly.
      // We set $_SERVER variables to fool the function.
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
          $old_values[$key] = $_REQUEST[$key];
          $_REQUEST[$key]   = $_GET[$key] = $value;
        }
      }

      // Re-run the filter, now with original environment in place
      remove_filter( 'request', array( $this, 'parse_request' ), 10, 1 );
      global $wp;
      $wp->parse_request();
      $query = $wp->query_vars;
      add_filter( 'request', array( $this, 'parse_request' ), 10, 1 );

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
   * Action to redirect to the custom permalink
   *
   * @access public
   * @since 0.1
   * @return void
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
      $request = $cp_form->custom_permalinks_check_conflicts( $request );
    }
    $request_noslash = preg_replace( '@/+@','/', trim( $request, '/' ) );

    $sql = $wpdb->prepare( "SELECT p.ID, pm.meta_value, p.post_type, p.post_status " .
            " FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON (pm.post_id = p.ID) " .
            " WHERE pm.meta_key = 'custom_permalink' " .
            " AND (pm.meta_value = '%s' OR pm.meta_value = '%s') " .
            " AND p.post_status != 'trash' AND p.post_type != 'nav_menu_item' " .
            " ORDER BY FIELD(post_status,'publish','private','draft','auto-draft','inherit')," .
            " FIELD(post_type,'post','page') LIMIT 1", $request_noslash, $request_noslash . "/" );

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
              $request_noslash, $request_noslash . "/" );

      $posts = $wpdb->get_results( $sql );
    }

    if ( ! isset( $posts[0]->ID ) || ! isset( $posts[0]->meta_value )
      || empty( $posts[0]->meta_value ) ) {
      global $wp_query;

      // If the post/tag/category we're on has a custom permalink, get it and
      // check against the request
      if ( ( is_single() || is_page() ) && ! empty( $wp_query->post ) ) {
        $post = $wp_query->post;
        $custom_permalink = get_post_meta( $post->ID, 'custom_permalink', true );
        if ( $post->post_type == 'page' ) {
          $original_permalink = $this->custom_permalinks_original_page_link( $post->ID );
        } else {
          $original_permalink = $this->custom_permalinks_original_post_link( $post->ID );
        }
      } elseif ( is_tag() || is_category() ) {
        $theTerm = $wp_query->get_queried_object();
        $custom_permalink = $this->custom_permalinks_permalink_for_term( $theTerm->term_id );
        if ( is_tag() ) {
          $original_permalink = $this->custom_permalinks_original_tag_link( $theTerm->term_id );
        } else {
          $original_permalink = $this->custom_permalinks_original_category_link( $theTerm->term_id );
        }
      }
    } else {
      $custom_permalink = $posts[0]->meta_value;
      if ( 'page' == $posts[0]->post_type ) {
        $original_permalink = $this->custom_permalinks_original_page_link( $posts[0]->ID );
      } else {
        $original_permalink = $this->custom_permalinks_original_post_link( $posts[0]->ID );
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
      exit();
    }
  }

  /**
   * Filter to replace the post permalink with the custom one
   *
   * @access public
   * @return string
   */
  public function custom_permalinks_post_link( $permalink, $post ) {
    $custom_permalink = get_post_meta( $post->ID, 'custom_permalink', true );
    if ( $custom_permalink ) {
      $post_type = isset( $post->post_type ) ? $post->post_type : 'post';
      $language_code = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $post->ID, 'element_type' => $post_type ) );
      if ( $language_code )
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink, $language_code );
      else
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink );
    }

    return $permalink;
  }

  /**
   * Filter to replace the page permalink with the custom one
   *
   * @access public
   * @return string
   */
  public function custom_permalinks_page_link( $permalink, $page ) {
    $custom_permalink = get_post_meta( $page, 'custom_permalink', true );
    if ( $custom_permalink ) {
      $language_code = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $page, 'element_type' => 'page' ) );
      if ( $language_code )
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink, $language_code );
      else
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink );
    }

    return $permalink;
  }

  /**
   * Filter to replace the term permalink with the custom one
   *
   * @access public
   * @return string
   */
  public function custom_permalinks_term_link( $permalink, $term ) {
    $table = get_option( 'custom_permalink_table' );
    if ( is_object( $term ) ) {
      $term = $term->term_id;
    }

    $custom_permalink = $this->custom_permalinks_permalink_for_term( $term );
    if ( $custom_permalink ) {
      $taxonomy = get_term( $term );
      if ( isset( $taxonomy ) && isset( $taxonomy->term_taxonomy_id ) ) {
        $term_type = 'category';
        if ( isset( $taxonomy->taxonomy ) ) {
          $term_type =  $taxonomy->taxonomy;
        }
        $language_code = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $taxonomy->term_taxonomy_id, 'element_type' => $term_type ) );
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink, $language_code );
      } else {
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $custom_permalink );
      }
    }

    return $permalink;
  }

  /**
   * Get original permalink for post
   *
   * @access public
   * @return string
   */
  public function custom_permalinks_original_post_link( $post_id ) {
    remove_filter( 'post_link', array( $this, 'custom_permalinks_post_link' ), 10, 3 );
    remove_filter( 'post_type_link', array( $this, 'custom_permalinks_post_link' ), 10, 2 );

    require_once ABSPATH . '/wp-admin/includes/post.php';
    list( $permalink, $post_name ) = get_sample_permalink( $post_id );
    $permalink = str_replace( array( '%pagename%','%postname%' ), $post_name, $permalink );
    $permalink = ltrim( str_replace( home_url(), '', $permalink ), '/' );

    add_filter( 'post_link', array( $this, 'custom_permalinks_post_link' ), 10, 3 );
    add_filter( 'post_type_link', array( $this, 'custom_permalinks_post_link' ), 10, 2 );

    return $permalink;
  }

  /**
   * Get original permalink for page
   *
   * @access public
   * @return string
   */
  public function custom_permalinks_original_page_link( $post_id ) {
    remove_filter( 'page_link', array( $this, 'custom_permalinks_page_link' ), 10, 2 );
    remove_filter( 'user_trailingslashit', array( $this, 'custom_permalinks_trailingslash' ), 10, 2 );

    require_once ABSPATH . '/wp-admin/includes/post.php';
    list( $permalink, $post_name ) = get_sample_permalink( $post_id );
    $permalink = str_replace( array( '%pagename%','%postname%' ), $post_name, $permalink );
    $permalink = ltrim( str_replace( home_url(), '', $permalink ), '/' );

    add_filter( 'user_trailingslashit', array( $this, 'custom_permalinks_trailingslash' ), 10, 2 );
    add_filter( 'page_link', array( $this, 'custom_permalinks_page_link' ), 10, 2 );
    return $permalink;
  }

  /**
   * Get original permalink for tag
   *
   * @access public
   * @return string
   */
  public function custom_permalinks_original_tag_link( $tag_id ) {
    remove_filter( 'tag_link', array( $this, 'custom_permalinks_term_link' ), 10, 2 );
    remove_filter( 'user_trailingslashit', array( $this, 'custom_permalinks_trailingslash' ), 10, 2 );
    $originalPermalink = ltrim( str_replace( home_url(), '', get_tag_link( $tag_id ) ), '/' );
    add_filter( 'user_trailingslashit', array( $this, 'custom_permalinks_trailingslash' ), 10, 2 );
    add_filter( 'tag_link', array( $this, 'custom_permalinks_term_link' ), 10, 2 );
    return $originalPermalink;
  }

  /**
   * Get original permalink for category
   *
   * @access public
   * @return string
   */
  public function custom_permalinks_original_category_link( $category_id ) {
    remove_filter( 'category_link', array( $this, 'custom_permalinks_term_link' ), 10, 2 );
    remove_filter( 'user_trailingslashit', array( $this, 'custom_permalinks_trailingslash' ), 10, 2 );
    $originalPermalink = ltrim( str_replace( home_url(), '', get_category_link( $category_id ) ), '/' );
    add_filter( 'user_trailingslashit', array( $this, 'custom_permalinks_trailingslash' ), 10, 2 );
    add_filter( 'category_link', array( $this, 'custom_permalinks_term_link' ), 10, 2 );
    return $originalPermalink;
  }

  /**
   * Filter to handle trailing slashes correctly
   *
   * @access public
   * @return string
   */
  public function custom_permalinks_trailingslash( $string, $type ) {
    global $_CPRegisteredURL;

    remove_filter( 'user_trailingslashit', array( $this, 'custom_permalinks_trailingslash' ), 10, 2 );
    $url = parse_url( get_bloginfo( 'url' ) );
    $request = ltrim( isset( $url['path'] ) ? substr( $string, strlen( $url['path'] ) ) : $string, '/' );
    add_filter( 'user_trailingslashit', array( $this, 'custom_permalinks_trailingslash' ), 10, 2 );

    if ( ! trim( $request ) ) {
      return $string;
    }

    if ( trim( $_CPRegisteredURL, '/' ) == trim( $request, '/' ) ) {
      if ( isset( $url['path'] ) ) {
        return ( $string{0} == '/' ? '/' : '' ) . trailingslashit( $url['path'] ) . $_CPRegisteredURL;
      } else {
        return ( $string{0} == '/' ? '/' : '' ) . $_CPRegisteredURL;
      }
    }
    return $string;
  }

  /**
   * Get permalink for term
   *
   * @access public
   * @return boolean
   */
  public function custom_permalinks_permalink_for_term( $id ) {
    $table = get_option( 'custom_permalink_table' );
    if ( $table ) {
      foreach ( $table as $link => $info ) {
        if ( $info['id'] == $id ) {
          return $link;
        }
      }
    }
    return false;
  }
}
