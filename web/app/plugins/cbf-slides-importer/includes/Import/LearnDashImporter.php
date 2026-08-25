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
		$config    = $summary['config'] ?? array();
		$mode      = $config['mode'] ?? 'lesson-only';
		$course_id = ! empty( $config['course_id'] ) ? (int) $config['course_id'] : 0;
		$img_dir   = $summary['img_dir'] ?? '';

		// Retrieve the learndash-bulk plugin instance.
		$bulk_plugin = $this->get_bulk_plugin();
		if ( is_wp_error( $bulk_plugin ) ) {
			return $bulk_plugin;
		}

		$rendered = \CodingBlackFemales\SlidesImporter\Pptx\BlockRenderer::render(
			$classified_deck,
			$mode
		);

		$created_post_ids = array();
		$errors           = array();

		if ( $mode === 'lesson-with-topics' ) {
			$result = $this->import_lesson_with_topics( $bulk_plugin, $rendered, $course_id, $img_dir, $errors );
		} else {
			$result = $this->import_lesson_only( $bulk_plugin, $rendered, $course_id, $img_dir, $errors );
		}

		if ( ! empty( $errors ) ) {
			Utils::log( 'Import errors.', array( 'count' => count( $errors ) ) );
		}

		return is_wp_error( $result )
			? $result
			: array(
				'created_post_ids' => $result,
				'errors' => $errors,
			);
	}


	// ── Import modes ──────────────────────────────────────────────────────────

	/**
	 * Import a single lesson (lesson-only mode).
	 *
	 * @param object $plugin    learndash-bulk plugin instance.
	 * @param array  $rendered  BlockRenderer output.
	 * @param int    $course_id Target course ID.
	 * @param string $img_dir   Absolute path to extracted images.
	 * @param array  &$errors   Errors collected during import.
	 * @return int[]|WP_Error
	 */
	private function import_lesson_only( object $plugin, array $rendered, int $course_id, string $img_dir, array &$errors ): array|WP_Error {
		$html   = $rendered['lesson_html'] ?? '';
		$title  = 'Imported Lesson'; // TODO: derive from deck name.

		$row = array(
			'post_title'   => $title,
			'post_content' => $html,
			'course_id'    => $course_id,
			'post_type'    => 'sfwd-lessons',
		);

		return $this->run_import_row( $plugin, $row, $img_dir, $errors );
	}


	/**
	 * Import a lesson + multiple topics (lesson-with-topics mode).
	 *
	 * @param object $plugin
	 * @param array  $rendered
	 * @param int    $course_id
	 * @param string $img_dir
	 * @param array  &$errors
	 * @return int[]|WP_Error
	 */
	private function import_lesson_with_topics( object $plugin, array $rendered, int $course_id, string $img_dir, array &$errors ): array|WP_Error {
		$post_ids = array();

		// 1. Create the lesson.
		$lesson_row = array(
			'post_title'   => 'Imported Lesson',
			'post_content' => $rendered['lesson_html'] ?? '',
			'course_id'    => $course_id,
			'post_type'    => 'sfwd-lessons',
		);

		$lesson_ids = $this->run_import_row( $plugin, $lesson_row, $img_dir, $errors );
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
			$topic_ids = $this->run_import_row( $plugin, $topic_row, $img_dir, $errors );
			if ( ! is_wp_error( $topic_ids ) ) {
				$post_ids = array_merge( $post_ids, $topic_ids );
			}
		}

		return $post_ids;
	}


	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Call run_import_cli() on the bulk plugin and rewrite image paths.
	 *
	 * @param  object $plugin    learndash-bulk plugin instance.
	 * @param  array  $row       CSV-equivalent row data.
	 * @param  string $img_dir   Path to extracted image files.
	 * @param  array  &$errors   Accumulates any errors.
	 * @return int[]|WP_Error    Created post IDs.
	 */
	private function run_import_row( object $plugin, array $row, string $img_dir, array &$errors ): array|WP_Error {
		$post_ids = array();

		try {
			// run_import_cli() accepts an array of row arrays matching CSV columns.
			$result = $plugin->run_import_cli( array( $row ) );

			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
				return $result;
			}

			if ( is_array( $result ) ) {
				$post_ids = array_filter( array_map( 'intval', $result ) );
			}

			// Rewrite image paths in post content.
			if ( ! empty( $img_dir ) ) {
				foreach ( $post_ids as $post_id ) {
					$this->rewrite_post_images( $post_id, $img_dir, $errors );
				}
			}
		} catch ( \Throwable $e ) {
			$errors[] = $e->getMessage();
			return new WP_Error( 'cbf_si_import_exception', $e->getMessage() );
		}

		return $post_ids;
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
	 * @return object|WP_Error Plugin instance with run_import_cli() method.
	 */
	private function get_bulk_plugin(): mixed {
		// The learndash-bulk plugin registers itself via a global or static method.
		// Check the most likely entry points.
		if ( function_exists( 'learndash_bulk_plugin' ) ) {
			return learndash_bulk_plugin();
		}

		if ( class_exists( 'ELDBC_Plugin' ) && method_exists( 'ELDBC_Plugin', 'instance' ) ) {
			return \ELDBC_Plugin::instance();
		}

		return new WP_Error(
			'cbf_si_no_bulk_plugin',
			'learndash-bulk-lessons-or-topics plugin is not available. Cannot import.'
		);
	}
}
