<?php
/**
 * Article link template.
 *
 * @since 4.4.0.1
 * @version 4.22.1
 *
 * @var array{action: string, articles: array<string>, helpscout_id: string, keyword: string, target: string, title: string, type: string, url: string, vimeo_id: string, youtube_id: string } $article Article.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( 'url' === $article['type'] ) {
	$url = esc_url( $article['url'] );
} else {
	$url = '#';
}
?>
<a
	href="<?php echo esc_url( $url ); ?>"
	data-type="<?php echo esc_attr( $article['type'] ); ?>"
	<?php if ( 'youtube_video' === $article['type'] ) : ?>
		data-youtube_id="<?php echo esc_attr( $article['youtube_id'] ); ?>"
	<?php elseif ( 'vimeo_video' === $article['type'] ) : ?>
		data-vimeo_id="<?php echo esc_attr( $article['vimeo_id'] ); ?>"
	<?php elseif ( in_array( $article['type'], array( 'article', 'overview_article' ), true ) ) : ?>
		data-helpscout_id="<?php echo esc_attr( $article['helpscout_id'] ); ?>"
	<?php elseif ( 'helpscout_action' === $article['type'] ) : ?>
		data-action="<?php echo esc_attr( $article['action'] ); ?>"
		<?php if ( 'open_doc' === $article['action'] ) : ?>
			data-keyword="<?php echo esc_attr( $article['keyword'] ); ?>"
		<?php elseif ( 'suggest_articles' === $article['action'] ) : ?>
			data-articles="<?php echo esc_attr( implode( ',', $article['articles'] ) ); ?>"
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( ! empty( $article['target'] ) ) : ?>
		target="<?php echo esc_attr( $article['target'] ); ?>"
	<?php endif; ?>
>
	<?php echo esc_html( $article['title'] ); ?>

	<?php if ( ! empty( $article['target'] ) && '_blank' === $article['target'] ) : ?>
		<span class="dashicons dashicons-external"></span>
	<?php endif; ?>
</a>
