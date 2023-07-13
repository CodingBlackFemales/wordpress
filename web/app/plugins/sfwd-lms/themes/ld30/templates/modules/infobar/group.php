<?php
/**
 * LearnDash LD30 Displays the infobar in group context
 *
 * @var int    $group_id     Group ID.
 * @var int    $user_id      User ID.
 * @var bool   $has_access   User has access to group or is enrolled.
 * @var bool   $group_status User's Group Status. Completed, No Started, or In Complete.
 * @var object $post         Group Post Object.
 *
 * @since 3.2.0
 *
 * @package LearnDash\Templates\LD30\Modules
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$group_pricing = learndash_get_group_price( $group_id );

if ( is_user_logged_in() && isset( $has_access ) && $has_access ) :
	?>
	<div class="ld-course-status ld-course-status-enrolled">
		<?php
		/**
		 * Action to add custom content inside the ld-course-status infobox before the progress bar
		 *
		 * @since 3.2.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $group_id  Group ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-group-infobar-access-progress-before', get_post_type(), $group_id, $user_id );

		learndash_get_template_part(
			'modules/progress-group.php',
			array(
				'context'  => 'group',
				'user_id'  => $user_id,
				'group_id' => $group_id,
			),
			true
		);

		/**
		 * Action to add custom content inside the ld-course-status infobox after the progress bar
		 *
		 * @since 3.2.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $group_id  Group ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-group-infobar-access-progress-after', get_post_type(), $group_id, $user_id );

		learndash_status_bubble( $group_status );

		/**
		 * Action to add custom content inside the ld-course-status infobox after the access status
		 *
		 * @since 3.2.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $group_id  Group ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-group-infobar-access-status-after', get_post_type(), $group_id, $user_id );
		?>

	</div> <!--/.ld-course-status-->

<?php elseif ( 'open' !== $group_pricing['type'] ) : ?>

	<div class="ld-course-status ld-course-status-not-enrolled">

		<?php
		/**
		 * Action to add custom content inside the un-enrolled ld-course-status infobox before the status
		 *
		 * @since 3.2.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $group_id  Group ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-group-infobar-noaccess-status-before', get_post_type(), $group_id, $user_id );
		?>

		<div class="ld-course-status-segment ld-course-status-seg-price">

			<?php do_action( 'learndash-group-infobar-status-cell-before', get_post_type(), $group_id, $user_id ); ?>

			<span class="ld-course-status-label"><?php echo esc_html__( 'Current Status', 'learndash' ); ?></span>
			<div class="ld-course-status-content">
				<span class="ld-status ld-status-waiting ld-tertiary-background" data-ld-tooltip="
				<?php
					printf(
						// translators: placeholder: group
						esc_attr_x( 'Enroll in this %s to get access', 'placeholder: group', 'learndash' ),
						esc_html( learndash_get_custom_label_lower( 'group' ) )
					);
				?>
				">
				<?php esc_html_e( 'Not Enrolled', 'learndash' ); ?></span>
			</div>

			<?php do_action( 'learndash-group-infobar-status-cell-after', get_post_type(), $group_id, $user_id ); ?>

		</div> <!--/.ld-course-status-segment-->

		<?php
		/**
		 * Action to add custom content inside the un-enrolled ld-course-status infobox before the price
		 *
		 * @since 3.0.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $group_id  Group ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-group-infobar-noaccess-price-before', get_post_type(), $group_id, $user_id );

		/**
		 * Fires inside the un-enrolled course infobox before the price.
		 *
		 * @since 3.0.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $course_id Course ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-course-infobar-noaccess-price-before', get_post_type(), $group_id, $user_id );
		?>

		<div class="ld-course-status-segment ld-course-status-seg-price ld-course-status-mode-<?php echo esc_attr( $group_pricing['type'] ); ?>">

			<?php
			/**
			 * Fires before the course infobar price cell.
			 *
			 * @since 3.0.0
			 *
			 * @param string|false $post_type Post type slug.
			 * @param int          $course_id Course ID.
			 * @param int          $user_id   User ID.
			 */
			do_action( 'learndash-course-infobar-price-cell-before', get_post_type(), $group_id, $user_id );
			?>

			<span class="ld-course-status-label"><?php echo esc_html__( 'Price', 'learndash' ); ?></span>

			<div class="ld-course-status-content">
			<?php
			// Some simple price settings validation logic. Not 100%.
			$group_pricing = wp_parse_args(
				$group_pricing,
				array(
					'type'             => LEARNDASH_DEFAULT_GROUP_PRICE_TYPE,
					'price'            => '',
					'interval'         => '',
					'frequency'        => '',
					'trial_price'      => '',
					'trial_interval'   => '',
					'trial_frequency'  => '',
					'repeats'          => '',
					'repeat_frequency' => '',
				)
			);

			if ( 'subscribe' === $group_pricing['type'] ) {
				if ( ( empty( $group_pricing['price'] ) ) || ( empty( $group_pricing['interval'] ) ) || ( empty( $group_pricing['frequency'] ) ) ) {
					$group_pricing['type']             = LEARNDASH_DEFAULT_GROUP_PRICE_TYPE;
					$group_pricing['interval']         = '';
					$group_pricing['frequency']        = '';
					$group_pricing['trial_price']      = '';
					$group_pricing['trial_interval']   = '';
					$group_pricing['trial_frequency']  = '';
					$group_pricing['repeats']          = '';
					$group_pricing['repeat_frequency'] = '';
				} else {
					if ( empty( $group_pricing['trial_price'] ) ) {
						$group_pricing['trial_price'] = 0;
					} elseif ( ( empty( $group_pricing['trial_interval'] ) ) || ( empty( $group_pricing['trial_frequency'] ) ) ) {
						$group_pricing['trial_price'] = '';
					}
				}
			}

			if ( 'subscribe' !== $group_pricing['type'] ) {
				?>
				<span class="ld-course-status-price">
					<?php
					if ( ! empty( $group_pricing['price'] ) ) {
						echo wp_kses_post( learndash_get_price_formatted( $group_pricing['price'] ) );
					} elseif ( in_array( $group_pricing['type'], array( 'closed', 'free' ), true ) ) {
							/**
							 * Filters label to be displayed when there is no price set for a course or it is closed.
							 *
							 * @since 3.0.0
							 *
							 * @param string $label The label displayed when there is no price.
							 */
							$label = apply_filters( 'learndash_no_price_price_label', ( 'closed' === $group_pricing['type'] ? __( 'Closed', 'learndash' ) : __( 'Free', 'learndash' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Late escaped on output
							echo esc_html( $label );
					}
					?>
				</span>
				<?php
			} elseif ( 'subscribe' === $group_pricing['type'] ) {
				if ( ! empty( $group_pricing['price'] ) ) {
					if ( ! empty( $group_pricing['trial_frequency'] ) ) {
						?>
						<span class="ld-course-status-trial-price">
						<?php
						echo '<p class="ld-text ld-trial-text">';
						echo wp_kses_post( learndash_get_price_formatted( $group_pricing['trial_price'] ) );
						echo '</p>';
						echo '<p class="ld-trial-pricing ld-pricing">';
						if ( ( ! empty( $group_pricing['trial_interval'] ) ) && ( ! empty( $group_pricing['trial_frequency'] ) ) ) {
							printf(
								// translators: placeholders: Trial interval, Trial frequency.
								esc_html_x( 'Trial price for %1$s %2$s, then', 'placeholders: Trial interval, Trial frequency', 'learndash' ),
								absint( $group_pricing['trial_interval'] ),
								esc_html( $group_pricing['trial_frequency'] )
							);
						}
						echo '</p>'; // closing '<p class="ld-trial-pricing ld-pricing">'
						?>
						</span>
						<span class="ld-course-status-course-price">
							<?php
							echo '<p class="ld-text ld-course-text">';
							echo wp_kses_post( learndash_get_price_formatted( $group_pricing['price'] ) );
							echo '</p>';
							echo '<p class="ld-course-pricing ld-pricing">';

							if ( ( ! empty( $group_pricing['interval'] ) ) && ( ! empty( $group_pricing['frequency'] ) ) ) {
								printf(
									// translators: placeholders: %1$s Interval of recurring payments (number), %2$s Frequency of recurring payments: day, week, month or year.
									esc_html_x( 'Full price every %1$s %2$s afterward', 'Recurring duration message', 'learndash' ),
									absint( $group_pricing['interval'] ),
									esc_html( $group_pricing['frequency'] )
								);

								if ( ! empty( $group_pricing['repeats'] ) ) {
									echo ' ';
									printf(
										// translators: placeholders: %1$s Number of times the recurring payment repeats, %2$s Frequency of recurring payments: day, week, month, year.
										esc_html__( 'for %1$s %2$s', 'learndash' ),
										// Get correct total time by multiplying interval by number of repeats
										absint( $group_pricing['interval'] * $group_pricing['repeats'] ),
										esc_html( $group_pricing['repeat_frequency'] )
									);
								}
							}

							echo '</p>'; // closing '<p class="ld-course-pricing ld-pricing">'.
							?>
						</span>
						<?php
					} else {
						?>
						<span class="ld-course-status-price">
						<?php
						if ( ! empty( $group_pricing['price'] ) ) {
							echo wp_kses_post( learndash_get_price_formatted( $group_pricing['price'] ) );
						}
						?>
						</span>
						<span class="ld-text ld-recurring-duration">
								<?php
								if ( ( ! empty( $group_pricing['interval'] ) ) && ( ! empty( $group_pricing['frequency'] ) ) ) {
									echo sprintf(
										// translators: Recurring duration message.
										esc_html_x( 'Every %1$s %2$s', 'Recurring duration message', 'learndash' ),
										esc_html( $group_pricing['interval'] ),
										esc_html( $group_pricing['frequency'] )
									);

									if ( ( ! empty( $group_pricing['repeats'] ) ) && ( ! empty( $group_pricing['repeat_frequency'] ) ) ) {
										printf(
											// translators: placeholders: %1$s Number of times the recurring payment repeats, %2$s Frequency of recurring payments: day, week, month, year.
											esc_html__( ' for %1$s %2$s', 'learndash' ),
											// Get correct total time by multiplying interval by number of repeats
											absint( $group_pricing['interval'] * $group_pricing['repeats'] ),
											esc_html( $group_pricing['repeat_frequency'] )
										);
									}
								}
								?>
						</span>
						<?php
					}
				}
			}
			?>
			</div>

			<?php
			/**
			 * Fires after the infobar price cell.
			 *
			 * @since 3.0.0
			 *
			 * @param string|false $post_type Post type slug.
			 * @param int          $course_id Course ID.
			 * @param int          $user_id   User ID.
			 */
			do_action( 'learndash-course-infobar-price-cell-after', get_post_type(), $group_id, $user_id );
			?>

		</div> <!--/.ld-group-status-segment-->

		<?php
		/**
		 * Action to add custom content inside the un-enrolled ld-course-status infobox before the action
		 *
		 * @since 3.2.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $group_id  Group ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-group-infobar-noaccess-action-before', get_post_type(), $group_id, $user_id );

		$group_status_class = apply_filters(
			'ld-course-status-segment-class',
			'ld-course-status-segment ld-course-status-seg-action status-' .
			( isset( $group_pricing['type'] ) ? sanitize_title( $group_pricing['type'] ) : '' )
		);
		?>

		<div class="<?php echo esc_attr( $group_status_class ); ?>">
			<span class="ld-course-status-label"><?php echo esc_html_e( 'Get Started', 'learndash' ); ?></span>
			<div class="ld-course-status-content">
				<div class="ld-course-status-action">
					<?php
						do_action( 'learndash-course-infobar-action-cell-before', get_post_type(), $group_id, $user_id );

						$login_model = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' );

						/** This filter is documented in themes/ld30/includes/shortcode.php */
						$login_url = apply_filters( 'learndash_login_url', ( 'yes' === $login_model ? '#login' : wp_login_url( get_permalink() ) ) );

					switch ( $group_pricing['type'] ) {
						case ( 'open' ):
						case ( 'free' ):
							if ( apply_filters( 'learndash_login_modal', true, $group_id, $user_id ) && ! is_user_logged_in() ) :
								echo '<a class="ld-button" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Login to Enroll', 'learndash' ) . '</a></span>';
								else :
									echo learndash_payment_buttons( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Payment button HTML
								endif;
							break;
						case ( 'paynow' ):
						case ( 'subscribe' ):
							// Price (Free / Price)
							$ld_payment_buttons = learndash_payment_buttons( $post );
							echo $ld_payment_buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Button HTML
							if ( apply_filters( 'learndash_login_modal', true, $group_id, $user_id ) && ! is_user_logged_in() ) :
								echo '<span class="ld-text">';
								if ( ! empty( $ld_payment_buttons ) ) {
									esc_html_e( 'or', 'learndash' );
								}
								echo '<a class="ld-login-text" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Login', 'learndash' ) . '</a></span>';
								endif;
							break;
						case ( 'closed' ):
							$button = learndash_payment_buttons( $post );
							if ( empty( $button ) ) :
								echo '<span class="ld-text">' . sprintf(
									// translators: placeholder: group
									esc_html_x( 'This %s is currently closed', 'placeholder: group', 'learndash' ),
									esc_html( learndash_get_custom_label_lower( 'group' ) )
								)
									. '</span>';
								else :
									echo $button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Button HTML
								endif;
							break;
					}

					/**
					 * Fires after the group infobar action cell.
					 *
					 * @since 3.2.0
					 *
					 * @param string|false $post_type Post type slug.
					 * @param int          $group_id  Group ID.
					 * @param int          $user_id   User ID.
					 */
					do_action( 'learndash-group-infobar-action-cell-after', get_post_type(), $group_id, $user_id );
					?>
				</div>
			</div>
		</div> <!--/.ld-group-status-action-->

		<?php
		/**
		 * Fires inside the un-enrolled group infobox after the price
		 *
		 * @since 3.2.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $group_id  Group ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-group-infobar-noaccess-price-after', get_post_type(), $group_id, $user_id );
		?>

	</div> <!--/.ld-course-status-->

<?php endif; ?>
