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
 *
 * @param array $essay_columns An Associative array of essay columns with slug as key and content as value.
 */
$essay_columns = apply_filters(
	'learndash-profile-essay-column',
	array(
		'comments' => '<a href="' . get_permalink( $essay->ID ) . '"><span class="ld-icon ld-icon-comments"></span> ' . $comments['all'] . '</a>',
		'status'   => learndash_status_bubble( $details['status'], 'essay', false ),
		'points'   => $details['points']['awarded'] . '/' . $details['points']['total'],
	)
); ?>

<div class="ld-table-list-item">
	<div class="ld-table-list-item-preview">
		<div class="ld-table-list-title">
			<a href="<?php echo esc_url( get_permalink( $essay->ID ) ); ?>"><?php echo wp_kses_post( get_the_title( $essay->ID ) ); ?></a>
		</div>
		<div class="ld-table-list-columns">
			<?php
			if ( $essay_columns ) :
				foreach ( $essay_columns as $slug => $content ) :
					?>
				<div class="ld-table-list-column <?php echo esc_attr( 'ld-table-list-column-' . $slug ); ?>">
									<?php echo wp_kses_post( $content ); ?>
				</div>
					<?php
				endforeach;
			endif;
			?>
		</div> <!--/.ld-table-list-columns-->
	</div> <!--/.ld-table-list-item-preview-->
</div> <!--/.ld-table-list-item-->
