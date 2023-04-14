<?php
/**
 * BuddyPages Functions
 *
 * @package BuddyPagesFunctions
 * @subpackage BuddyPages
 * @author WebDevStudios
 * @since 1.0.0
 */

/**
 * Creates new BuddyPage.
 *
 * @since 1.0.0
 */
function buddypages_process_page_create() {

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( isset( $_POST['buddypage-create-submit'] ) && 'create_page' === $_POST['buddypage-create-submit'] ) {

		wp_verify_nonce( 'buddypage-edit-security', 'edit_buddypage' );

		$is_draft = false;

		if ( isset( $_POST['post_status'] ) && 'draft' === $_POST['post_status'] ) {
			$is_draft = true;
		}

		$post_title = isset( $_POST['post_title'] ) ? sanitize_text_field( $_POST['post_title'] ) : '';

		$defaults = array(
			'post_title'   => '',
			'post_content' => '',
			'post_name'    => $post_title,
			'post_status'  => 'publish',
			'post_type'    => 'buddypages',
		);

		$post_args = wp_parse_args( $_POST, $defaults );

		$post_args['post_title']   = sanitize_text_field( $post_args['post_title'] );
		$post_args['post_content'] = wp_kses_post( $post_args['post_content'] );
		$post_args['post_status']  = 'publish';

		// Insert the post into the database.
		$post_id = wp_insert_post( $post_args );

		// If the post was a draft set post status. need to do this to force post_name creation.
		if ( $is_draft ) {
			 wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		}

		$post = get_post( $post_id );

		if ( is_wp_error( $post_id ) ) {
			$errors = $post_id->get_error_messages();
			foreach ( $errors as $error ) {
				echo esc_attr( $error );
			}
			bp_core_add_message( __( 'Error adding page.', 'buddypages' ) );
			bp_core_redirect( trailingslashit( bp_displayed_user_domain() ) );
		} else {
			update_post_meta( $post_id, 'post_in', sanitize_text_field( $_POST['post_in'] ) );
			bp_core_add_message( __( 'Page added.', 'buddypages' ) );
			bp_core_redirect( trailingslashit( bp_displayed_user_domain() . $post->post_name . '/edit' ) );
		}
	}

}
add_action( 'bp_init', 'buddypages_process_page_create' );

/**
 * Creates or updates current BuddyPage.
 *
 * @since 1.0.0
 */
function buddypages_process_page_edit() {

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( isset( $_POST['buddypage-edit-submit'] ) && 'edit_page' === $_POST['buddypage-edit-submit'] ) {

		wp_verify_nonce( 'buddypage-edit-security', 'edit_buddypage' );

		$defaults = array(
			'ID'           => 0,
			'post_title'   => '',
			'post_content' => '',
			'post_status'  => '',
		);

		$post_args = wp_parse_args( $_POST, $defaults );

		$post_args['post_title']   = sanitize_text_field( $post_args['post_title'] );
		$post_args['post_content'] = wp_kses_post( $post_args['post_content'] );

		// Update the post into the database.
		$post_id = wp_update_post( $post_args );
		$post    = get_post( $post_id );

		if ( is_wp_error( $post_id ) ) {
			$errors = $post_id->get_error_messages();
			foreach ( $errors as $error ) {
				echo esc_attr( $error );
			}
			bp_core_add_message( __( 'Error editing page.', 'buddypages' ) );
			bp_core_redirect( trailingslashit( bp_displayed_user_domain() . $post->post_name . '/' . bp_current_action() ) );
		} else {
			update_post_meta( $post_id, 'post_in', sanitize_text_field( $_POST['post_in'] ) );
			bp_core_add_message( __( 'Changes saved.', 'buddypages' ) );
			bp_core_redirect( trailingslashit( bp_displayed_user_domain() . $post->post_name . '/' . bp_current_action() ) );
		}
	}

}
add_action( 'bp_init', 'buddypages_process_page_edit' );

/**
 * Deletes BuddyPage.
 *
 * @since 1.0.0
 */
function buddypages_process_page_delete() {

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( isset( $_GET['delete'] ) && 'edit' === bp_current_action() && bp_is_user() ) {
		if ( $id = intval( $_GET['delete'] ) ) {
			$post_id = wp_delete_post( $id, true );
			if ( ! $post_id ) {
				bp_core_add_message( __( 'Error deleting page.', 'buddypages' ) );
				bp_core_redirect( bp_displayed_user_domain() );
			} else {
				bp_core_add_message( __( 'Page deleted.', 'buddypages' ) );
				bp_core_redirect( bp_displayed_user_domain() );
			}
		}
	}
}
add_action( 'bp_init', 'buddypages_process_page_delete' );

/**
 * Returns array of groups data for groups diplayed user is admin.
 *
 * @return array groups displayed user is admin.
 */
function buddypages_groups_get_is_admin() {

	/**
	 * Filters the arguments used for groups_get_groups in buddypages_groups_get_is_admin().
	 *
	 * @since 1.1.0
	 *
	 * @param array $value Array of arguments for `groups_get_groups()`.
	 */
	$args = apply_filters( 'buddypages_groups_get_is_admin', array( 'page' => false ) );

	$groups = function_exists( 'groups_get_groups' ) ? groups_get_groups( $args ) : false;

	if ( empty( $groups ) ) {
		return array();
	}

	$groups_get_is_admin = array();

	foreach ( $groups['groups'] as $key => $value ) {
		if ( groups_is_user_admin( bp_displayed_user_id(), $value->id ) ) {
			$groups_get_is_admin[] = $groups['groups'][ $key ];
		}
	}
	return $groups_get_is_admin;
}

/**
 * Select options for buddypage.
 *
 * @since 1.0.0
 *
 * @param mixed $post Post data.
 * @param mixed $meta Post meta.
 * @return void
 */
function buddypages_post_in_options( $post, $meta ) {

	$post_in = isset( $meta['post_in'] ) ? $meta['post_in'][0] : 'profile';

	if ( current_user_can( 'manage_options' ) ) {
		echo '<option value="all-users" '. selected( $post_in, 'all-users', false ) .'>' . esc_html__( 'All Users', 'buddypages' ) . '</option>';
		if ( bp_is_active( 'groups' ) ) {
			echo '<option value="all-groups" '. selected( $post_in, 'all-groups', false ) .'>' . esc_html__( 'All Groups', 'buddypages' ) . '</option>';
		}
	}
	echo '<option value="profile" '. selected( $post_in, 'profile', false ) .'>' . esc_html__( 'My Profile', 'buddypages' ) . '</option>';

	$groups = buddypages_groups_get_is_admin();

	// buddypages_groups_get_is_admin returns an array regardless. Safe to not check for empty.
	foreach ( $groups as $group ) {
		echo '<option value="group-' . esc_attr( $group->id ) . '" '. selected( $post_in, 'group-' . esc_attr( $group->id ), false ) .'>' . esc_attr( $group->name ) . '</option>';
	}

	/**
	 * Fires at the end of the options list for post options.
	 *
	 * @since 1.0.0
	 *
	 * @param object $post Post data.
	 * @param object $meta Post meta.
	 */
	do_action( 'buddypages_post_in_options', $post, $meta );
}

/**
 * Post status options.
 *
 * @since 1.0.0
 *
 * @param string $status post status.
 */
function buddypages_post_status_options( $status ) {
	$status = $status ? $status : 'draft';
	echo '<label for="status-draft"><input type="radio" id="status-draft" name="post_status" value="draft" ' . checked( $status, 'draft', false ) . '/>' . esc_html__( 'Draft', 'buddypages' ) . '</label>';
	echo '<label for="status-publish"><input type="radio" id="status-publish" name="post_status" value="publish" ' . checked( $status, 'publish', false ) . '/>' . esc_html__( 'Publish', 'buddypages' ) . '</label>';

	/**
	 * Fires after the post status option labels.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Current status.
	 */
	do_action( 'buddypages_post_status_options', $status );
}

/**
 * Creates url for to the locaton of page, either profiles or group.
 *
 * @since 1.0.0
 *
 * @param string  $post_name post slug.
 * @param integer $post_id post id.
 * @return void
 */
function buddypages_permalink( $post_name, $post_id ) {

	$meta     = buddypages_post_in( $post_id );
	$is_group = explode( '-', $meta );
	$group_id = 0;

	if ( 'group' === $is_group[0] ) {
		$meta     = 'group';
		$group_id = $is_group[1];
	}

	switch ( $meta ) {
		case 'profile':
			echo '<a href="'. esc_url( bp_displayed_user_domain() . $post_name ) .'/">' . esc_attr__( 'Profile', 'buddypages' ) . '</a>';
		break;
		case 'group':
			if ( bp_is_active( 'groups' ) ) {
				$group = groups_get_group( $group_id );
				echo '<a href="'. esc_url( bp_get_root_domain() . '/'  . buddypress()->groups->root_slug . '/' .  $group->slug . '/' . $post_name ) .'/">' . esc_attr( $group->name ) . '</a>';
			}
		break;
		case 'all-groups':
			if ( bp_is_active( 'groups' ) ) {
				echo '<a href="'. esc_url( bp_get_root_domain() . '/'  . buddypress()->groups->root_slug ) .'/">' . esc_attr__( 'All Groups', 'buddypages' ) . '</a>';
			}
		break;
		case 'all-users':
			echo '<a href="'. esc_url( bp_get_root_domain() . '/'  . buddypress()->members->root_slug ) .'/">' . esc_attr__( 'All Members', 'buddypages' ) . '</a>';
		break;
	}
}

/**
 * Gets post_in post meta to determine where the BuddyPage should display.
 *
 * @since 1.0.0
 *
 * @param integer $post_id post ID.
 * @return string
 */
function buddypages_post_in( $post_id ) {
	return get_post_meta( $post_id, 'post_in', true );
}

/**
 * Add BuddyPage options to BuddyPress Settings.
 *
 * @since 1.0.0
 */
function buddypages_admin_settings() {

	add_settings_section(
		'buddypages_plugin_section',
		__( 'BuddyPage Settings',  'buddypages' ),
		'buddypages_settings_pages_callback',
		'buddypress'
	);

	add_settings_field(
		'buddypages-member-pages',
		__( 'Member Pages', 'buddypages' ),
		'buddypages_member_pages_callback',
		'buddypress',
		'buddypages_plugin_section'
	);

	register_setting(
		'buddypress',
		'buddypages-member-pages',
		'buddypages_checkbox_field_validate'
	);

}
add_action( 'bp_register_admin_settings', 'buddypages_admin_settings', 100 );

/**
 * Callback for BuddyPages settings section.
 *
 * @since 1.0.0
 */
function buddypages_settings_pages_callback() {
}

/**
 * This is the display function for your field.
 *
 * @since 1.0.0
 */
function buddypages_member_pages_callback() {
	/* if you use bp_get_option(), then you are sure to get the option for the blog BuddyPress is activated on */
	$bp_plugin_option_value = bp_get_option( 'buddypages-member-pages' );

	?>
	<input id="buddypages-member-pages" name="buddypages-member-pages" type="checkbox" value="1" <?php checked( $bp_plugin_option_value ); ?> />
	<label for="buddypages-member-pages"><?php esc_attr_e( 'Allow Members to Create Pages', 'buddypages' ); ?></label>
	<p class="description"><?php esc_attr_e( 'Allow site members to create pages for profiles and groups. Site administrators can always create BuddyPages, regardless of this setting.', 'buddypages' ); ?></p>
	<?php
}

/**
 * Validate field for being checked.
 *
 * @since 1.0.0
 *
 * @param integer $option Determine if option is checked.
 * @return integer
 */
function buddypages_checkbox_field_validate( $option = 0 ) {
	return intval( $option );
}

/**
 * Returns post slug.
 *
 * @since 1.0.0
 *
 * @param object $post Post data.
 * @return string
 */
function buddypages_post_slug( $post ) {
	$post_name = $post->post_name ? $post->post_name : sanitize_title( $post->post_title );
	return $post_name;
}

/**
 * Displays the WordPress text editor field.
 *
 * @since 1.0.0
 *
 * @param string $post_content Post content.
 */
function buddypages_text_editor( $post_content = '' ) {

	remove_filter( 'the_content', 'do_shortcode', 11 );

	/**
	 * Filters wp_editor args for BuddyPages content editor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes wp_editor arguments.
	 */
	$editor_args = apply_filters( 'buddypages_textarea_editor_args', array(
		'teeny'         => true,
		'media_buttons' => false,
		'quicktags'     => true,
		'textarea_rows' => 1,
	), 'admin' );

	wp_editor(
		$post_content,
		'post_content',
		$editor_args
	);
}

/**
 * Checks author id against displayed user id.
 *
 * @param integer $author_id Displayed user id.
 * @return boolean
 */
function buddypages_can_edit( $post_author_id, $displayed_user_id, $post ) {
	$can_edit = absint( $post_author_id ) === absint( $displayed_user_id );
	return (bool) apply_filters( 'buddypages_can_edit', $can_edit, $post_author_id, $displayed_user_id, $post );
}

/**
 * Check whether or not a user should have access to BuddyPages.
 *
 * @since 1.1.0
 *
 * @return bool
 */
function buddypages_has_access() {
	if ( is_super_admin() ) {
		return true;
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}

	$buddypages_has_access = (bool) bp_get_option( 'buddypages-member-pages', false );
	if ( ! $buddypages_has_access ) {
		return false;
	}

	return (bool) apply_filters( 'buddypages_has_access', $buddypages_has_access );
}

function buddypages_can_edit_settings( $should_access ) {
	$should_access = bp_core_can_edit_settings();

	return $should_access;
}
add_filter( 'buddypages_has_access', 'buddypages_can_edit_settings' );

/**
 * Add an admin notice to BuddyPages editor screens regarding intended frontend usage.
 *
 * @since TBD
 */
function buddypages_admin_notice() {
	$bpscreen = get_current_screen();
	if ( null === $bpscreen ) {
		return;
	}

	if ( 'buddypages' === $bpscreen->post_type && 'post' === $bpscreen->base ) {
		printf(
			'<div class="notice notice-info"><p>%s</p></div>',
			esc_html__(
				'BuddyPages pages are meant to be managed on the frontend through users\' BuddyPress settings area.'
			)
		);
	}
}
add_action( 'admin_notices', 'buddypages_admin_notice' );

/**
 * Hide access to BuddyPages page administration if moderated by BuddyPress Registration Options.
 *
 * @since TBD
 *
 * @param bool $should_access
 * @return false|mixed
 */
function buddypages_deny_if_bpro_moderated( $should_access ) {
	if ( ! function_exists( 'bp_registration_get_moderation_status' ) ) {
		return $should_access;
	}

	if ( true === bp_registration_get_moderation_status( get_current_user_id() ) ) {
		$should_access = false;
	} else {
		$should_access = true;
	}

	return $should_access;
}
add_filter( 'buddypages_has_access', 'buddypages_deny_if_bpro_moderated' );
