<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_Form
{

    /*
     * JS file suffix (version number with with extension)
     */
     private $js_file_suffix = '-' . CUSTOM_PERMALINKS_PLUGIN_VERSION . '.min.js';

    /*
     * Decide whether to show metabox or override WordPress default Permalink box.
     */
    private $permalink_metabox = 0;

    /**
     * Initialize WordPress Hooks.
     *
     * @since 1.2.0
     * @access public
     */
    public function init()
    {
        add_action( 'add_meta_boxes', array( $this, 'permalink_edit_box' ) );
        add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
        add_action( 'delete_post', array( $this, 'delete_permalink' ), 10 );
        add_action( 'category_add_form', array( $this, 'term_options' ) );
        add_action( 'category_edit_form', array( $this, 'term_options' ) );
        add_action( 'post_tag_add_form', array( $this, 'term_options' ) );
        add_action( 'post_tag_edit_form', array( $this, 'term_options' ) );
        add_action( 'created_term', array( $this, 'save_term' ), 10, 3 );
        add_action( 'edited_term', array( $this, 'save_term' ), 10, 3 );
        add_action( 'delete_term', array( $this, 'delete_term_permalink' ), 10, 3 );
        add_action( 'rest_api_init', array( $this, 'rest_edit_form' ) );
        add_action( 'update_option_page_on_front',
            array( $this, 'static_homepage' ), 10, 2
        );

        add_filter( 'get_sample_permalink_html',
            array( $this, 'sample_permalink_html' ), 10, 2
        );
        add_filter( 'is_protected_meta', array( $this, 'protect_meta' ), 10, 2 );
    }

    /**
     * Initialize WordPress Hooks.
     *
     * @since 1.6.0
     * @access private
     *
     * @param object $post WP Post Object.
     *
     * return bool false Whether to show Custom Permalink form or not.
     */
    private function exclude_custom_permalinks( $post )
    {
        $args = array(
            'public' => true,
        );
        $exclude_post_types = apply_filters( 'custom_permalinks_exclude_post_type',
            $post->post_type
        );
        /*
         * Exclude custom permalink `form` from any post(s) if filter returns `true`.
         *
         * @since 1.6.0
         */
        $exclude_posts      = apply_filters( 'custom_permalinks_exclude_posts',
            $post
        );
        $public_post_types  = get_post_types( $args, 'objects' );

        if ( isset( $this->permalink_metabox ) && 1 === $this->permalink_metabox ) {
            $check_availability = true;
        } elseif ( 'attachment' === $post->post_type ) {
            $check_availability = true;
        } elseif ( $post->ID == get_option( 'page_on_front' ) ) {
            $check_availability = true;
        } elseif ( ! isset( $public_post_types[$post->post_type] ) ) {
            $check_availability = true;
        } elseif ( '__true' === $exclude_post_types ) {
            $check_availability = true;
        } elseif ( is_bool( $exclude_posts ) && $exclude_posts ) {
            $check_availability = true;
        } else {
            $check_availability = false;
        }

        return $check_availability;
    }

    /**
     * Register meta box(es).
     *
     * @since 1.4.0
     * @access public
     */
    public function permalink_edit_box()
    {
        add_meta_box( 'custom-permalinks-edit-box',
            __( 'Custom Permalinks', 'custom-permalinks' ),
            array( $this, 'meta_edit_form' ), null, 'normal', 'high',
            array(
                '__back_compat_meta_box' => false,
            )
        );
    }

    /**
     * Set the meta_keys to protected which is created by the plugin.
     *
     * @since 1.4.0
     * @access public
     *
     * @param bool $protected Whether the key is protected or not.
     * @param string $meta_key Meta key.
     *
     * @return bool `true` for the custom_permalink key.
     */
    public function protect_meta( $protected, $meta_key )
    {
        if ( 'custom_permalink' === $meta_key ) {
            $protected = true;
        }

        return $protected;
    }

    /**
     * Save per-post options.
     *
     * @access public
     *
     * @param int $post_id Post ID.
     */
    public function save_post( $post_id )
    {
        if ( ! isset( $_REQUEST['custom_permalinks_edit'] ) ) {
            return;
        }

        $cp_frontend   = new Custom_Permalinks_Frontend();
        $original_link = $cp_frontend->original_post_link( $post_id );

        if ( isset( $_REQUEST['custom_permalink'] )
            && $_REQUEST['custom_permalink'] != $original_link
        ) {
            $reserved_chars = array(
                '(',
                ')',
                '[',
                ']',
            );

            $unsafe_chars = array(
                '<',
                '>',
                '{',
                '}',
                '|',
                '`',
                '^',
                '\\',
            );

            $permalink = $_REQUEST['custom_permalink'];
            $permalink = ltrim( $permalink, '/' );
            $permalink = strtolower( $permalink );
            $permalink = str_replace( $reserved_chars, '', $permalink );
            $permalink = str_replace( $unsafe_chars, '', $permalink );
            $permalink = urlencode( $permalink );
            // Replace encoded slash input with slash
            $permalink = str_replace( '%2F', '/', $permalink );

            $replace_hyphen = array( '%20', '%2B', '+' );
            $split_path     = explode( '%3F', $permalink );
            if ( 1 < count( $split_path ) ) {
                // Replace encoded space and plus input with hyphen
                $replaced_path = str_replace( $replace_hyphen, '-', $split_path[0] );
                $replaced_path = preg_replace( '/(\-+)/', '-', $replaced_path );
                $permalink     = str_replace( $split_path[0], $replaced_path,
                    $permalink
                );
            } else {
                // Replace encoded space and plus input with hyphen
                $permalink = str_replace( $replace_hyphen, '-', $permalink );
                $permalink = preg_replace( '/(\-+)/', '-', $permalink );
            }

            update_post_meta( $post_id, 'custom_permalink', $permalink );
        }
    }

    /**
     * Delete Post Permalink.
     *
     * @access public
     *
     * @param int $post_id Post ID.
     */
    public function delete_permalink( $post_id )
    {
        delete_metadata( 'post', $post_id, 'custom_permalink' );
    }

    /**
     * Result Permalink HTML Form for classic editor and Gutenberg.
     *
     * @since 1.6.0
     * @access private
     *
     * @param object $post WP Post Object.
     * @param bool $meta_box Show whether calls from clasic WordPress or Gutenberg.
     *
     * @return string Permalink Form HTML.
     */
    private function get_permalink_html( $post, $meta_box = false )
    {
        $post_id   = $post->ID;
        $permalink = get_post_meta( $post_id, 'custom_permalink', true );

        ob_start();

        $cp_frontend = new Custom_Permalinks_Frontend();
        if ( 'page' === $post->post_type ) {
            $original_permalink = $cp_frontend->original_page_link( $post_id );
            $view_post          = __( 'View Page', 'custom-permalinks' );
        } else {
            $post_type_name   = '';
            $post_type_object = get_post_type_object( $post->post_type );
            if ( is_object($post_type_object) && isset( $post_type_object->labels )
                && isset( $post_type_object->labels->singular_name )
            ) {
                $post_type_name = ' ' . $post_type_object->labels->singular_name;
            } elseif ( is_object($post_type_object)
                && isset( $post_type_object->label )
            ) {
                $post_type_name = ' ' . $post_type_object->label;
            }

            $original_permalink = $cp_frontend->original_post_link( $post_id );
            $view_post = __( 'View', 'custom-permalinks' ) . $post_type_name;
        }
        $this->get_permalink_form( $permalink, $original_permalink, false,
            $post->post_name
        );

        $content = ob_get_contents();
        ob_end_clean();

        if ( 'trash' !== $post->post_status ) {
            wp_enqueue_script( 'custom-permalinks-form',
                plugins_url( '/js/script-form' . $this->js_file_suffix, __FILE__ ),
                array(), false, true
            );

            $home_url = trailingslashit( home_url() );
            if ( isset( $permalink ) && ! empty( $permalink ) ) {
                $view_post_link = $home_url . $permalink;
            } else {
                if ( 'draft' === $post->post_status ) {
                    $view_post      = 'Preview';
                    $view_post_link = $home_url . '?';
                    if ( 'page' === $post->post_type ) {
                        $view_post_link .= 'page_id';
                    } elseif ( 'post' === $post->post_type ) {
                        $view_post_link .= 'p';
                    } else {
                        $view_post_link .= 'post_type=' . $post->post_type . '&p';
                    }
                    $view_post_link .= '=' . $post_id . '&preview=true';
                } else {
                    $view_post_link = $home_url . $original_permalink;
                }
            }

            $content .= ' <span id="view-post-btn">' .
                          '<a href="' . $view_post_link . '" class="button button-medium" target="_blank">' . $view_post .'</a>' .
                        '</span><br>';
            if ( true === $meta_box ) {
                $content .= '<style>.editor-post-permalink,.cp-permalink-hidden{display:none;}</style>';
            }
        }

        return '<strong>' . __( 'Permalink:', 'custom-permalinks' ) . '</strong> ' . $content;
    }

    /**
     * Per-post/page options (Wordpress > 2.9).
     *
     * @access public
     *
     * @param string $html WP Post Permalink HTML.
     * @param int $post_id Post ID.
     *
     * @return string Edit Form string.
     */
    public function sample_permalink_html( $html, $post_id )
    {
        $post = get_post( $post_id );

        $disable_cp = $this->exclude_custom_permalinks( $post );
        $this->permalink_metabox = 1;
        if ( $disable_cp ) {
            return $html;
        }

        $output_content = $this->get_permalink_html( $post );

        return $output_content;
    }

    /**
     * Adds the Permalink Edit Meta box for the user with validating the
     * PostTypes to make compatibility with Gutenberg.
     *
     * @access public
     *
     * @param object $post WP Post Object.
     */
    public function meta_edit_form( $post )
    {
        $disable_cp = $this->exclude_custom_permalinks( $post );
        if ( $disable_cp ) {
            wp_enqueue_script( 'custom-permalinks-form',
                plugins_url( '/js/script-form' . $this->js_file_suffix, __FILE__ ),
                array(), false, true
            );

            return;
        }

        $screen = get_current_screen();
        if ( 'add' === $screen->action ) {
            echo '<input value="add" type="hidden" name="custom-permalinks-add" id="custom-permalinks-add" />';
        }

        $output_content = $this->get_permalink_html( $post, true );

        echo $output_content;
    }

    /**
     * Per-post options (Wordpress < 2.9).
     *
     * @access public
     */
    public function post_options()
    {
        global $post;

        if ( is_object( $post ) ) {
            $post_id = $post->ID;
        } else {
            $post_id = $post;
        }

        $permalink = get_post_meta( $post_id, 'custom_permalink', true );
        ?>
        <div class="postbox closed">
          <h3>
          <?php
          esc_html_e( 'Custom Permalink', 'custom-permalinks' );
          ?>
          </h3>

          <div class="inside">
          <?php
          $cp_frontend = new Custom_Permalinks_Frontend();
          $this->get_permalink_form( $permalink,
              $cp_frontend->original_post_link( $post_id )
          );
          ?>
          </div>
        </div>
        <?php
    }

    /**
     * Per-page options (Wordpress < 2.9).
     *
     * @access public
     */
    public function page_options()
    {
        global $post;

        if ( is_object( $post ) ) {
            $post_id = $post->ID;
        } else {
            $post_id = $post;
        }

        $permalink = get_post_meta( $post_id, 'custom_permalink', true );
        ?>
        <div class="postbox closed">
          <h3>
          <?php
          esc_html_e( 'Custom Permalink', 'custom-permalinks' );
          ?>
          </h3>
          <div class="inside">
          <?php
          $cp_frontend    = new Custom_Permalinks_Frontend();
          $page_permalink = $cp_frontend->original_page_link( $post_id );
          $this->get_permalink_form( $permalink, $page_permalink );
          ?>
          </div>
        </div>
        <?php
    }

    /**
     * Per-category/tag options.
     *
     * @access public
     *
     * @param object $object Term Object.
     */
    public function term_options( $object )
    {
        $permalink          = '';
        $original_permalink = '';
        if ( is_object( $object ) && isset( $object->term_id ) ) {
            $cp_frontend = new Custom_Permalinks_Frontend();
            if ( $object->term_id ) {
                $permalink = $cp_frontend->term_permalink( $object->term_id );
                $original_permalink = $cp_frontend->original_term_link(
                    $object->term_id
                );
            }
        }

        $this->get_permalink_form( $permalink, $original_permalink );

        // Move the save button to above this form
        wp_enqueue_script( 'jquery' );
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function() {
          var button = jQuery('#custom_permalink_form').parent().find('.submit');
          button.remove().insertAfter(jQuery('#custom_permalink_form'));
        });
        </script>
        <?php
    }

    /**
     * Helper function to render form.
     *
     * @access private
     *
     * @param string $permalink Permalink which is created by the plugin.
     * @param string $original Permalink which set by WordPress.
     * @param bool $render_containers Shows Post/Term Edit.
     * @param string $postname Post Name.
     */
    private function get_permalink_form( $permalink, $original = '',
        $render_containers = true, $postname = ''
    ) {
        $encoded_permalink = htmlspecialchars( urldecode( $permalink ) );
        $home_url = trailingslashit( home_url() );

        echo '<input value="true" type="hidden" name="custom_permalinks_edit" />' .
             '<input value="' . $home_url . '" type="hidden" name="custom_permalinks_home_url" id="custom_permalinks_home_url" />' .
             '<input value="' . $encoded_permalink . '" type="hidden" name="custom_permalink" id="custom_permalink" />';

        if ( $render_containers ) {
            echo '<table class="form-table" id="custom_permalink_form">' .
                    '<tr>' .
                      '<th scope="row">' . __( 'Custom Permalink', 'custom-permalinks' ) . '</th>' .
                      '<td>';
        }
        if ( '' === $permalink ) {
            $original = $this->check_conflicts( $original );
        }

        if ( $permalink ) {
            $post_slug            = htmlspecialchars( urldecode( $permalink ) );
            $original_encoded_url = htmlspecialchars( urldecode( $original ) );
        } else {
            $post_slug            = htmlspecialchars( urldecode( $original ) );
            $original_encoded_url = $post_slug;
        }

        wp_enqueue_script( 'custom-permalinks-form',
            plugins_url( '/js/script-form' . $this->js_file_suffix, __FILE__ ),
            array(), false, true
        );
        $postname_html = '';
        if ( isset( $postname ) && '' !== $postname ) {
            $postname_html = '<input type="hidden" id="new-post-slug" class="text" value="' . $postname . '" />';
        }

        $field_style = 'width: 250px;';
        if ( !$permalink ) {
            $field_style .= ' color: #ddd;';
        }

        echo $home_url .
          '<span id="editable-post-name" title="Click to edit this part of the permalink">' .
            $postname_html .
            '<input type="hidden" id="original-permalink" value="' . $original_encoded_url . '" />' .
            '<input type="text" id="custom-permalinks-post-slug" class="text" value="' . $post_slug . '" style="' . $field_style . '" />' .
          '</span>';

        if ( $render_containers ) {
            echo '<br />' .
                  '<small>' .
                      __( 'Leave blank to disable', 'custom-permalinks' ) .
                  '</small>' .
                  '</td>' .
                  '</tr>' .
                  '</table>';
        }
    }

    /**
     * Save term (common to tags and categories).
     *
     * @since 1.6.0
     * @access public
     *
     * @param string $term_id Term ID.
     */
    public function save_term( $term_id )
    {
        $term = get_term( $term_id );
        if ( isset( $_REQUEST['custom_permalink'] ) && isset( $term )
            && isset( $term->taxonomy )
        ) {
            $taxonomy_name = $term->taxonomy;
            if ( 'category' === $taxonomy_name
                || 'post_tag' === $taxonomy_name
            ) {
                if ( 'post_tag' === $taxonomy_name ) {
                    $taxonomy_name = 'tag';
                }

                $new_permalink = ltrim(
                    stripcslashes( $_REQUEST['custom_permalink'] ), '/'
                );
                if ( empty( $new_permalink ) || '' === $new_permalink ) {
                    return;
                }

                $cp_frontend   = new Custom_Permalinks_Frontend();
                $old_permalink = $cp_frontend->original_term_link( $term_id );
                if ( $new_permalink === $old_permalink ) {
                    return;
                }

                $this->delete_term_permalink( $term_id );

                $permalink = str_replace( '%2F', '/', urlencode( $new_permalink ) );
                $table     = get_option( 'custom_permalink_table' );

                if ( $permalink ) {
                    $table[$permalink] = array(
                        'id'   => $term_id,
                        'kind' => $taxonomy_name,
                        'slug' => $term->slug,
                    );
                }

                update_option( 'custom_permalink_table', $table );
            }
        }
    }

    /**
     * Delete term.
     *
     * @access public
     *
     * @param int $term_id Term ID.
     */
    public function delete_term_permalink( $term_id )
    {
        $table = get_option( 'custom_permalink_table' );
        if ( $table ) {
            foreach ( $table as $link => $info ) {
                if ( $info['id'] === $term_id ) {
                    unset( $table[$link] );
                    break;
                }
            }
        }

        update_option( 'custom_permalink_table', $table );
    }

    /**
     * Check Conflicts and resolve it (e.g: Polylang) UPDATED for Polylang
     * hide_default setting.
     *
     * @since 1.2.0
     * @access public
     *
     * @return string requested URL by removing the language/ from it if exist.
     */
    public function check_conflicts( $requested_url = '' )
    {
        if ( '' === $requested_url ) {
            return;
        }

        // Check if the Polylang Plugin is installed so, make changes in the URL
        if ( defined( 'POLYLANG_VERSION' ) ) {
            $polylang_config = get_option( 'polylang' );
            if ( 1 === $polylang_config['force_lang'] ) {
                if ( false !== strpos( $requested_url, 'language/' ) ) {
                    $requested_url = str_replace( 'language/', '', $requested_url );
                }

                /*
                 * Check if `hide_default` is `true` and the current language is not
                 * the default. Otherwise remove the lang code from the url.
                 */
                if ( 1 === $polylang_config['hide_default'] ) {
                    $current_language = '';
                    if ( function_exists( 'pll_current_language' ) ) {
                        // get current language
                        $current_language = pll_current_language();
                    }

                    // get default language
                    $default_language = $polylang_config['default_lang'];
                    if ( $current_language !== $default_language ) {
                        $remove_lang = ltrim( strstr( $requested_url, '/' ), '/' );
                        if ( '' != $remove_lang ) {
                            return $remove_lang;
                        }
                    }
                } else {
                    $remove_lang = ltrim( strstr( $requested_url, '/' ), '/' );
                    if ( '' != $remove_lang ) {
                        return $remove_lang;
                    }
                }
            }
        }

        return $requested_url;
    }

    /**
     * Refresh Permalink using AJAX Call.
     *
     * @since 1.6.0
     * @access public
     *
     * @param object $data Contains post id with some default REST Values.
     */
    public function refresh_meta_form( $data )
    {
        if ( isset( $data['id'] ) && is_numeric( $data['id'] ) ) {
            $post = get_post( $data['id'] );
            $all_permalinks = array();
            $all_permalinks['custom_permalink'] = get_post_meta( $data['id'],
                'custom_permalink', true
            );

            if ( ! $all_permalinks['custom_permalink'] ) {
                if ( 'draft' === $post->post_status ) {
                    $view_post_link = '?';
                    if ( 'page' === $post->post_type ) {
                        $view_post_link .= 'page_id';
                    } elseif ( 'post' === $post->post_type ) {
                        $view_post_link .= 'p';
                    } else {
                        $view_post_link .= 'post_type=' . $post->post_type . '&p';
                    }
                    $view_post_link .= '=' . $data['id'] . '&preview=true';

                    $all_permalinks['preview_permalink'] = $view_post_link;
                }
            }

            $cp_frontend = new Custom_Permalinks_Frontend;
            if ( 'page' === $post->post_type ) {
                $all_permalinks['original_permalink'] = $cp_frontend->original_page_link(
                    $data['id']
                );
            } else {
                $all_permalinks['original_permalink'] = $cp_frontend->original_post_link(
                    $data['id']
                );
            }

            echo json_encode( $all_permalinks );
            exit;
        }
    }

    /**
     * Added Custom Endpoints for refreshing the permalink.
     *
     * @since 1.6.0
     * @access public
     */
    public function rest_edit_form()
    {
        register_rest_route( 'custom-permalinks/v1', '/get-permalink/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'refresh_meta_form' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );
    }

    /**
     * Delete the Permalink for the Page selected as the Static Homepage.
     *
     * @since 1.6.0
     * @access public
     *
     * @param int $prev_homepage_id Page ID of previously set Front Page.
     * @param int $new_homepage_id Page ID of current Front Page.
     */
    public function static_homepage( $prev_homepage_id, $new_homepage_id ) {
        if ( $prev_homepage_id !== $new_homepage_id ) {
            $this->delete_permalink( $new_homepage_id );
        }
    }
}
