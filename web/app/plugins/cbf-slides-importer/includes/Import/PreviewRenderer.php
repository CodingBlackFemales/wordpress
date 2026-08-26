<?php
/**
 * Renders Gutenberg block HTML from a parsed PPTX for preview purposes.
 *
 * Encapsulates the re-parse → re-classify → render pipeline that both
 * JobRunner (eager preview at parse time) and PreviewController (on-demand
 * refresh when config changes) need to perform.
 *
 * @class   Import\PreviewRenderer
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Import;

use CodingBlackFemales\SlidesImporter\Pptx\Parser;
use CodingBlackFemales\SlidesImporter\Pptx\SlideClassifier;
use CodingBlackFemales\SlidesImporter\Pptx\BlockRenderer;
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
	 * Build block HTML from the job's stored PPTX + config summary.
	 *
	 * Requires the PPTX file to still exist on disk. Returns WP_Error if the
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
		$pptx_path = $summary['pptx_path'] ?? '';
		$img_dir   = $summary['img_dir'] ?? '';

		if ( empty( $pptx_path ) || ! file_exists( $pptx_path ) ) {
			return new WP_Error(
				'cbf_si_preview_unavailable',
				__(
					'Preview unavailable: the source file has been removed. It may have been cleaned up after a completed import.',
					'cbf-slides-importer'
				)
			);
		}

		$parsed = Parser::parse( $pptx_path, $img_dir );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$config          = isset( $summary['config'] ) && is_array( $summary['config'] ) ? $summary['config'] : array();
		$heading_regex   = $config['heading_layout_regex'] ?? '';
		$slide_overrides = array();

		if ( ! empty( $config['slide_overrides'] ) ) {
			$decoded = json_decode( $config['slide_overrides'], true );
			if ( is_array( $decoded ) ) {
				$slide_overrides = $decoded;
			}
		}

		$mode       = $config['mode'] ?? 'lesson-only';
		$classified = SlideClassifier::classify( $parsed, $heading_regex, $slide_overrides );

		// Derive an absolute URL for the image directory so the preview HTML
		// contains real <img src> values the browser can load.  The img_dir
		// lives inside wp-uploads (created by Utils::tmp_dir()), so we can map
		// the filesystem path to a URL via wp_upload_dir().
		$upload      = wp_upload_dir();
		$img_base_url = '';
		if ( ! empty( $upload['basedir'] ) && ! empty( $img_dir ) ) {
			$img_base_url = str_replace(
				untrailingslashit( $upload['basedir'] ),
				untrailingslashit( $upload['baseurl'] ),
				untrailingslashit( $img_dir )
			);
		}

		return BlockRenderer::render( $classified, $mode, true, $img_base_url );
	}
}
