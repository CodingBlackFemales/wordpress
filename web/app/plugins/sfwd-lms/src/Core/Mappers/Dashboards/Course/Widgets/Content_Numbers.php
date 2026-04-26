<?php
/**
 * Content in numbers widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Dashboards\Course\Widgets;

use LDLMS_Post_Types;
use LearnDash\Core\Template\Dashboards\Widgets\Interfaces;
use LearnDash\Core\Template\Dashboards\Widgets\Traits\Supports_Post;
use LearnDash\Core\Template\Dashboards\Widgets\Types\DTO\Values_Item;
use LearnDash\Core\Template\Dashboards\Widgets\Types\Values;
use LearnDash_Custom_Label;
use Learndash_DTO_Validation_Exception;

/**
 * Content in numbers widget.
 *
 * @since 4.9.0
 */
class Content_Numbers extends Values implements Interfaces\Requires_Post {
	use Supports_Post;

	/**
	 * Loads required data.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	protected function load_data(): void {
		$items_data = [
			[
				'label' => LearnDash_Custom_Label::get_label( 'lessons' ),
				'value' => $this->get_course_children_count_by_type( LDLMS_Post_Types::LESSON ),
			],
			[
				'label' => LearnDash_Custom_Label::get_label( 'topics' ),
				'value' => $this->get_course_children_count_by_type( LDLMS_Post_Types::TOPIC ),
			],
			[
				'label' => LearnDash_Custom_Label::get_label( 'quizzes' ),
				'value' => $this->get_course_children_count_by_type( LDLMS_Post_Types::QUIZ ),
			],
		];

		$items = [];

		foreach ( $items_data as $item ) {
			try {
				$items[] = new Values_Item( $item );
			} catch ( Learndash_DTO_Validation_Exception $e ) {
				continue;
			}
		}

		$this->set_items( $items );
	}

	/**
	 * Returns a number of children of a course by a post type.
	 *
	 * @since 4.9.0
	 *
	 * @param string $post_type_key Post type key.
	 *
	 * @return int
	 */
	private function get_course_children_count_by_type( string $post_type_key ): int {
		/**
		 * It's not efficient as it loads objects, but it's the safest way to get the number of children now.
		 *
		 * The logic is complex (shared steps, etc.), and we can't rely on the database normally.
		 */
		$children = learndash_course_get_children_of_step(
			$this->get_post()->ID,
			0,
			LDLMS_Post_Types::get_post_type_slug( $post_type_key ),
			'ids',
			true
		);

		return count( $children );
	}
}
