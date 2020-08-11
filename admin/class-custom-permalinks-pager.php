<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_Pager
{

    /**
     * Return the Pager HTML.
     *
     * @since 1.2.0
     * @access public
     *
     * @param int $total_permalinks No. of total results found.
     * @param int $current_pager_value Optional. Current Page. 1.
     * @param int $total_pager Optional. Total no. of pages. 0.
     *
     * @return string Pagination HTML if pager exist.
     */
    public function get_pagination( $total_permalinks, $current_pager_value = 1,
        $total_pager = 0
    ) {
        if ( 0 == $total_pager ) {
            return;
        }

        if ( 1 == $total_pager ) {
            $pagination_html = '<div class="tablenav-pages one-page">' .
                                '<span class="displaying-num">' .
                                    $total_permalinks .
                                    __( 'items', 'custom-permalinks' ) .
                                '</span>' .
                               '</div>';

            return $pagination_html;
        }

        $remove_pager_uri = array();
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $remove_pager_uri = explode(
                '&paged=' . $current_pager_value . '', $_SERVER['REQUEST_URI']
            );
        }
        $pagination_html = '<div class="tablenav-pages">' .
                              '<span class="displaying-num">' .
                                  $total_permalinks .
                                  __( 'items', 'custom-permalinks' ) .
                              '</span>' .
                              '<span class="pagination-links">';

        if ( 1 == $current_pager_value ) {
            $pagination_html .= '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo; </span>' .
                                '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo; </span>';
        } else {
            $prev_page = $current_pager_value - 1;
            if ( 1 == $prev_page ) {
                $pagination_html .= '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
            } else {
                $pagination_html .= ' <a href="' . $remove_pager_uri[0] . '&paged=1" title="' . __( "First page", "custom-permalinks" ) .'" class="first-page">' .
                                      '<span class="screen-reader-text">' .
                                          __( 'First page', 'custom-permalinks' ) .
                                      '</span>' .
                                      '<span aria-hidden="true">&laquo;</span>' .
                                    '</a> ';
            }
            $pagination_html .= ' <a href="' . $remove_pager_uri[0] . '&paged=' . $prev_page . '" title="' . __( "Previous page", "custom-permalinks" ) . '" class="prev-page">' .
                                  '<span class="screen-reader-text">' .
                                      __( 'Previous page', 'custom-permalinks' ) .
                                  '</span>' .
                                  '<span aria-hidden="true">&lsaquo;</span>' .
                                '</a> ';
        }

        $pagination_html .= '<span class="paging-input">' .
                              '<label for="current-page-selector" class="screen-reader-text">' .
                                  __( 'Current Page', 'custom-permalinks' ) .
                              '</label>' .
                              '<input class="current-page" id="current-page-selector" type="text" name="paged" value="' . $current_pager_value . '" size="1" aria-describedby="table-paging" />' .
                              '<span class="tablenav-paging-text"> of <span class="total-pages">' . $total_pager . ' </span> </span>' .
                            '</span>';

        if ( $current_pager_value == $total_pager ) {
            $pagination_html .= '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo; </span>' .
                                '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo; </span>';
        } else {
            $next_page = $current_pager_value + 1;
            $pagination_html .= ' <a href="' . $remove_pager_uri[0] . '&paged=' . $next_page . '" title="' . __( "Next page", "custom-permalinks" ) . '" class="next-page">' .
                                  '<span class="screen-reader-text">' .
                                      __( 'Next page', 'custom-permalinks' ) .
                                  '</span>' .
                                  '<span aria-hidden="true">&rsaquo;</span>' .
                                '</a> ';
            if ( $total_pager == $next_page ) {
                $pagination_html .= '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
            } else {
                $pagination_html .= ' <a href="' . $remove_pager_uri[0] . '&paged=' . $total_pager . '" title="' . __( "Last page", "custom-permalinks" ) . '" class="last-page">' .
                                      '<span class="screen-reader-text">' .
                                          __( 'Last page', 'custom-permalinks' ) .
                                      '</span>' .
                                      '<span aria-hidden="true">&raquo;</span>' .
                                    '</a> ';
            }
        }
        $pagination_html .= '</span></div>';

        return $pagination_html;
    }
}
