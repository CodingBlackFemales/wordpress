<?php
/**
 * Registration.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var string             $active_template_key            The active template key.
 * @var WP_User            $current_user                   The current user.
 * @var string             $form_width                     The form width.
 * @var bool               $has_registration_form_override Whether the registration form override is enabled.
 * @var bool               $is_registration_enabled        Whether the user can register.
 * @var bool               $is_user_logged_in              Whether the user is logged in.
 * @var bool               $is_registered                  Whether the user is registered.
 * @var int                $register_id                    The register ID.
 * @var int                $registration_page_id           The registration page ID.
 * @var string             $wrapper_class                  The wrapper class.
 * @var Template           $this                           The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( is_numeric( $form_width ) ) {
	$form_width .= 'px';
}

$form_width = empty( $form_width ) ? '' : ' margin-left: auto; margin-right: auto; max-width: ' . esc_attr( $form_width ) . ';';

$inner_wrapper_classes = [
	'ld-registration__wrapper',
];

if ( $is_user_logged_in ) {
	$inner_wrapper_classes[] = 'ld-registration__wrapper--logged-in';
}

if ( $is_registered ) {
	$inner_wrapper_classes[] = 'ld-registration__wrapper--is-registered';
}

if ( $register_id > 0 ) {
	$inner_wrapper_classes[] = 'ld-registration__wrapper--has-order';
}

?>
<div class="<?php echo esc_attr( $wrapper_class ); ?> ld-registration__outer-wrapper">
	<div class="ld-registration__size-wrapper" style="<?php echo esc_attr( $form_width ); ?>" >
		<div
			class="<?php echo esc_attr( implode( ' ', $inner_wrapper_classes ) ); ?>"
			data-js="learndash-view"
			data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
			data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
		>
			<?php if ( $is_user_logged_in && $is_registered ) : ?>
				<?php learndash_output_registration_success_alert(); ?>
			<?php endif; ?>

			<?php if ( $is_user_logged_in && ! $is_registered && ! $register_id ) : ?>
				<div class="ld-registration__form">
					<?php $this->template( 'modules/registration/login/already-logged-in' ); ?>
				</div>
			<?php elseif ( ! $is_user_logged_in ) : ?>
				<div class="ld-registration__form">

					<?php $this->template( 'modules/registration/register/heading' ); ?>
					<?php learndash_login_failed_alert(); ?>
					<?php $this->template( 'modules/registration/login/form' ); ?>

					<?php if ( $is_registration_enabled ) : ?>
						<?php
						if ( $has_registration_form_override ) {
							/**
							 * Allow for replacement of the default LearnDash Registration form
							 *
							 * @since 3.6.0
							 */
							do_action( 'learndash_registration_form_override' );
						} else {
							/**
							 * Fires before the registration form.
							 *
							 * @since 3.6.0
							 */
							do_action( 'learndash_registration_form_before' );

							$this->template( 'modules/registration/register/form' );

							/**
							 * Fires after the registration form.
							 *
							 * @since 3.6.0
							 */
							do_action( 'learndash_registration_form_after' );
						}
						?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $register_id && $register_id > 0 ) : ?>
				<?php $this->template( 'modules/registration/order/details' ); ?>
				<?php $this->template( 'modules/registration/return' ); ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
