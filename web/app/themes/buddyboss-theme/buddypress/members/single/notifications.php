<?php
/**
 * BuddyBoss - Users Notifications
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */
?>

<header class="entry-header notifications-header flex">
	<h1 class="entry-title flex-1"><?php esc_html_e( 'Notifications', 'buddyboss-theme' ); ?></h1>
	<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>
	<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>
</header>

<?php
$is_send_ajax_request = ! function_exists( 'bb_is_send_ajax_request' ) || bb_is_send_ajax_request();

switch ( bp_current_action() ) :

	case 'unread':
	case 'read':
		?>
		<div id="notifications-user-list" class="notifications dir-list" data-bp-list="notifications">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bp_nouveau_user_feedback( 'member-notifications-loading' );
				echo '</div>';
			} else {
				bp_get_template_part( 'members/single/notifications/notifications-loop' );
			}
			?>
		</div>

		<?php
		break;

	// Any other actions.
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
