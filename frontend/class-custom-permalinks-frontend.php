<?php
/**
 * @package CustomPermalinks
 */

class CustomPermalinksFrontend {

  /**
   * Initialize WordPress Hooks.
   *
   * @since 1.2.0
   * @access public
   */
  public function init() {
    add_action( 'template_redirect', array( $this, 'makeRedirect' ), 5 );

    add_filter( 'request', array( $this, 'parseRequest' ) );
    add_filter( 'post_link', array( $this, 'customPostLink' ), 10, 2 );
    add_filter( 'post_type_link', array( $this, 'customPostLink' ), 10, 2 );
    add_filter( 'page_link', array( $this, 'customPageLink' ), 10, 2 );
    add_filter( 'tag_link', array( $this, 'customTermLink' ), 10, 2 );
    add_filter( 'category_link', array( $this, 'customTermLink' ), 10, 2 );
    add_filter( 'user_trailingslashit', array( $this, 'customTrailingslash' ) );
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
  public function parseRequest( $query ) {
    global $wpdb;
    global $_CPRegisteredURL;

    /*
     * First, search for a matching custom permalink, and if found
     * generate the corresponding original URL
     */
    $originalUrl = NULL;

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
      $cpForm = new Custom_Permalinks_Form();
      $request = $cpForm->checkConflicts( $request );
    }
    $requestNoSlash = preg_replace( '@/+@','/', trim( $request, '/' ) );

    $sql = $wpdb->prepare( "SELECT p.ID, pm.meta_value, p.post_type, p.post_status " .
            " FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON (pm.post_id = p.ID) " .
            " WHERE pm.meta_key = 'custom_permalink' " .
            " AND (pm.meta_value = '%s' OR pm.meta_value = '%s') " .
            " AND p.post_status != 'trash' AND p.post_type != 'nav_menu_item' " .
            " ORDER BY FIELD(post_status,'publish','private','pending','draft','auto-draft','inherit')," .
            " FIELD(post_type,'post','page') LIMIT 1", $requestNoSlash, $requestNoSlash . "/" );

    $posts = $wpdb->get_results( $sql );

    $removeLikeQuery = apply_filters( 'cp_remove_like_query', '__true' );
    if ( ! $posts && '__true' === $removeLikeQuery ) {
      $sql = $wpdb->prepare( "SELECT p.ID, pm.meta_value, p.post_type, p.post_status FROM $wpdb->posts AS p " .
              " LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE " .
              " meta_key = 'custom_permalink' AND meta_value != '' AND " .
              " ( LOWER(meta_value) = LEFT(LOWER('%s'), LENGTH(meta_value)) OR " .
              "   LOWER(meta_value) = LEFT(LOWER('%s'), LENGTH(meta_value)) ) " .
              "  AND post_status != 'trash' AND post_type != 'nav_menu_item'" .
              " ORDER BY LENGTH(meta_value) DESC, " .
              " FIELD(post_status,'publish','private','pending','draft','auto-draft','inherit')," .
              " FIELD(post_type,'post','page'), p.ID ASC LIMIT 1",
              $requestNoSlash, $requestNoSlash . "/" );

      $posts = $wpdb->get_results( $sql );
    }

    if ( $posts ) {
      /*
       * A post matches our request. Preserve this url for later
       * if it's the same as the permalink (no extra stuff).
       */
      if ( $requestNoSlash == trim( $posts[0]->meta_value, '/' ) ) {
        $_CPRegisteredURL = $request;
      }

      if ( 'draft' == $posts[0]->post_status ) {
        if ( 'page' == $posts[0]->post_type ) {
          $originalUrl = '?page_id=' . $posts[0]->ID;
        } else {
          $originalUrl = '?post_type=' . $posts[0]->post_type . '&p=' . $posts[0]->ID;
        }
      } else {
        $postMeta = trim( strtolower( $posts[0]->meta_value ), '/' );
        if ( $posts[0]->post_type == 'page' ) {
          $getOriginalUrl = $this->originalPageLink( $posts[0]->ID );
          $originalUrl = preg_replace( '@/+@', '/',
            str_replace( $postMeta, $getOriginalUrl, strtolower( $requestNoSlash ) )
          );
        } else {
          $getOriginalUrl = $this->originalPostLink( $posts[0]->ID );
          $originalUrl = preg_replace( '@/+@', '/',
            str_replace( $postMeta, $getOriginalUrl, strtolower( $requestNoSlash ) )
          );
        }
      }
    }

    if ( NULL === $originalUrl ) {
      // See if any terms have a matching permalink
      $table = get_option( 'custom_permalink_table' );
      if ( ! $table ) {
        return $query;
      }

      foreach ( array_keys( $table ) as $permalink ) {
        if ( $permalink == substr( $requestNoSlash, 0, strlen( $permalink ) )
          || $permalink == substr( $requestNoSlash . '/', 0, strlen( $permalink ) ) ) {
          $term = $table[$permalink];

          // Preserve this url for later if it's the same as the permalink (no extra stuff)
          if ( $requestNoSlash == trim( $permalink, '/' ) ) {
            $_CPRegisteredURL = $request;
          }

          if ( 'category' == $term['kind'] ) {
            $categoryLink = $this->originalCategoryLink( $term['id'] );
          } else {
            $categoryLink = $this->originalTagLink( $term['id'] );
          }

          $originalUrl = str_replace(
            trim( $permalink, '/' ), $categoryLink, trim( $request, '/' )
          );
        }
      }
    }

    if ( NULL !== $originalUrl ) {
      $originalUrl = str_replace( '//', '/', $originalUrl );

      $pos = strpos( $_SERVER['REQUEST_URI'], '?' );
      if ( false !== $pos ) {
        $queryVars    = substr( $_SERVER['REQUEST_URI'], $pos + 1 );
        $originalUrl .= ( strpos( $originalUrl, '?' ) === false ? '?' : '&' ) . $queryVars;
      }

      /*
       * Now we have the original URL, run this back through WP->parse_request,
       * in order to parse parameters properly.
       * We set $_SERVER variables to fool the function.
       */
      $oldRequestUri  = $_SERVER['REQUEST_URI'];
      $oldQueryString = '';
      if ( isset( $_SERVER['QUERY_STRING'] ) ) {
        $oldQueryString = $_SERVER['QUERY_STRING'];
      }
      $_SERVER['REQUEST_URI'] = '/' . ltrim( $originalUrl, '/' );
      $pathInfo = apply_filters( 'custom_permalinks_path_info', '__false' );
      if ( '__false' !== $pathInfo ) {
        $_SERVER['PATH_INFO'] = '/' . ltrim( $originalUrl, '/' );
      }

      $_SERVER['QUERY_STRING'] = '';
      $pos = strpos( $originalUrl, '?' );
      if ( false !== $pos ) {
        $_SERVER['QUERY_STRING'] = substr( $originalUrl, $pos + 1 );
      }

      parse_str( $_SERVER['QUERY_STRING'], $queryArray );
      $oldValues = array();
      if ( is_array( $queryArray ) ) {
        foreach ( $queryArray as $key => $value ) {
          $oldValues[$key] = '';
          if ( isset( $_REQUEST[$key] ) ) {
            $oldValues[$key] = $_REQUEST[$key];
          }
          $_REQUEST[$key] = $_GET[$key] = $value;
        }
      }

      // Re-run the filter, now with original environment in place
      remove_filter( 'request', array( $this, 'parseRequest' ) );
      global $wp;
      if ( isset( $wp->matched_rule ) ) {
        $wp->matched_rule = NULL;
      }
      $wp->parse_request();
      $query = $wp->query_vars;
      add_filter( 'request', array( $this, 'parseRequest' ) );

      // Restore values
      $_SERVER['REQUEST_URI']  = $oldRequestUri;
      $_SERVER['QUERY_STRING'] = $oldQueryString;
      foreach ( $oldValues as $key => $value ) {
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
  public function makeRedirect() {
    global $wpdb;

    $customPermalink   = '';
    $originalPermalink = '';

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
      $cpForm = new Custom_Permalinks_Form();
      $request = $cpForm->checkConflicts( $request );
    }
    $requestNoSlash = preg_replace( '@/+@','/', trim( $request, '/' ) );

    $sql = $wpdb->prepare( "SELECT p.ID, pm.meta_value, p.post_type, p.post_status " .
            " FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON (pm.post_id = p.ID) " .
            " WHERE pm.meta_key = 'custom_permalink' " .
            " AND (pm.meta_value = '%s' OR pm.meta_value = '%s') " .
            " AND p.post_status != 'trash' AND p.post_type != 'nav_menu_item' " .
            " ORDER BY FIELD(post_status,'publish','private','draft','auto-draft','inherit')," .
            " FIELD(post_type,'post','page') LIMIT 1", $requestNoSlash, $requestNoSlash . "/" );

    $posts = $wpdb->get_results( $sql );

    $removeLikeQuery = apply_filters( 'cp_remove_like_query', '__true' );
    if ( ! $posts && '__false' !== $removeLikeQuery ) {
      $sql = $wpdb->prepare( "SELECT p.ID, pm.meta_value, p.post_type, p.post_status FROM $wpdb->posts AS p " .
              " LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE " .
              " meta_key = 'custom_permalink' AND meta_value != '' AND " .
              " ( LOWER(meta_value) = LEFT(LOWER('%s'), LENGTH(meta_value)) OR " .
              "   LOWER(meta_value) = LEFT(LOWER('%s'), LENGTH(meta_value)) ) " .
              "  AND post_status != 'trash' AND post_type != 'nav_menu_item'" .
              " ORDER BY LENGTH(meta_value) DESC, " .
              " FIELD(post_status,'publish','private','draft','auto-draft','inherit')," .
              " FIELD(post_type,'post','page'), p.ID ASC LIMIT 1",
              $requestNoSlash, $requestNoSlash . "/" );

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
        $customPermalink = get_post_meta( $post->ID, 'custom_permalink', true );
        if ( $post->post_type == 'page' ) {
          $originalPermalink = $this->originalPageLink( $post->ID );
        } else {
          $originalPermalink = $this->originalPostLink( $post->ID );
        }
      } elseif ( is_tag() || is_category() ) {
        $theTerm = $wp_query->get_queried_object();
        $customPermalink = $this->termPermalink( $theTerm->term_id );
        if ( is_tag() ) {
          $originalPermalink = $this->originalTagLink( $theTerm->term_id );
        } else {
          $originalPermalink = $this->originalCategoryLink( $theTerm->term_id );
        }
      }
    } else {
      $customPermalink = $posts[0]->meta_value;
      if ( 'page' == $posts[0]->post_type ) {
        $originalPermalink = $this->originalPageLink( $posts[0]->ID );
      } else {
        $originalPermalink = $this->originalPostLink( $posts[0]->ID );
      }
    }

    if ( $customPermalink
      && ( substr( $request, 0, strlen( $customPermalink ) ) != $customPermalink
      || $request == $customPermalink . '/' ) ) {

      // Request doesn't match permalink - redirect
      $url = $customPermalink;

      if ( substr( $request, 0, strlen( $originalPermalink ) ) == $originalPermalink
        && trim( $request, '/' ) != trim( $originalPermalink, '/' ) ) {
        // This is the original link; we can use this url to derive the new one
        $url = preg_replace( '@//*@', '/', str_replace( trim( $originalPermalink, '/' ), trim( $customPermalink, '/' ), $request ) );
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
  public function customPostLink( $permalink, $post ) {
    $customPermalink = get_post_meta( $post->ID, 'custom_permalink', true );
    if ( $customPermalink ) {
      $postType = isset( $post->post_type ) ? $post->post_type : 'post';
      $languageCode = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $post->ID, 'element_type' => $postType ) );
      if ( $languageCode )
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $customPermalink, $languageCode );
      else
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $customPermalink );
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
  public function customPageLink( $permalink, $page ) {
    $customPermalink = get_post_meta( $page, 'custom_permalink', true );
    if ( $customPermalink ) {
      $languageCode = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $page, 'element_type' => 'page' ) );
      if ( $languageCode )
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $customPermalink, $languageCode );
      else
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $customPermalink );
    }

    return $permalink;
  }

  /**
   * Filter to replace the term permalink with the custom one.
   *
   * @access public
   *
   * @param string $permalink Default WordPress Permalink of Term.
   * @param object $term Term Details.
   *
   * @return string customized Term Permalink.
   */
  public function customTermLink( $permalink, $term ) {
    if ( is_object( $term ) ) {
      $term = $term->term_id;
    }

    $customPermalink = $this->termPermalink( $term );
    if ( $customPermalink ) {
      $taxonomy = get_term( $term );
      if ( isset( $taxonomy ) && isset( $taxonomy->term_taxonomy_id ) ) {
        $termType = 'category';
        if ( isset( $taxonomy->taxonomy ) ) {
          $termType =  $taxonomy->taxonomy;
        }
        $languageCode = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $taxonomy->term_taxonomy_id, 'element_type' => $termType ) );
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $customPermalink, $languageCode );
      } else {
        return apply_filters( 'wpml_permalink', trailingslashit( home_url() ) . $customPermalink );
      }
    }

    return $permalink;
  }

  /**
   * Get original permalink for post.
   *
   * @access public
   *
   * @param int $postId Post ID.
   *
   * @return string Original Permalink for Posts.
   */
  public function originalPostLink( $postId ) {
    remove_filter( 'post_link', array( $this, 'customPostLink' ), 10, 3 );
    remove_filter( 'post_type_link', array( $this, 'customPostLink' ), 10, 2 );

    require_once ABSPATH . '/wp-admin/includes/post.php';
    list( $permalink, $postName ) = get_sample_permalink( $postId );
    $permalink = str_replace( array( '%pagename%','%postname%' ), $postName, $permalink );
    $permalink = ltrim( str_replace( home_url(), '', $permalink ), '/' );

    add_filter( 'post_link', array( $this, 'customPostLink' ), 10, 3 );
    add_filter( 'post_type_link', array( $this, 'customPostLink' ), 10, 2 );

    return $permalink;
  }

  /**
   * Get original permalink for page.
   *
   * @access public
   *
   * @param int $postId Page ID.
   *
   * @return string Original Permalink for the Page.
   */
  public function originalPageLink( $postId ) {
    remove_filter( 'page_link', array( $this, 'customPageLink' ), 10, 2 );
    remove_filter( 'user_trailingslashit', array( $this, 'customTrailingslash' ) );

    require_once ABSPATH . '/wp-admin/includes/post.php';
    list( $permalink, $postName ) = get_sample_permalink( $postId );
    $permalink = str_replace( array( '%pagename%','%postname%' ), $postName, $permalink );
    $permalink = ltrim( str_replace( home_url(), '', $permalink ), '/' );

    add_filter( 'user_trailingslashit', array( $this, 'customTrailingslash' ) );
    add_filter( 'page_link', array( $this, 'customPageLink' ), 10, 2 );
    return $permalink;
  }

  /**
   * Get original permalink for tag
   *
   * @access public
   *
   * @param int $tagId Term ID.
   *
   * @return string Original Permalink for the Term.
   */
  public function originalTagLink( $tagId ) {
    remove_filter( 'tag_link', array( $this, 'customTermLink' ), 10, 2 );
    remove_filter( 'user_trailingslashit', array( $this, 'customTrailingslash' ) );
    $originalPermalink = ltrim( str_replace( home_url(), '', get_tag_link( $tagId ) ), '/' );
    add_filter( 'user_trailingslashit', array( $this, 'customTrailingslash' ) );
    add_filter( 'tag_link', array( $this, 'customTermLink' ), 10, 2 );

    return $originalPermalink;
  }

  /**
   * Get original permalink for category.
   *
   * @access public
   *
   * @param int $categoryId Term ID.
   *
   * @return string Original Permalink for the Term.
   */
  public function originalCategoryLink( $categoryId ) {
    remove_filter( 'category_link', array( $this, 'customTermLink' ), 10, 2 );
    remove_filter( 'user_trailingslashit', array( $this, 'customTrailingslash' ) );
    $originalPermalink = ltrim( str_replace( home_url(), '', get_category_link( $categoryId ) ), '/' );
    add_filter( 'user_trailingslashit', array( $this, 'customTrailingslash' ) );
    add_filter( 'category_link', array( $this, 'customTermLink' ), 10, 2 );

    return $originalPermalink;
  }

  /**
   * Filter to handle trailing slashes correctly.
   *
   * @access public
   *
   * @param string $string URL with or without a trailing slash.
   *
   * @return string Adds/removes a trailing slash based on the permalink structure.
   */
  public function customTrailingslash( $string ) {
    global $_CPRegisteredURL;

    remove_filter( 'user_trailingslashit', array( $this, 'customTrailingslash' ) );
    $url = parse_url( get_bloginfo( 'url' ) );
    $request = ltrim( isset( $url['path'] ) ? substr( $string, strlen( $url['path'] ) ) : $string, '/' );
    add_filter( 'user_trailingslashit', array( $this, 'customTrailingslash' ) );

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
   * Get permalink for term.
   *
   * @access public
   *
   * @return bool Term link.
   */
  public function termPermalink( $id ) {
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
