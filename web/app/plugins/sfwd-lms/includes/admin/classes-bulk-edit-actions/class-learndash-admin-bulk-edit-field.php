<?php
/**
 * LearnDash Admin field class.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Bulk_Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Bulk_Edit_Field' ) ) {
	/**
	 * Learndash admin field abstract class.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Bulk_Edit_Field {
		const MINIMUM_REQUIRED_ATTRIBUTES = array( 'id', 'label', 'name', 'display_callback' );

		/**
		 * Field attributes.
		 *
		 * @since 4.2.0
		 *
		 * @var array
		 */
		protected $attributes;

		/**
		 * Construct.
		 *
		 * @since 4.2.0
		 *
		 * @param array $attributes Field attributes.
		 */
		public function __construct( array $attributes ) {
			// check required attributes.
			if ( count( array_intersect_key( $attributes, array_flip( self::MINIMUM_REQUIRED_ATTRIBUTES ) ) ) !== count( self::MINIMUM_REQUIRED_ATTRIBUTES ) ) {
				wp_die(
					sprintf(
						// translators: placeholder: required fields.
						esc_html__( 'Missing required attributes for field: %s', 'learndash' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						implode( ', ', self::MINIMUM_REQUIRED_ATTRIBUTES ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					)
				);
			}

			$this->attributes = array_replace(
				$attributes,
				array(
					'use_raw_name' => true,
				)
			);
			$this->add_raw_name_for_child_fields_recursively( $this->attributes );
		}

		/**
		 * Look for child fields to add the use_raw_name key.
		 *
		 * @since 4.2.0
		 *
		 * @param array $attributes Field attributes array by reference.
		 *
		 * @return void
		 */
		private function add_raw_name_for_child_fields_recursively( array &$attributes ) {
			foreach ( $attributes as $key => &$value ) {
				if ( 'inline_fields' === $key && is_array( $value ) ) {
					$this->add_raw_name_for_inline_fields_recursively( $value );
				} elseif ( is_array( $value ) ) {
					$this->add_raw_name_for_child_fields_recursively( $value );
				}
			}
		}

		/**
		 * Adds the use_raw_name key to child fields in the args array.
		 *
		 * @since 4.2.0
		 *
		 * @param array $attributes Field attributes array by reference.
		 *
		 * @return void
		 */
		private function add_raw_name_for_inline_fields_recursively( array &$attributes ): void {
			foreach ( $attributes as $key => &$value ) {
				if ( 'args' === $key && is_array( $value ) ) {
					$value['use_raw_name'] = true;
				}
				if ( is_array( $value ) ) {
					$this->add_raw_name_for_inline_fields_recursively( $value );
				}
			}
		}

		/**
		 * Returns the field id.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_id(): string {
			return $this->attributes['id'];
		}

		/**
		 * Returns the field label.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_label(): string {
			return $this->attributes['label'];
		}

		/**
		 * Returns the field name.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_name(): string {
			return $this->attributes['name'];
		}

		/**
		 * Echoes the input HTML.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public function display(): void {
			call_user_func( $this->attributes['display_callback'], $this->attributes );
		}
	}
}
