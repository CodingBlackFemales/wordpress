<?php
global $wpdb, $pmpro_msg, $pmpro_msgt, $pmpro_levels, $current_user, $levels;

// $atts    ::= array of attributes
// $content ::= text within enclosing form of shortcode element
// $code    ::= the shortcode found, when == callback name
// examples: [pmpro_account] [pmpro_account sections="membership,profile"/]

if ( ! isset( $atts ) ) {
	$atts = array();
}

extract( shortcode_atts( array(
	'section'  => '',
	'sections' => 'membership,profile,invoices,links',
), $atts ) );

//did they use 'section' instead of 'sections'?.
if ( ! empty( $section ) ) {
	$sections = $section;
}

//Extract the user-defined sections for the shortcode.
$sections = array_map( 'trim', explode( ",", $sections ) );
ob_start();

//if a member is logged in, show them some info here (1. past invoices. 2. billing information with button to update.).

$ssorder = new MemberOrder();
$ssorder->getLastMemberOrder();
$mylevels     = pmpro_getMembershipLevelsForUser();
$pmpro_levels = pmpro_getAllLevels( false, true ); // just to be sure - include only the ones that allow signups.
$invoices     = $wpdb->get_results("SELECT *, UNIX_TIMESTAMP(CONVERT_TZ(timestamp, '+00:00', @@global.time_zone)) as timestamp FROM $wpdb->pmpro_membership_orders WHERE user_id = '$current_user->ID' AND status NOT IN('review', 'token', 'error') ORDER BY timestamp DESC LIMIT 6");
?>
<div id="pmpro_account">
	<?php
	if ( in_array( 'membership', $sections, true ) || in_array( 'memberships', $sections, true ) ) {
		?>
		<div id="pmpro_account-membership" class="pmpro_box">

			<h3><?php esc_html_e( "My Memberships", 'buddyboss-theme' ); ?></h3>
			<table width="100%" cellpadding="0" cellspacing="0" border="0">
				<thead>
					<tr>
						<th><?php esc_html_e( "Level", 'buddyboss-theme' ); ?></th>
						<th><?php esc_html_e( "Billing", 'buddyboss-theme' ); ?></th>
						<th><?php esc_html_e( "Expiration", 'buddyboss-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				if ( empty( $mylevels ) ) { ?>
					<tr>
						<td colspan="3">
							<?php
							// Check to see if the user has a cancelled order.
							$ssorder = new MemberOrder();
							$ssorder->getLastMemberOrder( $current_user->ID, array( 'cancelled', 'expired', 'admin_cancelled' ) );

							if ( isset( $ssorder->membership_id ) && ! empty( $ssorder->membership_id ) && empty( $level->id ) ) {
								$level = pmpro_getLevel( $ssorder->membership_id );
							}

							// If no level check for a default level.
							if ( empty( $level ) || ! $level->allow_signups ) {
								$default_level_id = apply_filters( 'pmpro_default_level', 0 );
							}

							// Show the correct checkout link.
							if ( ! empty( $level ) && ! empty( $level->allow_signups ) ) {
								$url = pmpro_url( 'checkout', '?level=' . $level->id );
								printf(
									__( 'Your membership is not active. %s', 'buddyboss-theme' ),
									sprintf(
										/* translators: 1. Renew URL 2. Renew Text. */
										'<a href="%1$s">%2$s</a>',
										$url,
										esc_html__( 'Renew now.', 'buddyboss-theme' )
									)
								);
							} elseif ( ! empty( $default_level_id ) ) {
								$url = pmpro_url( 'checkout', '?level=' . $default_level_id );
								printf(
									__( 'You do not have an active membership. %s', 'buddyboss-theme' ),
									sprintf(
										/* translators: 1. Register URL 2. Register Text. */
										'<a href="%1$s">%2$s</a>',
										$url,
										esc_html__( 'Register here.', 'buddyboss-theme' )
									)
								);
							} else {
								$url = pmpro_url( 'levels' );
								printf(
									__( 'You do not have an active membership. %s', 'buddyboss-theme' ),
									sprintf(
										/* translators: 1. Register URL 2. Register Text. */
										'<a href="%1$s">%2$s</a>',
										$url,
										esc_html__( 'Choose a membership level.', 'buddyboss-theme' )
									)
								);
							}
							?>
						</td>
					</tr>
					<?php
				} else {
					foreach ( $mylevels as $level ) {
						?>
						<tr>
							<td class="pmpro_account-membership-levelname">
								<?php echo wp_kses_post( $level->name ) ?>
								<div class="pmpro_actionlinks">
									<?php
									do_action( "pmpro_member_action_links_before" );

									// Build the links to return.
									$pmpro_member_action_links = array();

									if ( array_key_exists( $level->id, $pmpro_levels ) && pmpro_isLevelExpiringSoon( $level ) ) {
										$pmpro_member_action_links['renew'] = sprintf( '<a id="pmpro_actionlink-renew" href="%s">%s</a>', esc_url( add_query_arg( 'level', $level->id, pmpro_url( 'checkout', '', 'https' ) ) ), esc_html__( 'Renew', 'buddyboss-theme' ) );
									}

									if (
										(
											isset( $order->status ) &&
											"success" === $order->status
										) &&
										(
											isset( $order->gateway ) &&
											in_array( $order->gateway, array( "authorizenet", "paypal", "stripe", "braintree", "payflow", "cybersource" ), true )
										) &&
										pmpro_isLevelRecurring( $level )
									) {
										$pmpro_member_action_links['update-billing'] = sprintf( '<a id="pmpro_actionlink-update-billing" href="%s">%s</a>', pmpro_url( 'billing', '', 'https' ), esc_html__( 'Update Billing Info', 'buddyboss-theme' ) );
									}

									//To do: Only show CHANGE link if this level is in a group that has upgrade/downgrade rules
									if ( count( $pmpro_levels ) > 1 && ! defined( "PMPRO_DEFAULT_LEVEL" ) ) {
										$pmpro_member_action_links['change'] = sprintf( '<a id="pmpro_actionlink-change" href="%s">%s</a>', pmpro_url( 'levels' ), esc_html__( 'Change', 'buddyboss-theme' ) );
									}

									$pmpro_member_action_links['cancel'] = sprintf( '<a id="pmpro_actionlink-cancel" href="%s">%s</a>', esc_url( add_query_arg( 'levelstocancel', $level->id, pmpro_url( 'cancel' ) ) ), esc_html__( 'Cancel', 'buddyboss-theme' ) );

									$pmpro_member_action_links = apply_filters( 'pmpro_member_action_links', $pmpro_member_action_links );

									$allowed_html = array(
										'a' => array(
											'class' => array(),
											'href' => array(),
											'id' => array(),
											'target' => array(),
											'title' => array(),
										),
									);
									echo wp_kses( implode( pmpro_actions_nav_separator(), $pmpro_member_action_links ), $allowed_html );

									do_action( "pmpro_member_action_links_after" );
									?>
								</div> <!-- end pmpro_actionlinks -->
							</td>
							<td class="pmpro_account-membership-levelfee">
								<p><?php echo pmpro_getLevelCost( $level, true, true ); ?></p>
							</td>
							<td class="pmpro_account-membership-expiration">
								<?php
								if ( $level->enddate ) {
									$expiration_text = date_i18n( get_option( 'date_format' ), $level->enddate );
								} else {
									$expiration_text = "---";
								}
								echo apply_filters( 'pmpro_account_membership_expiration_text', $expiration_text, $level );
								?>
							</td>
						</tr>
					<?php
					}
				}
				?>
				</tbody>
			</table>
			<?php //Todo: If there are multiple levels defined that aren't all in the same group defined as upgrades/downgrades. ?>
			<div class="pmpro_actionlinks">
				<a id="pmpro_actionlink-levels" href="<?php echo pmpro_url( "levels" ) ?>">
					<?php esc_html_e( "View all Membership Options", 'buddyboss-theme' ); ?>
				</a>
			</div>

		</div> <!-- end pmpro_account-membership -->
		<?php
	}
	if ( in_array( 'profile', $sections, true ) ) {
		?>
		<div id="pmpro_account-profile" class="pmpro_box">
			<?php wp_get_current_user(); ?>
			<h3><?php esc_html_e( "My Account", 'buddyboss-theme' ); ?></h3>
			<div class="bb-pmpro_account-profile">
				<?php
				if ( $current_user->user_firstname ) {
					?>
					<p><?php echo esc_html( $current_user->user_firstname ); ?> <?php echo esc_html( $current_user->user_lastname ); ?></p>
					<?php
				}
				?>
				<ul>
					<?php do_action( 'pmpro_account_bullets_top' ); ?>
					<li><strong><?php esc_html_e( "Username", 'buddyboss-theme' ); ?>:</strong> <?php echo esc_html( $current_user->user_login ); ?></li>
					<li><strong><?php esc_html_e( "Email", 'buddyboss-theme' ); ?>:</strong> <?php echo esc_html( $current_user->user_email ); ?></li>
					<?php do_action( 'pmpro_account_bullets_bottom' ); ?>
				</ul>
				<div class="pmpro_actionlinks">
					<?php
					// Get the edit profile and change password links if 'Member Profile Edit Page' is set.
					if ( ! empty( pmpro_getOption( 'member_profile_edit_page_id' ) ) ) {
						$edit_profile_url    = pmpro_url( 'member_profile_edit' );
						$change_password_url = add_query_arg( 'view', 'change-password', pmpro_url( 'member_profile_edit' ) );
					} elseif ( ! pmpro_block_dashboard() ) {
						$edit_profile_url    = admin_url( 'profile.php' );
						$change_password_url = admin_url( 'profile.php' );
					}

					// Build the links to return.
					$pmpro_profile_action_links = array();
					if ( ! empty( $edit_profile_url) ) {
						$pmpro_profile_action_links['edit-profile'] = sprintf( '<a id="pmpro_actionlink-profile" href="%s">%s</a>', esc_url( $edit_profile_url ), esc_html__( 'Edit Profile', 'buddyboss-theme' ) );
					}

					if ( ! empty( $change_password_url ) ) {
						$pmpro_profile_action_links['change-password'] = sprintf( '<a id="pmpro_actionlink-change-password" href="%s">%s</a>', esc_url( $change_password_url ), esc_html__( 'Change Password', 'buddyboss-theme' ) );
					}

					$pmpro_profile_action_links['logout'] = sprintf( '<a id="pmpro_actionlink-logout" href="%s">%s</a>', esc_url( wp_logout_url() ), esc_html__( 'Log Out', 'buddyboss-theme' ) );

					$pmpro_profile_action_links = apply_filters( 'pmpro_account_profile_action_links', $pmpro_profile_action_links );

					$allowed_html = array(
						'a' => array (
							'class' => array(),
							'href' => array(),
							'id' => array(),
							'target' => array(),
							'title' => array(),
						),
					);
					echo wp_kses( implode( pmpro_actions_nav_separator(), $pmpro_profile_action_links ), $allowed_html );
					?>
				</div>
			</div>
		</div> <!-- end pmpro_account-profile -->
		<?php
	}

	if ( in_array( 'invoices', $sections, true ) && ! empty( $invoices ) ) { ?>
		<div id="pmpro_account-invoices" class="pmpro_box">
			<h3><?php esc_html_e( "Past Invoices", 'buddyboss-theme' ); ?></h3>
			<table width="100%" cellpadding="0" cellspacing="0" border="0">
				<thead>
					<tr>
						<th><?php esc_html_e( "Date", 'buddyboss-theme' ); ?></th>
						<th><?php esc_html_e( "Level", 'buddyboss-theme' ); ?></th>
						<th><?php esc_html_e( "Amount", 'buddyboss-theme' ); ?></th>
						<th><?php esc_html_e( "Status", 'buddyboss-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$count = 0;
				foreach ( $invoices as $invoice ) {
					if ( $count ++ > 4 ) {
						break;
					}
					//get an member order object
					$invoice_id = $invoice->id;
					$invoice    = new MemberOrder;
					$invoice->getMemberOrderByID( $invoice_id );
					$invoice->getMembershipLevel();
					if ( in_array( $invoice->status, array( '', 'success', 'cancelled' ), true ) ) {
						$display_status = esc_html__( 'Paid', 'buddyboss-theme' );
					} elseif ( 'pending' === $invoice->status ) {
						// Some Add Ons set status to pending.
						$display_status = esc_html__( 'Pending', 'buddyboss-theme' );
					} elseif ( 'refunded' === $invoice->status ) {
						$display_status = esc_html__( 'Refunded', 'buddyboss-theme' );
					}
					?>
					<tr id="pmpro_account-invoice-<?php echo esc_attr( $invoice->code ); ?>">
						<td>
							<a href="<?php echo pmpro_url( "invoice", "?invoice=" . $invoice->code ) ?>">
								<?php echo date_i18n( get_option( "date_format" ), $invoice->timestamp ) ?>
							</a>
						</td>
						<td>
							<?php
							if ( ! empty( $invoice->membership_level ) ) {
								echo esc_html( $invoice->membership_level->name );
							} else {
								echo __( "N/A", 'buddyboss-theme' );
							}
							?>
						</td>
						<td><?php echo pmpro_formatPrice( $invoice->total ) ?></td>
						<td><?php echo esc_html( $display_status ); ?></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
			<?php
			if ( 6 === (int) $count ) {
				?>
				<div class="pmpro_actionlinks">
					<a id="pmpro_actionlink-invoices" href="<?php echo pmpro_url( "invoice" ); ?>">
						<?php esc_html_e( "View All Invoices", 'buddyboss-theme' ); ?>
					</a>
				</div>
				<?php
			}
			?>
		</div> <!-- end pmpro_account-invoices -->
	<?php }

	if (
		in_array( 'links', $sections, true ) &&
		(
			has_filter( 'pmpro_member_links_top' ) ||
			has_filter( 'pmpro_member_links_bottom' )
		)
	) {
		?>
		<div id="pmpro_account-links" class="pmpro_box">
			<h3><?php esc_html_e( "Member Links", 'buddyboss-theme' ); ?></h3>
			<ul>
				<?php
				do_action( "pmpro_member_links_top" );

				do_action( "pmpro_member_links_bottom" );
				?>
			</ul>
		</div> <!-- end pmpro_account-links -->
		<?php
	}
	?>
</div> <!-- end pmpro_account -->