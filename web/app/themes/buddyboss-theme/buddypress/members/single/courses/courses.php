<?php
/**
 * @package WordPress
 * @subpackage BuddyPress for LearnDash
 */
global $wp;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_url = remove_query_arg( array_keys( $_GET ), home_url( add_query_arg( $_GET, $wp->request ) ) );

$filepath = locate_template(
	array(
		'learndash/learndash_template_script.min.js',
		'learndash/learndash_template_script.js',
		'learndash_template_script.min.js',
		'learndash_template_script.js',
	)
);

$view              = bb_theme_get_directory_layout_preference( 'ld-course' );
$class_grid_active = ( 'grid' === $view ) ? 'active' : '';
$class_list_active = ( 'list' === $view ) ? 'active' : '';
$class_grid_show   = ( 'grid' === $view ) ? 'grid-view bb-grid' : '';
$class_list_show   = ( 'list' === $view ) ? 'list-view bb-list' : '';

if ( ! empty( $filepath ) ) {
	wp_enqueue_script( 'learndash_template_script_js', str_replace( ABSPATH, '/', $filepath ), array( 'jquery' ), LEARNDASH_VERSION, true );
	$learndash_assets_loaded['scripts']['learndash_template_script_js'] = __FUNCTION__;
} elseif ( file_exists( LEARNDASH_LMS_PLUGIN_DIR . '/templates/learndash_template_script' . ( ( defined( 'LEARNDASH_SCRIPT_DEBUG' ) && ( LEARNDASH_SCRIPT_DEBUG === true ) ) ? '' : '.min' ) . '.js' ) ) {
	wp_enqueue_script( 'learndash_template_script_js', LEARNDASH_LMS_PLUGIN_URL . 'templates/learndash_template_script' . ( ( defined( 'LEARNDASH_SCRIPT_DEBUG' ) && ( LEARNDASH_SCRIPT_DEBUG === true ) ) ? '' : '.min' ) . '.js', array( 'jquery' ), LEARNDASH_VERSION, true );
	$learndash_assets_loaded['scripts']['learndash_template_script_js'] = __FUNCTION__;
	$data            = array();
	$data['ajaxurl'] = admin_url( 'admin-ajax.php' );
	$data            = array( 'json' => wp_json_encode( $data ) );
	wp_localize_script( 'learndash_template_script_js', 'sfwd_data', $data );
}

add_action( 'wp_footer', array( 'LD_QuizPro', 'showModalWindow' ), 20 );
?>

<?php
$user_id  = bp_displayed_user_id();
$defaults = array(
	'user_id'            => get_current_user_id(),
	'per_page'           => false,
	'order'              => 'DESC',
	'orderby'            => 'ID',
	'course_points_user' => 'yes',
	'expand_all'         => false,
);
$atts     = apply_filters( 'bp_learndash_user_courses_atts', $defaults );
$atts     = wp_parse_args( $atts, $defaults );
if ( false === $atts['per_page'] ) {
	$atts['per_page'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
	$atts['quiz_num'] = $atts['per_page'];
} else {
	$atts['per_page'] = intval( $atts['per_page'] );
}

if ( $atts['per_page'] > 0 ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$atts['paged'] = isset( $_GET['current_page'] ) ? (int) sanitize_text_field( wp_unslash( $_GET['current_page'] ) ) : 1;
} else {
	unset( $atts['paged'] );
	$atts['nopaging'] = true;
}

$user_courses       = apply_filters( 'bp_learndash_user_courses', ld_get_mycourses( $user_id, $atts ) );
$usermeta           = get_user_meta( $user_id, '_sfwd-quizzes', true );
$quiz_attempts_meta = empty( $usermeta ) ? false : $usermeta;
$quiz_attempts      = array();
$profile_pager      = array();

if ( ( isset( $atts['per_page'] ) ) && ( intval( $atts['per_page'] ) > 0 ) ) {
	$atts['per_page'] = intval( $atts['per_page'] );
	if ( ( isset( $_GET['ld-profile-page'] ) ) && ( ! empty( $_GET['ld-profile-page'] ) ) ) {
		$profile_pager['paged'] = intval( $_GET['ld-profile-page'] );
	} else {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$profile_pager['paged'] = isset( $_GET['current_page'] ) ? (int) sanitize_text_field( wp_unslash( $_GET['current_page'] ) ) : 1;
	}

	$profile_pager['total_items'] = count( $user_courses );
	$profile_pager['total_pages'] = ceil( count( $user_courses ) / $atts['per_page'] );
	$user_courses                 = array_slice( $user_courses, ( $profile_pager['paged'] * $atts['per_page'] ) - $atts['per_page'], $atts['per_page'], false );
}

if ( ! empty( $quiz_attempts_meta ) ) {
	foreach ( $quiz_attempts_meta as $quiz_attempt ) {
		$c                          = learndash_certificate_details( $quiz_attempt['quiz'], $user_id );
		$quiz_attempt['post']       = get_post( $quiz_attempt['quiz'] );
		$quiz_attempt['percentage'] = ! empty( $quiz_attempt['percentage'] ) ? $quiz_attempt['percentage'] : ( ! empty( $quiz_attempt['count'] ) ? $quiz_attempt['score'] * 100 / $quiz_attempt['count'] : 0 );

		if ( $user_id === get_current_user_id() && ! empty( $c['certificateLink'] ) && ( ( isset( $quiz_attempt['percentage'] ) && $quiz_attempt['percentage'] >= $c['certificate_threshold'] * 100 ) ) ) {
			$quiz_attempt['certificate'] = $c;
		}
		$quiz_attempts[ learndash_get_course_id( $quiz_attempt['quiz'] ) ][] = $quiz_attempt;
	}
}
?>

<div id="bb-learndash_profile" class="<?php echo empty( $user_courses ) ? 'user-has-no-lessons' : ''; ?>">
	<div id="learndash-content" class="learndash-course-list">
		<?php
		if ( ! empty( $user_courses ) ) {
			?>
			<form id="bb-courses-directory-form" class="bb-courses-directory" method="get" action="<?php echo esc_url( $current_url ); ?>" data-order="<?php echo esc_attr( $atts['order'] ?? 'DESC' ); ?>" data-orderby="<?php echo esc_attr( $atts['orderby'] ?? 'ID' ); ?>">
				<div class="flex align-items-center bb-courses-header">
					<div id="courses-dir-search" class="bs-dir-search" role="search"></div>
					<div class="bb-secondary-list-tabs flex align-items-center" id="subnav" aria-label="Members directory secondary navigation" role="navigation">
						<div class="grid-filters" data-view="ld-course">
							<a href="#" class="layout-view layout-view-course layout-grid-view bp-tooltip <?php echo esc_attr( $class_grid_active ); ?>" data-view="grid" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Grid View', 'buddyboss-theme' ); ?>">
								<i class="dashicons dashicons-screenoptions" aria-hidden="true"></i>
							</a>

							<a href="#" class="layout-view layout-view-course layout-list-view bp-tooltip <?php echo esc_attr( $class_list_active ); ?>" data-view="list" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'List View', 'buddyboss-theme' ); ?>">
								<i class="dashicons dashicons-menu" aria-hidden="true"></i>
							</a>
						</div>
					</div>
				</div>
				<div class="grid-view bb-grid">
					<div id="course-dir-list" class="course-dir-list bs-dir-list">
						<?php
						if ( ! empty( $user_courses ) ) :
							global $post;
							$_post = $post;
							?>
							<ul class="bb-course-list bb-course-items <?php echo esc_attr( $class_grid_show . $class_list_show ); ?>" aria-live="assertive" aria-relevant="all">
								<?php
								foreach ( $user_courses as $course_id ) :
									$course = get_post( $course_id );
									$post   = $course;
									get_template_part( 'learndash/ld30/template-course-item' );
								endforeach;
								?>
							</ul>
							<?php
							$post = $_post;
						endif;

						$total_pages         = (int) $profile_pager['total_pages'];

						if ( 1 < $total_pages ) {
							?>
							<div class="bb-lms-pagination">
								<?php

									$big        = 999999999; // need an unlikely integer
									$translated = __( 'Page', 'buddyboss-theme' ); // Supply translatable string

									echo paginate_links(
										array(
											'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
											'format'  => '?paged=%#%',
											'current' => max( 1, (int) $profile_pager['paged'] ),
											'total'   => $total_pages,
											'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
										)
									);
								?>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<input type="hidden" name="type" value="my-courses">
			</form>
			<?php
		} else {
			?>
			<aside class="bp-feedback bp-messages info">
				<span class="bp-icon" aria-hidden="true"></span>
				<p>
					<?php
					printf(
					/* translators: The course label. */
						esc_html__( 'Sorry, no %s were found.', 'buddyboss-theme' ),
						esc_html( LearnDash_Custom_Label::label_to_lower( 'courses' ) )
					);
					?>
				</p>
			</aside>
			<?php
		}
		?>
	</div>
</div>
