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
                          '<label class="screen-reader-text" for="cb-select-all-1">' .
                              __( 'Select All', 'custom-permalinks' ) .
                          '</label>' .
                          '<input id="cb-select-all-1" type="checkbox">' .
                        '</td>' .
                        '<th scope="col" id="title" class="manage-column column-title column-primary">' .
                            __( 'Title', 'custom-permalinks' ) .
                        '</th>' .
                        '<th scope="col">' .
                            __( 'Type', 'custom-permalinks' ) .
                        '</th>' .
                        '<th scope="col">' .
                            __( 'Permalink', 'custom-permalinks' )  .
                        '</th>' .
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
        $home_url        = home_url();
        $page_html       = '';
        $request_uri     = '';
        $site_url        = site_url();
        $term_action     = filter_input( INPUT_POST, 'action' );
        $term_action2    = filter_input( INPUT_POST, 'action2' );
        $term_permalinks = filter_input( INPUT_POST, 'permalink',
            FILTER_DEFAULT, FILTER_REQUIRE_ARRAY
        );
        $user_id         = get_current_user_id();

        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $request_uri = $_SERVER['REQUEST_URI'];
        }

        // Handle Bulk Operations
        if ( ( 'delete' === $term_action || 'delete' === $term_action2 )
            && check_admin_referer( 'custom-permalinks-term_' . $user_id,
                '_custom_permalinks_term_nonce'
            )
        ) {
            if ( ! empty( $term_permalinks ) ) {
                $data = get_option( 'custom_permalink_table' );
                if ( isset( $data ) && is_array( $data ) ) {
                    $loopCount = 0;
                    foreach ( $data as $link => $info ) {
                        if ( in_array( $info['id'], $term_permalinks ) ) {
                            unset( $data[$link] );
                            unset( $term_permalinks[$loopCount] );
                            if ( ! is_array( $term_permalinks )
                                || empty( $term_permalinks )
                            ) {
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

        $get_paged    = filter_input( INPUT_GET, 'paged' );
        $page_limit   = 20;
        $pager_offset = '0';
        $search_input = filter_input( INPUT_GET, 's' );
        $search_value = '';
        $term_nonce = wp_nonce_field( 'custom-permalinks-term_' . $user_id,
            '_custom_permalinks_term_nonce', true, false
        );

        if ( ! empty( $search_input )
            && check_admin_referer( 'custom-permalinks-term_' . $user_id,
                '_custom_permalinks_term_nonce'
            )
        ) {
            $search_value = ltrim( htmlspecialchars( $search_input ), '/' );
            $page_html   .= '<span class="subtitle">Search results for "' . $search_value . '"</span>';
        }

        if ( is_numeric( $get_paged ) && 1 < $get_paged ) {
            $pager_offset = 20 * ( $get_paged - 1 );
            $page_limit   = $pager_offset + 20;
        }

        $page_html .= '<form action="' . $site_url . $request_uri . '" method="get">' .
                        '<p class="search-box">' .
                          '<input type="hidden" name="page" value="cp-category-permalinks" />' .
                          $term_nonce .
                          '<label class="screen-reader-text" for="custom-permalink-search-input">' .
                              __( 'Search Custom Permalink:', 'custom-permalinks' ) .
                          '</label>' .
                          '<input type="search" id="custom-permalink-search-input" name="s" value="' . $search_value . '">' .
                          '<input type="submit" id="search-submit" class="button" value="' . __( "Search Permalink", "custom-permalinks" ) . '">' .
                        '</p>' .
                      '</form>' .
                      '<form action="' . $site_url . $request_uri . '" method="post">' .
                        '<div class="tablenav top">' .
                          '<div class="alignleft actions bulkactions">' .
                            '<label for="bulk-action-selector-top" class="screen-reader-text">' .
                                __( 'Select bulk action', 'custom-permalinks' ) .
                            '</label>' .
                            $term_nonce .
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

            $page_html .= '<h2 class="screen-reader-text">' .
                              __( 'Custom Permalink navigation', 'custom-permalinks' ) .
                          '</h2>';

            $total_pages = ceil( $count_tags / 20 );
            if ( is_numeric( $get_paged ) && 0 < $get_paged ) {
                $pagination_html = $cp_pager->get_pagination(
                    $count_tags, $get_paged, $total_pages
                );
                if ( $get_paged > $total_pages ) {
                    $redirect_uri = explode( '&paged=' . $get_paged . '',
                        $request_uri
                    );

                    wp_safe_redirect( $redirect_uri[0], 301 );
                    exit;
                }
            } elseif ( ! isset( $get_paged ) ) {
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
            $cp_frontend = new Custom_Permalinks_Frontend();
            if ( class_exists( 'SitePress' ) ) {
                $wpml_lang_format = apply_filters( 'wpml_setting', 0,
                    'language_negotiation_type'
                );

                if ( 1 === intval( $wpml_lang_format ) ) {
                    $home_url = $site_url;
                }
            }

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

                $language_code = apply_filters( 'wpml_element_language_code',
                    null, array(
                        'element_id'   => $info['id'],
                        'element_type' => $type
                    )
                );

                $permalink = $cp_frontend->wpml_permalink_filter( $permalink,
                    $language_code
                );
                $permalink = $cp_frontend->remove_double_slash( $permalink );
                $perm_text = str_replace( $home_url, '', $permalink );

                $term       = get_term( $info['id'], $type );
                $page_html .= '<tr valign="top">' .
                                '<th scope="row" class="check-column">' .
                                  '<input type="checkbox" name="permalink[]" value="' . $info['id'] . '" />' .
                                '</th>' .
                                '<td><strong>' .
                                  '<a class="row-title" href="' . $site_url . '/wp-admin/edit-tags.php?action=edit&taxonomy=' . $type . '&tag_ID=' . $info['id'] . ' ">' .
                                      $term->name .
                                  '</a>' .
                                '</strong></td>' .
                                '<td>' . ucwords( $info['kind'] ) . '</td>' .
                                '<td>' .
                                  '<a href="' . $permalink . '" target="_blank" title="' . __( "Visit " . $term->name, "custom-permalinks" ) . '">' .
                                      $perm_text .
                                  '</a>' .
                                '</td>' .
                              '</tr>';
            }
        } else {
            $page_html .= '<tr class="no-items">' .
                            '<td class="colspanchange" colspan="4">' .
                                __( 'No permalinks found.', 'custom-permalinks' ) .
                            '</td>' .
                          '</tr>';
        }
        $page_html .= '</tbody>' .
                      '<tfoot>' . $table_navigation . '</tfoot>' .
                      '</table>';

        $page_html .= '<div class="tablenav bottom">' .
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

        echo $page_html;
    }
}
