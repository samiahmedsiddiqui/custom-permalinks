<?php
/**
 * @package CustomPermalinks
 */

class CustomPermalinksTaxonomies {

  /**
  * Call Taxonomy Permalinks Function.
  */
  function __construct() {
    $this->taxonomiesPermalinks();
  }

  /**
   * Return the Navigation row HTML same as Default Posts page for Category.
   *
   * @since 1.2.0
   * @access private
   *
   * @return string table row according to the provided params.
   */
  private function TaxonomyNav() {
    $taxNav = '<tr>' .
                '<td id="cb" class="manage-column column-cb check-column">' .
                  '<label class="screen-reader-text" for="cb-select-all-1">Select All</label>' .
                  '<input id="cb-select-all-1" type="checkbox">' .
                '</td>' .
                '<th scope="col" id="title" class="manage-column column-title column-primary">' . __( "Title", "custom-permalinks" ) . '</th>' .
                '<th scope="col">' . __( "Type", "custom-permalinks" ) . '</th>' .
                '<th scope="col">' . __( "Permalink", "custom-permalinks" )  . '</th>' .
              '</tr>';

    return $taxNav;
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
   * Shows all the Permalinks created by using this Plugin with Pager and
   * Search Functionality of Category/Tags.
   *
   * @since 1.2.0
   * @access private
   */
  private function taxonomiesPermalinks() {
    $taxHTML = '';

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
    $taxHTML .= '<div class="wrap">' .
                  '<h1 class="wp-heading-inline">' . __( 'Taxonomies Permalinks', 'custom-permalinks' ) . '</h1>';

    $searchValue = '';
    if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
      $searchValue  = ltrim( htmlspecialchars( $_GET['s'] ), '/' );
      $taxHTML     .= '<span class="subtitle">Search results for "' . $searchValue . '"</span>';
    }
    $pagerOffset = '0';
    $pageLimit   = 20;
    if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
      && 1 < $_GET['paged']
    ) {
      $pagerOffset = 20 * ( $_GET['paged'] - 1 );
      $pageLimit   = $pagerOffset + 20;
    }
    $taxHTML .= '<form action="' . $_SERVER["REQUEST_URI"] . '" method="get">' .
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
    $countTags      = 0;
    $paginationHTML = '';
    if ( isset( $table ) && is_array( $table ) ) {
      $countTags = count( $table );
    }
    if ( 0 < $countTags ) {
      require_once(
        CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-pager.php'
      );
      $cpPager = new CustomPermalinksPager();

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

      $taxHTML .= '<h2 class="screen-reader-text">Custom Permalink navigation</h2>';

      $totalPages = ceil( $countTags / 20 );
      if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
        && 0 < $_GET['paged'] ) {
        $paginationHTML = $cpPager->getPagination(
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
        $paginationHTML = $cpPager->getPagination( $countTags, 1, $totalPages );
      }

      $taxHTML .= $paginationHTML;
    }
    $tableNavigation = $this->TaxonomyNav();

    $taxHTML .= '</div>' .
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
        $taxHTML .= '<tr valign="top">' .
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
      $taxHTML .= '<tr class="no-items">' .
                    '<td class="colspanchange" colspan="4">' . __( "No permalinks found.", "custom-permalinks" ) . '</td>' .
                  '</tr>';
    }
    $taxHTML .= '</tbody>' .
                '<tfoot>' . $tableNavigation . '</tfoot>' .
                '</table>';

    $taxHTML .= '<div class="tablenav bottom">' .
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

    echo $taxHTML;
  }
}
