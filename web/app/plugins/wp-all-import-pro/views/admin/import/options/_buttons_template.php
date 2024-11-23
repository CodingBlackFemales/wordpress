<?php include (__DIR__ . '/../options/scheduling/_save_scheduling_button_blue.php'); ?>

<div class="wpallimport-submit-buttons">
	<?php wp_nonce_field('options', '_wpnonce_options') ?>
	<input type="hidden" name="is_submitted" value="1" />
	<input type="hidden" name="converted_options" value="1"/>
	
	<?php if ($isWizard): ?>

		<a href="<?php echo apply_filters('pmxi_options_back_link', esc_url(add_query_arg('action', 'template', $this->baseUrl), $isWizard)); ?>" class="back rad3"><?php _e('Back to Step 3', 'wp-all-import-pro') ?></a>

		<?php if (isset($source_type) and in_array($source_type, array('url', 'ftp', 'file'))): ?>
            <input type="hidden" disabled="disabled" name="save_only" value="Save Only" <?php _e('Save Only', 'wp-all-import-pro') ?> id="save_only_field" />
            <?php renderButton('Save Only', $this->isWizard, false, true); ?>
            <!--<input type="submit" name="save_only" class="button button-primary button-hero wpallimport-large-button" value="<?php _e('Save Only', 'wp-all-import-pro') ?>" style="background:#425f9a;"/> -->
		<?php endif ?>
        <?php renderButton('Continue', $this->isWizard, true, false); ?>

		<!--<input type="submit" class="button button-primary button-hero wpallimport-large-button" value="<?php _e('Continue', 'wp-all-import-pro') ?>" /> -->

	<?php else: ?>		
		<a href="<?php echo apply_filters('pmxi_options_back_link', esc_url(remove_query_arg('id', remove_query_arg('action', $this->baseUrl))), $isWizard); ?>" class="back rad3"><?php _e('Back to Manage Imports', 'wp-all-import-pro') ?></a>
        <?php renderButton('Save Import Configuration', $this->isWizard); ?>
	<?php endif ?>
</div>