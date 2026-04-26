<?php
/**
 * Custom documentation content template
 *
 * @package LearnDash_Settings_Page_Setup
 *
 * @var array<string, mixed>  $step
 * @var array<string, string> $overview_video
 * @var array<string, string> $overview_article
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use LearnDash_Settings_Page_Help as Help_Page;

?>

<h3><?php echo esc_html( $overview_video['title'] ); ?></h3>
<div class="overview-wrapper">
	<div
		class="video"
		data-vimeo_id="<?php echo esc_attr( $overview_video['youtube_id'] ); ?>"
		data-type="<?php echo esc_attr( $overview_video['type'] ); ?>"
	>
		<div class="icon">
			<span class="dashicons dashicons-arrow-right"></span>
		</div>
		<?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
		<img class="placeholder" src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . '/assets/images/overview.png' ); ?>">
	</div>
	<div class="overview">
		<div class="time"><?php esc_html_e( '2 Minutes', 'learndash' ); ?></div>
		<h2>
			<?php
				SFWD_LMS::get_view(
					'setup/components/article-link',
					array(
						'article' => $overview_article,
					),
					true
				);
				?>
		</h2>
		<h4><?php esc_html_e( 'Additional Resources', 'learndash' ); ?></h4>
		<ul>
            <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
			<?php foreach ( Help_Page::get_articles( 'additional_resources' ) as $article ) : ?>
				<li>
					<?php
						SFWD_LMS::get_view(
							'setup/components/article-link',
							array(
								'article' => $article,
							),
							true
						);
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
<div class="guides">
    <?php // phpcs:ignore Generic.Files.LineLength.TooLong, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound?>
	<?php foreach ( Help_Page::get_articles_categories( array( 'additional_resources' ) ) as $category_key => $category_title ) : ?>
		<div class="<?php echo esc_attr( $category_key ); ?>">
			<h4><?php echo esc_html( $category_title ); ?></h4>
			<ul>
                <?php // phpcs:ignore Generic.Files.LineLength.TooLong, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound?>
				<?php foreach ( Help_Page::get_articles( $category_key, array( 'additional_resources' ) ) as $article ) : ?>
					<li>
						<?php
							SFWD_LMS::get_view(
								'setup/components/article-link',
								array(
									'article' => $article,
								),
								true
							);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</div>
