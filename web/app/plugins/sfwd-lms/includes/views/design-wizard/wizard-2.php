<?php
/**
 * Setup wizard template of page 2
 *
 * @package LearnDash_Design_Wizard
 *
 * @var array<string, mixed> $template_details
 * @var array<string, array{ label: string, families: array<string, string> }> $fonts
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
			<img src="<?php echo esc_url( \LEARNDASH_LMS_PLUGIN_URL . '/assets/images/learndash.svg' ); ?>" alt="LearnDash" />
		</div>
		<div class="header">
			<div class="title-wrapper">
				<h1 class="title">
					<?php esc_html_e( 'Choose a font', 'learndash' ); ?>
				</h1>
				<div class="reset">
					<button href="#" class="reset-font-button">
						<span class="dashicons dashicons-image-rotate"></span>
					</button>
				</div>
			</div>
			<p class="description">
				<?php
				esc_html_e(
					'Let\'s pick a starting font, 
                you can always change it later and pick from more options.',
					'learndash'
				);
				?>
			</p>
			<div class="fonts">
				<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
				<?php foreach ( $fonts as $font_id => $font ) : ?>
					<div 
						class="font" 
						title="<?php echo esc_attr( $font['label'] ); ?>" 
						data-id="<?php echo esc_attr( $font_id ); ?>"
					>
						<div class="letter">
							<span 
								class="heading-font" 
								style="
									font-family: '<?php echo esc_attr( $font['families']['heading'] ); ?>'; 
									font-weight: 700;"
							>
								A
							</span>
							<span 
								class="body-font" 
								style="
									font-family: '<?php echo esc_attr( $font['families']['body'] ); ?>'; 
									font-weight: 400;"
							>
								a
							</span>
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
				<img src="<?php echo esc_url( \LEARNDASH_LMS_PLUGIN_URL . '/assets/images/design-wizard/svg/exit.svg' ); ?>" />
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
				<img class="icon" src="<?php echo esc_url( \LEARNDASH_LMS_PLUGIN_URL . '/assets/images/design-wizard/svg/back.svg' ); ?>" /> 
				<span class="text"><?php esc_html_e( 'Back', 'learndash' ); ?></span>
			</div>
			<div class="steps">
				<ol class="list">
					<li 
						class="active"
					>
						<span class="number">1</span> 
						<span class="text">
							<?php esc_html_e( 'Choose a template', 'learndash' ); ?>
						</span>
					</li>
					<li 
						class="active"
					>
						<span class="number">2</span> 
						<span class="text">
							<?php esc_html_e( 'Fonts', 'learndash' ); ?>
						</span>
					</li>
					<li>
						<span class="number">3</span> 
						<span class="text">
							<?php esc_html_e( 'Colors', 'learndash' ); ?>
						</span>
					</li>
				</ol>
			</div>
			<div class="buttons">
				<a href="#" class="button next-button"><?php esc_html_e( 'Next', 'learndash' ); ?></a>
			</div>
		</div>
	</div>
</div>
