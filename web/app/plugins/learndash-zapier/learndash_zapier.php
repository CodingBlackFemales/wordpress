<?php
/**
 * Plugin Name: LearnDash LMS - Zapier Integration
 * Plugin URI: http://www.learndash.com
 * Description: LearnDash LMS addon plugin that integrates LearnDash with Zapier
 * Version: 2.3.0
 * Author: LearnDash
 * Author URI: http://www.learndash.com
 */

if ( ! defined( 'LEARNDASH_ZAPIER_VERSION' ) ) {
	define( 'LEARNDASH_ZAPIER_VERSION', '2.3.0' );
}

// Plugin file
if ( ! defined( 'LEARNDASH_ZAPIER_FILE' ) ) {
	define( 'LEARNDASH_ZAPIER_FILE', __FILE__ );
}

// Plugin folder path
if ( ! defined( 'LEARNDASH_ZAPIER_PLUGIN_PATH' ) ) {
	define( 'LEARNDASH_ZAPIER_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

// Plugin folder URL
if ( ! defined( 'LEARNDASH_ZAPIER_PLUGIN_URL' ) ) {
	define( 'LEARNDASH_ZAPIER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Check and set dependencies
 *
 * @return void
 */
function learndash_zapier_check_dependency() {
	include LEARNDASH_ZAPIER_PLUGIN_PATH . 'includes/class-dependency-check.php';

	LearnDash_Dependency_Check_LD_Zapier::get_instance()->set_dependencies(
		array(
			'sfwd-lms/sfwd_lms.php' => array(
				'label'       => '<a href="https://learndash.com">LearnDash LMS</a>',
				'class'       => 'SFWD_LMS',
				'min_version' => '3.0.0',
			),
		)
	);

	LearnDash_Dependency_Check_LD_Zapier::get_instance()->set_message(
		__( 'LearnDash LMS - Zapier Integration Add-on requires the following plugin(s) to be active:', 'learndash-zapier' )
	);
}

//////////
// Init //
//////////
add_action( 'plugins_loaded', 'learndash_zapier_load_translation' );

learndash_zapier_check_dependency();

add_action(
	'plugins_loaded',
	function() {
		if ( LearnDash_Dependency_Check_LD_Zapier::get_instance()->check_dependency_results() ) {
			learndash_zapier_includes();
			learndash_zapier_hooks();
		}
	}
);

function learndash_zapier_includes() {
	if ( is_admin() ) {
		include_once LEARNDASH_ZAPIER_PLUGIN_PATH . 'includes/admin/class-settings-page.php';
		include_once LEARNDASH_ZAPIER_PLUGIN_PATH . 'includes/admin/class-settings-section.php';
	}

	include_once LEARNDASH_ZAPIER_PLUGIN_PATH . 'includes/class-api.php';
}

function learndash_zapier_hooks() {
	 add_action( 'init', 'ld_zapier_init', 1 );
	add_action( 'wp', 'ld_zapier_disable_frontend' );
	add_action( 'learndash_update_course_access', 'ld_zapier_learndash_update_course_access', 10, 4 );
	add_action( 'ld_group_postdata_updated', 'ld_zapier_group_enrolled', 10, 4 );
	add_action( 'ld_removed_group_access', 'ld_zapier_remove_sent_zap_record_via_user_group', 10, 2 );
	add_action( 'learndash_update_course_access', 'ld_zapier_remove_sent_course_enrollment_zap', 10, 4 );
	add_action( 'learndash_lesson_completed', 'ld_zapier_learndash_lesson_completed', 10, 1 );
	add_action( 'learndash_course_completed', 'ld_zapier_learndash_course_completed', 9999, 1 );
	add_action( 'learndash_topic_completed', 'ld_zapier_learndash_topic_completed', 10, 1 );
	add_action( 'learndash_new_essay_submitted', 'ld_zapier_new_essay_submitted', 10, 2 );
	add_action( 'learndash_quiz_completed', 'ld_zapier_learndash_quiz_passed', 10, 2 );
	add_action( 'learndash_essay_all_quiz_data_updated', 'ld_zapier_essay_graded', 10, 4 );

	add_action( 'admin_enqueue_scripts', 'ld_zapier_enqueue_script' );
	add_action( 'admin_menu', 'ld_zapier_menu', 1000 );
	add_action( 'admin_menu', 'learndash_zapier_add_admin_pages' );
	add_action( 'admin_init', 'ld_zapier_meta_box' );
	add_action( 'save_post', 'ld_zapier_save_post', 10, 2 );
	add_filter( 'learndash_submenu', 'ld_zapier_add_submenu_item' );
	add_filter( 'learndash_admin_tabs_set', 'learndash_zapier_admin_tabs_set', 10, 2 );
}

function ld_zapier_init() {
	 $post_args = array(
		 'labels' => array(
			 'name' => __( 'Zapier Triggers', 'learndash-zapier' ),
			 'singular_name' => __( 'Zapier Trigger', 'learndash-zapier' ),
			 'add_new' => __( 'Add Trigger', 'learndash-zapier' ),
			 'add_new_item' => __( 'Add Trigger', 'learndash-zapier' ),
			 'edit' => __( 'Edit Trigger', 'learndash-zapier' ),
			 'edit_item' => __( 'Edit Trigger', 'learndash-zapier' ),
			 'new_item' => __( 'Trigger', 'learndash-zapier' ),
			 'view' => __( 'View Trigger', 'learndash-zapier' ),
			 'view_item' => __( 'View Trigger', 'learndash-zapier' ),
			 'search_items' => __( 'Search Trigger', 'learndash-zapier' ),
			 'not_found' => __( 'No Trigger found', 'learndash-zapier' ),
			 'not_found_in_trash' => __( 'No trigger found in Trash', 'learndash-zapier' ),
		 ),
		 'public'              => false,
		 'show_ui'             => true,
		 'show_in_menu'        => false,
		 'show_in_admin_bar'   => false,
		 'menu_position'       => null,
		 'menu_icon'           => null,
		 'show_in_nav_menus'   => false,
		 'publicly_queryable'  => false,
		 'exclude_from_search' => true,
		 'has_archive'         => false,
		 'query_var'           => false,
		 'can_export'          => true,
		 'rewrite'             => false,
		 'capability_type'     => 'post',
		 'supports' => array(
			 'title',
		 ),
		 'menu_icon' => 'dashicons-admin-generic',
		 'has_archive' => false,
	 );
	 $post_args = apply_filters( 'learndash_post_args_zapier', $post_args );
	 register_post_type( 'sfwd-zapier', $post_args );
}

function ld_zapier_disable_frontend() {
		global $post;
	if ( ! is_admin() && ! empty( $post ) && $post->post_type == 'sfwd-zapier' ) {
			wp_redirect( get_bloginfo( 'siteurl' ) );
			exit;
	}
}

function ld_zapier_enqueue_script() {
	$screen = get_current_screen();

	if ( ! strstr( $screen->id, 'learndash-zapier' ) && ! strstr( $screen->id, 'sfwd-zapier' ) ) {
		return;
	}

	wp_enqueue_script( 'ld_zapier_admin', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array( 'jquery' ) );
	wp_localize_script(
		'ld_zapier_admin',
		'LD_Zapier_Params',
		array(
			'webhook_message' => __( 'Triggers on this page are used for legacy webhook integration and not for LearnDash public Zapier app.', 'learndash-zapier' ),
		)
	);
}

// Remove the default menu added via the register_post_type
function ld_zapier_menu() {
	global $submenu;

	if ( isset( $submenu['edit.php?post_type=sfwd-zapier'] ) ) {
		remove_menu_page( 'edit.php?post_type=sfwd-zapier' );
	}
}

function ld_zapier_add_submenu_item( $submenu ) {

	$notification_menu = array(
		array(
			'name' => __( 'Zapier', 'learndash-zapier' ),
			'cap'  => 'manage_options', // @TODO Need to confirm this capability on the menu.
			'link' => 'admin.php?page=learndash-zapier-settings',
		),
	);

	array_splice( $submenu, 10, 0, $notification_menu );

	return $submenu;
}

function learndash_zapier_add_admin_pages() {
	add_submenu_page( 'learndash-zapier-non-existent', __( 'Example Templates', 'learndash-zapier' ), __( 'Example Templates', 'learndash-zapier' ), 'manage_options', 'learndash-zapier-templates', 'learndash_zapier_output_templates_page' );
}


function learndash_zapier_output_templates_page() {
	?>
	<div class="wrap">
		<script src="https://zapier.com/apps/embed/widget.js?services=learndash&limit=10"></script>
	</div>
	<?php
}

function learndash_zapier_admin_tabs_set( $current_screen_parent_file, $tabs ) {
	$screen = get_current_screen();
	if ( ( $current_screen_parent_file == 'learndash-lms' && $screen->post_type == 'sfwd-zapier' ) || strstr( $screen->id, 'learndash-zapier-templates' ) ) {

		$tabs->add_admin_tab_item(
			$current_screen_parent_file,
			array(
				'link'          => 'admin.php?page=learndash-zapier-settings',
				'name'          => __( 'Settings', 'learndash-thrivecart' ),
				'id'            => 'learndash-zapier-settings',
			),
			1
		);

		$tabs->add_admin_tab_item(
			$current_screen_parent_file,
			array(
				'link'          => 'admin.php?page=learndash-zapier-templates',
				'name'          => __( 'Example Templates', 'learndash-thrivecart' ),
				'id'            => 'learndash-zapier-templates',
			),
			2
		);

		$tabs->add_admin_tab_item(
			$current_screen_parent_file,
			array(
				'link'          => 'edit.php?post_type=sfwd-zapier',
				'name'          => __( 'Triggers (Webhooks)', 'learndash-zapier' ),
				'id'            => 'edit-sfwd-zapier',
			),
			3
		);

	} elseif ( $current_screen_parent_file == 'edit.php?post_type=sfwd-zapier' && $screen->id !== 'admin_page_learndash-zapier-settings' ) {
		$tabs->add_admin_tab_item(
			$current_screen_parent_file,
			array(
				'link'          => 'admin.php?page=learndash-zapier-templates',
				'name'          => __( 'Example Templates', 'learndash-thrivecart' ),
				'id'            => 'learndash-zapier-templates',
			),
			2
		);

		$tabs->add_admin_tab_item(
			$current_screen_parent_file,
			array(
				'link'          => 'edit.php?post_type=sfwd-zapier',
				'name'          => __( 'Triggers (Webhooks)', 'learndash-zapier' ),
				'id'            => 'edit-sfwd-zapier',
			),
			3
		);

	} elseif ( $current_screen_parent_file == 'edit.php?post_type=sfwd-zapier' && $screen->id === 'admin_page_learndash-zapier-settings' ) {

		$tabs->add_admin_tab_item(
			$current_screen_parent_file,
			array(
				'link'          => 'admin.php?page=learndash-zapier-templates',
				'name'          => __( 'Example Templates', 'learndash-thrivecart' ),
				'id'            => 'learndash-zapier-templates',
			),
			2
		);

		$tabs->add_admin_tab_item(
			$current_screen_parent_file,
			array(
				'link'          => 'edit.php?post_type=sfwd-zapier',
				'name'          => __( 'Triggers (Webhooks)', 'learndash-zapier' ),
				'id'            => 'edit-sfwd-zapier',
			),
			3
		);
	}
}

function learndash_zapier_load_translation() {
	load_plugin_textdomain( 'learndash-zapier', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	// include translation/update class
	include LEARNDASH_ZAPIER_PLUGIN_PATH . 'includes/class-translations-ld-zapier.php';
}

function ld_zapier_meta_box() {
	add_meta_box(
		'ld_zapier_meta_box',
		__( 'Trigger Settings', 'learndash-zapier' ),
		'ld_zapier_meta_box_content',
		'sfwd-zapier',
		'normal',
		'high'
	);
}

function ld_zapier_meta_box_content( $zapier_data ) {
	$events = ld_zapier_get_trigger_events();
	$webhook_url = esc_html( get_post_meta( $zapier_data->ID, 'webhook', true ) );
	$zapier_trigger = get_post_meta( $zapier_data->ID, 'zapier_trigger', true );

	wp_nonce_field( 'metabox', 'ld_zapier_nonce' );
	?>
	<table>
	<tr>
			<td style="width: 150px"><?php _e( 'Trigger Event', 'learndash-zapier' ); ?></td>
			<td>
				<select name="zapier_trigger" class="zapier_trigger">
					<option value=""><?php _e( 'Select', 'learndash-zapier' ); ?></option>
					<option value="enrolled_into_course" <?php echo selected( $zapier_trigger, 'enrolled_into_course' ); ?> ><?php _e( 'Enrolled into course', 'learndash-zapier' ); ?></option>
					<option value="course_completed" <?php echo selected( $zapier_trigger, 'course_completed' ); ?> ><?php _e( 'Course completed', 'learndash-zapier' ); ?></option>
					<option value="lesson_completed" <?php echo selected( $zapier_trigger, 'lesson_completed' ); ?> ><?php _e( 'Lesson completed', 'learndash-zapier' ); ?></option>
					<option value="topic_completed" <?php echo selected( $zapier_trigger, 'topic_completed' ); ?> ><?php _e( 'Topic completed', 'learndash-zapier' ); ?></option>
					<option value="quiz_passed" <?php echo selected( $zapier_trigger, 'quiz_passed' ); ?> ><?php _e( 'Quiz passed', 'learndash-zapier' ); ?></option>
					<option value="quiz_failed" <?php echo selected( $zapier_trigger, 'quiz_failed' ); ?> ><?php _e( 'Quiz failed', 'learndash-zapier' ); ?></option>
					<option value="quiz_completed" <?php echo selected( $zapier_trigger, 'quiz_completed' ); ?> ><?php _e( 'Quiz completed', 'learndash-zapier' ); ?></option>
					<option value="essay_submitted" <?php echo selected( $zapier_trigger, 'essay_submitted' ); ?>><?php _e( 'Essay Submitted', 'learndash-zapier' ); ?></option>
				</select>
		<br>
		<small><?php _e( 'A trigger will be sent on the selected event to the URL configured below.', 'learndash-zapier' ); ?></small>
		<br>
			</td>
		</tr>
		<?php foreach ( $events as $post_type => $triggers ) : ?>
		<tr class="zapier_trigger_<?php echo esc_attr( $post_type ); ?>" style="display: none;">
			 <td style="width: 150px">
				<?php printf( __( 'Trigger %s', 'learndash-zapier' ), ucfirst( $post_type ) ); ?>
			</td>
			<td>
				<?php echo ld_zapier_trigger_select( $post_type ); ?>
				<br>
				<small>
					<?php printf( __( '%s that you want to associate with the trigger.', 'learndash-zapier' ), ucfirst( $post_type ) ); ?>
				</small>
			</td>
		</tr>
		<?php endforeach; ?>
		<tr>
			<td style="width: 150px"><?php _e( 'Webhook URL', 'learndash-zapier' ); ?></td>
			<td><input type="text"  name="webhook" value="<?php echo $webhook_url; ?>" /><br>
		<small><?php _e( 'This is the url of your Zapier webhook, provided by Zapier when creating a new Zap.', 'learndash-zapier' ); ?></small>
		</td>
		</tr>        
	</table>
	<?php
}

function ld_zapier_save_post( $post_id, $zapier_data ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_POST['ld_zapier_nonce'] ) || ! wp_verify_nonce( $_POST['ld_zapier_nonce'], 'metabox' ) ) {
		return;
	}

	$events = ld_zapier_get_trigger_events();

	if ( $zapier_data->post_type == 'sfwd-zapier' ) {
		if ( isset( $_POST['webhook'] ) ) {
			update_post_meta( $post_id, 'webhook', sanitize_text_field( $_POST['webhook'] ) );
		}

		if ( isset( $_POST['zapier_trigger'] ) ) {
			update_post_meta( $post_id, 'zapier_trigger', sanitize_text_field( $_POST['zapier_trigger'] ) );

			foreach ( $events as $post_type => $triggers ) {
				if ( in_array( $_POST['zapier_trigger'], $triggers ) ) {
					update_post_meta( $post_id, 'zapier_trigger_' . $post_type, sanitize_text_field( $_POST[ 'zapier_trigger_' . $post_type ] ) );
				} else {
					delete_post_meta( $post_id, 'zapier_trigger_' . $post_type );
				}
			}
		}
	}
}

// Enrolled into course
function ld_zapier_learndash_update_course_access( $user_id, $course_id, $access_list, $remove ) {
	ld_zapier_debug( 'ld_zapier_learndash_update_course_access' );

	if ( $remove || empty( $user_id ) || empty( $course_id ) ) {
		return;
	}
	$user = get_user_by( 'id', $user_id );
	if ( empty( $user->ID ) ) {
		return;
	}
	$course = get_post( $course_id );
	if ( empty( $course->ID ) ) {
		return;
	}

	$data = array(
		'user'   => $user,
		'course' => $course,
	);

	$data['course_started_on'] = ld_course_access_from( $data['course']->ID, $data['user']->ID );
	$data['course_started_on'] = date( 'Y-m-d H:i:s', $data['course_started_on'] );

	ld_zapier_debug( $data );

	ld_zapier_send_trigger( 'enrolled_into_course', $data );
}

/**
 * Enrolled into course via group
 */
function ld_zapier_group_enrolled( $group_id, $group_leaders, $group_users, $group_courses ) {
	ld_zapier_debug( 'ld_zapier_group_enrolled' );

	foreach ( $group_courses as $course_id ) {
		foreach ( $group_users as $user_id ) {
			// If zap sent, continue
			$sent = get_user_meta( $user_id, '_ld_zapier_sent_course_enrollment_zap', true );
			$sent = (array) maybe_unserialize( $sent );
			if ( in_array( $course_id, $sent ) ) {
				continue;
			}

			$user = get_user_by( 'id', $user_id );
			if ( empty( $user->ID ) ) {
				continue;
			}

			$course = get_post( $course_id );
			if ( empty( $course->ID ) ) {
				continue;
			}

			$data = array(
				'user'   => $user,
				'course' => $course,
			);

			ld_zapier_debug( $data );

			ld_zapier_send_trigger( 'enrolled_into_course', $data );

			// Store sent zap in DB
			$sent[] = $course_id;
			update_user_meta( $user_id, '_ld_zapier_sent_course_enrollment_zap', $sent );
		}
	}
}

// Remove $course_id from sent course enrollment zap notification if user unenrolled via user group
function ld_zapier_remove_sent_zap_record_via_user_group( $user_id, $group_id ) {
	$sent = get_user_meta( $user_id, '_ld_zapier_sent_course_enrollment_zap', true );
	$sent = (array) maybe_unserialize( $sent );

	foreach ( $sent as $key => $course_id ) {
		unset( $sent[ $key ] );
	}

	update_user_meta( $user_id, '_ld_zapier_sent_course_enrollment_zap', $sent );
}

// Remove $course_id from sent course enrollment zap notification if course unenrolled via course group
function ld_zapier_remove_sent_zap_record_via_course_group( $course_id, $group_id ) {
	$users = learndash_get_groups_users( $group_id );
	foreach ( $users as $user ) {
		$sent = get_user_meta( $user->ID, '_ld_zapier_sent_course_enrollment_zap', true );
		$sent = (array) maybe_unserialize( $sent );

		foreach ( $sent as $key => $c_id ) {
			if ( $course_id == $c_id ) {
				unset( $sent[ $key ] );
			}
		}

		update_user_meta( $user->ID, '_ld_zapier_sent_course_enrollment_zap', $sent );
	}
}

// Remove $course_id from sent course enrollment zap notification if user unenrolled
function ld_zapier_remove_sent_course_enrollment_zap( $user_id, $course_id, $access_list, $remove ) {
	if ( $remove !== true ) {
		return;
	}

	$sent = get_user_meta( $user_id, '_ld_zapier_sent_course_enrollment_zap', true );
	$sent = (array) maybe_unserialize( $sent );

	foreach ( $sent as $key => $c_id ) {
		if ( $c_id == $course_id ) {
			unset( $sent[ $key ] );
		}
	}

	update_user_meta( $user_id, 'ld_zapier_remove_sent_course_enrollment_zap', $sent );
}

// Lesson Completed
function ld_zapier_learndash_lesson_completed( $data ) {
	if ( ! empty( $data['user']->ID ) && ! empty( $data['lesson']->ID ) && ! empty( $data['course']->ID ) ) {
		ld_zapier_send_trigger( 'lesson_completed', $data );
	}
}

// Course completed
function ld_zapier_learndash_course_completed( $data ) {
	if ( ! empty( $data['user']->ID ) && ! empty( $data['course']->ID ) ) {
		$data['course_started_on'] = ld_course_access_from( $data['course']->ID, $data['user']->ID );
		$data['course_started_on'] = date( 'Y-m-d H:i:s', $data['course_started_on'] );

		$data['course_completed_on'] = learndash_user_get_course_completed_date( $data['user']->ID, $data['course']->ID );
		$data['course_completed_on'] = date( 'Y-m-d H:i:s', $data['course_completed_on'] );

		ld_zapier_send_trigger( 'course_completed', $data );
	}
}


// Topic completed
function ld_zapier_learndash_topic_completed( $data ) {
	if ( ! empty( $data['user']->ID ) && ! empty( $data['topic']->ID ) && ! empty( $data['lesson']->ID ) && ! empty( $data['course']->ID ) ) {
		ld_zapier_send_trigger( 'topic_completed', $data );
	}
}

/**
 * Send Zapier POST data when user submits new essay
 */
function ld_zapier_new_essay_submitted( $id, $args ) {
	$data = array();

	$user = get_user_by( 'id', $args['post_author'] );
	foreach ( $args as $key => $arg ) {
		$key = str_replace( 'post_', '', $key );
		$args[ $key ] = $arg;
	}

	unset( $args['type'] );
	unset( $args['author'] );

	$data['user']  = $user;
	$data['essay'] = $args;

	ld_zapier_send_trigger( 'essay_submitted', $data );
}

// Quiz completed
function ld_zapier_learndash_quiz_passed( $data, $user ) {
	if ( ! empty( $user->ID ) && ! empty( $data['quiz'] ) ) {
		$data['user'] = $user;
		unset( $data['rank'] );
		unset( $data['questions'] );

		ld_zapier_send_trigger( 'quiz_completed', $data );

		if ( $data['has_graded'] ) {
			foreach ( $data['graded'] as $id => $essay ) {
				if ( $essay['status'] == 'not_graded' ) {
					return;
				}
			}
		}

		if ( $data['pass'] == 1 ) {
			ld_zapier_send_trigger( 'quiz_passed', $data );
		} else {
			ld_zapier_send_trigger( 'quiz_failed', $data );
		}
	}
}

// Essay graded
function ld_zapier_essay_graded( $quiz_id, $question_id, $updated_scoring, $essay ) {
	if ( $essay->post_status == 'graded' ) {
		$user_id   = $essay->post_author;
		$real_quiz_id = learndash_get_quiz_id_by_pro_quiz_id( $quiz_id );
		$course_id = learndash_get_course_id( $real_quiz_id );

		// Exit if user already has completed the course
		if ( learndash_course_completed( $user_id, $course_id ) ) {
			return;
		}

		$users_quiz_data = get_user_meta( $essay->post_author, '_sfwd-quizzes', true );

		foreach ( $users_quiz_data as $quiz_key => $data ) {
			if ( $quiz_id == $data['pro_quizid'] ) {
				if ( $data['has_graded'] ) {
					foreach ( $data['graded'] as $id => $essay ) {
						if ( $essay['status'] == 'not_graded' ) {
							return;
						}
					}
				}

				if ( $data['pass'] == 1 ) {
					ld_zapier_send_trigger( 'quiz_passed', $data );
				} elseif ( $data['pass'] == 0 ) {
					ld_zapier_send_trigger( 'quiz_failed', $data );
				}
			}
		}
	}
}

function ld_zapier_send_trigger( $type, $data ) {
	$events = ld_zapier_get_trigger_events();

	if ( isset( $data['user']->user_pass ) ) {
		unset( $data['user']->user_pass );
	}

	$data['user_groups'] = learndash_get_users_group_ids( $data['user']->ID );
	if ( is_array( $data['user_groups'] ) ) {
		foreach ( $data['user_groups'] as $key => $group_id ) {
			$data['user_groups'][ $key ] = array(
				'id' => $group_id,
				'name' => get_the_title( $group_id ),
			);
		}
	}

	$data = apply_filters( 'learndash_zapier_post_data', $data, $type );

	$opt = array(
		'post_type' => 'sfwd-zapier',
		'meta_query' => array(
			array(
				'key' => 'zapier_trigger',
				'value' => $type,
			),
		),
		'posts_per_page' => -1,
	);

	$data['trigger_type'] = $type;
	$triggers = get_posts( $opt );
	ld_zapier_debug( $opt );
	ld_zapier_debug( $triggers );

	if ( ! empty( $triggers ) ) {
		foreach ( $triggers as $trigger ) {

			// Check if course, lesson, topic, quiz ID match with the trigger template
			foreach ( $events as $post_type => $trigger_events ) {
				if ( in_array( $type, $trigger_events ) ) {
					$trigger_post = get_post_meta( $trigger->ID, 'zapier_trigger_' . $post_type, true );

					if ( ! empty( $data[ $post_type ]->ID ) && $trigger_post != $data[ $post_type ]->ID && ! empty( $trigger_post ) ) {
						continue 2;
					}
				}
			}

			$webhook_url = get_post_meta( $trigger->ID, 'webhook', true );
			// Undefined variable $post_id
			// ld_zapier_debug($webhook_url.":".$post_id);
			if ( ! empty( $data['user']->data ) ) {
				$user = $data['user'];

				$data['user'] = $data['user']->data;

				$data['user']->first_name = $user->first_name;
				$data['user']->last_name  = $user->last_name;
			}

			ld_zapier_debug( $data );

			ld_zapier_post( $webhook_url, $data );
		}
	}
}

function ld_zapier_post( $url, $data ) {
	if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return;
	}

	$args = array(
		'method' => 'POST',
		'timeout'       => 20,
		'body'  => $data,
	);
	ld_zapier_debug( $data );
	return wp_remote_post( $url, $args );
}

function ld_zapier_debug( $msg ) {
	return;

	ini_set( 'log_errors', true );
	ini_set( 'error_log', __DIR__ . DIRECTORY_SEPARATOR . 'errors.log' );
	global $ld_lms_processing_id;
	error_log( "[$ld_lms_processing_id] " . print_r( $msg, true ) );
}

function ld_zapier_trigger_select( $post_type ) {
	switch ( $post_type ) {
		case 'course':
			$options = get_posts( 'post_type=sfwd-courses&posts_per_page=-1&orderby=title&order=ASC' );
			$plural = __( 'Courses', 'learndash-zapier' );
			$current = get_post_meta( get_the_ID(), 'zapier_trigger_course', true );
			break;

		case 'lesson':
			$options = get_posts( 'post_type=sfwd-lessons&posts_per_page=-1&orderby=title&order=ASC' );
			$plural = __( 'Lessons', 'learndash-zapier' );
			$current = get_post_meta( get_the_ID(), 'zapier_trigger_lesson', true );
			break;

		case 'topic':
			$options = get_posts( 'post_type=sfwd-topic&posts_per_page=-1&orderby=title&order=ASC' );
			$plural = __( 'Topics', 'learndash-zapier' );
			$current = get_post_meta( get_the_ID(), 'zapier_trigger_topic', true );
			break;

		case 'quiz':
			$options = get_posts( 'post_type=sfwd-quiz&posts_per_page=-1&orderby=title&order=ASC' );
			$plural = __( 'Quizzes', 'learndash-zapier' );
			$current = get_post_meta( get_the_ID(), 'zapier_trigger_quiz', true );
			break;
	}

	ob_start();
	?>

	<select name="zapier_trigger_<?php echo esc_attr( $post_type ); ?>">
		<option value="" <?php selected( '', $current, true ); ?>><?php printf( __( 'All %s', 'learndash-zapier' ), ucfirst( $plural ) ); ?></option>

		<?php foreach ( $options as $option ) : ?>

			<?php $option_id = esc_attr( $option->ID ); ?>

		<option value="<?php echo $option_id; ?>" <?php selected( $option_id, $current, true ); ?>><?php echo esc_attr( $option->post_title ); ?></option>

		<?php endforeach; ?>
	</select>

	<?php
	return ob_get_clean();
}

function ld_zapier_get_trigger_events() {
	$events = array(
		'course' => array(
			'enrolled_into_course',
			'course_completed',
		),
		'lesson' => array(
			'lesson_completed',
		),
		'topic' => array(
			'topic_completed',
		),
		'quiz' => array(
			'quiz_passed',
			'quiz_failed',
			'quiz_completed',
		),
	);

	return $events;
}