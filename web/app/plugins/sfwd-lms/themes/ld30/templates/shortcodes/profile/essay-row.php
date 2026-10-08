<?php
/**
 * LearnDash LD30 Displays a user's profile essay row.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Essay Row
 */

$meta     = get_post_meta( $essay->ID );
$comments = get_comment_count( $essay->ID );
$details  = learndash_get_essay_details( $essay->ID );

/**
 * Filters list of profile essay columns.
 *
 * @since 3.0.0
 * @version 4.21.3
 *
 * @param array $essay_columns An Associative array of essay columns with slug as key and content as value.
 */
$essay_columns = apply_filters(
	'learndash-profile-essay-column',
	array(
		'comments' => '<a aria-label="' . esc_attr__(
			sprintf(
				// translators: %1$d: comment count, %2$s: assignment title.
				_nx(
					'See %1$d comment for %2$s.',
					'See %1$d comments for %2$s.',
					get_comments_number( $essay->ID ),
					'Label for the link to the comments for the assignment.',
					'learndash'
				),
				get_comments_number( $essay->ID ),
				$essay->post_title
			)
		) . '" href="' . get_permalink( $essay->ID ) . '"><span class="ld-icon ld-icon-comments"></span> ' . $comments['all'] . '</a>',
		'status'   => learndash_status_bubble( $details['status'], 'essay', false ),
		'points'   => $details['points']['awarded'] . '/' . $details['points']['total'],
	)
); ?>

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
				<a
					aria-label="<?php echo esc_attr__( 'Go to the essay page.', 'learndash' ); ?>"
					href="<?php echo esc_url( get_permalink( $essay->ID ) ); ?>"
				>
					<?php echo wp_kses_post( get_the_title( $essay->ID ) ); ?>
				</a>
			</div>

			<?php
			if ( $essay_columns ) :
				foreach ( $essay_columns as $slug => $content ) :
				?>

				<div
					class="ld-table-list-column <?php echo esc_attr( 'ld-table-list-column-' . $slug ); ?>"
					role="cell"
					tabindex="0"
				>
					<?php echo wp_kses_post( $content ); ?>
				</div>

				<?php
				endforeach;
			endif;
			?>
		</div> <!--/.ld-table-list-columns-->
	</div> <!--/.ld-table-list-item-preview-->
</div> <!--/.ld-table-list-item-->
