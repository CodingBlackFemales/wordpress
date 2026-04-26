<?php
/**
 * LearnDash LD30 Displays a user's profile.
 *
 * Available Variables:
 *
 * @var int                    $user_id          Current User ID.
 * @var WP_User                $current_user     Currently logged in user object.
 * @var array<int>             $user_courses     Array of course ID's of the current user.
 * @var array<int, mixed>      $quiz_attempts    Array of quiz attempts of the current user.
 * @var array<string, mixed>   $shortcode_atts   Array of values passed to shortcode.
 * @var bool                   $show_saved_cards Whether to show the saved cards.
 *
 * @since 3.0.0
 * @version 4.25.3
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Models\User;
use LearnDash\Core\Repositories\Subscription;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Cast;

global $learndash_assets_loaded;
$learndash_shortcode_data_json = htmlspecialchars( wp_json_encode( $shortcode_atts ) );

/**
 * Logic to load assets as needed
 *
 * @var [type]
 */

if ( ! isset( $learndash_assets_loaded['scripts']['learndash_template_script_js'] ) ) :
	$learndash_template_script_filepath = SFWD_LMS::get_template( 'learndash_template_script.js', null, null, true );
	if ( ! empty( $learndash_template_script_filepath ) ) :
		wp_enqueue_script( 'learndash_template_script_js', learndash_template_url_from_path( $learndash_template_script_filepath ), array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
		$learndash_assets_loaded['scripts']['learndash_template_script_js'] = __FUNCTION__;

		$learndash_data            = array();
		$learndash_data['ajaxurl'] = admin_url( 'admin-ajax.php' );
		$learndash_data            = array( 'json' => wp_json_encode( $learndash_data ) );
		wp_localize_script( 'learndash_template_script_js', 'sfwd_data', $learndash_data );
	endif;
endif;


/**
 * We don't want to include this if this is a paginated or search view as we'll end up with duplicates
 *
 * @var $_GET['action'] (string)    is set to 'ld30_ajax_pager' if paginating or set to 'ld30_ajax_profile_search' on search.
 */

if ( ! isset( $_GET['action'] ) || ! in_array( $_GET['action'], array( 'ld30_ajax_pager', 'ld30_ajax_profile_search' ), true ) ) :
	LD_QuizPro::showModalWindow();
endif; ?>

<div class="<?php learndash_the_wrapper_class( null, 'ld-profile' ); ?>">
	<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped on line 20 ?>
	<div id="ld-profile" data-shortcode_instance="<?php echo $learndash_shortcode_data_json; ?>">
		<?php
		/**
		 * If the user wants to include the summary, they use the shortcode attr summary="true"
		 *
		 * @var [type]
		 */
		if ( isset( $shortcode_atts['show_header'] ) && 'yes' === $shortcode_atts['show_header'] ) :
			?>
			<div class="ld-profile-summary">
				<div class="ld-profile-card">
					<div class="ld-profile-avatar">
						<?php
						/**
						 * Related to the CSS themes/ld30/assets/css/learndash.css
						 * .learndash-wrapper .ld-profile-summary .ld-profile-card .ld-profile-avatar
						 */
						echo wp_kses_post( get_avatar( $user_id, 150 ) );
						?>
					</div> <!--/.ld-profile-avatar-->
					<?php
					if ( ! empty( $current_user->user_lastname ) || ! empty( $current_user->user_firstname ) ) :
						?>
						<h2 class="ld-profile-heading">
							<?php echo esc_html( $current_user->user_firstname . ' ' . $current_user->user_lastname ); ?>
						</h2>
					<?php endif; ?>

					<?php
					/**
					 * Filters whether to show the user profile link.
					 *
					 * @since 2.5.8
					 *
					 * @param boolean $show_profile Whether to show profile link.
					 */
					if ( current_user_can( 'read' ) && isset( $shortcode_atts['profile_link'] ) && true === (bool) $shortcode_atts['profile_link'] && apply_filters( 'learndash_show_profile_link', Cast::to_bool( $shortcode_atts['profile_link'] ) ) ) :
						?>
						<a class="ld-profile-edit-link" href='<?php echo esc_url( get_edit_user_link() ); ?>'><?php esc_html_e( 'Edit profile', 'learndash' ); ?></a>
					<?php endif; ?>
				</div> <!--/.ld-profile-card-->
				<div class="ld-profile-stats">
					<h3 class="sr-only">
						<?php esc_html_e( 'Your Stats', 'learndash' ); ?>
					</h3>

					<?php
					$learndash_user_stats = learndash_get_user_stats( $user_id );

					$learndash_profile_stats = array(
						array(
							'title' => LearnDash_Custom_Label::get_label( 'courses' ),
							'value' => $learndash_user_stats['courses'],
							'class' => 'ld-profile-stat-courses',
						),
						array(
							'title' => __( 'Completed', 'learndash' ),
							'value' => $learndash_user_stats['completed'],
							'class' => 'ld-profile-stat-completed',
						),

						array(
							'title' => __( 'Certificates', 'learndash' ),
							'value' => $learndash_user_stats['certificates'],
							'class' => 'ld-profile-stat-certificates',
						),
					);

					if ( isset( $shortcode_atts['course_points_user'] ) && 'yes' === $shortcode_atts['course_points_user'] ) {
						$learndash_profile_stats[] = array(
							'title' => esc_html__( 'Points', 'learndash' ),
							'value' => $learndash_user_stats['points'],
							'class' => 'ld-profile-stat-points',
						);
					}

					/**
					 * Filters LearnDash user profile statistics.
					 *
					 * @since 3.1.0
					 *
					 * @param array $learndash_profile_stats An array of profile stats data.
					 * @param int   $user_id                 User ID.
					 */
					$learndash_profile_stats = apply_filters( 'learndash_profile_stats', $learndash_profile_stats, $user_id );
					if ( ( ! empty( $learndash_profile_stats ) ) && ( is_array( $learndash_profile_stats ) ) ) {
						foreach ( $learndash_profile_stats as $learndash_profile_stat ) {
							$learndash_stat_class = 'ld-profile-stat';
							if ( ( isset( $learndash_profile_stat['class'] ) ) && ( ! empty( $learndash_profile_stat['class'] ) ) ) {
								$learndash_stat_class .= ' ' . $learndash_profile_stat['class'];
							}
							?>
							<div class="<?php echo esc_attr( $learndash_stat_class ); ?>">
								<strong><?php echo esc_html( $learndash_profile_stat['value'] ); ?></strong>
								<span><?php echo esc_html( $learndash_profile_stat['title'] ); ?></span>
							</div> <!--/.ld-profile-stat-->
							<?php
						}
					}
					?>
				</div> <!--/.ld-profile-stats-->
			</div>
		<?php endif; ?>

		<div class="ld-item-list ld-course-list">

			<div class="ld-section-heading">
				<h3>
					<?php
					printf(
						// translators: Profile Course Content Label.
						esc_html_x( 'Your %s', 'Profile Course Content Label', 'learndash' ),
						esc_attr( LearnDash_Custom_Label::get_label( 'courses' ) )
					);
					?>
				</h3>
				<div class="ld-item-list-actions">
					<?php if ( isset( $shortcode_atts['show_search'] ) && 'yes' === $shortcode_atts['show_search'] ) { ?>
					<button
						aria-controls="ld-course-search"
						aria-expanded="false"
						aria-label="<?php
							printf(
								// translators: placeholder: Profile Search Prompt Label.
								esc_html_x( 'Show %s Search Field', 'placeholder: Profile Search Prompt Label', 'learndash' ),
								esc_attr( LearnDash_Custom_Label::get_label( 'courses' ) )
							);
						?>"
						class="ld-search-prompt ld-icon-search ld-icon"
						data-ld-expands="ld-course-search"
					>
					</button> <!--/.ld-search-prompt-->
					<?php } ?>

					<?php
						$course_container_ids = implode(
							' ',
							array_map(
								function( $course_id ) {
									return "ld-course-list-item-{$course_id}-container";
								},
								$user_courses
							)
						);
					?>

					<button
						aria-controls="<?php echo esc_attr( $course_container_ids ); ?>"
						aria-expanded="false"
						class="ld-expand-button"
						data-ld-collapse-text="<?php echo esc_attr_e( 'Collapse All', 'learndash' ); ?>"
						data-ld-expand-text="<?php echo esc_attr_e( 'Expand All', 'learndash' ); ?>"
						data-ld-expands="<?php echo esc_attr( $course_container_ids ); ?>"
					>
						<span class="ld-icon-arrow-down ld-icon"></span>
						<span class="ld-text"><?php echo esc_html_e( 'Expand All', 'learndash' ); ?></span>

						<span class="screen-reader-text">
							<?php echo esc_html( learndash_get_custom_label( 'courses' ) ); ?>
						</span>
					</button> <!--/.ld-expand-button-->
				</div> <!--/.ld-course-list-actions-->
			</div> <!--/.ld-section-heading-->
			<?php
			if ( isset( $shortcode_atts['show_search'] ) && 'yes' === $shortcode_atts['show_search'] ) {
				learndash_get_template_part(
					'shortcodes/profile/search.php',
					array(
						'user_id'      => $user_id,
						'user_courses' => $user_courses,
					),
					true
				);
			}
			?>

			<div class="ld-item-list-items" id="ld-main-course-list" data-ld-expand-list="true" data-ld-expand-id="<?php echo esc_attr( 'ld-main-course-list' ); ?>">

				<?php
				if ( ! empty( $user_courses ) ) :
					foreach ( $user_courses as $learndash_course_id ) :

						learndash_get_template_part(
							'shortcodes/profile/course-row.php',
							array(
								'user_id'        => $user_id,
								'course_id'      => $learndash_course_id,
								'quiz_attempts'  => $quiz_attempts,
								'shortcode_atts' => $shortcode_atts,
							),
							true
						);

					endforeach;
					else :

						$learndash_no_courses_found_alert = array(
							'icon'    => 'alert',
							// translators: placeholder: Courses.
							'message' => sprintf( esc_html_x( 'No %s found', 'placeholder: Courses', 'learndash' ), LearnDash_Custom_Label::get_label( 'courses' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
							'type'    => 'warning',
							'role'    => $shortcode_atts['alert_role'] ?? 'status',
						);
						learndash_get_template_part( 'modules/alert.php', $learndash_no_courses_found_alert, true );

				endif;
					?>

			</div> <!--/.ld-course-list-items-->

		</div> <!--/ld-course-list-->

		<?php
		$learndash_profile_search = isset( $_GET['ld-profile-search'], $_GET['learndash_profile_course_search_nonce'] ) &&
									! empty( $_GET['ld-profile-search'] ) &&
									wp_verify_nonce( $_GET['learndash_profile_course_search_nonce'], $learndash_profile_course_search_nonce_field )
									? sanitize_text_field( $_GET['ld-profile-search'] )
									: false;
		learndash_get_template_part(
			'modules/pagination',
			array(
				'pager_results' => $profile_pager,
				'pager_context' => 'profile',
				'search'        => $learndash_profile_search,
			),
			true
		);
		?>

		<?php
			Template::show_template(
				'shortcodes/profile/subscriptions',
				[
					'subscriptions' => Subscription::find_by_user( Cast::to_int( $user_id ) ),
				]
			);

			if ( $show_saved_cards ) {
				$user = User::create_from_user( Cast::to_int( $user_id ) );

				Template::show_template(
					'shortcodes/profile/saved-cards',
					[
						'cards' => $user->get_cards(),
					]
				);
			}
		?>
	</div> <!--/#ld-profile-->

</div> <!--/.ld-course-wrapper-->
<?php
/** This filter is documented in themes/ld30/templates/course.php */
if ( apply_filters( 'learndash_course_steps_expand_all', $shortcode_atts['expand_all'], 0, 'profile_shortcode' ) ) :
	?>
	<script>
		jQuery( function() {
			setTimeout(function() {
				jQuery(".ld-item-list-actions .ld-expand-button").trigger('click');
			}, 1000 );
		});
	</script>
	<?php
endif;
