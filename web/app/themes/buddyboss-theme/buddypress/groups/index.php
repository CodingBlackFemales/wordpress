<?php
/**
 * BP Nouveau - Groups Directory
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

$is_send_ajax_request = ! function_exists( 'bb_is_send_ajax_request' ) || bb_is_send_ajax_request();
?>
<div class="subnav-search groups-search">
	<?php bp_nouveau_search_form(); ?>
</div>

<?php
bp_nouveau_before_groups_directory_content();
bp_nouveau_template_notices();

if ( ! bp_nouveau_is_object_nav_in_sidebar() ) {
	bp_get_template_part( 'common/nav/directory-nav' );
}
?>

<div class="flex bp-secondary-header align-items-center">
	<div class="push-right flex">
		<div class="bp-group-filter-wrap subnav-filters filters no-ajax">
			<?php bp_get_template_part( 'common/filters/group-filters' ); ?>
		</div>
		<div class="bp-groups-filter-wrap subnav-filters filters no-ajax">
			<?php bp_get_template_part( 'common/filters/directory-filters' ); ?>
		</div>

		<?php bp_get_template_part( 'common/filters/grid-filters' ); ?>
	</div>
</div>

<div class="screen-content">
	<div id="groups-dir-list" class="groups dir-list" data-bp-list="groups" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
		<?php
		if ( $is_send_ajax_request ) {
		echo '<div id="bp-ajax-loader">';
			bp_nouveau_user_feedback( 'directory-groups-loading' );
			echo '</div>';
		} else {
			bp_get_template_part( 'groups/groups-loop' );
		}
		?>
	</div><!-- #groups-dir-list -->

	<?php bp_nouveau_after_groups_directory_content(); ?>
</div><!-- // .screen-content -->
