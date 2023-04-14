<?php

$product      = new LLMS_Product( get_the_ID() );
$is_enrolled  = llms_is_user_enrolled( get_current_user_id(), $product->get( 'id' ) );
$purchaseable = $product->is_purchasable();
$has_free     = $product->has_free_access_plan();
$free_only    = ( $has_free && ! $purchaseable );

if ( $is_enrolled ) {

	$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;

	if ( empty( $user_id ) ) {
		return 0;
	}

	$student                 = llms_get_student( $user_id );
	$trigger                 = $student->get_enrollment_trigger( get_the_ID() );
	$product                 = $student->get_enrollment_trigger_id( get_the_ID() );
	$product_enrollment_date = $student->get_enrollment_date( get_the_ID(), '', get_option( 'date_format' ) );
	$order                   = new LLMS_Order( $product );
	$product_expiry_date     = $order->get_access_expiration_date( get_option( 'date_format' ) );

}
?>

<li class="bb-course-item-wrap">
	<div class="bb-cover-list-item">
		<div class="bb-course-cover">
			<a title="<?php echo esc_attr( get_the_title( get_the_ID() ) ); ?>" href="<?php echo esc_url( get_the_permalink( get_the_ID() ) ); ?>" class="bb-cover-wrap bb-cover-wrap--llms">
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'full' );
				}
				?>
			</a>

		</div>

		<div class="bb-card-course-details">
			<?php
			$course      = new LLMS_Course( get_the_ID() );
			$instructors = $course->get_instructors( true );
			$author_id   = $instructors[0]['id'];
			$img         = get_avatar( $author_id, 28 );
			?>

			<h2 class="bb-course-title">
				<a href="<?php echo esc_url( get_the_permalink() ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>">
					<?php echo wp_kses_post( get_the_title() ); ?>
				</a>
			</h2>

			<div class="bb-course-excerpt">
				<?php
					echo wp_kses_post( wp_trim_words( get_the_excerpt(), 20 ) );
				?>
			</div>

			<?php
			$lifterlms_course_author = buddyboss_theme_get_option( 'lifterlms_course_author' );

			if ( isset( $lifterlms_course_author ) && ( $lifterlms_course_author == 1 ) ) :
				?>
				<?php
				$user_link = buddyboss_theme()->lifterlms_helper()->bb_llms_get_user_link( $author_id );
				?>
				<div class="bb-course-meta bb-course-meta--membership">
					<a href="<?php echo esc_url( $user_link ); ?>" class="item-avatar">
						<?php echo $img; ?>
					</a> <strong> <a href="<?php echo esc_url( $user_link ); ?>">
							<?php echo wp_kses_post( get_the_author_meta( 'first_name', $author_id ) ); ?>
						</a> </strong>
				</div>

				<?php
				if ( ! apply_filters( 'llms_product_pricing_table_enrollment_status', $is_enrolled ) && ( $purchaseable || $has_free ) ) {
					$plans     = $product->get_access_plans( $free_only );
					$min_price = 0;
					if ( count( $plans ) > 1 ) {
						$price_arr = array();
						$break     = false;
						foreach ( $plans as $plan ) {
							$price_key = $plan->is_on_sale() ? 'sale_price' : 'price';
							$price     = $plan->get_price( $price_key, array(), 'float' );
							if ( 0.0 === $price ) {
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
						<div class="llms-meta-aplans llms-meta-aplans--multiple flex align-items-center <?php echo $has_free ? esc_attr( 'llms-meta-aplans--hasFree' ) :  ''; ?>">
							<div class="llms-meta-aplans__price">
								<div class="llms-meta-aplans__smTag"><?php esc_html_e( 'Starts from', 'buddyboss-theme' ); ?></div>
								<div class="llms-meta-aplans__figure"><h3><?php echo wp_kses_post( $min_price ); ?></h3></div>
							</div>
							<div class="llms-meta-aplans__btn push-right"><a class="llms-button-secondary" href="<?php echo esc_url( get_the_permalink() ); ?>"><?php esc_html_e( 'See Plans', 'buddyboss-theme' ); ?></a></div>
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
						<div class="llms-meta-aplans__in">
							<div class="llms-meta-aplans__smTag"><?php esc_html_e( 'Enrolled on', 'buddyboss-theme' ); ?></div>
							<div class="llms-meta-aplans__inDate"><?php echo wp_kses_post( $product_enrollment_date ); ?></div>
						</div>
						<div class="llms-meta-aplans__in push-right">
							<div class="llms-meta-aplans__smTag"><?php esc_html_e( 'Expires on', 'buddyboss-theme' ); ?></div>
							<div class="llms-meta-aplans__inDate"><?php echo wp_kses_post( $product_expiry_date ); ?></div>
						</div>
					</div>
					<?php
				}
				?>
			<?php endif; ?>
		</div>
	</div>
</li>
