<?php
/**
 * View: Users Dashboard Widget Item.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var WP_User  $user User.
 * @var Template $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
?>
<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>" class="ld-dashboard-widget-users__item">
	<?php $this->template( 'dashboard/widgets/users/avatar' ); ?>

	<?php $this->template( 'dashboard/widgets/users/title' ); ?>

	<?php $this->template( 'dashboard/widgets/users/custom-label' ); ?>

	<?php $this->template( 'dashboard/widgets/users/icon' ); ?>
</a>
