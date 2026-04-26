<?php
/**
 * Product access options mapper.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Course;

use LearnDash\Core\Models\Product;
use LearnDash\Core\Utilities\Cast;

/**
 * Product access options mapper.
 *
 * @since 4.21.0
 *
 * @phpstan-type KingStatusType 'after-end'|'before-start'|'expiration'|'seats-full'|'seats-remaining'|'before-end'|'after-start'|null
 * @phpstan-type SubjectStatusType 'end-date'|'start-date'|'expiration'
 */
class Product_Access_Options_Mapper {
	/**
	 * King status type 'after-end'.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const KING_STATUS_AFTER_END = 'after-end';

	/**
	 * King status type 'before-start'.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const KING_STATUS_BEFORE_START = 'before-start';

	/**
	 * King status type 'expiration'.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const KING_STATUS_EXPIRATION = 'expiration';

	/**
	 * King status type 'seats-full'.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const KING_STATUS_SEATS_FULL = 'seats-full';

	/**
	 * King status type 'seats-remaining'.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const KING_STATUS_SEATS_REMAINING = 'seats-remaining';

	/**
	 * King status type 'before-end'.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const KING_STATUS_BEFORE_END = 'before-end';

	/**
	 * King status type 'after-start'.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const KING_STATUS_AFTER_START = 'after-start';

	/**
	 * Subject status type 'end-date'.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const SUBJECT_STATUS_END_DATE = 'end-date';

	/**
	 * Subject status type 'start-date'.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const SUBJECT_STATUS_START_DATE = 'start-date';

	/**
	 * Subject status type 'expiration'.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const SUBJECT_STATUS_EXPIRATION = 'expiration';

	/**
	 * Product.
	 *
	 * @since 4.21.0
	 *
	 * @var Product
	 */
	private Product $product;

	/**
	 * Maps the product to the pricing options.
	 *
	 * @since 4.21.0
	 *
	 * @param Product $product Product.
	 *
	 * @return array{king: KingStatusType, subjects: string[]}
	 */
	public function map( Product $product ): array {
		$this->product = $product;

		$king     = $this->map_king();
		$subjects = $this->map_subjects( $king );

		return [
			'king'     => $king,
			'subjects' => $subjects,
		];
	}

	/**
	 * Generates the king based on the product.
	 *
	 * The Access UI “King” is the most relevant access-status information for the user to know about.
	 * It can be one of the following: Start Date, End Date, Access expiration, and Student Limit.
	 *
	 * Access King is determined as such:
	 *      - If only 1 access item is displayed, he is king.
	 *      - Within the King order, whoever is highest becomes king, and the others become subjects
	 *      - Subjects are ordered in the subject list in their king placement
	 *      - If multiple access items that result in “hide all other access items” are applicable follow King order to determine which state shows
	 *      - Access King order:
	 *          - End Date (after end date)
	 *          - Start Date (before start date)
	 *          - Access expiration
	 *          - Student Limit
	 *          - End Date (before end date)
	 *          - Start Date (after start date)
	 *
	 * Student Limit is a special case where he is never a subject. When he is not king,  he has a special position above the enroll button.
	 *
	 * Examples:
	 *  - If a course has an end date and the course has ended, the king is “End Date” with no subjects.
	 *  - If a course has an end date and a start date, and the course has not started, the king is “Start Date” with “End Date” as a subject.
	 *  - If a course has a start date, an end date, and an access expiration, and the course has not started, the king is “Start Date” with “End Date” and “Access Expiration” as subjects.
	 *
	 * @since 4.21.0
	 *
	 * @return KingStatusType
	 */
	protected function map_king(): ?string {
		$end_date  = $this->product->get_end_date();
		$has_ended = $this->product->has_ended();

		if (
			$end_date
			&& $has_ended
		) {
			return self::KING_STATUS_AFTER_END;
		}

		$start_date  = $this->product->get_start_date();
		$has_started = $this->product->has_started();

		if (
			$start_date
			&& ! $has_started
		) {
			return self::KING_STATUS_BEFORE_START;
		}

		$expiration_enabled = $this->product->get_setting( 'expire_access' ) === 'on';
		$expiration_in_days = Cast::to_int( $this->product->get_setting( 'expire_access_days' ) );

		if (
			$expiration_enabled
			&& $expiration_in_days > 0
		) {
			return self::KING_STATUS_EXPIRATION;
		}

		$seats_limit = $this->product->get_seats_limit();

		if ( ! is_null( $seats_limit ) ) {
			return $this->product->get_seats_available() > 0 // @phpstan-ignore-line -- False positive.
				? self::KING_STATUS_SEATS_REMAINING
				: self::KING_STATUS_SEATS_FULL;
		}

		if ( $end_date ) {
			return self::KING_STATUS_BEFORE_END;
		}

		if ( $start_date ) {
			return self::KING_STATUS_AFTER_START;
		}

		return null;
	}

	/**
	 * Maps subjects based on the king.
	 *
	 * When a course has more than one access item, the subjects are the other access items that are not the king,e.g.,
	 * it is secondary access-status information that is relevant for the user to know about. We can have multiple subjects.
	 *
	 * A subject can be one of the following: Start Date, End Date, Access expiration.
	 *
	 * @since 4.21.0
	 *
	 * @param string|null $king King.
	 *
	 * @return SubjectStatusType[]
	 */
	protected function map_subjects( ?string $king ): array {
		// Return early if the king does not have any subjects.

		if (
			$king === null
			|| in_array(
				$king,
				[ self::KING_STATUS_AFTER_END, self::KING_STATUS_SEATS_FULL,self::KING_STATUS_SEATS_REMAINING, self::KING_STATUS_AFTER_START ],
				true
			)
		) {
			return [];
		}

		$start_date = $this->product->get_start_date();
		$end_date   = $this->product->get_end_date();

		$expiration_enabled = $this->product->get_setting( 'expire_access' ) === 'on';
		$expiration_in_days = Cast::to_int( $this->product->get_setting( 'expire_access_days' ) );
		$has_expiration     = $expiration_enabled && $expiration_in_days > 0;

		if (
			is_null( $start_date )
			&& is_null( $end_date )
			&& ! $has_expiration
		) {
			return []; // No subjects.
		}

		// Map conditions to subject arrays.

		$subject_map = [
			self::KING_STATUS_BEFORE_START => array_filter(
				[
					$has_expiration ? self::SUBJECT_STATUS_EXPIRATION : null,
					$end_date ? self::SUBJECT_STATUS_END_DATE : null,
				]
			),
			self::KING_STATUS_EXPIRATION   => array_filter(
				[
					$end_date ? self::SUBJECT_STATUS_END_DATE : null,
					$start_date ? self::SUBJECT_STATUS_START_DATE : null,
				]
			),
			self::KING_STATUS_BEFORE_END   => array_filter(
				[
					$start_date ? self::SUBJECT_STATUS_START_DATE : null,
				]
			),
		];

		// Return the mapped subjects or empty array if king not found.

		/**
		 * List of subjects.
		 *
		 * @var SubjectStatusType[]
		 */
		return $subject_map[ $king ] ?? [];
	}
}
