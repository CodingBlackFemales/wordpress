<?php
/**
 * LearnDash Step Walker class.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Template\Steps;

use LearnDash\Core\Template\Template;

// TODO: Write tests for it.

// TODO: Start using the $depth modificator.

/**
 * LearnDash Steps walker class.
 *
 * @since 4.6.0
 */
class Walker extends \Walker {
	/**
	 * ID field names.
	 *
	 * @since 4.6.0
	 *
	 * @var array{
	 *     parent: string,
	 *     id: string
	 * }
	 */
	public $db_fields = [
		'parent' => 'parent_id',
		'id'     => 'id',
	];

	/**
	 * Depth modificator. Used to calculate the depth of the current step.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	protected $depth_modificator = 0;

	/**
	 * Sets the depth modificator.
	 *
	 * @since 4.6.0
	 *
	 * @param int $depth_modificator The depth modificator.
	 *
	 * @return self
	 */
	public function set_depth_modificator( int $depth_modificator ): self {
		$this->depth_modificator = $depth_modificator;

		return $this;
	}

	/**
	 * Starts the list before the elements are added.
	 *
	 * The $args parameter holds additional values that may be used with the child
	 * class methods. This method is called at the start of the output list.
	 *
	 * @since 4.6.0
	 *
	 * @param string       $output Used to append additional content (passed by reference).
	 * @param int          $depth  Depth of the item.
	 * @param array<mixed> $args   An array of additional arguments.
	 *
	 * @return void
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ): void {
		$output .= Template::get_template( 'components/steps/list/start', $args );
		$output .= Template::get_template( 'components/steps/step/link', $args );
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * The $args parameter holds additional values that may be used with the child
	 * class methods. This method finishes the list at the end of output of the elements.
	 *
	 * @since 4.6.0
	 *
	 * @param string       $output Used to append additional content (passed by reference).
	 * @param int          $depth  Depth of the item.
	 * @param array<mixed> $args   An array of additional arguments.
	 *
	 * @return void
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ): void {
		$output .= Template::get_template( 'components/steps/step/loader', $args );
		$output .= Template::get_template( 'components/steps/list/end', $args );
	}

	/**
	 * Starts the element output.
	 *
	 * The $args parameter holds additional values that may be used with the child
	 * class methods. Also includes the element output.
	 *
	 * @since 4.6.0
	 *
	 * @param string       $output            Used to append additional content (passed by reference).
	 * @param Step         $data_object       The step object.
	 * @param int          $depth             Depth of the item.
	 * @param array<mixed> $args              An array of additional arguments.
	 * @param int          $current_object_id Optional. ID of the current item. Default 0.
	 *
	 * @return void
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = [], $current_object_id = 0 ): void {
		$output .= Template::get_template( 'components/steps/list/item/start', $args );
		$output .= Template::get_template(
			'components/steps/' . ( $data_object->is_section() ? 'section' : 'step' ),
			$args
		);
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * The $args parameter holds additional values that may be used with the child class methods.
	 *
	 * @since 4.6.0
	 *
	 * @param string       $output      Used to append additional content (passed by reference).
	 * @param Step         $data_object The data object.
	 * @param int          $depth       Depth of the item.
	 * @param array<mixed> $args        An array of additional arguments.
	 *
	 * @return void
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = array() ): void {
		$output .= Template::get_template( 'components/steps/list/item/end', $args );
	}

	/**
	 * Traverses elements to create list from elements.
	 *
	 * Display one element if the element doesn't have any children otherwise,
	 * display the element and its children. Will only traverse up to the max
	 * depth and no ignore elements under that depth. It is possible to set the
	 * max depth to include all depths, see walk() method.
	 *
	 * This method should not be called directly, use the walk() method instead.
	 *
	 * @since 4.6.0
	 *
	 * @param Step|null                  $element           Data object.
	 * @param array<string,array<mixed>> $children_elements List of elements to continue traversing (passed by reference).
	 * @param int                        $max_depth         Max depth to traverse.
	 * @param int                        $depth             Depth of current element.
	 * @param array<mixed>               $args              An array of arguments.
	 * @param string                     $output            Used to append additional content (passed by reference).
	 *
	 * @return void
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ): void {
		if ( ! $element ) {
			return;
		}

		$depth = $depth + $this->depth_modificator;

		$id_field = $this->db_fields['id'];
		$id       = $element->$id_field;

		// Display this element.
		$this->has_children = ! empty( $children_elements[ $id ] );
		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args[0]['has_children'] = $this->has_children; // Back-compat.
		}

		$args[0]['depth']          = $depth;
		$args[0]['children_count'] = isset( $children_elements[ $id ] ) ? count( $children_elements[ $id ] ) : 0;
		$args[0]['step']           = $element;

		$this->start_el( $output, $element, $depth, ...array_values( $args ) );

		// Descend only when the depth is right and there are children for this element.
		if ( ( 0 === $max_depth || $max_depth > $depth + 1 ) && isset( $children_elements[ $id ] ) ) {
			foreach ( $children_elements[ $id ] as $child ) {
				if ( ! isset( $new_level ) ) {
					$new_level = true;
					// Start the child delimiter.
					$this->start_lvl( $output, $depth, ...array_values( $args ) );
				}
				$this->display_element( $child, $children_elements, $max_depth, $depth + 1, $args, $output );
			}

			unset( $children_elements[ $id ] );
		}

		if ( isset( $new_level ) ) {
			// End the child delimiter.
			$this->end_lvl( $output, $depth, ...array_values( $args ) );
		}

		// End this element.
		$this->end_el( $output, $element, $depth, ...array_values( $args ) );
	}

	/**
	 * Displays array of elements hierarchically.
	 *
	 * Does not assume any existing order of elements.
	 *
	 * $max_depth = -1 means flatly display every element.
	 * $max_depth = 0 means display all levels.
	 * $max_depth > 0 specifies the number of display levels.
	 *
	 * @since 4.6.0
	 *
	 * @param Step[] $elements  An array of elements.
	 * @param int    $max_depth The maximum hierarchical depth.
	 * @param mixed  ...$args   Optional additional arguments.
	 *
	 * @return string The hierarchical item output.
	 */
	public function walk( $elements, $max_depth, ...$args ): string {
		$output = '';

		// Invalid parameter or nothing to walk.
		if ( $max_depth < -1 || empty( $elements ) ) {
			return $output;
		}

		$parent_field = $this->db_fields['parent'];

		// Flat display.
		if ( -1 == $max_depth ) {
			$empty_array = array();
			foreach ( $elements as $e ) {
				$this->display_element( $e, $empty_array, 1, 0, $args, $output );
			}
			return $output;
		}

		/*
		 * Need to display in hierarchical order.
		 * Separate elements into two buckets: top level and children elements.
		 * Children_elements is two dimensional array. Example:
		 * Children_elements[10][] contains all sub-elements whose parent is 10.
		 */
		$top_level_elements = array();
		$children_elements  = array();
		foreach ( $elements as $e ) {
			if ( empty( $e->$parent_field ) || ! isset( $elements[ $e->get_parent_id() ] ) ) {
				$top_level_elements[] = $e;
			} else {
				$children_elements[ $e->$parent_field ][] = $e;
			}
		}

		/*
		 * When none of the elements is top level.
		 * Assume the first one must be root of the sub elements.
		 */
		if ( empty( $top_level_elements ) ) {

			$first = array_slice( $elements, 0, 1 );
			$root  = $first[0];

			$top_level_elements = array();
			$children_elements  = array();
			foreach ( $elements as $e ) {
				if ( $root->$parent_field == $e->$parent_field ) {
					$top_level_elements[] = $e;
				} else {
					$children_elements[ $e->$parent_field ][] = $e;
				}
			}
		}

		foreach ( $top_level_elements as $e ) {
			$this->display_element( $e, $children_elements, $max_depth, 0, $args, $output );
		}

		/*
		 * If we are displaying all levels, and remaining children_elements is not empty,
		 * then we got orphans, which should be displayed regardless.
		 */
		if ( ( 0 == $max_depth ) && count( $children_elements ) > 0 ) {
			$empty_array = array();
			foreach ( $children_elements as $orphans ) {
				foreach ( $orphans as $op ) {
					$this->display_element( $op, $empty_array, 1, 0, $args, $output );
				}
			}
		}

		return $output;
	}
}
