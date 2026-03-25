<?php
/**
 * BP Nouveau Group's edit courses template.
 * BuddyBoss - Groups Courses
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/courses.php.
 *
 * @since   2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 *
 * @version 1.0.0
 */

use memberpress\courses\models;
use memberpress\courses\helpers;

$group_id = bp_get_group_id();
if ( ! $group_id ) {
	return;
}

$options                   = get_option( 'mpcs-options' );
$paged                     = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1; // phpcs:ignore
$per_page                  = ( get_query_var( 'per_page' ) ) ? get_query_var( 'per_page' ) : (int) helpers\Options::val( $options, 'courses-per-page', 10 ); // phpcs:ignore
$progress_color            = implode( ', ', helpers\Options::get_rgb( $options, 'progress-color' ) );
$mpcs_sort_order           = helpers\Options::val( $options, 'courses-sort-order', 'alphabetically' );
$mpcs_sort_order_direction = helpers\Options::val( $options, 'courses-sort-order-direction', 'ASC' );

if ( ! in_array( $mpcs_sort_order_direction, array( 'ASC', 'DESC' ), true ) ) {
	$mpcs_sort_order_direction = 'ASC';
}

$sort_options = array(
	'alphabetically' => array(
		'orderby' => 'title',
	),
	'last-updated'   => array(
		'orderby' => 'modified',
	),
	'publish-date'   => array(
		'orderby' => 'date',
	),
);

$sort_option = $sort_options[ $mpcs_sort_order ] ?? $sort_options['default'];

$args = array(
	'group_id'    => $group_id,
	'fields'      => 'course_id',
	'count_total' => true,
	'paged'       => $paged,
	'per_page'    => $per_page,
	'order_by'    => $sort_option['orderby'],
	'order'       => $mpcs_sort_order_direction,
);

if ( get_query_var( 's' ) ) {
	$args['s'] = get_query_var( 's' );
}

$bb_meprlms_groups = bb_load_meprlms_group()->get( $args );

if ( ! function_exists( 'bb_enable_content_counts' ) || bb_enable_content_counts() ) {
	$count = ! empty( $bb_meprlms_groups['total'] ) ? $bb_meprlms_groups['total'] : 0;
	?>
	<div class="bb-item-count">
		<?php
		/* translators: %d is the courses count */
		printf(
			wp_kses( _n( '<span class="bb-count">%d</span> Course', '<span class="bb-count">%d</span> Courses', $count, 'buddyboss-pro' ), array( 'span' => array( 'class' => true ) ) ),
			$count
		);
		?>
	</div>
	<?php
	unset( $count );
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo bb_meprlms_get_course_search_form();

if ( ! empty( $bb_meprlms_groups['courses'] ) && count( $bb_meprlms_groups['courses'] ) ) {
	$courses       = $bb_meprlms_groups['courses'];
	$max_num_pages = ceil( $bb_meprlms_groups['total'] / $per_page );

	?>

	<div class="columns mpcs-cards">

	<?php
		ob_start();
	foreach ( $courses as $post ) { // Standard WordPress loop.
		setup_postdata( $post );
		$course           = new models\Course( get_the_ID() );
		$progress         = $course->user_progress( get_current_user_id() );
		$categories       = get_the_terms( $course->ID, 'mpcs-course-categories' );
		$course_is_locked = false;

		if ( MeprRule::is_locked( get_post( get_the_ID() ) ) ) {
			$course_is_locked = true;
		}
		?>
			<div class="column col-4 col-sm-12">
				<div class="card s-rounded">
					<div class="card-image">
					<?php if ( $course_is_locked ) { ?>
							<div class="locked-course-overlay">
								<i class="mpcs-icon mpcs-lock"></i>
							</div>
						<?php } ?>
						<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
						<?php
						if ( has_post_thumbnail() ) :
							the_post_thumbnail( apply_filters( 'mpcs_course_thumbnail_size', 'mpcs-course-thumbnail' ), array( 'class' => 'img-responsive' ) );
							else :
								?>
								<img src="<?php echo esc_url( bb_meprlms_integration_url( '/assets/images/course-placeholder.jpg' ) ); ?>" class="img-responsive" alt="" />
							<?php endif; ?>
						</a>
					</div>
					<div class="card-header">
					<div class="card-title">
							<h2 class="h5"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php if ( ! empty( $categories ) ) : ?>
								<div class="card-categories">
									<?php foreach ( $categories as $category ) : ?>
										<span class="card-category-name"><?php echo $category->name; ?><span class="card-category__separator">,</span></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<div class="card-body">
						<?php the_excerpt(); ?>
					</div>
					<div class="card-footer">
						<?php
						if ( models\UserProgress::has_started_course( get_current_user_id(), $course->ID ) ) :
							// Get lessons count.
							$total_lessons = $course->number_of_lessons();
							// If total lessons are n and progress is p% then get completed lesson count.
							$completed_lessons = ceil( $total_lessons * $progress / 100 );
							?>
							<div class="mpcs-progress-wrap">
								<div class="mpcs-progress-data">
									<strong class="mpcs-progress-lessons"><?php echo $completed_lessons . '/' . $total_lessons; ?></strong>
									<span class="mpcs-progress-per"><strong><?php echo $progress . '%'; ?></strong> <?php esc_html_e( ' Complete', 'buddyboss-pro' ); ?></span>
								</div>
								<div class="mpcs-progress-bar">
									<div class="mpcs-progress-bar-inner"
											style="width: <?php echo $progress; ?>%;"></div>
								</div>
							</div>
							<?php
							$next_lesson = models\UserProgress::next_lesson( get_current_user_id(), $course->ID );
							if ( false !== $next_lesson && is_object( $next_lesson ) ) {
								?>
								<a href="<?php echo get_permalink( $next_lesson->ID ); ?>"
									class="mpcs-btn-secondary"><i
											class="bb-icon-l bb-icon-play"></i><?php esc_html_e( 'Continue Course', 'buddyboss-pro' ); ?>
								</a>
								<?php
							}
							?>
						<?php else : ?>
							<span class="course-author">
								<?php
								$user_id    = get_the_author_meta( 'ID' );
								$author_url = bp_core_get_user_domain( $user_id );
								?>
								<a href="<?php echo esc_url_raw( $author_url ); ?>">
										<?php
										echo bp_core_fetch_avatar(
											array(
												'item_id' => $user_id,
												'html'    => true,
											)
										) . bp_core_get_user_displayname( $user_id );
										?>
								</a>
							</span>
						<?php endif; ?>
					</div>
				</div>
			</div>

		<?php
	} // end of the loop.
		wp_reset_postdata();

		echo ob_get_clean();
	?>
	</div>

	<?php
	if ( $max_num_pages > 1 ) {
		?>
		<div class="bb-lms-pagination">
			<?php
				$big          = 999999999; // need an unlikely integer.
				$translated   = esc_html__( 'Page', 'buddyboss-pro' ); // Supply translatable string.
				$current_page = max( 1, get_query_var( 'paged' ) );
				echo paginate_links(
					array(
						'base'               => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
						'format'             => '?paged=%#%',
						'current'            => $current_page,
						'total'              => $max_num_pages,
						'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
					)
				);
			?>
		</div>
		<?php
	}
} else {
	bp_nouveau_user_feedback( 'meprlms-courses-loop-none' );
}
