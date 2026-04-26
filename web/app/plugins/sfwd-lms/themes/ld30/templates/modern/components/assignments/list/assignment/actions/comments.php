<?php
/**
 * View: Assignment Comments action.
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

if ( ! $assignment->comments_open() ) {
	return;
}

?>
<a
	href="<?php echo esc_url( $assignment->get_comments_link() ); ?>"
	class="ld-assignments__list-item-action ld-assignments__list-item-action--comments"
>
	<?php
	$this->template(
		'components/icons/comment-outlined',
		[
			'classes'        => [
				'ld-assignments__list-item-action-icon',
				'ld-assignments__list-item-action-icon--comments',
			],
			'is_aria_hidden' => true,
		]
	);
	?>

	<?php
	echo esc_html(
		sprintf(
			/* translators: placeholder: Comment count. */
			_n(
				'%d Comment',
				'%d Comments',
				$assignment->get_comments_number(),
				'learndash'
			),
			$assignment->get_comments_number()
		)
	);
	?>

	<span class="screen-reader-text">
		<?php
		echo esc_html(
			sprintf(
				/* translators: placeholder: Post type label, Post title. */
				_x( 'for %1$s "%2$s"', 'Assignment comments link description for screen readers. Example: 2 comments for Assignment "Assignment 1"', 'learndash' ),
				$assignment->get_post_type_label(),
				$assignment->get_uploaded_file_name()
			)
		);
		?>
	</span>
</a>
