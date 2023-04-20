<?php
/**
 * Article link template
 *
 * @package LearnDash_Settings_Page_Setup
 *
 * @var array{
 *  'type': string,
 *  'youtube_id': string,
 *  'vimeo_id': string,
 *  'url': string,
 *  'helpscout_id': string,
 *  'action': string,
 *  'keyword': string,
 *  'articles': array<string>,
 *  'title': string
 * } $article
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( 'url' === $article['type'] ) {
	$learndash_setup_url = esc_url( $article['url'] );
} else {
	$learndash_setup_url = '#';
}
?>

<a
	href="<?php echo esc_attr( $learndash_setup_url ); ?>"
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
>
	<?php echo esc_html( $article['title'] ); ?>
</a>
