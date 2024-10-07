<?php
/**
 * WPForms Builder Context Menu (top) Template, Pro version.
 *
 * @since 1.8.8
 *
 * @var int  $form_id          The form ID.
 * @var bool $is_form_template Whether it's a form template (`wpforms-template`), or form (`wpforms`).
 * @var bool $has_entries      Whether the form has entries.
 * @var bool $has_payments     Whether the form has payments.
 * @var bool $show_whats_new   Whether to show the What's New menu item.
 * @var bool $can_duplicate    Whether the form can be duplicated.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
?>

<div class="wpforms-context-menu wpforms-context-menu-dropdown" id="wpforms-context-menu">
	<ul class="wpforms-context-menu-list">

		<?php if ( $is_form_template ) : ?>

			<?php if ( $can_duplicate ) : ?>
				<li class="wpforms-context-menu-list-item"
					data-action="duplicate-template"
					data-action-url="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'duplicate', 'form_id' => $form_id ] ), 'wpforms_duplicate_form_nonce' ) ); ?>"
				>
					<span class="wpforms-context-menu-list-item-icon">
						<i class="fa fa-copy"></i>
					</span>

					<span class="wpforms-context-menu-list-item-text">
						<?php esc_html_e( 'Duplicate Template', 'wpforms' ); ?>
					</span>
				</li>
			<?php endif; ?>

		<?php else : ?>

			<?php if ( $can_duplicate ) : ?>
				<li class="wpforms-context-menu-list-item"
					data-action="duplicate-form"
					data-action-url="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'duplicate', 'form_id' => $form_id ] ), 'wpforms_duplicate_form_nonce' ) ); ?>"
				>
					<span class="wpforms-context-menu-list-item-icon">
						<i class="fa fa-copy"></i>
					</span>

					<span class="wpforms-context-menu-list-item-text">
						<?php esc_html_e( 'Duplicate Form', 'wpforms' ); ?>
					</span>
				</li>
			<?php endif; ?>

			<li class="wpforms-context-menu-list-item"
				data-action="save-as-template"
				data-action-url="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'save_as_template', 'form_id' => $form_id ] ), 'wpforms_save_as_template_form_nonce' ) ); ?>"
			>
				<span class="wpforms-context-menu-list-item-icon">
					<i class="fa fa-file-text-o"></i>
				</span>

				<span class="wpforms-context-menu-list-item-text">
					<?php esc_html_e( 'Save as Template', 'wpforms' ); ?>
				</span>
			</li>

		<?php endif; ?>

		<?php if ( $can_duplicate || ! $is_form_template ) : ?>
			<li class="wpforms-context-menu-list-divider"></li>
		<?php endif; ?>

		<li class="<?php echo esc_attr( $has_entries ? 'wpforms-context-menu-list-item' : 'wpforms-context-menu-list-item wpforms-context-menu-list-item-inactive' ); ?>"
			data-action="view-entries"
			data-action-url="<?php echo $has_entries ? esc_url( admin_url( 'admin.php?page=wpforms-entries&view=list&form_id=' . $form_id ) ) : ''; ?>"
		>
			<span class="wpforms-context-menu-list-item-icon">
				<i class="fa fa-envelope-o"></i>
			</span>

			<span class="wpforms-context-menu-list-item-text">
				<?php esc_html_e( 'View Entries', 'wpforms' ); ?>
			</span>
		</li>

		<li class="<?php echo esc_attr( $has_payments ? 'wpforms-context-menu-list-item' : 'wpforms-context-menu-list-item wpforms-context-menu-list-item-inactive' ); ?>"
			data-action="view-payments"
			data-action-url="<?php echo $has_payments ? esc_url( admin_url( 'admin.php?page=wpforms-payments&form_id=' . $form_id ) ) : ''; ?>"
		>
			<span class="wpforms-context-menu-list-item-icon">
				<i class="fa fa-money"></i>
			</span>

			<span class="wpforms-context-menu-list-item-text">
				<?php esc_html_e( 'View Payments', 'wpforms' ); ?>
			</span>
		</li>

		<li class="wpforms-context-menu-list-divider"></li>

		<li class="wpforms-context-menu-list-item"
			data-action="keyboard-shortcuts"
		>
			<span class="wpforms-context-menu-list-item-icon">
				<i class="fa fa-keyboard-o"></i>
			</span>

			<span class="wpforms-context-menu-list-item-text">
				<?php esc_html_e( 'Keyboard Shortcuts', 'wpforms' ); ?>
			</span>
		</li>

		<?php if ( $show_whats_new ) : ?>

			<li class="wpforms-context-menu-list-item"
				data-action="whats-new"
			>
			<span class="wpforms-context-menu-list-item-icon">
				<i class="fa fa-bullhorn"></i>
			</span>

				<span class="wpforms-context-menu-list-item-text">
				<?php esc_html_e( 'What\'s New', 'wpforms' ); ?>
			</span>
			</li>

		<?php endif; ?>

	</ul>
</div>
