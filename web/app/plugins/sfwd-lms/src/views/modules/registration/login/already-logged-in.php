<?php
/**
 * Registration - display a message if user is already logged in.
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var WP_User $current_user         Current user object.
 * @var int     $registration_page_id Page ID of the registration page.
 *
 * @package LearnDash\Core
 */

?>
<h2 class="ld-registration__heading ld-registration-login__heading">
	<?php if ( $current_user->ID === 0 ) : ?>
		<?php esc_html_e( 'You\'re Not Logged In', 'learndash' ); ?>
	<?php else : ?>
		<?php esc_html_e( 'You\'re Already Logged In', 'learndash' ); ?>
	<?php endif; ?>
</h2>
<p class="ld-registration__p">
	<?php
	if ( $current_user->ID === 0 ) {
		printf(
			// translators: placeholders: Home page link.
			esc_html_x( 'Looking for something? %2$s', 'Message displayed when the user finds themselves on the already logged in page but is not logged in.', 'learndash' ),
			esc_html( $current_user->user_login ),
			'<a href="' . esc_url( get_home_url() ) . '">' . esc_html__( 'Go to the home page', 'learndash' ) . '</a>'
		);
	} else {
		printf(
			// translators: placeholders: Current Logged In Username, WP Logout Link.
			esc_html_x( 'You\'re currently logged in as %1$s. Want to sign in as a different user? %2$s', 'placeholder: Current Logged In Username, WP Logout Link.', 'learndash' ),
			esc_html( $current_user->user_login ),
			'<a href="' . esc_url( wp_logout_url( (string) get_permalink( $registration_page_id ) ) ) . '">' . esc_html__( 'Log Out', 'learndash' ) . '</a>'
		);
	}
	?>
</p>
