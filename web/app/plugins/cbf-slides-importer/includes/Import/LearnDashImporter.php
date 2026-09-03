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
	 * Posts are created in their natural 'publish' state. If any errors occur
	 * during the batch, all created posts are reverted to 'draft' so students
	 * never see partial content (NR4). Using revert-on-error rather than a
	 * temporary wp_insert_post_data filter avoids interfering with any other
	 * concurrent post-creation processes.
	 *
	 * @param  array $classified_deck ParsedDeck with slide_type and rendered HTML.
	 * @param  array $summary         Result summary from JobRunner (contains config, mode, img_dir, etc.).
	 * @return array|WP_Error { created_post_ids: int[], skipped_post_ids: int[], errors: string[] }
	 */
	public function import( array $classified_deck, array $summary ): array|WP_Error {
		$params      = $this->parse_import_params( $summary );
		$bulk_plugin = $this->get_bulk_plugin();
		if ( is_wp_error( $bulk_plugin ) ) {
			return $bulk_plugin;
		}

		$rendered    = \CodingBlackFemales\SlidesImporter\Pptx\BlockRenderer::render( $classified_deck, $params['mode'] );
		$errors      = array();
		$skipped_ids = array();
		$result      = $this->run_import_mode( $bulk_plugin, $rendered, $params, $errors, $skipped_ids );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! empty( $errors ) ) {
			Utils::log( 'Import errors — reverting created posts to draft.', array( 'count' => count( $errors ) ) );
			$this->revert_to_draft( $result );
		}

		return array(
			'created_post_ids' => $result,
			'skipped_post_ids' => $skipped_ids,
			'errors'           => $errors,
		);
	}


	/**
	 * Parse import parameters from a job summary into a flat array.
	 *
	 * @param array $summary Job result_summary.
	 * @return array { mode, course_id, lesson_id, overwrite, img_dir, post_title }
	 */
	private function parse_import_params( array $summary ): array {
		$config = $summary['config'] ?? array();
		return array(
			'mode'       => $config['mode'] ?? 'lesson-only',
			'course_id'  => ! empty( $config['course_id'] ) ? (int) $config['course_id'] : 0,
			'lesson_id'  => ! empty( $config['lesson_id'] ) ? (int) $config['lesson_id'] : 0,
			'overwrite'  => ! empty( $config['overwrite'] ),
			'img_dir'    => $summary['img_dir'] ?? '',
			'post_title' => $this->resolve_post_title( $config, $summary ),
		);
	}


	/**
	 * Resolve the post title from config, falling back to deck name.
	 *
	 * @param array $config  Stored config.
	 * @param array $summary Job result_summary.
	 * @return string
	 */
	private function resolve_post_title( array $config, array $summary ): string {
		if ( ! empty( $config['post_title'] ) ) {
			return $config['post_title'];
		}
		$mode     = $config['mode'] ?? 'lesson-only';
		$fallback = $mode === 'topic' ? 'Imported Topic' : 'Imported Lesson';
		return $summary['deck_name'] ?? $fallback;
	}


	/**
	 * Dispatch to the mode-appropriate importer method.
	 *
	 * Isolating this keeps import() well under the cyclomatic complexity limit.
	 *
	 * @param object $bulk_plugin  learndash-bulk plugin instance.
	 * @param array  $rendered     BlockRenderer output.
	 * @param array  $params       Parsed import params (mode, course_id, lesson_id, …).
	 * @param array  &$errors      Accumulated errors.
	 * @param array  &$skipped_ids Accumulated skipped post IDs.
	 * @return int[]|WP_Error
	 */
	private function run_import_mode( object $bulk_plugin, array $rendered, array $params, array &$errors, array &$skipped_ids ): array|WP_Error {
		if ( $params['mode'] === 'topic' ) {
			return $this->import_as_topic( $bulk_plugin, $rendered, $params['course_id'], $params['lesson_id'], $params['img_dir'], $params['post_title'], $params['overwrite'], $errors, $skipped_ids );
		}
		return $this->import_lesson_only( $bulk_plugin, $rendered, $params['course_id'], $params['img_dir'], $params['post_title'], $params['overwrite'], $errors, $skipped_ids );
	}


	// ── Import modes ──────────────────────────────────────────────────────────

	/**
	 * Import a single lesson (lesson-only mode).
	 *
	 * @param object $plugin       learndash-bulk plugin instance.
	 * @param array  $rendered     BlockRenderer output.
	 * @param int    $course_id    Target course ID.
	 * @param string $img_dir      Absolute path to extracted images.
	 * @param string $post_title   Lesson title (from config panel or deck name).
	 * @param bool   $overwrite    Whether to overwrite existing posts matched by title.
	 * @param array  &$errors      Errors collected during import.
	 * @param array  &$skipped_ids IDs of posts skipped due to title match + overwrite=false.
	 * @return int[]|WP_Error
	 */
	private function import_lesson_only( object $plugin, array $rendered, int $course_id, string $img_dir, string $post_title, bool $overwrite, array &$errors, array &$skipped_ids ): array|WP_Error {
		$row = array(
			'post_title'   => $post_title,
			'post_content' => $rendered['lesson_html'] ?? '',
			'course_id'    => $course_id,
			'post_type'    => 'sfwd-lessons',
		);

		return $this->run_import_row( $plugin, $row, $img_dir, $overwrite, $errors, $skipped_ids );
	}


	/**
	 * Import the deck as a single topic (topic mode).
	 *
	 * @param object $plugin       learndash-bulk plugin instance.
	 * @param array  $rendered     BlockRenderer output.
	 * @param int    $course_id    Target course ID.
	 * @param int    $lesson_id    Target lesson ID to nest the topic under (0 = unassigned).
	 * @param string $img_dir      Absolute path to extracted images.
	 * @param string $post_title   Topic title (from config panel or deck name).
	 * @param bool   $overwrite    Whether to overwrite existing posts matched by title.
	 * @param array  &$errors      Errors collected during import.
	 * @param array  &$skipped_ids IDs of posts skipped due to title match + overwrite=false.
	 * @return int[]|WP_Error
	 */
	private function import_as_topic( object $plugin, array $rendered, int $course_id, int $lesson_id, string $img_dir, string $post_title, bool $overwrite, array &$errors, array &$skipped_ids ): array|WP_Error {
		$row = array(
			'post_title'   => $post_title,
			'post_content' => $rendered['lesson_html'] ?? '',
			'course_id'    => $course_id,
			'lesson_id'    => $lesson_id,
			'post_type'    => 'sfwd-topic',
		);

		return $this->run_import_row( $plugin, $row, $img_dir, $overwrite, $errors, $skipped_ids );
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
	 * Posts that the bulk plugin skips due to a title match when overwrite is false
	 * are collected into $skipped_ids (not $post_ids) so the caller can surface a
	 * clear "existing post found" message rather than silently reporting 0 created.
	 *
	 * @param  object $plugin       learndash-bulk plugin instance.
	 * @param  array  $row          Associative row data (post_title, post_content, etc.).
	 * @param  string $img_dir      Path to extracted image files for media rewrite.
	 * @param  bool   $overwrite    Whether to overwrite existing posts matched by title.
	 * @param  array  &$errors      Accumulates any errors.
	 * @param  array  &$skipped_ids Accumulates IDs of skipped (title-matched) posts.
	 * @return int[]|WP_Error       Created/updated post IDs only.
	 */
	private function run_import_row( object $plugin, array $row, string $img_dir, bool $overwrite, array &$errors, array &$skipped_ids ): array|WP_Error {
		$content_type = $row['post_type'] ?? 'sfwd-lessons';
		unset( $row['post_type'] );
		$headers = array_keys( $row );
		$rows    = array( array_values( $row ) );

		try {
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

			return $this->parse_result_stats( $result, $errors, $skipped_ids );
		} catch ( \Throwable $e ) {
			$errors[] = $e->getMessage();
			return new WP_Error( 'cbf_si_import_exception', $e->getMessage() );
		}
	}


	/**
	 * Extract created/updated/skipped post IDs from a run_import() stats array.
	 *
	 * @param array  $result      Stats array returned by the bulk plugin.
	 * @param array  &$errors     Accumulated errors.
	 * @param array  &$skipped_ids Accumulated skipped post IDs.
	 * @return int[] Created and updated post IDs.
	 */
	private function parse_result_stats( array $result, array &$errors, array &$skipped_ids ): array {
		if ( ! empty( $result['errors'] ) ) {
			$errors = array_merge( $errors, $result['errors'] );
		}

		$post_ids = array_merge(
			$this->extract_entry_ids( $result['created_entries'] ?? array() ),
			$this->extract_entry_ids( $result['updated_entries'] ?? array() )
		);

		$skipped = $this->collect_skipped( $result['skipped_entries'] ?? array(), $skipped_ids );
		if ( $skipped > 0 ) {
			Utils::log(
				'Import skipped existing posts (title match, overwrite off).',
				array( 'skipped' => $skipped )
			);
		}

		return array_values( array_filter( array_map( 'intval', $post_ids ) ) );
	}


	/**
	 * Extract integer post IDs from a bulk-plugin entries array.
	 *
	 * Each entry is either an int (bare ID) or an array containing an 'id' key.
	 *
	 * @param array $entries
	 * @return int[]
	 */
	private function extract_entry_ids( array $entries ): array {
		$ids = array();
		foreach ( $entries as $entry ) {
			$id = is_array( $entry ) ? ( $entry['id'] ?? 0 ) : (int) $entry;
			if ( $id ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}


	/**
	 * Append skipped-entry IDs to $skipped_ids and return the count added.
	 *
	 * @param array $entries     Skipped entries from the bulk-plugin stats.
	 * @param array &$skipped_ids Accumulated skipped IDs.
	 * @return int Number of skipped IDs appended.
	 */
	private function collect_skipped( array $entries, array &$skipped_ids ): int {
		$count = 0;
		foreach ( $entries as $entry ) {
			$id = is_array( $entry ) ? ( $entry['id'] ?? 0 ) : (int) $entry;
			if ( $id ) {
				$skipped_ids[] = $id;
				++$count;
			}
		}
		return $count;
	}


	/**
	 * Revert a set of published posts back to 'draft'.
	 *
	 * Called when one or more errors occurred during an import batch so that
	 * students never see incomplete content (NR4).
	 *
	 * @param int[] $post_ids Post IDs to revert.
	 */
	private function revert_to_draft( array $post_ids ): void {
		foreach ( $post_ids as $post_id ) {
			wp_update_post(
				array(
					'ID'          => (int) $post_id,
					'post_status' => 'draft',
				)
			);
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
