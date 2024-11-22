<div class="wpallimport-collapsed closed wpallimport-section wpallimport-featured-images">
	<div class="wpallimport-content-section" style="padding-bottom: 0;">
		<div class="wpallimport-collapsed-header" style="margin-bottom: 15px;">
			<h3><?php echo $section_title;?></h3>	
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<input type="button" rel="images_hints" value="<?php _e('Show hints', 'wp-all-import-pro');?>" class="show_hints">
				<table class="form-table" style="max-width:none;">
					<tr>
						<td colspan="3">
							<div class="input">
								<div class="input">							
									<input type="radio" name="<?php echo $section_slug; ?>download_images" value="yes" class="switcher" id="<?php echo $section_slug; ?>download_images_yes" <?php echo ("yes" == $post[$section_slug . 'download_images']) ? 'checked="checked"' : '';?>/>
									<label for="<?php echo $section_slug; ?>download_images_yes"><?php _e('Download images hosted elsewhere', 'wp-all-import-pro'); ?></label>
									<a href="#help" class="wpallimport-help" title="<?php _e('http:// or https://', 'wp-all-import-pro') ?>" style="position: relative; top: -2px;">?</a>
								</div>						
								<div class="switcher-target-<?php echo $section_slug; ?>download_images_yes" style="padding-left:27px;">
									<label for="<?php echo $section_slug; ?>download_featured_delim"><?php _e('Enter image URL one per line, or separate them with a ', 'wp-all-import-pro');?></label>
									<input type="text" class="small" id="<?php echo $section_slug; ?>download_featured_delim" name="<?php echo $section_slug; ?>download_featured_delim" value="<?php echo esc_attr($post[$section_slug . 'download_featured_delim']) ?>" style="width:5%; text-align:center;"/>
									<textarea name="<?php echo $section_slug; ?>download_featured_image" class="newline rad4" style="clear: both; display:block;" placeholder=""><?php echo esc_attr($post[$section_slug . 'download_featured_image']) ?></textarea>
								</div>
								<div class="input">							
									<input type="radio" name="<?php echo $section_slug; ?>download_images" value="gallery" class="switcher" id="<?php echo $section_slug; ?>download_images_gallery" <?php echo ("gallery" == $post[$section_slug . 'download_images']) ? 'checked="checked"' : '';?>/>
									<label for="<?php echo $section_slug; ?>download_images_gallery"><?php _e('Use images currently in Media Library', 'wp-all-import-pro'); ?></label>
									<!--a href="#help" class="wpallimport-help" title="<?php _e('http:// or https://', 'wp-all-import-pro') ?>" style="position: relative; top: -2px;">?</a-->
								</div>						
								<div class="switcher-target-<?php echo $section_slug; ?>download_images_gallery" style="padding-left:27px;">
									<label for="<?php echo $section_slug; ?>gallery_featured_delim"><?php _e('Enter image filenames one per line, or separate them with a ', 'wp-all-import-pro');?></label>
									<input type="text" class="small" id="<?php echo $section_slug; ?>gallery_featured_delim" name="<?php echo $section_slug; ?>gallery_featured_delim" value="<?php echo esc_attr($post[$section_slug . 'gallery_featured_delim']) ?>" style="width:5%; text-align:center;"/>
									<textarea name="<?php echo $section_slug; ?>gallery_featured_image" class="newline rad4" style="clear: both; display:block; "><?php echo esc_attr($post[$section_slug . 'gallery_featured_image']) ?></textarea>
								</div>
								<div class="input">
									<?php $wp_uploads = wp_upload_dir(); ?>																					
									<input type="radio" name="<?php echo $section_slug; ?>download_images" value="no" class="switcher" id="<?php echo $section_slug; ?>download_images_no" <?php echo ("no" == $post[$section_slug . 'download_images']) ? 'checked="checked"' : '';?>/>
									<label for="<?php echo $section_slug; ?>download_images_no"><?php printf(__('Use images currently uploaded in %s', 'wp-all-import-pro'), preg_replace('%.*wp-content/%', 'wp-content/', $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::FILES_DIRECTORY . DIRECTORY_SEPARATOR) ); ?></label>
								</div>
								<div class="switcher-target-<?php echo $section_slug; ?>download_images_no" style="padding-left:27px;">
									<label for="<?php echo $section_slug; ?>featured_delim"><?php _e('Enter image filenames one per line, or separate them with a ', 'wp-all-import-pro');?></label>
									<input type="text" class="small" id="<?php echo $section_slug; ?>featured_delim" name="<?php echo $section_slug; ?>featured_delim" value="<?php echo esc_attr($post[$section_slug . 'featured_delim']) ?>" style="width:5%; text-align:center;"/>
									<textarea name="<?php echo $section_slug; ?>featured_image" class="newline rad4" style="clear: both; display:block; "><?php echo esc_attr($post[$section_slug . 'featured_image']) ?></textarea>
								</div>																
							</div>
							<h4><?php _e('Image Options', 'wp-all-import-pro'); ?></h4>
							<div class="search_through_the_media_library">
								<div class="input" style="margin:3px;">
									<input type="hidden" name="<?php echo $section_slug; ?>search_existing_images" value="0" />
									<input type="checkbox" id="<?php echo $section_slug; ?>search_existing_images" name="<?php echo $section_slug; ?>search_existing_images" value="1" <?php echo $post[$section_slug . 'search_existing_images'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
									<label for="<?php echo $section_slug; ?>search_existing_images"><?php _e('Search through the Media Library for existing images before importing new images','wp-all-import-pro');?> </label>						
									<a href="#help" class="wpallimport-help" title="<?php _e('If an image with the same file name or remote URL is found in the Media Library then that image will be attached to this record instead of importing a new image. Disable this setting if you always want to download a new image.', 'wp-all-import-pro') ?>" style="position: relative; top: -2px;">?</a>
									<div class="switcher-target-<?php echo $section_slug; ?>search_existing_images" style="padding-left:23px;">
                                        <div class="search_through_the_media_library_logic">
                                            <div class="input">
                                                <input type="radio" id="<?php echo $section_slug; ?>search_existing_images_logic_url" name="<?php echo $section_slug; ?>search_existing_images_logic" value="by_url" <?php echo ( "by_url" == $post[$section_slug . 'search_existing_images_logic'] ) ? 'checked="checked"': '' ?>/>
                                                <label for="<?php echo $section_slug; ?>search_existing_images_logic_url"><?php _e('Match image by URL', 'wp-all-import-pro') ?></label>
                                            </div>
                                            <div class="input">
                                                <input type="radio" id="<?php echo $section_slug; ?>search_existing_images_logic_filename" name="<?php echo $section_slug; ?>search_existing_images_logic" value="by_filename" <?php echo ( "by_filename" == $post[$section_slug . 'search_existing_images_logic'] ) ? 'checked="checked"': '' ?>/>
                                                <label for="<?php echo $section_slug; ?>search_existing_images_logic_filename"><?php _e('Match image by filename', 'wp-all-import-pro') ?></label>
                                            </div>
                                        </div>
									</div>
								</div>							
								<div class="input" style="margin: 3px;">
									<input type="hidden" name="<?php echo $section_slug; ?>do_not_remove_images" value="0" />
									<input type="checkbox" id="<?php echo $section_slug; ?>do_not_remove_images" name="<?php echo $section_slug; ?>do_not_remove_images" value="1" <?php echo $post[$section_slug . 'do_not_remove_images'] ? 'checked="checked"': '' ?> />
									<label for="<?php echo $section_slug; ?>do_not_remove_images"><?php _e('Keep images currently in Media Library', 'wp-all-import-pro') ?></label>
									<a href="#help" class="wpallimport-help" title="<?php _e('If disabled, images attached to imported posts will be deleted and then all images will be imported.', 'wp-all-import-pro') ?>" style="position:relative; top: -2px;">?</a>
								</div>
								<?php if ($section_type == 'images'): ?>
								<div class="input" style="margin: 3px;">
									<input type="hidden" name="<?php echo $section_slug; ?>import_img_tags" value="0" />
									<input type="checkbox" id="<?php echo $section_slug; ?>import_img_tags" name="<?php echo $section_slug; ?>import_img_tags" value="1" <?php echo (isset($post[$section_slug . 'import_img_tags']) && $post[$section_slug . 'import_img_tags']) ? 'checked="checked"': '' ?> />
									<label for="<?php echo $section_slug; ?>import_img_tags"><?php _e('Scan through post content and import images wrapped in &lt;img&gt; tags', 'wp-all-import-pro') ?></label>
									<a href="#help" class="wpallimport-help" title="<?php _e('Only images hosted on other sites will be imported. Images will be imported to WordPress and the &lt;img&gt tag updated with the new image URL.', 'wp-all-import-pro') ?>" style="position:relative; top: -2px;">?</a>
								</div>
								<?php endif; ?>
							</div>
							<?php if ($section_type == 'images'): ?>
							<div class="input">
								<input type="hidden" value="<?php echo $section_slug; ?>" class="wp_all_import_section_slug"/>
								<a class="preview_images" href="javascript:void(0);" rel="preview_images"><?php _e('Preview & Test', 'wp-all-import-pro'); ?></a>
							</div>																					
							<div class="input" style="margin:3px;">
								<input type="hidden" name="<?php echo $section_slug; ?>is_featured" value="0" />
								<input type="checkbox" id="<?php echo $section_slug; ?>is_featured" name="<?php echo $section_slug; ?>is_featured" value="1" <?php echo $post[$section_slug . 'is_featured'] ? 'checked="checked"' : '' ?> class="fix_checkbox"/>
								<label for="<?php echo $section_slug; ?>is_featured"><?php _e('Set the first image to the Featured Image (_thumbnail_id)','wp-all-import-pro');?> </label>						
							</div>																					
							<div class="input" style="margin:3px;">
								<input type="hidden" name="<?php echo $section_slug; ?>create_draft" value="no" />
								<input type="checkbox" id="<?php echo $section_slug; ?>create_draft" name="<?php echo $section_slug; ?>create_draft" value="yes" <?php echo 'yes' == $post[$section_slug . 'create_draft'] ? 'checked="checked"' : '' ?> class="fix_checkbox"/>
								<label for="<?php echo $section_slug; ?>create_draft"><?php _e('If no images are downloaded successfully, create entry as Draft.', 'wp-all-import-pro') ?></label>
							</div>
                                <div class="input" style="margin:3px;">
                                    <input type="hidden" name="<?php echo $section_slug; ?>allow_delay_image_resize" value="0" />
                                    <input type="checkbox" id="<?php echo $section_slug; ?>allow_delay_image_resize" name="<?php echo $section_slug; ?>allow_delay_image_resize" value="1" <?php echo $post[$section_slug . 'allow_delay_image_resize'] ? 'checked="checked"' : '' ?> class="fix_checkbox"/>
                                    <label for="<?php echo $section_slug; ?>allow_delay_image_resize"><?php _e('Do not generate image metadata or additional image sizes during import.', 'wp-all-import-pro') ?></label>
                                    <a href="#help" class="wpallimport-help" title="<?php _e('When enabled, WP All Import will schedule the generation of image metadata and additional image sizes using WP Cron after the import is completed. This should increase import speed when many images are configured for import. However, there will be a delay before the images include all of their configured data. Such delay may temporarily impact how they\'re viewed on the site until the WP Cron jobs have all completed.', 'wp_all_import_plugin') ?>" style="position:relative; top: -2px;">?</a>
                                </div>
                            <?php if(\extension_loaded('curl') && 'product' === $post['custom_type']):?>
                                <div class="input" style="margin:3px;">
                                    <input type="hidden" name="<?php echo $section_slug; ?>preload_images" value="no" />
                                    <input type="checkbox" id="<?php echo $section_slug; ?>preload_images" name="<?php echo $section_slug; ?>preload_images" value="yes" <?php echo 'yes' == $post[$section_slug . 'preload_images'] ? 'checked="checked"' : '' ?> class="fix_checkbox"/>
                                    <label for="<?php echo $section_slug; ?>preload_images"><?php _e('Attempt to preload images', 'wp_all_import_plugin') ?></label>
                                    <a href="#help" class="wpallimport-help" title="<?php _e('When enabled, all images for each product will attempt to download simultaneously to minimize overall download time. Use only when importing multiple images that are slow to download, as small, fast-downloading images may take longer with this option.', 'wp_all_import_plugin') ?>" style="position:relative; top: -2px;">?</a>
                                </div>
                            <?php endif; ?>
							<?php endif; ?>																						
						</td>
					</tr>
				</table>
			</div>

			<div class="wpallimport-collapsed closed wpallimport-section">
				<div class="wpallimport-content-section rad0" style="margin:0; border-top:1px solid #ddd; border-bottom: none; border-right: none; border-left: none; background: #f1f2f2;">
					<div class="wpallimport-collapsed-header">
						<h3 style="color:#40acad;"><?php _e('SEO & Advanced Options','wp-all-import-pro');?></h3>	
					</div>
					<div class="wpallimport-collapsed-content" style="padding: 0;">
						<div class="wpallimport-collapsed-content-inner">
							<hr>						
							<table class="form-table" style="max-width:none;">
								<tr>
									<td colspan="3">
										<h4><?php _e('Meta Data', 'wp-all-import-pro'); ?></h4>
										<div class="input">
											<input type="hidden" name="<?php echo $section_slug; ?>set_image_meta_title" value="0" />
											<input type="checkbox" id="<?php echo $section_slug; ?>set_image_meta_title" name="<?php echo $section_slug; ?>set_image_meta_title" value="1" <?php echo $post[$section_slug . 'set_image_meta_title'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
											<label for="<?php echo $section_slug; ?>set_image_meta_title"><?php _e('Set Title(s)','wp-all-import-pro');?></label>
											<div class="switcher-target-<?php echo $section_slug; ?>set_image_meta_title" style="padding-left:23px;">							
												<label for="<?php echo $section_slug; ?>image_meta_title_delim"><?php _e('Enter one per line, or separate them with a ', 'wp-all-import-pro');?></label>
												<input type="text" class="small" id="<?php echo $section_slug; ?>image_meta_title_delim" name="<?php echo $section_slug; ?>image_meta_title_delim" value="<?php echo esc_attr($post[$section_slug . 'image_meta_title_delim']) ?>" style="width:5%; text-align:center;"/>
												<p style="margin-bottom:5px;"><?php _e('The first title will be linked to the first image, the second title will be linked to the second image, ...', 'wp-all-import-pro');?></p>
												<textarea name="<?php echo $section_slug; ?>image_meta_title" class="newline rad4"><?php echo esc_attr($post[$section_slug . 'image_meta_title']) ?></textarea>																				
											</div>
										</div>
										<div class="input">
											<input type="hidden" name="<?php echo $section_slug; ?>set_image_meta_caption" value="0" />
											<input type="checkbox" id="<?php echo $section_slug; ?>set_image_meta_caption" name="<?php echo $section_slug; ?>set_image_meta_caption" value="1" <?php echo $post[$section_slug . 'set_image_meta_caption'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
											<label for="<?php echo $section_slug; ?>set_image_meta_caption"><?php _e('Set Caption(s)','wp-all-import-pro');?></label>
											<div class="switcher-target-<?php echo $section_slug; ?>set_image_meta_caption" style="padding-left:23px;">							
												<label for="<?php echo $section_slug; ?>image_meta_caption_delim"><?php _e('Enter one per line, or separate them with a ', 'wp-all-import-pro');?></label>
												<input type="text" class="small" id="<?php echo $section_slug; ?>image_meta_caption_delim" name="<?php echo $section_slug; ?>image_meta_caption_delim" value="<?php echo esc_attr($post[$section_slug . 'image_meta_caption_delim']) ?>" style="width:5%; text-align:center;"/>
												<p style="margin-bottom:5px;"><?php _e('The first caption will be linked to the first image, the second caption will be linked to the second image, ...', 'wp-all-import-pro');?></p>
												<textarea name="<?php echo $section_slug; ?>image_meta_caption" class="newline rad4"><?php echo esc_attr($post[$section_slug . 'image_meta_caption']) ?></textarea>																				
											</div>
										</div>
										<div class="input">
											<input type="hidden" name="<?php echo $section_slug; ?>set_image_meta_alt" value="0" />
											<input type="checkbox" id="<?php echo $section_slug; ?>set_image_meta_alt" name="<?php echo $section_slug; ?>set_image_meta_alt" value="1" <?php echo $post[$section_slug . 'set_image_meta_alt'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
											<label for="<?php echo $section_slug; ?>set_image_meta_alt"><?php _e('Set Alt Text(s)','wp-all-import-pro');?></label>
											<div class="switcher-target-<?php echo $section_slug; ?>set_image_meta_alt" style="padding-left:23px;">							
												<label for="<?php echo $section_slug; ?>image_meta_alt_delim"><?php _e('Enter one per line, or separate them with a ', 'wp-all-import-pro');?></label>
												<input type="text" class="small" id="<?php echo $section_slug; ?>image_meta_alt_delim" name="<?php echo $section_slug; ?>image_meta_alt_delim" value="<?php echo esc_attr($post[$section_slug . 'image_meta_alt_delim']) ?>" style="width:5%; text-align:center;"/>
												<p style="margin-bottom:5px;"><?php _e('The first alt text will be linked to the first image, the second alt text will be linked to the second image, ...', 'wp-all-import-pro');?></p>
												<textarea name="<?php echo $section_slug; ?>image_meta_alt" class="newline rad4"><?php echo esc_attr($post[$section_slug . 'image_meta_alt']) ?></textarea>
											</div>
										</div>
										<div class="input">
											<input type="hidden" name="<?php echo $section_slug; ?>set_image_meta_description" value="0" />
											<input type="checkbox" id="<?php echo $section_slug; ?>set_image_meta_description" name="<?php echo $section_slug; ?>set_image_meta_description" value="1" <?php echo $post[$section_slug . 'set_image_meta_description'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
											<label for="<?php echo $section_slug; ?>set_image_meta_description"><?php _e('Set Description(s)','wp-all-import-pro');?></label>
											<div class="switcher-target-<?php echo $section_slug; ?>set_image_meta_description" style="padding-left:23px;">	
												<div class="input">
													<input id="<?php echo $section_slug; ?>image_meta_description_delim_logic_separate" type="radio" name="<?php echo $section_slug; ?>image_meta_description_delim_logic" value="separate" <?php echo ($post[$section_slug . 'image_meta_description_delim_logic'] == 'separate' and ! empty($post[$section_slug . 'image_meta_description_delim'])) ? 'checked="checked"' : ''; ?>/>
													<label for="<?php echo $section_slug; ?>image_meta_description_delim_logic_separate"><?php _e('Separate them with a', 'wp-all-import-pro'); ?></label>
													<input type="text" class="small" id="<?php echo $section_slug; ?>image_meta_description_delim" name="<?php echo $section_slug; ?>image_meta_description_delim" value="<?php echo esc_attr($post[$section_slug . 'image_meta_description_delim']) ?>" style="width:5%; text-align:center;"/>													
												</div>
												<div class="input">
													<input id="<?php echo $section_slug; ?>image_meta_description_delim_logic_line" type="radio" name="<?php echo $section_slug; ?>image_meta_description_delim_logic" value="line" <?php echo ($post[$section_slug . 'image_meta_description_delim_logic'] == 'line' or empty($post[$section_slug . 'image_meta_description_delim'])) ? 'checked="checked"' : ''; ?>/>
													<label for="<?php echo $section_slug; ?>image_meta_description_delim_logic_line"><?php _e('Enter them one per line', 'wp-all-import-pro'); ?></label>
												</div>												
												<p style="margin-bottom:5px;"><?php _e('The first description will be linked to the first image, the second description will be linked to the second image, ...', 'wp-all-import-pro');?></p>
												<textarea name="<?php echo $section_slug; ?>image_meta_description" class="newline rad4"><?php echo esc_attr($post[$section_slug . 'image_meta_description']) ?></textarea>																				
											</div>
										</div>										
										<h4><?php _e('Files', 'wp-all-import-pro'); ?></h4>
										<div class="advanced_options_files">
											<p style="font-style:italic; display:none;"><?php _e('These options not available if Use images currently in Media Library is selected above.', 'wp-all-import-pro'); ?></p>
											<div class="input" style="margin:3px 0px;">
												<input type="hidden" name="<?php echo $section_slug; ?>auto_rename_images" value="0" />
												<input type="checkbox" id="<?php echo $section_slug; ?>auto_rename_images" name="<?php echo $section_slug; ?>auto_rename_images" value="1" <?php echo $post[$section_slug . 'auto_rename_images'] ? 'checked="checked"' : ''; ?> class="switcher fix_checkbox"/>
												<label for="<?php echo $section_slug; ?>auto_rename_images"><?php _e('Change image file names to','wp-all-import-pro');?> </label>
												<div class="input switcher-target-<?php echo $section_slug; ?>auto_rename_images" style="padding-left:23px;">
													<input type="text" id="<?php echo $section_slug; ?>auto_rename_images_suffix" name="<?php echo $section_slug; ?>auto_rename_images_suffix" value="<?php echo esc_attr($post[$section_slug . 'auto_rename_images_suffix']) ?>" style="width:480px;"/> 
													<p class="note"><?php _e('Multiple image will have numbers appended, i.e. image-name-1.jpg, image-name-2.jpg ', 'wp-all-import-pro'); ?></p>
												</div>
											</div>
											<div class="input" style="margin:3px 0px;">
												<input type="hidden" name="<?php echo $section_slug; ?>auto_set_extension" value="0" />
												<input type="checkbox" id="<?php echo $section_slug; ?>auto_set_extension" name="<?php echo $section_slug; ?>auto_set_extension" value="1" <?php echo $post[$section_slug . 'auto_set_extension'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
												<label for="<?php echo $section_slug; ?>auto_set_extension"><?php _e('Change image file extensions','wp-all-import-pro');?> </label>
												<div class="input switcher-target-<?php echo $section_slug; ?>auto_set_extension" style="padding-left:23px;">
													<input type="text" id="<?php echo $section_slug; ?>new_extension" name="<?php echo $section_slug; ?>new_extension" value="<?php echo esc_attr($post[$section_slug . 'new_extension']) ?>" placeholder="jpg" style="width:480px;"/>
												</div>
											</div>											
										</div>										
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</div>		
		</div>
	</div>
</div>
<div id="images_hints" style="display:none;">	
	<ul>
		<li><?php _e('WP All Import will automatically ignore elements with blank image URLs/filenames.', 'wp-all-import-pro'); ?></li>
		<li><?php _e('WP All Import must download the images to your server. You can\'t have images in a Gallery that are referenced by external URL. That\'s just how WordPress works.', 'wp-all-import-pro'); ?></li>
		<li><?php printf(__('Importing a variable number of images can be done using a <a href="%s" target="_blank">FOREACH LOOP</a>', 'wp-all-import-pro'), 'https://www.wpallimport.com/documentation/developers/custom-code/foreach-loops/'); ?></li>
		<li><?php printf(__('For more information check out our <a href="%s" target="_blank">comprehensive documentation</a>', 'wp-all-import-pro'), 'https://www.wpallimport.com/documentation/'); ?></li>
	</ul>
</div>
