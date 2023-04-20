<?php
/**
 * Setup page template
 *
 * @package LearnDash_Settings_Page_Setup
 *
 * @var array<string, array>  $steps
 * @var array<string, string> $overview_video
 * @var array<string, string> $overview_article
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>

<div class="wrap learndash-setup">
	<div class="logo">
		<img
			src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . '/assets/images/learndash.svg' ); ?>"
			alt="LearnDash"
		/>
	</div>

	<div class="hero">
		<h1><?php esc_html_e( 'Set up your site', 'learndash' ); ?></h1>
		<p class="tagline">
			<?php esc_html_e( 'Our set up wizard will help you get the most out of your site.', 'learndash' ); ?>
			</h2>
	</div>

	<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
	<?php foreach ( $steps as $step ) : ?>
		<div
			class="box <?php echo esc_attr( $step['class'] ); ?>"
			data-url="<?php echo esc_url( $step['url'] ); ?>"
			data-completed="<?php echo esc_attr( (string) $step['completed'] ); ?>"
		>
			<div class="heading">
				<div class="title-wrapper">
					<h2><?php echo esc_html( $step['title'] ); ?></h2>
					<p class="description"><?php echo esc_html( $step['description'] ); ?></p>
				</div>
				<?php
				if ( isset( $step['completed'] ) && $step['completed'] ) {
					SFWD_LMS::get_view(
						'setup/components/status-completed',
						array(
							'step' => $step,
						),
						true
					);
				} elseif ( ! empty( $step['time_in_minutes'] ) ) {
					SFWD_LMS::get_view(
						'setup/components/status-time',
						array(
							'step' => $step,
						),
						true
					);
				}
				?>
			</div>
			<div class="content">
				<?php if ( ! empty( $step['content_path'] ) ) : ?>
					<?php
					SFWD_LMS::get_view(
						$step['content_path'],
						compact(
							'step',
							'overview_video',
							'overview_article'
						),
						true
					);
					?>
				<?php else : ?>
					<div class="icon-wrapper">
						<div class="icon">
							<img src="<?php echo esc_url( $step['icon_url'] ); ?>">
						</div>
					</div>
					<div class="text-wrapper">
						<h3><?php echo esc_html( $step['action_label'] ); ?></h3>
						<p class="description"><?php echo esc_html( $step['action_description'] ); ?>
						</p>
					</div>
					<div class="button-wrapper">
						<?php if ( ! isset( $step['completed'] ) || ! $step['completed'] ) : ?>
							<?php if ( isset( $step['button_type'] ) && $step['button_type'] === 'arrow' ) : ?>
								<a href="<?php echo esc_url( $step['url'] ); ?>">
									<span class="dashicons dashicons-arrow-right-alt2"></span>
								</a>
							<?php elseif ( $step['button_type'] === 'button' ) : ?>
								<a
									class="button <?php echo esc_attr( $step['button_class'] ); ?>"
									href="#"
								>
									<?php echo esc_html( $step['button_text'] ); ?>
								</a>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
<div class="video-wrapper">
	<div class="background"></div>
	<div class="video">
		<div class="text-wrapper"><?php esc_html_e( 'Loading', 'learndash' ); ?>...</div>
		<div class="buttons-wrapper">
			<div class="close">
				<span class="icon dashicons dashicons-no-alt"></span>
				<span class="text"><?php esc_html_e( 'Close', 'learndash' ); ?></span>
			</div>
			<div class="clear"></div>
		</div>
		<div class="iframe-wrapper">
			<iframe
				class="video-iframe"
				id="video-iframe"
				width="516"
				height="315"
				src=""
				frameborder="0"
				allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
				allowfullscreen
			></iframe>
		</div>
	</div>
</div>
