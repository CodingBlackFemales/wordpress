<?php
/**
 * BuddyBoss - Activity Feed (Single Item)
 *
 * This template is used by activity-loop.php and AJAX functions to show
 * each activity.
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

bp_nouveau_activity_hook( 'before', 'entry' ); ?>

<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>" data-bp-activity-id="<?php bp_activity_id(); ?>" data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>" data-bp-activity="<?php ( function_exists('bp_nouveau_edit_activity_data') ) ? bp_nouveau_edit_activity_data() : ''; ?>">

	<?php
	if ( function_exists( 'bb_nouveau_activity_entry_bubble_buttons' ) ) {
		bb_nouveau_activity_entry_bubble_buttons();
	}
	?>

	<div class="bp-activity-head">
		<div class="activity-avatar item-avatar">
			<a href="<?php bp_activity_user_link(); ?>"><?php bp_activity_avatar( array( 'type' => 'full' ) ); ?></a>
		</div>

		<div class="activity-header">
			<?php bp_activity_action(); ?>
			<p class="activity-date">
                <a href="<?php echo esc_url( bp_activity_get_permalink( bp_get_activity_id() ) ); ?>"><?php echo bp_core_time_since( bp_get_activity_date_recorded() ); ?></a>
				<?php
				if ( function_exists( 'bp_nouveau_activity_is_edited' ) ){
					bp_nouveau_activity_is_edited();
				}
				?>
            </p>
			<?php
            if ( function_exists( 'bp_nouveau_activity_privacy' ) ) {
	            bp_nouveau_activity_privacy();
            }
            ?>

		</div>
	</div>

	<?php bp_nouveau_activity_hook( 'before', 'activity_content' ); ?>

	<div class="activity-content <?php ( function_exists('bp_activity_entry_css_class') ) ? bp_activity_entry_css_class(): ''; ?>">
		<?php if ( bp_nouveau_activity_has_content() ) : ?>
			<div class="activity-inner <?php echo ( function_exists( 'bp_activity_has_content' ) && empty( bp_activity_has_content() ) ) ? esc_attr( 'bb-empty-content' ) : esc_attr( '' ); ?>">
				<?php
					bp_nouveau_activity_content();

					if ( function_exists( 'bb_nouveau_activity_inner_buttons' ) ) {
						bb_nouveau_activity_inner_buttons();
					}
				?>
			</div>
		<?php endif; ?>

		<?php
        if ( function_exists( 'bp_nouveau_activity_state' ) ) {
            bp_nouveau_activity_state();
        }
        ?>
	</div>

	<?php bp_nouveau_activity_hook( 'after', 'activity_content' ); ?>

	<?php bp_nouveau_activity_entry_buttons(); ?>

	<?php bp_nouveau_activity_hook( 'before', 'entry_comments' ); ?>

	<?php if ( bp_activity_get_comment_count() || ( is_user_logged_in() && ( bp_activity_can_comment() || bp_is_single_activity() ) ) ) : ?>

		<div class="activity-comments">

			<?php bp_activity_comments(); ?>

			<?php bp_nouveau_activity_comment_form(); ?>

		</div>

	<?php endif; ?>

	<?php bp_nouveau_activity_hook( 'after', 'entry_comments' ); ?>

</li>

<?php
bp_nouveau_activity_hook( 'after', 'entry' );
