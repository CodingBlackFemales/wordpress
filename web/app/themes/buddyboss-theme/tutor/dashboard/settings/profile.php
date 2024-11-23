<?php
/**
 * Profile
 *
 * @package Tutor\Templates
 * @subpackage Dashboard\Settings
 *
 * @since 1.6.2
 */

$user = wp_get_current_user();

// Prepare profile pic.
$profile_placeholder = apply_filters( 'tutor_login_default_avatar', tutor()->url . 'assets/images/profile-photo.png' );
$profile_photo_src   = $profile_placeholder;
$profile_photo_id    = get_user_meta( $user->ID, '_tutor_profile_photo', true );
if ( $profile_photo_id ) {
	$url                                 = wp_get_attachment_image_url( $profile_photo_id, 'full' );
	! empty( $url ) ? $profile_photo_src = $url : 0;
}

// Prepare cover photo.
$cover_placeholder = tutor()->url . 'assets/images/cover-photo.jpg';
$cover_photo_src   = $cover_placeholder;
$cover_photo_id    = get_user_meta( $user->ID, '_tutor_cover_photo', true );
if ( $cover_photo_id ) {
	$url                               = wp_get_attachment_image_url( $cover_photo_id, 'full' );
	! empty( $url ) ? $cover_photo_src = $url : 0;
}

// Prepare display name.
$public_display                     = array();
$public_display['display_nickname'] = $user->nickname;
$public_display['display_username'] = $user->user_login;

if ( ! empty( $user->first_name ) ) {
	$public_display['display_firstname'] = $user->first_name;
}

if ( ! empty( $user->last_name ) ) {
	$public_display['display_lastname'] = $user->last_name;
}

if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {
	$public_display['display_firstlast'] = $user->first_name . ' ' . $user->last_name;
	$public_display['display_lastfirst'] = $user->last_name . ' ' . $user->first_name;
}

if ( ! in_array( $user->display_name, $public_display ) ) { // Only add this if it isn't duplicated elsewhere.
	$public_display = array( 'display_displayname' => $user->display_name ) + $public_display;
}

$public_display = array_map( 'trim', $public_display );
$public_display = array_unique( $public_display );
$max_filesize   = floatval( ini_get( 'upload_max_filesize' ) ) * ( 1024 * 1024 );
?>

<div class="tutor-dashboard-setting-profile tutor-dashboard-content-inner">

	<?php do_action( 'tutor_profile_edit_form_before' ); ?>

	<?php
	if ( ! bb_theme_enable_tutorlms_override() ) {
		?>

		<div id="tutor_profile_cover_photo_editor">

			<input id="tutor_photo_dialogue_box" type="file" accept=".png,.jpg,.jpeg"/>
			<input type="hidden" class="upload_max_filesize" value="<?php echo esc_attr( $max_filesize ); ?>">
			<div id="tutor_cover_area" data-fallback="<?php echo esc_attr( $cover_placeholder ); ?>" style="background-image:url(<?php echo esc_url( $cover_photo_src ); ?>)">
				<span class="tutor_cover_deleter">
					<span class="dashboard-profile-delete tutor-icon-trash-can-bold"></span>
				</span>
				<div class="tutor_overlay">
					<button class="tutor_cover_uploader tutor-btn tutor-btn-primary">
						<i class="tutor-icon-camera tutor-mr-12" area-hidden="true"></i>
						<span><?php echo $profile_photo_id ? esc_html__( 'Update Cover Photo', 'buddyboss-theme' ) : esc_html__( 'Upload Cover Photo', 'buddyboss-theme' ); ?></span>
					</button>
				</div>
			</div>
			<div id="tutor_photo_meta_area">
				<img src="<?php echo esc_url( tutor()->url . '/assets/images/' ); ?>info-icon.svg" alt="" />
				<span><?php esc_html_e( 'Profile Photo Size', 'buddyboss-theme' ); ?>: <span><?php esc_html_e( '200x200', 'buddyboss-theme' ); ?></span> <?php esc_html_e( 'pixels', 'buddyboss-theme' ); ?></span>
				<span>&nbsp;&nbsp;&nbsp;&nbsp;<?php esc_html_e( 'Cover Photo Size', 'buddyboss-theme' ); ?>: <span><?php esc_html_e( '700x430', 'buddyboss-theme' ); ?></span> <?php esc_html_e( 'pixels', 'buddyboss-theme' ); ?> </span>
				<span class="loader-area"><?php esc_html_e( 'Saving...', 'buddyboss-theme' ); ?></span>
			</div>
			<div id="tutor_profile_area" data-fallback="<?php echo esc_attr( $profile_placeholder ); ?>" style="background-image:url(<?php echo esc_url( $profile_photo_src ); ?>)">
				<div class="tutor_overlay">
					<i class="tutor-icon-camera"></i>
				</div>
			</div>
			<div id="tutor_pp_option">
				<div class="up-arrow">
					<i></i>
				</div>

				<span class="tutor_pp_uploader profile-uploader">
					<i class="profile-upload-icon tutor-icon-image-landscape tutor-mr-4"></i> <?php esc_html_e( 'Upload Photo', 'buddyboss-theme' ); ?>
				</span>
				<span class="tutor_pp_deleter profile-uploader">
					<i class="profile-upload-icon tutor-icon-trash-can-bold tutor-mr-4"></i> <?php esc_html_e( 'Delete', 'buddyboss-theme' ); ?>
				</span>

				<div></div>
			</div>
		</div>

		<?php
	}
	?>

	<form action="" method="post" enctype="multipart/form-data">
		<?php
		$error_list = apply_filters( 'tutor_profile_edit_validation_errors', array() );
		if ( is_array( $error_list ) && count( $error_list ) ) {
			echo '<div class="tutor-alert-warning tutor-mb-12"><ul class="tutor-required-fields">';
			foreach ( $error_list as $error_key => $error_value ) {
				echo '<li>' . esc_html( $error_value ) . '</li>';
			}
			echo '</ul></div>';
		}
		?>

		<?php do_action( 'tutor_profile_edit_input_before' ); ?>

		<?php
		$is_readonly = '';
		$is_hidden   = '';
		if ( bb_theme_enable_tutorlms_override() ) {
			$is_readonly = 'readonly';
			$is_hidden   = 'display:none;';
		}

		if ( bb_theme_enable_tutorlms_override() && function_exists( 'bp_core_get_user_domain') ) {
			?>
			<div class="tutor-row tutor-row--notice">
				<aside class="bp-feedback bp-messages info">
					<span class="bp-icon" aria-hidden="true"></span>
					<?php
						$view_profile_url  = trailingslashit( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_profile_slug() );
						$edit_profile_url  = $view_profile_url . 'edit';
						echo sprintf(
							'<p>' . esc_html__( 'You can %s or %s your profile here.', 'buddyboss-theme' ) . '</p>',
							'<a href="' . $view_profile_url . '">' . esc_html__( 'view', 'buddyboss-theme' ) . '</a>',
							'<a href="' . $edit_profile_url . '">' . esc_html__( 'edit', 'buddyboss-theme' ) . '</a>'
						);
					?>
				</aside>
			</div>
			<?php
		}
		?>
		<div class="tutor-row" style='<?php echo $is_hidden; ?>' >
			<div class="tutor-col-12 tutor-col-sm-6 tutor-col-md-12 tutor-col-lg-6 tutor-mb-32">
				<label class="tutor-form-label tutor-color-secondary">
					<?php esc_html_e( 'First Name', 'buddyboss-theme' ); ?>
				</label>
				<input <?php echo $is_readonly; ?> class="tutor-form-control" type="text" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" placeholder="<?php esc_attr_e( 'First Name', 'buddyboss-theme' ); ?>">
			</div>

			<div class="tutor-col-12 tutor-col-sm-6 tutor-col-md-12 tutor-col-lg-6 tutor-mb-32">
				<label class="tutor-form-label tutor-color-secondary">
					<?php esc_html_e( 'Last Name', 'buddyboss-theme' ); ?>
				</label>
				<input <?php echo $is_readonly; ?> class="tutor-form-control" type="text" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" placeholder="<?php esc_attr_e( 'Last Name', 'buddyboss-theme' ); ?>">
			</div>
		</div>

		<div class="tutor-row" style='<?php echo $is_hidden; ?>' >
			<div class="tutor-col-12 tutor-col-sm-6 tutor-col-md-12 tutor-col-lg-6 tutor-mb-32">
				<label class="tutor-form-label tutor-color-secondary">
					<?php esc_html_e( 'User Name', 'buddyboss-theme' ); ?>
				</label>
				<input class="tutor-form-control" type="text" disabled="disabled" value="<?php echo esc_attr( $user->user_login ); ?>">
			</div>

			<div class="tutor-col-12 tutor-col-sm-6 tutor-col-md-12 tutor-col-lg-6 tutor-mb-32">
				<label class="tutor-form-label tutor-color-secondary">
					<?php esc_html_e( 'Phone Number', 'buddyboss-theme' ); ?>
				</label>
				<input <?php echo $is_readonly; ?> class="tutor-form-control" type="tel" pattern="[0-9]{3}-[0-9]{2}-[0-9]{3}" name="phone_number" value="<?php echo esc_html( filter_var( get_user_meta( $user->ID, 'phone_number', true ), FILTER_SANITIZE_NUMBER_INT ) ); ?>" placeholder="<?php esc_attr_e( 'Phone Number', 'buddyboss-theme' ); ?>">
			</div>
		</div>

		<div class="tutor-row" style='<?php echo $is_hidden; ?>' >
			<div class="tutor-col-12 tutor-mb-32">
				<label class="tutor-form-label tutor-color-secondary">
					<?php esc_html_e( 'Skill/Occupation', 'buddyboss-theme' ); ?>
				</label>
				<input class="tutor-form-control" type="text" name="tutor_profile_job_title" value="<?php echo esc_attr( get_user_meta( $user->ID, '_tutor_profile_job_title', true ) ); ?>" placeholder="<?php esc_attr_e( 'UX Designer', 'buddyboss-theme' ); ?>">
			</div>
		</div>

		<div class="tutor-row" style='<?php echo $is_hidden; ?>' >
			<div class="tutor-col-12 tutor-mb-32">
				<label class="tutor-form-label tutor-color-secondary">
					<?php esc_html_e( 'Bio', 'buddyboss-theme' ); ?>
				</label>
				<?php
				$profile_bio = get_user_meta( $user->ID, '_tutor_profile_bio', true );
				wp_editor( $profile_bio, 'tutor_profile_bio', tutor_utils()->get_profile_bio_editor_config() );
				?>
			</div>
		</div>

		<div class="tutor-row" style='<?php echo $is_hidden; ?>' >
			<div class="tutor-col-12 tutor-col-sm-6 tutor-col-md-12 tutor-col-lg-6 tutor-mb-32">
				<label class="tutor-form-label tutor-color-secondary">
					<?php esc_html_e( 'Display name publicly as', 'buddyboss-theme' ); ?>

				</label>
				<select <?php echo $is_readonly; ?> class="tutor-form-select" name="display_name">
					<?php
					foreach ( $public_display as $_id => $item ) {
						?>
								<option <?php selected( $user->display_name, $item ); ?>><?php echo esc_html( $item ); ?></option>
							<?php
					}
					?>
				</select>
				<div class="tutor-fs-7 tutor-color-secondary tutor-mt-12">
					<?php esc_html_e( 'The display name is shown in all public fields, such as the author name, instructor name, student name, and name that will be printed on the certificate.', 'buddyboss-theme' ); ?>
				</div>
			</div>
		</div>
		<?php do_action( 'tutor_profile_edit_input_after', $user ); ?>

		<div class="tutor-row">
			<div class="tutor-col-12">
				<button type="submit" class="tutor-btn tutor-btn-primary tutor-profile-settings-save">
					<?php esc_html_e( 'Update Profile', 'buddyboss-theme' ); ?>
				</button>
			</div>
		</div>
	</form>

	<?php do_action( 'tutor_profile_edit_form_after' ); ?>
</div>
<style>
	.tutor-form-control.invalid{border-color: red;}
</style>
