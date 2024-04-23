<?php
/**
 * BP Nouveau Group's edit courses template.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/edit-courses.php.
 *
 * @since   2.4.40
 *
 * @package BuddyBoss\TutorLMS
 *
 * @version 1.0.0
 */

$action   = isset( $args['action'] ) ? $args['action'] : '';
$group_id = isset( $args['group_id'] ) ? $args['group_id'] : 0;
if ( empty( $group_id ) ) {
	return;
}

$course_activities          = bb_tutorlms_get_group_course_activities( $group_id );
$global_course_activities   = bb_get_enabled_tutorlms_course_activities();
$tutorlms_course_activities = ! empty( $global_course_activities ) ? bb_tutorlms_course_activities( $global_course_activities ) : array();
$bb_tutorlms_groups         = groups_get_groupmeta( $group_id, 'bb-tutorlms-group' );

// Get group course.
$args = array(
	'group_id' => $group_id,
	'fields'   => 'course_id',
);
if ( 'edit' === $action ) {
	$args['per_page'] = false;
}
$bb_tutorlms_groups = bb_load_tutorlms_group()->get( $args );
$courses            = ! empty( $bb_tutorlms_groups['courses'] ) ? $bb_tutorlms_groups['courses'] : array();
$is_course_enable   = bb_tutorlms_group_courses_is_enable( $group_id );
$hide_select_course = ! $is_course_enable ? 'bb-hide' : '';
?>

<div class="bb-group-tutorlms-settings-container">
	<div class="bb-course-instruction">
		<?php
		if ( 'create' === $action ) {
			$not_access = ! bb_tutorlms_manage_tab();
			?>
			<h4 class="bb-section-title">
				<?php esc_html_e( 'Course Tab', 'buddyboss-pro' ); ?>
			</h4>
			<p class="bb-section-info">
				<?php _e( 'As an Instructor you can add a courses tab to your group and link associated courses within the tab. <br> You can then select what activity posts should automatically be added to the activity feed.', 'buddyboss-pro' ); ?>
			</p>
			<p class="checkbox bp-checkbox-wrap bb-tutorlms-group-option-enable <?php echo $not_access ? esc_attr( 'bb_tutorlms_not_allow' ) : ''; ?>">
				<input type="checkbox" name="bb-tutorlms-group[bb-tutorlms-group-course-is-enable]" id="bb-tutorlms-group-course-is-enable" class="bs-styled-checkbox" value="1" <?php checked( $is_course_enable ); disabled( $not_access ) ; ?>/>
				<label for="bb-tutorlms-group-course-is-enable">
					<?php esc_html_e( 'Yes, I want to add a group tab', 'buddyboss-pro' ); ?>
				</label>
			</p>
			<?php
		} else {
			?>
			<p class="bb-section-info">
				<?php esc_html_e( 'Add a course tab to your group and select which courses you would like to show under the course tab.', 'buddyboss-pro' ); ?>
			</p>
			<div class="field-group">
				<p class="checkbox bp-checkbox-wrap bb-tutorlms-group-option-enable">
					<input type="checkbox" name="bb-tutorlms-group[bb-tutorlms-group-course-is-enable]" id="bb-tutorlms-group-course-is-enable" class="bs-styled-checkbox" value="1" <?php checked( $is_course_enable ); ?> />
					<label for="bb-tutorlms-group-course-is-enable">
						<span><?php esc_html_e( 'Yes, I want to add courses to this group', 'buddyboss-pro' ); ?></span>
					</label>
				</p>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	if ( bb_tutorlms_manage_tab() ) {
		?>
		<div class="bb-course-activity-selection <?php echo esc_attr( $hide_select_course ); ?>">
			<?php
			if ( ! empty( $tutorlms_course_activities ) ) {
				?>
				<fieldset>
					<h3><?php echo __( 'Select Course Activities', 'buddyboss-pro' ); ?></h3>
					<p class="bb-section-info">
						<?php esc_html_e( 'Which activities should be displayed in this group?', 'buddyboss-pro' ); ?>
					</p>
					<div class="bb-group-tutorlms-settings-activities">
						<?php
						foreach ( $tutorlms_course_activities as $key => $value ) {
							$checked = isset( $course_activities[ $key ] ) ? $course_activities[ $key ] : '0';
							?>
							<div class="field-group bp-checkbox-wrap">
								<p class="checkbox bp-checkbox-wrap bp-group-option-enable">
									<input type="checkbox" name="bb-tutorlms-group[course-activity][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>" class="bs-styled-checkbox" value="1" <?php checked( $checked, '1' ); ?>/>
									<label for="<?php echo esc_attr( $key ); ?>">
										<span><?php echo esc_html( $value ); ?></span>
									</label>
								</p>
							</div>
							<?php
						}
						?>
					</div>
				</fieldset>
				<?php
			}
			?>
			<fieldset>
				<h3><?php echo __( 'Select Courses', 'buddyboss-pro' ); ?></h3>
				<p class="bb-section-info">
					<?php esc_html_e( 'Choose your courses you would like to associate with this group.', 'buddyboss-pro' ); ?>
				</p>
				<select name="bb-tutorlms-group[courses][]" class="bb_tutorlms_select2" multiple="multiple">
					<?php
					if ( ! empty( $courses ) ) {
						foreach ( $courses as $course_id ) {
							$course_id = (int) $course_id;
							$title     = html_entity_decode( get_the_title( $course_id ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
							?>
							<option value="<?php echo esc_attr( $course_id ); ?>" <?php selected( $course_id, $course_id ); ?>>
								<?php echo esc_html( $title ); ?>
							</option>
							<?php
						}
					} else {
						?>
						<option value=""><?php _e( 'Start typing a course name to associate with this group.', 'buddyboss-pro' ); ?></option>
						<?php
					}
					?>
				</select>
			</fieldset>
			<input type="hidden" id="bp-tutorlms-group-id" value="<?php echo esc_attr( $group_id ); ?>"/>
			<?php
			wp_nonce_field( 'groups_' . $action . '_save_tutorlms', 'tutorlms_group_admin_ui' );
			?>
		</div>
		<?php
	}
	?>
</div>
