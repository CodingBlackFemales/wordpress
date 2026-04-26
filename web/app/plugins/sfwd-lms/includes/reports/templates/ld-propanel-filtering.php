<?php
/**
 * Learndash ProPanel Reporting
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;
?>
<?php
	// if ( ( learndash_is_admin_user( get_current_user_id() ) ) || ( learndash_is_group_leader_user( get_current_user_id() ) ) ) {
	// $filter_tab_active = '';
	// $filter_tab_display = '';
	// } else {
		$filter_tab_active  = 'active';
		$filter_tab_display = 'display';
	// }
?>
<div class="ld-propanel-filters-wrap">

	<div class="table-actions-wrap">
		<?php if ( is_admin() ) { ?>
		<div class="section-toggles clearfix">
			<a href="#table-filters" title="<?php esc_attr_e( 'Filters', 'learndash' ); ?>" class="button section-toggle <?php echo esc_attr( $filter_tab_active ); ?>"><?php esc_html_e( 'Filters', 'learndash' ); ?></a>

			<?php if ( ( learndash_is_admin_user( get_current_user_id() ) ) || ( learndash_is_group_leader_user( get_current_user_id() ) ) ) { ?>
				<a href="#email" title="<?php esc_attr_e( 'Email', 'learndash' ); ?>" class="button section-toggle email-toggle">
					<?php esc_html_e( 'Email', 'learndash' ); ?><span class="count"></span>
				</a>

				<a class="button full-page" href="<?php echo esc_attr( admin_url( '?page=propanel-reporting' ) ); ?>">
					<?php esc_html_e( 'Full Page', 'learndash' ); ?>
				</a>

				<a class="button dashboard-page" href="<?php echo esc_attr( admin_url( '/' ) ); ?>">
					<?php esc_html_e( 'Dashboard', 'learndash' ); ?>
				</a>
			<?php } ?>
		</div>
		<?php } ?>
		<?php require ld_propanel_get_template( 'ld-propanel-filtering-filters.php' ); ?>

		<?php require ld_propanel_get_template( 'ld-propanel-filtering-emails.php' ); ?>
	</div>
</div>
