<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_PostTypes {

  /**
  * Call Post Permalinks Function.
  */
  function __construct() {
    $this->post_permalinks();
  }

  /**
   * Return the Navigation row HTML same as Default Posts page for PostTypes.
   *
   * @since 1.2.0
   * @access private
   *
   * @param string $order_by_class Class either asc or desc.
   * @param string $order_by set orderby for sorting.
   * @param string $search_permalink Permalink which has been searched or an empty string.
   *
   * @return string table row according to the provided params.
   */
  private function postNav( $order_by_class, $order_by, $search_permalink ) {
    $post_nav = '<tr>' .
                  '<td id="cb" class="manage-column column-cb check-column">' .
                    '<label class="screen-reader-text" for="cb-select-all-1">Select All</label>' .
                    '<input id="cb-select-all-1" type="checkbox">' .
                  '</td>' .
                  '<th scope="col" id="title" class="manage-column column-title column-primary sortable ' . $order_by_class . '">' .
                    '<a href="/wp-admin/admin.php?page=cp-post-permalinks&amp;orderby=title&amp;order=' . $order_by . $search_permalink . '">' .
                      '<span>' . __( "Title", "custom-permalinks" ) . '</span>' .
                      '<span class="sorting-indicator"></span>' .
                    '</a>' .
                  '</th>' .
                  '<th scope="col">' . __( "Type", "custom-permalinks" ) . '</th>' .
                  '<th scope="col">' . __( "Permalink", "custom-permalinks" ) . '</th>' .
                '</tr>';

    return $post_nav;
  }

  /**
   * Shows all the Permalinks created by using this Plugin with Pager and
   * Search Functionality of Posts/Pages.
   *
   * @since 1.2.0
   * @access private
   */
  private function post_permalinks() {
    global $wpdb;

    $error            = '';
    $filter_options   = '';
    $filter_permalink = '';
    $post_html        = '';
    $search_permalink = '';

    // Handle Bulk Operations
    if ( ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] )
      || ( isset( $_POST['action2'] ) && 'delete' === $_POST['action2'] )
    ) {
      if ( isset( $_POST['permalink'] ) && ! empty( $_POST['permalink'] ) ) {
        $post_ids = implode( ',', $_POST['permalink'] );
        if ( preg_match( '/^\d+(?:,\d+)*$/', $post_ids ) ) {
          $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN ($post_ids) AND meta_key = 'custom_permalink'" );
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
    $post_html .= '<div class="wrap">' .
                    '<h1 class="wp-heading-inline">' . __( 'PostTypes Permalinks', 'custom-permalinks' ) . '</h1>' .
                    $error;

    $search_value = '';
    if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
      $filter_permalink = 'AND pm.meta_value LIKE "%' . $_GET['s'] . '%"';
      $search_permalink = '&s=' . $_GET['s'] . '';
      $search_value     = ltrim( htmlspecialchars( $_GET['s'] ), '/' );
      $post_html       .= '<span class="subtitle">Search results for "' . $search_value . '"</span>';
    }
    $page_limit = 'LIMIT 0, 20';
    if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
      && 1 < $_GET['paged']
    ) {
      $pager      = 20 * ( $_GET['paged'] - 1 );
      $page_limit = 'LIMIT ' . $pager . ', 20';
    }
    $sorting_by     = 'ORDER By p.ID DESC';
    $order_by       = 'asc';
    $order_by_class = 'desc';
    if ( isset( $_GET['orderby'] ) && 'title' == $_GET['orderby'] ) {
      $filter_options .= '<input type="hidden" name="orderby" value="title" />';
      if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] ) {
        $sorting_by      = 'ORDER By p.post_title DESC';
        $order_by        = 'asc';
        $order_by_class  = 'desc';
        $filter_options .= '<input type="hidden" name="order" value="desc" />';
      } else {
        $sorting_by      = 'ORDER By p.post_title';
        $order_by        = 'desc';
        $order_by_class  = 'asc';
        $filter_options .= '<input type="hidden" name="order" value="asc" />';
      }
    }
    $count_query = "SELECT COUNT(p.ID) AS total_permalinks FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != '' " . $filter_permalink . "";
    $count_posts = $wpdb->get_row( $count_query );

    $post_html .= '<form action="' . $_SERVER["REQUEST_URI"] . '" method="get">' .
                    '<p class="search-box">' .
                    '<input type="hidden" name="page" value="cp-post-permalinks" />' .
                    $filter_options .
                    '<label class="screen-reader-text" for="custom-permalink-search-input">Search Custom Permalink:</label>' .
                    '<input type="search" id="custom-permalink-search-input" name="s" value="' . $search_value . '">' .
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

    $posts           = 0;
    $pagination_html = '';
    if ( isset( $count_posts->total_permalinks )
      && 0 < $count_posts->total_permalinks
    ) {
      require_once(
        CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-pager.php'
      );
      $cp_pager = new Custom_Permalinks_Pager();

      $post_html .= '<h2 class="screen-reader-text">Custom Permalink navigation</h2>';

      $query = "SELECT p.ID, p.post_title, p.post_type, pm.meta_value FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != '' " . $filter_permalink . " " . $sorting_by . " " . $page_limit . "";
      $posts = $wpdb->get_results( $query );

      $total_pages = ceil( $count_posts->total_permalinks / 20 );
      if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
        && 0 < $_GET['paged']
      ) {
        $pagination_html = $cp_pager->get_pagination(
          $count_posts->total_permalinks, $_GET['paged'], $total_pages
        );
        if ( $_GET['paged'] > $total_pages ) {
          $redirect_uri = explode(
            '&paged=' . $_GET['paged'] . '', $_SERVER['REQUEST_URI']
          );
          header( 'Location: ' . $redirect_uri[0], 301 );
          exit(0);
        }
      } elseif ( ! isset( $_GET['paged'] ) ) {
        $pagination_html = $cp_pager->get_pagination(
          $count_posts->total_permalinks, 1, $total_pages
        );
      }

      $post_html .= $pagination_html;
    }
    $table_navigation = $this->postNav(
      $order_by_class, $order_by, $search_permalink
    );

    $post_html .= '</div>';
    $post_html .= '<table class="wp-list-table widefat fixed striped posts">' .
                    '<thead>' . $table_navigation . '</thead>' .
                    '<tbody>';
    if ( 0 != $posts && ! empty( $posts ) ) {
      foreach ( $posts as $post ) {
        $post_html .= '<tr valign="top">' .
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
      $post_html .= '<tr class="no-items">' .
                      '<td class="colspanchange" colspan="4">' . __( "No permalinks found.", "custom-permalinks" ) . '</td>' .
                    '</tr>';
    }
    $post_html .= '</tbody>' .
                  '<tfoot>' . $table_navigation . '</tfoot>' .
                  '</table>';

    $post_html .= '<div class="tablenav bottom">' .
                    '<div class="alignleft actions bulkactions">' .
                      '<label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>' .
                      '<select name="action2" id="bulk-action-selector-bottom">' .
                        '<option value="-1">' . __( "Bulk Actions", "custom-permalinks" ) . '</option>' .
                        '<option value="delete">' . __( "Delete Permalinks", "custom-permalinks" ) . '</option>' .
                      '</select>' .
                      '<input type="submit" id="doaction2" class="button action" value="Apply">' .
                    '</div>' .
                    $pagination_html .
                  '</div>' .
                  '</form></div>';

    echo $post_html;
  }
}
