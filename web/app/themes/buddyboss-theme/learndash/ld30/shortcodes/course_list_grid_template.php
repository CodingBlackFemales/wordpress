<?php
/**
 * Template part for displaying LearnDash Course Grid [ld_course_list]
 *
 * @link    https://www.learndash.com/add-on/course-grid/
 *
 * @package BuddyBoss_Theme
 */

$col   = empty( $shortcode_atts['col'] ) ? LEARNDASH_COURSE_GRID_COLUMNS : intval( $shortcode_atts['col'] );
$col   = $col > 6 ? 6 : $col;
$smcol = $col == 1 ? 1 : $col / 2;
$col   = 12 / $col;
$smcol = intval( ceil( 12 / $smcol ) );
$col   = is_float( $col ) ? number_format( $col, 1 ) : $col;
$col   = str_replace( '.', '-', $col );

global $post;
$course_id   = $post->ID;
$course_id   = ! empty( $shortcode_atts['course_id'] ) ? $shortcode_atts['course_id'] : learndash_get_course_id( $post->ID );
$user_id     = get_current_user_id();
$course_type = get_post_type( $post->ID );

$cg_short_description = get_post_meta( $post->ID, '_learndash_course_grid_short_description', true );
$enable_video         = get_post_meta( $post->ID, '_learndash_course_grid_enable_video_preview', true );
$embed_code           = get_post_meta( $post->ID, '_learndash_course_grid_video_embed_code', true );
$button_text          = get_post_meta( $post->ID, '_learndash_course_grid_custom_button_text', true );

// Retrieve oembed HTML if URL provided.
if ( preg_match( '/^http/', $embed_code ) ) {
	$embed_code = wp_oembed_get(
		$embed_code,
		array(
			'height' => 600,
			'width'  => 400,
		)
	);
}

if ( isset( $course_id ) ) {
	$button_link = learndash_get_step_permalink( get_the_ID(), $course_id );
} else {
	$button_link = get_permalink();
}

$button_link      = apply_filters( 'learndash_course_grid_custom_button_link', $button_link, $course_id );
$button_text      = isset( $button_text ) && ! empty( $button_text ) ? $button_text : __( 'See more...', 'buddyboss-theme' );
$button_text      = apply_filters( 'learndash_course_grid_custom_button_text', $button_text, $course_id );
$options          = get_option( 'sfwd_cpt_options' );
$currency_setting = class_exists( 'LearnDash_Settings_Section' ) ? LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'paypal_currency' ) : null;
$currency         = '';

if ( isset( $currency_setting ) || ! empty( $currency_setting ) ) {
	$currency = $currency_setting;
} elseif ( isset( $options['modules'] ) && isset( $options['modules']['sfwd-courses_options'] ) && isset( $options['modules']['sfwd-courses_options']['sfwd-courses_paypal_currency'] ) ) {
	$currency = $options['modules']['sfwd-courses_options']['sfwd-courses_paypal_currency'];
}

if ( class_exists( 'NumberFormatter' ) ) {
	$locale        = get_locale();
	$number_format = new NumberFormatter( $locale . '@currency=' . $currency, NumberFormatter::CURRENCY );
	$currency      = $number_format->getSymbol( NumberFormatter::CURRENCY_SYMBOL );
}

/**
 * Currency symbol filter hook
 *
 * @param string $currency Currency symbol
 * @param int    $course_id
 */
$currency = apply_filters( 'learndash_course_grid_currency', $currency, $course_id );

$course_options = get_post_meta( $course_id, '_sfwd-courses', true );

// For LD >= 3.0.
if ( function_exists( 'learndash_get_course_price' ) ) {
	$price_args = learndash_get_course_price( $course_id );
	$price      = $price_args['price'];
	$price_type = $price_args['type'];
} else {
	$price      = $course_options && isset( $course_options['sfwd-courses_course_price'] ) ? $course_options['sfwd-courses_course_price'] : __( 'Free', 'buddyboss-theme' );
	$price_type = $course_options && isset( $course_options['sfwd-courses_course_price_type'] ) ? $course_options['sfwd-courses_course_price_type'] : '';
}

$legacy_short_description = '';
if ( 'sfwd-courses' === $course_type ) {
	$legacy_short_description = isset( $course_options['sfwd-courses_course_short_description'] ) ? $course_options['sfwd-courses_course_short_description'] : '';
}

$short_description = '';
if ( ! empty( $cg_short_description ) ) {
	$short_description = $cg_short_description;
} elseif ( ! empty( $legacy_short_description ) ) {
	$short_description = $legacy_short_description;
}

/**
 * Filter: individual grid class
 *
 * @param int   $course_id      Course ID
 * @param array $course_options Course options
 *
 * @var string
 */
$grid_class = apply_filters( 'learndash_course_grid_class', '', $course_id, $course_options );

$has_access   = sfwd_lms_has_access( $course_id, $user_id );
$is_completed = learndash_course_completed( $user_id, $course_id );

$price_text = '';
if ( is_numeric( $price ) && ! empty( $price ) ) {
	$price_format = apply_filters( 'learndash_course_grid_price_text_format', '{currency}{price}' );
	$price_text   = str_replace( array( '{currency}', '{price}' ), array( $currency, $price ), $price_format );
} elseif ( is_string( $price ) && ! empty( $price ) ) {
	$price_text = $price;
} elseif ( empty( $price ) ) {
	$price_text = __( 'Free', 'buddyboss-theme' );
}

$class              = 'ld_course_grid_price';
$custom_ribbon_text = get_post_meta( $post->ID, '_learndash_course_grid_custom_ribbon_text', true );
$ribbon_text        = ( isset( $custom_ribbon_text ) && ! empty( $custom_ribbon_text ) ) ? $custom_ribbon_text : '';

if ( $has_access && ! $is_completed && $price_type != 'open' && empty( $ribbon_text ) ) {
	$class      .= ' ribbon-enrolled';
	$ribbon_text = __( 'Enrolled', 'buddyboss-theme' );
} elseif ( $has_access && $is_completed && $price_type != 'open' && empty( $ribbon_text ) ) {
	$class      .= '';
	$ribbon_text = __( 'Completed', 'buddyboss-theme' );
} elseif ( $price_type == 'open' && empty( $ribbon_text ) ) {
	if ( is_user_logged_in() && ! $is_completed ) {
		$class      .= ' ribbon-enrolled';
		$ribbon_text = __( 'Enrolled', 'buddyboss-theme' );
	} elseif ( is_user_logged_in() && $is_completed ) {
		$class      .= '';
		$ribbon_text = __( 'Completed', 'buddyboss-theme' );
	} else {
		$class      .= ' ribbon-enrolled';
		$ribbon_text = '';
	}
} elseif ( $price_type == 'closed' && empty( $price ) ) {
	$class .= ' ribbon-enrolled';

	if ( is_numeric( $price ) ) {
		$ribbon_text = $price_text;
	} else {
		$ribbon_text = '';
	}
} else {
	if ( empty( $ribbon_text ) ) {
		$class      .= ! empty( $course_options['sfwd-courses_course_price'] ) ? ' price_' . $currency : ' free';
		$ribbon_text = $price_text;
	} else {
		$class .= ' custom';
	}
}

/**
 * Filter: individual course ribbon text
 *
 * @param string $ribbon_text Returned ribbon text
 * @param int    $course_id   Course ID
 * @param string $price_type  Course price type
 */
$ribbon_text = apply_filters( 'learndash_course_grid_ribbon_text', $ribbon_text, $course_id, $price_type );

if ( '' == $ribbon_text ) {
	$class = '';
}

/**
 * Filter: individual course ribbon class names
 *
 * @param string $class          Returned class names
 * @param int    $course_id      Course ID
 * @param array  $course_options Course's options
 * @var string
 */
$class = apply_filters( 'learndash_course_grid_ribbon_class', $class, $course_id, $course_options );

$thumb_size = isset( $shortcode_atts['thumb_size'] ) && ! empty( $shortcode_atts['thumb_size'] ) ? $shortcode_atts['thumb_size'] : 'course-thumb';


/**
 * Display class if course is paid, and content is enabled
 */
$course_price      = trim( learndash_get_course_meta_setting( get_the_ID(), 'course_price' ) );
$course_price_type = learndash_get_course_meta_setting( get_the_ID(), 'course_price_type' );

/**
 * Display class if content is disabled
 */
$class_price_type = '';
if ( ! empty( $course_price ) && ( 'paynow' === $course_price_type || 'subscribe' === $course_price_type || 'closed' === $course_price_type ) && ( 'true' == $shortcode_atts['show_content'] ) ) {
	$class_price_type = 'bb-course-paid';
}

/**
 * Display class if course has content disabled
 */
$class_content_type = '';
if ( 'true' !== $shortcode_atts['show_content'] ) {
	$class_content_type = 'bb-course-no-content';
}

/**
 * Filter: individual course container class names
 *
 * @param string $course_class   Returned class names
 * @param int    $course_id      Course ID
 * @param array  $course_options Course's options
 *
 * @var string
 */
$class_content_type = apply_filters( 'learndash_course_grid_course_class', $class_content_type, $course_id, $course_options );

$course_pricing = learndash_get_course_price( get_the_ID() );

$types_array  = array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-assignment', 'sfwd-essays', 'sfwd-courses' );
$course_type  = get_post_type( get_the_ID() );
$ribbon_title = '';
if ( $course_type == 'sfwd-lessons' ) {
	$ribbon_title = LearnDash_Custom_Label::get_label( 'lesson' );

} elseif ( $course_type == 'sfwd-topic' ) {
	$ribbon_title = LearnDash_Custom_Label::get_label( 'topic' );

} elseif ( $course_type == 'sfwd-quiz' ) {
	$ribbon_title = LearnDash_Custom_Label::get_label( 'quiz' );

} elseif ( $course_type == 'sfwd-assignment' ) {
	$ribbon_title = LearnDash_Custom_Label::get_label( 'assignment' );

} elseif ( $course_type == 'sfwd-essays' ) {
	$ribbon_title = LearnDash_Custom_Label::get_label( 'essays' );

} elseif ( $course_type == 'sfwd-courses' ) {
	$ribbon_title = LearnDash_Custom_Label::get_label( 'course' );

}
?>
<div class="ld_course_grid col-sm-<?php echo esc_attr( $smcol ); ?> col-md-<?php echo esc_attr( $col ); ?> <?php echo esc_attr( $grid_class ); ?> bb-course-item-wrap">

	<div class="bb-cover-list-item <?php echo esc_attr( $class_price_type ); ?> <?php echo esc_attr( $class_content_type ); ?>">
		<?php if ( 'true' == $shortcode_atts['show_thumbnail'] ) : ?>

			<div class="bb-course-cover <?php echo ( 1 == $enable_video && ! empty( $embed_code ) ) ? 'has-video-cover' : ''; ?>">
				<a title="<?php the_title_attribute(); ?>" href="<?php the_permalink(); ?>" class="bb-cover-wrap">

					<?php
					/**
					 * Status label
					 */
					if ( 'sfwd-courses' === $course_type ) {

						// sfwd-courses status.
						$progress = learndash_course_progress(
							array(
								'user_id'   => $user_id,
								'course_id' => $course_id,
								'array'     => true,
							)
						);

						$status = ! empty( $progress['percentage'] ) && ( 100 === (int) $progress['percentage'] ) ? 'completed' : 'notcompleted';

						if ( ! empty( $progress['percentage'] ) && 100 !== (int) $progress['percentage'] ) {
							$status = 'progress';
						}

						if ( isset( $custom_ribbon_text ) && ! empty( $custom_ribbon_text ) ) {
							echo '<div class="ld-status ld-status-progress ld-primary-background ld-custom-ribbon-text">' . sprintf( esc_html_x( '%s', 'Start ribbon', 'buddyboss-theme' ), $custom_ribbon_text ) . '</div>';
						} elseif ( is_user_logged_in() && isset( $has_access ) && $has_access ) {
							if ( ( 'open' === $price_type && 0 === $progress['percentage'] ) || ( 'open' !== $price_type && $has_access && 0 === $progress['percentage'] ) ) {
								echo '<div class="ld-status ld-status-progress ld-primary-background">' . sprintf( esc_html_x( 'Start %s ', 'Start ribbon', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( 'course' ) ) . '</div>';
							} else {
								learndash_status_bubble( $status );
							}
						} elseif ( 'free' === $price_type ) {

							echo '<div class="ld-status ld-status-incomplete ld-third-background">' . esc_html__( 'Free', 'buddyboss-theme' ) . '</div>';

						} elseif ( 'open' !== $price_type ) {

							echo '<div class="ld-status ld-status-incomplete ld-third-background">' . esc_html__( 'Not Enrolled', 'buddyboss-theme' ) . '</div>';

						} elseif ( 'open' === $price_type ) {

							echo '<div class="ld-status ld-status-progress ld-primary-background">' . esc_html__( 'Start ', 'buddyboss-theme' ) . sprintf( esc_html__( '%s', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( 'course' ) ) . '</div>';

						}
					} else {
						if ( in_array( $course_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ), true ) ) {

							$progress = learndash_course_progress(
								array(
									'user_id'   => $user_id,
									'course_id' => $course_id,
									'array'     => true,
								)
							);

							// sfwd-lessons status.
							switch ( $course_type ) {
								case 'sfwd-lessons';
									$is_completed = learndash_is_lesson_complete( $user_id, $post->ID, $course_id );
									$s_lable      = 'lesson';
									break;
								case 'sfwd-topic';
									$_GET['course_id'] = $course_id; // Set this course_id so learndash_get_course_id work in below function.
									$is_completed      = learndash_is_topic_complete( $user_id, $post->ID );
									$s_lable           = 'topic';
									break;
								case 'sfwd-quiz';
									$is_completed = learndash_is_quiz_complete( $user_id, $post->ID, $course_id );
									$s_lable      = 'quiz';
									break;
							}

							$course_access = sfwd_lms_has_access( $course_id, $user_id );
							if ( is_user_logged_in() && isset( $course_access ) && $course_access ) {
								$status = 'incomplete';
								$status = ( $is_completed ) ? 'completed' : 'incomplete';

								learndash_status_bubble( $status );
							} elseif ( ( 'open' === $price_type && 0 === $progress['percentage'] ) || ( 'open' !== $price_type && $has_access && 0 === $progress['percentage'] ) ) {
								echo '<div class="ld-status ld-status-progress ld-primary-background">' . sprintf( esc_html_x( 'Start %s ', 'Start ribbon', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( $s_lable ) ) . '</div>';
							}
						}
					}
					?>

					<?php
					if ( 1 == $enable_video && ! empty( $embed_code ) ) :
						echo $embed_code;
					elseif ( has_post_thumbnail() ) :
						the_post_thumbnail( $thumb_size );
					endif;
					?>
				</a>
			</div>

		<?php endif; ?>

		<?php if ( 'true' != $shortcode_atts['show_content'] ) : ?>
			<style type="text/css">
				.bb-card-course-details {
					display: none !important;
				}
			</style>
		<?php endif; ?>

			<div class="bb-card-course-details">
				<?php
				if ( 'sfwd-courses' === get_post_type() || 'sfwd-lessons' === get_post_type() ) {

					if ( 'sfwd-courses' === get_post_type() ) {
						$lessons_arr = learndash_get_lesson_list( get_the_ID(), array( 'num' => - 1 ) );
						$lessons_arr = $lessons_arr ? $lessons_arr : array();
						$_count      = count( $lessons_arr );
						$p_lable     = 'lessons';
						$s_lable     = 'lesson';
					} elseif ( 'sfwd-lessons' === get_post_type() ) {
						$topic_arr = learndash_get_topic_list( get_the_ID(), $course_id );
						$topic_arr = $topic_arr ? $topic_arr : array();
						$_count    = count( $topic_arr );
						$p_lable   = 'topics';
						$s_lable   = 'topic';
					}

					$total_str = (
					$_count > 1
						? sprintf( __( '%1$s %2$s', 'buddyboss-theme' ), $_count, LearnDash_Custom_Label::get_label( $p_lable ) )
						: sprintf( __( '%1$s %2$s ', 'buddyboss-theme' ), $_count, LearnDash_Custom_Label::get_label( $s_lable ) ) );

					if ( $_count > 0 ) {
						echo '<div class="course-lesson-count">' . $total_str . '</div>';
					} else {
						echo '<div class="course-lesson-count">' . __( '0 ', 'buddyboss-theme' ) . sprintf( __( '%s', 'buddyboss-theme' ), LearnDash_Custom_Label::get_label( $p_lable ) ) . '</div>';
					}
				}

				?>
				<h2 class="bb-course-title"><a href="<?php echo esc_url( get_the_permalink() ); ?>"><?php the_title(); ?></a></h2>
				<?php
				if ( ! empty( $short_description ) ) :
					?>
					<p class="entry-content"><?php echo do_shortcode( htmlspecialchars_decode( $short_description, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ); ?></p>
					<?php
				endif;

				if ( buddyboss_theme_get_option( 'learndash_course_author' ) ) {
					SFWD_LMS::get_template( 'course_list_course_author', compact( 'post' ), true );
				}
				?>
				<?php if ( isset( $shortcode_atts['progress_bar'] ) && $shortcode_atts['progress_bar'] == 'true' ) : ?>
					<div class="course-progress-wrap">
						<?php
						learndash_get_template_part(
							'modules/progress.php',
							array(
								'context'   => 'course',
								'user_id'   => get_current_user_id(),
								'course_id' => get_the_ID(),
							),
							true
						);
						?>
					</div>
				<?php endif; ?>

				<?php if ( isset( $button_text ) && ! empty( $button_text ) ) : ?>
					<p class="entry-content ld_course_grid_button"><a class="btn btn-primary" role="button" href="<?php echo esc_url( $button_link ); ?>" rel="bookmark"><?php echo esc_attr( $button_text ); ?></a></p>
					<?php
				endif;

				$course_price           = trim( learndash_get_course_meta_setting( get_the_ID(), 'course_price' ) );
				$course_price_type      = learndash_get_course_meta_setting( get_the_ID(), 'course_price_type' );
				$course_pricing         = learndash_get_course_price( get_the_ID() );
				$user_course_has_access = sfwd_lms_has_access( get_the_ID(), get_current_user_id() );
				$is_enrolled            = false;
				if ( $user_course_has_access ) {
					$is_enrolled = true;
				} else {
					$is_enrolled = false;
				}
				// Price.
				if ( ! empty( $course_price ) && ! $is_enrolled ) {
					?>
					<div class="bb-course-footer bb-course-pay">
					<span class="course-fee">
					<?php
					if ( 'closed' !== $course_pricing['type'] ) :
						echo wp_kses_post( '<span class="ld-currency">' . function_exists( 'learndash_get_currency_symbol' ) ?
							learndash_get_currency_symbol() : learndash_30_get_currency_symbol() . '</span> ' );
					endif;
					?>
					<?php echo wp_kses_post( $course_pricing['price'] ); ?>
				</span>
					</div>
					<?php
				}

				?>

			</div><!-- .entry-header -->

	</div><!-- #post-## -->
</div>
<?php