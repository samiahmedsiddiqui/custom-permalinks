<?php
/**
 * Custom Permalinks Post Types.
 *
 * @package CustomPermalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Taxonomies Permalinks table class.
 */
final class Custom_Permalinks_Taxonomies_Table extends WP_List_Table {
	/**
	 * Singleton instance variable
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Initialize the Taxonomies Permalinks table list.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Permalink', 'custom-permalinks' ),
				'plural'   => __( 'Permalinks', 'custom-permalinks' ),
				'ajax'     => false,
			)
		);

		// Handle screen options.
		$this->screen_options();
	}

	/**
	 * Singleton instance method.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return Custom_Permalinks_Taxonomies_Table The instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Handle displaying and saving screen options.
	 *
	 * @since 2.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function screen_options() {
		$per_page_option = "{$this->screen->id}_per_page";

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Save screen options if the form has been submitted.
		if ( isset( $_POST['screen-options-apply'] ) ) {
			// Save posts per page option.
			if ( isset( $_POST['wp_screen_options']['value'] ) ) {
				update_user_option(
					get_current_user_id(),
					$per_page_option,
					sanitize_text_field( wp_unslash( $_POST['wp_screen_options']['value'] ) )
				);
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Add per page option to the screen options.
		$this->screen->add_option(
			'per_page',
			array(
				'option' => $per_page_option,
			)
		);
	}

	/**
	 * No items found text.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No permalinks found.', 'custom-permalinks' );
	}

	/**
	 * Get list of columns in the form of array.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return array Column list.
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'title'     => esc_html__( 'Title', 'custom-permalinks' ),
			'type'      => esc_html__( 'Type', 'custom-permalinks' ),
			'permalink' => esc_html__( 'Permalink', 'custom-permalinks' ),
		);

		return $columns;
	}

	/**
	 * Returns the output of the page.
	 *
	 * @since 2.0.0
	 * @access public
	 */
	public static function output() {
		$user_id              = get_current_user_id();
		$permalink_deleted    = filter_input( INPUT_GET, 'deleted' );
		$search_permalink     = filter_input( INPUT_GET, 's' );
		$taxonomy_types_table = self::instance();
		$taxonomy_types_table->prepare_items();
		?>

		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Taxonomies Permalinks', 'custom-permalinks' ); ?>
			</h1>

			<?php if ( isset( $search_permalink ) && ! empty( $search_permalink ) ) : ?>
				<span class="subtitle">
				<?php
					esc_html_e( 'Search results for: ', 'custom-permalinks' );
					printf( '<strong>%s</strong>', esc_html( $search_permalink ) );
				?>
				</span>
			<?php endif; ?>

			<hr class="wp-header-end">

			<?php if ( isset( $permalink_deleted ) && 0 < $permalink_deleted ) : ?>
				<div id="message" class="updated notice is-dismissible">
					<p>
					<?php
					$delete_msg = '1 permalink deleted.';
					if ( 1 < $permalink_deleted ) {
						$delete_msg = $permalink_deleted . ' permalinks deleted.';
					}

					echo esc_html( $delete_msg );
					?>
					</p>
				</div>
			<?php endif; ?>
			<form id="posts-filter" method="GET">
				<input type="hidden" name="page" value="cp-taxonomy-permalinks" />
				<?php
					wp_nonce_field(
						'custom-permalinks-taxonomy_' . $user_id,
						'_custom_permalinks_taxonomy_nonce'
					);
					$taxonomy_types_table->search_box(
						esc_html__(
							'Search Permalinks',
							'custom-permalinks'
						),
						'search-submit'
					);
					$taxonomy_types_table->display();
				?>
			</form>
		</div>

		<?php
	}

	/**
	 * Set up column headings for WP_List_Table.
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_hidden_columns() {
		$columns = get_user_option( "manage{$this->screen->id}columnshidden" );

		return apply_filters( 'custom_permalinks_taxonomy_table_hidden_columns', (array) $columns );
	}

	/**
	 * Render the checkbox for bulk action.
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @param array $item Single Item.
	 *
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="permalink[]" value="%s" />',
			$item['ID']
		);
	}

	/**
	 * Set up column contents for `Title`.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param array $item Single Item.
	 *
	 * @return string Post Title.
	 */
	protected function column_title( $item ) {
		$edit_link  = '';
		$term_title = 'NOT SET';

		if ( isset( $item['ID'] ) && isset( $item['type'] ) ) {
			$taxonomy_type = 'category';
			if ( 'tag' === $item['type'] ) {
				$taxonomy_type = 'post_tag';
			}

			$edit_link = get_edit_term_link( $item['ID'], $taxonomy_type );
			$term      = get_term( $item['ID'], $taxonomy_type );

			if ( isset( $term ) && isset( $term->name ) && ! empty( $term->name ) ) {
				$term_title = $term->name;
			}
		}

		$title_with_edit_link = $term_title;
		if ( ! empty( $edit_link ) ) {
			$title_with_edit_link = sprintf(
				'<a href="%s" target="_blank" title="' . esc_html__( 'Edit ', 'custom-permalinks' ) . ' ' . $term_title . '">%s</a>',
				$edit_link,
				$term_title
			);
		}

		return $title_with_edit_link;
	}

	/**
	 * Set up column contents for `Type`.
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @param array $item Single Item.
	 *
	 * @return string Taxonomy Name.
	 */
	protected function column_type( $item ) {
		$taxonomy_type = 'category';

		if ( isset( $item['type'] ) ) {
			$taxonomy_type = ucwords( $item['type'] );
		}

		return $taxonomy_type;
	}

	/**
	 * Set up column contents for `Permalink`.
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @param array $item Single Item.
	 *
	 * @return string Post Permalink.
	 */
	protected function column_permalink( $item ) {
		$permalink = '';

		if ( $item['permalink'] ) {
			$cp_frontend      = new Custom_Permalinks_Frontend();
			$custom_permalink = '/' . $item['permalink'];
			$home_url         = home_url();
			$taxonomy_type    = 'category';

			if ( class_exists( 'SitePress' ) ) {
				$wpml_lang_format = apply_filters(
					'wpml_setting',
					0,
					'language_negotiation_type'
				);

				if ( 1 === intval( $wpml_lang_format ) ) {
					$home_url = site_url();
				}
			}

			if ( 'tag' === $item['type'] ) {
				$taxonomy_type = 'post_tag';
			}

			$language_code = apply_filters(
				'wpml_element_language_code',
				null,
				array(
					'element_id'   => $item['ID'],
					'element_type' => $taxonomy_type,
				)
			);

			$permalink = $cp_frontend->wpml_permalink_filter(
				$custom_permalink,
				$language_code
			);
			$permalink = $cp_frontend->remove_double_slash( $permalink );
			$perm_text = str_replace( $home_url, '', $permalink );

			$term_title = '';
			if ( isset( $item['ID'] ) && isset( $item['type'] ) ) {
				$term = get_term( $item['ID'], $item['type'] );
				if ( isset( $term ) && isset( $term->name ) && ! empty( $term->name ) ) {
					$term_title = $term->name;
				}
			}

			$permalink = sprintf(
				'<a href="%s" target="_blank" title="' . esc_html__( 'Visit', 'custom-permalinks' ) . ' ' . $term_title . '">%s</a>',
				$permalink,
				$perm_text
			);
		}

		return $permalink;
	}

	/**
	 * Get bulk actions.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return array Available Actions.
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => esc_html__( 'Delete Permalinks', 'custom-permalinks' ),
		);
	}

	/**
	 * Process bulk actions.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		if ( isset( $_REQUEST['_custom_permalinks_taxonomy_nonce'] ) ) {
			$deleted = 0;
			$user_id = get_current_user_id();

			// Detect when a bulk action is being triggered.
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( 'delete' === $this->current_action()
				&& wp_verify_nonce(
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
					$_REQUEST['_custom_permalinks_taxonomy_nonce'],
					'custom-permalinks-taxonomy_' . $user_id
				)
			) {
				if ( isset( $_REQUEST['permalink'] ) ) {
					$del_permalinks = wp_unslash( $_REQUEST['permalink'] );
				}

				if ( isset( $del_permalinks )
					&& ! empty( $del_permalinks )
					&& is_array( $del_permalinks )
					&& 0 < count( $del_permalinks )
				) {
					$cp_form = new Custom_Permalinks_Form();
					foreach ( $del_permalinks as $term_id ) {
						if ( is_numeric( $term_id ) ) {
							$cp_form->delete_term_permalink( $term_id );
							++$deleted;
						}
					}
				}
			}
			// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$cp_page     = filter_input( INPUT_GET, 'page' );
			$cp_paged    = filter_input( INPUT_GET, 'paged' );
			$perm_search = filter_input( INPUT_GET, 's' );
			$page_args   = array();

			if ( ! empty( $cp_page ) ) {
				$page_args['page'] = $cp_page;
			} else {
				$page_args['page'] = 'cp-taxonomy-permalinks';
			}

			if ( ! empty( $perm_search ) ) {
				$page_args['s'] = $perm_search;
			}

			if ( ! empty( $cp_paged ) && is_numeric( $cp_paged ) ) {
				$page_args['paged'] = $cp_paged;
			}

			if ( 0 < $deleted ) {
				$page_args['deleted'] = $deleted;
			}

			wp_safe_redirect( add_query_arg( $page_args, admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Generate the table navigation above or below the table.
	 *
	 * @since 2.0.0
	 * @access protected
	 *
	 * @param string $which Table Navigation position.
	 *
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>">
				<?php if ( $this->has_items() ) : ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
					<?php
				endif;

				$this->extra_tablenav( $which );
				$this->pagination( $which );
				?>

				<br class="clear" />
			</div>
		<?php
	}

	/**
	 * Prepare table list items.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Process bulk action.
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( "{$this->screen->id}_per_page" );
		$current_page = $this->get_pagenum();
		$total_items  = Custom_Permalinks_Taxonomies::total_permalinks();
		$this->items  = Custom_Permalinks_Taxonomies::get_permalinks( $per_page, $current_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}
