<?php
/**
 * LearnDash Send Invoice Action class.
 *
 * @since 4.19.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Orders\Admin\Actions;

use LearnDash\Core\Models\Invoice;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotice;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;

/**
 * LearnDash Send Invoice Action class.
 *
 * @since 4.19.0
 */
class Send_Invoice {
	/**
	 * Invoice model.
	 *
	 * @since 4.19.0
	 *
	 * @var Invoice
	 */
	private Invoice $invoice;

	/**
	 * Whether to render the notice immediately after attempting to send the Invoice email.
	 *
	 * @since 4.19.0
	 *
	 * @var bool
	 */
	private bool $render_notice = false;

	/**
	 * Constructor for the Send Invoice Action class.
	 *
	 * @since 4.19.0
	 *
	 * @param Invoice $invoice Invoice model.
	 *
	 * @return void
	 */
	public function __construct( Invoice $invoice ) {
		$this->invoice = $invoice;
	}

	/**
	 * Sends the invoice for the Order.
	 *
	 * @since 4.19.0
	 *
	 * @return bool Success/failure.
	 */
	public function send(): bool {
		$success = $this->invoice->send_email();

		if ( $this->render_notice ) {
			$this->show_notice( $success );
		}

		return $success;
	}

	/**
	 * Configures the object to render a notice after attempting to send an Invoice email.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function with_notice(): void {
		$this->render_notice = true;
	}

	/**
	 * Renders an Admin Notice based on success/failure.
	 *
	 * @since 4.19.0
	 *
	 * @param bool $success Whether to create the success or failure Admin Notice.
	 *
	 * @return void
	 */
	private function show_notice( bool $success ): void {
		$notice = $this->create_notice( $success );

		AdminNotices::render( $notice );
	}

	/**
	 * Creates an Admin Notice based on success/failure.
	 *
	 * @since 4.19.0
	 *
	 * @param bool $success Whether to create the success or failure Admin Notice.
	 *
	 * @return AdminNotice
	 */
	private function create_notice( bool $success ): AdminNotice {
		$notice = new AdminNotice(
			'learndash_order_invoice_send_success',
			sprintf(
				// translators: placeholder: order label.
				__( "The %s's invoice email was sent successfully.", 'learndash' ),
				learndash_get_custom_label_lower( 'order' )
			)
		);

		$notice->urgency( 'success' );

		if ( ! $success ) {
			$notice = new AdminNotice(
				'learndash_order_invoice_send_failure',
				sprintf(
					// translators: placeholder: order label.
					__( "The %s's invoice email was not able to be sent.", 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				)
			);

			$notice->urgency( 'error' );
		}

		$notice->autoParagraph( true );

		return $notice;
	}
}
