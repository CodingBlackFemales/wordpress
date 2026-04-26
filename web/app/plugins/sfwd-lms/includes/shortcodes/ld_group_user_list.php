<?php
/**
 * LearnDash `[learndash_group_user_list]` shortcode processing.
 *
 * @since 2.1.0
 * @package LearnDash\Shortcodes
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Group user list
 *
 * @param array  $attr {
 *    An array of shortcode attributes.
 * }.
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'learndash_group_user_list'.
 *
 * @return string
 */
function learndash_group_user_list( $attr = array(), $content = '', $shortcode_slug = 'learndash_group_user_list' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	if ( ( isset( $attr[0] ) ) && ( ! empty( $attr[0] ) ) ) {
		if ( ! isset( $attr['group_id'] ) ) {
			$attr['group_id'] = absint( $attr[0] );
			unset( $attr[0] );
		}
	}

	$attr = shortcode_atts(
		array(
			'group_id' => 0,
		),
		$attr
	);

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$attr = apply_filters( 'learndash_shortcode_atts', $attr, $shortcode_slug );

	$attr['group_post'] = null;
	if ( ! empty( $attr['group_id'] ) ) {
		// Check post access.
		if (
			! learndash_shortcode_can_current_user_access_post(
				Cast::to_int( $attr['group_id'] )
			)
		) {
			return '';
		}

		$attr['group_id']   = Cast::to_int( $attr['group_id'] );
		$attr['group_post'] = get_post( $attr['group_id'] );
		if ( ( $attr['group_post'] ) && ( is_a( $attr['group_post'], 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'group' ) === $attr['group_post']->post_type ) ) {

			$current_user = wp_get_current_user();

			$is_allowed_to_view = learndash_is_admin_user( $current_user );

			if (
				! $is_allowed_to_view
				&& learndash_is_group_leader_user( $current_user )
				&& (
					learndash_get_group_leader_manage_groups() === 'advanced'
					|| in_array( $attr['group_id'], learndash_get_administrators_group_ids( $current_user->ID ), true )
				)
			) {
				$is_allowed_to_view = true;
			}

			if ( ! $is_allowed_to_view ) {
				return sprintf(
					// translators: placeholder: Group.
					esc_html_x( 'Please login as a %s Administrator', 'placeholder: Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				);
			}

			$users = learndash_get_groups_users( $attr['group_id'] );
			if ( ! empty( $users ) ) {
				$level = ob_get_level();
				ob_start();
				?>
				<table cellspacing="0" class="wp-list-table widefat fixed groups_user_table">
				<thead>
					<tr>
						<th class="manage-column column-sno " id="sno" scope="col" ><?php esc_html_e( 'S. No.', 'learndash' ); ?></th>
						<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Name', 'learndash' ); ?></th>
						<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Username', 'learndash' ); ?></th>
						<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Email', 'learndash' ); ?></th>
						<th class="manage-column column-action" id="action" scope="col"><?php esc_html_e( 'Action', 'learndash' ); ?></span></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th class="manage-column column-sno " id="sno" scope="col" ><?php esc_html_e( 'S. No.', 'learndash' ); ?></th>
						<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Name', 'learndash' ); ?></th>
						<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Username', 'learndash' ); ?></th>
						<th class="manage-column column-name " id="group" scope="col"><?php esc_html_e( 'Email', 'learndash' ); ?></th>
						<th class="manage-column column-action" id="action" scope="col"><?php esc_html_e( 'Action', 'learndash' ); ?></span></th>
					</tr>
				</tfoot>
				<tbody>
					<?php
					$sn = 1;
					foreach ( $users as $user ) {
						$name       = isset( $user->display_name ) ? $user->display_name : $user->user_nicename;
						$report_url = add_query_arg(
							array(
								'page'     => 'group_admin_page',
								'group_id' => $attr['group_id'],
								'user_id'  => $user->ID,
							),
							admin_url( 'admin.php' )
						);
						?>
						<tr>
							<td><?php echo absint( $sn++ ); ?></td>
							<td><?php echo esc_html( $name ); ?></td>
							<td><?php echo esc_html( $user->user_login ); ?></td>
							<td><?php echo esc_html( $user->user_email ); ?></td>
							<td><a href="<?php echo esc_url( $report_url ); ?>"><?php esc_html_e( 'Report', 'learndash' ); ?></a></td>
						</tr>
						<?php
					}
					?>
				</tbody>
				</table>
				<?php
				return learndash_ob_get_clean( $level );
			} else {
				return esc_html__( 'No users.', 'learndash' );
			}
		}
	}
	return '';
}
add_shortcode( 'learndash_group_user_list', 'learndash_group_user_list', 10, 3 );
