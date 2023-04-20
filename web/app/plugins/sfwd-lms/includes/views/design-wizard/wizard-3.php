<?php
/**
 * Page 3 of the design wizard template
 *
 * @package LearnDash_Design_Wizard
 *
 * @var array<string, mixed> $template_details
 * @var array<string, array<string, array<string, string>>> $palettes
 * @var LearnDash_Design_Wizard $design_wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>
<div class="design-wizard">
	<div class="sidebar">
		<div class="logo">
            <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
			<img src="<?php echo esc_url( \LEARNDASH_LMS_PLUGIN_URL . '/assets/images/learndash.svg' ); ?>" alt="LearnDash" >
		</div>
		<div class="header">
			<div class="title-wrapper">
				<h1 class="title">
					<?php esc_html_e( 'Pick some colors', 'learndash' ); ?>
				</h1>
				<div class="reset">
					<button
						href="#"
						class="reset-palette-button"
					>
						<span class="dashicons dashicons-image-rotate"></span>
					</button>
				</div>
			</div>
			<p class="description">
				<?php
				esc_html_e(
					'Let\'s get you some starting colors. 
                You can always update, expand, and change these later.',
					'learndash'
				);
				?>
			</p>
			<div class="palettes">
				<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
				<?php foreach ( $palettes as $palette_id => $palette ) : ?>
				<div
					class="palette"
					data-id="<?php echo esc_attr( $palette_id ); ?>"
				>
					<div class="colors">
						<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
						<?php foreach ( $palette['colors'] as $color ) : ?>
						<div
							class="color"
							style="background-color: <?php echo esc_attr( $color ); ?>;"
						></div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<div class="content">
		<div class="header">
			<div class="exit">
				<span class="text"><?php esc_html_e( 'Exit to Setup', 'learndash' ); ?></span>
                <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
				<img src="<?php echo esc_url( \LEARNDASH_LMS_PLUGIN_URL . '/assets/images/design-wizard/svg/exit.svg' ); ?>" >
			</div>
		</div>
		<?php
			SFWD_LMS::get_view(
				'design-wizard/live-preview',
				compact( 'template_details', 'design_wizard' ),
				true
			);
			?>
		<div class="footer">
			<div class="back">
                <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
				<img class="icon" src="<?php echo esc_url( \LEARNDASH_LMS_PLUGIN_URL . '/assets/images/design-wizard/svg/back.svg' ); ?>" > 
				<span class="text"><?php esc_html_e( 'Back', 'learndash' ); ?></span>
			</div>
			<div class="steps">
				<ol class="list">
					<li class="active"><span class="number">1</span> <span
							class="text"><?php esc_html_e( 'Choose a template', 'learndash' ); ?></span></li>
					<li class="active"><span class="number">2</span> <span
							class="text"><?php esc_html_e( 'Fonts', 'learndash' ); ?></span></li>
					<li class="active"><span class="number">3</span> <span
							class="text"><?php esc_html_e( 'Colors', 'learndash' ); ?></span></li>
				</ol>
			</div>
			<div class="buttons">
				<a
					href="#"
					class="button next-button"
				><?php esc_html_e( 'Next', 'learndash' ); ?></a>
			</div>
		</div>
	</div>
</div>
