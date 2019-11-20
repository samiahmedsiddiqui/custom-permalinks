<?php
/**
 * @package CustomPermalinks
 */

class CustomPermalinksPager {

  /**
   * Return the Pager HTML.
   *
   * @since 1.2.0
   * @access public
   *
   * @param int $totalPermalinks No. of total results found.
   * @param int $currentPagerValue Optional. Current Page. 1.
   * @param int $totalPager Optional. Total no. of pages. 0.
   *
   * @return string Pagination HTML if pager exist.
   */
  public function getPagination( $totalPermalinks, $currentPagerValue = 1, $totalPager = 0 ) {

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
}
