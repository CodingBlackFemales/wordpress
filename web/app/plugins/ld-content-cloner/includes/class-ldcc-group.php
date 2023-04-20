<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wisdmlabs.com
 * @since      1.0.1
 *
 * @package    Ld_Content_Cloner
 * @subpackage Ld_Content_Cloner/includes
 */

namespace LDCC_Group;

/**
 * The LD Group plugin class.
 *
 * @since      1.0.1
 * @package    Ld_Content_Cloner
 * @subpackage Ld_Content_Cloner/includes
 * @author     WisdmLabs <info@wisdmlabs.com>
 */
class LDCC_Group {

	/**
	 * This property stores the group id being processed.
	 *
	 * @var integer
	 */
	protected static $group_id = 0;

	/**
	 * This property stores the ID of the cloned group.
	 *
	 * @var integer
	 */
	protected static $new_group_id = 0;

	/**
	 * This is the constructor for the class.
	 *
	 * @since    1.0.1
	 */
	public function __construct() {
	}

	/**
	 * This method is used to add the clone group link to the Group Listing page.
	 *
	 * @param array  $actions   Links available in the Group Listing admin page.
	 * @param object $post_data The post being processed.
	 * @return array $actions   Links array with the link for cloning group added.
	 */
	public function add_group_row_actions( $actions, $post_data ) {
		if ( get_post_type( $post_data->ID ) === 'groups' ) {
			$actions = array_merge(
				$actions,
				array(
					'clone_group' => '<a href="#" title="Clone this Group" class="ldcc-clone-group" data-group-id="' . $post_data->ID . '" data-group="' . wp_create_nonce( 'dup_group_' . $post_data->ID ) . '">' . sprintf( __( 'Clone %s', 'ld-content-cloner' ), \LearnDash_Custom_Label::get_label( 'group' ) ) . '</a>',
				)
			);
		}
		return $actions;
	}

	/**
	 * This method performs the actual functionality of cloning a group.
	 */
	public static function create_duplicate_group() {
		$group_id     = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );
		$course_nonce = filter_input( INPUT_POST, 'group' );
		$nonce_check  = wp_verify_nonce( $course_nonce, 'dup_group_' . $group_id );

		if ( false === $nonce_check ) {
			echo wp_json_encode( array( 'error' => __( 'Security check failed.', 'ld-content-cloner' ) ) );
			die();
		}

		if ( ( ! isset( $group_id ) ) || ! ( 'groups' === get_post_type( $group_id ) ) ) {
			echo wp_json_encode( array( 'error' => __( 'The current post is not a Group and hence could not be cloned.', 'ld-content-cloner' ) ) );
			die();
		}

		$group_post = get_post( $group_id, ARRAY_A );

		$group_post = self::strip_post_data( $group_post );

		$new_group_id = wp_insert_post( wp_slash( $group_post ), true );

		/**
		 * This action will run after group clone post is created.
		 *
		 * @since 1.2.8 [<description>]
		 */
		do_action( 'ldcc_group_clone_post_created', $new_group_id, $group_id );

		if ( ! is_wp_error( $new_group_id ) ) {
			self::set_meta( $group_id, $new_group_id );

			$group_leaders = learndash_get_groups_administrator_ids( $group_id );

			learndash_set_groups_administrators( $new_group_id, $group_leaders );

			$group_users = learndash_get_groups_user_ids( $group_id );
			learndash_set_groups_users( $new_group_id, $group_users );

			$group_enroll_course = learndash_group_enrolled_courses( $group_id );
			if ( ! empty( $group_enroll_course ) ) {
				foreach ( $group_enroll_course as $course_id ) {
					update_post_meta( $course_id, 'learndash_group_enrolled_' . $new_group_id, time() );
				}
			}
			$c_data = array(
				'lesson' => array(),
				'quiz'   => array(),
			);

			$send_result = array(
				'success' => array(
					'new_group_id' => $new_group_id,
					'c_data'       => $c_data,
				),
			);
			echo wp_json_encode( $send_result );
		} else {
			echo wp_json_encode( array( 'error' => __( 'Some error occurred. The Group could not be cloned.', 'ld-content-cloner' ) ) );
		}

		die();
	}

	/**
	 * This method is used to remove unnecessary information from the cloned group.
	 *
	 * @param  array $post_array Post Array.
	 * @return array $post_array Post Array.
	 */
	public static function strip_post_data( $post_array ) {
		$exclude_remove = array( 'post_content', 'post_title', 'post_status', 'post_type', 'tags_input' );
		foreach ( $post_array as $key => $value ) {
			if ( ! in_array( $key, $exclude_remove, true ) ) {
				unset( $post_array[ $key ] );
			}
		}
		$post_array['post_status'] = 'draft';
		$new_module_slug           = apply_filters( 'ldcc_duplicate_slug_before_insert', 'Copy', $post_array );
		$post_array['post_title'] .= ' ' . $new_module_slug;
		unset( $value );
		return $post_array;
	}

	public static function getDetaultValues( $ld_data ) {
		if ( empty( $ld_data['groups_group_courses_orderby'] ) ) {
			$ld_data['groups_group_courses_orderby'] = '';
		}
		if ( empty( $ld_data['groups_group_courses_order'] ) ) {
			$ld_data['groups_group_courses_order'] = '';
		}
		return $ld_data;
	}

	/**
	 * This method is used to set autoenroll group courses information.
	 *
	 * @param integer $old_post_id Old Post ID.
	 * @param integer $new_post_id New Post ID.
	 */
	public static function set_meta( $old_post_id, $new_post_id ) {
		update_post_meta( $new_post_id, 'ld_auto_enroll_group_courses', \wdm_recursively_slash_strings( get_post_meta( $old_post_id, 'ld_auto_enroll_group_courses', true ) ) );
		global $wpdb;
		$ld_data = get_post_meta( $old_post_id, '_groups', true );
		if ( ! empty( $ld_data ) ) {
			$ld_data = self::getDetaultValues( $ld_data );
			if ( ! empty( $ld_data['groups_group_price_type'] ) ) {
				if ( $ld_data['groups_group_price_type'] == 'subscribe' ) {
					$billing_cycle_time = get_post_meta( $old_post_id, 'group_price_billing_t3', true );
					update_post_meta( $new_post_id, 'group_price_billing_t3', $billing_cycle_time );
					$billing_cycle_day = get_post_meta( $old_post_id, 'group_price_billing_p3', true );
					update_post_meta( $new_post_id, 'group_price_billing_p3', $billing_cycle_day );
					$recurring_times = get_post_meta( $old_post_id, 'groups_group_price_type_subscribe_billing_recurring_times', true );
					update_post_meta( $new_post_id, 'groups_group_price_type_subscribe_billing_recurring_times', $recurring_times );
					$groups_group_trial_price = get_post_meta( $old_post_id, 'groups_group_trial_price', true );
					update_post_meta( $new_post_id, 'groups_group_trial_price', $groups_group_trial_price );
					$trial_duration = get_post_meta( $old_post_id, 'group_trial_duration_t1', true );
					update_post_meta( $new_post_id, 'group_trial_duration_t1', $trial_duration );
					$trial_duration2 = get_post_meta( $old_post_id, 'group_trial_duration_p1', true );
					update_post_meta( $new_post_id, 'group_trial_duration_p1', $trial_duration2 );
				}
			}
		}
		update_post_meta( $new_post_id, '_groups', \wdm_recursively_slash_strings( $ld_data ) );
		$term_taxonomy_ids = $wpdb->get_results( 'SELECT term_taxonomy_id FROM ' . $wpdb->prefix . 'term_relationships where object_id=' . $old_post_id );

		if ( ! empty( $term_taxonomy_ids ) ) {
			foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
				$wpdb->insert(
					$wpdb->prefix . 'term_relationships',
					array(
						'object_id'        => $new_post_id,
						'term_taxonomy_id' => $term_taxonomy_id->term_taxonomy_id,
						'term_order'       => 0,
					),
					array(
						'%d',
						'%d',
						'%d',
					)
				);
			}
		}
	}

	/**
	 * This method is used to populate structure for the cloning modal.
	 */
	public function add_modal_structure() {
		global $current_screen;

		if ( isset( $current_screen ) && in_array( $current_screen->post_type, array( 'groups' ), true ) && ! isset( $_GET['post'] ) ) {// phpcs:ignore
			?>
			<div id="ldcc-group-dialog" class="hidden" title="
			<?php
			echo esc_html( sprintf( __( '%s Cloning', 'ld-content-cloner' ), \LearnDash_Custom_Label::get_label( 'group' ) ) );
			?>
			">
				<div id="ldcc_clone_status"></div>
				<div class="ldcc-success">
					<div>
						<?php
						/* translators: Link to group edit page. */
						echo sprintf( __( 'Click %s to edit the cloned %s', 'ld-content-cloner' ), "<a class='ldcc-group-link' href='#'>" . __( 'here', 'ld-content-cloner' ) . '</a>', \LearnDash_Custom_Label::get_label( 'group' ) );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						?>
					</div>
				</div>

				<div class="ldcc-notice">
				<?php
				echo esc_html( sprintf( __( 'Note: Remember to change the Title of the %s.', 'ld-content-cloner' ), \LearnDash_Custom_Label::get_label( 'group' ) ) );
				?>
			</div></br>
			<?php
			$slider_loc = 'popup';
			$slider_loc = $slider_loc;
			require_once 'ldcc-slider.php';
		}
	}
}
