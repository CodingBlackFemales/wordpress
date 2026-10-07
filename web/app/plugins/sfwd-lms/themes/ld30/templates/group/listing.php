<?php
/**
 * LearnDash LD30 Displays the listing of group content
 *
 * @var int    $group_id            Group ID.
 * @var int    $user_id             User ID.
 * @var bool   $has_access          User has access to group or is enrolled.
 * @var bool   $group_status        User's Group Status. Completed, No Started, or In Complete.
 * @var array  $group_courses       Array of Group Courses to display in listing.
 * @var array $course_pager_results Array of pager details.
 *
 * @since 3.1.7
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display group courses if they exist
 *
 * @since 3.1.7
 *
 * @var $group_courses [array]
 */

if ( ! empty( $group_courses ) ) :

	/**
	 * Filters LearnDash Group Courses table CSS class.
	 *
	 * @since 3.1.7
	 *
	 * @param string $table_class CSS classes for group courses table.
	 */
	$table_class = apply_filters( 'learndash_group_courses_table_class', 'ld-item-list-items ld-group-courses ld-group-courses-' . $group_id );

	/**
	 * Display the expand button if lesson has topics
	 *
	 * @since 3.0.0
	 *
	 * @var $lessons [array]
	 */
	?>

	<div class="<?php echo esc_attr( $table_class ); ?>" id="<?php echo esc_attr( 'ld-item-list-' . $group_id ); ?>" data-ld-expand-list="true" data-ld-expand-id="<?php echo esc_attr( 'ld-item-list-' . $group_id ); ?>">
		<?php
		/**
		 * Fires before the group courses listing.
		 *
		 * @since 3.1.7
		 *
		 * @param int $group_id Group ID.
		 * @param int $user_id  User ID.
		 */
		do_action( 'learndash_group_courses_listing_before', $group_id, $user_id );

		if ( $group_courses && ! empty( $group_courses ) ) {

			foreach ( $group_courses as $course_id ) {
				learndash_get_template_part(
					'group/partials/course-row.php',
					array(
						'group_id'   => $group_id,
						'user_id'    => $user_id,
						'course_id'  => $course_id,
						'has_access' => $has_access,
					),
					true
				);
			}
		}

		/**
		 * Fires after the group courses listing.
		 *
		 * @since 3.1.7
		 *
		 * @param int $group_id Group ID.
		 * @param int $user_id  User ID.
		 */
		do_action( 'learndash_group_listing_after', $group_id, $user_id );

		/**
		 * Fires before the group pagination.
		 *
		 * @since 3.1.7
		 *
		 * @param int $group_id Group ID.
		 * @param int $user_id  User ID.
		 */
		do_action( 'learndash_group_pagination_before', $group_id, $user_id );

		if ( isset( $course_pager_results['pager'] ) ) :
			learndash_get_template_part(
				'modules/pagination.php',
				array(
					'pager_results' => $course_pager_results['pager'],
					'pager_context' => ( isset( $context ) ? $context : 'group_courses' ),
					'group_id'      => $group_id,
				),
				true
			);
		endif;

		/**
		 * Fires after the group pagination.
		 *
		 * @since 3.0.0
		 *
		 * @param int $group_id Group ID.
		 * @param int $user_id  User ID.
		 */
		do_action( 'learndash_group_pagination_after', $group_id, $user_id );
		?>
	</div> <!--/.ld-item-list-items-->
<?php endif; ?>
