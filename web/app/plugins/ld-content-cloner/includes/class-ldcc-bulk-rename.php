<?php
/**
 * This file contains a class for handling Bulk Rename feature.
 *
 * @package Content Cloner.
 */

namespace LDCC_Bulk_Rename;

/**
 * This class handles the bulk rename feature.
 */
class LDCC_Bulk_Rename {

	/**
	 * This method is used to register the submenu page for Course Bulk Rename.
	 */
	public function bulk_rename_submenu_page() {
		add_submenu_page(
			'learndash-lms',
			sprintf(__( '%s Bulk Rename', 'ld-content-cloner' ), \LearnDash_Custom_Label::get_label( 'course' ) ),
			sprintf(__( '%s Bulk Rename', 'ld-content-cloner' ), \LearnDash_Custom_Label::get_label( 'course' ) ),
			'edit_courses',
			'learndash-course-bulk-rename',
			array( $this, 'bulk_rename_page_callback' )
		);
	}

	/**
	 * This method is used to show the bulk rename structure.
	 */
	public function bulk_rename_page_callback() {
		$args              = array(
			'post_type'      => 'sfwd-courses',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => -1,
		);
		$user_id           = get_current_user_id();
		$course_ids_shared = get_user_meta( $user_id, 'ir_shared_courses', 1 );
		$courses_shared    = array();
		if ( ! empty( $course_ids_shared ) ) {
			$course_ids_shared = explode( ',', $course_ids_shared );
			$courses_shared    = array_map(
				function( $course_id ) {
					return get_post( $course_id );
				},
				$course_ids_shared
			);
		}
		$courses         = get_posts( $args );
		$courses         = array_merge( $courses, $courses_shared );
		$selected_course = filter_input( INPUT_GET, 'ldbr-select-course', FILTER_VALIDATE_INT );
		$selected        = '';

		$this->add_slider();

		// disable topic transient.
		add_filter( 'learndash_transients_disabled', array( $this, 'disable_topic_transient' ), 20, 2 );

		?>
		<div>
			<h2><?php echo esc_html( sprintf( __( '%s Bulk Rename', 'ld-content-cloner' ), \LearnDash_Custom_Label::get_label( 'course' ) ) ); ?></h2>
			<?php
				$this->add_course_list( $courses, $selected_course );
			?>

			<div>
					<?php
					if ( ! empty( $selected_course ) ) {
						$lesson_ids = array();
						$topic_ids  = array();
						$quiz_ids   = array();
						echo '<form id="" name="">';

						$c_quizzes = array();
						$l_quizzes = array();
						$t_quizzes = array();

						$lessons = learndash_get_course_lessons_list( $selected_course, null, array( 'num' => 0 ) );

						$c_quizzes = learndash_get_course_quiz_list( $selected_course );
						if ( ! empty( $lessons ) ) {
							foreach ( $lessons as $lesson ) {
								$lesson_ids[ $lesson['post']->ID ] = $lesson['post']->post_title;

								$topics = learndash_get_topic_list( $lesson['post']->ID, $selected_course );
								if ( ! empty( $topics ) ) {
									foreach ( $topics as $topic ) {
										$topic_ids[ $topic->ID ] = $topic->post_title;
										$t_quizzes               = array_merge( $t_quizzes, learndash_get_lesson_quiz_list( $topic->ID, '', $selected_course ) );
									}
								}
								unset( $topics );

								$l_quizzes = array_merge( $l_quizzes, learndash_get_lesson_quiz_list( $lesson['post']->ID, '', $selected_course ) );
							}
						}

						$quizzes = array_merge( $c_quizzes, $l_quizzes, $t_quizzes );
						if ( ! empty( $quizzes ) ) {
							foreach ( $quizzes as $quiz ) {
								$quiz_ids[ $quiz['post']->ID ] = $quiz['post']->post_title;
							}
						}

						echo "<table class='ldbr-table'>";
						echo "<tr class='ldbr-head-row'><th>" . __( 'Post Type', 'ld-content-cloner' ) . "</th><th>" . __( 'Post Title', 'ld-content-cloner' ) . "</th><th>" . __( "New Title", 'ld-content-cloner' ) . "</th></tr>";
						$this->ldcc_display_renaming( $selected_course, get_the_title( $selected_course ) );

						foreach ( $lesson_ids as $id => $title ) {
							$this->ldcc_display_renaming( $id, $title );
						}

						foreach ( $topic_ids as $id => $title ) {
							$this->ldcc_display_renaming( $id, $title );
						}

						foreach ( $quiz_ids as $id => $title ) {
							$this->ldcc_display_renaming( $id, $title );
						}

						echo '<tr class="ldbr-foot-row">
                                    <td colspan="3">
                                        <input type="hidden" name="ldbr_security" id="ldbr_security" value="' . esc_attr( wp_create_nonce( 'bulk_renaming' ) ) . '" />
                                        <input type="button" class="button button-primary" name="save_post_titles" id="save_post_titles" data-lock="0" value="' . esc_attr__( 'Save New Titles', 'ld-content-cloner' ) . '" />
                                    </td>
                                </tr>';
						echo '</table></form>';
					}
					?>
			</div>

		</div>
		<?php
		unset( $selected );
	}

	/**
	 * This method is used to disable topic transient.
	 *
	 * @param  boolean $ld_transient_disabled True/False.
	 * @param  string  $transient_key         Transient Key.
	 */
	public function disable_topic_transient( $ld_transient_disabled, $transient_key ) {
		unset( $ld_transient_disabled );
		if ( strpos( $transient_key, 'learndash_lesson_topics_' ) !== false ) {
			return true;
		}
	}

	/**
	 * This method is used to add values for bulk rename.
	 *
	 * @param array   $courses         Array of Courses.
	 * @param integer $selected_course Selected Course ID.
	 */
	public function add_course_list( $courses, $selected_course ) {
		?>
			<form action="" method="get" id="ldbr-select-form" name="ldbr-select-form">
				<select id="ldbr-select-course" name="ldbr-select-course">
					<option value="0"> <?php echo esc_html( sprintf( __( '( ID ) Select %s', 'ld-content-cloner' ), \LearnDash_Custom_Label::get_label( 'course' ) ) ); ?> </option>
					<?php
					$selected = '';
					foreach ( $courses as $sin_course ) {
						if ( ! empty( $selected_course ) ) {
							$selected = selected( $selected_course, $sin_course->ID, 0 );
						}

						?>
						<option value="
						<?php
						echo esc_attr( $sin_course->ID );
						?>
						" 
						<?php
						echo esc_attr( $selected );
						?>
						>
						<?php
						echo '( ' . esc_html( $sin_course->ID ) . ' ) ' . esc_html( $sin_course->post_title );
						?>
						</option>
						<?php
					}
					?>
				</select>
				<input type="hidden" name="page" value="learndash-course-bulk-rename" />
				<input type="submit" class="button button-primary" id="ldbr-select-button" name="ldbr-select-button" value="<?php esc_html_e( 'Select Course', 'ld-content-cloner' ); ?>" />
			</form>
		<?php
	}

	/**
	 * This method is used to add the ads slider.
	 */
	public function add_slider() {
		?>
		<div class="wrap">
		<?php
		$slider_loc = 'bulk_rename';
		$slider_loc = $slider_loc;
		require_once 'ldcc-slider.php';
		?>
		</div>
		<?php
	}

	/**
	 * Added hyperlink to edit contents. Edited hyperlink title.
	 *
	 * @param integer $post_id Post ID.
	 * @param string  $title   Post Title.
	 */
	public function ldcc_display_renaming( $post_id, $title ) {
		$shared_steps_course = '';
		if ( class_exists( '\LearnDash_Settings_Section' ) ) {
			$shared_steps_course = \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' );
		}
		$obj         = get_post_type_object( get_post_type( $post_id ) );
		$lesson_id   = '';
		$lesson_name = '';
		if ( 'yes' !== $shared_steps_course ) {
			if ( 'sfwd-topic' === get_post_type( $post_id ) ) {
				$lesson_id = get_post_meta( $post_id, 'lesson_id', true );
				if ( '' !== $lesson_id ) {
					$lesson_name = "<a href='" . esc_url( get_edit_post_link( $lesson_id ) ) . "' title = 'Edit This Lesson'>" . get_the_title( $lesson_id ) . '</a> -> ';
				}
			}
		}
		echo "<tr class='ldbr-row'>
                <td class='ldbr-post-type'> " . esc_html( $obj->labels->singular_name ) . "</td>
                <td class='ldbr-post-title'>" . $lesson_name . "<a title = 'Edit This " . esc_attr( $obj->labels->singular_name ) . "' href='" . esc_url( get_edit_post_link( $post_id ) ) . "'>" . esc_html( $title ) . " </a></td>
                <td> <input class='ldbr-post-new-title' type='text' data-post-id='" . esc_attr( $post_id ) . "' value='" . esc_attr( $title ) . "'> </td>
            </tr>";
	}

	/**
	 * This method is used for implementing bulk rename.
	 */
	public function bulk_rename_callback() {
		$security = filter_input( INPUT_POST, 'security', FILTER_SANITIZE_STRING );

		if ( wp_verify_nonce( $security, 'bulk_renaming' ) ) {
			$rename_data = filter_input( INPUT_POST, 'course_data' );
			$rename_data = (array) json_decode( $rename_data );
			foreach ( $rename_data as $post_id => $new_title ) {
				if ( get_the_title( $post_id ) !== trim( $new_title ) ) {
					$this->update_post( $post_id, $new_title );
				}
			}
			echo wp_json_encode( array( 'success' => __( 'All Post Titles Updated.', 'ld-content-cloner' ) ) );
		} else {
			echo wp_json_encode( array( 'error' => __( 'Security check failed.', 'ld-content-cloner' ) ) );
		}
		die();
	}

	/**
	 * This method is used to update post.
	 *
	 * @param  integer $post_id   Post ID.
	 * @param  string  $new_title New Post Title.
	 */
	public function update_post( $post_id, $new_title ) {
		$post_arr = array(
			'ID'         => $post_id,
			'post_title' => $new_title,
		);

		if ( ! empty( $post_id ) ) {
			$post = get_post( $post_id );
		}
		
		if ( get_post_status( $post_id ) === 'publish' ) {
			$new_slug              = sanitize_title( $post->post_title );
			$post_arr['post_name'] = $new_slug;
		}

		wp_update_post( $post_arr );
		unset( $post_id );
		unset( $post );
	}
}
