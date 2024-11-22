<div style="padding-top:11px;">
    <label class="manual-scheduling-label">
        <input type="radio" name="scheduling_enable"
               value="2" <?php if ($post['scheduling_enable'] == 2) { ?> checked="checked" <?php } ?>/>
        <h4 style="margin: 0;display: inline-block;"><?php _e('Manual Scheduling', 'wp-all-import-pro'); ?></h4>
    </label>
    <div class="run-this-export-using-cronjobs" style="margin-left: 26px; margin-bottom: 10px; font-size: 13px; margin-top:12px;"><?php _e('Run this import using cron jobs.', 'wp-all-import-pro'); ?></div>
    <div style="<?php if ($post['scheduling_enable'] != 2) { ?> display: none; <?php } ?>" class="manual-scheduling">
        <p style="margin:0;">
        <h5 style="margin-bottom: 10px; margin-top: 10px; font-size: 14px;"><?php _e('Trigger URL', 'wp-all-import-pro'); ?></h5>
        <code style="padding: 10px; border: 1px solid #ccc; display: block; width: 90%;">
	        <?php echo site_url() . '/wp-load.php?import_key=' . $cron_job_key . '&import_id=' . $import_id . '&action=trigger'; ?>
        </code>
        <h5 style="margin-bottom: 10px; margin-top: 10px; font-size: 14px;"><?php _e('Processing URL', 'wp-all-import-pro'); ?></h5>
        <code style="padding: 10px; border: 1px solid #ccc; display: block; width: 90%;">
		    <?php echo site_url() . '/wp-load.php?import_key=' . $cron_job_key . '&import_id=' . $import_id . '&action=processing'; ?>
        </code>

        </p>
        <p style="margin: 0 0 15px;">
        <h5 style="margin-bottom: 10px; margin-top: 10px; font-size: 14px;"><?php _e('Example Trigger Cron Command', 'wp-all-import-pro'); ?></h5>
        <code style="padding: 10px; border: 1px solid #ccc; display: block; width: 90%;">
		    <?php echo 'wget -q -O - "'.site_url() . '/wp-load.php?import_key=' . $cron_job_key . '&import_id=' . $import_id . '&action=trigger&rand="$RANDOM'; ?>
        </code>
        <h5 style="margin-bottom: 10px; margin-top: 10px; font-size: 14px;"><?php _e('Example Processing Cron Command', 'wp-all-import-pro'); ?></h5>
        <code style="padding: 10px; border: 1px solid #ccc; display: block; width: 90%;">
		    <?php echo 'wget -q -O - "'.site_url() . '/wp-load.php?import_key=' . $cron_job_key . '&import_id=' . $import_id . '&action=processing&rand="$RANDOM'; ?>
        </code>
        </p>
        <p style="margin:0; padding-left: 0;"><?php _e('Read more about manual scheduling', 'wp-all-import-pro'); ?>: <a target="_blank" href="https://www.wpallimport.com/documentation/cron/">https://www.wpallimport.com/documentation/cron/</a></p>
    </div>
</div>
