<?php
/**
 * @package CustomPermalinks
 */

class CustomPermalinksPostTypes {

  /**
  * Call Taxonomy Permalinks Function.
  */
  function __construct() {
    $this->postPermalinks();
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
  private function postNav( $orderByClass, $orderBy, $searchPermalink ) {
    $postsNav = '<tr>' .
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

    return $postsNav;
  }

  /**
   * Shows all the Permalinks created by using this Plugin with Pager and
   * Search Functionality of Posts/Pages.
   *
   * @since 1.2.0
   * @access private
   */
  private function postPermalinks() {
    global $wpdb;

    $error           = '';
    $filterOptions   = '';
    $filterPermalink = '';
    $postshtml       = '';
    $searchPermalink = '';

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
    $postshtml .= '<div class="wrap">' .
                    '<h1 class="wp-heading-inline">' . __( 'PostTypes Permalinks', 'custom-permalinks' ) . '</h1>' .
                    $error;

    $searchValue = '';
    if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
      $filterPermalink = 'AND pm.meta_value LIKE "%' . $_GET['s'] . '%"';
      $searchPermalink = '&s=' . $_GET['s'] . '';
      $searchValue     = ltrim( htmlspecialchars( $_GET['s'] ), '/' );
      $postshtml      .= '<span class="subtitle">Search results for "' . $searchValue . '"</span>';
    }
    $pageLimit = 'LIMIT 0, 20';
    if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
      && 1 < $_GET['paged']
    ) {
      $pager     = 20 * ( $_GET['paged'] - 1 );
      $pageLimit = 'LIMIT ' . $pager . ', 20';
    }
    $sortingBy    = 'ORDER By p.ID DESC';
    $orderBy      = 'asc';
    $orderByClass = 'desc';
    if ( isset( $_GET['orderby'] ) && 'title' == $_GET['orderby'] ) {
      $filterOptions .= '<input type="hidden" name="orderby" value="title" />';
      if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] ) {
        $sortingBy      = 'ORDER By p.post_title DESC';
        $orderBy        = 'asc';
        $orderByClass   = 'desc';
        $filterOptions .= '<input type="hidden" name="order" value="desc" />';
      } else {
        $sortingBy      = 'ORDER By p.post_title';
        $orderBy        = 'desc';
        $orderByClass   = 'asc';
        $filterOptions .= '<input type="hidden" name="order" value="asc" />';
      }
    }
    $countQuery = "SELECT COUNT(p.ID) AS total_permalinks FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != '' " . $filterPermalink . "";
    $countPosts = $wpdb->get_row( $countQuery );

    $postshtml .= '<form action="' . $_SERVER["REQUEST_URI"] . '" method="get">' .
                    '<p class="search-box">' .
                    '<input type="hidden" name="page" value="cp-post-permalinks" />' .
                    $filterOptions .
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

    $posts          = 0;
    $paginationHTML = '';
    if ( isset( $countPosts->total_permalinks )
      && 0 < $countPosts->total_permalinks
    ) {
      require_once(
        CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-pager.php'
      );
      $cpPager = new CustomPermalinksPager();

      $postshtml .= '<h2 class="screen-reader-text">Custom Permalink navigation</h2>';

      $query = "SELECT p.ID, p.post_title, p.post_type, pm.meta_value FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != '' " . $filterPermalink . " " . $sortingBy . " " . $pageLimit . "";
      $posts = $wpdb->get_results( $query );

      $totalPages = ceil( $countPosts->total_permalinks / 20 );
      if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
        && 0 < $_GET['paged']
      ) {
        $paginationHTML = $cpPager->getPagination(
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
        $paginationHTML = $cpPager->getPagination(
          $countPosts->total_permalinks, 1, $totalPages
        );
      }

      $postshtml .= $paginationHTML;
    }
    $tableNavigation = $this->postNav(
      $orderByClass, $orderBy, $searchPermalink
    );

    $postshtml .= '</div>';
    $postshtml .= '<table class="wp-list-table widefat fixed striped posts">' .
                    '<thead>' . $tableNavigation . '</thead>' .
                    '<tbody>';
    if ( 0 != $posts && ! empty( $posts ) ) {
      foreach ( $posts as $post ) {
        $postshtml .= '<tr valign="top">' .
                        '<th scope="row" class="check-column">' .
                          '<input type="checkbox" name="permalink[]" value="' . $post->ID . '" />' .
                        '</th>' .
                        '<td>' .
                          '<strong>' .
                            '<a class="row-title" href="post.php?action=edit&post=' . $post->ID . '">' . $post->post_title . '</a>' .
                          '</strong>' .
                        '</td>' .
                        '<td>' . ucwords( $post->post_type ) . '</td>' .
                        '<td>' .
                          '<a href="/' . $post->meta_value . '" target="_blank" title="' . __( "Visit " . $post->post_title, "custom-permalinks" ) . '">/' .
                            urldecode( $post->meta_value ) .
                          '</a>' .
                        '</td>' .
                      '</tr>';
      }
    } else {
      $postshtml .= '<tr class="no-items">' .
                      '<td class="colspanchange" colspan="4">' . __( "No permalinks found.", "custom-permalinks" ) . '</td>' .
                    '</tr>';
    }
    $postshtml .= '</tbody>' .
                  '<tfoot>' . $tableNavigation . '</tfoot>' .
                  '</table>';

    $postshtml .= '<div class="tablenav bottom">' .
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

    echo $postshtml;
  }
}
