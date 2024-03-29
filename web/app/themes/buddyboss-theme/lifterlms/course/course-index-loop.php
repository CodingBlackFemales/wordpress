<?php

global $post;

$course = new LLMS_Course( get_the_ID() );

$llms_product             = new LLMS_Product( get_the_ID() );
$is_enrolled              = llms_is_user_enrolled( get_current_user_id(), $llms_product->get( 'id' ) );
$purchasable              = $llms_product->is_purchasable();
$has_free                 = $llms_product->has_free_access_plan();
$free_only                = ( $has_free && ! $purchasable );
$user_id                  = empty( $user_id ) ? get_current_user_id() : $user_id;
$video                    = '';
$lessons_ids              = buddyboss_theme()->lifterlms_helper()->get_course_lessons( get_the_ID() );
$number_of_lessons        = count( $lessons_ids );
$instructors              = $course->get_instructors( true );
$lifter_lms_course_author = buddyboss_theme_get_option( 'lifterlms_course_author' );
$product_enrollment_date  = '';
$product_expiry_date      = '';
$progress                 = 0;

if ( $is_enrolled ) {

	if ( empty( $user_id ) ) {
		return 0;
	}

	$student = llms_get_student( $user_id );
	$trigger = $student->get_enrollment_trigger( get_the_ID() );
	$product = $student->get_enrollment_trigger_id( get_the_ID() );

	if ( $product ) {
		$product_enrollment_date = $student->get_enrollment_date( get_the_ID(), '', get_option( 'date_format' ) );
		$llms_order              = new LLMS_Order( $llms_product );
		$product_expiry_date     = $llms_order->get_access_expiration_date( get_option( 'date_format' ) );
	} else {
		if ( strpos( $trigger, 'membership_' ) !== false ) {
			$trigger_arr = explode( 'membership_', $trigger );
			if ( is_array( $trigger_arr ) && isset( $trigger_arr['1'] ) && (int) $trigger_arr['1'] > 0 ) {
				$membership_id           = (int) $trigger_arr['1'];
				$product_enrollment_date = $student->get_enrollment_date( $membership_id, '', get_option( 'date_format' ) );
				$llms_product            = $student->get_enrollment_trigger_id( $membership_id );
				$llms_order              = new LLMS_Order( $llms_product );
				$product_expiry_date     = $llms_order->get_access_expiration_date( get_option( 'date_format' ) );
			} else {
				$product_enrollment_date = '';
				$product_expiry_date     = '';
			}
		} else {
			$product_enrollment_date = '';
			$product_expiry_date     = '';
		}
	}

	$progress     = $course->get_percent_complete( $user_id );
	$llms_status  = __( 'Complete', 'buddyboss-theme' );
	$status_class = ' ld-status-complete';
	if ( is_nan( $progress ) || ( $progress === 0 ) ) {
		$llms_status  = __( 'Start Course', 'buddyboss-theme' );
		$status_class = ' ld-status-progress';
	} else {
		if ( $progress < 100 ) :
			$llms_status  = __( 'In Progress', 'buddyboss-theme' );
			$status_class = ' ld-status-progress ';
		endif;
	}
} else {
	$llms_status  = __( 'Not Enrolled', 'buddyboss-theme' );
	$status_class = ' ld-status-progress ';
}

if ( ! empty( $post->post_type ) && $post->post_type === 'course' ) {
	if ( $course->get( 'tile_featured_video' ) === 'yes' ) {
		$video = $course->get_video();
		if ( $video ) {
			$course_video_tile = 'bb-course-cover--videoTile';
		}
	}
}
?>
<li class="bb-course-item-wrap">
	<div class="bb-cover-list-item">
		<?php
		if ( ! empty( $post->post_type ) && $post->post_type === 'course' ) {
			if ( $course->get( 'tile_featured_video' ) === 'yes' && $video ) {
				echo '<div class="bb-course-cover bb-course-cover--videoTile">' . $video . '</div>';
			} else {
				?>
				<div class="bb-course-cover">
					<a title="<?php echo esc_attr( get_the_title( get_the_ID() ) ); ?>" href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>" class="bb-cover-wrap bb-cover-wrap--llms">
						<div class="ld-status ld-primary-background <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $llms_status ); ?></div>
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail( 'full' );
						}
						?>
					</a>
				</div>
				<?php
			}
		} else {
			?>
			<div class="bb-course-cover">
				<a title="<?php echo esc_attr( get_the_title( get_the_ID() ) ); ?>" href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>" class="bb-cover-wrap bb-cover-wrap--llms">
					<div class="ld-status ld-primary-background <?php echo $status_class; ?>"><?php echo esc_html( $llms_status ); ?></div>
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'full' );
					}
					?>
				</a>
			</div>
		<?php } ?>

		<div class="bb-card-course-details">

			<div class="course-details-verbose">
				<div class="course-lesson-count">
					<?php
					printf(
						_n( '%s Lesson', '%s Lessons', $number_of_lessons, 'buddyboss-theme' ),
						number_format_i18n( $number_of_lessons )
					);
					?>
				</div>

				<h2 class="bb-course-title">
					<a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>">
						<?php echo get_the_title(); ?>
					</a>
				</h2>

				<?php

				if ( isset( $lifter_lms_course_author ) && ( $lifter_lms_course_author === 1 ) ) :
					$author_id = ( ! empty( $instructors ) ? current( $instructors )['id'] : 0 );
					$img       = get_avatar( $author_id, 28 );
					?>
					<div class="bb-course-meta">
						<?php
						$user_link = buddyboss_theme()->lifterlms_helper()->bb_llms_get_user_link( $author_id );
						?>
						<a href="<?php echo esc_url( $user_link ); ?>" class="item-avatar"><?php echo $img; ?></a> <strong> <a href="<?php echo esc_url( $user_link ); ?>"><?php echo esc_html( get_the_author_meta( 'first_name', $author_id ) ); ?></a> </strong>
					</div>
				<?php endif; ?>

				<?php
				if ( is_user_logged_in() && $is_enrolled ) {
					$user_progress = round( $progress, 2 );
					buddyboss_theme()->lifterlms_helper()->lifterlms_course_progress_bar( $user_progress, false, false, true, $lessons_ids );
				} else {
					?>
					<?php if ( has_excerpt() ) { ?>
						<div class="bb-course-excerpt">
							<?php echo wp_trim_words( get_the_excerpt(), 20 ); ?>
						</div>
					<?php } ?>
				<?php } ?>
			</div>

			<?php
			if ( ! apply_filters( 'llms_product_pricing_table_enrollment_status', $is_enrolled ) && ( $purchasable || $has_free ) ) {
				$plans     = $llms_product->get_access_plans( $free_only );
				$min_price = 0;
				if ( count( $plans ) > 1 ) {
					$price_arr = array();
					$break     = false;
					foreach ( $plans as $plan ) {
						$price_key = $plan->is_on_sale() ? 'sale_price' : 'price';
						$price     = $plan->get_price( $price_key, array(), 'float' );
						if ( $price === 0.0 ) {
							$price = $plan->get_price( $price_key, array(), 'html' );
							$break = true;
							break;
						}
						$price_arr[] = $price;
					}
					if ( $break ) {
						$min_price = $price;
					} else {
						$minimum   = min( $price_arr );
						$price     = llms_price( $minimum, array() );
						$min_price = $price;
					}
					?>
					<div class="llms-meta-aplans llms-meta-aplans--multiple flex align-items-center <?php echo $has_free ? 'llms-meta-aplans--hasFree' : ''; ?>">
						<div class="llms-meta-aplans__price">
							<div class="llms-meta-aplans__smTag"><?php esc_html_e( 'Starts from', 'buddyboss-theme' ); ?></div>
							<div class="llms-meta-aplans__figure"><h3><?php echo $min_price; ?></h3></div>
						</div>
						<div class="llms-meta-aplans__btn push-right"><a class="llms-button-secondary" href="<?php echo esc_url( get_permalink() ); ?>"><?php esc_html_e( 'See Plans', 'buddyboss-theme' ); ?></a>
						</div>
					</div>
					<?php
				} else {
					foreach ( $plans as $plan ) {
						$price_key = $plan->is_on_sale() ? 'sale_price' : 'price';
						$price     = $plan->get_price( $price_key );
						?>
						<div class="llms-meta-aplans flex align-items-center <?php echo $has_free ? 'llms-meta-aplans--hasFree' : ''; ?>">
							<div class="llms-meta-aplans__price">
								<div class="llms-meta-aplans__figure">
									<h3><?php echo wp_kses_post( $price ); ?></h3>
								</div>
							</div>
							<div class="llms-meta-aplans__btn push-right">
								<a class="btn-meta-join" href="<?php echo esc_url( $plan->get_checkout_url() ); ?>"><i class="bb-icon-l bb-icon-plus"></i><?php echo wp_kses_post( $plan->get_enroll_text() ); ?></a>
							</div>
						</div>
						<?php
					}
				}
			} elseif ( apply_filters( 'llms_product_pricing_table_enrollment_status', $is_enrolled ) ) {
				?>
				<div class="llms-meta-aplans llms-meta-aplans--enrolled flex align-items-center">
					<?php

					if ( $product_enrollment_date !== '' ) {
						?>
						<div class="llms-meta-aplans__in">
							<div class="llms-meta-aplans__smTag"><?php esc_html_e( 'Enrolled on', 'buddyboss-theme' ); ?></div>
							<div class="llms-meta-aplans__inDate"><?php echo $product_enrollment_date; ?></div>
						</div>
						<?php
					}

					if ( $product_expiry_date !== '' ) {
						?>
						<div class="llms-meta-aplans__in push-right">
							<div class="llms-meta-aplans__smTag"><?php esc_html_e( 'Expires on', 'buddyboss-theme' ); ?></div>
							<div class="llms-meta-aplans__inDate"><?php echo $product_expiry_date; ?></div>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</li>
