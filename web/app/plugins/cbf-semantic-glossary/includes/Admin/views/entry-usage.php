<?php
/**
 * "Used in N posts" panel.
 *
 * @package CodingBlackFemales/SemanticGlossary
 *
 * @var CodingBlackFemales\SemanticGlossary\Entry\Entry $entry Entry.
 * @var array<int, object>                              $uses  One row per post, first-used order.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( $uses === array() ) : ?>
	<p><?php esc_html_e( 'No post references this term yet.', 'cbf-semantic-glossary' ); ?></p>
<?php else : ?>
	<ul class="glossary-usage">
		<?php foreach ( $uses as $use ) : ?>
			<li class="glossary-usage__item">
				<a href="<?php echo esc_url( (string) get_edit_post_link( (int) $use->post_id ) ); ?>"><?php echo esc_html( get_the_title( (int) $use->post_id ) ); ?></a>
				<span class="glossary-usage__form">
					<?php
					if ( (int) $use->is_inline === 1 ) {
						/* translators: %s: the text marked in the post */
						printf( esc_html__( 'as “%s”', 'cbf-semantic-glossary' ), esc_html( $use->ref_text ) );
					} else {
						esc_html_e( 'glossary only', 'cbf-semantic-glossary' );
					}
					?>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="description"><?php esc_html_e( 'Editing this entry updates every post above.', 'cbf-semantic-glossary' ); ?></p>
<?php endif; ?>
