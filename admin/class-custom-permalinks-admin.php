<?php
/**
 * @package CustomPermalinks
 */

class CustomPermalinksAdmin {

  /**
   * Initializes WordPress hooks.
   */
  function __construct() {
    add_action( 'admin_menu', array( $this, 'adminMenu' ) );
    add_filter( 'plugin_action_links_' . CUSTOM_PERMALINKS_BASENAME,
      array( $this, 'settingsLink' )
    );
    add_action( 'admin_init', array( $this, 'privacyPolicy' ) );
  }

  /**
   * Added Pages in Menu for Settings.
   *
   * @since 1.2.0
   * @access public
   */
  public function adminMenu() {
    add_menu_page( 'Custom Permalinks', 'Custom Permalinks', 'cp_view_post_permalinks',
      'cp-post-permalinks', array( $this,'posttypePermalinks' ),
      'dashicons-admin-links'
    );
    add_submenu_page( 'cp-post-permalinks', 'PostTypes Permalinks',
      'PostTypes Permalinks', 'cp_view_post_permalinks', 'cp-post-permalinks',
      array( $this, 'posttypePermalinks' )
    );
    add_submenu_page( 'cp-post-permalinks', 'Category Permalinks',
      'Category Permalinks', 'cp_view_category_permalinks', 'cp-category-permalinks',
      array( $this, 'categoryPermalinks' )
    );
    add_submenu_page( 'cp-post-permalinks', 'About Custom Permalinks',
      'About CP', 'install_plugins', 'cp-about-plugins',
      array( $this, 'aboutPlugin' )
    );
  }

  /**
   * Shows all the Permalinks created by using this Plugin with Pager and
   * Search Functionality of Posts/Pages.
   *
   * @since 1.2.0
   * @access public
   */
  public function posttypePermalinks() {
    global $wpdb;
    $filterOptions   = '';
    $filterPermalink = '';
    $searchPermalink = '';
    $html            = '';
    $error           = '';

    // Handle Bulk Operations
    if ( ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] )
      || ( isset( $_POST['action2'] ) && 'delete' === $_POST['action2'] )
    ) {
      if ( isset( $_POST['permalink'] ) && ! empty( $_POST['permalink'] ) ) {
        $postIds = implode( ',', $_POST['permalink'] );
        if ( preg_match( '/^\d+(?:,\d+)*$/', $postIds ) ) {
          $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN ($postIds) AND meta_key = 'custom_permalink'" );
        } else {
          $error = '<div id="message" class="error">' .
                      '<p>' .
                        __( 'Please select permalinks which you like to be deleted.', 'custom-permalinks' ) .
                      '</p>' .
                    '</div>';
        }
      } else {
        $error = '<div id="message" class="error">' .
                    '<p>' .
                      __( 'There is some error to proceed your request. Please retry with your request or contact to the plugin author.', 'custom-permalinks' ) .
                    '</p>' .
                  '</div>';
      }
    }
    $html .= '<div class="wrap">' .
                '<h1 class="wp-heading-inline">' . __( 'PostTypes Permalinks', 'custom-permalinks' ) . '</h1>' .
                $error;

    $searchValue = '';
    if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
      $filterPermalink = 'AND pm.meta_value LIKE "%' . $_GET['s'] . '%"';
      $searchPermalink = '&s=' . $_GET['s'] . '';
      $searchValue     = ltrim( htmlspecialchars( $_GET['s'] ), '/' );
      $html           .= '<span class="subtitle">Search results for "' . $searchValue . '"</span>';
    }
    $pageLimit = 'LIMIT 0, 20';
    if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
      && 1 < $_GET['paged']
    ) {
      $pager      = 20 * ( $_GET['paged'] - 1 );
      $pageLimit = 'LIMIT ' . $pager . ', 20';
    }
    $sortingBy     = 'ORDER By p.ID DESC';
    $orderBy       = 'asc';
    $orderByClass = 'desc';
    if ( isset( $_GET['orderby'] ) && 'title' == $_GET['orderby'] ) {
      $filterOptions .= '<input type="hidden" name="orderby" value="title" />';
      if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] ) {
        $sortingBy      = 'ORDER By p.post_title DESC';
        $orderBy        = 'asc';
        $orderByClass  = 'desc';
        $filterOptions .= '<input type="hidden" name="order" value="desc" />';
      } else {
        $sortingBy      = 'ORDER By p.post_title';
        $orderBy        = 'desc';
        $orderByClass  = 'asc';
        $filterOptions .= '<input type="hidden" name="order" value="asc" />';
      }
    }
    $countQuery = "SELECT COUNT(p.ID) AS total_permalinks FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != '' " . $filterPermalink . "";
    $countPosts = $wpdb->get_row( $countQuery );

    $html .= '<form action="' . $_SERVER["REQUEST_URI"] . '" method="get">' .
                '<p class="search-box">' .
                '<input type="hidden" name="page" value="cp-post-permalinks" />' .
                $filterOptions .
                '<label class="screen-reader-text" for="custom-permalink-search-input">Search Custom Permalink:</label>' .
                '<input type="search" id="custom-permalink-search-input" name="s" value="' . $searchValue . '">' .
                '<input type="submit" id="search-submit" class="button" value="Search Permalink"></p>' .
              '</form>' .
              '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">';
                '<div class="tablenav top">' .
                  '<div class="alignleft actions bulkactions">' .
                    '<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>' .
                    '<select name="action" id="bulk-action-selector-top">' .
                      '<option value="-1">' . __( "Bulk Actions", "custom-permalinks" ) . '</option>' .
                      '<option value="delete">' . __( "Delete Permalinks", "custom-permalinks" ) . '</option>' .
                    '</select>' .
                    '<input type="submit" id="doaction" class="button action" value="Apply">' .
                  '</div>';

    $posts          = 0;
    $paginationHTML = '';
    if ( isset( $countPosts->total_permalinks )
      && 0 < $countPosts->total_permalinks
    ) {

      $html .= '<h2 class="screen-reader-text">Custom Permalink navigation</h2>';

      $query = "SELECT p.ID, p.post_title, p.post_type, pm.meta_value FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != '' " . $filterPermalink . " " . $sortingBy . " " . $pageLimit . "";
      $posts = $wpdb->get_results( $query );

      $totalPages = ceil( $countPosts->total_permalinks / 20 );
      if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
        && 0 < $_GET['paged']
      ) {
        $paginationHTML = $this->customPager(
          $countPosts->total_permalinks, $_GET['paged'], $totalPages
        );
        if ( $_GET['paged'] > $totalPages ) {
          $redirectUri = explode(
            '&paged=' . $_GET['paged'] . '', $_SERVER['REQUEST_URI']
          );
          header( 'Location: ' . $redirectUri[0], 301 );
          exit(0);
        }
      } elseif ( ! isset( $_GET['paged'] ) ) {
        $paginationHTML = $this->customPager(
          $countPosts->total_permalinks, 1, $totalPages
        );
      }

      $html .= $paginationHTML;
    }
    $tableNavigation = $this->tableNavPosts(
      $orderByClass, $orderBy, $searchPermalink
    );

    $html .= '</div>';
    $html .= '<table class="wp-list-table widefat fixed striped posts">' .
                '<thead>' . $tableNavigation . '</thead>' .
                '<tbody>';
    if ( 0 != $posts && ! empty( $posts ) ) {
      foreach ( $posts as $post ) {
        $html .= '<tr valign="top">' .
                    '<th scope="row" class="check-column">' .
                      '<input type="checkbox" name="permalink[]" value="' . $post->ID . '" />' .
                    '</th>' .
                    '<td><strong>' .
                      '<a class="row-title" href="post.php?action=edit&post=' . $post->ID . '">' . $post->post_title . '</a>' .
                    '</strong></td>' .
                    '<td>' . ucwords( $post->post_type ) . '</td>' .
                    '<td>' .
                      '<a href="/' . $post->meta_value . '" target="_blank" title="' . __( "Visit " . $post->post_title, "custom-permalinks" ) . '">/' .
                        urldecode( $post->meta_value ) .
                      '</a>' .
                    '</td>' .
                  '</tr>';
      }
    } else {
      $html .= '<tr class="no-items">' .
                  '<td class="colspanchange" colspan="10">' . __( "No permalinks found.", "custom-permalinks" ) . '</td>' .
                '</tr>';
    }
    $html .= '</tbody>' .
              '<tfoot>' . $tableNavigation . '</tfoot>' .
              '</table>';

    $html .= '<div class="tablenav bottom">' .
                '<div class="alignleft actions bulkactions">' .
                  '<label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>' .
                  '<select name="action2" id="bulk-action-selector-bottom">' .
                    '<option value="-1">' . __( "Bulk Actions", "custom-permalinks" ) . '</option>' .
                    '<option value="delete">' . __( "Delete Permalinks", "custom-permalinks" ) . '</option>' .
                  '</select>' .
                  '<input type="submit" id="doaction2" class="button action" value="Apply">' .
                '</div>' .
                $paginationHTML .
              '</div>' .
              '</form></div>';
    echo $html;

    add_filter( 'admin_footer_text', array( $this, 'adminFooterText' ), 1 );
  }

  /**
   * Return the Navigation row HTML same as Default Posts page for PostTypes.
   *
   * @since 1.2.0
   * @access private
   *
   * @param string $orderByClass Class either asc or desc.
   * @param string $orderBy set orderby for sorting.
   * @param string $searchPermalink Permalink which has been searched or an empty string.
   *
   * @return string table row according to the provided params.
   */
  private function tableNavPosts( $orderByClass, $orderBy, $searchPermalink ) {
    $nav = '<tr>' .
              '<td id="cb" class="manage-column column-cb check-column">' .
                '<label class="screen-reader-text" for="cb-select-all-1">Select All</label>' .
                '<input id="cb-select-all-1" type="checkbox">' .
              '</td>' .
              '<th scope="col" id="title" class="manage-column column-title column-primary sortable ' . $orderByClass . '">' .
                '<a href="/wp-admin/admin.php?page=cp-post-permalinks&amp;orderby=title&amp;order=' . $orderBy . $searchPermalink . '">' .
                  '<span>' . __( "Title", "custom-permalinks" ) . '</span>' .
                  '<span class="sorting-indicator"></span>' .
                '</a>' .
              '</th>' .
              '<th scope="col">' . __( "Type", "custom-permalinks" ) . '</th>' .
              '<th scope="col">' . __( "Permalink", "custom-permalinks" ) . '</th>' .
            '</tr>';

    return $nav;
  }

  /**
   * Shows all the Permalinks created by using this Plugin with Pager and
   * Search Functionality of Category/Tags.
   *
   * @since 1.2.0
   * @access public
   */
  public function categoryPermalinks() {
    $html = '';

    // Handle Bulk Operations
    if ( ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] )
      || ( isset( $_POST['action2'] ) && 'delete' === $_POST['action2'] )
    ) {
      if ( isset( $_POST['permalink'] ) && ! empty( $_POST['permalink'] ) ) {
        $removePerm = $_POST['permalink'];
        $data = get_option( 'custom_permalink_table' );
        if ( isset( $data ) && is_array( $data ) ) {
          $loopCount = 0;
          foreach ( $data as $link => $info ) {
            if ( in_array( $info['id'], $removePerm ) ) {
              unset( $data[$link] );
              unset( $removePerm[$loopCount] );
              if ( ! is_array( $removePerm ) || empty( $removePerm ) ) {
                break;
              }
            }
            $loopCount++;
          }
        }
        update_option( 'custom_permalink_table', $data );
      }
    }
    $html .= '<div class="wrap">' .
                '<h1 class="wp-heading-inline">' . __( 'Category/Tags Permalinks', 'custom-permalinks' ) . '</h1>';

    $searchValue = '';
    if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
      $searchValue = ltrim( htmlspecialchars( $_GET['s'] ), '/' );
      $html        .= '<span class="subtitle">Search results for "' . $searchValue . '"</span>';
    }
    $pagerOffset = '0';
    $pageLimit   = 20;
    if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
      && 1 < $_GET['paged']
    ) {
      $pagerOffset = 20 * ( $_GET['paged'] - 1 );
      $pageLimit   = $pagerOffset + 20;
    }
    $html .= '<form action="' . $_SERVER["REQUEST_URI"] . '" method="get">' .
                '<p class="search-box">' .
                '<input type="hidden" name="page" value="cp-category-permalinks" />' .
                '<label class="screen-reader-text" for="custom-permalink-search-input">Search Custom Permalink:</label>' .
                '<input type="search" id="custom-permalink-search-input" name="s" value="' . $searchValue . '">' .
                '<input type="submit" id="search-submit" class="button" value="Search Permalink"></p>' .
              '</form>' .
              '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">' .
                '<div class="tablenav top">' .
                  '<div class="alignleft actions bulkactions">' .
                    '<label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>' .
                    '<select name="action" id="bulk-action-selector-top">' .
                      '<option value="-1">' . __( "Bulk Actions", "custom-permalinks" ) . '</option>' .
                      '<option value="delete">' . __( "Delete Permalinks", "custom-permalinks" ) . '</option>' .
                    '</select>' .
                    '<input type="submit" id="doaction" class="button action" value="Apply">' .
                  '</div>';

    $table          = get_option( 'custom_permalink_table' );
    $countTags      = count( $table );
    $paginationHTML = '';
    if ( isset( $table ) && is_array( $table ) && 0 < $countTags ) {

      $filtered = array();
      if ( '' != $searchValue ) {
        foreach ( $table as $key => $value ) {
          if ( preg_match( '/' . $searchValue . '/', $key) ) {
            $filtered[$key] = $value;
          }
        }
        $table = $filtered;
        $countTags = count( $table );
      }

      $html .= '<h2 class="screen-reader-text">Custom Permalink navigation</h2>';

      $totalPages = ceil( $countTags / 20 );
      if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
        && 0 < $_GET['paged'] ) {
        $paginationHTML = $this->customPager(
          $countTags, $_GET['paged'], $totalPages
        );
        if ( $_GET['paged'] > $totalPages ) {
          $redirectUri = explode(
            '&paged=' . $_GET['paged'] . '', $_SERVER['REQUEST_URI']
          );
          header( 'Location: ' . $redirectUri[0], 301 );
          exit(0);
        }
      } elseif ( ! isset( $_GET['paged'] ) ) {
        $paginationHTML = $this->customPager( $countTags, 1, $totalPages );
      }

      $html .= $paginationHTML;
    }
    $tableNavigation = $this->tableNavCategory();

    $html .= '</div>' .
              '<table class="wp-list-table widefat fixed striped posts">' .
              '<thead>' . $tableNavigation . '</thead>' .
              '<tbody>';

    if ( $table && is_array( $table ) && 0 < $countTags ) {
      uasort( $table, array( 'Custom_Permalinks_Admin', 'sort_array' ) );
      $loopCount = -1;
      foreach ( $table as $permalink => $info ) {
        $loopCount++;
        if ( $loopCount < $pagerOffset ) {
          continue;
        }

        if ( $loopCount >= $pageLimit ) {
          break;
        }

        $type = 'category';
        if ( 'tag' == $info['kind'] ) {
          $type = 'post_tag';
        }

        $term  = get_term( $info['id'], $type );
        $html .= '<tr valign="top">' .
                    '<th scope="row" class="check-column">' .
                      '<input type="checkbox" name="permalink[]" value="' . $info['id'] . '" />' .
                    '</th>' .
                    '<td><strong>' .
                      '<a class="row-title" href="edit-tags.php?action=edit&taxonomy=' . $type . '&tag_ID=' . $info['id'] . ' ">' . $term->name . '</a>' .
                    '</strong></td>' .
                    '<td>' . ucwords( $info['kind'] ) . '</td>' .
                    '<td>' .
                      '<a href="/' . $permalink . '" target="_blank" title="' . __( "Visit " . $term->name, "custom-permalinks" ) . '">/' . $permalink . '</a>' .
                    '</td>' .
                  '</tr>';
      }
    } else {
      $html .= '<tr class="no-items">' .
                  '<td class="colspanchange" colspan="10">' . __( "No permalinks found.", "custom-permalinks" ) . '</td>' .
                '</tr>';
    }
    $html .= '</tbody>' .
              '<tfoot>' . $tableNavigation . '</tfoot>' .
              '</table>';

    $html .= '<div class="tablenav bottom">' .
                '<div class="alignleft actions bulkactions">' .
                  '<label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>' .
                  '<select name="action2" id="bulk-action-selector-bottom">' .
                    '<option value="-1">' . __( "Bulk Actions", "custom-permalinks" ) . '</option>' .
                    '<option value="delete">' . __( "Delete Permalinks", "custom-permalinks" ) . '</option>' .
                  '</select>' .
                  '<input type="submit" id="doaction2" class="button action" value="Apply">' .
                '</div>' .
                $paginationHTML .
              '</div>' .
              '</form></div>';
    echo $html;

    add_filter( 'admin_footer_text', array( $this, 'adminFooterText' ), 1 );
  }

  /**
   * Sort the terms array in desc order using term id.
   *
   * @since 1.2.0
   * @access private
   *
   * @return int
   */
  private function sort_array( $a, $b ) {
    return $b['id'] - $a['id'];
  }

  /**
   * Return the Navigation row HTML same as Default Posts page for Category.
   *
   * @since 1.2.0
   * @access private
   *
   * @return string table row according to the provided params.
   */
  private function tableNavCategory() {
    $nav = '<tr>' .
              '<td id="cb" class="manage-column column-cb check-column">' .
                '<label class="screen-reader-text" for="cb-select-all-1">Select All</label>' .
                '<input id="cb-select-all-1" type="checkbox">' .
              '</td>' .
              '<th scope="col" id="title" class="manage-column column-title column-primary">' . __( "Title", "custom-permalinks" ) . '</th>' .
              '<th scope="col">' . __( "Type", "custom-permalinks" ) . '</th>' .
              '<th scope="col">' . __( "Permalink", "custom-permalinks" )  . '</th>' .
            '</tr>';

    return $nav;
  }

  /**
   * Return the Pager HTML.
   *
   * @since 1.2.0
   * @access private
   *
   * @param int $totalPermalinks No. of total results found.
   * @param int $currentPagerValue Optional. Current Page. 1.
   * @param int $totalPager Optional. Total no. of pages. 0.
   *
   * @return string Pagination HTML if pager exist.
   */
  private function customPager( $totalPermalinks, $currentPagerValue = 1, $totalPager = 0 ) {

    if ( 0 == $totalPager ) {
      return;
    }

    if ( 1 == $totalPager ) {
      $paginationHTML = '<div class="tablenav-pages one-page">' .
                          '<span class="displaying-num">' .
                            $totalPermalinks . ' items' .
                          '</span>' .
                        '</div>';

      return $paginationHTML;
    }

    $removePagerUri = explode(
      '&paged=' . $currentPagerValue . '', $_SERVER['REQUEST_URI']
    );
    $paginationHTML = '<div class="tablenav-pages">' .
                          '<span class="displaying-num">' .
                            $totalPermalinks . ' items' .
                          '</span>' .
                          '<span class="pagination-links">';

    if ( 1 == $currentPagerValue ) {
      $paginationHTML .= '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo; </span>' .
                          '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo; </span>';
    } else {
      $prevPage = $currentPagerValue - 1;
      if ( 1 == $prevPage ) {
        $paginationHTML .= '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
      } else {
        $paginationHTML .= ' <a href="' . $removePagerUri[0] . '&paged=1" title="First page" class="first-page">' .
                             '<span class="screen-reader-text">First page</span>' .
                             '<span aria-hidden="true">&laquo;</span>' .
                           '</a> ';
      }
      $paginationHTML .= ' <a href="' . $removePagerUri[0] . '&paged=' . $prevPage . '" title="Previous page" class="prev-page">' .
                            '<span class="screen-reader-text">Previous page</span>' .
                            '<span aria-hidden="true">&lsaquo;</span>' .
                         '</a> ';
    }

    $paginationHTML .= '<span class="paging-input">' .
                          '<label for="current-page-selector" class="screen-reader-text">Current Page</label>' .
                          '<input class="current-page" id="current-page-selector" type="text" name="paged" value="' . $currentPagerValue . '" size="1" aria-describedby="table-paging" />' .
                          '<span class="tablenav-paging-text"> of <span class="total-pages">' . $totalPager . ' </span> </span>' .
                       '</span>';

    if ( $currentPagerValue == $totalPager ) {
      $paginationHTML .= '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo; </span>' .
                          '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo; </span>';
    } else {
      $nextPage = $currentPagerValue + 1;
      $paginationHTML .= ' <a href="' . $removePagerUri[0] . '&paged=' . $nextPage . '" title="Next page" class="next-page">' .
                            '<span class="screen-reader-text">Next page</span>' .
                            '<span aria-hidden="true">&rsaquo;</span>' .
                         '</a> ';
      if ( $totalPager == $nextPage) {
        $paginationHTML .= '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
      } else {
        $paginationHTML .= ' <a href="' . $removePagerUri[0] . '&paged=' . $totalPager . '" title="Last page" class="last-page">' .
                              '<span class="screen-reader-text">Last page</span>' .
                              '<span aria-hidden="true">&raquo;</span>' .
                           '</a> ';
      }
    }
    $paginationHTML .= '</span></div>';

    return $paginationHTML;
  }

  /**
   * Add About Plugins Page.
   *
   * @since 1.2.11
   * @access public
   */
  public function aboutPlugin() {
    require_once(
      CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-about.php'
    );
    new CustomPermalinksAbout();
    add_filter( 'admin_footer_text', array( $this, 'adminFooterText' ), 1 );
  }

  /**
   * Add Plugin Support and Follow Message in the footer of Admin Pages.
   *
   * @since 1.2.11
   * @access public
   *
   * @return string Shows version, website link and twitter.
   */
  public function adminFooterText() {
    $footerText = sprintf(
      __( 'Custom Permalinks version %s by <a href="%s" title="Sami Ahmed Siddiqui Company Website" target="_blank">Sami Ahmed Siddiqui</a> - <a href="%s" title="Support forums" target="_blank">Support forums</a> - Follow on Twitter: <a href="%s" title="Follow Sami Ahmed Siddiqui on Twitter" target="_blank">Sami Ahmed Siddiqui</a>', 'custom-permalinks' ),
      CUSTOM_PERMALINKS_PLUGIN_VERSION, 'https://www.yasglobal.com/',
      'https://wordpress.org/support/plugin/custom-permalinks',
      'https://twitter.com/samisiddiqui91'
    );

    return $footerText;
  }

  /**
   * Add About and Premium Settings Page Link on the Plugin Page
   * under the Plugin Name.
   *
   * @since 1.2.11
   * @access public
   *
   * @param array $links Contains the Plugin Basic Link (Activate/Deactivate/Delete).
   *
   * @return array Plugin Basic Links and added some custome link for Settings,
   *   Contact, and About.
   */
  public function settingsLink( $links ) {
    $about = sprintf(
      __( '<a href="%s" title="About">About</a>', 'custom-permalinks' ),
      'admin.php?page=cp-about-plugins'
    );
    $premiumSupport = sprintf(
      __( '<a href="%s" title="Premium Support" target="_blank">Premium Support</a>', 'custom-permalinks' ),
      'https://www.custompermalinks.com/#pricing-section'
    );
    $contact = sprintf(
      __( '<a href="%s" title="Contact" target="_blank">Contact</a>', 'custom-permalinks' ),
      'https://www.custompermalinks.com/contact-us/'
    );
    array_unshift( $links, $contact );
    array_unshift( $links, $premiumSupport );
    array_unshift( $links, $about );

    return $links;
  }

  /**
   * Add Privacy Policy about the Plugin.
   *
   * @since 1.2.23
   * @access public
   */
  public function privacyPolicy() {
    if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
      return;
    }

    $content = sprintf(
      __( 'This plugin doesn\'t collect/store any user related information.
      To have any kind of further query please feel free to
      <a href="%s" target="_blank">contact us</a>.',
      'custom-permalinks' ),
      'https://www.custompermalinks.com/contact-us/'
    );

    wp_add_privacy_policy_content(
      'Custom Permalinks',
      wp_kses_post( wpautop( $content, false ) )
    );
  }
}
