<?php
bp_nouveau_member_hook( 'before', 'invites_sent_template' );

$email = trim ( bb_theme_filter_input_string( INPUT_GET, 'email' ) );
if ( isset( $email ) && '' !== $email ) {
	?>
    <aside class="bp-feedback bp-send-invites bp-template-notice success">
        <span class="bp-icon" aria-hidden="true"></span>
        <p>
			<?php
			$text = __( 'Invitations were sent successfully to the following email addresses:', 'buddyboss-theme' );
			echo trim ($text.' '. $email );
			?>
        </p>
    </aside>
	<?php
}

$failed = trim ( bb_theme_filter_input_string( INPUT_GET, 'failed' ) );
if ( isset( $failed ) && '' !== $failed ) {
	?>
    <aside class="bp-feedback bp-send-invites bp-template-notice error">
        <span class="bp-icon" aria-hidden="true"></span>
        <p>
			<?php
			$text = __( 'Invitations did not send as the following email addresses are invalid:', 'buddyboss-theme' );
			echo trim ($text.' '. $failed );
			?>
        </p>

    </aside>
	<?php
}

$exists = trim ( bb_theme_filter_input_string( INPUT_GET, 'exists' ) );
if ( isset( $exists ) && '' !== $exists ) {
	?>
    <aside class="bp-feedback bp-send-invites bp-template-notice error">
        <span class="bp-icon" aria-hidden="true"></span>
        <p>
			<?php
			$text = __( 'Invitations did not send to the following email addresses, because they are already members:', 'buddyboss-theme' );
			echo trim ($text.' '. $exists );
			?>
        </p>

    </aside>
	<?php
}
?>
<script>window.history.replaceState(null, null, window.location.pathname);</script>

<h2 class="screen-heading general-settings-screen">
	<?php _e( 'Sent Invites', 'buddyboss-theme' ); ?>
</h2>

<p class="info invite-info">
	<?php _e( 'You have sent invitation emails to the following people:', 'buddyboss-theme' ); ?>
</p>

<div class="table-responsive">
	<table class="invite-settings bp-tables-user" id="<?php echo esc_attr( 'member-invites-table' ); ?>">
		<thead>
		<tr>
			<th class="title"><?php esc_html_e( 'Name', 'buddyboss-theme' ); ?></th>
			<th class="title"><?php esc_html_e( 'Email', 'buddyboss-theme' ); ?></th>
			<th class="title"><?php esc_html_e( 'Invited', 'buddyboss-theme' ); ?></th>
			<th class="title"><?php esc_html_e( 'Status', 'buddyboss-theme' ); ?></th>
		</tr>
		</thead>

		<tbody>

		<?php
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
		$sent_invites_pagination_count = apply_filters( 'sent_invites_pagination_count', 25 );
		$args = array(
			'posts_per_page' => $sent_invites_pagination_count,
			'post_type'      => bp_get_invite_post_type(),
			'author'         => bp_loggedin_user_id(),
			'paged'          => $paged,
		);
		$the_query = new WP_Query( $args );

		if($the_query->have_posts()) {

			while ( $the_query->have_posts() ) : $the_query->the_post();
				?>
				<tr>
					<td class="field-name">
						<span><?php echo get_post_meta( get_the_ID(), '_bp_invitee_name', true ); ?></span>
					</td>
					<td class="field-email">
						<span><?php echo get_post_meta( get_the_ID(), '_bp_invitee_email', true ); ?></span>
					</td>
					<td class="field-email">
						<span>
							<?php
							$date = get_the_date( '',get_the_ID() );
							echo $date;
							?>
						</span>
					</td>
					<td class="field-email">
						<?php
						$allow_custom_registration = bp_allow_custom_registration();
						if ( $allow_custom_registration && '' !== bp_custom_register_page_url() ) {
							$class         = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? 'registered' : 'registered';
							$revoke_link   = '';
							$title         = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? __( 'Registered', 'buddyboss-theme' ) : __( 'Invited', 'buddyboss-theme' );
							$alert_message = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? __( 'Registered', 'buddyboss-theme' ) : __( 'Are you sure you want to revoke this invitation?', 'buddyboss-theme' );
							$icon          = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? 'bb-icon-l bb-icon-check' : 'bb-icon-l bb-icon-user-clock';
						} else {
							$class         = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? 'registered' : 'revoked-access';
							$revoke_link   = bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_invites_slug() . '/revoke-invite';
							$title         = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? __( 'Registered', 'buddyboss-theme' ) : __( 'Revoke Invite', 'buddyboss-theme' );
							$alert_message = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? __( 'Registered', 'buddyboss-theme' ) : __( 'Are you sure you want to revoke this invitation?', 'buddyboss-theme' );
							$icon          = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? 'bb-icon-l bb-icon-check' : 'bb-icon-rl bb-icon-times';
						}

						if ( $allow_custom_registration && '' !== bp_custom_register_page_url() ) {
							?>
							<span class="bp-invitee-status">
								<span class="<?php echo esc_attr( $icon ); ?>"></span><?php echo $title; ?>
							</span>
						<?php
						} else {
							?>
							<span class="bp-invitee-status">
								<?php
								if ( 'registered' === $class ) {
									?>
									<span class="<?php echo esc_attr( $icon ); ?>"></span><?php echo $title; ?>
									<?php
								} else {
									?>
									<a data-revoke-access="<?php echo esc_url( $revoke_link ); ?>" data-name="<?php echo esc_attr( $alert_message ); ?>" id="<?php echo esc_attr( get_the_ID() ); ?>" class="<?php echo esc_attr( $class ); ?>" href="javascript:void(0);">
										<span class="<?php echo esc_attr( $icon ); ?>"></span><?php echo $title; ?>
									</a>
									<?php
								}
								?>
							</span>
							<?php
						}
						?>
					</td>
				</tr>
				<?php
			endwhile;

		} else {
			?>
			<tr>
				<td colspan="4" class="field-name">
					<span><?php esc_html_e( 'You haven\'t sent any invitations yet.', 'buddyboss-theme' ); ?></span>
				</td>
			</tr>
			<?php
		}

		$total_pages = $the_query->max_num_pages;

		if ( $total_pages > 1 ){

			$current_page = max(1, get_query_var('paged'));

			echo paginate_links( array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'current'   => $current_page,
				'total'     => $total_pages,
				'prev_text' => __( '« Prev', 'buddyboss-theme' ),
				'next_text' => __( 'Next »', 'buddyboss-theme' ),
			) );
		}

		wp_reset_postdata();
		?>

		</tbody>
	</table>
</div>

<?php
bp_nouveau_member_hook( 'after', 'invites_sent_template' );