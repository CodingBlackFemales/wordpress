<?php
/**
 * LearnDash certificates (certificate) Posts Listing Class.
 *
 * @package LearnDash\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Certificates_Listing' ) ) ) {
	/**
	 * Class for LearnDash Certificates Listing Pages.
	 */
	class Learndash_Admin_Certificates_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'certificate' );

			parent::__construct();
		}

		/**
		 * Called via the WordPress init action hook.
		 */
		public function listing_init() {
			if ( $this->listing_init_done ) {
				return;
			}
			$this->selectors = array();

			$this->columns = array(
				'certificate_groups_courses_quizzes' => array(
					'label'   => esc_html__( 'Used in', 'learndash' ),
					'display' => array( $this, 'show_column_certificate_groups_courses_quizzes' ),
					'after'   => 'title',
				),
			);

			parent::listing_init();

			$this->listing_init_done = true;
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 */
		public function on_load_listing() {
			if ( $this->post_type_check() ) {
				parent::on_load_listing();

				if ( isset( $this->columns['certificate_groups_courses_quizzes'] ) ) {
					if ( ( ! current_user_can( 'edit_groups' ) ) && ( ! current_user_can( 'edit_courses' ) ) ) {
						unset( $this->columns['certificate_groups_courses_quizzes'] );
					} elseif ( ( ! learndash_post_meta_processed( learndash_get_post_type_slug( 'course' ) ) ) && ( ! learndash_post_meta_processed( learndash_get_post_type_slug( 'quiz' ) ) ) && ( ! learndash_post_meta_processed( learndash_get_post_type_slug( 'group' ) ) ) ) {
						unset( $this->columns['certificate_groups_courses_quizzes'] );
					}
				}
			}
		}

		/**
		 * Show Group Course Users column.
		 *
		 * @since 3.4.1
		 *
		 * @param int   $post_id     The Step post ID shown.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_certificate_groups_courses_quizzes( $post_id = 0, $column_meta = array() ) {
			if ( ! empty( $post_id ) ) {

				if ( current_user_can( 'edit_courses' ) ) {
					if ( learndash_post_meta_processed( learndash_get_post_type_slug( 'course' ) ) ) {
						$cert_sets = learndash_certificate_get_used_by( $post_id, learndash_get_post_type_slug( 'course' ) );
						if ( ! empty( $cert_sets ) ) {
							$filter_url = add_query_arg(
								array(
									'post_type'      => learndash_get_post_type_slug( 'course' ),
									'certificate_id' => $post_id,
								),
								admin_url( 'edit.php' )
							);

							$link_aria_label = sprintf(
								// translators: placeholder: Courses, Certificate.
								esc_html_x( 'Filter %1$s by Certificate "%2$s"', 'placeholder: Courses, Certificate Post title', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'courses' ),
								get_the_title( $post_id )
							);

							echo sprintf(
								// translators: placeholder: Courses, Certificate Courses Count.
								esc_html_x( '%1$s: %2$s', 'placeholder: Courses, Certificate Courses Count', 'learndash' ),
								esc_attr( learndash_get_custom_label( 'courses' ) ),
								'<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $link_aria_label ) . '">' . count( $cert_sets ) . '</a>'
							);
							echo '<br />';
						}
					}

					if ( learndash_post_meta_processed( learndash_get_post_type_slug( 'course' ) ) ) {
						$cert_sets = learndash_certificate_get_used_by( $post_id, learndash_get_post_type_slug( 'quiz' ) );
						if ( ! empty( $cert_sets ) ) {
							$filter_url = add_query_arg(
								array(
									'post_type'      => learndash_get_post_type_slug( 'quiz' ),
									'certificate_id' => $post_id,
								),
								admin_url( 'edit.php' )
							);

							$link_aria_label = sprintf(
								// translators: placeholder: Quizzes, Certificate Post title.
								esc_html_x( 'Filter %1$s by Certificate "%2$s"', 'placeholder: Quizzes, Certificate Post title', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'quizzes' ),
								get_the_title( $post_id )
							);

							echo sprintf(
								// translators: placeholder: Quizzes, Certificate Quizzes Count.
								esc_html_x( '%1$s: %2$s', 'placeholder: Quizzes, Certificate Quizzes Count', 'learndash' ),
								esc_attr( learndash_get_custom_label( 'quizzes' ) ),
								'<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $link_aria_label ) . '">' . count( $cert_sets ) . '</a>'
							);
							echo '<br />';
						}
					}
				}

				if ( current_user_can( 'edit_groups' ) ) {
					if ( learndash_post_meta_processed( learndash_get_post_type_slug( 'group' ) ) ) {
						$cert_sets = learndash_certificate_get_used_by( $post_id, learndash_get_post_type_slug( 'group' ) );
						if ( ! empty( $cert_sets ) ) {

							$filter_url = add_query_arg(
								array(
									'post_type'      => learndash_get_post_type_slug( 'group' ),
									'certificate_id' => $post_id,
								),
								admin_url( 'edit.php' )
							);

							$link_aria_label = sprintf(
								// translators: placeholder: Groups, Certificate Post title.
								esc_html_x( 'Filter %1$s by Certificate "%2$s"', 'placeholder: Groups, Certificate Post title', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'groups' ),
								get_the_title( $post_id )
							);

							echo sprintf(
								// translators: placeholder: Groups, Certificate Groups Count.
								esc_html_x( '%1$s: %2$s', 'placeholder: Groups, Certificate Groups Count', 'learndash' ),
								esc_attr( learndash_get_custom_label( 'groups' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
								'<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $link_aria_label ) . '">' . count( $cert_sets ) . '</a>'
							);
						}
					}
				}
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Certificates_Listing();
