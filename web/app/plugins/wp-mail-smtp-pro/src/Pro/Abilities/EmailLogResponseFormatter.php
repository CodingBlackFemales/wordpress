<?php

namespace WPMailSMTP\Pro\Abilities;

use DateTime;
use DateTimeZone;
use Exception;
use WPMailSMTP\Pro\Emails\Logs\Email;

/**
 * Formats Email Log entities into the abilities-API wire shape.
 *
 * Owns the email-summary and email-detail payloads so the registered output
 * schemas and the runtime rows stay in agreement. Lives in Pro because the
 * Email Log subsystem it reads from only exists in Pro.
 *
 * @since 4.9.0
 */
class EmailLogResponseFormatter {

	/**
	 * Format an email log row to its summary shape (no body content).
	 *
	 * @since 4.9.0
	 *
	 * @param Email $email Email model.
	 *
	 * @return array
	 */
	public function email_summary( Email $email ) {

		return [
			'id'              => $email->get_id(),
			'subject'         => $email->get_subject(),
			'from'            => $email->get_header( 'From' ),
			'to'              => $this->people_to_array( $email->get_people( 'to' ) ),
			'status'          => $this->status_label( $email->get_status() ),
			'date_sent'       => $this->date_sent_or_null( $email ),
			'mailer'          => $email->get_mailer(),
			'has_attachments' => $email->get_attachments() > 0,
		];
	}

	/**
	 * Format an email log row to its detail shape (includes body content).
	 *
	 * @since 4.9.0
	 *
	 * @param Email $email Email model.
	 *
	 * @return array
	 */
	public function email_detail( Email $email ) {

		return [
			'id'               => $email->get_id(),
			'subject'          => $email->get_subject(),
			'from'             => $email->get_header( 'From' ),
			'to'               => $this->people_to_array( $email->get_people( 'to' ) ),
			'cc'               => $this->people_to_array( $email->get_people( 'cc' ) ),
			'bcc'              => $this->people_to_array( $email->get_people( 'bcc' ) ),
			'reply_to'         => $this->people_to_array( $email->get_people( 'reply_to' ) ),
			'status'           => $this->status_label( $email->get_status() ),
			'date_sent'        => $this->date_sent_or_null( $email ),
			'mailer'           => $email->get_mailer(),
			'content_plain'    => $email->get_content_plain(),
			'content_html'     => $email->get_content_html(),
			'headers'          => $this->headers_to_array( $email ),
			'attachment_count' => $email->get_attachments(),
			'error_message'    => $email->get_error_text(),
			'error_code'       => $email->get_error_code(),
		];
	}

	/**
	 * Map an internal status int to its public label.
	 *
	 * @since 4.9.0
	 *
	 * @param int $status Internal status int.
	 *
	 * @return string
	 */
	public function status_label( $status ) {

		return AbstractEmailLogAbility::STATUS_INTERNAL_TO_PUBLIC[ (int) $status ] ?? 'unsent';
	}

	/**
	 * Resolve the ISO 8601 send date, or null when the email was never sent.
	 *
	 * The model defaults an empty send date to "now" for rows that were never
	 * sent (unsent or blocked), which would misreport those as just-sent;
	 * surface null instead.
	 *
	 * @since 4.9.0
	 *
	 * @param Email $email Email model.
	 *
	 * @return string|null
	 */
	public function date_sent_or_null( Email $email ) {

		if ( in_array( $email->get_status(), [ Email::STATUS_UNSENT, Email::STATUS_BLOCKED ], true ) ) {
			return null;
		}

		return $this->to_iso8601( $email->get_date_sent() );
	}

	/**
	 * Decode the stored headers JSON into an array.
	 *
	 * @since 4.9.0
	 *
	 * @param Email $email Email model.
	 *
	 * @return array
	 */
	public function headers_to_array( Email $email ) {

		$decoded = json_decode( $email->get_headers(), true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Normalize a `get_people()` result to a flat array of address strings.
	 *
	 * @since 4.9.0
	 *
	 * @param mixed $people People value from the Email model.
	 *
	 * @return array
	 */
	public function people_to_array( $people ) {

		if ( empty( $people ) ) {
			return [];
		}

		$out = [];

		foreach ( (array) $people as $person ) {
			// Recipients are stored as flat email strings, but tolerate the
			// PHPMailer [ email, name ] pair shape by taking the address.
			if ( is_array( $person ) ) {
				$person = reset( $person );
			}

			if ( is_scalar( $person ) && $person !== '' ) {
				$out[] = (string) $person;
			}
		}

		return $out;
	}

	/**
	 * Convert a DateTime to an ISO 8601 UTC string.
	 *
	 * @since 4.9.0
	 *
	 * @param DateTime $datetime Date to format.
	 *
	 * @return string
	 */
	public function to_iso8601( DateTime $datetime ) {

		// Clone before retiming so the caller's DateTime keeps its own timezone;
		// formatting on a UTC copy guarantees the `+00:00` offset the docblock promises.
		$datetime = clone $datetime;

		$datetime->setTimezone( new DateTimeZone( 'UTC' ) );

		return $datetime->format( 'c' );
	}

	/**
	 * Convert a MySQL datetime string (UTC) to an ISO 8601 string.
	 *
	 * @since 4.9.0
	 *
	 * @param string $mysql_datetime MySQL datetime.
	 *
	 * @return string
	 */
	public function mysql_to_iso8601( $mysql_datetime ) {

		try {
			$date = new DateTime( $mysql_datetime, new DateTimeZone( 'UTC' ) );
		} catch ( Exception $e ) {
			return '';
		}

		return $this->to_iso8601( $date );
	}
}
