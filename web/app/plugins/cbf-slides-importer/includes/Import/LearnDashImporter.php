<?php
/**
 * Wraps the learndash-bulk-lessons-or-topics plugin's import entry point.
 *
 * Calls run_import_cli() from ELDBC_Plugin and reuses ELDBC_Media::rewrite_paths()
 * for image URL rewriting (SHA-256 dedup).
 *
 * @class   Import\LearnDashImporter
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Import;

use CodingBlackFemales\SlidesImporter\Utils;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LearnDashImporter class.
 *
 * Responsibilities:
 * 1. Assemble the CSV row data from BlockRenderer output.
 * 2. Call run_import_cli() on the learndash-bulk plugin instance.
 * 3. Rewrite image paths using ELDBC_Media::rewrite_paths() after post creation.
 * 4. Return the list of created WP post IDs.
 */
final class LearnDashImporter {

	/**
	 * Import a classified and rendered deck into LearnDash.
	 *
	 * @param  array $classified_deck ParsedDeck with slide_type and rendered HTML.
	 * @param  array $summary         Result summary from JobRunner (contains config, mode, img_dir, etc.).
	 * @return array|WP_Error { created_post_ids: int[] } on success, WP_Error on failure.
	 */
	public function import( array $classified_deck, array $summary ): array|WP_Error {
		$config     = $summary['config'] ?? array();
		$mode       = $config['mode'] ?? 'lesson-only';
		$course_id  = ! empty( $config['course_id'] ) ? (int) $config['course_id'] : 0;
		$overwrite  = ! empty( $config['overwrite'] );
		$img_dir    = $summary['img_dir'] ?? '';
		$post_title = ! empty( $config['post_title'] )
			? $config['post_title']
			: ( $summary['deck_name'] ?? 'Imported Lesson' );

		// Retrieve the learndash-bulk plugin instance.
		$bulk_plugin = $this->get_bulk_plugin();
		if ( is_wp_error( $bulk_plugin ) ) {
			return $bulk_plugin;
		}

		$rendered = \CodingBlackFemales\SlidesImporter\Pptx\BlockRenderer::render(
			$classified_deck,
			$mode
		);

		$errors = array();

		if ( $mode === 'lesson-with-topics' ) {
			$result = $this->import_lesson_with_topics( $bulk_plugin, $rendered, $course_id, $img_dir, $post_title, $overwrite, $errors );
		} else {
			$result = $this->import_lesson_only( $bulk_plugin, $rendered, $course_id, $img_dir, $post_title, $overwrite, $errors );
		}

		if ( ! empty( $errors ) ) {
			Utils::log( 'Import errors.', array( 'count' => count( $errors ) ) );
		}

		return is_wp_error( $result )
			? $result
			: array(
				'created_post_ids' => $result,
				'errors'           => $errors,
			);
	}


	// ── Import modes ──────────────────────────────────────────────────────────

	/**
	 * Import a single lesson (lesson-only mode).
	 *
	 * @param object $plugin     learndash-bulk plugin instance.
	 * @param array  $rendered   BlockRenderer output.
	 * @param int    $course_id  Target course ID.
	 * @param string $img_dir    Absolute path to extracted images.
	 * @param string $post_title Lesson title (from config panel or deck name).
	 * @param array  &$errors    Errors collected during import.
	 * @return int[]|WP_Error
	 */
	private function import_lesson_only( object $plugin, array $rendered, int $course_id, string $img_dir, string $post_title, bool $overwrite, array &$errors ): array|WP_Error {
		$row = array(
			'post_title'   => $post_title,
			'post_content' => $rendered['lesson_html'] ?? '',
			'course_id'    => $course_id,
			'post_type'    => 'sfwd-lessons',
		);

		return $this->run_import_row( $plugin, $row, $img_dir, $overwrite, $errors );
	}


	/**
	 * Import a lesson + multiple topics (lesson-with-topics mode).
	 *
	 * @param object $plugin
	 * @param array  $rendered
	 * @param int    $course_id
	 * @param string $img_dir
	 * @param string $post_title Lesson title (from config panel or deck name).
	 * @param array  &$errors
	 * @return int[]|WP_Error
	 */
	private function import_lesson_with_topics( object $plugin, array $rendered, int $course_id, string $img_dir, string $post_title, bool $overwrite, array &$errors ): array|WP_Error {
		$post_ids = array();

		// 1. Create the lesson.
		$lesson_row = array(
			'post_title'   => $post_title,
			'post_content' => $rendered['lesson_html'] ?? '',
			'course_id'    => $course_id,
			'post_type'    => 'sfwd-lessons',
		);

		$lesson_ids = $this->run_import_row( $plugin, $lesson_row, $img_dir, $overwrite, $errors );
		if ( is_wp_error( $lesson_ids ) ) {
			return $lesson_ids;
		}

		$lesson_id = ! empty( $lesson_ids[0] ) ? (int) $lesson_ids[0] : 0;
		$post_ids  = array_merge( $post_ids, $lesson_ids );

		// 2. Create topics.
		foreach ( $rendered['topics'] as $topic ) {
			$topic_row = array(
				'post_title'   => $topic['title'],
				'post_content' => $topic['html'],
				'course_id'    => $course_id,
				'lesson_id'    => $lesson_id,
				'post_type'    => 'sfwd-topic',
			);
			$topic_ids = $this->run_import_row( $plugin, $topic_row, $img_dir, $overwrite, $errors );
			if ( ! is_wp_error( $topic_ids ) ) {
				$post_ids = array_merge( $post_ids, $topic_ids );
			}
		}

		return $post_ids;
	}


	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Call run_import() on the bulk plugin and rewrite image paths.
	 *
	 * Extended_LearnDash_Bulk_Create::run_import() expects:
	 *   - $content_type : post type slug (e.g. 'sfwd-lessons', 'sfwd-topic')
	 *   - $headers      : ordered list of column names
	 *   - $rows         : array of rows, each row is a values array matching $headers
	 *   - $options      : ['overwrite' => bool, 'media_dir' => string]
	 *
	 * The $row passed in here is an associative array keyed by column name, so
	 * we derive $headers and a single-element $rows from it.
	 *
	 * @param  object $plugin    learndash-bulk plugin instance.
	 * @param  array  $row       Associative row data (post_title, post_content, etc.).
	 * @param  string $img_dir   Path to extracted image files for media rewrite.
	 * @param  array  &$errors   Accumulates any errors.
	 * @return int[]|WP_Error    Created/updated post IDs.
	 */
	private function run_import_row( object $plugin, array $row, string $img_dir, bool $overwrite, array &$errors ): array|WP_Error {
		// Separate the post_type out — it drives content_type, not a column.
		$content_type = $row['post_type'] ?? 'sfwd-lessons';
		unset( $row['post_type'] );

		// Build CSV-style headers + rows from the associative array.
		$headers = array_keys( $row );
		$rows    = array( array_values( $row ) );

		try {
			// run_import() returns a stats array, not post IDs directly.
			$result = $plugin->run_import(
				$content_type,
				$headers,
				$rows,
				array(
					'overwrite' => $overwrite,
					'media_dir' => $img_dir,
				)
			);

			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
				return $result;
			}

			if ( ! is_array( $result ) ) {
				return array();
			}

			// Collect errors from the stats.
			if ( ! empty( $result['errors'] ) ) {
				$errors = array_merge( $errors, $result['errors'] );
			}

			// Collect created and updated post IDs.
			$post_ids = array();
			foreach ( $result['created_entries'] ?? array() as $entry ) {
				$id = is_array( $entry ) ? ( $entry['id'] ?? 0 ) : (int) $entry;
				if ( $id ) {
					$post_ids[] = $id;
				}
			}
			foreach ( $result['updated_entries'] ?? array() as $entry ) {
				$id = is_array( $entry ) ? ( $entry['id'] ?? 0 ) : (int) $entry;
				if ( $id ) {
					$post_ids[] = $id;
				}
			}

			// Also collect skipped IDs (existing posts matched by title when overwrite
			// is false). These are included so the job tracks the associated post ID
			// and the action column shows the post rather than appearing blank.
			$skipped = 0;
			foreach ( $result['skipped_entries'] ?? array() as $entry ) {
				$id = is_array( $entry ) ? ( $entry['id'] ?? 0 ) : (int) $entry;
				if ( $id ) {
					$post_ids[] = $id;
					++$skipped;
				}
			}
			if ( $skipped > 0 ) {
				Utils::log(
					'Import skipped existing posts (title match, overwrite off).',
					array( 'skipped' => $skipped )
				);
			}

			return array_values( array_filter( array_map( 'intval', $post_ids ) ) );
		} catch ( \Throwable $e ) {
			$errors[] = $e->getMessage();
			return new WP_Error( 'cbf_si_import_exception', $e->getMessage() );
		}
	}


	/**
	 * Rewrite image src paths in a post using ELDBC_Media::rewrite_paths().
	 *
	 * @param int    $post_id  WP post ID.
	 * @param string $img_dir  Absolute path to image directory.
	 * @param array  &$errors  Accumulated errors.
	 */
	private function rewrite_post_images( int $post_id, string $img_dir, array &$errors ): void {
		if ( ! class_exists( 'ELDBC_Media' ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$media_errors = array();
		$new_content  = \ELDBC_Media::rewrite_paths( $post->post_content, $img_dir, $media_errors );

		if ( ! empty( $media_errors ) ) {
			$errors = array_merge( $errors, $media_errors );
		}

		if ( $new_content !== $post->post_content ) {
			wp_update_post(
				array(
					'ID' => $post_id,
					'post_content' => $new_content,
				)
			);
		}
	}


	/**
	 * Retrieve the active learndash-bulk plugin instance.
	 *
	 * The plugin (learndash-bulk-lessons-or-topics) instantiates its main class
	 * directly into a global variable at the bottom of its bootstrap file:
	 *
	 *   $extended_learndash_bulk_create = new Extended_LearnDash_Bulk_Create();
	 *
	 * We retrieve it via the global. If the global is absent (plugin inactive or
	 * not yet loaded), we fall back to constructing a fresh instance, which is
	 * safe because the constructor only registers hooks.
	 *
	 * @return object|WP_Error Plugin instance with run_import() method.
	 */
	private function get_bulk_plugin(): mixed {
		global $extended_learndash_bulk_create;

		if ( ! empty( $extended_learndash_bulk_create ) && is_object( $extended_learndash_bulk_create ) ) {
			return $extended_learndash_bulk_create;
		}

		// Fallback: instantiate directly if the class is available.
		if ( class_exists( 'Extended_LearnDash_Bulk_Create' ) ) {
			return new \Extended_LearnDash_Bulk_Create();
		}

		return new WP_Error(
			'cbf_si_no_bulk_plugin',
			'learndash-bulk-lessons-or-topics plugin is not available. Cannot import.'
		);
	}
}
