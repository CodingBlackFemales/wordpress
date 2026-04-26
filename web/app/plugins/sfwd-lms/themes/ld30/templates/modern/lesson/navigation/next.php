<?php
/**
 * View: Lesson Navigation Next.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Step     $progression The progression object.
 * @var Template $this        Current Instance of template engine rendering this template.
 * @var WP_User  $user        The user object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Progression\Step;
use LearnDash\Core\Template\Template;

$is_disabled = empty( $progression->get_next_url() );

$container_classes = [
	'ld-navigation__next',
	$is_disabled ? 'ld-tooltip ld-tooltip--modern' : '',
];

$link_classes = [
	'ld-navigation__next-link',
	$progression->is_just_completed() ? 'ld-navigation__next-link--just-completed' : '',
	$is_disabled ? 'ld-navigation__next-link--disabled' : '',
	// If the user is not logged in, we use a special style for the next link.
	! $user->exists() ? 'ld-navigation__next-link--no-user' : '',
];

?>
<div class="<?php echo esc_attr( implode( ' ', array_filter( $container_classes ) ) ); ?>">
	<a
		aria-disabled="<?php echo $is_disabled ? 'true' : 'false'; ?>"
		class="<?php echo esc_attr( implode( ' ', array_filter( $link_classes ) ) ); ?>"
		<?php if ( ! $is_disabled ) : ?>
			href="<?php echo esc_url( $progression->get_next_url() ); ?>"
		<?php endif; ?>
		id="ld-navigation__next-link"
		role="link"
	>
		<?php if ( $is_disabled ) : ?>
			<span class="ld-tooltip__text" role="tooltip">
				<?php echo esc_html__( 'Finish the required activity on this page to continue.', 'learndash' ); ?>
			</span>
		<?php endif; ?>

		<?php if ( $progression->is_just_completed() ) : ?>
			<span class="screen-reader-text">
				<?php
				echo esc_html(
					sprintf(
						// translators: placeholder: lesson label.
						__( '%s marked complete.', 'learndash' ),
						LearnDash_Custom_Label::get_label( LDLMS_Post_Types::LESSON )
					)
				);
				?>
			</span>
		<?php endif; ?>

		<span class="ld-navigation__label ld-navigation__label--next ld-navigation__label--short">
			<?php echo esc_html( $progression->get_next_short_label() ); ?>
		</span>

		<span class="ld-navigation__label ld-navigation__label--next ld-navigation__label--long">
			<?php echo esc_html( $progression->get_next_label() ); ?>
		</span>

		<?php
		$this->template(
			'components/icons/caret-' . esc_attr( is_rtl() ? 'left' : 'right' ),
			[ 'is_aria_hidden' => true ]
		);
		?>
	</a>
</div>
