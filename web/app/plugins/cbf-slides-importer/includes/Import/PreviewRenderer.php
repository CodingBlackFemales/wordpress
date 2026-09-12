<?php
/**
 * Renders Gutenberg block HTML from a parsed source document for preview purposes.
 *
 * Encapsulates the re-parse → re-classify → render pipeline that both
 * JobRunner (eager preview at parse time) and PreviewController (on-demand
 * refresh when config changes) need to perform.
 *
 * @class   Import\PreviewRenderer
 * @version 1.1.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Import;

use CodingBlackFemales\SlidesImporter\Document\BlockRenderer;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Document\SlideClassifier;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PreviewRenderer class.
 *
 * All methods are static — there is no per-instance state. The class exists
 * purely as a namespace for the render-from-summary logic so it can be called
 * from both the background job runner and the REST controller without
 * duplicating the pipeline.
 */
final class PreviewRenderer {

	/**
	 * Build block HTML from the job's stored source file + config summary.
	 *
	 * Requires the source file to still exist on disk. Returns WP_Error if the
	 * file has been cleaned up (e.g. after a completed import) or if parsing
	 * fails.
	 *
	 * The returned array matches `BlockRenderer::render()` output:
	 *
	 *   [
	 *     'lesson_html' => '<string>',
	 *     'topics'      => [ [ 'title' => '…', 'html' => '…' ], … ],
	 *   ]
	 *
	 * @param  array $summary Decoded result_summary array from the job DB row.
	 * @return array{lesson_html: string, topics: array}|WP_Error
	 */
	public static function render_from_summary( array $summary ): array|WP_Error {
		$source_path = JobRunner::source_path( $summary );
		$img_dir     = $summary['img_dir'] ?? '';

		if ( empty( $source_path ) || ! file_exists( $source_path ) ) {
			return new WP_Error(
				'cbf_si_preview_unavailable',
				__(
					'Preview unavailable: the source file has been removed. It may have been cleaned up after a completed import.',
					'cbf-slides-importer'
				)
			);
		}

		$parsed = ParserFactory::parse( $source_path, $img_dir );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$config     = self::config_from( $summary );
		$classified = SlideClassifier::classify(
			$parsed,
			$config['heading_layout_regex'] ?? '',
			self::decode_overrides( $config )
		);

		return BlockRenderer::render(
			$classified,
			$config['mode'] ?? 'lesson-only',
			true,
			self::media_base_url( $img_dir )
		);
	}


	/**
	 * The config stored on a job summary, or an empty one.
	 *
	 * @param  array $summary Decoded result_summary array.
	 * @return array
	 */
	private static function config_from( array $summary ): array {
		$config = $summary['config'] ?? array();

		return is_array( $config ) ? $config : array();
	}


	/**
	 * Decode the per-entry type overrides stored on a job's config.
	 *
	 * @param  array $config Stored config.
	 * @return array<int, string> Overrides keyed by 1-based entry number.
	 */
	private static function decode_overrides( array $config ): array {
		if ( empty( $config['slide_overrides'] ) ) {
			return array();
		}

		$decoded = json_decode( $config['slide_overrides'], true );

		return is_array( $decoded ) ? $decoded : array();
	}


	/**
	 * Derive a browser-loadable URL for a job's extracted image directory.
	 *
	 * The directory lives inside wp-uploads (created by Utils::tmp_dir()), so
	 * the filesystem path maps to a URL via wp_upload_dir(). Preview HTML needs
	 * real <img src> values; the import path uses relative `media/` paths
	 * instead and rewrites them after attachments are created.
	 *
	 * @param  string $img_dir Absolute path to the job's image directory.
	 * @return string Base URL, or '' when the path is outside the uploads dir.
	 */
	private static function media_base_url( string $img_dir ): string {
		$upload = wp_upload_dir();

		if ( empty( $upload['basedir'] ) || empty( $img_dir ) ) {
			return '';
		}

		return str_replace(
			untrailingslashit( $upload['basedir'] ),
			untrailingslashit( $upload['baseurl'] ),
			untrailingslashit( $img_dir )
		);
	}
}
