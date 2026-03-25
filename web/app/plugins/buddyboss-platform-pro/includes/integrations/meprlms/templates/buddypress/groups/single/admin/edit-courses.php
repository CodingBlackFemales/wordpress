<?php
/**
 * BP Nouveau Group's edit courses template.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/edit-courses.php.
 *
 * @since   2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 *
 * @version 1.0.0
 */

$action   = $args['action'] ?? ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$group_id = $args['group_id'] ?? 0;
if ( empty( $group_id ) ) {
	return;
}

$course_activities         = bb_meprlms_get_group_course_activities( $group_id );
$global_course_activities  = bb_get_enabled_meprlms_course_activities();
$meprlms_course_activities = ! empty( $global_course_activities ) ? bb_meprlms_course_activities( $global_course_activities ) : array();
$bb_meprlms_groups         = groups_get_groupmeta( $group_id, 'bb-meprlms-group' );

// Get group course.
$args = array(
	'group_id' => $group_id,
	'fields'   => 'course_id',
);
if ( 'edit' === $action ) {
	$args['per_page'] = false;
}
$bb_meprlms_groups  = bb_load_meprlms_group()->get( $args );
$courses            = ! empty( $bb_meprlms_groups['courses'] ) ? $bb_meprlms_groups['courses'] : array();
$is_course_enable   = bb_meprlms_group_courses_is_enable( $group_id );
$hide_select_course = ! $is_course_enable ? 'bb-hide' : '';
?>

<div class="bb-group-meprlms-settings-container">
	<div class="bb-course-instruction">
		<?php
		if ( 'create' === $action ) {
			$not_access = ! bb_meprlms_manage_tab();
			?>
			<h4 class="bb-section-title">
				<?php esc_html_e( 'Course Tab', 'buddyboss-pro' ); ?>
			</h4>
			<p class="bb-section-info">
				<?php
					echo wp_kses(
						__( 'As an Instructor you can add a courses tab to your group and link associated courses within the tab.<br />You can then select what activity posts should automatically be added to the activity feed.', 'buddyboss-pro' ),
						array( 'br' => array() )
					);
				?>
			</p>
			<p class="checkbox bp-checkbox-wrap bb-meprlms-group-option-enable <?php echo $not_access ? esc_attr( 'bb_meprlms_not_allow' ) : ''; ?>">
				<input type="checkbox" name="bb-meprlms-group[bb-meprlms-group-course-is-enable]" id="bb-meprlms-group-course-is-enable" class="bs-styled-checkbox" value="1" 
				<?php
				checked( $is_course_enable );
				disabled( $not_access );
				?>
				/>
				<label for="bb-meprlms-group-course-is-enable">
					<?php esc_html_e( 'Yes, I want to add a course tab', 'buddyboss-pro' ); ?>
				</label>
			</p>
			<?php
		} else {
			?>
			<p class="bb-section-info">
				<?php esc_html_e( 'Add a course tab to your group and select which courses you would like to show under the course tab.', 'buddyboss-pro' ); ?>
			</p>
			<div class="field-group">
				<p class="checkbox bp-checkbox-wrap bb-meprlms-group-option-enable">
					<input type="checkbox" name="bb-meprlms-group[bb-meprlms-group-course-is-enable]" id="bb-meprlms-group-course-is-enable" class="bs-styled-checkbox" value="1" <?php checked( $is_course_enable ); ?> />
					<label for="bb-meprlms-group-course-is-enable">
						<span><?php esc_html_e( 'Yes, I want to add courses to this group', 'buddyboss-pro' ); ?></span>
					</label>
				</p>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	if ( bb_meprlms_manage_tab() ) {
		?>
		<div class="bb-course-activity-selection <?php echo esc_attr( $hide_select_course ); ?>">
			<?php
			if ( ! empty( $meprlms_course_activities ) ) {
				?>
				<fieldset>
					<h3><?php echo esc_html__( 'Select Course Activities', 'buddyboss-pro' ); ?></h3>
					<p class="bb-section-info">
						<?php esc_html_e( 'Which activities should be displayed in this group?', 'buddyboss-pro' ); ?>
					</p>
					<div class="bb-group-meprlms-settings-activities">
						<?php
						foreach ( $meprlms_course_activities as $key => $value ) {
							$checked = $course_activities[ $key ] ?? '0';
							?>
							<div class="field-group bp-checkbox-wrap">
								<p class="checkbox bp-checkbox-wrap bp-group-option-enable">
									<input type="checkbox" name="bb-meprlms-group[course-activity][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>" class="bs-styled-checkbox" value="1" <?php checked( $checked, '1' ); ?>/>
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
				<h3><?php echo esc_html__( 'Select Courses', 'buddyboss-pro' ); ?></h3>
				<p class="bb-section-info">
					<?php esc_html_e( 'Choose your courses you would like to associate with this group.', 'buddyboss-pro' ); ?>
				</p>
				<select name="bb-meprlms-group[courses][]" class="bb_meprlms_select2" multiple="multiple">
					<?php
					if ( ! empty( $courses ) ) {
						foreach ( $courses as $course_id ) {
							$course_id = (int) $course_id;

							// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							$title = html_entity_decode( get_the_title( $course_id ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
							?>
							<option value="<?php echo esc_attr( $course_id ); ?>" <?php selected( $course_id, $course_id ); ?>>
								<?php echo esc_html( $title ); ?>
							</option>
							<?php
						}
					} else {
						?>
						<option value=""><?php esc_html_e( 'Start typing a course name to associate with this group.', 'buddyboss-pro' ); ?></option>
						<?php
					}
					?>
				</select>
			</fieldset>
			<input type="hidden" id="bp-meprlms-group-id" value="<?php echo esc_attr( $group_id ); ?>"/>
			<?php
			wp_nonce_field( 'groups_' . $action . '_save_meprlms', 'meprlms_group_admin_ui' );
			?>
		</div>
		<?php
	}
	?>
</div>
