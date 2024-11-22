<?php $is_new_import = ($isWizard or $import->imported + $import->skipped == $import->count or $import->imported + $import->skipped == 0 or $import->options['is_import_specified'] or $import->triggered); ?>
<?php $visible_sections = apply_filters('pmxi_visible_confirm_sections', array('data_to_import'), $post['custom_type']); ?>
<h2 class="wpallimport-wp-notices"></h2>

<div class="wpallimport-wrapper wpallimport-step-5">

	<div class="wpallimport-wrapper">
		<div class="wpallimport-header">
			<div class="wpallimport-logo"></div>
			<div class="wpallimport-title">
				<h2><?php _e('Confirm & Run', 'wp-all-import-pro'); ?></h2>
			</div>
			<div class="wpallimport-links">
				<a href="https://www.wpallimport.com/support/" target="_blank"><?php _e('Support', 'wp-all-import-pro'); ?></a> | <a href="https://www.wpallimport.com/documentation/" target="_blank"><?php _e('Documentation', 'wp-all-import-pro'); ?></a>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	<?php
	$is_valid_root_element = true;
	$error_codes = $this->errors->get_error_codes();
	if ( ! empty($error_codes) and is_array($error_codes) and in_array('root-element-validation', $error_codes))
	{
		$is_valid_root_element = false;
	}
	?>
	<div class="ajax-console">
		<?php if ($this->errors->get_error_codes()): ?>
			<?php $this->error() ?>
		<?php endif ?>
		<?php if ($this->warnings->get_error_codes()): ?>
			<?php $this->warning() ?>
		<?php endif ?>

		<?php
			wp_all_import_template_notifications( $post );
		?>
	</div>

	<div class="rad4 first-step-errors error-no-root-element" <?php if ($is_valid_root_element === false):?>style="display:block;"<?php endif; ?>>
		<div class="wpallimport-notify-wrapper">
			<div class="error-headers exclamation">
				<?php if (isset($import) && !$import->isEmpty() && $import->type == 'url'): ?>
				<h3><?php _e('This URL no longer returns an import file', 'wp-all-import-pro');?></h3>
				<h4><?php _e("You must provide a URL that returns a valid import file.", "wp-all-import-pro"); ?></h4>
				<?php else: ?>
				<h3><?php _e('There\'s a problem with your import file', 'wp-all-import-pro');?></h3>
				<h4><?php _e("It has changed and is not compatible with this import template.", "wp-all-import-pro"); ?></h4>
				<?php endif; ?>
			</div>
		</div>
		<a class="button button-primary button-hero wpallimport-large-button wpallimport-notify-read-more" href="https://www.wpallimport.com/documentation/problems-with-import-files/#problem-with-file" target="_blank"><?php _e('Read More', 'wp-all-import-pro');?></a>
	</div>

	<?php
		switch ($post['custom_type']){
			case 'taxonomies':
				$custom_type = get_taxonomy($post['taxonomy_type']);
				break;
            case 'comments':
                $custom_type = new stdClass();
                $custom_type->labels = new stdClass();
                $custom_type->labels->singular_name = __('Comments', 'wp-all-import-pro');
                $custom_type->labels->name = __('Comment', 'wp-all-import-pro');
                break;
			default:
				$custom_type = wp_all_import_custom_type( $post['custom_type'] );
				break;
		}
	?>

	<?php if ($is_valid_root_element):?>
		<div class="wpallimport-content-section" style="padding: 30px; overflow: hidden;">
			<div class="wpallimport-ready-to-go">

				<?php if ($is_new_import):?>
				<h3><?php _e('Your file is all set up!', 'wp-all-import-pro'); ?></h3>
				<?php else: ?>
				<h3><?php _e('This import did not finish successfully last time it was run.', 'wp-all-import-pro'); ?></h3>
				<?php endif; ?>

				<?php if ($is_new_import):?>
					<h4><?php _e('Check the settings below, then click the green button to run the import.', 'wp-all-import-pro'); ?></h4>
				<?php else: ?>
					<h4><?php _e('You can attempt to continue where it left off.', 'wp-all-import-pro'); ?></h4>
				<?php endif; ?>

			</div>
			<?php if ($is_new_import):?>
				<form class="confirm <?php echo ! $isWizard ? 'edit' : '' ?>" method="post" style="float:right;">
					<?php wp_nonce_field('confirm', '_wpnonce_confirm') ?>
					<input type="hidden" name="is_confirmed" value="1" />
					<input type="submit" class="rad10" value="<?php _e('Confirm & Run Import', 'wp-all-import-pro') ?>" />
				</form>
			<?php else: ?>
				<form class="confirm <?php echo ! $isWizard ? 'edit' : '' ?>" method="post" style="float: right;">
					<?php wp_nonce_field('confirm', '_wpnonce_confirm') ?>
					<input type="hidden" name="is_confirmed" value="1" />
					<!--input type="hidden" name="is_continue" value="1" /-->
					<div class="input wpallimport-is-continue">
						<div class="input">
							<input type="radio" name="is_continue" value="yes" checked="checked" id="is_continue_yes"/>
							<label for="is_continue_yes"><?php _e('Continue from the last run', 'wp-all-import-pro'); ?></label>
						</div>
						<div class="input">
							<input type="radio" name="is_continue" value="no" id="is_continue_no"/>
							<label for="is_continue_no"><?php _e('Run from the beginning', 'wp-all-import-pro'); ?></label>
						</div>
					</div>
					<input type="submit" class="rad10" value="<?php _e('Continue Import', 'wp-all-import-pro') ?>" style="margin-left: 0px; float: right;"/>
					<!--div class="input" style="margin-top:20px;">
						<a href="<?php echo esc_url(add_query_arg(array('id' => $import->id, 'action' => 'update', 'continue' => 'no'), $this->baseUrl)); ?>" id="entire_run"><?php _e('Run entire import from the beginning', 'wp-all-import-pro'); ?></a>
					</div-->
				</form>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="clear"></div>

	<table class="wpallimport-layout confirm">
		<tr>
			<td class="left">

			<?php if ( $is_new_import ):?>

			<?php $max_execution_time = ini_get('max_execution_time');?>

			<div class="wpallimport-section" style="margin-top: -20px;">
				<div class="wpallimport-content-section">
					<div class="wpallimport-collapsed-header" style="padding-left: 30px;">
						<h3 style="color: #425e99;"><?php _e('Import Summary', 'wp-all-import-pro'); ?> <?php if (!$isWizard):?><span style="color:#000;"><?php printf(__(" - ID: %s - %s", 'wp-all-import-pro'), $import->id, empty($import->friendly_name) ? $import->name : $import->friendly_name);?></span><?php endif;?></h3>
					</div>
					<div class="wpallimport-collapsed-content" style="padding: 15px 25px 25px;">

						<?php $delete_missing_notice = wp_all_import_delete_missing_notice($import->options); ?>
						<?php if (!empty($delete_missing_notice)): ?>
                            <p class="exclamation"><?php echo $delete_missing_notice; ?></p>
						<?php endif; ?>

						<!-- Warnings -->
						<?php if ($max_execution_time != -1): ?>
						<p><?php printf(__('Your max_execution_time is %s seconds', 'wp-all-import-pro'), $max_execution_time); ?></p>
						<?php endif;?>

						<!-- General -->
						<?php
							$import_type = (!empty($source['type'])) ? $source['type'] : $import['type'];
							$path = $source['path'];
							if ( in_array($import_type, array('upload', 'file'))){
								$path = wp_all_import_get_absolute_path($source['path']);
							}
							if ( in_array($import_type, array('upload'))){
								$path_parts = pathinfo($source['path']);
								if ( ! empty($path_parts['dirname'])){
									$path_all_parts = explode('/', $path_parts['dirname']);
									$dirname = array_pop($path_all_parts);
									if ( wp_all_import_isValidMd5($dirname)){
										$path = str_replace($dirname, preg_replace('%^(.{3}).*(.{3})$%', '$1***$2', $dirname), str_replace('temp/', '', $path));
									}
								}
							} elseif ( in_array($import_type, array('ftp'))){
							    $path = $import->options['ftp_username'] . '@' . preg_replace('%^ftps?://%i', '', $import->options['ftp_host']) . '/' . $import->options['ftp_path'];
                            } else{
								$path = str_replace("\\", '/', preg_replace('%^(\w+://[^:]+:)[^@]+@%', '$1*****@', $path));
							}
							if ( in_array($import_type, array('upload', 'file'))){ $path = preg_replace('%.*wp-content/%', 'wp-content/', $path); }
						?>
						<p><?php printf(__('WP All Import will import the file <span style="color:#40acad;">%s</span>, which is <span style="color:#000; font-weight:bold;">%s</span>', 'wp-all-import-pro'), $path, (isset($locfilePath)) ? pmxi_human_filesize(filesize($locfilePath)) : __('undefined', 'wp-all-import-pro')); ?></p>

						<?php if ( strpos($xpath, '[') !== false){ ?>
						<p><?php printf(__('WP All Import will process the records matching the XPath expression: <span style="color:#46ba69; font-weight:bold;">%s</span>', 'wp-all-import-pro'), $xpath); ?></p>
						<?php } elseif ($post['delimiter'] and $isWizard ) { ?>
						<p><?php printf(__('WP All Import will process <span style="color:#46ba69; font-weight:bold;">%s</span> rows in this import file', 'wp-all-import-pro'), $count); ?></p>
						<?php } elseif ( $isWizard ) { ?>
						<p><?php printf(__('WP All Import will process all %s <span style="color:#46ba69; font-weight:bold;">&lt;%s&gt;</span> records in this import file', 'wp-all-import-pro'), $count, $source['root_element']); ?></p>
						<?php } ?>

						<?php if ( $post['is_import_specified']): ?>
						<p><?php printf(__('WP All Import will process only specified records: %s', 'wp-all-import-pro'), $post['import_specified']); ?></p>
						<?php endif;?>

						<!-- Record Matching -->

						<?php if ( "new" == $post['wizard_type']): ?>

							<p><?php printf(__('Your unique key is <span style="color:#000; font-weight:bold;">%s</span>', 'wp-all-import-pro'), wp_all_import_clear_xss($post['unique_key'])); ?></p>

							<?php if ( ! $isWizard and !empty($custom_type)): ?>

								<p><?php printf(__('%ss previously imported by this import (ID: %s) with the same unique key will be updated.', 'wp-all-import-pro'), $custom_type->labels->singular_name, $import->id); ?></p>

								<?php if ( $post['create_new_records']): ?>
									<p><?php printf(__('Records with unique keys that don\'t match any unique keys from %ss created by previous runs of this import (ID: %s) will be created.', 'wp-all-import-pro'), $custom_type->labels->singular_name, $import->id); ?></p>
								<?php endif; ?>

							<?php endif; ?>

						<?php else: ?>

							<?php
							$criteria = '';
							if ( 'pid' == $post['duplicate_indicator']) $criteria = 'has the same ID';
							if ( 'title' == $post['duplicate_indicator']){
								switch ($post['custom_type']){
									case 'import_users':
									case 'shop_customer':
										$criteria = 'has the same Login';
										break;
									default:
										$criteria = 'has the same Title';
										break;
								}
							}
							if ( 'content' == $post['duplicate_indicator']){
								switch ($post['custom_type']){
									case 'import_users':
									case 'shop_customer':
										$criteria = 'has the same Email';
										break;
									default:
										$criteria = 'has the same Content';
										break;
								}
							}
							if ( 'custom field' == $post['duplicate_indicator']) $criteria = 'has Custom Field named "'. $post['custom_duplicate_name'] .'" with value = ' . $post['custom_duplicate_value'];
							?>
							<p><?php printf(__('WP All Import will merge data into existing %ss, matching the following criteria: %s', 'wp-all-import-pro'), $custom_type->labels->singular_name, $criteria); ?></p>
                        <?php endif; ?>

                        <?php if ( "new" != $post['wizard_type'] || !$isWizard ): ?>

                            <?php if ( "no" == $post['is_keep_former_posts'] and "yes" == $post['update_all_data']){ ?>
                                <p><?php _e('Existing data will be updated with the data specified in this import.', 'wp-all-import-pro'); ?></p>
                            <?php } elseif ("no" == $post['is_keep_former_posts'] and "no" == $post['update_all_data']){?>
                                <div>
                                    <p><?php printf(__('Next %s data will be updated, <strong>all other data will be left alone</strong>', 'wp-all-import-pro'), $custom_type->labels->singular_name); ?></p>
                                    <?php if ( in_array('data_to_import', $visible_sections)):?>
                                        <ul style="padding-left: 35px;">
                                            <?php if ( $post['is_update_status'] && 'taxonomies' != $post['custom_type'] ): ?>
                                                <li> <?php _e('status', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_title']): ?>
                                                <li> <?php _e('title', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_slug']): ?>
                                                <li> <?php _e('slug', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_content']): ?>
                                                <li> <?php _e('content', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_author']): ?>
                                                <li> <?php _e('author', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_comment_status']): ?>
                                                <li> <?php _e('comment status', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( current_theme_supports( 'post-formats' ) && post_type_supports( $post['custom_type'], 'post-formats' ) && $post['is_update_post_format']): ?>
                                                <li> <?php _e('post format', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_excerpt'] && 'taxonomies' != $post['custom_type'] && 'comments' != $post['custom_type']): ?>
                                                <li> <?php _e('excerpt', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_dates'] && 'taxonomies' != $post['custom_type']): ?>
                                                <li> <?php _e('dates', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_menu_order'] && 'taxonomies' != $post['custom_type'] && 'comments' != $post['custom_type']): ?>
                                                <li> <?php _e('menu order', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_parent']): ?>
                                                <li> <?php _e('parent post', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_post_type'] && 'taxonomies' != $post['custom_type'] && 'comments' != $post['custom_type']): ?>
                                                <li> <?php _e('post type', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( $post['is_update_attachments'] && 'taxonomies' != $post['custom_type'] && 'comments' != $post['custom_type']): ?>
                                                <li> <?php _e('attachments', 'wp-all-import-pro'); ?></li>
                                            <?php endif; ?>
                                            <?php if ( ! empty($post['is_update_acf'])): ?>
                                                <li>
                                                    <?php
                                                    switch($post['update_acf_logic']){
                                                        case 'full_update':
                                                            _e('all advanced custom fields', 'wp-all-import-pro');
                                                            break;
                                                        case 'mapped':
                                                            _e('only ACF presented in import options', 'wp-all-import-pro');
                                                            break;
                                                        case 'only':
                                                            printf(__('only these ACF : %s', 'wp-all-import-pro'), $post['acf_only_list']);
                                                            break;
                                                        case 'all_except':
                                                            printf(__('all ACF except these: %s', 'wp-all-import-pro'), $post['acf_except_list']);
                                                            break;
                                                    } ?>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ( ! empty($post['is_update_images'])): ?>
                                                <li>
                                                    <?php
                                                    switch($post['update_images_logic']){
                                                        case 'full_update':
                                                            _e('old images will be updated with new', 'wp-all-import-pro');
                                                            break;
                                                        case 'add_new':
                                                            _e('only new images will be added', 'wp-all-import-pro');
                                                            break;
                                                    } ?>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ( ! empty($post['is_update_attributes'])): ?>
                                                <li>
                                                    <?php
                                                    switch($post['update_attributes_logic']){
                                                        case 'full_update':
                                                            _e('all attributes', 'wp-all-import-pro');
                                                            break;
                                                        case 'only':
                                                            printf(__('only these attributes: %s', 'wp-all-import-pro'), $post['attributes_only_list']);
                                                            break;
                                                        case 'all_except':
                                                            printf(__('all attributes except these: %s', 'wp-all-import-pro'), $post['attributes_except_list']);
                                                            break;
                                                        case 'add_new':
                                                            _e('don\'t touch existing attributes, add new attributes', 'wp-all-import-pro');
                                                            break;
                                                    } ?>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ( ! empty($post['is_update_custom_fields'])): ?>
                                                <li>
                                                    <?php
                                                    switch($post['update_custom_fields_logic']){
                                                        case 'full_update':
                                                            _e('all custom fields', 'wp-all-import-pro');
                                                            break;
                                                        case 'only':
                                                            printf(__('only these custom fields : %s', 'wp-all-import-pro'), $post['custom_fields_only_list']);
                                                            break;
                                                        case 'all_except':
                                                            printf(__('all custom fields except these: %s', 'wp-all-import-pro'), $post['custom_fields_except_list']);
                                                            break;
                                                    } ?>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ( ! empty($post['is_update_categories']) && 'taxonomies' != $post['custom_type'] && 'comments' != $post['custom_type']): ?>
                                                <li>
                                                    <?php
                                                    switch($post['update_categories_logic']){
                                                        case 'full_update':
                                                            _e('remove existing taxonomies, add new taxonomies', 'wp-all-import-pro');
                                                            break;
                                                        case 'add_new':
                                                            _e('only add new', 'wp-all-import-pro');
                                                            break;
                                                        case 'only':
                                                            printf(__('update only these taxonomies: %s , leave the rest alone', 'wp-all-import-pro'), $post['taxonomies_only_list']);
                                                            break;
                                                        case 'all_except':
                                                            printf(__('leave these taxonomies: %s alone, update all others', 'wp-all-import-pro'), $post['taxonomies_except_list']);
                                                            break;
                                                    }

                                                    if(!empty($post['do_not_create_terms']))
                                                        _e(' - no new terms will be created', 'wp-all-import-pro');
                                                    ?>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <?php do_action('pmxi_confirm_data_to_import', $isWizard, $post);?>
                                </div>
                            <?php } ?>

                        <?php endif; ?>

                        <?php if ( $post['create_new_records']): ?>
                        <p><?php printf(__('New %ss will be created from records that don\'t match the above criteria.', 'wp-all-import-pro'), $custom_type->labels->singular_name); ?></p>
                        <?php endif; ?>

						<!-- Import Performance -->
                        <p><?php printf(__('Piece By Piece Processing enabled. %s records will be processed each iteration. If it takes longer than your server\'s max_execution_time to process %s records, your import will fail.', 'wp-all-import-pro'), $post['records_per_request'], $post['records_per_request']); ?></p>

                        <p><?php printf(__('This import file will be split into %s records chunks before processing.', 'wp-all-import-pro'), PMXI_Plugin::getInstance()->getOption('large_feed_limit')); ?></p>

						<?php if ($post['is_fast_mode']):?>
						<p><?php _e('do_action calls will be disabled in wp_insert_post and wp_insert_attachment during the import.', 'wp-all-import-pro'); ?></p>
						<?php endif; ?>

					</div>
				</div>
			</div>

			<?php endif; ?>

			</td>
		</tr>
	</table>
	<?php if ( isset($import_type) && $import_type !== 'upload' ): ?>
    <div style="color: #425F9A; font-size: 14px; font-weight: bold; margin: 0 0 15px; line-height: 25px; text-align: center;">
        <div id="no-subscription" style="display: none;">
            <?php _e("Looks like you're trying out Automatic Scheduling!", 'wp-all-import-pro');?><br/>
            <?php _e("Your Automatic Scheduling settings won't be saved without a subscription.", 'wp-all-import-pro');?>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($is_new_import):?>
	<form id="wpai-submit-confirm-form" class="confirm <?php echo ! $isWizard ? 'edit' : '' ?>" method="post">
		<?php wp_nonce_field('confirm', '_wpnonce_confirm') ?>
		<input type="hidden" name="is_confirmed" value="1" />
        <input type="submit" class="rad10" value="<?php _e('Confirm & Run Import', 'wp-all-import-pro') ?>" />
		<p>
		<?php if ($isWizard): ?>
			<a href="<?php echo apply_filters('pmxi_options_back_link', esc_url(add_query_arg('action', 'options', $this->baseUrl)), $isWizard); ?>"><?php _e('or go back to Step 4', 'wp-all-import-pro') ?></a>
		<?php else:?>
			<a href="<?php echo apply_filters('pmxi_options_back_link', esc_url(remove_query_arg('id', remove_query_arg('action', $this->baseUrl))), $isWizard); ?>"><?php _e('or go back to Manage Imports', 'wp-all-import-pro') ?></a>
		<?php endif; ?>
		</p>
	</form>
	<?php endif; ?>
	<a href="http://soflyy.com/" target="_blank" class="wpallimport-created-by"><?php _e('Created by', 'wp-all-import-pro'); ?> <span></span></a>

</div>
