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
	<div class="welcome-to-learndash">
		<p>
			Just getting started with LearnDash? Find out more about what features we offer in the link to our documentation below:
		</p>
		<p>
			<br /><a target="_blank" href="https://www.learndash.com/support/docs/core/">LearnDash Core</a>
		</p>
		<p>
			<br />Other helpful information and tips can be found within this section as well.
		</p>
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
