<?php
global $messages_template;
$menu_link            = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );
$unread_message_count = messages_get_unread_count();
?>
<div id="header-messages-dropdown-elem" class="dropdown-passive dropdown-right notification-wrap messages-wrap bb-message-dropdown-notification menu-item-has-children">
	<a href="<?php echo esc_url( $menu_link ); ?>" ref="notification_bell" class="notification-link">
		<span data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Messages', 'buddyboss-theme' ); ?>" class="bb-member-unread-count-span-<?php echo esc_attr( bp_loggedin_user_id() ); ?>">
			<i class="bb-icon-l bb-icon-inbox"></i>
			<?php if ( $unread_message_count > 0 ) : ?>
				<span class="count"><?php echo esc_html( $unread_message_count ); ?></span>
			<?php endif; ?>
		</span>
	</a>
	<section class="notification-dropdown">
		<header class="notification-header">
			<h2 class="title"><?php esc_html_e( 'Messages', 'buddyboss-theme' ); ?></h2>
		</header>

		<ul class="notification-list">
			<p class="bb-header-loader"><i class="bb-icon-l bb-icon-spinner animate-spin"></i></p>
		</ul>

		<footer class="notification-footer">
			<a href="<?php echo esc_url( $menu_link ); ?>" class="delete-all">
				<?php esc_html_e( 'View Inbox', 'buddyboss-theme' ); ?>
				<i class="bb-icon-l bb-icon-angle-right"></i>
			</a>
		</footer>
	</section>
</div>
