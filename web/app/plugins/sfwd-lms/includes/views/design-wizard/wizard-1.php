<?php
/**
 * Setup wizard template of page 1
 *
 * @package LearnDash_Design_Wizard
 *
 * @var array<string, mixed> $templates
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
			<img src="<?php echo esc_url( \LEARNDASH_LMS_PLUGIN_URL . '/assets/images/learndash.svg' ); ?>"
				alt="LearnDash" >
		</div>
		<div class="header">
			<h1 class="title">
				<?php esc_html_e( 'Choose a template', 'learndash' ); ?>
			</h1>
			<p class="description">
				<?php
				esc_html_e(
					'Our setup wizard will help you 
                get the most out of your store.',
					'learndash'
				);
				?>
			</p>
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
		<div class="templates">
			<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound?>
			<?php foreach ( $templates as $template_details ) : ?>
				<?php
					SFWD_LMS::get_view(
						'design-wizard/template',
						compact( 'template_details', 'design_wizard' ),
						true
					);
				?>
			<?php endforeach; ?>
		</div>
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
					<li><span class="number">2</span> <span
							class="text"><?php esc_html_e( 'Fonts', 'learndash' ); ?></span></li>
					<li><span class="number">3</span> <span
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
	<div class="preview-wrapper">
		<div class="background"></div>
		<div class="preview">
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
					class="ld-site-preview"
					id="ld-site-preview"
					src=""
					frameborder="0"
				></iframe>
			</div>
		</div>
	</div>
</div>
