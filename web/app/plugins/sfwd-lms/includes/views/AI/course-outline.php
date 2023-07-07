<?php
/**
 * Course outline form view file.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Modules\AI\Course_Outline
 *
 * @var string $api_key
 */

use LearnDash\Core\Modules\AI\Course_Outline;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ld_wrapper_class = '';
if ( isset( $_GET['error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ld_message        = urldecode( wp_kses_post( wp_unslash( $_GET['error'] ) ) );
	$ld_wrapper_class .= ' ld-text-red-800 ld-bg-red-50 ld-border-red-500';
} elseif ( isset( $_GET['success'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ld_message        = urldecode( wp_kses_post( wp_unslash( $_GET['success'] ) ) );
	$ld_wrapper_class .= ' ld-text-green-800 ld-bg-green-50 ld-border-green-500';
}

$ld_settings_url = add_query_arg(
	[
		'section-advanced' => 'settings_ai_integrations',
	],
	menu_page_url( 'learndash_lms_advanced', false )
);
?>

<div class="ld-container ld-mx-auto">
	<div class="ld-flex ld-flex-wrap ld-flex-col ld-items-center">
		<div class="ld-flex ld-mt-6">
			<h1 class="ld-text-4xl">
				<?php
				echo wp_sprintf(
					// translators: Course label.
					esc_html__( 'Create %s Outline from AI.', 'learndash' ),
					esc_html( learndash_get_custom_label( 'course' ) )
				);
				?>
			</h1>
		</div>
		<div class="ld-flex ld-mt-6">
			<p class="ld-text-xl">
				<?php
				echo wp_sprintf(
					// translators: Course label.
					esc_html__( 'You can take advantage of the latest breakthrough AI technology to create your %s outline.', 'learndash' ),
					esc_html( learndash_get_custom_label_lower( 'course' ) )
				);
				?>
			</p>
		</div>
		<div class="ld-flex ld-mt-10 ld-w-1/2 ld-justify-center">
			<?php if ( empty( $api_key ) ) : ?>
				<div class="notice notice-error">
					<p>
						<?php
						echo wp_sprintf(
							// translators: HTML tags.
							esc_html__( '%1$sClick here%2$s to enter your OpenAI API key.', 'learndash' ),
							'<a class="ld-underline ld-text-[#2271b1]" href="' . esc_url( $ld_settings_url ) . '">',
							'</a>'
						);
						?>
					</p>
				</div>
			<?php else : ?>
				<div class="ld-w-full">
					<form class="ld-bg-white ld-shadow-lg ld-rounded-md ld-p-8" method="post" action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
						<?php if ( isset( $ld_message ) ) : ?>
							<div class="ld-mb-4 ld-p-2 ld-text-sm ld-font-semibold ld-rounded ld-border ld-border-solid <?php echo esc_attr( $ld_wrapper_class ); ?>">
								<span>
									<?php echo wp_kses_post( wp_unslash( $ld_message ) ); ?>
								</span>
							</div>
						<?php endif; ?>
						<input type="hidden" name="action" value="<?php echo esc_attr( Course_Outline::$slug ); ?>">
						<?php wp_nonce_field( Course_Outline::$slug ); ?>

						<div class="ld-w-full ld-mb-4">
							<label
								for="course_id"
								class="ld-block ld-text-gray-700 ld-text-sm ld-font-bold ld-mb-1"
							>
								<?php
								echo wp_sprintf(
									// translators: Course label.
									esc_html__( '%s Title', 'learndash' ),
									esc_html( learndash_get_custom_label( 'course' ) )
								);
								?>
							</label>
							<select class="ld-w-full ld-block !ld-max-w-full" type="text" name="course_id" id="course_id" required>

							</select>
							<p class="ld-w-full ld-italic">
								<?php
								echo wp_sprintf(
									// translators: Course label.
									esc_html__( 'You can select an existing %s or create a new one.', 'learndash' ),
									esc_html( learndash_get_custom_label_lower( 'course' ) )
								);
								?>
							</p>
						</div>

						<div class="ld-w-full ld-mb-4">
							<label
								for="lesson_count"
								class="ld-block ld-text-gray-700 ld-text-sm ld-font-bold ld-mb-1"
							>
								<?php
								echo wp_sprintf(
									// translators: Lessons label.
									esc_html__( 'Number of %s', 'learndash' ),
									esc_html( learndash_get_custom_label( 'lessons' ) )
								);
								?>
							</label>
							<input class="ld-w-full ld-block !ld-max-w-full" type="number" name="lesson_count" id="lesson_count" min="1" max="30" required>
							<p class="ld-w-full ld-italic">
								<?php
								echo wp_sprintf(
									// translators: Lessons label.
									esc_html__( 'Number of %s you want to generate the outline for.', 'learndash' ),
									esc_html( learndash_get_custom_label_lower( 'lessons' ) )
								);
								?>
							</p>
						</div>

						<div class="ld-w-full ld-mb-4">
							<label
								for="course_idea"
								class="ld-block ld-text-gray-700 ld-text-sm ld-font-bold ld-mb-1"
							>
								<?php
								echo wp_sprintf(
									// translators: Course label.
									esc_html__( 'Describe Your %s', 'learndash' ),
									esc_html( learndash_get_custom_label( 'course' ) )
								);
								?>
							</label>
							<textarea class="ld-w-full ld-block !ld-max-w-full ld-py-1 ld-px-1" name="course_idea" id="course_idea" required></textarea>
							<p class="ld-w-full ld-italic">
								<?php
								echo wp_sprintf(
									// translators: Course label.
									esc_html__( '%s idea in clear and brief description.', 'learndash' ),
									esc_html( learndash_get_custom_label( 'course' ) )
								);
								?>
							</p>
						</div>

						<div class="ld-w-full">
							<button type="submit" class="button button-primary !ld-flex !ld-ml-auto">
								<?php
								echo wp_sprintf(
									// translators: Lessons label.
									esc_html__( 'Create %s', 'learndash' ),
									esc_html( learndash_get_custom_label( 'lessons' ) )
								);
								?>
							</button>
						</div>
					</form>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
