<?php
/**
 * Group Leader Groups Listing.
 *
 * @since 2.1.2
 * @package LearnDash\Group_Users
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Groups_Users_List' ) ) {

	/**
	 * Class Group Leader Groups Listing.
	 *
	 * @since 2.1.2
	 */
	class Learndash_Admin_Groups_Users_List {

		/**
		 * List table object
		 *
		 * @var object $list_table Post List table instance
		 */
		public $list_table;

		/**
		 * Form method
		 *
		 * @var string $form_method Form Method
		 */
		public $form_method = 'get';

		/**
		 * Title
		 *
		 * @var string $title Title
		 */
		public $title = '';

		/**
		 * Current table action
		 *
		 * @var string $current_action Current table action
		 */
		public $current_action = '';

		/**
		 * Group ID
		 *
		 * @var integer $group_id Group ID
		 */
		public $group_id = 0;

		/**
		 * User ID
		 *
		 * @var integer $user_id User ID
		 */
		public $user_id = 0;

		/**
		 * Public constructor for class
		 *
		 * @since 2.1.2
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'learndash_group_admin_menu' ) );
		}

		/**
		 * Register Group Administration submenu page
		 *
		 * @since 2.1.2
		 */
		public function learndash_group_admin_menu() {

			$menu_user_cap = '';
			$menu_parent   = '';
			$position      = 0;

			if ( current_user_can( 'edit_groups' ) ) {
				$user_group_ids = learndash_get_administrators_group_ids( get_current_user_id(), true );
				if ( ! empty( $user_group_ids ) ) {
					$menu_user_cap = 'edit_groups';
					$menu_parent   = 'edit.php?post_type=groups';
					$position      = null; // Let the position be natural.
				}
			} elseif ( learndash_is_group_leader_user() ) {
				$user_group_ids = learndash_get_administrators_group_ids( get_current_user_id(), true );
				if ( ! empty( $user_group_ids ) ) {
					$menu_user_cap = LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK;
					$menu_parent   = 'learndash-lms';

					if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_courses_enabled' ) === 'yes' ) {
						$position = 6; // Position to the top for Group Leader.
					} else {
						$position = 0; // Position to the top for Group Leader.
					}
				}
			}

			if ( ! empty( $menu_user_cap ) ) {
				global $submenu;

				$page_hook = add_submenu_page(
					$menu_parent,
					LearnDash_Custom_Label::get_label( 'groups' ),
					LearnDash_Custom_Label::get_label( 'groups' ),
					$menu_user_cap,
					'group_admin_page',
					array( $this, 'show_page' ),
					$position
				);
				add_action( 'load-' . $page_hook, array( $this, 'on_load' ) );

				if ( ( isset( $submenu['learndash-lms'] ) ) && ( ! empty( $submenu['learndash-lms'] ) ) ) {
					foreach ( $submenu['learndash-lms'] as $menu_idx => &$menu_item ) {
						if ( ( isset( $menu_item['2'] ) ) && ( 'group_admin_page' === $menu_item['2'] ) ) {
							if ( ! isset( $menu_item['4'] ) ) {
								$menu_item['4'] = 'submenu-ldlms-groups';
							}
						}
					}
				}
			}
		}

		/**
		 * On page load
		 *
		 * @since 2.1.2
		 */
		public function on_load() {
			global $learndash_assets_loaded;

			if ( ( isset( $_GET['action'] ) ) && ( ! empty( $_GET['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->current_action = sanitize_text_field( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			if ( ( isset( $_GET['group_id'] ) ) && ( ! empty( $_GET['group_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->group_id = intval( $_GET['group_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			if ( ( isset( $_GET['user_id'] ) ) && ( ! empty( $_GET['user_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->user_id = intval( $_GET['user_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			wp_enqueue_style(
				'sfwd-module-style',
				LEARNDASH_LMS_PLUGIN_URL . '/assets/css/sfwd_module' . learndash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'sfwd-module-style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['sfwd-module-style'] = __FUNCTION__;

			wp_enqueue_script(
				'sfwd-module-script',
				LEARNDASH_LMS_PLUGIN_URL . '/assets/js/sfwd_module' . learndash_min_asset() . '.js',
				array( 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
			$learndash_assets_loaded['scripts']['sfwd-module-script'] = __FUNCTION__;

			// Because we need the ajaxurl for the pagination AJAX.
			$data = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			);

			$data = array( 'json' => wp_json_encode( $data ) );
			wp_localize_script( 'sfwd-module-script', 'sfwd_data', $data );

			$filepath = SFWD_LMS::get_template( 'learndash_pager.css', null, null, true );
			if ( ! empty( $filepath ) ) {
				wp_enqueue_style( 'learndash_pager_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
				$learndash_assets_loaded['styles']['learndash_pager_css'] = __FUNCTION__;
			}

			$filepath = SFWD_LMS::get_template( 'learndash_pager.js', null, null, true );
			if ( ! empty( $filepath ) ) {
				wp_enqueue_script( 'learndash_pager_js', learndash_template_url_from_path( $filepath ), array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
				$learndash_assets_loaded['scripts']['learndash_pager_js'] = __FUNCTION__;
			}

			if ( empty( $this->current_action ) ) {

				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-groups-users-list-table.php';
				$this->list_table = new Learndash_Admin_Groups_Users_List_Table();
				$screen           = get_current_screen();

				$screen_key = $screen->id;
				if ( ! empty( $this->group_id ) ) {
					$screen_key .= '_users';
				} else {
					$screen_key .= '_groups';
				}
				$screen_key .= '_per_page';

				$screen_per_page_option = str_replace( '-', '_', $screen_key );

				if ( isset( $_POST['wp_screen_options']['option'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

					if ( isset( $_POST['wp_screen_options']['value'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$per_page = intval( $_POST['wp_screen_options']['value'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
						if ( ( ! $per_page ) || ( $per_page < 1 ) ) {
							$per_page = 20;
						}
						update_user_meta( get_current_user_id(), $screen_per_page_option, $per_page );
					}
				}
				$per_page = get_user_meta( get_current_user_id(), $screen_per_page_option, true );
				if ( ( ! $per_page ) || ( $per_page < 1 ) ) {
					$per_page = 20;
				}

				$this->title = '';

				$this->list_table->per_page = $per_page;
				add_screen_option(
					'per_page',
					array(
						'label'   => esc_html__( 'per Page', 'learndash' ),
						'default' => $per_page,
					)
				);

				if ( ( ! empty( $this->group_id ) ) && ( ! empty( $this->user_id ) ) ) {

					$this->on_process_actions_list();

					$this->form_method = 'post';

					$user = get_user_by( 'id', $this->user_id );
					return;
				} elseif ( ! empty( $this->group_id ) ) {
					$group_post = get_post( $this->group_id );
					if ( $group_post ) {
						$this->list_table->group_id = $this->group_id;

						$this->list_table->columns['username']     = esc_html__( 'Username', 'learndash' );
						$this->list_table->columns['name']         = esc_html__( 'Name', 'learndash' );
						$this->list_table->columns['email']        = esc_html__( 'Email', 'learndash' );
						$this->list_table->columns['user_actions'] = esc_html__( 'Actions', 'learndash' );

						return;
					}
				}
			} elseif ( 'learndash-group-email' == $this->current_action ) {

				$group_post = get_post( $this->group_id );
				if ( $group_post ) {
					return;
				}
			}

			$this->list_table->columns['group_name']    = LearnDash_Custom_Label::get_label( 'groups' );
			$this->list_table->columns['group_actions'] = esc_html__( 'Actions', 'learndash' );
		}

		/**
		 * Show page
		 *
		 * @since 2.3.0
		 */
		public function show_page() {
			?>
			<div class="wrap wrap-learndash-group-list">
				<hr class="wp-header-end">
				<?php if ( ! empty( $this->title ) ) { ?>
				<h2><?php echo wp_kses_post( $this->title ); ?></h2>
				<?php } ?>
				<?php
					$current_user = wp_get_current_user();
				if ( ( ! learndash_is_group_leader_user( $current_user ) ) && ( ! learndash_is_admin_user( $current_user ) ) ) {
					die(
						sprintf(
							// translators: placeholder: Group.
							esc_html_x( 'Please login as a %s Administrator', 'placeholder: Group', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'group' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
						)
					);
				}
				?>
				<div class="wrap-learndash-view-content">
					<?php
					if ( 'learndash-group-email' == $this->current_action ) {
						?>
						<input id="group_email_ajaxurl" type="hidden" name="group_email_ajaxurl" value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" />
						<input id="group_email_group_id" type="hidden" name="group_email_group_id" value="<?php echo absint( $this->group_id ); ?>" />
						<input id="group_email_nonce" type="hidden" name="group_email_nonce" value="<?php echo esc_attr( wp_create_nonce( 'group_email_nonce_' . $this->group_id . '_' . $current_user->ID ) ); ?>" />

						<!-- Email Group feature below the Group Table (on the Group Leader page) -->
						<table class="form-table">
							<tr>
								<th scope="row"><label for="group_email_sub"><?php esc_html_e( 'Email Subject:', 'learndash' ); ?></label></th>
								<td><input id="group_email_sub" rows="5" class="regular-text group_email_sub"/></td>
							</tr>
							<tr>
								<th scope="row"><label for="text"><strong><?php esc_html_e( 'Email Message:', 'learndash' ); ?></strong></label></th>
								<td><div class="groupemailtext" >
								<?php
								wp_editor(
									'',
									'groupemailtext',
									array(
										'media_buttons' => true,
										'wpautop'       => true,
									)
								);
								?>
								</div></td>
							</tr>
						</table>

						<p>
							<button id="email_group" class="button button-primary" type="button"><?php esc_html_e( 'Send', 'learndash' ); ?></button>
							<button id="email_reset" class="button button-secondary" type="button"><?php esc_html_e( 'Reset', 'learndash' ); ?></button><br />
							<span class="empty_status" style="color: red; display: none;"><?php esc_html_e( 'Both Email Subject and Message are required and cannot be empty.', 'learndash' ); ?></span>
							<span class="sending_status" style="display: none;"><?php esc_html_e( 'Sending...', 'learndash' ); ?></span>
							<span class="sending_result" style="display: none;"></span>
						</p>
						<?php
					} else {

						$this->list_table->views();
						?>
						<form id="learndash-view-form" action="" method="<?php echo esc_attr( $this->form_method ); ?>">
							<input type="hidden" name="page" value="group_admin_page" />
							<?php
							if ( empty( $this->user_id ) ) {
								?>
									<input type="hidden" name="user_id" value="<?php echo absint( $this->user_id ); ?>" />
									<?php
									$this->list_table->check_table_filters();
									$this->list_table->prepare_items();

									if ( ! empty( $this->group_id ) ) {
										?>
										<input type="hidden" name="group_id" value="<?php echo absint( $this->group_id ); ?>" />
										<?php
										$this->list_table->search_box( esc_html__( 'Search Users', 'learndash' ), 'users' );
									} else {
										$this->list_table->search_box(
											sprintf(
											// translators: placeholder: Groups.
												esc_html_x( 'Search %s', 'placeholder: Groups', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'groups' )
											),
											'groups'
										);
									}
									wp_nonce_field( 'ld-group-list-view-nonce-' . get_current_user_id(), 'ld-group-list-view-nonce' );
									$this->list_table->display();
							} else {
								$group_user_ids = learndash_get_groups_user_ids( $this->group_id );
								if ( ! empty( $group_user_ids ) ) {
									if ( in_array( $this->user_id, $group_user_ids, true ) ) {
										$atts = array(
											'user_id'      => $this->user_id,
											'group_id'     => $this->group_id,
											'progress_num' => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'progress_num' ),
											'progress_orderby' => 'title',
											'progress_order' => 'ASC',
											'quiz_num'     => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'quiz_num' ),
											'quiz_orderby' => 'taken',
											'quiz_order'   => 'DESC',
										);

										/**
										 * Filters group administration course info attributes.
										 *
										 * @since 2.5.7
										 *
										 * @param array         $atts An array of group admin course info attributes.
										 * @param WP_User|false $user User Object.
										 */
										$atts = apply_filters( 'learndash_group_administration_course_info_atts', $atts, get_user_by( 'id', $this->user_id ) );

										echo learndash_course_info_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML

										if ( learndash_show_user_course_complete( $this->user_id ) ) {
											echo submit_button( esc_html__( 'Update User', 'learndash' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}
									}
								}
							}
							?>
						</form>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Handle actions from table
		 *
		 * @since 2.3.0
		 *
		 * @return void
		 */
		public function on_process_actions_list() {
			if ( ! empty( $this->user_id ) ) {
				learndash_save_user_course_complete( $this->user_id );
			}
		}

		// End of functions.
	}
}

/**
 * Handle Groups Table AJAX for Reports.
 *
 * @since 2.3.0
 *
 * @return void
 */
function learndash_data_group_reports_ajax() {
	$reply_data = array( 'status' => false );

	if ( ( is_user_logged_in() ) && ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) ) {
		if ( ( isset( $_POST['nonce'] ) ) && ( ! empty( $_POST['nonce'] ) ) && ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ld-group-list-view-nonce-' . get_current_user_id() ) ) ) {
			if ( ( isset( $_POST['data'] ) ) && ( ! empty( $_POST['data'] ) ) ) {
				$ld_admin_settings_data_reports = new Learndash_Admin_Settings_Data_Reports();
				$reply_data['data']             = $ld_admin_settings_data_reports->do_data_reports( $_POST['data'], $reply_data ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				echo wp_json_encode( $reply_data );
			}
		}
	}

	wp_die(); // this is required to terminate immediately and return a proper response.
}

add_action( 'wp_ajax_learndash_data_group_reports', 'learndash_data_group_reports_ajax' );
