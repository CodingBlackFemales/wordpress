<?php
/**
 * LearnDash Admin Import Taxonomies.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Import' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_Taxonomies' ) &&
	! class_exists( 'Learndash_Admin_Import_Taxonomies' )
) {
	/**
	 * Class LearnDash Admin Import Taxonomies.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Taxonomies extends Learndash_Admin_Import {
		use Learndash_Admin_Import_Export_Taxonomies;

		/**
		 * Saves taxonomies' terms.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function import(): void {
			$taxonomies = $this->load_and_decode_file();

			if ( empty( $taxonomies ) ) {
				return;
			}

			$old_new_parent_taxonomy_id_hash = array();

			foreach ( $taxonomies as $taxonomy ) {
				$this->processed_items_count++;

				if ( empty( $taxonomy['wp_taxonomy_terms'] ) ) {
					continue;
				}

				foreach ( $taxonomy['wp_taxonomy_terms'] as $importing_term ) {
					if ( ! taxonomy_exists( $importing_term['taxonomy'] ) ) {
						continue;
					}

					$has_parent    = 0 !== intval( $importing_term['parent'] );
					$existing_term = term_exists( $importing_term['name'], $importing_term['taxonomy'] );

					if ( is_array( $existing_term ) ) {
						if ( ! $has_parent ) {
							$old_new_parent_taxonomy_id_hash[ $importing_term['term_id'] ] = $existing_term['term_id'];
						}

						$this->update_old_id_meta( intval( $existing_term['term_id'] ), $importing_term['term_id'] );

						continue;
					}

					$created_term = wp_insert_term(
						$importing_term['name'],
						$importing_term['taxonomy'],
						array(
							'description' => $importing_term['description'],
							'parent'      => $has_parent
								? $old_new_parent_taxonomy_id_hash[ $importing_term['parent'] ]
								: 0,
							'slug'        => $importing_term['slug'],
						)
					);

					if ( is_wp_error( $created_term ) ) {
						continue;
					}

					$this->imported_items_count++;

					if ( ! $has_parent ) {
						$old_new_parent_taxonomy_id_hash[ $importing_term['term_id'] ] = $created_term['term_id'];
					}

					$this->update_old_id_meta( $created_term['term_id'], $importing_term['term_id'] );
				}

				Learndash_Admin_Import::clear_wpdb_query_cache();
			}
		}

		/**
		 * Updates old term id meta. It is used later to assign post terms.
		 *
		 * @since 4.3.0
		 *
		 * @param int $new_term_id New term ID.
		 * @param int $old_term_id Old term ID.
		 *
		 * @return void
		 */
		protected function update_old_id_meta( int $new_term_id, int $old_term_id ): void {
			update_term_meta(
				$new_term_id,
				Learndash_Admin_Import::META_KEY_IMPORTED_FROM_TERM_ID,
				$old_term_id
			);
		}
	}
}
