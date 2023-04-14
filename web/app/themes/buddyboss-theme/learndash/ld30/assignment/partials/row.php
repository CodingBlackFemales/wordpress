<?php
/**
 * LearnDash LD30 Display lesson/topic assignment uploads listing row
 *
 * Available Variables:
 *
 * $course_step_post: WP_Post object for the Lesson/Topic being shown
 *
 * @since   3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$assignment_points = learndash_get_points_awarded_array( $assignment->ID ); ?>

<div class="ld-table-list-item">
	<div class="ld-table-list-item-preview">

		<?php
		/**
		 * Fires before the assignment list.
		 *
		 * @since 3.0.0
		 *
		 * @param WP_Post $assignment WP_Post object for assignment.
		 * @param int     $post_id    Post ID.
		 * @param int     $course_id  Course ID.
		 * @param int     $user_id    User ID.
		 */
		do_action( 'learndash-assignment-row-before', $assignment, get_the_ID(), $course_id, $user_id );
		?>

		<div class="ld-table-list-title">

			<?php
			/**
			 * Fires before the assignment delete link.
			 *
			 * @since 3.0.0
			 *
			 * @param WP_Post $assignment WP_Post object for assignment.
			 * @param int     $post_id    Post ID.
			 * @param int     $course_id  Course ID.
			 * @param int     $user_id    User ID.
			 */
			do_action( 'learndash-assignment-row-delete-before', $assignment, get_the_ID(), $course_id, $user_id );

			/**
			 * Delete assignment link
			 */
			if ( ! learndash_is_assignment_approved_by_meta( $assignment->ID ) ) :
				if ( ( isset( $post_settings['lesson_assignment_deletion_enabled'] ) && 'on' === $post_settings['lesson_assignment_deletion_enabled'] && absint( $assignment->post_author ) === absint( $user_id ) ) || ( learndash_is_admin_user( $user_id ) ) || ( learndash_is_group_leader_of_user( $user_id, $assignment->post_author ) ) ) :
					?>
					<a href="<?php echo esc_url( add_query_arg( 'learndash_delete_attachment', $assignment->ID ) ); ?>"
					   title="<?php esc_html_e( 'Delete this uploaded Assignment', 'buddyboss-theme' ); ?>">
						<span class="ld-icon ld-icon-delete" aria-label="<?php esc_html_e( 'Delete Assignment', 'buddyboss-theme' ); ?>"></span>
					</a>
					<?php
				endif;
			endif;

			/**
			 * Fires before the assignment title and link.
			 *
			 * @since 3.0.0
			 *
			 * @param WP_Post $assignment WP_Post object for assignment.
			 * @param int     $post_id    Post ID.
			 * @param int     $course_id  Course ID.
			 * @param int     $user_id    User ID.
			 */
			do_action( 'learndash-assignment-row-title-before', $assignment, get_the_ID(), $course_id, $user_id );
			?>

			<a href='<?php echo esc_url( get_post_meta( $assignment->ID, 'file_link', true ) ); ?>' target="_blank">
				<span class="ld-item-icon">
					<span class="ld-icon ld-icon-download" aria-label="<?php esc_html_e( 'Download Assignment', 'buddyboss-theme' ); ?>"></span>
				</span>
			</a>

			<?php
			$assignment_link = ( true === (bool) $assignment_post_type_object->publicly_queryable ? get_permalink( $assignment->ID ) : get_post_meta( $assignment->ID, 'file_link', true ) );
			?>

			<a href="<?php echo esc_url( $assignment_link ); ?>"><?php echo esc_html( get_the_title( $assignment->ID ) ); ?></a>

			<?php
			/**
			 * Fires after the assignment title and link.
			 *
			 * @since 3.0.0
			 *
			 * @param WP_Post $assignment WP_Post object for assignment.
			 * @param int     $post_id    Post ID.
			 * @param int     $course_id  Course ID.
			 * @param int     $user_id    User ID.
			 */
			do_action( 'learndash-assignment-row-title-after', $assignment, get_the_ID(), $course_id, $user_id );
			?>

		</div> <!--/.ld-table-list-title-->

		<div class="ld-table-list-columns">

			<?php
			// Use an array so it can be filtered later.
			$row_columns = array();

			/**
			 * Fires before the assignment post link.
			 *
			 * @since 3.0.0
			 *
			 * @param WP_Post $assignment WP_Post object for assignment.
			 * @param int     $post_id    Post ID.
			 * @param int     $course_id  Course ID.
			 * @param int     $user_id    User ID.
			 */
			do_action( 'learndash-assignment-row-columns-before', $assignment, get_the_ID(), $course_id, $user_id );

			ob_start();

			/**
			 * Fires before assignment comment count & link.
			 *
			 * @since 3.0.0
			 *
			 * @param WP_Post $assignment WP_Post object for assignment.
			 * @param int     $post_id    Post ID.
			 * @param int     $course_id  Course ID.
			 * @param int     $user_id    User ID.
			 */
			do_action( 'learndash-assignment-row-comments-before', $assignment, get_the_ID(), $course_id, $user_id );

			/** This filter is documented in https://developer.wordpress.org/reference/hooks/comments_open/ */
			if ( post_type_supports( 'sfwd-assignment', 'comments' ) && apply_filters( 'comments_open', $assignment->comment_status, $assignment->ID ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core filter
				?>
                <a href='<?php echo esc_url( get_comments_link( $assignment->ID ) ); ?>' data-balloon-pos="up" data-balloon="
					<?php
				echo sprintf(
				// translators: placeholder: comment count.
					esc_html_x( '%d Comments', 'placeholder: comment count', 'buddyboss-theme' ),
					esc_html( get_comments_number( $assignment->ID ) ) // get_comments_number returns a number. Adding escaping just in case somebody changes the template.
				);
				?>
				"><?php echo esc_html( get_comments_number( $assignment->ID ) ); ?><span class="ld-icon ld-icon-comments"></span></a>
				<?php
			} else {
				echo '';
			}

			// Add the markup to the array.
			$row_columns['comments'] = ob_get_clean();
			ob_flush();

			/**
			 * Fires after the assignment comment count & link.
			 *
			 * @since 3.0.0
			 *
			 * @param WP_Post $assignment WP_Post object for assignment.
			 * @param int     $post_id    Post ID.
			 * @param int     $course_id  Course ID.
			 * @param int     $user_id    User ID.
			 */
			do_action( 'learndash-assignment-row-comments-after', $assignment, get_the_ID(), $course_id, $user_id );

			if ( ! learndash_is_assignment_approved_by_meta( $assignment->ID ) && ! $assignment_points ) :

				ob_start();
				?>

				<span class="ld-status ld-status-waiting ld-tertiary-background">
				<span class="ld-icon ld-icon-calendar"></span>
				<span class="ld-text"><?php esc_html_e( 'Waiting Review', 'buddyboss-theme' ); ?></span>
			</span> <!--/.ld-status-waiting-->

				<?php
				$row_columns['status'] = ob_get_clean();
				ob_flush();

			elseif ( $assignment_points || learndash_is_assignment_approved_by_meta( $assignment->ID ) ) :

				ob_start();
				?>

				<span class="ld-status ld-status-complete">
				<span class="ld-icon ld-icon-checkmark"></span>
				<?php
				if ( $assignment_points ) :
					echo sprintf(
					     // translators: placeholders: points current, points max.
						     esc_html_x( '%1$s/%2$s Points Awarded ', 'placeholders: points current, points max', 'buddyboss-theme' ),
						     esc_html( $assignment_points['current'] ),
						     esc_html( $assignment_points['max'] )
					     ) . ' - ';
				endif;

				esc_html_e( 'Approved', 'buddyboss-theme' );
				?>
			</span>

				<?php
				$row_columns['status'] = ob_get_clean();
				ob_flush();

			endif;

			/**
			 * Filters assignment list columns content.
			 *
			 * @since 3.0.0
			 *
			 * @param array $row_columns Array of assignment row columns content
			 */
			$row_columns = apply_filters( 'learndash-assignment-list-columns-content', $row_columns );
			if ( ! empty( $row_columns ) ) :
				foreach ( $row_columns as $slug => $content ) :

					/**
					 * Fires before an assignment row.
					 *
					 * The dynamic part of the hook `$slug` refers to the slug of the column.
					 *
					 * @since 3.0.0
					 *
					 * @param WP_Post $assignment WP_Post object for assignment.
					 * @param int     $post_id    Post ID.
					 * @param int     $course_id  Course ID.
					 * @param int     $user_id    User ID.
					 */
					do_action( 'learndash-assignment-row-' . $slug . '-before', $assignment, get_the_ID(), $course_id, $user_id );
					?>
					<div class="<?php echo esc_attr( 'ld-table-list-column ld-' . $slug . '-column' ); ?>">
						<?php
						/**
						 * Fires before an assignment row content.
						 *
						 * The dynamic part of the hook `$slug` refers to the slug of the column.
						 *
						 * @since 3.0.0
						 *
						 * @param WP_Post $assignment WP_Post object for assignment.
						 * @param int     $post_id    Post ID.
						 * @param int     $course_id  Course ID.
						 * @param int     $user_id    User ID.
						 */
						do_action( 'learndash-assignment-row-' . $slug . '-inside-before', $assignment, get_the_ID(), $course_id, $user_id );

						echo wp_kses_post( $content );

						/**
						 * Fires after an assignment row content.
						 *
						 * The dynamic part of the hook `$slug` refers to the slug of the column.
						 *
						 * @since 3.0.0
						 *
						 * @param WP_Post $assignment WP_Post object for assignment.
						 * @param int     $post_id    Post ID.
						 * @param int     $course_id  Course ID.
						 * @param int     $user_id    User ID.
						 */
						do_action( 'learndash-assignment-row-' . $slug . '-inside-after', $assignment, get_the_ID(), $course_id, $user_id );
						?>
					</div>
					<?php
					/**
					 * Fires after an assignment row.
					 *
					 * The dynamic part of the hook `$slug` refers to the slug of the column.
					 *
					 * @since 3.0.0
					 *
					 * @param WP_Post $assignment WP_Post object for assignment.
					 * @param int     $post_id    Post ID.
					 * @param int     $course_id  Course ID.
					 * @param int     $user_id    User ID.
					 */
					do_action( 'learndash-assignment-row-' . $slug . '-after', $assignment, get_the_ID(), $course_id, $user_id );
					?>
					<?php
				endforeach;
			endif;
			?>

		</div> <!--/.ld-table-list-columns-->

		<?php
		/**
		 * Fires after all the assignment row content.
		 *
		 * @since 3.0.0
		 *
		 * @param WP_Post $assignment WP_Post object for assignment.
		 * @param int     $post_id    Post ID.
		 * @param int     $course_id  Course ID.
		 * @param int     $user_id    User ID.
		 */
		do_action( 'learndash-assignment-row-after', $assignment, get_the_ID(), $course_id, $user_id );
		?>
	</div>
</div>
