<?php
/**
 * DTO validation exception.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_DTO_Validation_Exception' ) && class_exists( 'Exception' ) ) {
	/**
	 * DTO validation exception class.
	 *
	 * @since 4.5.0
	 */
	class Learndash_DTO_Validation_Exception extends Exception {
		/**
		 * Constructor.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_DTO                                            $data_transfer_object Data transfer object.
		 * @param array<string,Learndash_DTO_Property_Validation_Result[]> $validation_errors    Validation errors.
		 *
		 * @return void
		 */
		public function __construct( Learndash_DTO $data_transfer_object, array $validation_errors ) {
			$class_name = get_class( $data_transfer_object );

			$messages = array();

			foreach ( $validation_errors as $property_name => $errors ) {
				foreach ( $errors as $error ) {
					$messages[] = "\t - `{$class_name}->{$property_name}`: {$error->get_message()}";
				}
			}

			parent::__construct(
				'Validation errors: ' . PHP_EOL . implode( PHP_EOL, $messages )
			);
		}
	}
}
