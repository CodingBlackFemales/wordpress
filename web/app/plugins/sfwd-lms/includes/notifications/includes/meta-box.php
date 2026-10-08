<?php
/**
 * Meta box functions.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 */

use LearnDash\Core\Utilities\Cast;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add meta box function
 */
function learndash_notifications_add_meta_boxes() {
	add_meta_box( 'ld-notifications-meta-box', __( 'Notification Settings', 'learndash' ), 'learndash_notifications_meta_box', 'ld-notification', 'advanced', 'high' );

	add_meta_box( 'ld-notifications-shortcodes-meta-box', __( 'Available Shortcodes', 'learndash' ), 'learndash_notifications_shortcodes_meta_box', 'ld-notification', 'advanced', 'low' );
}

/**
 * Output meta box HTML
 *
 * @param array $args Args array passed from add_meta_box function
 */
function learndash_notifications_meta_box( $args ) {
	$settings = learndash_notifications_get_meta_box_settings();
	$post_id  = get_the_ID();
	?>

	<style type="text/css">
		#minor-publishing-actions,
		#misc-publishing-actions {
			display: none;
		}
	</style>

	<?php

	echo '<div class="sfwd sfwd_options inputs-wrapper ld_notifications_metabox_settings">';
	wp_nonce_field( 'learndash_notifications_meta_box', 'learndash_notifications_nonce' );

	$function = 'learndash_notifications_meta_box_output';

	foreach ( $settings as $key => $setting ) {
		if ( $setting['type'] === 'trigger_objects' ) {
			learndash_notifications_output_object_field();
		} else {
			echo $function( $key, $setting );
		}
	}

	echo '</div>';
}

/**
 * Output trigger object field.
 *
 * @since 5.2.0
 *
 * @param string $context Optional. The context the fields are output in, passed through to each field. Default empty string.
 *
 * @return void
 */
function learndash_notifications_output_object_field( $context = '' ): void {
	$function = 'learndash_notifications_meta_box_output';
	$settings = learndash_notifications_get_meta_box_settings();

	foreach ( $settings['trigger_objects']['fields'] as $key => $field ) {
		$function( $key, $field, true, $context );
	}
}

/**
 * Output field label.
 *
 * @since 5.2.0
 *
 * @param string $key   Field key.
 * @param array  $field Field arguments.
 *
 * @return void
 */
function learndash_notifications_output_field_label( $key, $field ): void {
	?>
	<span class="sfwd_option_label">
		<a
			class="sfwd_help_text_link" style="cursor:pointer;"
			title="<?php esc_attr_e( 'Click for Help!', 'learndash' ); ?>"
		>
			<img src="<?php echo LEARNDASH_LMS_PLUGIN_URL . 'assets/images/question.png'; ?>">
			<label class="sfwd_label textinput"><?php echo $field['title']; ?></label>
		</a>
	</span>
	<?php
}

/**
 * Get condition field.
 *
 * @since 5.2.0
 *
 * @param int|string           $key             Optional. The sequence number of the condition. Default 1.
 * @param array<string, mixed> $condition_value Optional. The saved condition values, keyed by field key. Default empty array.
 *
 * @return string
 */
function learndash_notifications_get_condition_field( $key = 1, $condition_value = [] ): string {
	$key             = Cast::to_string( $key );
	$fields          = learndash_notifications_get_object_fields();
	$condition_types = learndash_notifications_get_conditions();

	ob_start();
	?>

	<h4 class="title" data-title-sequence="<?php echo esc_attr( $key ); ?>">
	<?php
		printf(
			// translators: %s: Sequence number of the condition, wrapped in a span tag.
			esc_html_x( 'Condition %s', 'The sequence of condition.', 'learndash' ),
			'<span class="title-sequence-number">#' . esc_html( $key ) . '</span>'
		);
	?>
	<span class="remove-condition dashicons dashicons-no-alt"></span></h4>
	<div class="condition accordion-item inputs-wrapper" data-sequence="<?php echo esc_attr( $key ); ?>">
		<?php
			learndash_notifications_meta_box_output(
				'condition_type',
				[
					'type'          => 'dropdown',
					'title'         => __( 'Condition', 'learndash' ),
					'help_text'     => __( 'Condition type to check.', 'learndash' ),
					'class'         => 'condition-input-wrapper',
					'hide'          => 0,
					'disabled'      => 0,
					'value'         => [
						'' => __( '-- Select Condition --', 'learndash' ),
					] + $condition_types,
					'current_value' => $condition_value['condition_type'] ?? null,
				],
				true,
				'condition',
				$key
			);

		foreach ( $fields as $field_key => $field ) {
			$field['current_value'] = $condition_value[ $field_key ] ?? null;
			learndash_notifications_meta_box_output( $field_key, $field, true, 'condition', $key );
		}
		?>
	</div>

	<?php
	return ob_get_clean();
}

/**
 * Output available shortcodes meta box HTML
 */
function learndash_notifications_shortcodes_meta_box() {
	?>

	<div class="shortcodes-instruction no-instruction 0"> <?php esc_html_e( 'Select an email trigger on the notification settings above to see available shortcodes.', 'learndash' ); ?></div>

	<?php $instructions = learndash_notifications_get_shortcodes_instructions(); ?>
	<?php foreach ( $instructions as $class => $instruction ) : ?>

		<div class="shortcodes-instruction <?php echo $class; ?>">
			<div class="header-text">
				<?php esc_html_e( 'Here are the available shortcodes for the email trigger selected:', 'learndash' ); ?>

			</div>

			<?php foreach ( $instruction as $shortcode => $label ) : ?>

				<div class="shortcode-wrapper">
					<span class="shortcode"><?php echo $shortcode; ?></span> : <span
							class="label"><?php echo $label; ?></span>
				</div>

			<?php endforeach; ?>

			<?php if ( $class == 'complete_course' ) : ?>

				<div class="additional-help-text <?php echo $class; ?>" style="display:none;">
					<?php echo wp_kses( __( '<em>Cumulative</em> is average for all quizzes of the course.', 'learndash' ), [ 'em' => [] ] ); ?>
					<br/>
					<?php echo wp_kses( __( '<em>Aggregate</em> is sum for all quizzes of the course.', 'learndash' ), [ 'em' => [] ] ); ?>
				</div>

			<?php endif; ?>
		</div>

	<?php endforeach; ?>

	<?php
}

add_action( 'add_meta_boxes', 'learndash_notifications_add_meta_boxes', 1, 2 );

/**
 * Get LearnDash Notifications metabox settings
 *
 * @return array Metabox settings
 */
function learndash_notifications_get_meta_box_settings() {
	$settings = [
		'trigger'                    => [
			'type'      => 'dropdown',
			'title'     => __( 'Email trigger', 'learndash' ),
			'help_text' => __( 'When this email will be sent.', 'learndash' ),
			'disabled'  => 1,
			'hide'      => 0,
			'value'     => [
				0 => __( '-- Select an Email Trigger --', 'learndash' ),
			] + learndash_notifications_get_triggers(),
		],
		'exclude_pre_ordered_course' => [
			'type'      => 'checkbox',
			'title'     => __( 'Exclude Pre-Ordered Course', 'learndash' ),
			'help_text' => __( 'Check to exclude pre-ordered course(s) from triggering this notification.', 'learndash' ),
			'disabled'  => 0,
			'hide'      => 1,
			'parent'    => [ 'enroll_course' ],
			'label'     => __( 'Exclude', 'learndash' ),
			'value'     => '1',
		],
		'trigger_objects'            => [
			'type'   => 'trigger_objects',
			'hide'   => 1,
			'fields' => learndash_notifications_get_object_fields(),
		],
		'conditions'                 => [
			'type'      => 'conditions',
			'title'     => __( 'Conditions', 'learndash' ),
			'help_text' => __( 'Conditions to check before notification is being sent.', 'learndash' ),
			'class'     => 'accordion',
			'disabled'  => 0,
			'hide'      => 0,
			'value'     => '',
		],
		'not_logged_in_days'         => [
			'type'       => 'text',
			'title'      => __( 'After how many days?', 'learndash' ),
			'help_text'  => __( 'Setting associated with the email trigger setting above.', 'learndash' ),
			'label'      => __( 'day(s)', 'learndash' ),
			'hide'       => 1,
			'hide_delay' => 1,
			'size'       => 2,
			'parent'     => 'not_logged_in',
		],
		'send_only_once'             => [
			'type'       => 'dropdown',
			'title'      => __( 'One time only', 'learndash' ),
			'help_text'  => __( 'Check if you want to send this notification only one time.', 'learndash' ),
			'hide'       => 1,
			'hide_delay' => 1,
			'size'       => 2,
			'parent'     => 'not_logged_in',
			'value'      => [
				1 => __( 'Yes', 'learndash' ),
				0 => __( 'No', 'learndash' ),
			],
		],
		'course_expires_days'        => [
			'type'       => 'text',
			'title'      => __( 'Before how many days?', 'learndash' ),
			'help_text'  => __( 'Setting associated with the email trigger setting above.', 'learndash' ),
			'label'      => __( 'day(s)', 'learndash' ),
			'hide'       => 1,
			'hide_delay' => 1,
			'size'       => 2,
			'parent'     => 'course_expires',
		],
		'course_expires_after_days'  => [
			'type'       => 'text',
			'title'      => __( 'After how many days?', 'learndash' ),
			'help_text'  => __( 'Setting associated with the email trigger setting above.', 'learndash' ),
			'label'      => __( 'day(s)', 'learndash' ),
			'hide'       => 1,
			'hide_delay' => 1,
			'size'       => 2,
			'parent'     => 'course_expires_after',
		],
		'recipient'                  => [
			'type'      => 'checkbox',
			'title'     => __( 'Recipient', 'learndash' ),
			'help_text' => __( 'Recipient of this email.', 'learndash' ),
			'hide'      => 0,
			'hide_on'   => [],
			'value'     => learndash_notifications_get_default_recipients(),
		],
		'bcc'                        => [
			'type'      => 'text',
			'title'     => __( 'Additional Recipient', 'learndash' ),
			'help_text' => __( 'Additional email addresses (separated by comma) that will also get this notification', 'learndash' ),
			'hide'      => 0,
			'size'      => 50,
		],
		'delay'                      => [
			'type'      => 'text',
			'title'     => __( 'Delay', 'learndash' ),
			'help_text' => __( 'How long this email is delayed after the trigger occurs (default is 0).', 'learndash' ),
			'hide'      => 0,
			'disabled'  => 0,
			'hide_on'   => [ 'not_logged_in', 'course_expires', 'course_expires_after' ],
			'default'   => 0,
			'size'      => 2,
		],
		'delay_unit'                 => [
			'type'      => 'dropdown',
			'title'     => __( 'Delay unit', 'learndash' ),
			'help_text' => __( 'How long the notification will be delayed for. Specify minutes, hours or days.', 'learndash' ),
			'disabled'  => 0,
			'default'   => 'days',
			'hide'      => 0,
			'hide_on'   => [ 'not_logged_in', 'course_expires', 'course_expires_after' ],
			'value'     => [
				'minutes' => __( 'Minutes', 'learndash' ),
				'hours'   => __( 'Hours', 'learndash' ),
				'days'    => __( 'Days', 'learndash' ),
			],
		],
	];

	if ( version_compare( LEARNDASH_VERSION, '4.6.0.1', '<=' ) ) { // @phpstan-ignore-line -- False positive.
		unset( $settings['exclude_pre_ordered_course'] );
	}

	return apply_filters( 'learndash_notification_settings', $settings );
}

/**
 * Output meta box input HTML.
 *
 * @since 5.2.0
 *
 * @param string          $key           Setting key.
 * @param array           $args          Args array of metabox setting.
 * @param bool            $echo          Whether to print the HTML instead of returning it.
 * @param string          $context       Context the setting is output in.
 * @param int|string|null $condition_key Sequence number of the condition the setting belongs to, if any.
 *
 * @return string String of HTML output.
 */
function learndash_notifications_meta_box_output( $key, $args, $echo = false, $context = '', $condition_key = null ): string {
	$screen = get_current_screen();

	$hide    = $args['hide'] == 1 ? 'display: none;' : '';
	$hide_on = '';
	if ( isset( $args['hide_on'] ) ) {
		foreach ( $args['hide_on'] as $class ) {
			$hide_on .= ' hide_on hide_on_' . $class . ' ';
		}
	}

	$parent_class = '';
	if ( isset( $args['parent'] ) && ! is_array( $args['parent'] ) ) {
		$parent_class = $args['parent'] ? $args['parent'] . ' child-input ' : '';
	} elseif ( isset( $args['parent'] ) && is_array( $args['parent'] ) ) {
		$parent_class = '';
		foreach ( $args['parent'] as $parent_class_name ) {
			$parent_class .= $parent_class_name . ' ';
		}
		$parent_class .= ' child-input';
	}

	$class = '';
	if ( isset( $args['class'] ) && ! is_array( $args['class'] ) ) {
		$class = $args['class'] ? $args['class'] . ' ' : ' ';
	} elseif ( isset( $args['class'] ) && is_array( $args['class'] ) ) {
		$class = '';
		foreach ( $args['class'] as $class_name ) {
			$class .= $class_name . ' ';
		}
	}

	// Dynamic options.
	if ( isset( $args['dynamic_options'] ) && $args['dynamic_options'] ) {
		$class .= ' dynamic-options';
	}

	// Disabled child.
	if ( isset( $args['disabled_child'] ) && $args['disabled_child'] ) {
		$class .= ' disabled-child';
	}

	// Multiple attribute.
	$multiple = '';
	if ( ! empty( $args['multiple'] ) && $args['multiple'] ) {
		$multiple = 'multiple="multiple" ';
	}

	// Disabled attribute.
	if ( $screen->post_type == 'ld-notification' && $screen->action != 'add' && isset( $args['disabled'] ) && $args['disabled'] == '1' ) {
		$disabled = 'disabled="disabled" ';
	} else {
		$disabled = '';
	}

	if ( $context === 'condition' ) {
		$input_name = '_ld_notifications_conditions[' . $condition_key . '][' . $key . ']';
	} else {
		$input_name = '_ld_notifications_' . $key;
	}

	switch ( $key ) {
		case 'group_id':
			$type_label = learndash_get_custom_label( 'group' );
			break;

		case 'course_id':
			$type_label = learndash_get_custom_label( 'course' );
			break;

		case 'lesson_id':
			$type_label = learndash_get_custom_label( 'lesson' );
			break;

		case 'topic_id':
			$type_label = learndash_get_custom_label( 'topic' );
			break;

		case 'quiz_id':
			$type_label = learndash_get_custom_label( 'quiz' );
			break;

		default:
			$type_label = '';
			break;
	}

	$post_id = get_the_ID();

	$key_value = $args['current_value'] ?? get_post_meta( $post_id, $input_name, true );
	$key_value = maybe_unserialize( $key_value );

	$selected = '';

	if ( 'delay_unit' === $key && empty( $key_value ) ) {
		$key_value = 'days';
	} elseif ( in_array(
		$input_name,
		[
			'_ld_notifications_group_id',
			'_ld_notifications_course_id',
			'_ld_notifications_lesson_id',
			'_ld_notifications_topic_id',
			'_ld_notifications_quiz_id',
		]
	) ) {
		// Handle legacy value to work with new multi values.
		$key_value = $key_value === '0' ? 'all' : $key_value;
		$key_value = ! is_array( $key_value ) ? [ $key_value ] : $key_value;
	}

	if ( is_array( $key_value ) ) {
		$key_value = is_array( $key_value ) ? $key_value : [];
		$key_value = in_array( 'all', $key_value, true ) ? array_unique( array_merge( [ 'all' ], $key_value ) ) : $key_value;

		foreach ( $key_value as $value ) {
			if ( is_string( $value ) && strlen( $value ) && $value === '0' ) {
				$value = 'all';
			}

			// Set pre-selected value.
			if ( $value === 'all' ) {
				$selected .= '<option value="' . $value . '" selected="selected">' . wp_sprintf(
					// translators: Post type label.
					__( 'Any %s', 'learndash' ),
					$type_label
				) . '</option>';
			} elseif ( is_numeric( $value ) && $value > 0 ) {
				$pre_selected_object = get_post( $value );

				$selected .= '<option value="' . $value . '" selected="selected">' . $pre_selected_object->post_title . '</option>';
			}
		}
	}

	$default    = isset( $args['default'] ) ? $args['default'] : '';
	$hide_delay = isset( $args['hide_delay'] ) && $args['hide_delay'] == 1 ? 'hide-delay' : '';

	$hide_empty_select = $screen->post_type == 'ld-notification' && $screen->action != 'add' && empty( $key_value ) && $args['type'] == 'dropdown' && strpos( $input_name, '_id' ) !== false ? 'hide-empty-select' : '';

	// Handle trigger object arg value.
	if ( isset( $args['trigger_object'] ) && (bool) $args['trigger_object'] ) {
		$input_name = $input_name . '[]';
	}

	if ( $args['type'] === 'conditions' ) {
		$conditions = get_post_meta( $post_id, '_ld_notifications_conditions', true );
	}

	ob_start();
	?>

	<div class="sfwd_input <?php echo $parent_class; ?> <?php echo $class; ?> <?php echo $hide_delay; ?> <?php echo $hide_on; ?> <?php echo $hide_empty_select; ?>"
		id="<?php echo $key; ?>" style="<?php echo $hide; ?>">
		<span class="sfwd_option_label">
			<a
				class="sfwd_help_text_link"
				style="cursor:pointer;"
					title="<?php esc_attr_e( 'Click for Help!', 'learndash' ); ?>"
			>
				<img src="<?php echo LEARNDASH_LMS_PLUGIN_URL . 'assets/images/question.png'; ?>">
				<label class="sfwd_label textinput"><?php echo $args['title']; ?></label>
			</a>
		</span>
		<span class="sfwd_option_input">
			<div class="sfwd_option_div">
				<?php if ( $args['type'] === 'dropdown' ) : ?>

					<select name="<?php echo esc_attr( $input_name ); ?>" <?php echo esc_attr( $disabled . $multiple ); ?>>

						<?php foreach ( $args['value'] as $value => $title ) : ?>
							<?php $value_selected = is_array( $key_value ) ? ( in_array( $value, $key_value, true ) ? ' selected="selected" ' : '' ) : ( selected( $value, $key_value, false ) ); ?>

							<option value="<?php echo $value; ?>" <?php echo $value_selected; ?>><?php echo $title; ?></option>
						<?php endforeach; ?>

						<?php echo $selected; ?>

					</select>

				<?php elseif ( $args['type'] === 'text' ) : ?>

					<?php $value = ! empty( $key_value ) ? $key_value : $default; ?>

					<input type="text" size="<?php echo $args['size']; ?>" name="<?php echo $input_name; ?>"
							value="<?php echo $value; ?>" style="width: initial;" <?php echo $disabled; ?>>

					<?php if ( isset( $args['label'] ) ) : ?>
						<label><?php echo $args['label']; ?></label>
					<?php endif; ?>

				<?php elseif ( $args['type'] === 'checkbox' ) : ?>

					<?php $cb_input_name = is_array( $args['value'] ) ? $input_name . '[]' : $input_name; ?>

					<?php if ( is_array( $args['value'] ) && count( $args['value'] ) > 1 ) : ?>

						<?php foreach ( $args['value'] as $value => $label ) : ?>
							<?php $key_value = empty( $key_value ) ? [] : $key_value; ?>

							<?php $checked = in_array( $value, $key_value ) ? 'checked="checked"' : ''; ?>

							<input type="checkbox" name="<?php echo $cb_input_name; ?>" id="<?php echo $value; ?>"
									value="<?php echo $value; ?>" <?php echo $checked; ?> <?php echo $disabled; ?>>
							<label for="<?php echo $value; ?>"><?php echo $label; ?></label><br/>

						<?php endforeach; ?>

					<?php else : ?>

						<?php $checked = checked( $args['value'], $key_value, false ); ?>

						<input type="checkbox" name="<?php echo $cb_input_name; ?>" id="<?php echo $args['value']; ?>"
								value="<?php echo $args['value']; ?>" <?php echo $checked; ?> <?php echo $disabled; ?>>
						<label for="<?php echo $args['value']; ?>"><?php echo $args['label']; ?></label><br/>

					<?php endif; ?>

				<?php elseif ( $args['type'] === 'conditions' ) : ?>
					<div class="conditions-wrapper accordion-wrapper">
						<?php if ( ! empty( $conditions ) && is_array( $conditions ) ) : ?>
							<?php foreach ( $conditions as $key => $condition ) : ?>
								<?php echo learndash_notifications_get_condition_field( $key, $condition ); ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div class="add-condition">
						<span class="dashicons dashicons-plus-alt2"></span>
					</div>
				<?php endif; ?>
			</div>
			<div class="sfwd_help_text_div" style="display: none;" id="learndash-notifications_<?php echo $key; ?>_tip">
				<label class="sfwd_help_text"><?php echo $args['help_text']; ?></label>
			</div>
		</span>
		<p style="clear:left"></p>
	</div>

	<?php
	$output = ob_get_clean();

	if ( $echo ) {
		echo $output;
	}

	return $output;
}

/**
 * Save notifications meta box value
 *
 * @param int $post_id ID of post created/updated
 */
function learndash_notifications_save_meta_box( $notification_id ) {
	$notification = get_post( $notification_id );

	if ( ! isset( $_POST['learndash_notifications_nonce'] ) ) {
		return;
	}

	if ( $notification->post_type != 'ld-notification' || ! check_admin_referer( 'learndash_notifications_meta_box', 'learndash_notifications_nonce' ) ) {
		return;
	}

	// Update recipient post meta if all unchecked.
	if ( ! isset( $_POST['_ld_notifications_recipient'] ) ) {
		update_post_meta( $notification_id, '_ld_notifications_recipient', [] );
	}

	// Delete exclude pre order course meta if it's not set.
	if ( ! isset( $_POST['_ld_notifications_exclude_pre_ordered_course'] ) ) {
		delete_post_meta( $notification_id, '_ld_notifications_exclude_pre_ordered_course' );
	}

	foreach ( $_POST as $key => $value ) {
		if ( strpos( $key, '_ld_notifications' ) === false ) {
			continue;
		}

		if ( is_array( $value ) ) {
			array_walk_recursive(
				$value,
				function ( &$v, $k ) {
					return $v = sanitize_text_field( $v );
				}
			);
		} else {
			$value = sanitize_text_field( $value );
		}

		update_post_meta( $notification_id, $key, $value );
	}

	if ( in_array(
		$_POST['_ld_notifications_trigger'],
		[
			'enroll_course',
			'complete_course',
			'course_expires',
			'course_expires_after',
		]
	) ) {
		update_post_meta( $notification_id, '_ld_notifications_lesson_id', [] );
		update_post_meta( $notification_id, '_ld_notifications_topic_id', [] );
		update_post_meta( $notification_id, '_ld_notifications_quiz_id', [] );
	} elseif ( in_array( $_POST['_ld_notifications_trigger'], [ 'complete_lesson', 'lesson_available' ] ) ) {
		$course_id = (array) $_POST['_ld_notifications_course_id'];
		update_post_meta( $notification_id, '_ld_notifications_course_id', $course_id );
		update_post_meta( $notification_id, '_ld_notifications_topic_id', [] );
		update_post_meta( $notification_id, '_ld_notifications_quiz_id', [] );
	} elseif ( in_array( $_POST['_ld_notifications_trigger'], [ 'complete_topic' ] ) ) {
		$course_id = (array) $_POST['_ld_notifications_course_id'];
		$lesson_id = (array) $_POST['_ld_notifications_lesson_id'];
		update_post_meta( $notification_id, '_ld_notifications_course_id', $course_id );
		update_post_meta( $notification_id, '_ld_notifications_lesson_id', $lesson_id );
		update_post_meta( $notification_id, '_ld_notifications_quiz_id', [] );
	} elseif ( in_array(
		$_POST['_ld_notifications_trigger'],
		[
			'pass_quiz',
			'fail_quiz',
			'submit_quiz',
			'complete_quiz',
		]
	) ) {
		$course_id = (array) $_POST['_ld_notifications_course_id'];
		$lesson_id = (array) $_POST['_ld_notifications_lesson_id'];
		$topic_id  = (array) $_POST['_ld_notifications_topic_id'];

		update_post_meta( $notification_id, '_ld_notifications_course_id', $course_id );
		update_post_meta( $notification_id, '_ld_notifications_lesson_id', $lesson_id );
		update_post_meta( $notification_id, '_ld_notifications_topic_id', $topic_id );
	}
}

add_action( 'save_post', 'learndash_notifications_save_meta_box' );

/**
 * Returns the available shortcodes for every email trigger, keyed by trigger.
 *
 * @since 5.2.0
 *
 * @return array<string,array<string,string>> Shortcode instructions keyed by trigger.
 */
function learndash_notifications_get_shortcodes_instructions() {
	$user_shortcode = [
		'[ld_notifications field="user" show="username"]'  => __( 'Display user\'s username.', 'learndash' ),
		'[ld_notifications field="user" show="email"]'     => __( 'Display user\'s email.', 'learndash' ),
		'[ld_notifications field="user" show="display_name"]' => __( 'Display user\'s display name.', 'learndash' ),
		'[ld_notifications field="user" show="first_name"]' => __( 'Display user\'s first name.', 'learndash' ),
		'[ld_notifications field="user" show="last_name"]' => __( 'Display user\'s last name.', 'learndash' ),
	];

	$group_basic_shortcode = [
		'[ld_notifications field="group" show="title"]' => __( 'Display group title.', 'learndash' ),
		'[ld_notifications field="group" show="url"]'   => __( 'Display group URL.', 'learndash' ),
	];

	$course_basic_shortcode = [
		'[ld_notifications field="course" show="title"]' => __( 'Display course title.', 'learndash' ),
		'[ld_notifications field="course" show="url"]'   => __( 'Display course URL.', 'learndash' ),
	];

	$course_advanced_shortcode = [
		'[ld_notifications field="course" show="completed_on" format=""]'  => __( 'Display time when course is completed. <a href="http://php.net/manual/en/function.date.php" target="_blank">Click here</a> to see format options.', 'learndash' ),
		'[ld_notifications field="course" show="cumulative_score"]'        => __( 'Display average points scored across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="cumulative_points"]'       => __( 'Display average points got across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="cumulative_total_points"]' => __( 'Display average total points got across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="cumulative_percentage"]'   => __( 'Display average correct answers percentage across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="cumulative_timespent"]'    => __( 'Display average time spent across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="cumulative_count"]'        => __( 'Display average correct answer counted across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="aggregate_percentage"]'    => __( 'Display sum of correct answers percentage across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="aggregate_score"]'         => __( 'Display sum of points scored across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="aggregate_points"]'        => __( 'Display sum of points got across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="aggregate_total_points"]'  => __( 'Display sum of total points got across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="aggregate_timespent"]'     => __( 'Display sum of time spent across all quizzes on the course.', 'learndash' ),
		'[ld_notifications field="course" show="aggregate_count"]'         => __( 'Display sum of correct answer counted across all quizzes on the course.', 'learndash' ),
	];

	$lesson_shortcode = [
		'[ld_notifications field="lesson" show="title"]' => __( 'Display lesson title.', 'learndash' ),
		'[ld_notifications field="lesson" show="url"]'   => __( 'Display lesson URL.', 'learndash' ),
	];

	$topic_shortcode = [
		'[ld_notifications field="topic" show="title"]' => __( 'Display topic title.', 'learndash' ),
		'[ld_notifications field="topic" show="url"]'   => __( 'Display topic URL.', 'learndash' ),
	];

	$quiz_shortcode = [
		'[ld_notifications field="quiz" show="url"]'       => __( 'Display quiz URL.', 'learndash' ),
		'[ld_notifications field="quiz" show="timestamp" format=""]' => __( 'Display time when the quiz is taken. <a href="http://php.net/manual/en/function.date.php" target="_blank">Click here</a> to see format options.', 'learndash' ),
		'[ld_notifications field="quiz" show="percentage"]' => __( 'Display correct percentage of the quiz.', 'learndash' ),
		'[ld_notifications field="quiz" show="pass"]'      => __( 'Display whether the user passes the quiz or not. Display "Yes" or "No".', 'learndash' ),
		'[ld_notifications field="quiz" show="quiz_title"]' => __( 'Display quiz title.', 'learndash' ),
		'[ld_notifications field="quiz" show="course_title"]' => __( 'Display course title that quiz belongs to.', 'learndash' ),
		'[ld_notifications field="quiz" show="timespent"]' => __( 'Display how long is taken to complete the quiz.', 'learndash' ),
		'[ld_notifications field="quiz" show="categories"]' => __( 'Display quiz result based on categories.', 'learndash' ),
	];

	$essay_shortcode = [
		'[ld_notifications field="essay" show="points_earned"]' => __( 'Display total points earned.', 'learndash' ),
		'[ld_notifications field="essay" show="points_total"]'  => __( 'Display total points possible for the essay.', 'learndash' ),
	];

	$assignment_shortcode = [
		'[ld_notifications field="assignment" show="title"]'        => __( 'Display assignment title.', 'learndash' ),
		'[ld_notifications field="assignment" show="file_name"]'    => __( 'Display assignment file name.', 'learndash' ),
		'[ld_notifications field="assignment" show="file_link"]'    => __( 'Display assignment file link.', 'learndash' ),
		'[ld_notifications field="assignment" show="lesson_title"]' => __( 'Display lesson title that the assignment belongs to.', 'learndash' ),
		'[ld_notifications field="assignment" show="lesson_type"]'  => __( 'Display lesson type that the assignment belongs to.', 'learndash' ),
	];

	$shortcode_groupings = apply_filters(
		'learndash_notifications_shortcode_instruction_groupings',
		[
			'user'            => $user_shortcode,
			'group_basic'     => $group_basic_shortcode,
			'course_basic'    => $course_basic_shortcode,
			'course_advanced' => $course_advanced_shortcode,
			'lesson'          => $lesson_shortcode,
			'topic'           => $topic_shortcode,
			'quiz'            => $quiz_shortcode,
			'essay'           => $essay_shortcode,
			'assignment'      => $assignment_shortcode,
		]
	);

	$instructions = [
		'enroll_group'         => array_merge( $shortcode_groupings['user'], $shortcode_groupings['group_basic'] ),
		'enroll_course'        => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'] ),
		'complete_course'      => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'], $shortcode_groupings['course_advanced'] ),
		'complete_lesson'      => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'], $shortcode_groupings['lesson'] ),
		'lesson_available'     => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'], $shortcode_groupings['lesson'] ),
		'complete_topic'       => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'], $shortcode_groupings['lesson'], $shortcode_groupings['topic'] ),
		'pass_quiz'            => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'], $shortcode_groupings['lesson'], $shortcode_groupings['topic'], $shortcode_groupings['quiz'] ),
		'fail_quiz'            => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'], $shortcode_groupings['lesson'], $shortcode_groupings['topic'], $shortcode_groupings['quiz'] ),
		'submit_quiz'          => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'], $shortcode_groupings['lesson'], $shortcode_groupings['topic'], $shortcode_groupings['quiz'] ),
		'complete_quiz'        => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'], $shortcode_groupings['lesson'], $shortcode_groupings['topic'], $shortcode_groupings['quiz'] ),
		'submit_essay'         => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'], $shortcode_groupings['lesson'], $shortcode_groupings['essay'] ),
		'essay_graded'         => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'], $shortcode_groupings['lesson'], $shortcode_groupings['essay'] ),
		'upload_assignment'    => array_merge( $shortcode_groupings['user'], $shortcode_groupings['assignment'] ),
		'approve_assignment'   => array_merge( $shortcode_groupings['user'], $shortcode_groupings['assignment'] ),
		'not_logged_in'        => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'] ),
		'course_expires'       => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'] ),
		'course_expires_after' => array_merge( $shortcode_groupings['user'], $shortcode_groupings['course_basic'] ),
	];

	return apply_filters( 'learndash_notifications_shortcodes_instructions', $instructions, $shortcode_groupings );
}
