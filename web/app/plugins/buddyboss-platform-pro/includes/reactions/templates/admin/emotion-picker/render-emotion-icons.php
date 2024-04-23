<?php
/**
 * This template will use for emotion selection.
 *
 * @since 2.4.50
 *
 * @package BuddyBossPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$bb_platform_pro = bb_platform_pro();

?>
<div id="bbpro_emotion_modal" class="supports-drag-drop" style="display: none; position: relative;">
	<div tabindex="0" class="media-modal wp-core-ui">
		<button type="button" id="bbpro_icon_modal_close" class="media-modal-close">
			<span class="media-modal-icon">
				<span class="screen-reader-text"><?php esc_html_e( 'Close', 'buddyboss-pro' ); ?></span>
			</span>
		</button>
		<div class="media-modal-content">
			<div class="bbpro-modal-box__header">
				<h3 data-alternate-title="<?php esc_html_e( 'Icons', 'buddyboss-pro' ); ?>">
					<?php esc_html_e( 'Emotion Editor', 'buddyboss-pro' ); ?>
				</h3>
			</div>
			<div class="bbpro-modal-box__body">
				<div id="tab-1" class="tab-content bbpro-tab-1-content current">
					<div id="bbpro-icon-left-section">
						<div class="bbpro-dialog-icon-picker" data-id="">
							<div class="bbpro-icon-filters">
								<label class="bbpro-icon-legacy-filter bbpro-icon-filters-cmn">
									<select class="bbpro-emotion-type-select-filter" data-section="bbpro-emojis" >
										<?php
										$emotion_types = bb_reactions_icon_types();
										foreach ( $emotion_types as $emotion_type ) {
											echo '<option value="' . esc_attr( $emotion_type['value'] ) . '">' . esc_html( $emotion_type['label'] ) . '</option>';
										}
										?>
									</select>
								</label>
								<div class="bbpro-icon-category-filter bbpro-icon-filters-cmn">
									<?php $this->render_emoji_category_list(); ?>
								</div>
								<div class="bbpro-icon-search bbpro-icon-filters-cmn">
									<i class="bb-icon-i bb-icon-search"></i>
									<input class="bbpro-icon-search-input medium-text" type="text" name="s" placeholder="<?php echo esc_attr__( 'Search Icons', 'buddyboss-pro' ); ?>" value="">
								</div>
							</div>
							<?php
							require bb_reaction_path( 'templates/admin/emotion-picker/bb-icons.php' );
							require bb_reaction_path( 'templates/admin/emotion-picker/bb-emojis.php' );
							require bb_reaction_path( 'templates/admin/emotion-picker/bb-custom.php' );
							?>
						</div>
					</div>
					<div id="bbpro-icon-right-section">
						<?php
						require bb_reaction_path( 'templates/admin/emotion-picker/bb-icons-settings.php' );
						require bb_reaction_path( 'templates/admin/emotion-picker/bb-emojis-settings.php' );
						require bb_reaction_path( 'templates/admin/emotion-picker/bb-custom-settings.php' );
						?>
					</div>
				</div>
			</div>
			<div class="bbpro-modal-box__footer">
				<div class="icon-action">
					<div class="bbpro_loading icon-loader" style="display: none;"></div>
					<input
						type="submit"
						id="icon-picker-saved"
						name="save_icon_picker"
						class="button-primary bbpro_select_icon"
						value="<?php echo esc_html__( 'Save Emotion', 'buddyboss-pro' ); ?>"
						data-alternate-title="<?php esc_html_e( 'Select Icon', 'buddyboss-pro' ); ?>"
					/>
				</div>
			</div>
		</div>
	</div>
	<div class="media-modal-backdrop"></div>
</div>

<script type="text/html" id="tmpl-bb-pro-add-new-emotion-placeholder">
	<div class="bb_emotions_item bb_emotions_item_action">
		<button class="bb_emotions_add_new" aria-label="<?php esc_attr_e( 'Add New Emotion', 'buddyboss-pro' ); ?>" data-bp-tooltip="<?php esc_attr_e( 'Add new', 'buddyboss-pro' ); ?>" data-bp-tooltip-pos="<?php esc_attr_e( 'up', 'buddyboss-pro' ); ?>">
			<i class="bb-icon-f bb-icon-plus"></i>
		</button>
	</div>
</script>

<script type="text/html" id="tmpl-bb-pro-add-emotion">
	<div class="bb_emotions_item ui-sortable-handle" data-reaction-id="{{data.id}}">
		<div class="bb_emotions_actions">
			<div class="bb_emotions_actions_enable">
				<input type="checkbox" name="reaction_checks[{{data.id}}]" {{ data.isActive ? 'checked' : '' }} value="1">
			</div>
			<button class="bb_emotions_actions_remove" aria-label="<?php esc_attr_e( 'Remove Emotion', 'buddyboss-pro' ); ?>">
				<i class="bb-icon-l bb-icon-times"></i>
			</button>
		</div>

		<div class="bb_emotions_icon">
			<#
				let reactionType = data.type;
				if ( reactionType === 'custom' ) {
					#>
					<img src="{{data.icon_path}}" alt="" />
					<#
				} else if ( reactionType === 'emotions' ) {
					#>
					<span class="bbpro-icon-emoji">
						<#
						if ( 'undefined' !== typeof data.icon_path ) {
						#>
							<img src="{{data.icon_path}}" alt="" />
						<#
						} else {
						#>
							{{data.icon}}
						<#
						}
						#>
					</span>
					<#
				} else if ( reactionType === 'bb-icons' ) {
					#>
					<i class="bb-icon-rf bb-icon-{{data.icon}}" style="color:{{data.icon_color}}"></i>
					<#
				}
			#>
		</div>

		<div class="bb_emotions_footer">
			<span style="color:{{data.text_color}}">{{data.icon_text ? data.icon_text : data.name}}</span>
			<button
				class="bb_emotions_edit"
				aria-label="<?php esc_attr_e( 'Edit Emotion', 'buddyboss-pro' ); ?>"
				data-icon="{{JSON.stringify(data)}}"
				data-type="{{data.type}}">
				<i class="bb-icon-l bb-icon-pencil"></i>
			</button>
		</div>
		<input type="hidden" class="bb_admin_setting_reaction_item" name="reaction_items[{{data.id}}]" value="{{JSON.stringify(data)}}">
	</div>
</script>

<script type="text/html" id="tmpl-buddyboss-icons-category-list">
	<?php
	BB_Reactions_Picker::instance()->render_bb_icons_category_dropdown();
	?>
</script>

<script type="text/html" id="tmpl-buddyboss-emojis-category-list">
	<?php
	BB_Reactions_Picker::instance()->render_emoji_category_list();
	?>
</script>

<script type="text/html" id="tmpl-buddyboss-no-results">
	<div class="no-results bb-pro-no-results-screen">
		<i class="bb-icon bb-icon-f bb-icon-emoticon-frown"></i>
		<h3><?php esc_html_e( 'No results found', 'buddyboss-pro' ); ?></h3>
		<p> <?php esc_html_e( 'No, matching results found, Try again', 'buddyboss-pro' ); ?> </p>
	</div>
</script>
