<?php
/**
 * Test Mode Admin Bar Additions.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Admin_Bar\Payments;

use WP_Admin_Bar;
use Learndash_Payment_Gateway;

/**
 * Test Mode Admin Bar Additions.
 *
 * @since 4.18.0
 */
class Test_Mode {
	/**
	 * ID to assign to the parent node.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	private string $parent_id = 'learndash__admin-bar--test-mode-indicators';

	/**
	 * ID prefix to use for child nodes.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	private string $child_id_prefix = 'learndash__admin-bar--test-mode-indicator-';

	/**
	 * Creates the Admin Bar menu displaying Test Mode statuses.
	 *
	 * @since 4.18.0
	 *
	 * @param WP_Admin_Bar $admin_bar The WP_Admin_Bar instance, passed by reference.
	 *
	 * @return void
	 */
	public function create( $admin_bar ): void {
		if ( ! current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
			return;
		}

		$gateways = Learndash_Payment_Gateway::get_active_gateways_in_test_mode();

		if ( empty( $gateways ) ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'     => $this->parent_id,
				'parent' => 'top-secondary',
				// We are providing a default of LearnDash LMS -> Settings -> Payments so that keyboard navigation works.
				'href'   => esc_url(
					add_query_arg(
						[
							'page' => 'learndash_lms_payments',
						],
						admin_url( 'admin.php' )
					)
				),
				'title'  => wp_kses(
					sprintf(
						// translators: placeholder: The number of active Payment Gateway Test Modes and HTML for the dropdown arrow.
						__( 'Test Modes Active (%1$1d) %2$2s', 'learndash' ),
						count( $gateways ),
						'<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>'
					),
					[
						'span' => [
							'class'       => true,
							'aria-hidden' => true,
						],
					]
				),
			]
		);

		foreach ( $gateways as $gateway ) {
			$settings_url = $gateway->get_settings_url();

			$admin_bar->add_node(
				[
					'id'     => esc_attr( $this->child_id_prefix . $gateway->get_name() ),
					'parent' => $this->parent_id,
					'href'   => esc_url( $settings_url ),
					'title'  => wp_kses(
						$gateway->get_label(),
						[
							'span' => [
								'class'       => true,
								'aria-hidden' => true,
							],
						]
					),
				]
			);
		}
	}
}
