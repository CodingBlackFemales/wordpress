<?php

namespace Wpai\WordPress;

class RegenerateImages {
	private static $attachment_ids = [];
	private static $batch_size = 10;
	private static $allocated_time_per_image = 4;

	public static function setup() {
		\add_action('pmxi_background_image_regen', [__CLASS__, 'background_image_regen_handler'], 10, 2);
	}

	public static function init($attachment_ids, $batch_size, $import_id) {
		self::$attachment_ids = $attachment_ids;
		self::$batch_size = $batch_size;
		self::schedule_image_regen(0, $import_id);
	}

	public static function schedule_image_regen($offset, $import_id) {
		$total_images = \count(self::$attachment_ids);
		$batches = \array_chunk(self::$attachment_ids, self::$batch_size);
		$results = [];

		foreach ($batches as $batch) {
			$results[] = \wp_schedule_single_event(\time() + ($offset * (count($batch) * self::$allocated_time_per_image)), 'pmxi_background_image_regen', [$batch, $import_id, rand()], true);
			$offset++;
		}

		if (\defined('WP_DEBUG') && WP_DEBUG) {
			\error_log("WP All Import Scheduled {$total_images} images in " . \count($batches) . " batches.");
		}
	}

	public static function background_image_regen_handler($batch, $import_id) {
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');

		foreach ($batch as $attachment_id) {
			$image_meta = \wp_generate_attachment_metadata($attachment_id, \get_attached_file($attachment_id));
			if (!\is_wp_error($image_meta) && !empty($image_meta)) {
				\wp_update_attachment_metadata($attachment_id, $image_meta);
			} else {
				if (\defined('WP_DEBUG') && WP_DEBUG) {
					\error_log('WP All Import: Failed to generate metadata for attachment ID ' . $attachment_id);
				}
			}
		}

		AttachmentHandler::remove_values_from_stored_data($import_id, 'createdAttachmentIds', $batch);

		if (\defined('WP_DEBUG') && WP_DEBUG) {
			\error_log('WP All Import Processed a batch of ' . \count($batch) . ' images.');
		}

		// Clean up after the last scheduled task is complete.
		$is_last_run = self::is_last_scheduled_event('pmxi_background_image_regen');
		if ($is_last_run) {
			if (\defined('WP_DEBUG') && WP_DEBUG) {
				\error_log('WP All Import finished delayed processing of images for import ID ' . $import_id . '.');
			}

			AttachmentHandler::reset_data('createdAttachmentIds', false, $import_id);

		}
	}

	public static function plugin_deactivation() {
		\remove_action('pmxi_background_image_regen', [__CLASS__, 'background_image_regen_handler']);

		// Get all scheduled events.
		$crons = _get_cron_array();

		foreach ($crons as $timestamp => $cron) {
			if (isset($cron['pmxi_background_image_regen'])) {
				// Loop through each event and unschedule it.
				foreach ($cron['pmxi_background_image_regen'] as $hook_key => $event) {
					\wp_unschedule_event($timestamp, 'pmxi_background_image_regen');
				}
			}
		}

		if (\defined('WP_DEBUG') && WP_DEBUG) {
			\error_log('WP All Import: Plugin deactivated, all scheduled events cleared.');
		}
	}

	private static function is_last_scheduled_event($hook) {
		$crons = _get_cron_array();
		$scheduled_events_count = 0;

		foreach ($crons as $timestamp => $cron) {
			if (isset($cron[$hook])) {
				$scheduled_events_count += count($cron[$hook]);
			}
		}

		return $scheduled_events_count < 1;
	}
}