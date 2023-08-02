<?php
/**
 * View: Focus Mode Comments.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var int         $approved_comments_number Number of approved comments.
 * @var Course_Step $model                    Course step model.
 * @var WP_User     $user                     User.
 * @var Template    $this                     Template instance.
 *
 * @package LearnDash\Core
 *
 * cSpell:ignore replytocom
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Interfaces\Course_Step;
use LearnDash\Core\Template\Template;

if ( post_password_required() ) {
	return;
}

// TODO: Split into smaller files.

// This filter is documented in themes/ld30/templates/focus/index.php.
if ( ! apply_filters( 'learndash_focus_mode_show_existing_comments', false ) && ! comments_open() ) {
	return;
}
?>
<div class="ld-focus-comments">
	<?php if ( $approved_comments_number > 0 && ! isset( $_GET['replytocom'] ) ) : ?>
		<div class="ld-focus-comments__heading">
			<div class="ld-focus-comments__header">
				<?php
				printf(
					esc_html(
						// translators: single approved comment, multiple approved comments.
						_nx( '%s Comment', '%s Comments', $approved_comments_number, 'comments', 'learndash' )
					),
					esc_html( number_format_i18n( $approved_comments_number ) )
				);
				?>
			</div>
			<div class="ld-focus-comments__heading-actions">
				<div
					class="ld-expand-button ld-button-alternate ld-expanded"
					id="ld-expand-button-comments"
					data-ld-expands="ld-comments"
					data-ld-expand-text="<?php esc_html_e( 'Expand Comments', 'learndash' ); ?>"
					data-ld-collapse-text="<?php esc_html_e( 'Collapse Comments', 'learndash' ); ?>"
				>
					<span class="ld-text">
						<?php esc_html_e( 'Collapse Comments', 'learndash' ); ?>
					</span>

					<?php $this->template( 'components/icons/arrow-down' ); ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="ld-focus-comments__comments ld-expanded" id="ld-comments" data-ld-expand-id="ld-comments">
		<div class="ld-focus-comments__comments-items" id="ld-comments-wrapper">
			<?php
			// If comments are open, or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) {
				// Add filter to direct comments to our template.
				add_filter(
					'comments_template',
					function( $theme_template = '' ) {
						$theme_template_alt = SFWD_LMS::get_template(
							'focus/comments_list.php',
							null,
							null,
							true
						);

						if ( ! empty( $theme_template_alt ) ) {
							$theme_template = $theme_template_alt;
						}

						return $theme_template;
					},
					999,
					1
				);

				comments_template();

				if ( ! isset( $_GET['replytocom'] ) ) {
					the_comments_navigation();
				}
			}
			?>
		</div>
	</div>

	<?php if ( 0 === $approved_comments_number ) : ?>
		<div class="ld-expand-button ld-button-alternate" id="ld-comments-post-button">
			<?php $this->template( 'components/icons/arrow-down' ); ?>

			<span class="ld-text">
				<?php esc_html_e( 'Post a comment', 'learndash' ); ?>
			</span>
		</div>
	<?php endif; ?>

	<div
		class="ld-focus-comments__form-container<?php echo esc_attr( 0 === $approved_comments_number ? ' ld-collapsed' : '' ); ?>"
		id="ld-comments-form"
	>
		<?php
		comment_form(
			// This filter is documented in themes/ld30/templates/focus/comments.php.
			apply_filters(
				'learndash_focus_mode_comment_form_args',
				array(
					'title_reply' => esc_html__( 'Leave a Comment', 'learndash' ),
				)
			)
		);
		?>
	</div>
</div>
