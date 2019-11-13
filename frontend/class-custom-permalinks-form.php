<?php
/**
 * @package CustomPermalinks
 */

class CustomPermalinksForm {

  private $permalinkMetabox = 0;

  /**
   * Initialize WordPress Hooks.
   *
   * @since 1.2.0
   * @access public
   */
  public function init() {
    add_action( 'add_meta_boxes', array( $this, 'permalinkEditBox' ) );
    add_action( 'save_post', array( $this, 'savePost' ), 10, 3 );
    add_action( 'delete_post', array( $this, 'deletePermalink' ), 10 );
    add_action( 'category_add_form', array( $this, 'termOptions' ) );
    add_action( 'category_edit_form', array( $this, 'termOptions' ) );
    add_action( 'post_tag_add_form', array( $this, 'termOptions' ) );
    add_action( 'post_tag_edit_form', array( $this, 'termOptions' ) );
    add_action( 'edited_post_tag', array( $this, 'saveTag' ) );
    add_action( 'edited_category', array( $this, 'saveCategory' ) );
    add_action( 'create_post_tag', array( $this, 'saveTag' ) );
    add_action( 'create_category', array( $this, 'saveCategory' ) );
    add_action( 'delete_post_tag', array( $this, 'deleteTerm' ) );
    add_action( 'delete_post_category', array( $this, 'deleteTerm' ) );

    add_filter( 'get_sample_permalink_html',
      array( $this, 'getSamplePermalinkHTML' ), 10, 2
    );
    add_filter( 'is_protected_meta', array( $this, 'protectMeta' ), 10, 2 );
  }

  /**
   * Register meta box(es).
   *
   * @since 1.4.0
   * @access public
   */
  public function permalinkEditBox() {
    add_meta_box( 'custom-permalinks-edit-box',
      __( 'Permalink', 'custom-permalinks' ),
      array( $this, 'metaEditForm' ), null, 'normal', 'high',
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
   * @param string $metaKey Meta key.
   *
   * @return bool `true` for the custom_permalink key.
   */
  public function protectMeta( $protected, $metaKey ) {
    if ( 'custom_permalink' === $metaKey ) {
      $protected = true;
    }
    return $protected;
  }

  /**
   * Save per-post options.
   *
   * @access public
   *
   * @param int $id Post ID.
   */
  public function savePost( $id ) {
    if ( ! isset( $_REQUEST['custom_permalinks_edit'] ) ) {
      return;
    }

    delete_post_meta( $id, 'custom_permalink' );

    $cpFrontend = new CustomPermalinksFrontend();
    $originalLink = $cpFrontend->originalPostLink( $id );
    if ( $_REQUEST['custom_permalink'] && $_REQUEST['custom_permalink'] != $originalLink ) {
      add_post_meta( $id, 'custom_permalink',
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
   * @param int $id Post ID.
   */
  public function deletePermalink( $id ) {
    global $wpdb;
    $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = 'custom_permalink' AND post_id = %d", $id ) );
  }

  /**
   * Per-post/page options (Wordpress > 2.9).
   *
   * @access public
   *
   * @param string $html WP Post Permalink HTML.
   * @param int $id Post ID.
   *
   * @return string Edit Form string.
   */
  public function getSamplePermalinkHTML( $html, $id ) {
    $post                   = get_post( $id );
    $this->permalinkMetabox = 1;

    if ( 'attachment' == $post->post_type || $post->ID == get_option( 'page_on_front' ) ) {
      return $html;
    }

    $excludePostTypes = $post->post_type;
    $excluded = apply_filters( 'custom_permalinks_exclude_post_type', $excludePostTypes );
    if ( '__true' === $excluded ) {
      return $html;
    }
    $permalink = get_post_meta( $id, 'custom_permalink', true );

    ob_start();

    $cpFrontend = new CustomPermalinksFrontend();
    if ( 'page' == $post->post_type ) {
      $originalPermalink = $cpFrontend->originalPageLink( $id );
      $viewPost          = __( 'View Page', 'custom-permalinks' );
    } else {
      $originalPermalink = $cpFrontend->originalPostLink( $id );
      $viewPost          = __( 'View ' . ucfirst( $post->post_type ), 'custom-permalinks' );
    }
    $this->getPermalinkForm( $permalink, $originalPermalink, false, $post->post_name );

    $content = ob_get_contents();
    ob_end_clean();

    $viewPostLink = get_permalink( $post );

    return '<strong>' . __( 'Permalink:', 'custom-permalinks' ) . "</strong>\n" . $content .
         ( isset( $viewPost ) ? "<span id='view-post-btn'><a href='$viewPostLink' class='button button-small' target='_blank'>$viewPost</a></span>\n" : "" );
  }

  /**
   * Adds the Permalink Edit Meta box for the user with validating the PostTypes
   * to make compatibility with Gutenberg.
   *
   * @access public
   *
   * @param object $post WP Post Object.
   */
  public function metaEditForm( $post ) {
    if ( isset( $this->permalinkMetabox ) && 1 === $this->permalinkMetabox ) {
      wp_enqueue_script( 'custom-permalinks-form',
        plugins_url( '/js/script-form.min.js', __FILE__ ), array(), false, true
      );
      return;
    }

    $screen = get_current_screen();
    if ( 'add' === $screen->action ) {
      wp_enqueue_script( 'custom-permalinks-form',
        plugins_url( '/js/script-form.min.js', __FILE__ ), array(), false, true
      );
      return;
    }

    if ( 'attachment' == $post->post_type || $post->ID == get_option( 'page_on_front' ) ) {
      return;
    }

    $excludePostTypes = $post->post_type;
    $excluded = apply_filters( 'custom_permalinks_exclude_post_type', $excludePostTypes );
    if ( '__true' === $excluded ) {
      wp_enqueue_script( 'custom-permalinks-form',
        plugins_url( '/js/script-form.min.js', __FILE__ ), array(), false, true
      );
      return;
    }
    $permalink = get_post_meta( $post->ID, 'custom_permalink', true );

    ob_start();

    $cpFrontend = new CustomPermalinksFrontend();
    if ( 'page' == $post->post_type ) {
      $originalPermalink = $cpFrontend->originalPageLink( $post->ID );
      $viewPost = __( 'View Page', 'custom-permalinks' );
    } else {
      $originalPermalink = $cpFrontend->originalPostLink( $post->ID );
      $viewPost = __( 'View ' . ucfirst( $post->post_type ), 'custom-permalinks' );
    }
    $this->getPermalinkForm( $permalink, $originalPermalink, false, $post->post_name );

    $content = ob_get_contents();
    ob_end_clean();

    if ( 'trash' != $post->post_status ) {
      wp_enqueue_script( 'custom-permalinks-form',
        plugins_url( '/js/script-form.min.js', __FILE__ ), array(), false, true
      );
      $viewPostLink = get_permalink( $post );

      $content .= ' <span id="view-post-btn">' .
                    '<a href="' . $viewPostLink . '" class="button button-small" target="_blank">' . $viewPost . '</a>' .
                  '</span><br>';
    }
    echo $content;
  }

  /**
   * Per-post options (Wordpress < 2.9).
   *
   * @access public
   */
  public function postOptions() {
    global $post;

    if ( is_object( $post ) ) {
      $postId = $post->ID;
    } else {
      $postId = $post;
    }

    $permalink = get_post_meta( $postId, 'custom_permalink', true );
    ?>
    <div class="postbox closed">
      <h3><?php _e( 'Custom Permalink', 'custom-permalinks' ) ?></h3>
      <div class="inside">
        <?php
          $cpFrontend = new CustomPermalinksFrontend();
          $cpFrontend->getPermalinkForm( $permalink, $cpFrontend->originalPostLink( $postId ) );
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
  public function pageOptions() {
    global $post;

    if ( is_object( $post ) ) {
      $postId = $post->ID;
    } else {
      $postId = $post;
    }

    $permalink = get_post_meta( $postId, 'custom_permalink', true );
    ?>
    <div class="postbox closed">
      <h3><?php _e( 'Custom Permalink', 'custom-permalinks' ); ?></h3>
      <div class="inside">
      <?php
        $cpFrontend = new CustomPermalinksFrontend();
        $pagePermalink = $cpFrontend->originalPageLink( $postId );
        $this->getPermalinkForm( $permalink, $pagePermalink );
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
  public function termOptions( $object ) {
    if ( is_object( $object ) && isset( $object->term_id ) ) {
      $cpFrontend = new CustomPermalinksFrontend();
      $permalink  = $cpFrontend->termPermalink( $object->term_id );

      if ( $object->term_id ) {
        if ( $object->taxonomy == 'post_tag' ) {
          $originalPermalink = $cpFrontend->originalTagLink( $object->term_id );
        } else {
          $originalPermalink = $cpFrontend->originalCategoryLink( $object->term_id );
        }
      }

      $this->getPermalinkForm( $permalink, $originalPermalink );
    } else {
      $this->getPermalinkForm( '' );
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
   * @param bool $renderContainers Shows Post/Term Edit.
   * @param string $postname Post Name.
   */
  private function getPermalinkForm( $permalink, $original = '', $renderContainers = true, $postname = '' ) {
    ?>
    <input value="true" type="hidden" name="custom_permalinks_edit" />
    <input value="<?php echo home_url(); ?>" type="hidden" name="custom_permalinks_home_url" id="custom_permalinks_home_url" />
    <input value="<?php echo htmlspecialchars( urldecode( $permalink ) ); ?>" type="hidden" name="custom_permalink" id="custom_permalink" />

    <?php
    if ( $renderContainers ) :
    ?>
    <table class="form-table" id="custom_permalink_form">
    <tr>
      <th scope="row"><?php _e( 'Custom Permalink', 'custom-permalinks' ); ?></th>
      <td>
    <?php
    endif;
    if ( $permalink == '' ) {
      $original = $this->checkConflicts( $original );
    }
    $postSlug = htmlspecialchars( $permalink ? urldecode( $permalink ) : urldecode( $original ) );
    $originalEncodedUrl = htmlspecialchars( urldecode( $original ) );
    wp_enqueue_script( 'custom-permalinks-form', plugins_url( '/js/script-form.min.js', __FILE__ ), array(), false, true );
    $postnameHTML = '';
    if ( isset( $postname ) && $postname != '' ) {
      $postnameHTML = '<input type="hidden" id="new-post-slug" class="text" value="' . $postname . '" />';
    }

    echo home_url() . '/<span id="editable-post-name" title="Click to edit this part of the permalink">' . $postnameHTML;

    ?>
    <input type="text" id="custom-permalinks-post-slug" class="text" value="<?php echo $postSlug; ?>"
    style="width: 250px; <?php if ( !$permalink ) echo 'color: #ddd'; ?>"
    onfocus="if ( this.style.color = '#ddd' ) { this.style.color = '#000'; }"
    onblur="document.getElementById('custom_permalink').value = this.value; if ( this.value == '' || this.value == '<?php echo $originalEncodedUrl;  ?>' ) { this.value = '<?php echo $originalEncodedUrl; ?>'; this.style.color = '#ddd'; }" />
    </span>

    <?php if ( $renderContainers ) : ?>
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
   * @param int $id Term ID.
   */
  public function saveTag( $id ) {
    if ( ! isset( $_REQUEST['custom_permalinks_edit'] )
      || isset( $_REQUEST['post_ID'] ) ) {
      return;
    }
    $newPermalink = ltrim( stripcslashes( $_REQUEST['custom_permalink'] ), '/' );

    $cpFrontend = new CustomPermalinksFrontend();
    if ( $newPermalink == $cpFrontend->originalTagLink( $id ) ) {
      return;
    }

    $term = get_term( $id, 'post_tag' );
    $this->saveTerm( $term, str_replace( '%2F', '/', urlencode( $newPermalink ) ) );
  }

  /**
   * Save per-category options.
   *
   * @access public
   *
   * @param int $id Term ID.
   */
  public function saveCategory( $id ) {
    if ( ! isset( $_REQUEST['custom_permalinks_edit'] )
      || isset( $_REQUEST['post_ID'] ) ) {
      return;
    }
    $newPermalink = ltrim( stripcslashes( $_REQUEST['custom_permalink'] ), '/' );

    $cpFrontend = new CustomPermalinksFrontend();
    if ( $newPermalink == $cpFrontend->originalCategoryLink( $id ) ) {
      return;
    }

    $term = get_term( $id, 'category' );
    $this->saveTerm(
      $term, str_replace( '%2F', '/', urlencode( $newPermalink ) )
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
  public function saveTerm( $term, $permalink ) {

    $this->deleteTerm( $term->term_id );
    $table = get_option( 'custom_permalink_table' );
    if ( $permalink ) {
      $table[$permalink] = array(
        'id' => $term->term_id,
        'kind' => ( $term->taxonomy == 'category' ? 'category' : 'tag' ),
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
   * @param int $id Term ID.
   */
  public function deleteTerm( $id ) {
    $table = get_option( 'custom_permalink_table' );
    if ( $table ) {
      foreach ( $table as $link => $info ) {
        if ( $info['id'] == $id ) {
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
  public function checkConflicts( $requestedUrl = '' ) {
    if ( '' == $requestedUrl ) {
      return;
    }

    // Check if the Polylang Plugin is installed so, make changes in the URL
    if ( defined( 'POLYLANG_VERSION' ) ) {
      $polylangConfig = get_option( 'polylang' );
      if ( $polylangConfig['force_lang'] == 1 ) {

        if ( false !== strpos( $requestedUrl, 'language/' ) ) {
          $requestedUrl = str_replace( 'language/', '', $requestedUrl );
        }

        /*
         * Check if hide_default is true and the current language is not the
         * default. If true the remove the  lang code from the url.
         */
        if ( 1 == $polylangConfig['hide_default'] ) {
          $currentLanguage = '';
          if ( function_exists( 'pll_current_language' ) ) {
            // get current language
            $currentLanguage = pll_current_language();
          }

          // get default language
          $defaultLanguage = $polylangConfig['default_lang'];
          if ( $currentLanguage !== $defaultLanguage ) {
            $removeLang = ltrim( strstr( $requestedUrl, '/' ), '/' );
            if ( '' != $removeLang ) {
              return $removeLang;
            }
          }
        } else {
          $removeLang = ltrim( strstr( $requestedUrl, '/' ), '/' );
          if ( '' != $removeLang ) {
            return $removeLang;
          }
        }
      }
    }

    return $requestedUrl;
  }
}
