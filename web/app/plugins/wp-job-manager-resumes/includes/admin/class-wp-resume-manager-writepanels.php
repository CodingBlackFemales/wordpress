<?php
/**
 * File containing the WP_Resume_Manager_Writepanels.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Job_Manager_Writepanels' ) ) {
	include JOB_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-job-manager-writepanels.php';
}

class WP_Resume_Manager_Writepanels extends WP_Job_Manager_Writepanels {
	/**
	 * Singleton instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since  1.18.0
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * WP_Resume_Manager_Writepanels constructor.
	 */
	public function __construct() {
		// No need for parent hooks.
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_post' ], 1, 2 );
		add_action( 'resume_manager_save_resume', [ $this, 'save_resume_data' ], 1, 2 );
	}

	/**
	 * Resume fields
	 *
	 * @return array
	 */
	public static function resume_fields() {
		global $post_id;

		$current_user = wp_get_current_user();
		$fields_raw   = WP_Resume_Manager_Post_Types::get_resume_fields();
		$fields       = [];

		if ( $current_user->has_cap( 'edit_others_posts' ) ) {
			$fields['_resume_author'] = [
				'label'    => __( 'Posted by', 'wp-job-manager-resumes' ),
				'type'     => 'author',
				'priority' => 0,
			];
		}

		foreach ( $fields_raw as $meta_key => $field ) {
			$show_in_admin = $field['show_in_admin'];
			if ( is_callable( $show_in_admin ) ) {
				$show_in_admin = (bool) call_user_func( $show_in_admin, true, $meta_key, $post_id, $current_user->ID );
			}

			if ( ! $show_in_admin ) {
				continue;
			}

			/**
			 * Check auth callback. Mirrors first 4 params of WordPress core's `auth_{$object_type}_meta_{$meta_key}` filter.
			 *
			 * @param bool   $allowed   Whether the user can edit the resume meta. Default false.
			 * @param string $meta_key  The meta key.
			 * @param int    $object_id Object ID.
			 * @param int    $user_id   User ID.
			 */
			if ( ! call_user_func( $field['auth_edit_callback'], false, $meta_key, $post_id, $current_user->ID ) ) {
				continue;
			}

			$fields[ $meta_key ] = $field;
		}

		/**
		 * Filters resume data fields shown in WP admin.
		 *
		 * To add resume data fields, use the `resume_manager_resume_fields` found in `includes/class-wp-resume-manager-post-types.php`.
		 *
		 * @since @@version
		 *
		 * @param array    $fields  Resume fields for WP admin. See `resume_manager_resume_fields` filter for more information.
		 * @param int|null $post_id Post ID to get fields for. May be null.
		 */
		$fields = apply_filters( 'resume_manager_resume_wp_admin_fields', $fields, $post_id );

		uasort( $fields, [ __CLASS__, 'sort_by_priority' ] );

		return $fields;
	}

	/**
	 * Sorts array of custom fields by priority value.
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	protected static function sort_by_priority( $a, $b ) {
		if ( ! isset( $a['priority'] ) || ! isset( $b['priority'] ) || $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
	}

	/**
	 * add_meta_boxes function.
	 */
	public function add_meta_boxes() {
		add_meta_box( 'resume_data', __( 'Candidate Data', 'wp-job-manager-resumes' ), [ $this, 'resume_data' ], 'resume', 'normal', 'high' );
		add_meta_box( 'resume_url_data', __( 'URL(s)', 'wp-job-manager-resumes' ), [ $this, 'url_data' ], 'resume', 'side', 'low' );
		add_meta_box( 'resume_education_data', __( 'Education', 'wp-job-manager-resumes' ), [ $this, 'education_data' ], 'resume', 'normal', 'high' );
		add_meta_box( 'resume_experience_data', __( 'Experience', 'wp-job-manager-resumes' ), [ $this, 'experience_data' ], 'resume', 'normal', 'high' );
	}

	/**
	 * Resume data
	 *
	 * @param mixed $post
	 */
	public function resume_data( $post ) {
		global $post, $thepostid;

		$thepostid = $post->ID;

		echo '<div class="wp_resume_manager_meta_data wp_job_manager_meta_data">';

		wp_nonce_field( 'save_meta_data', 'resume_manager_nonce' );

		do_action( 'resume_manager_resume_data_start', $thepostid );

		foreach ( $this->resume_fields() as $key => $field ) {
			$type = ! empty( $field['type'] ) ? $field['type'] : 'text';

			if ( ! isset( $field['value'] ) && metadata_exists( 'post', $thepostid, $key ) ) {
				$field['value'] = get_post_meta( $thepostid, $key, true );
			}

			if ( ! isset( $field['value'] ) && isset( $field['default'] ) ) {
				$field['value'] = $field['default'];
			} elseif ( ! isset( $field['value'] ) ) {
				$field['value'] = '';
			}

			if ( '_resume_file' === $key ) {
				if ( is_array( $field['value'] ) ) {
					$field['download'] = array_map(
						function( $value, $key ) use ( $thepostid ) {
							return get_resume_file_download_url( $thepostid, $key, site_url() );
						},
						$field['value'],
						array_keys( $field['value'] )
					);
				} else {
					$field['download'] = get_resume_file_download_url( $thepostid, 0, site_url() );
				}
			}

			if ( has_action( 'resume_manager_input_' . $type ) ) {
				do_action( 'resume_manager_input_' . $type, $key, $field );
			} elseif ( method_exists( $this, 'input_' . $type ) ) {
				call_user_func( [ $this, 'input_' . $type ], $key, $field );
			}
		}

		$user_edited_date = get_post_meta( $post->ID, '_resume_edited', true );
		if ( $user_edited_date ) {
			echo '<p class="form-field"><em>';
			echo sprintf(
				// translators: %1$s placeholder is the object type singular name; %2$s is the relative date the resume was edited.
				esc_html__( '%1$s was last modified by the user on %2$s.', 'wp-job-manager-resumes' ),
				esc_html( get_post_type_object( 'resume' )->labels->singular_name ),
				esc_html( date_i18n( get_option( 'date_format' ), $user_edited_date ) )
			);
			echo '</em></p>';
		}

		do_action( 'resume_manager_resume_data_end', $thepostid );

		echo '</div>';
	}

	/**
	 * Output repeated rows
	 */
	public static function repeated_rows_html( $group_name, $fields, $data ) {
		?>
		<table class="wc-job-manager-resumes-repeated-rows">
			<thead>
				<tr>
					<th class="sort-column">&nbsp;</th>
					<?php foreach ( $fields as $field ) : ?>
						<th><label><?php echo esc_html( $field['label'] ); ?></label></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="<?php echo count( $fields ) + 1; ?>">
						<div class="submit">
							<input type="submit" class="button resume_manager_add_row" value="<?php printf( __( 'Add %s', 'wp-job-manager-resumes' ), $group_name ); ?>" data-row="
																											<?php
																											ob_start();
																											echo '<tr>';
																											echo '<td class="sort-column" width="1%">&nbsp;</td>';
																											foreach ( $fields as $key => $field ) {
																												echo '<td>';
																												$type           = ! empty( $field['type'] ) ? $field['type'] : 'text';
																												$field['value'] = '';

																												if ( method_exists( __CLASS__, 'input_' . $type ) ) {
																													call_user_func( [ __CLASS__, 'input_' . $type ], $key, $field );
																												} else {
																													do_action( 'resume_manager_input_' . $type, $key, $field );
																												}
																												echo '</td>';
																											}
																											echo '</tr>';
																											echo esc_attr( ob_get_clean() );
																											?>
							" />
						</div>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
				if ( $data ) {
					foreach ( $data as $item ) {
						echo '<tr>';
						echo '<td class="sort-column" width="1%">&nbsp;</td>';
						foreach ( $fields as $key => $field ) {
							echo '<td>';
							$type           = ! empty( $field['type'] ) ? $field['type'] : 'text';
							$field['value'] = isset( $item[ $key ] ) ? $item[ $key ] : '';

							if ( method_exists( __CLASS__, 'input_' . $type ) ) {
								call_user_func( [ __CLASS__, 'input_' . $type ], $key, $field );
							} else {
								do_action( 'resume_manager_input_' . $type, $key, $field );
							}
							echo '</td>';
						}
						echo '</tr>';
					}
				}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Resume fields
	 *
	 * @return array
	 */
	public static function resume_links_fields() {
		return apply_filters(
			'resume_manager_resume_links_fields',
			[
				'name' => [
					'label'       => __( 'Name', 'wp-job-manager-resumes' ),
					'name'        => 'resume_url_name[]',
					'placeholder' => __( 'Your site', 'wp-job-manager-resumes' ),
					'description' => '',
					'required'    => true,
				],
				'url'  => [
					'label'       => __( 'URL', 'wp-job-manager-resumes' ),
					'name'        => 'resume_url[]',
					'placeholder' => 'http://',
					'description' => '',
					'required'    => true,
				],
			]
		);
	}

	/**
	 * Resume fields
	 *
	 * @return array
	 */
	public static function resume_education_fields() {
		return apply_filters(
			'resume_manager_resume_education_fields',
			[
				'location'      => [
					'label'       => __( 'Institution', 'wp-job-manager-resumes' ),
					'name'        => 'resume_education_location[]',
					'placeholder' => '',
					'description' => '',
					'required'    => true,
				],
				'qualification' => [
					'label'       => __( 'Certification(s)', 'wp-job-manager-resumes' ),
					'name'        => 'resume_education_qualification[]',
					'placeholder' => '',
					'description' => '',
				],
				'date'          => [
					'label'       => __( 'Start/end date', 'wp-job-manager-resumes' ),
					'name'        => 'resume_education_date[]',
					'placeholder' => '',
					'description' => '',
				],
				'notes'         => [
					'label'       => __( 'Notes', 'wp-job-manager-resumes' ),
					'name'        => 'resume_education_notes[]',
					'placeholder' => '',
					'description' => '',
					'type'        => 'textarea',
				],
			]
		);
	}

	/**
	 * Resume fields
	 *
	 * @return array
	 */
	public static function resume_experience_fields() {
		return apply_filters(
			'resume_manager_resume_experience_fields',
			[
				'employer'  => [
					'label'       => __( 'Employer', 'wp-job-manager-resumes' ),
					'name'        => 'resume_experience_employer[]',
					'placeholder' => '',
					'description' => '',
					'required'    => true,
				],
				'job_title' => [
					'label'       => __( 'Job Title', 'wp-job-manager-resumes' ),
					'name'        => 'resume_experience_job_title[]',
					'placeholder' => '',
					'description' => '',
				],
				'date'      => [
					'label'       => __( 'Start/end date', 'wp-job-manager-resumes' ),
					'name'        => 'resume_experience_date[]',
					'placeholder' => '',
					'description' => '',
				],
				'notes'     => [
					'label'       => __( 'Notes', 'wp-job-manager-resumes' ),
					'name'        => 'resume_experience_notes[]',
					'placeholder' => '',
					'description' => '',
					'type'        => 'textarea',
				],
			]
		);
	}

	/**
	 * Resume URL data
	 *
	 * @param mixed $post
	 */
	public function url_data( $post ) {
		echo '<p>' . __( 'Optionally provide links to any of your websites or social network profiles.', 'wp-job-manager-resumes' ) . '</p>';
		$fields = $this->resume_links_fields();
		$this->repeated_rows_html( __( 'URL', 'wp-job-manager-resumes' ), $fields, get_post_meta( $post->ID, '_links', true ) );
	}

	/**
	 * Resume Education data
	 *
	 * @param mixed $post
	 */
	public function education_data( $post ) {
		$fields = $this->resume_education_fields();
		$this->repeated_rows_html( __( 'Education', 'wp-job-manager-resumes' ), $fields, get_post_meta( $post->ID, '_candidate_education', true ) );
	}

	/**
	 * Resume Education data
	 *
	 * @param mixed $post
	 */
	public function experience_data( $post ) {
		$fields = $this->resume_experience_fields();
		$this->repeated_rows_html( __( 'Experience', 'wp-job-manager-resumes' ), $fields, get_post_meta( $post->ID, '_candidate_experience', true ) );
	}

	/**
	 * Triggered on Save Post
	 *
	 * @param mixed $post_id
	 * @param mixed $post
	 */
	public function save_post( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( is_int( wp_is_post_revision( $post ) ) ) {
			return;
		}
		if ( is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}
		if ( empty( $_POST['resume_manager_nonce'] ) || ! wp_verify_nonce( $_POST['resume_manager_nonce'], 'save_meta_data' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( $post->post_type != 'resume' ) {
			return;
		}

		do_action( 'resume_manager_save_resume', $post_id, $post );
	}

	/**
	 * Save Resume Meta
	 *
	 * @param mixed $post_id
	 * @param mixed $post
	 */
	public function save_resume_data( $post_id, $post ) {
		global $wpdb;

		// These need to exist
		add_post_meta( $post_id, '_featured', 0, true );

		foreach ( $this->resume_fields() as $key => $field ) {

			// Expirey date
			if ( '_resume_expires' === $key ) {
				if ( ! empty( $_POST[ $key ] ) ) {
					update_post_meta( $post_id, $key, date( 'Y-m-d', strtotime( sanitize_text_field( $_POST[ $key ] ) ) ) );
				} else {
					update_post_meta( $post_id, $key, '' );
				}
			} elseif ( '_candidate_location' === $key ) {
				if ( update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) ) ) {
					do_action( 'resume_manager_candidate_location_edited', $post_id, sanitize_text_field( $_POST[ $key ] ) );
				} elseif ( apply_filters( 'resume_manager_geolocation_enabled', true ) && ! WP_Job_Manager_Geocode::has_location_data( $post_id ) ) {
					WP_Job_Manager_Geocode::generate_location_data( $post_id, sanitize_text_field( $_POST[ $key ] ) );
				}
				continue;
			} elseif ( '_resume_author' === $key ) {
				$wpdb->update( $wpdb->posts, [ 'post_author' => $_POST[ $key ] > 0 ? absint( $_POST[ $key ] ) : 0 ], [ 'ID' => $post_id ] );
			}

			// Everything else
			else {
				$type = ! empty( $field['type'] ) ? $field['type'] : '';

				switch ( $type ) {
					case 'textarea':
						update_post_meta( $post_id, $key, wp_kses_post( stripslashes( $_POST[ $key ] ) ) );
						break;
					case 'checkbox':
						if ( isset( $_POST[ $key ] ) ) {
							update_post_meta( $post_id, $key, 1 );
						} else {
							update_post_meta( $post_id, $key, 0 );
						}
						break;
					default:
						if ( is_array( $_POST[ $key ] ) ) {
							update_post_meta( $post_id, $key, array_filter( array_map( 'sanitize_text_field', $_POST[ $key ] ) ) );
						} else {
							update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
						}
						break;
				}
			}
		}

		$save_repeated_fields = [
			'_links'                => $this->resume_links_fields(),
			'_candidate_education'  => $this->resume_education_fields(),
			'_candidate_experience' => $this->resume_experience_fields(),
		];

		foreach ( $save_repeated_fields as $meta_key => $fields ) {
			$this->save_repeated_row( $post_id, $meta_key, $fields );
		}
	}

	/**
	 * Save repeated rows
	 *
	 * @since 1.11.3
	 */
	public static function save_repeated_row( $post_id, $meta_key, $fields ) {
		$items            = [];
		$first_field      = current( $fields );
		$first_field_name = str_replace( '[]', '', $first_field['name'] );

		if ( ! empty( $_POST[ $first_field_name ] ) && is_array( $_POST[ $first_field_name ] ) ) {
			$keys = array_keys( $_POST[ $first_field_name ] );
			foreach ( $keys as $posted_key ) {
				$item = [];
				foreach ( $fields as $key => $field ) {
					$input_name = str_replace( '[]', '', $field['name'] );
					$type       = ! empty( $field['type'] ) ? $field['type'] : 'text';

					switch ( $type ) {
						case 'textarea':
							$item[ $key ] = wp_kses_post( stripslashes( $_POST[ $input_name ][ $posted_key ] ) );
							break;
						default:
							if ( is_array( $_POST[ $input_name ][ $posted_key ] ) ) {
								$item[ $key ] = array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $_POST[ $input_name ][ $posted_key ] ) ) );
							} else {
								$item[ $key ] = sanitize_text_field( stripslashes( $_POST[ $input_name ][ $posted_key ] ) );
							}
							break;
					}
					if ( empty( $item[ $key ] ) && ! empty( $field['required'] ) ) {
						continue 2;
					}
				}
				$items[] = $item;
			}
		}
		update_post_meta( $post_id, $meta_key, $items );
	}
}
