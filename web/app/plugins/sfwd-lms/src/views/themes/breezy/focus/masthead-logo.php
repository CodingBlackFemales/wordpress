<?php
/**
 * View: Focus Mode Masthead Logo.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Course_Step $model Course step model.
 * @var WP_User     $user  User.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Interfaces\Course_Step;

// TODO: Maybe refactor this $header mapping.

$logo_id = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_logo' );

// TODO: I'm not sure I like this array, maybe change to an object.
/**
 * Filters Focus mode header logo arguments.
 *
 * @since 4.6.0
 *
 * @param array{logo_url: string, logo_alt: string, text: string, url: string} $args Logo args.
 *
 * @ignore
 */
$header = apply_filters(
	'learndash_focus_mode_logo_args',
	array(
		'logo_url' => $logo_id ? (string) wp_get_attachment_url( $logo_id ) : '',
		'logo_alt' => $logo_id ? strval( get_post_meta( $logo_id, '_wp_attachment_image_alt', true ) ) : '',
		'text'     => '',
		'url'      => get_home_url(),
	)
);
?>
<div class="ld-brand-logo">
	<?php if ( ! empty( $header['url'] ) ) : ?>
		<a href="<?php echo esc_url( $header['url'] ); ?>">
	<?php endif; ?>

	<?php if ( ! empty( $header['logo_url'] ) ) : ?>
		<img src="<?php echo esc_url( $header['logo_url'] ); ?>" alt=""<?php echo esc_attr( $header['logo_alt'] ); ?>"/>
	<?php else : ?>
		<?php echo esc_html( $header['text'] ); ?>
	<?php endif; ?>

	<?php if ( ! empty( $header['url'] ) ) : ?>
		</a>
	<?php endif; ?>
</div>
