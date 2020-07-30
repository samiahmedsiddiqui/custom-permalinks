<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_Taxonomies
{

    /**
     * Call Taxonomy Permalinks Function.
     */
    function __construct()
    {
        $this->taxonomies_permalinks();
    }

    /**
     * Return the Navigation row HTML same as Default Posts page for Category.
     *
     * @since 1.2.0
     * @access private
     *
     * @return string table row according to the provided params.
     */
    private function taxonomy_nav()
    {
        $navigation = '<tr>' .
                        '<td id="cb" class="manage-column column-cb check-column">' .
                          '<label class="screen-reader-text" for="cb-select-all-1">Select All</label>' .
                          '<input id="cb-select-all-1" type="checkbox">' .
                        '</td>' .
                        '<th scope="col" id="title" class="manage-column column-title column-primary">' .
                            __( "Title", "custom-permalinks" ) .
                        '</th>' .
                        '<th scope="col">' . __( "Type", "custom-permalinks" ) . '</th>' .
                        '<th scope="col">' . __( "Permalink", "custom-permalinks" )  . '</th>' .
                      '</tr>';

        return $navigation;
    }

    /**
     * Sort the terms array in desc order using term id.
     *
     * @since 1.2.0
     * @access public
     *
     * @return int
     */
    public function sort_array( $comp1, $comp2 )
    {
        return $comp2['id'] - $comp1['id'];
    }

    /**
     * Shows all the Permalinks created by using this Plugin with Pager and
     * Search Functionality of Category/Tags.
     *
     * @since 1.2.0
     * @access private
     */
    private function taxonomies_permalinks()
    {
        $page_html = '';

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
                        $loopCount += 1;
                    }
                }

                update_option( 'custom_permalink_table', $data );
            }
        }
        $page_html .= '<div class="wrap">' .
                        '<h1 class="wp-heading-inline">' .
                            __( 'Taxonomies Permalinks', 'custom-permalinks' ) .
                        '</h1>';

        $search_value = '';
        if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
            $search_value = ltrim( htmlspecialchars( $_GET['s'] ), '/' );
            $page_html   .= '<span class="subtitle">Search results for "' . $search_value . '"</span>';
        }
        $pager_offset = '0';
        $page_limit   = 20;
        if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
            && 1 < $_GET['paged']
        ) {
            $pager_offset = 20 * ( $_GET['paged'] - 1 );
            $page_limit   = $pager_offset + 20;
        }
        $page_html .= '<form action="' . $_SERVER["REQUEST_URI"] . '" method="get">' .
                        '<p class="search-box">' .
                        '<input type="hidden" name="page" value="cp-category-permalinks" />' .
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

        $table           = get_option( 'custom_permalink_table' );
        $count_tags      = 0;
        $pagination_html = '';
        if ( isset( $table ) && is_array( $table ) ) {
            $count_tags = count( $table );
        }
        if ( 0 < $count_tags ) {
            include_once CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-pager.php';

            $cp_pager = new Custom_Permalinks_Pager();
            $filtered = array();
            if ( '' != $search_value ) {
                foreach ( $table as $key => $value ) {
                    if ( preg_match( '/' . $search_value . '/', $key) ) {
                        $filtered[$key] = $value;
                    }
                }
                $table      = $filtered;
                $count_tags = count( $table );
            }

            $page_html .= '<h2 class="screen-reader-text">Custom Permalink navigation</h2>';

            $total_pages = ceil( $count_tags / 20 );
            if ( isset( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
                && 0 < $_GET['paged']
            ) {
                $pagination_html = $cp_pager->get_pagination(
                    $count_tags, $_GET['paged'], $total_pages
                );
                if ( $_GET['paged'] > $total_pages ) {
                    $redirect_uri = explode( '&paged=' . $_GET['paged'] . '',
                        $_SERVER['REQUEST_URI']
                    );

                    header( 'Location: ' . $redirect_uri[0], 301 );
                    exit(0);
                }
            } elseif ( ! isset( $_GET['paged'] ) ) {
                $pagination_html = $cp_pager->get_pagination(  $count_tags, 1,
                    $total_pages
                );
            }

            $page_html .= $pagination_html;
        }
        $table_navigation = $this->taxonomy_nav();

        $page_html .= '</div>' .
                      '<table class="wp-list-table widefat fixed striped posts">' .
                      '<thead>' . $table_navigation . '</thead>' .
                      '<tbody>';

        if ( $table && is_array( $table ) && 0 < $count_tags ) {
            uasort( $table, array( $this, 'sort_array' ) );
            $loopCount = -1;
            foreach ( $table as $permalink => $info ) {
                $loopCount += 1;
                if ( $loopCount < $pager_offset ) {
                    continue;
                }

                if ( $loopCount >= $page_limit ) {
                    break;
                }

                $type = 'category';
                if ( 'tag' == $info['kind'] ) {
                    $type = 'post_tag';
                }

                $term       = get_term( $info['id'], $type );
                $page_html .= '<tr valign="top">' .
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
            $page_html .= '<tr class="no-items">' .
                            '<td class="colspanchange" colspan="4">' . __( "No permalinks found.", "custom-permalinks" ) . '</td>' .
                          '</tr>';
        }
        $page_html .= '</tbody>' .
                      '<tfoot>' . $table_navigation . '</tfoot>' .
                      '</table>';

        $page_html .= '<div class="tablenav bottom">' .
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

        echo $page_html;
    }
}
