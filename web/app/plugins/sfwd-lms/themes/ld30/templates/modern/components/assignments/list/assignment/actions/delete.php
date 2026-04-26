<?php
/**
 * View: Assignment Delete action.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Assignment $assignment The assignment.
 * @var WP_User    $user       The user.
 * @var Template   $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Assignment;
use LearnDash\Core\Template\Template;

if ( ! $assignment->can_be_deleted( $user ) ) {
	return;
}

?>
<a
	href="<?php echo esc_url( $assignment->get_delete_url() ); ?>"
	class="ld-assignments__list-item-action ld-assignments__list-item-action--delete"
>
	<?php
	$this->template(
		'components/icons/trash-can',
		[
			'classes'        => [
				'ld-assignments__list-item-action-icon',
				'ld-assignments__list-item-action-icon--delete',
			],
			'is_aria_hidden' => true,
		]
	);
	?>

	<?php echo esc_html_x( 'Delete', 'Assignment delete action', 'learndash' ); ?>

	<span class="screen-reader-text">
		<?php
		echo esc_html(
			sprintf(
				/* translators: placeholder: Post type label, Post title. */
				_x( '%1$s "%2$s"', 'Assignment delete action description for screen readers. Example: Delete Assignment "Assignment 1"', 'learndash' ),
				$assignment->get_post_type_label(),
				$assignment->get_uploaded_file_name()
			)
		);
		?>
	</span>
</a>
