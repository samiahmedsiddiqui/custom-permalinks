<?php
/**
 * @package CustomPermalinks
 */

class Custom_Permalinks_Form {

  private $permalink_metabox = 0;

  /**
   * Initialize WordPress Hooks.
   *
   * @since 1.2.0
   * @access public
   */
  public function init() {
    add_action( 'add_meta_boxes', array( $this, 'permalink_edit_box' ) );
    add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
    add_action( 'delete_post', array( $this, 'delete_permalink' ), 10 );
    add_action( 'category_add_form', array( $this, 'term_options' ) );
    add_action( 'category_edit_form', array( $this, 'term_options' ) );
    add_action( 'post_tag_add_form', array( $this, 'term_options' ) );
    add_action( 'post_tag_edit_form', array( $this, 'term_options' ) );
    add_action( 'edited_post_tag', array( $this, 'save_tag' ) );
    add_action( 'edited_category', array( $this, 'save_category' ) );
    add_action( 'create_post_tag', array( $this, 'save_tag' ) );
    add_action( 'create_category', array( $this, 'save_category' ) );
    add_action( 'delete_post_tag', array( $this, 'delete_term' ) );
    add_action( 'delete_post_category', array( $this, 'delete_term' ) );

    add_filter( 'get_sample_permalink_html',
      array( $this, 'sample_permalink_html' ), 10, 2
    );
    add_filter( 'is_protected_meta', array( $this, 'protect_meta' ), 10, 2 );
  }

  /**
   * Register meta box(es).
   *
   * @since 1.4.0
   * @access public
   */
  public function permalink_edit_box() {
    add_meta_box( 'custom-permalinks-edit-box',
      __( 'Permalink', 'custom-permalinks' ),
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
  public function protect_meta( $protected, $meta_key ) {
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
  public function save_post( $post_id ) {
    if ( ! isset( $_REQUEST['custom_permalinks_edit'] ) ) {
      return;
    }

    delete_post_meta( $post_id, 'custom_permalink' );

    $cp_frontend = new Custom_Permalinks_Frontend();
    $original_link = $cp_frontend->original_post_link( $post_id );
    if ( $_REQUEST['custom_permalink'] && $_REQUEST['custom_permalink'] != $original_link ) {
      add_post_meta( $post_id, 'custom_permalink',
        str_replace( '%2F', '/',
          urlencode( ltrim( stripcslashes( $_REQUEST['custom_permalink'] ), "/" ) )
        )
      );
    }
  }

  /**
   * Delete Post Permalink.
   *
   * @access public
   *
   * @param int $post_id Post ID.
   */
  public function delete_permalink( $post_id ) {
    global $wpdb;
    $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = 'custom_permalink' AND post_id = %d", $post_id ) );
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
  public function sample_permalink_html( $html, $post_id ) {
    $post                    = get_post( $post_id );
    $this->permalink_metabox = 1;

    if ( 'attachment' === $post->post_type || $post->ID === get_option( 'page_on_front' ) ) {
      return $html;
    }

    $exclude_post_types = $post->post_type;
    $excluded           = apply_filters( 'custom_permalinks_exclude_post_type', $exclude_post_types );
    if ( '__true' === $excluded ) {
      return $html;
    }
    $permalink = get_post_meta( $post_id, 'custom_permalink', true );

    ob_start();

    $cp_frontend = new Custom_Permalinks_Frontend();
    if ( 'page' === $post->post_type ) {
      $original_permalink = $cp_frontend->original_page_link( $post_id );
      $view_post          = __( 'View Page', 'custom-permalinks' );
    } else {
      $original_permalink = $cp_frontend->original_post_link( $post_id );
      $view_post          = __( 'View ' . ucfirst( $post->post_type ), 'custom-permalinks' );
    }
    $this->get_permalink_form( $permalink, $original_permalink, false, $post->post_name );

    $content = ob_get_contents();
    ob_end_clean();

    if ( 'trash' !== $post->post_status ) {
      wp_enqueue_script( 'custom-permalinks-form',
        plugins_url( '/js/script-form.min.js', __FILE__ ), array(), false, true
      );
      if ( isset( $permalink ) && ! empty( $permalink ) ) {
        $view_post_link = trailingslashit( home_url() ) . $permalink;
      } else {
        $view_post_link = trailingslashit( home_url() ) . $original_permalink;
      }

      $content .= ' <span id="view-post-btn">' .
                    '<a href="' . $view_post_link . '" class="button button-small" target="_blank">' . $view_post .' </a>' .
                  '</span><br>';
    }

    return '<strong>' . __( 'Permalink:', 'custom-permalinks' ) . '</strong>\n' . $content;
  }

  /**
   * Adds the Permalink Edit Meta box for the user with validating the PostTypes
   * to make compatibility with Gutenberg.
   *
   * @access public
   *
   * @param object $post WP Post Object.
   */
  public function meta_edit_form( $post ) {
    $form_return = 0;
    if ( isset( $this->permalink_metabox ) && 1 === $this->permalink_metabox ) {
      $form_return = 1;
    }

    if ( 'attachment' === $post->post_type ) {
      $form_return = 1;
    } else if ( $post->ID === get_option( 'page_on_front' ) ) {
      $form_return = 1;
    }

    $post_types = get_post_types( $args, 'objects' );
    if ( ! isset( $post_types[$post->post_type] ) ) {
      $form_return = 1;
    }

    $exclude_post_types = $post->post_type;
    $excluded           = apply_filters( 'custom_permalinks_exclude_post_type', $exclude_post_types );
    if ( '__true' === $excluded ) {
      $form_return = 1;
    }

    if ( 1 === $form_return ) {
      wp_enqueue_script( 'custom-permalinks-form',
        plugins_url( '/js/script-form.min.js', __FILE__ ), array(), false, true
      );
      return;
    }

    $screen = get_current_screen();
    if ( 'add' === $screen->action ) {
      echo '<input value="add" type="hidden" name="custom-permalinks-add" id="custom-permalinks-add" />';
    }
    $permalink = get_post_meta( $post->ID, 'custom_permalink', true );

    ob_start();

    $cp_frontend = new Custom_Permalinks_Frontend();
    if ( 'page' === $post->post_type ) {
      $original_permalink = $cp_frontend->original_page_link( $post->ID );
      $view_post          = __( 'View Page', 'custom-permalinks' );
    } else {
      $original_permalink = $cp_frontend->original_post_link( $post->ID );
      $view_post          = __( 'View ' . ucfirst( $post->post_type ), 'custom-permalinks' );
    }
    $this->get_permalink_form( $permalink, $original_permalink, false, $post->post_name );

    $content = ob_get_contents();
    ob_end_clean();

    if ( 'trash' !== $post->post_status ) {
      wp_enqueue_script( 'custom-permalinks-form',
        plugins_url( '/js/script-form.min.js', __FILE__ ), array(), false, true
      );
      $content .= '<label style="display:block;">Actions</label>';
      if ( isset( $permalink ) && ! empty( $permalink ) ) {
        $view_url = trailingslashit( home_url() ) . $permalink;
        $content .= ' <span id="view-post-btn">' .
                      '<a href="' . $view_url . '" class="button button-small" target="_blank">' . $view_post . '</a>' .
                    '</span><br>';
      } else {
        $view_url = trailingslashit( home_url() ) . $original_permalink;
        $content .= ' <span id="view-post-btn">' .
                      '<a href="' . $view_url . '" class="button button-small" target="_blank">' . $view_post .' </a>' .
                    '</span><br>';
      }
      $content .= '<style>.editor-post-permalink,.cp-permalink-hidden{display:none;}</style>';
    }

    echo $content;
  }

  /**
   * Per-post options (Wordpress < 2.9).
   *
   * @access public
   */
  public function post_options() {
    global $post;

    if ( is_object( $post ) ) {
      $post_id = $post->ID;
    } else {
      $post_id = $post;
    }

    $permalink = get_post_meta( $post_id, 'custom_permalink', true );
    ?>
    <div class="postbox closed">
      <h3><?php _e( 'Custom Permalink', 'custom-permalinks' ) ?></h3>
      <div class="inside">
        <?php
          $cp_frontend = new Custom_Permalinks_Frontend();
          $this->get_permalink_form( $permalink, $cp_frontend->original_post_link( $post_id ) );
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
  public function page_options() {
    global $post;

    if ( is_object( $post ) ) {
      $post_id = $post->ID;
    } else {
      $post_id = $post;
    }

    $permalink = get_post_meta( $post_id, 'custom_permalink', true );
    ?>
    <div class="postbox closed">
      <h3><?php _e( 'Custom Permalink', 'custom-permalinks' ); ?></h3>
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
  public function term_options( $object ) {
    if ( is_object( $object ) && isset( $object->term_id ) ) {
      $cp_frontend = new Custom_Permalinks_Frontend();
      $permalink   = $cp_frontend->term_permalink( $object->term_id );

      if ( $object->term_id ) {
        if ( 'post_tag' === $object->taxonomy ) {
          $original_permalink = $cp_frontend->original_tag_link( $object->term_id );
        } else {
          $original_permalink = $cp_frontend->original_category_link( $object->term_id );
        }
      }

      $this->get_permalink_form( $permalink, $original_permalink );
    } else {
      $this->get_permalink_form( '' );
    }

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
  private function get_permalink_form( $permalink, $original = '', $render_containers = true, $postname = '' ) {
    $encoded_permalink = htmlspecialchars( urldecode( $permalink ) );
    echo '<input value="true" type="hidden" name="custom_permalinks_edit" />' .
         '<input value="' . home_url() . '" type="hidden" name="custom_permalinks_home_url" id="custom_permalinks_home_url" />' .
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
    $post_slug = htmlspecialchars( $permalink ? urldecode( $permalink ) : urldecode( $original ) );
    $original_encoded_url = htmlspecialchars( urldecode( $original ) );
    wp_enqueue_script( 'custom-permalinks-form', plugins_url( '/js/script-form.min.js', __FILE__ ), array(), false, true );
    $postname_html = '';
    if ( isset( $postname ) && '' !== $postname ) {
      $postname_html = '<input type="hidden" id="new-post-slug" class="text" value="' . $postname . '" />';
    }

    $home_url   = trailingslashit( home_url() );

    echo $home_url . '/<span id="editable-post-name" title="Click to edit this part of the permalink">' . $postname_html;

    ?>
    <input type="text" id="custom-permalinks-post-slug" class="text" value="<?php echo $post_slug; ?>"
    style="width: 250px; <?php if ( !$permalink ) echo 'color: #ddd'; ?>"
    onfocus="if ( this.style.color = '#ddd' ) { this.style.color = '#000'; }"
    onblur="document.getElementById('custom_permalink').value = this.value; if ( this.value === '' || this.value === '<?php echo $original_encoded_url;  ?>' ) { this.value = '<?php echo $original_encoded_url; ?>'; this.style.color = '#ddd'; }" />
    </span>

    <?php if ( $render_containers ) : ?>
    <br />
    <small><?php _e( 'Leave blank to disable', 'custom-permalinks' ); ?></small>
    </td>
    </tr>
    </table>
    <?php
    endif;
  }

  /**
   * Save per-tag options.
   *
   * @access public
   *
   * @param int $term_id Term ID.
   */
  public function save_tag( $term_id ) {
    if ( ! isset( $_REQUEST['custom_permalinks_edit'] )
      || isset( $_REQUEST['post_ID'] ) ) {
      return;
    }
    $new_permalink = ltrim( stripcslashes( $_REQUEST['custom_permalink'] ), '/' );

    $cp_frontend = new Custom_Permalinks_Frontend();
    if ( $new_permalink === $cp_frontend->original_tag_link( $term_id ) ) {
      return;
    }

    $term = get_term( $term_id, 'post_tag' );
    $this->save_term( $term, str_replace( '%2F', '/', urlencode( $new_permalink ) ) );
  }

  /**
   * Save per-category options.
   *
   * @access public
   *
   * @param int $term_id Term ID.
   */
  public function save_category( $term_id ) {
    if ( ! isset( $_REQUEST['custom_permalinks_edit'] )
      || isset( $_REQUEST['post_ID'] ) ) {
      return;
    }
    $new_permalink = ltrim( stripcslashes( $_REQUEST['custom_permalink'] ), '/' );

    $cp_frontend = new Custom_Permalinks_Frontend();
    if ( $new_permalink === $cp_frontend->original_category_link( $term_id ) ) {
      return;
    }

    $term = get_term( $term_id, 'category' );
    $this->save_term(
      $term, str_replace( '%2F', '/', urlencode( $new_permalink ) )
    );
  }

  /**
   * Save term (common to tags and categories).
   *
   * @access public
   *
   * @param object $term Term Object.
   * @param string $permalink New permalink which needs to be saved.
   */
  public function save_term( $term, $permalink ) {

    $this->delete_term( $term->term_id );
    $table = get_option( 'custom_permalink_table' );
    if ( $permalink ) {
      $table[$permalink] = array(
        'id' => $term->term_id,
        'kind' => ( $term->taxonomy === 'category' ? 'category' : 'tag' ),
        'slug' => $term->slug
      );
    }

    update_option( 'custom_permalink_table', $table );
  }

  /**
   * Delete term.
   *
   * @access public
   *
   * @param int $term_id Term ID.
   */
  public function delete_term( $term_id ) {
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
  public function check_conflicts( $requested_url = '' ) {
    if ( '' === $requested_url ) {
      return;
    }

    // Check if the Polylang Plugin is installed so, make changes in the URL
    if ( defined( 'POLYLANG_VERSION' ) ) {
      $polylang_config = get_option( 'polylang' );
      if ( 1 === $polylang_config['force_lang'] ) {

        if ( false !== strpos( $requestedUrl, 'language/' ) ) {
          $requested_url = str_replace( 'language/', '', $requested_url );
        }

        /*
         * Check if hide_default is true and the current language is not the
         * default. If true the remove the  lang code from the url.
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
}
