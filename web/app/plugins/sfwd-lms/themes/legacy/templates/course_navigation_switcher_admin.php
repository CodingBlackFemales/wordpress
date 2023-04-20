<?php
/**
 * Displays the Course Switcher displayed within the Associate Content admin widget.
 * Available Variables:
 * none
 *
 * @since 2.5.0
 *
 * @package LearnDash\Templates\Legacy\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
	$learndash_edit_post = get_post( intval( $_GET['post'] ) );
	if ( is_a( $learndash_edit_post, 'WP_Post' ) && ( in_array( $learndash_edit_post->post_type, learndash_get_post_types( 'course_steps' ), true ) ) ) {
		$learndash_cb_courses      = learndash_get_courses_for_step( $learndash_edit_post->ID );
		$learndash_count_primary   = 0;
		$learndash_count_secondary = 0;

		if ( isset( $learndash_cb_courses['primary'] ) ) {
			$learndash_count_primary = count( $learndash_cb_courses['primary'] );
		}

		if ( isset( $learndash_cb_courses['secondary'] ) ) {
			$learndash_count_secondary = count( $learndash_cb_courses['secondary'] );
		}

		if ( ( $learndash_count_primary > 0 ) || ( $learndash_count_secondary > 0 ) ) {

			$learndash_use_select_opt_groups = false;
			if ( ( $learndash_count_primary > 0 ) && ( $learndash_count_secondary > 0 ) ) {
				$learndash_use_select_opt_groups = true;
			}

			$learndash_default_course_id = learndash_get_course_id( $learndash_edit_post->ID, true );

			$learndash_course_post_id = 0;
			if ( isset( $_GET['course_id'] ) ) {
				$learndash_course_post_id = intval( $_GET['course_id'] );
			}

			if ( ( empty( $learndash_course_post_id ) ) && ( ! empty( $learndash_default_course_id ) ) ) {
				$learndash_course_post_id = absint( $learndash_default_course_id );
			}

			?><p class="widget_course_switcher">
			<?php
			// translators: placeholder: Course.
			echo sprintf( esc_html_x( '%s switcher', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'Course' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<br />
			<span class="ld-course-message" style="display:none">
			<?php
			// translators: placeholder: Course.
			echo sprintf( esc_html_x( 'Switch to the Primary %s to edit this setting', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</span>
			<input type="hidden" id="ld-course-primary" name="ld-course-primary" value="<?php echo absint( $learndash_default_course_id ); ?>" />

			<?php
				$learndash_item_url = get_edit_post_link( $learndash_edit_post->ID );
			?>
			<select name="ld-course-switcher" id="ld-course-switcher">
				<option value="">
				<?php
				// translators: placeholder: Course.
				echo sprintf( esc_html_x( 'Select a %s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'Course' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				</option>
				<?php
				if ( ( learndash_get_post_type_slug( 'quiz' ) === $learndash_edit_post->post_type ) && ( empty( $learndash_count_primary ) ) && ( empty( $learndash_count_secondary ) ) ) {
					?>
						<option selected="selected" data-course_id="0" value="<?php echo esc_url( remove_query_arg( 'course_id', $learndash_item_url ) ); ?>">
						<?php
						// translators: placeholder: Quiz.
						echo sprintf( esc_html_x( 'Standalone %s', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'Quiz' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
						</option>
						<?php
				}
				?>
				<?php
				$learndash_selected_course_id = 0;
				foreach ( $learndash_cb_courses as $learndash_course_key => $learndash_course_set ) {
					if ( true === $learndash_use_select_opt_groups ) {
						if ( 'primary' === $learndash_course_key ) {
							?>
							<optgroup label="
							<?php
							// translators: placeholder: Course.
							echo sprintf( esc_html_x( 'Primary %s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'Course' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
							">
							<?php
						} elseif ( 'secondary' === $learndash_course_key ) {
							?>
							<optgroup label="
							<?php
							// translators: placeholder: Courses.
							echo sprintf( esc_html_x( 'Shared %s', 'placeholder: Courses', 'learndash' ), LearnDash_Custom_Label::get_label( 'Courses' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
							">
							<?php
						}
					}

					foreach ( $learndash_course_set as $learndash_course_id => $learndash_course_title ) {
						$learndash_item_url = add_query_arg( 'course_id', $learndash_course_id, $learndash_item_url );

						$learndash_selected = '';
						if ( learndash_get_post_type_slug( 'quiz' ) === $learndash_edit_post->post_type ) {
							if ( $learndash_course_id == $learndash_course_post_id ) {
								$learndash_selected           = ' selected="selected" ';
								$learndash_selected_course_id = $learndash_course_id;
							}
						} else {
							if ( ( $learndash_course_id == $learndash_course_post_id ) || ( ( empty( $learndash_course_post_id ) ) && ( $learndash_course_id == $learndash_default_course_id ) ) ) {
								$learndash_selected           = ' selected="selected" ';
								$learndash_selected_course_id = $learndash_course_id;
							}
						}
						?>
						<option <?php echo esc_attr( $learndash_selected ); ?> data-course_id="<?php echo absint( $learndash_course_id ); ?>" value="<?php echo esc_url( $learndash_item_url ); ?>"><?php echo wp_kses_post( get_the_title( $learndash_course_id ) ); ?></option>
						<?php
					}

					if ( true === $learndash_use_select_opt_groups ) {
						?>
						</optgroup>
						<?php
					}
				}
				?>
			</select></p>
			<?php

			if ( absint( $learndash_selected_course_id ) !== absint( $learndash_default_course_id ) ) {
				wp_nonce_field( 'ld-course-primary-set-nonce', 'ld-course-primary-set-nonce', false );
				?>
				<input type="checkbox" id="ld-course-primary-set" name="ld-course-primary-set" value="<?php echo absint( $learndash_selected_course_id ); ?>" /> <label for="ld-course-primary-set">
				<?php
					echo sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Set Primary %s', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);
				?>
				</label>
				<?php
			}
		}
	}
}
