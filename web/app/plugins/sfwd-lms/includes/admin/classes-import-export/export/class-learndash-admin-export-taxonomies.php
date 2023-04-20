<?php
/**
 * LearnDash Admin Export Taxonomies.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Export' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_Taxonomies' ) &&
	! class_exists( 'Learndash_Admin_Export_Taxonomies' )
) {
	/**
	 * Class LearnDash Admin Export Taxonomies.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Export_Taxonomies extends Learndash_Admin_Export {
		use Learndash_Admin_Import_Export_Taxonomies;

		/**
		 * Post Types.
		 *
		 * @since 4.3.0
		 *
		 * @var array
		 */
		protected $post_types;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param array                               $post_types   Post types.
		 * @param Learndash_Admin_Export_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			array $post_types,
			Learndash_Admin_Export_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->post_types = $post_types;

			parent::__construct( $file_handler, $logger );
		}

		/**
		 * Returns the list of taxonomies.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		public function get_data(): string {
			$taxonomy_names = array();

			foreach ( $this->post_types as $post_type ) {
				$taxonomy_names = array_merge(
					$taxonomy_names,
					get_object_taxonomies( $post_type )
				);
			}

			/**
			 * Filters the list of taxonomies to export.
			 *
			 * @since 4.3.0
			 *
			 * @param string[] $taxonomy_names Taxonomy names.
			 *
			 * @return string[] Taxonomy names.
			 */
			$taxonomy_names = apply_filters(
				'learndash_export_taxonomies',
				array_values(
					array_unique( $taxonomy_names )
				)
			);

			$result = array();

			foreach ( $taxonomy_names as $taxonomy_name ) {
				$data = array(
					'wp_taxonomy_terms' => get_terms(
						array(
							'taxonomy'   => $taxonomy_name,
							'orderby'    => 'parent',
							'order'      => 'ASC',
							'hide_empty' => false,
						)
					),
				);

				/**
				 * Filters the taxonomies object to export.
				 *
				 * @since 4.3.0
				 *
				 * @param array $data Taxonomies object.
				 *
				 * @return array Taxonomies object.
				 */
				$data = apply_filters( 'learndash_export_taxonomies_object', $data );

				$result[] = $data;
			}

			return wp_json_encode( $result );
		}
	}
}
