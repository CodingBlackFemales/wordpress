<?php
/**
 * Reporting Widget Download Button.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;
?>

<p style="display:none;" class="download-button-wrap"><button class="button button-primary reporting-download" data-template="activity-courses" data-nonce="<?php echo wp_create_nonce( 'learndash-data-reports-user-courses-' . get_current_user_id() ); ?>" data-slug="user-courses" type="button" title="
																																										<?php
																																										printf(
																																										// translators: Export Course Data
																																											esc_html_x( 'Export %s Data', 'Export Course Data', 'learndash' ),
																																											LearnDash_Custom_Label::get_label( 'course' )
																																										);
																																										?>
"><span class="dashicons dashicons-download"></span><?php esc_html_e( 'Download', 'learndash' ); ?><span class="status"></span></button></p>
