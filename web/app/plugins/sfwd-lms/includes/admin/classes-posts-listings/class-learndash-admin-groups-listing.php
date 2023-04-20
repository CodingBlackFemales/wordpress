<?php
/**
 * LearnDash Groups (groups) Posts Listing.
 *
 * @since 3.2.0
 * @package LearnDash\Group\Listing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Groups_Listing' ) ) ) {

	/**
	 * Class LearnDash Groups (groups) Posts Listing.
	 *
	 * @since 3.2.0
	 * @uses Learndash_Admin_Posts_Listing
	 */
	class Learndash_Admin_Groups_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.0
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'group' );

			parent::__construct();
		}

		/**
		 * Called via the WordPress init action hook.
		 *
		 * @since 3.2.3
		 */
		public function listing_init() {
			if ( $this->listing_init_done ) {
				return;
			}
			$this->selectors = array(
				'user_id'        => array(
					'type'                     => 'user',
					'show_all_value'           => '',
					'show_all_label'           => esc_html__( 'All Users', 'learndash' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_author' ),
					'selector_value_function'  => array( $this, 'selector_value_for_author' ),
				),
				'price_type'     => array(
					'type'                   => 'early',
					'display'                => array( $this, 'show_selector_price_type' ),
					'show_all_value'         => '',
					'show_all_label'         => esc_html__( 'All Price Types', 'learndash' ),
					'options'                => array(
						'free'      => esc_html__( 'Free', 'learndash' ),
						'paynow'    => esc_html__( 'Buy now', 'learndash' ),
						'subscribe' => esc_html__( 'Recurring', 'learndash' ),
						'closed'    => esc_html__( 'Closed', 'learndash' ),
					),
					'listing_query_function' => array( $this, 'filter_listing_by_price_type' ),
					'select2'                => true,
					'select2_fetch'          => false,
				),
				'course_id'      => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'course' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'All %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'listing_query_function'   => array( $this, 'selector_filter_for_group_course' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_course' ),
					'selector_filters'         => array( 'group_id' ),
				),
				'certificate_id' => array(
					'type'                   => 'post_type',
					'post_type'              => learndash_get_post_type_slug( 'certificate' ),
					'show_all_value'         => '',
					'show_all_label'         => esc_html__( 'All Certificates', 'learndash' ),
					'show_empty_value'       => 'empty',
					'show_empty_label'       => esc_html__( '-- No Certificate --', 'learndash' ),
					'listing_query_function' => array( $this, 'listing_filter_by_certificate' ),
				),
			);

			$this->columns = array(
				'groups_courses_users' => array(
					'label'   => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( '%s / Users', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'display' => array( $this, 'show_column_course_users' ),
					'after'   => 'title',
				),
				'price_type'           => array(
					'label'    => esc_html__( 'Price Type', 'learndash' ),
					'after'    => 'title',
					'display'  => array( $this, 'show_column_price_type' ),
					'required' => true,
				),
			);

			add_action( 'admin_notices', array( $this, 'nonpublic_groups_warning' ) );

			parent::listing_init();

			$this->listing_init_done = true;
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 *
		 * @since 3.2.3
		 */
		public function on_load_listing() {
			if ( $this->post_type_check() ) {
				parent::on_load_listing();

				add_filter( 'learndash_listing_table_query_vars_filter', array( $this, 'listing_table_query_vars_filter_groups' ), 30, 3 );

				/**
				 * Convert the Group Post Meta items.
				 *
				 * @since 3.4.1
				 */
				$ld_data_upgrade_group_post_meta = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Group_Post_Meta' );
				if ( ( $ld_data_upgrade_group_post_meta ) && ( is_a( $ld_data_upgrade_group_post_meta, 'Learndash_Admin_Data_Upgrades_Group_Post_Meta' ) ) ) {
					$ld_data_upgrade_group_post_meta->process_post_meta( false );
				}
			}
		}

		/**
		 * Listing table query vars
		 *
		 * @since 3.2.3
		 *
		 * @param array  $q_vars    Array of query vars.
		 * @param string $post_type Post Type being displayed.
		 * @param array  $query     Main Query.
		 */
		public function listing_table_query_vars_filter_groups( $q_vars, $post_type, $query ) {
			if ( $post_type === $this->post_type ) {
				if ( ! learndash_is_admin_user() ) {
					if ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'basic' === learndash_get_group_leader_manage_groups() ) ) {
						$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
						$group_ids = array_map( 'absint', $group_ids );
						if ( ! empty( $group_ids ) ) {
							if ( empty( $q_vars['post__in'] ) ) {
								$q_vars['post__in'] = $group_ids;
							} else {
								$q_vars['post__in'] = array_intersect( $q_vars['post__in'], $group_ids );
								if ( empty( $q_vars['post__in'] ) ) {
									$q_vars['post__in'] = array( 0 );
									return $q_vars;
								}
							}
						} else {
							$q_vars['post__in'] = array( 0 );
							return $q_vars;
						}
					}
				}

				$user_selector = $this->get_selector( 'user_id' );
				if ( ( $user_selector ) && ( isset( $user_selector['selected'] ) ) && ( ! empty( $user_selector['selected'] ) ) ) {
					$group_ids = learndash_get_users_group_ids( $user_selector['selected'], true );
					$group_ids = array_map( 'absint', $group_ids );
					if ( ! empty( $group_ids ) ) {
						if ( empty( $q_vars['post__in'] ) ) {
							$q_vars['post__in'] = $group_ids;
						} else {
							$q_vars['post__in'] = array_intersect( $q_vars['post__in'], $group_ids );
							if ( empty( $q_vars['post__in'] ) ) {
								$q_vars['post__in'] = array( 0 );
								return $q_vars;
							}
						}
					} else {
						$q_vars['post__in'] = array( 0 );
						return $q_vars;
					}
				}

				// Filter the Groups listing for the Group Membership for the Post.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( ( isset( $_GET['ld-group-membership-post-id'] ) ) && ( ! empty( $_GET['ld-group-membership-post-id'] ) ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$group_membership_settings = learndash_get_post_group_membership_settings( absint( $_GET['ld-group-membership-post-id'] ) );
					if ( ! empty( $group_membership_settings['groups_membership_groups'] ) ) {
						$group_ids = $group_membership_settings['groups_membership_groups'];
						if ( empty( $q_vars['post__in'] ) ) {
							$q_vars['post__in'] = $group_ids;
						} else {
							$q_vars['post__in'] = array_intersect( $q_vars['post__in'], $group_ids );
							if ( empty( $q_vars['post__in'] ) ) {
								$q_vars['post__in'] = array( 0 );
								return $q_vars;
							}
						}
					} else {
						$q_vars['post__in'] = array( 0 );
						return $q_vars;
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Show Group Course Users column.
		 *
		 * @since 3.2.3
		 *
		 * @param int    $post_id     The Step post ID shown.
		 * @param string $column_name Column name/slug.
		 */
		protected function show_column_course_users( $post_id = 0, $column_name = '' ) {
			if ( ( ! empty( $post_id ) ) && ( 'groups_courses_users' === $column_name ) ) {
				$hidden = (array) get_hidden_columns( get_current_screen()->id );
				if ( in_array( $column_name, $hidden, true ) ) {
					echo esc_html__( 'reload', 'learndash' );
					return;
				}

				$group_users = learndash_get_groups_user_ids( $post_id );
				if ( ( empty( $group_users ) ) || ( ! is_array( $group_users ) ) ) {
					$group_users = array();
				}

				echo sprintf(
					// translators: placeholder: Group Users Count.
					esc_html_x( 'Users: %d', 'placeholder: Group Users Count', 'learndash' ),
					count( $group_users )
				);
				echo '<br />';

				// Group Courses.
				$group_courses = learndash_group_enrolled_courses( $post_id );
				if ( ( empty( $group_courses ) ) || ( ! is_array( $group_courses ) ) ) {
					$group_courses = array();
				}

				echo sprintf(
					// translators: placeholder: Group Courses Count.
					esc_html_x( '%1$s: %2$d', 'placeholders: Courses, Group Courses Count', 'learndash' ),
					learndash_get_custom_label( 'courses' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					count( $group_courses )
				);
				echo '<br />';

				// Group Leaders.
				$group_leaders = learndash_get_groups_administrator_ids( $post_id );
				if ( ( empty( $group_leaders ) ) || ( ! is_array( $group_leaders ) ) ) {
					$group_leaders = array();
				}
				printf(
					// translators: placeholder: Group Leaders Count.
					esc_html_x( 'Leaders %d', 'placeholder: Group Leaders Count', 'learndash' ),
					count( $group_leaders )
				);
			}
		}
		/**
		 * Filter the main query listing by the course_id
		 *
		 * @since 3.2.3
		 *
		 * @param  array $q_vars   Query vars used for the table listing.
		 * @param  array $selector Array of attributes used to display the filter selector.
		 * @return array $q_vars.
		 */
		protected function selector_filter_for_group_course( $q_vars = array(), $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				$course_group_ids = learndash_get_course_groups( absint( $selector['selected'] ), true );
				if ( ! empty( $course_group_ids ) ) {
					if ( ! isset( $q_vars['post__in'] ) ) {
						$q_vars['post__in'] = array();
					}
					if ( empty( $q_vars['post__in'] ) ) {
						$q_vars['post__in'] = $course_group_ids;
					} else {
						$q_vars['post__in'] = array_intersect( $q_vars['post__in'], $course_group_ids );
					}
				} else {
					$q_vars['post__in'] = array( 0 );
				}
			}

			return $q_vars;
		}

		/**
		 * Show selector for price type.
		 *
		 * @since 3.4.1
		 *
		 * @param array $selector Selector args.
		 */
		protected function show_selector_price_type( $selector = array() ) {

			if ( ( isset( $selector['options'] ) ) && ( ! empty( $selector['options'] ) ) ) {
				$this->show_selector_start( $selector );
				$this->show_selector_all_option( $selector );

				if ( ( isset( $_GET['price_type'] ) ) && ( ! empty( $_GET['price_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$selected_price_type = sanitize_text_field( wp_unslash( $_GET['price_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				} else {
					$selected_price_type = '';
				}

				foreach ( $selector['options'] as $price_type_key => $price_type_label ) {
					echo '<option value="' . esc_attr( $price_type_key ) . '" ' . selected( $price_type_key, $selected_price_type, false ) . '>' . esc_attr( $price_type_label ) . '</option>';
				}

				$this->show_selector_end( $selector );
			}
		}

		/**
		 * This function fill filter the table listing items by the selected Price Type.
		 * Called via 'parse_query' filter from WP.
		 *
		 * @since 3.4.2
		 *
		 * @param  object $q_vars Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function filter_listing_by_price_type( $q_vars, $selector ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( learndash_post_meta_processed( $this->post_type ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}

					$q_vars['meta_query'][] = array(
						'key'   => '_ld_price_type',
						'value' => $selector['selected'],
					);
				} else {
					$post_ids = learndash_get_posts_by_price_type( $this->post_type, $selector['selected'] );
					if ( ! empty( $post_ids ) ) {
						$q_vars['post__in'] = $post_ids;
					} else {
						$q_vars['post__in'] = array( 0 );
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Show Price Type column.
		 *
		 * @since 3.4.1
		 *
		 * @param int    $post_id     The Step post ID shown.
		 * @param string $column_name Column name/slug being processed.
		 */
		protected function show_column_price_type( $post_id = 0, $column_name = '' ) {
			if ( ( ! empty( $post_id ) ) && ( 'price_type' === $column_name ) ) {
				$price_type_key = learndash_get_setting( $post_id, 'group_price_type' );
				if ( ! empty( $price_type_key ) ) {
					if ( isset( $this->selectors[ $column_name ]['options'][ $price_type_key ] ) ) {
						$price_type_label = $this->selectors[ $column_name ]['options'][ $price_type_key ];

						$row_actions = array();

						$filter_url = add_query_arg( 'price_type', $price_type_key, $this->get_clean_filter_url() );

						$link_aria_label = esc_html__( 'Filter listing by Price type', 'learndash' );

						echo '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $link_aria_label ) . '">' . esc_attr( $price_type_label ) . '</a>';
						$row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $link_aria_label ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';
						echo $this->list_table_row_actions( $row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
					}
				}
			}

		}

		/**
		 * Returns message if groups are not set to Public
		 *
		 * @since 3.4.2
		 */
		public function nonpublic_groups_warning() {
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'public' ) !== 'yes' ) {
				$message = learndash_groups_get_not_public_message();
				if ( ! empty( $message ) ) {
					echo wp_kses_post( $message );
				}
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Groups_Listing();
