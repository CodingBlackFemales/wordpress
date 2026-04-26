<?php
/**
 * Question Admin Listing screen class file.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Quiz\Question\Admin;

use LDLMS_Post_Types;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Cast;
use WP_Post;
use WP_Posts_List_Table;
use WpProQuiz_Model_Question;

/**
 * Question admin listing class.
 *
 * @since 4.21.4
 */
class Listing {
	/**
	 * Removes the default 'title' column.
	 *
	 * @param array<string, string> $columns The columns.
	 *
	 * @since 4.21.4
	 *
	 * @return array<string, string>
	 */
	public function remove_title_column( $columns ) {
		unset( $columns['title'] );

		return $columns;
	}

	/**
	 * Handles the title custom column output.
	 *
	 * @since 4.21.4
	 *
	 * @param int $post_id The current post ID.
	 *
	 * @return void
	 */
	public function show_column_title_custom( $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		// Add the title content.

		$wp_list_table = _get_list_table( WP_Posts_List_Table::class );
		$wp_list_table->column_title( $post );

		// Add accessibility tooltips.

		$this->add_accessibility_tooltips( $post );
	}

	/**
	 * Sets the primary/default column for the list table.
	 * This is important to show the row actions properly.
	 *
	 * @since 4.21.4
	 *
	 * @param string $default_column Column name default for the list table.
	 * @param string $context        Screen ID for the list table.
	 *
	 * @return string
	 */
	public function set_primary_column( $default_column, $context ) {
		if ( $context === 'edit-' . learndash_get_post_type_slug( LDLMS_Post_Types::QUESTION ) ) {
			return 'ld_question_title_custom';
		}

		return $default_column;
	}

	/**
	 * Adds the accessibility tooltips.
	 *
	 * @since 4.21.4
	 *
	 * @param WP_Post $post The current post object.
	 *
	 * @return void
	 */
	private function add_accessibility_tooltips( $post ): void {
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$question = fetchQuestionModel(
			Cast::to_int(
				get_post_meta(
					Cast::to_int( $post->ID ),
					'question_pro_id',
					true
				)
			)
		);

		if (
			! $question instanceof WpProQuiz_Model_Question
			|| $question->getAnswerType() !== 'matrix_sort_answer'
		) {
			return;
		}

		echo '<span class="tooltip">';

		Template::show_template(
			'components/icons/warning',
			[
				'classes' => [ 'ld-accessibility-warning' ],
			]
		);

		echo '<span class="tooltiptext">' . esc_html( Edit::get_matrix_sort_answer_accessibility_warning( false ) ) . '</span>';

		echo '</span>';
	}


	/**
	 * Adds the title custom column to the sortable columns.
	 *
	 * @param array<string, string> $columns The columns.
	 *
	 * @since 4.21.4
	 *
	 * @return array<string, string>
	 */
	public function add_sortable_column( $columns ) {
		$columns['ld_question_title_custom'] = 'title';

		return $columns;
	}
}
