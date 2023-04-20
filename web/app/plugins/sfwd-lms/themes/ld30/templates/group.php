<?php
/**
 * LearnDash LD30 Displays a group
 *
 * Available Variables:
 *
 * @var int    $group_id          Group ID.
 * @var int    $user_id           User ID.
 * @var bool   $has_access        User has access to group or is enrolled.
 * @var bool   $group_status      User's Group Status. Completed, No Started, or In Complete.
 * @var object $post              Group Post Object.
 * @var array  $group_courses     Array of Group Courses to display in listing.
 * @var string $materials         Group Material from Settings.
 * @var bool   $has_group_content True/False if there is Group Post content.
 *
 * @since 3.1.7
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// $group_course_ids = learndash_group_enrolled_courses( $group_id );
?>
<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">

	<?php
	global $course_pager_results;

	/**
	 * Fires before the group.
	 *
	 * @since 3.1.7
	 *
	 * @param int $post_id  Post ID.
	 * @param int $group_id Group ID.
	 * @param int $user_id  User ID.
	 */
	do_action( 'learndash_group_before', get_the_ID(), $group_id, $user_id );

	/**
	 * Fires before the group certificate link.
	 *
	 * @since 3.1.7
	 *
	 * @param int $group_id Group ID.
	 * @param int $user_id  User ID.
	 */
	do_action( 'learndash_group_certificate_link_before', $group_id, $user_id );

	/**
	 * Certificate link
	 */
	if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
		$shown_content_key = 'learndash-shortcode-wrap-ld_certificate-' . absint( $group_id ) . '_' . absint( $user_id );
		if ( false === strstr( $content, $shown_content_key ) ) {
			$shortcode_out = do_shortcode( '[ld_certificate group_id="' . $group_id . '" user_id="' . $user_id . '" display_as="banner"]' );
			if ( ! empty( $shortcode_out ) ) {
				echo $shortcode_out;
			}
		}
	} else {
		if ( $group_certficate_link && ! empty( $group_certficate_link ) ) :

			learndash_get_template_part(
				'modules/alert.php',
				array(
					'type'    => 'success ld-alert-certificate',
					'icon'    => 'certificate',
					'message' => __( 'You\'ve earned a certificate!', 'learndash' ),
					'button'  => array(
						'url'    => $group_certficate_link,
						'icon'   => 'download',
						'label'  => __( 'Download Certificate', 'learndash' ),
						'target' => '_new',
					),
				),
				true
			);

		endif;
	}

	/**
	 * Fires after the group certificate link.
	 *
	 * @since 3.1.7
	 *
	 * @param int $group_id Group ID.
	 * @param int $user_id  User ID.
	 */
	do_action( 'learndash_group_certificate_link_after', $group_id, $user_id );

	/**
	 * Course info bar
	 */
	if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
		$shown_content_key = 'learndash-shortcode-wrap-ld_infobar-' . absint( $group_id ) . '_' . absint( $user_id );
		if ( false === strstr( $content, $shown_content_key ) ) {
			$shortcode_out = do_shortcode( '[ld_infobar group_id="' . $group_id . '" user_id="' . $user_id . '"]' );
			if ( ! empty( $shortcode_out ) ) {
				echo $shortcode_out;
			}
		}
	} else {
		learndash_get_template_part(
			'modules/infobar_group.php',
			array(
				'context'      => 'group',
				'group_id'     => $group_id,
				'user_id'      => $user_id,
				'has_access'   => $has_access,
				'group_status' => $group_status,
				'post'         => $post,
			),
			true
		);
	}
	?>

	<?php
	/**
	 * Filters the content to be echoed after the group status section of the group template output.
	 *
	 * @since 3.1.7
	 * See https://developers.learndash.com/hook/ld_after_course_status_template_container/ for example use of this filter used for Courses.
	 *
	 * @param string $content            Custom content showed after the group status section. Can be empty.
	 * @param string $group_status_index Group status index from the course status label
	 * @param int    $group_id           Group ID.
	 * @param int    $user_id            User ID.
	 */
	echo apply_filters( 'ld_after_group_status_template_container', '', learndash_course_status_idx( $group_status ), $group_id, $user_id );

	/**
	 * Content tabs
	 */
	learndash_get_template_part(
		'modules/tabs_group.php',
		array(
			'group_id'  => $group_id,
			'post_id'   => get_the_ID(),
			'user_id'   => $user_id,
			'content'   => $content,
			'materials' => $materials,
			'context'   => 'group',
		),
		true
	);

	/**
	 * Identify if we should show the course content listing
	 *
	 * @var $show_course_content [bool]
	 */
	$show_group_content = ( ! $has_access && 'on' === learndash_get_setting( $group_id, 'group_disable_content_table' ) ? false : true );

	if ( $has_group_content && $show_group_content ) :

		if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
			$shown_content_key = 'learndash-shortcode-wrap-course_content-' . absint( $group_id ) . '_' . absint( $user_id );
			if ( false === strstr( $content, $shown_content_key ) ) {
				$shortcode_out = do_shortcode( '[course_content group_id="' . $group_id . '" user_id="' . $user_id . '"]' );
				if ( ! empty( $shortcode_out ) ) {
					echo $shortcode_out;
				}
			}
		} else {
			?>
			<div class="ld-item-list ld-lesson-list">
				<div class="ld-section-heading">

					<?php
					/**
					 * Fires before the group heading.
					 *
					 * @since 3.1.7
					 *
					 * @param int $group_id Group ID.
					 * @param int $user_id  User ID.
					 */
					do_action( 'learndash_group_heading_before', $group_id, $user_id );
					?>

					<h2>
					<?php
					printf(
						// translators: placeholders: Group, Courses.
						esc_html_x( '%1$s %2$s', 'placeholders: Group, Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					);
					?>
					</h2>

					<?php
					/**
					 * Fires after the group heading.
					 *
					 * @since 3.1.7
					 *
					 * @param int $group_id Group ID.
					 * @param int $user_id  User ID.
					 */
					do_action( 'learndash_group_heading_after', $group_id, $user_id );
					?>

					<?php if ( true === $has_access ) { ?>
					<div class="ld-item-list-actions" data-ld-expand-list="true">

						<?php
						/**
						 * Fires before the course expand.
						 *
						 * @since 3.1.7
						 *
						 * @param int $group_id Group ID.
						 * @param int $user_id  User ID.
						 */
						do_action( 'learndash_group_expand_before', $group_id, $user_id );

						// Only display if there is something to expand.
						if ( ( isset( $group_courses ) ) && ( ! empty( $group_courses ) ) ) {
							?>
							<div class="ld-expand-button ld-primary-background" id="<?php echo esc_attr( 'ld-expand-button-' . $group_id ); ?>" data-ld-expands="<?php echo esc_attr( 'ld-item-list-' . $group_id ); ?>" data-ld-expand-text="<?php echo esc_attr_e( 'Expand All', 'learndash' ); ?>" data-ld-collapse-text="<?php echo esc_attr_e( 'Collapse All', 'learndash' ); ?>">
								<span class="ld-icon-arrow-down ld-icon"></span>
								<span class="ld-text"><?php echo esc_html_e( 'Expand All', 'learndash' ); ?></span>
							</div> <!--/.ld-expand-button-->
							<?php
							/**
							 * Filters whether to expand all course steps by default. Default is false.
							 *
							 * @since 2.5.0
							 *
							 * @param boolean $expand_all Whether to expand all course steps.
							 * @param int     $course_id  Course ID.
							 * @param string  $context    The context where course is expanded.
							 */
							if ( apply_filters( 'learndash_course_steps_expand_all', false, $group_id, 'course_lessons_listing_main' ) ) {
								?>
								<script>
									jQuery( function(){
										setTimeout(function(){
											jQuery("<?php echo esc_attr( '#ld-expand-button-' . $group_id ); ?>").trigger('click');
										}, 1000);
									});
								</script>
								<?php
							}
						}

						/**
						 * Action to add custom content after the course content expand button
						 *
						 * @since 3.0.0
						 *
						 * @param int $group_id Group ID.
						 * @param int $user_id  User ID.
						 */
						do_action( 'learndash_group_expand_after', $group_id, $user_id );
						?>

					</div> <!--/.ld-item-list-actions-->
					<?php } ?>
				</div> <!--/.ld-section-heading-->

				<?php
				/**
				 * Fires before the group content listing
				 *
				 * @since 3.1.7
				 *
				 * @param int $group_id Group ID.
				 * @param int $user_id  User ID.
				 */
				do_action( 'learndash_group_content_list_before', $group_id, $user_id );

				/**
				 * Content content listing
				 *
				 * @since 3.1.7
				 *
				 * ('listing.php');
				 */
				learndash_get_template_part(
					'group/listing.php',
					array(
						'group_id'             => $group_id,
						'user_id'              => $user_id,
						'group_courses'        => $group_courses,
						'has_access'           => $has_access,
						'course_pager_results' => $course_pager_results,
					),
					true
				);

				/**
				 * Fires before the group content listing.
				 *
				 * @since 3.1.7
				 *
				 * @param int $group_id Group ID.
				 * @param int $user_id  User ID.
				 */
				do_action( 'learndash_group_content_list_after', $group_id, $user_id );
				?>

			</div> <!--/.ld-item-list-->

			<?php
		}
	endif;

	/**
	 * Fires before the group listing.
	 *
	 * @since 3.1.7
	 *
	 * @param int $post_id  Post ID.
	 * @param int $group_id Group ID.
	 * @param int $user_id  User ID.
	 */
	do_action( 'learndash_group_after', get_the_ID(), $group_id, $user_id );
	learndash_load_login_modal_html();
	?>
</div>
