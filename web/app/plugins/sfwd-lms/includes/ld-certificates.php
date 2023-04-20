<?php
/**
 * Certificate functions
 *
 * @since 2.1.0
 *
 * @package LearnDash\Certificates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets certificate details.
 *
 * Return a link to the certificate and certificate threshold.
 *
 * @since 2.1.0
 *
 * @param int      $post_id      Post ID.
 * @param int|null $cert_user_id Optional. ID of the user to get certificate details for. Default null.
 *
 * @return array Returns certificate details.
 */
function learndash_certificate_details( $post_id, $cert_user_id = null ) {
	$cert_details = array();

	$cert_user_id = ! empty( $cert_user_id ) ? intval( $cert_user_id ) : get_current_user_id();

	if ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) {
		$view_user_id = get_current_user_id();
	} else {
		$view_user_id = $cert_user_id;
	}

	$certificate_link = '';
	$post             = get_post( $post_id );

	if ( ( $post instanceof WP_Post ) && ( 'sfwd-quiz' === $post->post_type ) ) {

		$meta = get_post_meta( $post_id, '_sfwd-quiz', true );
		if ( is_array( $meta ) && ! empty( $meta ) ) {

			if ( ( isset( $meta['sfwd-quiz_threshold'] ) ) && ( '' !== $meta['sfwd-quiz_threshold'] ) ) {
				$certificate_threshold = $meta['sfwd-quiz_threshold'];
			} else {
				$certificate_threshold = '0.8';
			}

			if ( ( isset( $meta['sfwd-quiz_certificate'] ) ) && ( ! empty( $meta['sfwd-quiz_certificate'] ) ) ) {
				$certificate_post = intval( $meta['sfwd-quiz_certificate'] );
				$certificate_link = get_permalink( $certificate_post );

				if ( ! empty( $certificate_link ) ) {

					$cert_query_args = array(
						'quiz' => $post->ID,
					);

					// We add the user query string key/value if the viewing user is an admin. This
					// allows the admin to view other user's certificated.
					if ( ( $cert_user_id != $view_user_id ) && ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) ) {
						$cert_query_args['user'] = $cert_user_id;
					}
					$cert_query_args['cert-nonce'] = wp_create_nonce( $post->ID . $cert_user_id . $view_user_id );

					$certificate_link = add_query_arg( $cert_query_args, $certificate_link );
				}

				/**
				 * Filters certificate details link.
				 *
				 * @param string $certificate_link Certificate Link.
				 * @param int    $certificate_post Certificate Post.
				 * @param int    $post_id         Post ID.
				 * @param int    $user_id         User ID.
				 */
				$certificate_link = apply_filters( 'learndash_certificate_details_link', $certificate_link, $certificate_post, $post->ID, $cert_user_id );

				$cert_details = array(
					'certificateLink'       => $certificate_link,
					'certificate_threshold' => $certificate_threshold,
				);
			}
		}
	}

	return $cert_details;
}

/**
 * Gets the course certificate link for the user.
 *
 * @since 2.1.0
 *
 * @param int      $course_id    Course ID.
 * @param int|null $cert_user_id Optional. ID of the user to get certificate link for. Default null.
 *
 * @return string Course certificate link.
 */
function learndash_get_course_certificate_link( $course_id, $cert_user_id = null ) {
	$cert_user_id = ! empty( $cert_user_id ) ? intval( $cert_user_id ) : get_current_user_id();

	if ( ( empty( $course_id ) ) || ( empty( $cert_user_id ) ) ) {
		return '';
	}

	$certificate_id = learndash_get_setting( $course_id, 'certificate' );
	if ( empty( $certificate_id ) ) {
		return '';
	}

	if ( ( learndash_get_post_type_slug( 'certificate' ) !== get_post_type( $certificate_id ) ) ) {
		return '';
	}

	if ( ( learndash_get_post_type_slug( 'course' ) !== get_post_type( $course_id ) ) ) {
		return '';
	}

	$course_status = learndash_course_status( $course_id, $cert_user_id, true );
	if ( 'completed' !== $course_status ) {
		return '';
	}

	if ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) {
		$view_user_id = get_current_user_id();
	} else {
		$view_user_id = $cert_user_id;
	}

	$cert_query_args = array(
		'course_id' => $course_id,
	);

	// We add the user query string key/value if the viewing user is an admin. This
	// allows the admin to view other user's certificated.
	if ( ( $cert_user_id != $view_user_id ) && ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) ) {
		$cert_query_args['user'] = $cert_user_id;
	}
	$cert_query_args['cert-nonce'] = wp_create_nonce( $course_id . $cert_user_id . $view_user_id );

	$url = add_query_arg( $cert_query_args, get_permalink( $certificate_id ) );

	/**
	 * Filters course certificate link.
	 *
	 * Used in `learndash_get_course_certificate_link` function to filter value of certificate link.
	 *
	 * @param string $url       The course certificate link.
	 * @param int    $course_id Course ID.
	 * @param int    $user_id   The user ID for which the certificate link is generated.
	 */
	return apply_filters( 'learndash_course_certificate_link', $url, $course_id, $cert_user_id );
}



/**
 * Gets the certificate link if the certificate exists and quizzes are completed.
 *
 * @todo  consider for deprecation, not being used in plugin
 *
 * @since 2.1.0
 *
 * @param int      $quiz_id Quiz ID.
 * @param int|null $user_id Optional. User ID. Default null.
 *
 * @return string Certificate link HTML output or empty string.
 */
function learndash_get_certificate_link( $quiz_id, $user_id = null ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) || empty( $quiz_id ) ) {
		return '';
	}

	$c = learndash_certificate_details( $quiz_id, $user_id );

	if ( empty( $c['certificateLink'] ) ) {
		return '';
	}

	$usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );
	$usermeta = maybe_unserialize( $usermeta );

	if ( ! is_array( $usermeta ) ) {
		$usermeta = array();
	}

	foreach ( $usermeta as $quizdata ) {
		if ( ! empty( $quizdata['quiz'] ) && $quizdata['quiz'] == $quiz_id ) {
			if ( $c['certificate_threshold'] <= $quizdata['percentage'] / 100 ) {

				/**
				 * Filters label for certificate link.
				 *
				 * @param string $label Label for certificate link.
				 * @param int    $user_id         User ID.
				 * @param int    $quiz_id         Quiz ID.
				 */
				return '<a target="_blank" href="' . $c['certificateLink'] . '">' . apply_filters( 'ld_certificate_link_label', esc_html__( 'PRINT YOUR CERTIFICATE', 'learndash' ), $user_id, $quiz_id ) . '</a>';
			}
		}
	}

	return '';
}



/**
 * Disables the visual editor for the certificate post type edit screen.
 *
 * User should not be able to use the visual editor tab.
 * Fires on `wp_default_editor` hook.
 *
 * @global WP_Post $post Global post object.
 *
 * @since 2.1.0
 *
 * @param  array $return An array of editors. Accepts 'tinymce', 'html', 'test'.
 *
 * @return array|string $return The type of the editor.
 */
function learndash_disable_editor_on_certificate( $return ) {
	global $post;

	if ( is_admin() && ! empty( $post->post_type ) && 'sfwd-certificates' === $post->post_type ) {
		return 'html';
	}

	return $return;
}

add_filter( 'wp_default_editor', 'learndash_disable_editor_on_certificate', 1, 1 );



/**
 * Disables the visual editor for the certificate post type edit screen with javascript.
 *
 * @global WP_Post $post Global post object.
 *
 * User should not be able to use the visual editor tab.
 * Fires on `admin_footer` hook.
 *
 * @since 2.1.0
 */
function learndash_disable_editor_on_certificate_js() {
	global $post;
	if ( is_admin() && ! empty( $post->post_type ) && 'sfwd-certificates' === $post->post_type ) {
		?>
			<style type="text/css">
			a#content-tmce, a#content-tmce:hover, #qt_content_fullscreen, #insert-media-button{
				display:none;
			}
			</style>
			<script type="text/javascript">
			jQuery( function(){
				jQuery("#content-tmce").attr("onclick", null);
			});
			</script>
		<?php
	}
}

add_filter( 'admin_footer', 'learndash_disable_editor_on_certificate_js', 99 );

/**
 * Registers certificate options metabox.
 *
 * Fires on `add_meta_boxes` hook.
 *
 * @param WP_Post $post WP_Post object.
 */
function learndash_certificates_add_meta_box( $post ) {
	add_meta_box(
		'learndash_certificate_options',
		esc_html__( 'LearnDash Certificate Options', 'learndash' ),
		'learndash_certificate_options_metabox',
		'sfwd-certificates',
		'advanced',
		'high'
	);
}
add_action( 'add_meta_boxes', 'learndash_certificates_add_meta_box' );

/**
 * Displays certificate options metabox fields.
 *
 * @param WP_Post $certificate The current certificate WP_Post object being edited.
 */
function learndash_certificate_options_metabox( $certificate ) {

	$learndash_certificate_options_selected = get_post_meta( $certificate->ID, 'learndash_certificate_options', true );

	if ( ! is_array( $learndash_certificate_options_selected ) ) {
		if ( ! empty( $learndash_certificate_options_selected ) ) {
			$learndash_certificate_options_selected = array( $learndash_certificate_options_selected );
		} else {
			$learndash_certificate_options_selected = array();
		}
	}

	if ( ! isset( $learndash_certificate_options_selected['pdf_page_format'] ) ) {
		$learndash_certificate_options_selected['pdf_page_format'] = 'LETTER';
	}

	if ( ! isset( $learndash_certificate_options_selected['pdf_page_orientation'] ) ) {
		$learndash_certificate_options_selected['pdf_page_orientation'] = 'L';
	}

	wp_nonce_field( plugin_basename( __FILE__ ), 'learndash_certificates_nonce' );

	$learndash_certificate_options['pdf_page_format'] = array(
		'LETTER' => esc_html__( 'Letter / USLetter (default)', 'learndash' ),
		'A4'     => esc_html__( 'A4', 'learndash' ),
	);

	/**
	 * Filters certificate pdf page format.
	 *
	 * @param array $pdf_formats An array of pdf format details.
	 */
	$learndash_certificate_options['pdf_page_format'] = apply_filters( 'learndash_certificate_pdf_page_formats', $learndash_certificate_options['pdf_page_format'] );

	$learndash_certificate_options['pdf_page_orientation'] = array(
		'L' => esc_html__( 'Landscape (default)', 'learndash' ),
		'P' => esc_html__( 'Portrait', 'learndash' ),
	);

	/**
	 * Filters certificate pdf page orientations.
	 *
	 * @param array $pdf_orientations An array of pdf page orientations details.
	 */
	$learndash_certificate_options['pdf_page_orientation'] = apply_filters( 'learndash_certificate_pdf_page_orientations', $learndash_certificate_options['pdf_page_orientation'] );

	if ( ( is_array( $learndash_certificate_options['pdf_page_format'] ) ) && ( ! empty( $learndash_certificate_options['pdf_page_format'] ) ) ) {
		?>
		<p><label for="learndash_certificate_options_pdf_page_format"><?php esc_html_e( 'PDF Page Size', 'learndash' ); ?></label>
			<select id="learndash_certificate_options_pdf_page_format" name="learndash_certificate_options[pdf_page_format]">
			<?php
			foreach ( $learndash_certificate_options['pdf_page_format'] as $key => $label ) {
				?>
					<option <?php selected( $key, $learndash_certificate_options_selected['pdf_page_format'] ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $label ); ?></option>
											<?php
			}
			?>
			</select>
		</p>
		<?php
	}

	if ( ( is_array( $learndash_certificate_options['pdf_page_orientation'] ) ) && ( ! empty( $learndash_certificate_options['pdf_page_orientation'] ) ) ) {

		?>
		<p><label for="learndash_certificate_options_pdf_page_orientation"><?php esc_html_e( 'PDF Page Orientation', 'learndash' ); ?></label>
			<select id="learndash_certificate_options_pdf_page_orientation" name="learndash_certificate_options[pdf_page_orientation]">
			<?php
			foreach ( $learndash_certificate_options['pdf_page_orientation'] as $key => $label ) {
				?>
					<option <?php selected( $key, $learndash_certificate_options_selected['pdf_page_orientation'] ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $label ); ?></option>
											<?php
			}
			?>
			</select>
		</p>
		<?php
	}
}


/**
 * Saves certificate option metabox fields.
 *
 * Fires on `save_post` hook.
 *
 * @param int $post_id Current post ID being edited.
 */
function learndash_certificates_save_meta_box( $post_id ) {
	// verify if this is an auto save routine.
	// If it is our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times.
	if ( ! isset( $_POST['learndash_certificates_nonce'] ) || ! wp_verify_nonce( $_POST['learndash_certificates_nonce'], plugin_basename( __FILE__ ) ) ) {
		return;
	}

	if ( 'sfwd-certificates' != $_POST['post_type'] ) {
		return;
	}

	// Check permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$learndash_certificate_options = array();

	if ( ( isset( $_POST['learndash_certificate_options']['pdf_page_format'] ) ) && ( ! empty( $_POST['learndash_certificate_options']['pdf_page_format'] ) ) ) {
		$learndash_certificate_options['pdf_page_format'] = esc_attr( $_POST['learndash_certificate_options']['pdf_page_format'] );
	} else {
		$learndash_certificate_options['pdf_page_format'] = 'LETTER';
	}

	if ( ( isset( $_POST['learndash_certificate_options']['pdf_page_orientation'] ) ) && ( ! empty( $_POST['learndash_certificate_options']['pdf_page_orientation'] ) ) ) {
		$learndash_certificate_options['pdf_page_orientation'] = esc_attr( $_POST['learndash_certificate_options']['pdf_page_orientation'] );
	} else {
		$learndash_certificate_options['pdf_page_orientation'] = PDF_PAGE_ORIENTATION;
	}

	update_post_meta( $post_id, 'learndash_certificate_options', $learndash_certificate_options );
}
add_action( 'save_post', 'learndash_certificates_save_meta_box' );


/**
 * Certificate published/updated notice to replace the default notice that contains a link to a non-existent resource.
 *
 * Fires on `post_updated_messages` hook.
 *
 * @since 3.0.0
 *
 * @param array $messages An array of post updated messages.
 *
 * @return array Array of published/updated notice messages.
 */
function learndash_certificates_post_updated_messages( $messages ) {

	$post             = get_post();
	$post_type        = get_post_type( $post );
	$post_type_object = get_post_type_object( $post_type );

	$published_message = wp_kses_post(
		sprintf(
		// translators: quiz, course.
			_x(
				'Certificate published. <br /><br />To view the certificate, you must assign it to a %1$s or %2$s. <br />Once you complete the assigned milestone, you can generate the certificate. <br /><br />Click here to read more about this topic: <a href="https://www.learndash.com/support/docs/core/certificates/create-certificate/#previewing_certificates" target="_blank">Previewing Certificates</a>.',
				'placeholder: quiz, course',
				'learndash'
			),
			esc_html( learndash_get_custom_label_lower( 'quiz' ) ),
			esc_html( learndash_get_custom_label_lower( 'course' ) )
		)
	);

	$updated_message = wp_kses_post(
		sprintf(
		// translators: quiz, course.
			_x(
				'Certificate updated. <br /><br />To view the certificate, you must assign it to a %1$s or %2$s. <br />Once you complete the assigned milestone, you can generate the certificate. <br /><br />Click here to read more about this topic: <a href="https://www.learndash.com/support/docs/core/certificates/create-certificate/#previewing_certificates" target="_blank">Previewing Certificates</a>.',
				'placeholder: quiz, course',
				'learndash'
			),
			esc_html( learndash_get_custom_label_lower( 'quiz' ) ),
			esc_html( learndash_get_custom_label_lower( 'course' ) )
		)
	);

	$messages['sfwd-certificates'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => $updated_message,
		2  => esc_html__( 'Custom field updated.', 'learndash' ),
		3  => esc_html__( 'Custom field deleted.', 'learndash' ),
		4  => $updated_message,
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Certificate restored to revision from %s', 'learndash' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => $published_message,
		7  => esc_html__( 'Certificate saved.', 'learndash' ),
		8  => esc_html__( 'Certificate submitted.', 'learndash' ),
		9  => sprintf(
			// translators: placeholder: Post Date.
			esc_html_x( 'Certificate scheduled for: <strong>%s</strong>.', 'placeholder: Post Date', 'learndash' ),
			// translators: Publish box date format, see https://secure.php.net/date.
			date_i18n( __( 'M j, Y @ H:i', 'learndash' ), strtotime( $post->post_date ) )
		),
		10 => esc_html__( 'Certificate draft updated.', 'learndash' ),
	);

	return $messages;
}
add_filter( 'post_updated_messages', 'learndash_certificates_post_updated_messages' );

/**
 * Get Group certificate link for user
 *
 * @since 3.2.0
 *
 * @param  int $group_id     Group ID.
 * @param  int $cert_user_id User ID.
 *
 * @return string
 */
function learndash_get_group_certificate_link( $group_id, $cert_user_id = null ) {
	$cert_user_id = ! empty( $cert_user_id ) ? intval( $cert_user_id ) : get_current_user_id();

	if ( ( empty( $group_id ) ) || ( empty( $cert_user_id ) ) ) {
		return '';
	}

	$certificate_id = learndash_get_setting( $group_id, 'certificate' );
	if ( empty( $certificate_id ) ) {
		return '';
	}

	if ( ( learndash_get_post_type_slug( 'certificate' ) !== get_post_type( $certificate_id ) ) ) {
		return '';
	}

	if ( ( learndash_get_post_type_slug( 'group' ) !== get_post_type( $group_id ) ) ) {
		return '';
	}

	$group_status = learndash_get_user_group_status( $group_id, $cert_user_id, true );
	if ( 'completed' !== $group_status ) {
		return '';
	}

	if ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) {
		$view_user_id = get_current_user_id();
	} else {
		$view_user_id = $cert_user_id;
	}

	$cert_query_args = array(
		'group_id' => $group_id,
	);

	// We add the user query string key/value if the viewing user is an admin. This
	// allows the admin to view other user's certificated.
	if ( ( $cert_user_id != $view_user_id ) && ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) ) {
		$cert_query_args['user'] = $cert_user_id;
	}
	$cert_query_args['cert-nonce'] = wp_create_nonce( $group_id . $cert_user_id . $view_user_id );

	$url = add_query_arg( $cert_query_args, get_permalink( $certificate_id ) );

	/**
	 * Filter Group Certificate URL.
	 *
	 * @since 3.2.0
	 *
	 * @param string $url          Group Certificate URL.
	 * @param int    $group_id     Group ID.
	 * @param int    $cert_user_id User ID.
	 */
	return apply_filters( 'learndash_group_certificate_link', $url, $group_id, $cert_user_id );
}

/**
 * Display the PDF Certificate.
 *
 * This function was moved from template_redirect_access() in
 * includes/class-ld-cpt-instance.php
 *
 * @since 3.2.3
 */
function learndash_certificate_display() {
	if ( is_singular( learndash_get_post_type_slug( 'certificate' ) ) ) {
		if ( ( isset( $_GET['cert-nonce'] ) ) && ( ! empty( $_GET['cert-nonce'] ) ) ) {
			$certificate_post = get_post( get_the_ID() );

			// The viewing user ID.
			$view_user_id = get_current_user_id();

			/**
			 * Then determined for whom the certificate if for. A
			 * Group Leader or admin user can view other users.
			 */
			if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && ( ! empty( $_GET['user'] ) ) ) ) {
				$cert_user_id = absint( $_GET['user'] );
			} else {
				$cert_user_id = get_current_user_id();
			}

			if ( ( isset( $_GET['group_id'] ) ) && ( ! empty( $_GET['group_id'] ) ) ) {
				$group_id = absint( $_GET['group_id'] );
				if ( wp_verify_nonce( esc_attr( $_GET['cert-nonce'] ), $group_id . $cert_user_id . $view_user_id ) ) {
					$group_post = get_post( $group_id );
					if ( ( $group_post ) && ( is_a( $group_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'group' ) === $group_post->post_type ) ) {
						$group_certificate_post_id = learndash_get_setting( $group_post->ID, 'certificate' );
						if ( absint( $group_certificate_post_id ) === absint( $certificate_post->ID ) ) {
							$group_status = learndash_get_user_group_status( $group_id, $cert_user_id, true );
							if ( 'completed' === $group_status ) {
								if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( intval( $cert_user_id ) !== intval( $view_user_id ) ) ) {
									wp_set_current_user( $cert_user_id );
								}

								if ( has_action( 'learndash_tcpdf_init' ) ) {
									/**
									 * Fires on tcpdf initialization.
									 *
									 * @since 3.2.0
									 *
									 * @param array $args {
									 *     @type int $cert_id      Certificate Post ID.
									 *     @type int $cert_user_id User ID.
									 *     @type int $post_id      Related Course, Quiz post ID.
									 * } Args.
									 */
									do_action(
										'learndash_tcpdf_init',
										array(
											'cert_id' => $certificate_post->ID,
											'user_id' => $cert_user_id,
											'post_id' => $group_id,
										)
									);
								} else {
									require_once __DIR__ . '/ld-convert-post-pdf.php';
									learndash_certificate_post_shortcode(
										array(
											'cert_id' => $certificate_post->ID,
											'user_id' => $cert_user_id,
											'post_id' => $group_id,
										)
									);
								}
							}
						}
					}
				}
			} elseif ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
				$course_id = absint( $_GET['course_id'] );

				if ( wp_verify_nonce( esc_attr( $_GET['cert-nonce'] ), $course_id . $cert_user_id . $view_user_id ) ) {
					$course_post = get_post( $course_id );
					if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course_post->post_type ) ) {
						$course_certificate_post_id = learndash_get_setting( $course_post->ID, 'certificate' );
						if ( absint( $course_certificate_post_id ) === absint( $certificate_post->ID ) ) {
							$course_status = learndash_course_status( $course_id, $cert_user_id, true );
							if ( 'completed' === $course_status ) {
								if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( intval( $cert_user_id ) !== intval( $view_user_id ) ) ) {
									wp_set_current_user( $cert_user_id );
								}

								/** This filter is documented in includes/class-ld-cpt-instance.php */
								if ( has_action( 'learndash_tcpdf_init' ) ) {
									do_action(
										'learndash_tcpdf_init',
										array(
											'cert_id' => $certificate_post->ID,
											'user_id' => $cert_user_id,
											'post_id' => $course_id,
										)
									);
								} else {
									require_once __DIR__ . '/ld-convert-post-pdf.php';
									learndash_certificate_post_shortcode(
										array(
											'cert_id' => $certificate_post->ID,
											'user_id' => $cert_user_id,
											'post_id' => $course_id,
										)
									);
								}
								die();
							}
						}
					}
				}
			} elseif ( ( isset( $_GET['quiz'] ) ) && ( ! empty( $_GET['quiz'] ) ) ) {
				$quiz_id = intval( $_GET['quiz'] );
				if ( wp_verify_nonce( $_GET['cert-nonce'], $quiz_id . $cert_user_id . $view_user_id ) ) {

					$quiz_post = get_post( $quiz_id );
					if ( ( $quiz_post ) && ( is_a( $quiz_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'quiz' ) === $quiz_post->post_type ) ) {
						$quiz_certificate_post_id = learndash_get_setting( $quiz_post->ID, 'certificate' );
						if ( absint( $quiz_certificate_post_id ) === absint( $certificate_post->ID ) ) {
							$time               = isset( $_GET['time'] ) ? intval( $_GET['time'] ) : -1;
							$quizinfo           = get_user_meta( $cert_user_id, '_sfwd-quizzes', true );
							$selected_quizinfo  = null;
							$selected_quizinfo2 = null;

							if ( ! empty( $quizinfo ) ) {
								foreach ( $quizinfo as $quiz_i ) {

									if ( ( ( isset( $quiz_i['time'] ) ) && intval( $quiz_i['time'] ) == intval( $time ) ) && ( intval( $quiz_i['quiz'] ) === intval( $quiz_id ) ) ) {
										$selected_quizinfo = $quiz_i;
										break;
									}

									if ( intval( $quiz_i['quiz'] ) === intval( $quiz_id ) ) {
										$selected_quizinfo2 = $quiz_i;
									}
								}
							}

							$selected_quizinfo = empty( $selected_quizinfo ) ? $selected_quizinfo2 : $selected_quizinfo;
							if ( ! empty( $selected_quizinfo ) ) {
								$certificate_threshold = learndash_get_setting( $selected_quizinfo['quiz'], 'threshold' );

								if ( ( isset( $selected_quizinfo['percentage'] ) && $selected_quizinfo['percentage'] >= $certificate_threshold * 100 ) || ( isset( $selected_quizinfo['count'] ) && $selected_quizinfo['score'] / $selected_quizinfo['count'] >= $certificate_threshold ) ) {
									if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( $cert_user_id !== $view_user_id ) ) {
										wp_set_current_user( $cert_user_id );
									}

									if ( has_action( 'learndash_tcpdf_init' ) ) {
										/** This filter is documented in includes/class-ld-cpt-instance.php */
										do_action(
											'learndash_tcpdf_init',
											array(
												'cert_id' => $certificate_post->ID,
												'user_id' => $cert_user_id,
												'post_id' => $selected_quizinfo['quiz'],
											)
										);
									} else {
										/**
										 * Include library to generate PDF
										 */
										require_once __DIR__ . '/ld-convert-post-pdf.php';
										learndash_certificate_post_shortcode(
											array(
												'cert_id' => $certificate_post->ID,
												'user_id' => $cert_user_id,
												'post_id' => $selected_quizinfo['quiz'],
											)
										);
									}
									die();
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Action to allow custom handling of when a user cannot view a certificate.
		 *
		 * @since 3.2.3
		 */
		do_action( 'learndash_certificate_disallowed' );

		// If here we display the error and exit.
		esc_html_e( 'Access to certificate page is disallowed.', 'learndash' );
		die();

	}
}
add_action( 'template_redirect', 'learndash_certificate_display', 5 );

/**
 * Get where the Certificate post is used. Course/Quiz/Group.
 *
 * @since 3.4.1
 *
 * @param integer $post_id   Certificate Post ID.
 * @param string  $post_type Single post type slug to check.
 *
 * @return array Array of post IDs.
 */
function learndash_certificate_get_used_by( $post_id = 0, $post_type = '' ) {
	$post_ids = array();

	$post_id   = absint( $post_id );
	$post_type = esc_attr( $post_type );

	if ( ( ! empty( $post_id ) ) && ( ! empty( $post_type ) ) ) {
		$transient_key      = 'learndash_cert_used_' . $post_id . '_' . $post_type;
		$post_ids_transient = LDLMS_Transients::get( $transient_key );

		if ( false === $post_ids_transient ) {
			$query_args = array(
				'post_type'    => $post_type,
				'fields'       => 'ids',
				'nopaging'     => true,
				'meta_key'     => '_ld_certificate',
				'meta_value'   => $post_id,
				'meta_compare' => '=',
			);

			$query = new WP_Query( $query_args );
			if ( property_exists( $query, 'posts' ) ) {
				$post_ids = array_map( 'absint', $query->posts );
			}

			LDLMS_Transients::set( $transient_key, $post_ids, MINUTE_IN_SECONDS );
		} else {
			$post_ids = $post_ids_transient;
		}
	}

	return $post_ids;
}
