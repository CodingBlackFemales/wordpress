<?php
/**
 * LearnDash Group Membership.
 *
 * @since 3.2.0
 * @package LearnDash\Groups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LD_Groups_Membership' ) ) {
	/**
	 * Class to create the instance.
	 *
	 * @since 3.2.0
	 */
	class LD_Groups_Membership {
 // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound

		/**
		 * Static instance variable to ensure
		 * only one instance of class is used.
		 *
		 * @var object $instance
		 */
		protected static $instance = null;

		/**
		 * Group Membership metabox instance.
		 *
		 * @var object $mb_instance
		 */
		protected $mb_instance = null;

		/**
		 * Group Membership settings.
		 *
		 * @var array $global_setting
		 */
		protected $global_setting = null;

		/**
		 * Group Membership Post settings.
		 *
		 * @var array $post_setting
		 */
		protected $post_setting = null;

		/**
		 * Array of runtime vars.
		 *
		 * @var array $vars Includes post_id, post, user_id, user, debug.
		 */
		protected $vars = array();

		/**
		 * Get or create instance object of class.
		 *
		 * @since 3.2.0
		 */
		final public static function get_instance() {
			if ( ! isset( static::$instance ) ) {
				static::$instance = new self();
			}

			return static::$instance;
		}

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.0
		 */
		protected function __construct() {
			add_action( 'load-post.php', array( $this, 'on_load' ) );
			add_action( 'load-post-new.php', array( $this, 'on_load' ) );
			add_filter( 'the_content', array( $this, 'the_content_filter' ), 99 );
			add_action( 'load-edit.php', array( $this, 'on_load_edit' ) );
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 *
		 * @since 3.2.0
		 */
		public function on_load_edit() {
			global $typenow, $post;
			global $learndash_assets_loaded;

			if ( in_array( $typenow, $this->get_global_included_post_types(), true ) ) {

				if ( learndash_use_select2_lib() ) {
					if ( ! isset( $learndash_assets_loaded['styles']['learndash-select2-jquery-style'] ) ) {
						wp_enqueue_style(
							'learndash-select2-jquery-style',
							LEARNDASH_LMS_PLUGIN_URL . 'assets/vendor-libs/select2-jquery/css/select2.min.css',
							array(),
							LEARNDASH_SCRIPT_VERSION_TOKEN
						);
						$learndash_assets_loaded['styles']['learndash-select2-jquery-style'] = __FUNCTION__;
					}

					if ( ! isset( $learndash_assets_loaded['scripts']['learndash-select2-jquery-script'] ) ) {
						wp_enqueue_script(
							'learndash-select2-jquery-script',
							LEARNDASH_LMS_PLUGIN_URL . 'assets/vendor-libs/select2-jquery/js/select2.full.min.js',
							array( 'jquery' ),
							LEARNDASH_SCRIPT_VERSION_TOKEN,
							true
						);
						$learndash_assets_loaded['scripts']['learndash-select2-jquery-script'] = __FUNCTION__;
					}
				}

				if ( ! isset( $learndash_assets_loaded['styles']['learndash-admin-settings-bulk-edit'] ) ) {
					wp_enqueue_style(
						'learndash-admin-settings-page',
						LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-settings-bulk-edit' . learndash_min_asset() . '.css',
						array(),
						LEARNDASH_SCRIPT_VERSION_TOKEN
					);
					wp_style_add_data( 'learndash-admin-settings-bulk-edit', 'rtl', 'replace' );
					$learndash_assets_loaded['styles']['learndash-admin-settings-bulk-edit'] = __FUNCTION__;
				}

				if ( ! isset( $learndash_assets_loaded['scripts']['learndash-admin-settings-bulk-edit'] ) ) {
					wp_enqueue_script(
						'learndash-admin-settings-bulk-edit',
						LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-settings-bulk-edit' . learndash_min_asset() . '.js',
						array( 'jquery' ),
						LEARNDASH_SCRIPT_VERSION_TOKEN,
						true
					);
					$learndash_assets_loaded['scripts']['learndash-admin-settings-bulk-edit'] = __FUNCTION__;

				}

				add_filter( 'manage_edit-' . $typenow . '_columns', array( $this, 'add_data_columns' ), 10, 1 );
				add_action( 'manage_' . $typenow . '_posts_custom_column', array( $this, 'posts_custom_column' ), 10, 2 );
				add_action( 'bulk_edit_custom_box', array( $this, 'display_custom_bulk_edit' ), 10, 2 );

				add_action( 'save_post', array( $this, 'save_post_bulk_edit' ), 10, 2 );
			}
		}

		/**
		 * Adds the protection columns in the table listing.
		 *
		 * @global string $typenow
		 *
		 * @since 3.2.1
		 *
		 * @param array $cols An array of columns for admin posts listing.
		 * @return array $cols An array of columns for admin posts listing.
		 */
		public function add_data_columns( $cols = array() ) {
			global $typenow;

			if ( ! isset( $cols['ld_groups_membership'] ) ) {
				$cols['ld_groups_membership'] = sprintf(
					// translators: placeholder Group.
					esc_html_x( '%s Content Protection', 'placeholder Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				);
			}

			return $cols;
		}

		/**
		 * Show the protection columns in the table listing.
		 *
		 * @since 3.2.0
		 *
		 * @param string  $column_name Column name key.
		 * @param integer $post_id     Post ID of row.
		 */
		public function posts_custom_column( $column_name = '', $post_id = 0 ) {
			$column_name = esc_attr( $column_name );
			$post_id     = absint( $post_id );

			if ( ( 'ld_groups_membership' === $column_name ) && ( ! empty( $post_id ) ) ) {
				$settings = learndash_get_post_group_membership_settings( $post_id );
				if ( ( isset( $settings['groups_membership_enabled'] ) ) && ( 'on' === $settings['groups_membership_enabled'] ) ) {
					echo sprintf(
						// translators: placeholder: Groups Compare, Groups Listing link.
						esc_html_x( '%1$s of %2$s', 'placeholder: Groups Compare Type, Groups Listing link', 'learndash' ),
						esc_html( $settings['groups_membership_compare'] ),
						'<a href="' . esc_url(
							add_query_arg(
								array(
									'post_type' => learndash_get_post_type_slug( 'group' ),
									'ld-group-membership-post-id' => $post_id,
								),
								admin_url( 'edit.php' )
							)
						) . '">' . sprintf(
							// translators: placeholder: Count of Groups, Groups.
							esc_html_x( '%1$s %2$s', 'placeholder: Count of Groups, Groups', 'learndash' ),
							count( $settings['groups_membership_groups'] ),
							( 1 === count( $settings['groups_membership_groups'] ) ? LearnDash_Custom_Label::get_label( 'group' ) : LearnDash_Custom_Label::get_label( 'groups' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
						) . '</a>'
					);
				}
			}
		}

		/**
		 * Display bulk edit on table listing
		 *
		 * @since 3.2.3
		 *
		 * @param string $column_name Column name key.
		 * @param string $post_type   Post Type slug.
		 */
		public function display_custom_bulk_edit( $column_name = '', $post_type = '' ) {
			global $sfwd_lms;

			static $print_nonce = true;

			if ( ( 'ld_groups_membership' === $column_name ) && ( in_array( $post_type, $this->get_global_included_post_types(), true ) ) ) {
				if ( $print_nonce ) {
					$print_nonce = false;
					?><input type="hidden" name="learndash_groups_membership[nonce]" value="<?php echo esc_attr( wp_create_nonce( 'learndash_groups_membership_' . $post_type ) ); ?>" />
					<?php
				}

				$select_groups_options = $sfwd_lms->select_a_group();
				if ( ! empty( $select_groups_options ) ) {
					if ( learndash_use_select2_lib() ) {
						$select_groups_options_default = sprintf(
							// translators: placeholder: Group.
							esc_html_x( 'Search or select a %s…', 'placeholder: Group', 'learndash' ),
							learndash_get_custom_label( 'group' )
						);
					} else {
						$select_groups_options_default = array(
							'' => sprintf(
								// translators: placeholder: Group.
								esc_html_x( 'Select %s', 'placeholder: Group', 'learndash' ),
								learndash_get_custom_label( 'group' )
							),
						);
						if ( ( is_array( $select_groups_options ) ) && ( ! empty( $select_groups_options ) ) ) {
							$select_groups_options = $select_groups_options_default + $select_groups_options;
						} else {
							$select_groups_options = $select_groups_options_default;
						}
						$select_groups_options_default = '';
					}

					?>
					<div class="learndash-inline-edit">
						<fieldset class="inline-edit-col-left inline-edit-col-<?php echo esc_attr( $column_name ); ?> inline-edit-col-<?php echo esc_attr( $column_name ); ?>-settings">
							<legend class="inline-edit-legend">
							<?php
								echo sprintf(
									// translators: placeholder: Group.
									esc_html_x( 'LearnDash %s Content Protection', 'placeholder: Group', 'learndash' ),
									LearnDash_Custom_Label::get_label( 'group' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
								);
							?>
								</legend>

							<div class="inline-edit-col ld-inline-edit-col ld-inline-edit-col-left ld-inline-edit-col-settings">
								<label class="ld-group-membership-inline-edit-action">
									<span class="title"><?php echo esc_html__( 'Action', 'learndash' ); ?></span>
									<select name="learndash_groups_membership[action]">
										<option value=""><?php echo esc_html__( '&mdash; No Change &mdash;', 'learndash' ); ?></option>
										<option value="replace"><?php echo esc_html__( 'Replace setting', 'learndash' ); ?></option>
										<option value="add"><?php echo esc_html__( 'Add settings', 'learndash' ); ?></option>
										<option value="remove"><?php echo esc_html__( 'Remove settings', 'learndash' ); ?></option>
									</select>
								</label>
								<label class="ld-group-membership-inline-edit-compare">
									<span class="title"><?php echo esc_html__( 'Compare', 'learndash' ); ?></span>
									<select name="learndash_groups_membership[compare]">
										<option value=""><?php echo esc_html__( '&mdash; No Change &mdash;', 'learndash' ); ?></option>
										<option value="any">
										<?php
										echo sprintf(
											// translators: placeholder: Group.
											esc_html_x( 'Any %s', 'placeholder: Group', 'learndash' ),
											esc_attr( learndash_get_custom_label( 'group' ) )
										);
										?>
										</option>
										<option value="all">
										<?php
										echo sprintf(
											// translators: placeholder: Groups.
											esc_html_x( 'All %s', 'placeholder: Groups', 'learndash' ),
											esc_attr( learndash_get_custom_label( 'groups' ) )
										);
										?>
										</option>
									</select>
								</label>

								<?php
								if ( is_post_type_hierarchical( $post_type ) ) {
									$post_type_object = get_post_type_object( $post_type );
									if ( ( $post_type_object ) && ( is_a( $post_type_object, 'WP_Post_Type' ) ) ) {
										$plural_label = $post_type_object->labels->name;
									} else {
										$plural_label = 'Post';
									}
									?>
										<label class="ld-group-membership-inline-edit-children">
											<span class="title">
											<?php
											echo sprintf(
											// translators: placeholder: Post type plural label.
												esc_html_x( 'Sub-%s', 'placeholder: Post type plural label', 'learndash' ),
												esc_attr( $plural_label )
											);
											?>
												</span>
											<select name="learndash_groups_membership[children]">
												<option value=""><?php echo esc_html__( '&mdash; No Change &mdash;', 'learndash' ); ?></option>
												<option value="yes">
												<?php
												echo sprintf(
												// translators: placeholder: Post type plural label.
													esc_html_x( 'Apply to sub-%s', 'placeholder: Post type plural label', 'learndash' ),
													esc_attr( $plural_label )
												);
												?>
												</option>
												<option value="no">
												<?php
												echo sprintf(
												// translators: placeholder: Post type plural label.
													esc_html_x( 'Do not apply to sub-%s', 'placeholder: Post type plural label', 'learndash' ),
													esc_attr( $plural_label )
												);
												?>
												</option>
											</select>
										</label>
										<?php
								}
								?>
							</div>
						</fieldset>
						<fieldset class="inline-edit-col-right inline-edit-col-<?php echo esc_attr( $column_name ); ?> inline-edit-col-<?php echo esc_attr( $column_name ); ?>-groups">
							<div class="inline-edit-col ld-inline-edit-col ld-inline-edit-col-right ld-inline-edit-col-groups
							<?php
							if ( is_post_type_hierarchical( $post_type ) ) {
								echo ' ld-inline-edit-col-groups-hierarchical'; }
							?>
							">
								<label class="ld-group-membership-inline-edit-groups">
									<span class="title">
									<?php
										echo learndash_get_custom_label( 'groups' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
									?>
									</span>
									<select multiple="" autocomplete="off" name="learndash_groups_membership[groups][]" id="learndash_groups_membership_groups" class="learndash-section-field learndash-section-field-multiselect select2-hidden-accessible"
									placeholder="
									<?php
									echo sprintf(
										// translators: placeholder: Group.
										esc_html_x( 'Search or select a %s…', 'placeholder: Group', 'learndash' ),
										esc_attr( learndash_get_custom_label( 'group' ) )
									);
									?>
									" data-ld-select2="1" data-select2-id="learndash_groups_membership_groups">
									<?php
									foreach ( $select_groups_options as $group_id => $group_title ) {
										?>
											<option value="<?php echo absint( $group_id ); ?>"><?php echo wp_kses_post( apply_filters( 'the_title', $group_title, $group_id ) ); ?></option> <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core Hook ?>
											<?php
									}
									?>
									</select>
								</label>
							</div>
						</fieldset>
					</div>
					<?php
				}
			}
		}

		/**
		 * Save bulk edit changes.
		 *
		 * @since 3.2.3
		 *
		 * @param integer $post_id Post ID.
		 * @param object  $post    WP_Post object.
		 */
		public function save_post_bulk_edit( $post_id, $post ) {
			global $typenow;

			if ( ! in_array( $typenow, $this->get_global_included_post_types(), true ) ) {
				return false;
			}

			if ( ( ! isset( $_GET['learndash_groups_membership']['nonce'] ) ) || ( empty( $_GET['learndash_groups_membership']['nonce'] ) ) || ( ! wp_verify_nonce( esc_attr( $_GET['learndash_groups_membership']['nonce'] ), 'learndash_groups_membership_' . $typenow ) ) ) {
				return false;
			}

			if ( ( ! isset( $_GET['post'] ) ) || ( empty( $_GET['post'] ) ) || ( ! is_array( $_GET['post'] ) ) ) {
				return false;
			}
			$bulk_post_ids = array_map( 'absint', $_GET['post'] );

			if ( ( isset( $_GET['learndash_groups_membership']['groups'] ) ) && ( ! empty( $_GET['learndash_groups_membership']['groups'] ) ) && ( is_array( $_GET['learndash_groups_membership']['groups'] ) ) ) {
				$bulk_group_ids = array_map( 'absint', $_GET['learndash_groups_membership']['groups'] );
			} else {
				$bulk_group_ids = array();
			}

			foreach ( $bulk_post_ids as $bulk_post_id ) {
				$post_group_settings = learndash_get_post_group_membership_settings( $bulk_post_id );

				if ( ( isset( $_GET['learndash_groups_membership']['compare'] ) ) && ( ! empty( $_GET['learndash_groups_membership']['compare'] ) ) ) {
					if ( 'all' === strtolower( $_GET['learndash_groups_membership']['compare'] ) ) {
						$post_group_settings['groups_membership_compare'] = 'ALL';
					} elseif ( 'any' === strtolower( $_GET['learndash_groups_membership']['compare'] ) ) {
						$post_group_settings['groups_membership_compare'] = 'ANY';
					}
				}

				if ( ( is_post_type_hierarchical( $typenow ) ) && ( isset( $_GET['learndash_groups_membership']['children'] ) ) && ( ! empty( $_GET['learndash_groups_membership']['children'] ) ) ) {
					if ( 'yes' === strtolower( $_GET['learndash_groups_membership']['children'] ) ) {
						$post_group_settings['groups_membership_children'] = 'on';
					} elseif ( 'no' === strtolower( $_GET['learndash_groups_membership']['children'] ) ) {
						$post_group_settings['groups_membership_children'] = '';
					}
				}

				if ( ( isset( $_GET['learndash_groups_membership']['action'] ) ) && ( ! empty( $_GET['learndash_groups_membership']['action'] ) ) ) {
					if ( 'replace' === $_GET['learndash_groups_membership']['action'] ) {
						$post_group_settings['groups_membership_groups'] = $bulk_group_ids;
					} elseif ( 'add' === $_GET['learndash_groups_membership']['action'] ) {
						$post_group_settings['groups_membership_groups'] = array_merge( $post_group_settings['groups_membership_groups'], $bulk_group_ids );
						$post_group_settings['groups_membership_groups'] = array_unique( $post_group_settings['groups_membership_groups'] );
					} elseif ( 'remove' === $_GET['learndash_groups_membership']['action'] ) {
						if ( ! empty( $bulk_group_ids ) ) {
							$post_group_settings['groups_membership_groups'] = array_diff( $post_group_settings['groups_membership_groups'], $bulk_group_ids );
						} else {
							$post_group_settings['groups_membership_groups'] = array();
						}
					}
				}

				learndash_set_post_group_membership_settings( $bulk_post_id, $post_group_settings );
			}
		}

		/**
		 * Get Group Membership post metabox instance.
		 *
		 * @since 3.2.0
		 */
		protected function get_metabox_instance() {
			if ( is_null( $this->mb_instance ) ) {
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-group-membership-post-settings.php';
				$this->mb_instance = LearnDash_Settings_Metabox_Group_Membership_Post_Settings::add_metabox_instance();
			}

			return $this->mb_instance;
		}

		/**
		 * Initialize runtime vars.
		 *
		 * @since 3.2.0
		 */
		protected function init_vars() {
			$this->vars['post_id'] = get_the_ID();
			if ( ! empty( $this->vars['post_id'] ) ) {
				$this->vars['post'] = get_post( $this->vars['post_id'] );
			}

			if ( is_user_logged_in() ) {
				$this->vars['user_id'] = get_current_user_id();
				if ( ! empty( $this->vars['user_id'] ) ) {
					$this->vars['user'] = get_user_by( 'ID', $this->vars['user_id'] );
				}
			} else {
				$this->vars['user_id'] = 0;
			}

			if ( ( ! is_admin() ) && ( isset( $_GET['ld_debug'] ) ) ) {
				$this->vars['debug'] = true;
			} else {
				$this->vars['debug'] = false;
			}

			$this->vars['debug_messages'] = array();
		}

		/**
		 * Add debug message to array.
		 *
		 * @since 3.2.0
		 *
		 * @param string $message Message text to add.
		 */
		protected function add_debug_message( $message = '' ) {
			if ( ( isset( $this->vars['debug'] ) ) && ( true === $this->vars['debug'] ) ) {
				$this->vars['debug_messages'][] = $message;
			}
		}

		/**
		 * Output debug message.
		 *
		 * @since 3.2.0
		 */
		protected function output_debug_messages() {
			if ( ( isset( $this->vars['debug'] ) ) && ( true === $this->vars['debug'] ) && ( ! empty( $this->vars['debug_messages'] ) ) ) {
				echo '<code>';
				echo implode( '<br />', array_map( 'wp_kses_post', $this->vars['debug_messages'] ) );
				echo '<br /></code><br />';
			}
		}

		/**
		 * Load the Groups Membership Global settings
		 *
		 * @since 3.2.0
		 */
		protected function init_global_settings() {
			if ( is_null( $this->global_setting ) ) {
				$this->global_setting = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Groups_Membership' );
			}

			if ( ! isset( $this->global_setting['groups_membership_enabled'] ) ) {
				$this->global_setting['groups_membership_enabled'] = '';
			}

			if ( ! isset( $this->global_setting['groups_membership_message'] ) ) {
				$this->global_setting['groups_membership_message'] = '';
			}

			if ( ! isset( $this->global_setting['groups_membership_post_types'] ) ) {
				$this->global_setting['groups_membership_post_types'] = array();
			}

			if ( ! isset( $this->global_setting['groups_membership_user_roles'] ) ) {
				$this->global_setting['groups_membership_user_roles'] = array();
			}
		}

		/**
		 * Get the managed membership post types.
		 *
		 * @since 3.2.0
		 */
		protected function get_global_included_post_types() {
			$included_post_types = array();

			$this->init_global_settings();

			if ( ! empty( $this->global_setting['groups_membership_enabled'] ) ) {
				if ( ( is_array( $this->global_setting['groups_membership_post_types'] ) ) && ( ! empty( $this->global_setting['groups_membership_post_types'] ) ) ) {
					$included_post_types = $this->global_setting['groups_membership_post_types'];
				}
			}

			return $included_post_types;
		}

		/**
		 * Get Group Membership excluded user roles.
		 *
		 * @since 3.2.0
		 */
		protected function get_excluded_user_roles() {
			$excluded_user_roles = array();

			$this->init_global_settings();

			if ( ! empty( $this->global_setting['groups_membership_enabled'] ) ) {
				if ( ( is_array( $this->global_setting['groups_membership_user_roles'] ) ) && ( ! empty( $this->global_setting['groups_membership_user_roles'] ) ) ) {
					$excluded_user_roles = $this->global_setting['groups_membership_user_roles'];
				}
			}

			return $excluded_user_roles;
		}

		/**
		 * Get Group Membership access denied message.
		 *
		 * @since 3.2.0
		 */
		protected function get_access_denied_message() {
			static $inline_css_loaded = false;

			$access_denied_message = '';

			$this->init_global_settings();

			if ( ! empty( $this->global_setting['groups_membership_enabled'] ) ) {
				$access_denied_message = $this->global_setting['groups_membership_message'];

				if ( ( learndash_is_active_theme( 'ld30' ) ) && ( function_exists( 'learndash_get_template_part' ) ) ) {

					/**
					 * Filter to show alert message box used in LD30 templates.
					 *
					 * @since 3.2.0
					 *
					 * @param boolean $show_alert true.
					 * @param int     $post_id    Current Post ID.
					 * @param int     $user_id    Current User ID.
					 * @return boolean True to process template. Anything else to abort.
					 */
					if ( true === apply_filters( 'learndash_group_membership_access_denied_show_ld30_alert', true, $this->vars['post_id'], $this->vars['user_id'] ) ) {
						if ( false === $inline_css_loaded ) {
							$inline_css_loaded      = true;
							$css_front_file_content = '.learndash-wrapper .ld-alert a.ld-button.learndash-group-membership-link { text-decoration: none !important; }';
							wp_add_inline_style( 'learndash-front-group-membership', $css_front_file_content );
						}

						$alert = array(
							'icon'    => 'alert',
							'message' => $access_denied_message,
							'type'    => 'warning',
						);

						if ( ( 1 === count( $this->post_setting['groups_membership_groups'] ) ) && ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'public' ) ) ) {

							$alert['button'] = array(
								'url'   => get_permalink( $this->post_setting['groups_membership_groups'][0] ),
								'class' => 'learndash-link-previous-incomplete learndash-group-membership-link',
								'label' => sprintf(
									// translators: placeholder: Group.
									esc_html_x( 'View %s', 'placeholder: Group', 'learndash' ),
									learndash_get_custom_label( 'group' )
								),
							);
						}

						$access_denied_message = learndash_get_template_part( 'modules/alert.php', $alert, false );
						$access_denied_message = '<div class="learndash-wrapper">' . $access_denied_message . '</div>';
					}
				}
			}

			return $access_denied_message;
		}

		/**
		 * Get Group Membership Post metabox setting
		 *
		 * @since 3.2.0
		 *
		 * @param integer $post_id Post ID to get settings for.
		 *
		 * @return array of settings.
		 */
		protected function init_post_settings( $post_id = 0 ) {
			$this->post_setting = learndash_get_post_group_membership_settings( $post_id );
			return $this->post_setting;
		}

		/**
		 * Get the managed membership post groups.
		 *
		 * @since 3.2.0
		 *
		 * @param integer $post_id Post ID to get settings for.
		 *
		 * @return array of post groups.
		 */
		protected function get_post_included_groups( $post_id = 0 ) {
			$included_post_groups = array();

			if ( ! empty( $post_id ) ) {
				$this->init_post_settings( $post_id );

				if ( ! empty( $this->post_setting['groups_membership_enabled'] ) ) {
					if ( ( is_array( $this->post_setting['groups_membership_groups'] ) ) && ( ! empty( $this->post_setting['groups_membership_groups'] ) ) ) {
						$included_post_groups = $this->post_setting['groups_membership_groups'];
					}
				}
			}

			$this->add_debug_message( __FUNCTION__ . ': post_id [' . $post_id . '] post included groups [' . implode( ', ', $included_post_groups ) . ']' );

			return $included_post_groups;
		}

		/**
		 * Get the managed membership post groups compare.
		 *
		 * @since 3.2.0
		 *
		 * @param integer $post_id Post ID to get settings for.
		 *
		 * @return array of post groups.
		 */
		protected function get_post_groups_compare( $post_id = 0 ) {
			$post_groups_compare = '';

			if ( ! empty( $post_id ) ) {
				$this->init_post_settings( $post_id );

				if ( ! empty( $this->post_setting['groups_membership_enabled'] ) ) {
					$post_groups_compare = $this->post_setting['groups_membership_compare'];
				}
			}
			$this->add_debug_message( __FUNCTION__ . ': post_id [' . $post_id . '] post groups compare[' . $post_groups_compare . ']' );

			return $post_groups_compare;
		}

		/**
		 * Check if post type is managed by membership logic.
		 *
		 * @since 3.2.0
		 *
		 * @param string $post_type Post type slug to check.
		 */
		protected function is_included_post_type( $post_type = '' ) {
			if ( ( ! empty( $post_type ) ) && ( in_array( $post_type, $this->get_global_included_post_types(), true ) ) ) {
				$this->add_debug_message( __FUNCTION__ . ': post_type [' . $post_type . '] is included.' );
				return true;
			}
			$this->add_debug_message( __FUNCTION__ . ': post_type [' . $post_type . '] NOT included.' );
		}

		/**
		 * Check if user_role is excluded by membership logic.
		 *
		 * @since 3.2.0
		 *
		 * @param integer $user_id User ID.
		 */
		protected function is_excluded_user_role( $user_id = 0 ) {
			$this->add_debug_message( __FUNCTION__ . ': user_id [' . $user_id . '] ' );
			if ( ! empty( $user_id ) ) {
				$user = get_user_by( 'ID', $user_id );
				if ( ( is_object( $user ) ) && ( property_exists( $user, 'roles' ) ) && ( ! empty( $user->roles ) ) ) {
					$user_roles          = array_map( 'esc_attr', $user->roles );
					$excluded_user_roles = $this->get_excluded_user_roles();
					$excluded_user_roles = array_map( 'esc_attr', $excluded_user_roles );
					if ( ! empty( $excluded_user_roles ) ) {
						$this->add_debug_message( __FUNCTION__ . ': user_roles [' . implode( ', ', $user_roles ) . '] excluded_roles [' . implode( ', ', $excluded_user_roles ) . ']' );
						if ( array_intersect( $user_roles, $excluded_user_roles ) ) {
							$this->add_debug_message( __FUNCTION__ . ': user role excluded.' );
							return true;
						}
						$this->add_debug_message( __FUNCTION__ . ': user role NOT excluded.' );
					}
				}
			}
		}

		/**
		 * Check if Post is enabled and if the post type is included in the global settings.
		 *
		 * @since 3.2.0
		 *
		 * @param integer $post_id Post ID.
		 *
		 * @return boolean
		 */
		protected function is_post_blocked( $post_id = 0 ) {
			$this->add_debug_message( __FUNCTION__ . ': post_id [' . $post_id . ']' );

			if ( is_preview() || is_admin() ) {
				$this->add_debug_message( __FUNCTION__ . ': is_preview or is_admin true. aborting.' );
				return false;
			}

			if ( ! empty( $post_id ) ) {
				$this->init_post_settings( $post_id );

				if ( $this->is_included_post_type( get_post_type( $post_id ) ) ) {
					if ( ( ! empty( $this->post_setting['groups_membership_enabled'] ) ) && ( ! empty( $this->post_setting['groups_membership_groups'] ) ) ) {
						$this->add_debug_message( __FUNCTION__ . ': post type [' . get_post_type( $post_id ) . '] is under membership control.' );
						return true;
					}
				}
				$this->add_debug_message( __FUNCTION__ . ': post type [' . get_post_type( $post_id ) . '] not under membership control. bypassed' );
			}

			return false;
		}

		/**
		 * Check if User enrolled groups against Post and Membership settings.
		 *
		 * @since 3.2.0
		 *
		 * @param integer $post_id Post ID.
		 * @param integer $user_id USer ID.
		 *
		 * @return boolean
		 */
		protected function is_user_blocked( $post_id = 0, $user_id = 0 ) {
			if ( ! empty( $post_id ) ) {
				$this->init_post_settings( $post_id );

				if ( ! empty( $user_id ) ) {
					if ( $this->is_excluded_user_role( $user_id ) ) {
						$this->add_debug_message( __FUNCTION__ . ': user role excluded. bypassed.' );
						return false;
					} else {
						$this->add_debug_message( __FUNCTION__ . ': user role not excluded. blocked.' );
					}

					if ( $this->is_user_in_post_groups( $post_id, $user_id ) ) {
						$this->add_debug_message( __FUNCTION__ . ': user in post groups. bypassed.' );
						return false;
					} else {
						$this->add_debug_message( __FUNCTION__ . ': user not in post groups. blocked.' );
					}
				} else {
					$post_groups = $this->get_post_included_groups( $post_id );
					if ( empty( $post_groups ) ) {
						$this->add_debug_message( __FUNCTION__ . ': empty post groups. bypassed.' );
						return false;
					} else {
						$this->add_debug_message( __FUNCTION__ . ': empty user. post groups exists. blocked.' );
					}
				}
				return true;
			}
			return true;
		}

		/**
		 * Check if user if in the associated post membership groups.
		 *
		 * @since 3.2.0
		 *
		 * @param integer $post_id Post ID.
		 * @param integer $user_id User ID.
		 */
		protected function is_user_in_post_groups( $post_id = 0, $user_id = 0 ) {
			if ( ( ! empty( $user_id ) ) && ( ! empty( $post_id ) ) ) {
				$this->init_post_settings( $post_id );

				$post_groups = $this->get_post_included_groups( $post_id );
				$post_groups = array_map( 'absint', $post_groups );
				if ( ! empty( $post_groups ) ) {
					$user_groups = learndash_get_users_group_ids( $user_id );
					$user_groups = array_map( 'absint', $user_groups );
					if ( ! empty( $user_groups ) ) {
						$groups_compare = $this->get_post_groups_compare( $post_id );

						$common_groups = array_intersect( $user_groups, $post_groups );
						if ( 'ANY' === $groups_compare ) {
							if ( ! empty( $common_groups ) ) {
								$this->add_debug_message( __FUNCTION__ . ': user is in ANY groups.' );
								return true;
							}
							$this->add_debug_message( __FUNCTION__ . ': user not in ANY groups.' );
						} elseif ( 'ALL' === $groups_compare ) {
							if ( empty( array_diff( $common_groups, $post_groups ) ) && empty( array_diff( $post_groups, $common_groups ) ) ) {
								$this->add_debug_message( __FUNCTION__ . ': user is in ALL groups.' );
								return true;
							}
							$this->add_debug_message( __FUNCTION__ . ': user not in ALL groups.' );
						}
					} else {
						$this->add_debug_message( __FUNCTION__ . ': user groups empty.' );
					}
				}
			}
		}

		/**
		 * Called when the Post is Added or Edited.
		 *
		 * @since 3.2.0
		 */
		public function on_load() {
			global $typenow;

			if ( $this->is_included_post_type( $typenow ) ) {
				add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
				$this->get_metabox_instance();
			}
		}

		/**
		 * Called when the Post is Saved.
		 *
		 * @since 3.2.0
		 *
		 * @param integer $post_id Post ID.
		 * @param object  $post    WP_Post instance.
		 * @param boolean $update  If update to post.
		 */
		public function save_post( $post_id = 0, $post = null, $update = null ) {
			if ( $this->is_included_post_type( $post->post_type ) ) {
				$mb_instance = $this->get_metabox_instance();
				$mb_instance->save_post_meta_box( $post_id, $post, $update );
			}

			return true;
		}

		/**
		 * Start the logic to filter the content.
		 *
		 * @since 3.2.0
		 *
		 * @param string/HTML $content The Post content.
		 */
		public function the_content_filter( $content ) {
			if ( is_preview() || is_admin() ) {
				return $content;
			}

			$this->init_vars();

			if ( ( ! isset( $this->vars['post'] ) ) || ( ! is_a( $this->vars['post'], 'WP_Post' ) ) ) {
				return $content;
			}

			$post_blocked = $this->is_post_blocked( $this->vars['post_id'] );
			$user_blocked = $this->is_user_blocked( $this->vars['post_id'], $this->vars['user_id'] );

			$this->add_debug_message( __FUNCTION__ . ': post_blocked[' . $post_blocked . '] user_blocked[' . $user_blocked . ']' );

			if ( ( true === $post_blocked ) && ( true === $user_blocked ) ) {
				$this->add_debug_message( __FUNCTION__ . ': blocked.' );
				$this->output_debug_messages();

				return $this->get_access_denied_message();
			} else {
				$this->add_debug_message( __FUNCTION__ . ': not blocked.' );
				$this->output_debug_messages();

				return $content;
			}
		}

		// End of functions.
	}
	add_action(
		'init',
		function() {
			LD_Groups_Membership::get_instance();
		},
		10,
		1
	);
}

/**
 * Utility function to get the post group membership settings.
 *
 * @since 3.2.0
 *
 * @param integer $post_id Post ID.
 * @return array Array of settings.
 */
function learndash_get_post_group_membership_settings( $post_id = 0 ) {
	$learndash_settings = array();

	if ( ! empty( $post_id ) ) {
		$is_hierarchical = is_post_type_hierarchical( get_post_type( $post_id ) );

		$learndash_settings['groups_membership_enabled'] = get_post_meta( $post_id, '_ld_groups_membership_enabled', true );
		$learndash_settings['groups_membership_compare'] = get_post_meta( $post_id, '_ld_groups_membership_compare', true );
		$learndash_settings['groups_membership_groups']  = learndash_get_post_group_membership_groups( $post_id );

		if ( ( ! isset( $learndash_settings['groups_membership_enabled'] ) ) || ( 'on' !== $learndash_settings['groups_membership_enabled'] ) ) {
			$learndash_settings['groups_membership_enabled'] = '';
		}

		if ( ( ! isset( $learndash_settings['groups_membership_compare'] ) ) || ( empty( $learndash_settings['groups_membership_compare'] ) ) ) {
			$learndash_settings['groups_membership_compare'] = 'ANY';
		}

		if ( ! isset( $learndash_settings['groups_membership_groups'] ) ) {
			$learndash_settings['groups_membership_groups'] = array();
		}

		if ( ( 'on' === $learndash_settings['groups_membership_enabled'] ) && ( true === $is_hierarchical ) ) {
			$learndash_settings['groups_membership_children'] = get_post_meta( $post_id, '_ld_groups_membership_children', true );
			if ( ( ! isset( $learndash_settings['groups_membership_children'] ) ) || ( 'on' !== $learndash_settings['groups_membership_children'] ) ) {
				$learndash_settings['groups_membership_children'] = '';
			}
		} else {
			$learndash_settings['groups_membership_children'] = '';
		}

		if ( ( ! empty( $learndash_settings['groups_membership_groups'] ) ) && ( 'on' === $learndash_settings['groups_membership_enabled'] ) ) {
			$learndash_settings['groups_membership_groups'] = learndash_validate_groups( $learndash_settings['groups_membership_groups'] );
			if ( empty( $learndash_settings['groups_membership_groups'] ) ) {
				$learndash_settings['groups_membership_enabled']  = '';
				$learndash_settings['groups_membership_children'] = '';
			}
		} else {
			$learndash_settings['groups_membership_enabled']  = '';
			$learndash_settings['groups_membership_groups']   = array();
			$learndash_settings['groups_membership_children'] = '';
		}

		if ( ( empty( $learndash_settings['groups_membership_enabled'] ) ) && ( true === $is_hierarchical ) ) {
			$parents_post_id = wp_get_post_parent_id( $post_id );
			if ( ! empty( $parents_post_id ) ) {
				$parent_settings = learndash_get_post_group_membership_settings( $parents_post_id );
				if ( ( isset( $parent_settings['groups_membership_enabled'] ) ) && ( 'on' === $parent_settings['groups_membership_enabled'] ) ) {
					if ( ( isset( $parent_settings['groups_membership_children'] ) ) && ( 'on' === $parent_settings['groups_membership_children'] ) ) {
						$parent_settings['groups_membership_parent'] = absint( $parents_post_id );
						$learndash_settings                          = $parent_settings;
					}
				}
			}
		}
	}

	return $learndash_settings;
}

/**
 * Utility function to set the post group membership settings.
 *
 * @since 3.2.0
 *
 * @param integer $post_id  Post ID.
 * @param array   $settings Array of settings.
 */
function learndash_set_post_group_membership_settings( $post_id = 0, $settings = array() ) {
	if ( ! empty( $post_id ) ) {

		$default_settings = array(
			'groups_membership_enabled'  => '',
			'groups_membership_children' => '',
			'groups_membership_compare'  => 'ANY',
			'groups_membership_groups'   => array(),
		);

		$settings = wp_parse_args( $settings, $default_settings );

		if ( empty( $settings['groups_membership_compare'] ) ) {
			$settings['groups_membership_compare'] = 'ANY';
		}
		if ( ! is_array( $settings['groups_membership_groups'] ) ) {
			$settings['groups_membership_groups'] = array();
		} elseif ( ! empty( $settings['groups_membership_groups'] ) ) {
			$settings['groups_membership_groups'] = array_map( 'absint', $settings['groups_membership_groups'] );
		}

		if ( ! empty( $settings['groups_membership_groups'] ) ) {
			$settings['groups_membership_enabled'] = 'on';
		} else {
			$settings['groups_membership_enabled']  = '';
			$settings['groups_membership_children'] = '';
			$settings['groups_membership_compare']  = '';
		}

		foreach ( $settings as $_key => $_val ) {
			if ( 'groups_membership_groups' === $_key ) {
				learndash_set_post_group_membership_groups( $post_id, $_val );
			} else {
				if ( empty( $_val ) ) {
					delete_post_meta( $post_id, '_ld_' . $_key );
				} else {
					update_post_meta( $post_id, '_ld_' . $_key, $_val );
				}
			}
		}
	}
}

/**
 * Get the Groups related to the Post for Group Membership.
 *
 * @since 3.2.0
 *
 * @param integer $post_id Post ID.
 * @return array Array of settings.
 */
function learndash_get_post_group_membership_groups( $post_id = 0 ) {
	$group_ids = array();

	$post_id = absint( $post_id );
	if ( ! empty( $post_id ) ) {
		$post_meta = get_post_meta( $post_id );
		if ( ! empty( $post_meta ) ) {
			foreach ( $post_meta as $meta_key => $meta_set ) {
				if ( '_ld_groups_membership_group_' == substr( $meta_key, 0, strlen( '_ld_groups_membership_group_' ) ) ) {
					$group_id = str_replace( '_ld_groups_membership_group_', '', $meta_key );
					$group_id = absint( $group_id );
					if ( learndash_get_post_type_slug( 'group' ) === get_post_type( $group_id ) ) {
						$group_ids[] = $group_id;
					}
				}
			}
		}
	}

	return $group_ids;
}

/**
 * Set the Groups related to the Post for Group Membership.
 *
 * @since 3.2.0
 *
 * @param int   $post_id    Post ID to update.
 * @param array $groups_new Array of group IDs to set for the Post ID. Can be empty.
 */
function learndash_set_post_group_membership_groups( $post_id = 0, $groups_new = array() ) {
	$post_id = absint( $post_id );
	if ( ! is_array( $groups_new ) ) {
		$groups_new = array();
	} elseif ( ! empty( $groups_new ) ) {
		$groups_new = array_map( 'absint', $groups_new );
	}

	if ( ! empty( $post_id ) ) {

		$groups_old = learndash_get_post_group_membership_groups( $post_id );
		if ( ! is_array( $groups_old ) ) {
			$groups_old = array();
		} elseif ( ! empty( $groups_old ) ) {
			$groups_old = array_map( 'absint', $groups_old );
		}

		$groups_intersect = array_intersect( $groups_new, $groups_old );

		$groups_add = array_diff( $groups_new, $groups_intersect );
		if ( ! empty( $groups_add ) ) {
			foreach ( $groups_add as $group_id ) {
				add_post_meta( $post_id, '_ld_groups_membership_group_' . $group_id, time() );
			}
		}

		$groups_remove = array_diff( $groups_old, $groups_intersect );
		if ( ! empty( $groups_remove ) ) {
			foreach ( $groups_remove as $group_id ) {
				delete_post_meta( $post_id, '_ld_groups_membership_group_' . $group_id );
			}
		}
	}
}
