<?php
/**
 * View: Assignment Download action.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Assignment $assignment The assignment.
 * @var Template   $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Assignment;
use LearnDash\Core\Template\Template;

$download_url = $assignment->get_download_url();

if ( empty( $download_url ) ) {
	return;
}

?>
<a
	href="<?php echo esc_url( $download_url ); ?>"
	class="ld-assignments__list-item-action ld-assignments__list-item-action--download"
>
	<?php
	$this->template(
		'components/icons/download-mini',
		[
			'classes'        => [
				'ld-assignments__list-item-action-icon',
				'ld-assignments__list-item-action-icon--download',
			],
			'is_aria_hidden' => true,
		]
	);
	?>

	<?php echo esc_html_x( 'Download', 'Assignment download action', 'learndash' ); ?>

	<span class="screen-reader-text">
		<?php
		echo esc_html(
			sprintf(
				/* translators: placeholder: Post type label, Post title. */
				_x( '%1$s "%2$s"', 'Assignment download action description for screen readers. Example: Download Assignment "Assignment 1"', 'learndash' ),
				$assignment->get_post_type_label(),
				$assignment->get_uploaded_file_name()
			)
		);
		?>
	</span>
</a>
