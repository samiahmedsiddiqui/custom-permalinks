<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_PostTypes
{

    /**
     * Call Post Permalinks Function.
     */
    function __construct()
    {
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
    private function post_nav( $order_by_class, $order_by, $search_permalink )
    {
        $admin_url = get_admin_url();
        $page_url  = $admin_url . 'admin.php?page=cp-post-permalinks';
        $title_url = $page_url . '&amp;orderby=title&amp;order=' . $order_by;
        $user_id   = get_current_user_id();

        if ( $search_permalink ) {
            $title_url = $title_url . $search_permalink;
            $title_url = wp_nonce_url( $title_url,
                'custom-permalinks-post_' . $user_id,
                '_custom_permalinks_post_nonce'
            );
        }

        $post_nav = '<tr>' .
                      '<td id="cb" class="manage-column column-cb check-column">' .
                        '<label class="screen-reader-text" for="cb-select-all-1">' .
                            __( 'Select All', 'custom-permalinks' ) .
                        '</label>' .
                        '<input id="cb-select-all-1" type="checkbox">' .
                      '</td>' .
                      '<th scope="col" id="title" class="manage-column column-title column-primary sortable ' . $order_by_class . '">' .
                        '<a href="' . $title_url . '">' .
                          '<span>' .
                              __( 'Title', 'custom-permalinks' ) .
                          '</span>' .
                          '<span class="sorting-indicator"></span>' .
                        '</a>' .
                      '</th>' .
                      '<th scope="col">' .
                          __( 'Type', 'custom-permalinks' ) .
                      '</th>' .
                      '<th scope="col">' .
                          __( 'Permalink', 'custom-permalinks' ) .
                      '</th>' .
                    '</tr>';

        return $post_nav;
    }

    /**
     * Shows all the Permalinks created by using this Plugin with Pager and Search
     * Functionality of Posts/Pages.
     *
     * @since 1.2.0
     * @access private
     */
    private function post_permalinks()
    {
        global $wpdb;

        $error            = '';
        $filter_options   = '';
        $filter_permalink = '';
        $home_url         = home_url();
        $post_html        = '';
        $post_action      = filter_input( INPUT_POST, 'action' );
        $post_action2     = filter_input( INPUT_POST, 'action2' );
        $post_permalinks  = filter_input( INPUT_POST, 'permalink', FILTER_DEFAULT,
            FILTER_REQUIRE_ARRAY
        );
        $request_uri      = '';
        $search_permalink = '';
        $site_url         = site_url();
        $user_id          = get_current_user_id();

        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $request_uri = $_SERVER['REQUEST_URI'];
        }

        // Handle Bulk Operations
        if ( ( 'delete' === $post_action || 'delete' === $post_action2 )
            && check_admin_referer( 'custom-permalinks-post_' . $user_id,
                '_custom_permalinks_post_nonce'
            )
        ) {
          if ( ! empty( $post_permalinks ) ) {
              $post_ids = $post_permalinks;
              if ( is_array( $post_ids ) && 0 < count( $post_ids ) ) {
                  foreach ( $post_ids as $post_id ) {
                      if ( is_numeric( $post_id ) ) {
                          delete_metadata( 'post', $post_id, 'custom_permalink' );
                      }
                  }
              } else {
                  $error = '<div id="message" class="error">' .
                              '<p>' .
                                  __( 'Please select permalinks which you like to be deleted.',
                                      'custom-permalinks'
                                  ) .
                              '</p>' .
                            '</div>';
              }
          } else {
              $error = '<div id="message" class="error">' .
                          '<p>' .
                              __( 'There is some error to proceed your request. Please retry with your request or contact to the plugin author.',
                                  'custom-permalinks'
                              ) .
                          '</p>' .
                        '</div>';
          }
        }
        $post_html .= '<div class="wrap">' .
                        '<h1 class="wp-heading-inline">' .
                            __( 'PostTypes Permalinks', 'custom-permalinks' ) .
                        '</h1>' .
                        $error;

        $get_paged      = filter_input( INPUT_GET, 'paged' );
        $get_order      = filter_input( INPUT_GET, 'order' );
        $get_order_by   = filter_input( INPUT_GET, 'orderby' );
        $order_by       = 'asc';
        $order_by_class = 'desc';
        $page_limit     = 'LIMIT 0, 20';
        $search_input   = filter_input( INPUT_GET, 's' );
        $search_value   = '';
        $sorting_by     = 'ORDER By p.ID DESC';

        if ( $search_input
            && check_admin_referer( 'custom-permalinks-post_' . $user_id,
                '_custom_permalinks_post_nonce'
            )
        ) {
            $filter_permalink = 'AND pm.meta_value LIKE "%' . $search_input . '%"';
            $search_permalink = '&s=' . $search_input . '';
            $search_value     = ltrim( htmlspecialchars( $search_input ), '/' );
            $post_html       .= '<span class="subtitle">Search results for "' . $search_value . '"</span>';
        }

        if ( is_numeric( $get_paged ) && 1 < $get_paged ) {
            $pager      = 20 * ( $get_paged - 1 );
            $page_limit = 'LIMIT ' . $pager . ', 20';
        }

        if ( 'title' === $get_order_by ) {
            $filter_options .= '<input type="hidden" name="orderby" value="title" />';
            if ( 'desc' === $get_order ) {
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
        $post_nonce  = wp_nonce_field( 'custom-permalinks-post_' . $user_id,
            '_custom_permalinks_post_nonce', true, false
        );

        $post_html .= '<form action="' . $site_url . $request_uri . '" method="get">' .
                        '<p class="search-box">' .
                        '<input type="hidden" name="page" value="cp-post-permalinks" />' .
                        $post_nonce .
                        $filter_options .
                        '<label class="screen-reader-text" for="custom-permalink-search-input">' .
                            __( 'Search Custom Permalink:', 'custom-permalinks' ) .
                        '</label>' .
                        '<input type="search" id="custom-permalink-search-input" name="s" value="' . $search_value . '">' .
                        '<input type="submit" id="search-submit" class="button" value="' . __( "Search Permalink", "custom-permalinks" ) . '"></p>' .
                      '</form>' .
                      '<form action="' . $site_url . $request_uri . '" method="post">' .
                        '<div class="tablenav top">' .
                          '<div class="alignleft actions bulkactions">' .
                            '<label for="bulk-action-selector-top" class="screen-reader-text">' .
                                __( 'Select bulk action', 'custom-permalinks' ) .
                            '</label>' .
                            $post_nonce .
                            '<select name="action" id="bulk-action-selector-top">' .
                              '<option value="-1">' .
                                  __( 'Bulk Actions', 'custom-permalinks' ) .
                              '</option>' .
                              '<option value="delete">' .
                                  __( 'Delete Permalinks', 'custom-permalinks' ) .
                              '</option>' .
                            '</select>' .
                            '<input type="submit" id="doaction" class="button action" value="' . __( "Apply", "custom-permalinks" ) . '">' .
                          '</div>';

        $posts           = 0;
        $pagination_html = '';
        if ( isset( $count_posts->total_permalinks )
            && 0 < $count_posts->total_permalinks
        ) {
            include_once CUSTOM_PERMALINKS_PATH . 'admin/class-custom-permalinks-pager.php';

            $cp_pager   = new Custom_Permalinks_Pager();
            $post_html .= '<h2 class="screen-reader-text">' .
                            __( 'Custom Permalink navigation', 'custom-permalinks' ) .
                          '</h2>';

            $query = "SELECT p.ID, p.post_title, p.post_type, pm.meta_value FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id) WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != '' " . $filter_permalink . " " . $sorting_by . " " . $page_limit . "";
            $posts = $wpdb->get_results( $query );

            $total_pages = ceil( $count_posts->total_permalinks / 20 );
            if ( is_numeric( $get_paged ) && 0 < $get_paged ) {
                $pagination_html = $cp_pager->get_pagination(
                    $count_posts->total_permalinks, $get_paged, $total_pages
                );
                if ( $get_paged > $total_pages ) {
                    $redirect_uri = explode( '&paged=' . $get_paged . '',
                        $request_uri
                    );

                    wp_safe_redirect( $redirect_uri[0], 301 );
                    exit;
                }
            } elseif ( ! $get_paged ) {
                $pagination_html = $cp_pager->get_pagination(
                    $count_posts->total_permalinks, 1, $total_pages
                );
            }

            $post_html .= $pagination_html;
        }
        $table_navigation = $this->post_nav( $order_by_class, $order_by,
            $search_permalink
        );

        $post_html .= '</div>';
        $post_html .= '<table class="wp-list-table widefat fixed striped posts">' .
                        '<thead>' . $table_navigation . '</thead>' .
                        '<tbody>';
        if ( 0 != $posts && ! empty( $posts ) ) {
            $cp_frontend = new Custom_Permalinks_Frontend();
            if ( class_exists( 'SitePress' ) ) {
                $wpml_lang_format = apply_filters( 'wpml_setting', 0,
                    'language_negotiation_type'
                );

                if ( 1 === intval( $wpml_lang_format ) ) {
                    $home_url = $site_url;
                }
            }

            foreach ( $posts as $post ) {
                $custom_permalink = '/' . $post->meta_value;
                $post_type        = 'post';
                if ( isset( $post->post_type ) ) {
                    $post_type = $post->post_type;
                }

                $language_code = apply_filters( 'wpml_element_language_code', null,
                    array(
                        'element_id'   => $post->ID,
                        'element_type' => $post_type
                    )
                );

                $permalink = $cp_frontend->wpml_permalink_filter( $custom_permalink,
                    $language_code
                );
                $permalink = $cp_frontend->remove_double_slash( $permalink );
                $perm_text = str_replace( $home_url, '', $permalink );

                $post_html .= '<tr valign="top">' .
                                '<th scope="row" class="check-column">' .
                                  '<input type="checkbox" name="permalink[]" value="' . $post->ID . '" />' .
                                '</th>' .
                                '<td>' .
                                  '<strong>' .
                                    '<a class="row-title" href="' . $site_url . '/wp-admin/post.php?action=edit&post=' . $post->ID . '">' .
                                      $post->post_title .
                                    '</a>' .
                                  '</strong>' .
                                '</td>' .
                                '<td>' . ucwords( $post->post_type ) . '</td>' .
                                '<td>' .
                                  '<a href="' . $permalink . '" target="_blank" title="' . __( "Visit " . $post->post_title, "custom-permalinks" ) . '">' .
                                    $perm_text .
                                  '</a>' .
                                '</td>' .
                              '</tr>';
            }
        } else {
            $post_html .= '<tr class="no-items">' .
                            '<td class="colspanchange" colspan="4">' .
                                __( 'No permalinks found.', 'custom-permalinks' ) .
                            '</td>' .
                          '</tr>';
        }
        $post_html .= '</tbody>' .
                      '<tfoot>' . $table_navigation . '</tfoot>' .
                      '</table>';

        $post_html .= '<div class="tablenav bottom">' .
                        '<div class="alignleft actions bulkactions">' .
                          '<label for="bulk-action-selector-bottom" class="screen-reader-text">' .
                              __( 'Select bulk action', 'custom-permalinks' ) .
                          '</label>' .
                          '<select name="action2" id="bulk-action-selector-bottom">' .
                            '<option value="-1">' .
                                __( 'Bulk Actions', 'custom-permalinks' ) .
                            '</option>' .
                            '<option value="delete">' .
                                __( 'Delete Permalinks', 'custom-permalinks' ) .
                            '</option>' .
                          '</select>' .
                          '<input type="submit" id="doaction2" class="button action" value="' . __( "Apply", "custom-permalinks" ) . '">' .
                        '</div>' .
                        $pagination_html .
                      '</div>' .
                      '</form></div>';

        echo $post_html;
    }
}
