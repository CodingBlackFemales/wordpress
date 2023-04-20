<?php
/**
 * LearnDash Quiz Questions (sfwd-question) Posts Listing.
 *
 * @since 2.6.0
 * @package LearnDash\Question\Listing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Questions_Listing' ) ) ) {

	/**
	 * Class LearnDash Quiz Questions (sfwd-question) Posts Listing.
	 *
	 * @since 2.6.0
	 * @uses Learndash_Admin_Posts_Listing
	 */
	class Learndash_Admin_Questions_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Collection of deleted Question post IDs.
		 *
		 * @var array $posts_to_delete.
		 */
		protected $posts_to_delete = array();

		/**
		 * Public constructor for class
		 *
		 * @since 2.6.0
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'question' );

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
				'question_type'         => array(
					'type'                   => 'early',
					'display'                => array( $this, 'show_selector_question_type' ),
					'show_all_value'         => '',
					'show_all_label'         => sprintf(
						// translators: placeholder: Question.
						esc_html_x( 'All %s Types', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'listing_query_function' => array( $this, 'filter_by_question_type' ),
					'select2'                => true,
					'select2_fetch'          => false,
				),
				'question_pro_category' => array(
					'type'                   => 'early',
					'display'                => array( $this, 'show_selector_question_pro_category' ),
					'show_all_value'         => '',
					'show_all_label'         => sprintf(
						// translators: placeholder: Question.
						esc_html_x( 'All %s Categories', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'listing_query_function' => array( $this, 'filter_by_question_pro_category' ),
					'select2'                => true,
					'select2_fetch'          => false,
				),
				'quiz_id'               => array(
					'type'                    => 'post_type',
					'post_type'               => learndash_get_post_type_slug( 'quiz' ),
					'show_all_value'          => '',
					'show_all_label'          => sprintf(
						// translators: placeholder: Quizzes.
						esc_html_x( 'All %s', 'placeholder: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quizzes' )
					),
					'show_empty_value'        => 'empty',
					'show_empty_label'        => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '-- No %s --', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'listing_query_function'  => array( $this, 'filter_by_question_quiz_id' ),
					'selector_value_function' => array( $this, 'selector_value_integer' ),
				),
			);

			$this->columns = array(
				'question_type'   => array(
					'label'   => esc_html__( 'Type', 'learndash' ),
					'after'   => 'title',
					'display' => array( $this, 'show_column_question_type' ),
				),
				'question_points' => array(
					'label'   => esc_html__( 'Points', 'learndash' ),
					'after'   => 'question_type',
					'display' => array( $this, 'show_column_question_points' ),
				),
				'quiz'            => array(
					'label'    => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'Assigned %s', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'after'    => 'question_points',
					'display'  => array( $this, 'show_column_step_quiz' ),
					'required' => true,
				),
			);

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Questions_Taxonomies', 'proquiz_question_category' ) == 'yes' ) {
				$this->columns['proquiz_question_category'] = array(
					'label'   => sprintf(
						// translators: placeholder: Question.
						esc_html_x( '%s Category', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'after'   => 'quiz',
					'display' => array( $this, 'show_column_question_proquiz_category' ),
				);
			}

			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) ) {
				unset( $this->columns['quiz'] );
				unset( $this->selectors['quiz_id']['show_empty_value'] );
				unset( $this->selectors['quiz_id']['show_empty_label'] );
			}

			// If Group Leader remove the selector empty option.
			if ( learndash_is_group_leader_user() ) {
				$gl_manage_courses_capabilities = learndash_get_group_leader_manage_courses();
				if ( 'advanced' !== $gl_manage_courses_capabilities ) {
					unset( $this->selectors['quiz_id']['show_empty_value'] );
					unset( $this->selectors['quiz_id']['show_empty_label'] );
				}
			}
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

				add_action( 'admin_footer', array( $this, 'admin_footer' ), 30 );
			}
		}

		/**
		 * Hook into the WP admin footer logic to add custom JavaScript to replace the default page title.
		 *
		 * @since 2.6.0
		 */
		public function admin_footer() {
			global $post_type, $post_type_object;

			if ( $this->post_type_check() ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['quiz_id'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$quiz_id = absint( $_GET['quiz_id'] );
					if ( ! empty( $quiz_id ) ) {
						$quizzes_url = add_query_arg( 'post_type', learndash_get_post_type_slug( 'quiz' ), admin_url( 'edit.php' ) );

						$new_title     = '<a href="' . esc_url( $quizzes_url ) . '">' . LearnDash_Custom_Label::get_label( 'quizzes' ) . '</a> &gt; <a href="' . get_edit_post_link( $quiz_id ) . '">' . get_the_title( $quiz_id ) . '</a> - ' . esc_html( $post_type_object->labels->name );
						$post_new_file = add_query_arg(
							array(
								'post_type' => $post_type,
								'quiz_id'   => $quiz_id,
							),
							'post-new.php'
						);
						$add_new_url   = admin_url( $post_new_file );
						?>
						<script>
							jQuery( function() {
								jQuery( 'h1.wp-heading-inline, .ld-global-header h1' ).html('<?php echo wp_kses_post( $new_title ); ?>' );
								jQuery( 'a.page-title-action, a.global-new-entity-button' ).attr( 'href', '<?php echo esc_url( $add_new_url ); ?>' );
							});
						</script>
						<?php
					}
				}
			}
		}

		/**
		 * Show Question Type column value.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id  The Step post ID shown.
		 * @param array $selector Selector array.
		 */
		protected function show_column_question_type( $post_id = 0, $selector = array() ) {
			global $learndash_question_types;

			$question_type_slug = get_post_meta( $post_id, 'question_type', true );

			$question_values = $this->get_question_values( $post_id );
			if ( ( isset( $question_values['answer_type'] ) ) && ( ! empty( $question_values['answer_type'] ) ) && ( $question_type_slug !== $question_values['answer_type'] ) ) {
				$question_type_slug = $question_values['answer_type'];
				update_post_meta( $post_id, 'question_type', $question_type_slug );
			}

			if ( ( ! empty( $question_type_slug ) ) && ( isset( $learndash_question_types[ $question_type_slug ] ) ) ) {
				$question_type_label = $learndash_question_types[ $question_type_slug ];

				$row_actions = array();

				$filter_url = add_query_arg( 'question_type', $question_type_slug, $this->get_clean_filter_url() );

				$link_aria_label = sprintf(
					// translators: placeholder: Question Type.
					esc_html_x( 'Filter listing by %s type', 'placeholder: Question Type', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'question' )
				);

				echo '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $link_aria_label ) . '">' . esc_attr( $question_type_label ) . '</a>';
				$row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $link_aria_label ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';
				echo $this->list_table_row_actions( $row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
			} else {
				$question_type_label = '-';
			}
		}

		/**
		 * Show Question Points column value.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id  The Step post ID shown.
		 * @param array $selector Selector array.
		 */
		protected function show_column_question_points( $post_id = 0, $selector = array() ) {
			$question_values = $this->get_question_values( $post_id );

			if ( ( ! isset( $question_values['points'] ) ) || ( empty( $question_values['points'] ) ) ) {
				$question_values['points'] = 1;
			}
			echo absint( $question_values['points'] );
		}

		/**
		 * Show Question WPProQuiz Category column value.
		 *
		 * @since 3.2.3
		 * @param int   $post_id  The Step post ID shown.
		 * @param array $selector Selector array.
		 */
		protected function show_column_question_proquiz_category( $post_id = 0, $selector = array() ) {
			$question_values = $this->get_question_values( $post_id );

			if ( ( isset( $question_values['category_id'] ) ) && ( ! empty( $question_values['category_id'] ) ) ) {
				$category_mapper = new WpProQuiz_Model_CategoryMapper();
				$cat             = $category_mapper->fetchById( $question_values['category_id'] );
				if ( ( $cat ) && ( is_a( $cat, 'WpProQuiz_Model_Category' ) ) ) {
					$row_actions = array();
					$filter_url  = add_query_arg( 'question_pro_category', $cat->getCategoryId(), $this->get_clean_filter_url() );
					echo '<a href="' . esc_url( $filter_url ) . '">' . esc_html( stripslashes( $cat->getCategoryName() ) ) . '</a>';
					$row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';
					echo $this->list_table_row_actions( $row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
				}
			}
		}

		/**
		 * Show selector for question type.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector args.
		 */
		protected function show_selector_question_type( $selector = array() ) {
			global $learndash_question_types;

			/**
			 * Filter selector for Question Types.
			 */
			if ( ! empty( $learndash_question_types ) ) {
				$this->show_selector_start( $selector );
				$this->show_selector_all_option( $selector );

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( ( isset( $_GET['question_type'] ) ) && ( ! empty( $_GET['question_type'] ) ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$selected_question_type = sanitize_text_field( wp_unslash( $_GET['question_type'] ) );
				} else {
					$selected_question_type = '';
				}

				foreach ( $learndash_question_types as $q_type => $q_label ) {
					echo '<option value="' . esc_attr( $q_type ) . '" ' . selected( $q_type, $selected_question_type, false ) . '>' . esc_attr( $q_label ) . '</option>';
				}

				$this->show_selector_end( $selector );
			}
		}

		/**
		 * Show selector for legacy WPProQuiz category
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector args.
		 */
		protected function show_selector_question_pro_category( $selector = array() ) {
			$category_mapper         = new WpProQuiz_Model_CategoryMapper();
			$question_pro_categories = $category_mapper->fetchAll();
			if ( ! empty( $question_pro_categories ) ) {

				if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
					$selected_question_pro_category = esc_attr( $selector['selected'] );
				} else {
					$selected_question_pro_category = '';
				}

				$this->show_selector_start( $selector );
				$this->show_selector_all_option( $selector );

				foreach ( $question_pro_categories as $question_pro_category ) {
					echo '<option value="' . absint( $question_pro_category->getCategoryId() ) . '" ' . selected( $question_pro_category->getCategoryId(), $selected_question_pro_category, false ) . '>' . esc_attr( $question_pro_category->getCategoryName() ) . '</option>';
				}

				$this->show_selector_end( $selector );
			}
		}

		/**
		 * Filter listing query by Question Type.
		 *
		 * @since 3.2.3
		 *
		 * @param array $q_vars   Array of query vars.
		 * @param array $selector Selector array.
		 *
		 * @return array $q_vars   Query Args array.
		 */
		protected function filter_by_question_type( $q_vars, $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ! isset( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				}

				$q_vars['meta_query'][] = array(
					'key'     => 'question_type',
					'value'   => esc_attr( $selector['selected'] ),
					'compare' => '=',
				);
			}

			return $q_vars;
		}

		/**
		 * Filter listing query by Question Type.
		 *
		 * @since 3.2.3
		 *
		 * @param array $q_vars   Array of query vars.
		 * @param array $selector Selector array.
		 *
		 * @return array $q_vars   Query Args array.
		 */
		protected function filter_by_question_pro_category( $q_vars, $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ! isset( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				}

				$q_vars['meta_query'][] = array(
					'key'     => 'question_pro_category',
					'value'   => esc_attr( $selector['selected'] ),
					'compare' => '=',
				);
			}

			return $q_vars;
		}

		/**
		 * Filter the main query listing by the quiz_id
		 *
		 * @since 3.2.3
		 *
		 * @param object $q_vars   Query vars used for the table listing.
		 * @param array  $selector Selector array.
		 *
		 * @return object $q_vars.
		 */
		protected function filter_by_question_quiz_id( $q_vars, $selector = array() ) {
			// Holds the included question ids.
			$questions_include = '';

			$quiz_selector = $this->get_selector( 'quiz_id' );
			if ( ( isset( $quiz_selector['selected'] ) ) && ( ! empty( $quiz_selector['selected'] ) ) ) {
				$question_ids = array();

				$quiz_selector = $this->get_selector( 'quiz_id' );
				if ( $quiz_selector['show_empty_value'] === $quiz_selector['selected'] ) {
					$query_args    = array(
						'post_type'      => learndash_get_post_type_slug( 'question' ),
						'posts_per_page' => -1,
						'post_status'    => 'publish',
						'fields'         => 'ids',
						'orderby'        => 'title',
						'order'          => 'ASC',
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							'relation' => 'OR',
							array(
								'key'     => 'quiz_id',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => 'quiz_id',
								'value'   => '0',
								'compare' => '=',
							),
						),
					);
					$query_results = new WP_Query( $query_args );
					if ( ( is_a( $query_results, 'WP_Query' ) ) && ( property_exists( $query_results, 'posts' ) ) && ( ! empty( $query_results->posts ) ) ) {
						$questions_include  = $query_results->posts;
						$q_vars['post__in'] = $query_results->posts;
					} else {
						$q_vars['post__in'] = array( 0 );
					}
				} else {
					$questions_include        = array();
					$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( absint( $quiz_selector['selected'] ) );
					$question_post_ids        = $ld_quiz_questions_object->get_questions();
					if ( ! empty( $question_post_ids ) ) {
						$questions_include = array_keys( $question_post_ids );
					}

					$questions_query_args = array(
						'post_type'      => learndash_get_post_type_slug( 'question' ),
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'orderby'        => 'menu_order',
						'order'          => 'ASC',
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							array(
								'key'     => 'quiz_id',
								'value'   => absint( $quiz_selector['selected'] ),
								'compare' => '=',
							),
						),
					);
					if ( ( isset( $question_post_ids ) ) && ( ! empty( $question_post_ids ) ) ) {
						$questions_query_args['post__not_in'] = $question_post_ids;
					}
					$questions_query = new WP_Query( $questions_query_args );
					if ( ( is_a( $questions_query, 'WP_Query' ) ) && ( property_exists( $questions_query, 'posts' ) ) && ( ! empty( $questions_query->posts ) ) ) {
						$questions_include = array_merge( $questions_include, $questions_query->posts );
						$questions_include = array_unique( $questions_include );
					}

					if ( ! empty( $questions_include ) ) {
						$q_vars['post__in'] = $questions_include;
						$q_vars['orderby']  = 'post__in';
					} else {
						$q_vars['post__in'] = array( 0 );
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Utility function to get the question values.
		 *
		 * @since 3.2.3
		 *
		 * @param int $post_id  The Step post ID shown.
		 */
		protected function get_question_values( $post_id = 0 ) {
			static $field_values = array();

			$post_id = absint( $post_id );
			if ( ! empty( $post_id ) ) {
				if ( ! isset( $field_values[ $post_id ] ) ) {
					$question_pro_id = get_post_meta( $post_id, 'question_pro_id', true );
					if ( ! empty( $question_pro_id ) ) {
						$field_values[ $post_id ] = learndash_get_question_pro_fields( $question_pro_id, array( 'points', 'answer_type', 'category_id', 'category_name' ) );
					} else {
						$field_values[ $post_id ] = array(
							'points'        => '',
							'answer_type'   => 'single',
							'category_id'   => 0,
							'category_name' => '',
						);
					}
				}

				return $field_values[ $post_id ];
			}
		}

		/**
		 * Initial hook for deleting a post.
		 * For the Questions post type we want to also remove the ProQuiz Question. So we grab
		 * the reference from the post meta for 'question_pro_id'.
		 *
		 * @since 3.0.0
		 *
		 * @param integer $post_id $Post ID to be deleted.
		 */
		public function before_delete_post( $post_id = 0 ) {
			global $post_type, $post_type_object;

			if ( ( ! is_admin() ) || ( $post_type !== $this->post_type ) ) {
				return;
			}

			$post_id = absint( $post_id );
			if ( ( ! empty( $post_id ) ) && ( current_user_can( 'delete_post', $post_id ) ) && ( ! isset( $this->posts_to_delete[ $post_id ] ) ) ) {
				$question_pro_id = get_post_meta( $post_id, 'question_pro_id', true );
				if ( ! empty( $question_pro_id ) ) {
					$this->posts_to_delete[ $post_id ] = absint( $question_pro_id );
				}
			}
		}

		/**
		 * Called after the post has been deleted.
		 * Uses registered delete post ID
		 *
		 * @since 3.0.0
		 *
		 * @param integer $post_id $Post ID to be deleted.
		 */
		public function deleted_post( $post_id = 0 ) {
			global $post_type, $post_type_object;

			if ( ( ! is_admin() ) || ( $post_type !== $this->post_type ) ) {
				return;
			}

			if ( ( ! empty( $post_id ) ) && ( current_user_can( 'delete_post', $post_id ) ) && ( isset( $this->posts_to_delete[ $post_id ] ) ) ) {
				global $wpdb;

				$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					LDLMS_DB::get_table_name( 'quiz_question' ),
					array(
						'id' => $this->posts_to_delete[ $post_id ],
					),
					array( '%d' )
				);
				unset( $this->posts_to_delete[ $post_id ] );
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Questions_Listing();
