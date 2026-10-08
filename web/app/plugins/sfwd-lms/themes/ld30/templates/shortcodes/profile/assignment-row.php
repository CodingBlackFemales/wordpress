<?php
/**
 * LearnDash LD30 Displays a user's profile assignments row.
 *
 * @since 3.0.0
 * @version 4.21.3
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$learndash_assignment_link = learndash_assignment_get_download_url( $assignment->ID );

$assignment_points = learndash_get_points_awarded_array( $assignment->ID );
( $assignment->ID );
?>

<div
	class="ld-table-list-item"
	role="row"
>
	<div class="ld-table-list-item-preview">
		<div class="ld-table-list-columns">
			<div
				class="ld-table-list-title"
				role="rowheader"
			>
				<a class="ld-item-icon" href='<?php echo esc_url( $learndash_assignment_link ); ?>' target="_blank">
					<span class="ld-icon ld-icon-assignment" aria-label="<?php esc_html_e( 'Download Assignment', 'learndash' ); ?>"></span>
				</a>

				<?php
				$assignment_link = ( true === $assignment_post_type_object->publicly_queryable ? get_permalink( $assignment->ID ) : $learndash_assignment_link );
				?>

				<a
					aria-label="<?php echo esc_attr__( 'Go to the assignment page.', 'learndash' ) ?>"
					class="ld-text"
					href="<?php echo esc_url( $assignment_link ); ?>"
				>
					<?php echo esc_html( get_the_title( $assignment->ID ) ); ?>
				</a>

			</div>
			<?php
			// Use an array so it can be filtered later.
			$row_columns = array();

			/**
			 * Comment count and link to assignment
			 */

			/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
			do_action( 'learndash-assignment-row-columns-before', $assignment, get_the_ID(), $course_id, $user_id );

			/** This filter is documented in https://developer.wordpress.org/reference/hooks/comments_open/ */
			if ( post_type_supports( 'sfwd-assignment', 'comments' ) && apply_filters( 'comments_open', $assignment->comment_status, $assignment->ID ) ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core hook

				ob_start();

				/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
				do_action( 'learndash-assignment-row-comments-before', $assignment, get_the_ID(), $course_id, $user_id );

				if ( true === (bool) $assignment_post_type_object->publicly_queryable ) : ?>
					<div class="ld-tooltip">
						<a
							aria-describedby="ld-profile__assignment-row-comments-tooltip--<?php echo esc_attr( $assignment->ID ); ?>"
							href='<?php echo esc_url( get_comments_link( $assignment->ID ) ); ?>'
						>
				<?php endif; ?>
				<?php echo esc_html( get_comments_number( $assignment->ID ) ); ?><span class="ld-icon ld-icon-comments"></span>
				<?php
				if ( true === (bool) $assignment_post_type_object->publicly_queryable ) : ?>
						</a>

						<div
							class="ld-tooltip__text"
							id="ld-profile__assignment-row-comments-tooltip--<?php echo esc_attr( $assignment->ID ); ?>"
							role="tooltip"
						>
							<?php
							echo esc_html(
								sprintf(
									// translators: %1$d: comment count, %2$s: assignment title.
									_nx(
										'%1$d Comment for %2$s',
										'%1$d Comments for %2$s',
										get_comments_number( $assignment->ID ),
										'Comment count',
										'learndash'
									),
									get_comments_number( $assignment->ID ),
									$assignment->post_title
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>
				<?php
				// Add the markup to the array.
				$row_columns['comments'] = ob_get_clean();
				ob_flush();

				/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
				do_action( 'learndash-assignment-row-comments-after', $assignment, get_the_ID(), $course_id, $user_id );

			endif;

			if ( ! learndash_is_assignment_approved_by_meta( $assignment->ID ) && ! $assignment_points ) :

				ob_start();
				?>

				<span class="ld-status ld-status-waiting ld-tertiary-background">
					<span class="ld-icon ld-icon-calendar"></span>
					<span class="ld-text"><?php esc_html_e( 'Waiting Review', 'learndash' ); ?></span>
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
							// translators: placeholder: %1$s: Current points, %2$s: Maximum points.
							esc_html__( '%1$s/%2$s Points Awarded ', 'learndash' ),
							esc_html( $assignment_points['current'] ),
							esc_html( $assignment_points['max'] )
						) . ' - ';
					endif;

					esc_html_e( 'Approved', 'learndash' );
					?>
				</span>

				<?php
				$row_columns['status'] = ob_get_clean();
				ob_flush();

			endif;

			$row_columns['date'] = get_the_date( get_option( 'date_format' ), $assignment->ID );

			// Apply a fitler so devs can add more info here later.
			/** This filter is documented in themes/ld30/templates/assignment/partials/row.php */
			$row_columns = apply_filters( 'learndash-assignment-list-columns-content', $row_columns );
			if ( ! empty( $row_columns ) ) :
				foreach ( $row_columns as $slug => $content ) :
					/** This action is documented in themes/ld30/templates/assignment/partials/row.php */
					do_action( 'learndash-assignment-row-' . $slug . '-before', $assignment, get_the_ID(), $course_id, $user_id );
					?>

					<div
						class="<?php echo esc_attr( 'ld-table-list-column ld-' . $slug . '-column' ); ?>"
						role="cell"
						tabindex="0"
					>
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
