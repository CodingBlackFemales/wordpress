<?php

/**
 * LearnDash LD30 Displays a user's profile assignments row.
 *
 * @since   3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$assignment_points = learndash_get_points_awarded_array( $assignment->ID );
( $assignment->ID ); ?>

<div class="ld-table-list-item">
	<div class="ld-table-list-item-preview">
		<div class="ld-table-list-title">

			<a class="ld-item-icon" href='<?php echo esc_url( get_post_meta( $assignment->ID, 'file_link', true ) ); ?>'
			   target="_blank">
				<span class="ld-icon ld-icon-assignment"
					  aria-label="<?php esc_html_e( 'Download Assignment', 'buddyboss-theme' ); ?>"></span>
			</a>

			<?php $assignment_link = ( true === $assignment_post_type_object->publicly_queryable ? get_permalink( $assignment->ID ) : get_post_meta( $assignment->ID, 'file_link', true ) ); ?>

			<a class="ld-text"
			   href="<?php echo esc_url( $assignment_link ); ?>"><?php echo esc_html( get_the_title( $assignment->ID ) ); ?></a>

		</div>
		<div class="ld-table-list-columns">
			<?php
			// Use an array so it can be filtered later.
			$row_columns = array();

			/**
			 * Comment count and link to assignment
			 *
			 * @var [type]
			 */
			if ( true === (bool) $assignment_post_type_object->publicly_queryable ) :

				/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
				do_action( 'learndash-assignment-row-columns-before', $assignment, get_the_ID(), $course_id, $user_id );

				/** This filter is documented in https://developer.wordpress.org/reference/hooks/comments_open/ */
				if ( post_type_supports( 'sfwd-assignment', 'comments' ) && apply_filters( 'comments_open', $assignment->comment_status, $assignment->ID ) ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core hook

					ob_start();

					/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
					do_action( 'learndash-assignment-row-comments-before', $assignment, get_the_ID(), $course_id, $user_id );
					?>

					<a href='<?php echo esc_url( get_comments_link( $assignment->ID ) ); ?>' data-balloon-pos="up" data-balloon="<?php echo sprintf( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterOpen,Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
							// translators: placeholder: commentd count.
						esc_html_x( '%d Comments', 'placeholder: commented count', 'buddyboss-theme' ),
						esc_html( get_comments_number( $assignment->ID ) )
																																 );
																																	?>
					">
						<?php
						echo esc_html( get_comments_number( $assignment->ID ) );
						?>
						<span class="ld-icon ld-icon-comments"></span>
					</a>

					<?php
					// Add the markup to the array.
					$row_columns['comments'] = ob_get_clean();
					ob_flush();

					/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
					do_action( 'learndash-assignment-row-comments-after', $assignment, get_the_ID(), $course_id, $user_id );

				endif;
			endif;

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
						        // translators: placeholder: %1$s: Current points, %2$s: Maximum points
							     esc_html__( '%1$s/%2$s Points Awarded ', 'buddyboss-theme' ),
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

			$row_columns['date'] = get_the_date( get_option( 'date_format' ), $assignment->ID );

			// Apply a filter so devs can add more info here later.
			/** This filter is documented in themes/ld30/templates/assignment/partials/row.php */
			$row_columns = apply_filters( 'learndash-assignment-list-columns-content', $row_columns );
			if ( ! empty( $row_columns ) ) :
				foreach ( $row_columns as $slug => $content ) :

					/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
					do_action( 'learndash-assignment-row-' . $slug . '-before', $assignment, get_the_ID(), $course_id, $user_id );
					?>
					<div class="<?php echo esc_attr( 'ld-table-list-column ld-' . $slug . '-column' ); ?>">
						<?php

						/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
						do_action( 'learndash-assignment-row-' . $slug . '-inside-before', $assignment, get_the_ID(), $course_id, $user_id );

						echo wp_kses_post( $content );

						/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
						do_action( 'learndash-assignment-row-' . $slug . '-inside-after', $assignment, get_the_ID(), $course_id, $user_id );
						?>
					</div>
					<?php
					/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
					do_action( 'learndash-assignment-row-' . $slug . '-after', $assignment, get_the_ID(), $course_id, $user_id );
					?>
					<?php
				endforeach;
			endif;
			?>
		</div>
	</div>
</div>
