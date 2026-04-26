<?php
/**
 * This class provides the easy way to interact with an Invoice.
 *
 * @since 4.19.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use InvalidArgumentException;
use LearnDash_Settings_Section_Emails_Purchase_Invoice;

/**
 * Invoice model class.
 *
 * @since 4.19.0
 */
class Invoice extends Model {
	/**
	 * Creates a model from a Transaction.
	 *
	 * @since 4.19.0
	 *
	 * @param Transaction $transaction Transaction model.
	 *
	 * @return static
	 */
	public static function create_from_transaction( Transaction $transaction ): self {
		$model = new static();

		$model->set_transaction( $transaction );

		return $model;
	}

	/**
	 * Returns a transaction for the invoice or null if the invoice is not associated with a transaction.
	 *
	 * @since 4.19.0
	 *
	 * @return Transaction|null
	 */
	public function get_transaction(): ?Transaction {
		$transaction = $this->getAttribute( 'transaction' );

		if ( ! $transaction instanceof Transaction ) {
			$transaction = null;
		}

		/**
		 * Filters an invoice transaction.
		 *
		 * @since 4.19.0
		 *
		 * @param Transaction|null $transaction Transaction model.
		 * @param Invoice          $invoice     Invoice model.
		 *
		 * @return Transaction|null Invoice transaction model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_invoice_transaction',
			$transaction,
			$this
		);
	}

	/**
	 * Send the course/group purchase invoice email.
	 *
	 * @since 4.19.0
	 *
	 * @return bool Email success/failure.
	 */
	public function send_email(): bool {
		$email_settings = LearnDash_Settings_Section_Emails_Purchase_Invoice::get_section_settings_all();

		if (
			'on' !== $email_settings['enabled']
			&& 'yes' !== $email_settings['enabled']
		) {
			return false;
		}

		$transaction = $this->get_transaction();

		if ( ! $transaction ) {
			return false;
		}

		$user    = $transaction->get_user();
		$product = $transaction->get_product();

		if (
			0 === $user->ID
			|| ! $product
		) {
			return false;
		}

		$email_settings = $this->apply_placeholders( $email_settings );

		$pdf = learndash_generate_purchase_invoice( $transaction->get_id() );

		if (
			empty( $email_settings['subject'] )
			|| empty( $pdf )
		) {
			return false;
		}

		return learndash_emails_send(
			$user->user_email,
			$email_settings,
			'',
			[
				$pdf['filepath'] . $pdf['filename'],
			]
		);
	}

	/**
	 * Sets the Transaction for the Invoice.
	 *
	 * @since 4.19.0
	 *
	 * @param Transaction $transaction Transaction model.
	 *
	 * @throws InvalidArgumentException If the Transaction does not have a product.
	 *
	 * @return void
	 */
	private function set_transaction( Transaction $transaction ): void {
		/**
		 * The Parent Transaction doesn't have a Product associated with it itself.
		 * Currently, only one Product is supported per-Child Transaction and it is expected to only
		 * have one Child Transaction per-Parent Transaction.
		 *
		 * TODO: Support multiple Child Transactions within a single Invoice.
		 */
		if ( $transaction->is_parent() ) {
			$transaction = $transaction->get_first_child();
		}

		// An invoice cannot be generated for a transaction without a product.
		if (
			! $transaction instanceof Transaction
			|| ! $transaction->get_product()
		) {
			throw new InvalidArgumentException( esc_html__( 'Transaction does not have a product.', 'learndash' ) );
		}

		$this->setAttribute( 'transaction', $transaction );
	}

	/**
	 * Applies placeholders to the provided email settings.
	 *
	 * @since 4.19.0
	 *
	 * @param array{subject?: string, message?: string} $email_settings Saved email settings.
	 *
	 * @return array{subject?: string, message?: string}
	 */
	private function apply_placeholders( array $email_settings ): array {
		$transaction = $this->get_transaction();

		if ( ! $transaction ) {
			return $email_settings;
		}

		$product = $transaction->get_product();
		$user    = $transaction->get_user();

		if (
			$user->ID <= 0
			|| ! $product
		) {
			return $email_settings;
		}

		$placeholders = [
			'{user_login}'   => $user->user_login,
			'{first_name}'   => $user->user_firstname,
			'{last_name}'    => $user->user_lastname,
			'{display_name}' => $user->display_name,
			'{user_email}'   => $user->user_email,
			'{post_title}'   => $transaction->get_product_name(),
		];

		/** This filter is documented in includes/payments/ld-purchase-invoice-functions.php */
		$placeholders = apply_filters(
			'learndash_purchase_invoice_email_placeholders',
			$placeholders,
			$user->ID,
			$product->get_id()
		);

		/**
		 * Filters purchase invoice email subject.
		 *
		 * @since 4.2.0
		 *
		 * @param string $email_subject Email subject text.
		 * @param int    $user_id       User ID.
		 * @param int    $product_id    Transaction Product ID.
		 */
		$email_settings['subject'] = apply_filters(
			'learndash_purchase_invoice_email_subject',
			$email_settings['subject'] ?? '',
			$user->ID,
			$product->get_id()
		);

		if ( ! empty( $email_settings['subject'] ) ) {
			$email_settings['subject'] = learndash_emails_parse_placeholders(
				$email_settings['subject'],
				$placeholders
			);
		}

		/**
		 * Filters purchase invoice email message.
		 *
		 * @since 4.2.0
		 *
		 * @param string $email_message Email message text.
		 * @param int    $user_id       User ID.
		 * @param int    $product_id    Transaction Product ID.
		 */
		$email_settings['message'] = apply_filters(
			'learndash_purchase_invoice_email_message',
			$email_settings['message'] ?? '',
			$user->ID,
			$product->get_id()
		);

		if ( ! empty( $email_settings['message'] ) ) {
			$email_settings['message'] = learndash_emails_parse_placeholders(
				$email_settings['message'],
				$placeholders
			);
		}

		return $email_settings;
	}
}
