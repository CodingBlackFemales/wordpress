<?php
/**
 * Reads and writes a course's LearnDash section headings.
 *
 * Section headings are not posts. They are entries in a JSON array held in the
 * course's `course_sections` post meta, and a heading "contains" the lessons
 * that follow it in the course's lesson ordering — `order` is an index into
 * that list, not a parent link.
 *
 * Two consequences shape this class:
 *
 * 1. **All headings are created in one write.** The whole array lives in a
 *    single meta value, so two concurrent read-modify-write cycles would
 *    silently discard one another's headings. Bulk migration therefore creates
 *    every heading it needs once, before any import job runs.
 * 2. **Placement is a whole-course operation.** Because membership is
 *    positional, putting a lesson "under" a heading means ordering the entire
 *    lesson list. That is done once at the end of a batch rather than per job.
 *
 * Structure verified against live course data (P8.6a):
 *
 *     {"order":0,"ID":1631808942506,"post_title":"Test Driven Development",
 *      "url":"","edit_link":"","tree":[],"expanded":false,"type":"section-heading"}
 *
 * `ID` is a millisecond timestamp assigned by the course builder in the
 * browser, not a post ID — so new headings mint one the same way.
 *
 * @class   Bulk\SectionHeadings
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Bulk;

use CodingBlackFemales\SlidesImporter\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SectionHeadings class.
 */
final class SectionHeadings {

	/** Post meta key holding the course's section headings. */
	const META_KEY = 'course_sections';

	/** The `type` every section entry carries. */
	const TYPE = 'section-heading';

	/** Post type of the steps a heading groups. */
	const LESSON_POST_TYPE = 'sfwd-lessons';


	/**
	 * Read a course's section headings, in course order.
	 *
	 * @param  int $course_id Course post ID.
	 * @return array<array<string, mixed>> Section entries.
	 */
	public static function read( int $course_id ): array {
		$raw = get_post_meta( $course_id, self::META_KEY, true );

		if ( empty( $raw ) ) {
			return array();
		}

		$decoded = is_string( $raw ) ? json_decode( $raw, true ) : $raw;

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$sections = array_values(
			array_filter(
				$decoded,
				static fn( $section ): bool => is_array( $section ) && isset( $section['post_title'] )
			)
		);

		usort( $sections, static fn( array $a, array $b ): int => ( (int) ( $a['order'] ?? 0 ) ) <=> ( (int) ( $b['order'] ?? 0 ) ) );

		return $sections;
	}


	/**
	 * Find a heading by title, ignoring case and surrounding whitespace.
	 *
	 * @param  array  $sections Sections from read().
	 * @param  string $title    Heading title to look for.
	 * @return array|null The section entry, or null when absent.
	 */
	public static function find( array $sections, string $title ): ?array {
		$needle = self::normalise( $title );

		if ( $needle === '' ) {
			return null;
		}

		foreach ( $sections as $section ) {
			if ( self::normalise( (string) $section['post_title'] ) === $needle ) {
				return $section;
			}
		}

		return null;
	}


	/**
	 * Ensure every named heading exists on the course, creating what is missing.
	 *
	 * Performs at most one write regardless of how many headings are new, which
	 * is what makes this safe to call before a batch and unsafe to call from
	 * within one.
	 *
	 * New headings are appended after the course's existing content, in the
	 * order given. Their positions are corrected by place_lessons() once the
	 * batch knows which lessons belong under them.
	 *
	 * @param  int      $course_id Course post ID.
	 * @param  string[] $titles    Heading titles required by the batch.
	 * @return array<string, int> Map of normalised title to section ID.
	 */
	public static function ensure( int $course_id, array $titles ): array {
		$sections = self::read( $course_id );
		$map      = array();
		$wanted   = array();

		foreach ( $titles as $title ) {
			$key = self::normalise( $title );
			if ( $key === '' || isset( $wanted[ $key ] ) ) {
				continue;
			}
			$wanted[ $key ] = trim( $title );
		}

		$created = array();
		$next_id = self::next_id( $sections );

		// New headings go after everything already in the course. Reusing the
		// lesson count alone would collide with headings added by an earlier
		// call, and equal `order` values sort unpredictably.
		$order = max( self::lesson_count( $course_id ), self::highest_order( $sections ) + 1 );

		foreach ( $wanted as $key => $title ) {
			$existing = self::find( $sections, $title );

			if ( $existing !== null ) {
				$map[ $key ] = (int) $existing['ID'];
				continue;
			}

			$created[]   = self::entry( $next_id, $order, $title );
			$map[ $key ] = $next_id;
			++$next_id;
			++$order;
		}

		if ( $created !== array() ) {
			self::write( $course_id, array_merge( $sections, $created ) );
			Utils::log(
				'Created course section headings.',
				array(
					'course_id' => $course_id,
					'created'   => count( $created ),
				)
			);
		}

		return $map;
	}


	/**
	 * Order a course's lessons so each sits under its intended heading.
	 *
	 * Run once, after a batch has created its lessons. Existing lessons keep
	 * their relative order and their current heading; newly created ones are
	 * appended to the end of the heading they were imported for.
	 *
	 * @param int   $course_id  Course post ID.
	 * @param array $placements Map of section ID to the lesson IDs to append.
	 */
	public static function place_lessons( int $course_id, array $placements ): void {
		$placements = array_filter( $placements );

		if ( $placements === array() ) {
			return;
		}

		$sections = self::read( $course_id );
		$lessons  = self::ordered_lessons( $course_id );
		$grouped  = self::group_by_section( $sections, $lessons );

		foreach ( $placements as $section_id => $lesson_ids ) {
			$ids = array_map( 'intval', $lesson_ids );

			// A lesson lives in exactly one group. Without lifting it out of
			// wherever it currently sits, it would be written twice and every
			// position after it would shift.
			$grouped = self::detach( $grouped, $ids );

			$key             = (string) $section_id;
			$grouped[ $key ] = array_values( array_unique( array_merge( $grouped[ $key ] ?? array(), $ids ) ) );
		}

		self::apply_order( $course_id, $sections, $grouped );
	}


	/**
	 * Remove lessons from every group they currently appear in.
	 *
	 * @param  array $grouped Lesson IDs keyed by section ID.
	 * @param  int[] $ids     Lessons being moved.
	 * @return array Updated grouping.
	 */
	private static function detach( array $grouped, array $ids ): array {
		foreach ( $grouped as $key => $lessons ) {
			$grouped[ $key ] = array_values( array_diff( $lessons, $ids ) );
		}

		return $grouped;
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Distribute the course's lessons across its headings.
	 *
	 * A lesson belongs to the nearest heading at or before its position; those
	 * before the first heading are held under the '' key.
	 *
	 * @param  array $sections Sections in course order.
	 * @param  array $lessons  Lesson IDs in course order.
	 * @return array<string, int[]> Lesson IDs keyed by section ID, '' for unsectioned.
	 */
	private static function group_by_section( array $sections, array $lessons ): array {
		$grouped = array( '' => array() );
		$current = '';
		$next    = 0;

		foreach ( $lessons as $index => $lesson_id ) {
			$next    = self::open_sections_up_to( $sections, $grouped, $current, $next, $index );
			$grouped[ $current ][] = (int) $lesson_id;
		}

		// Headings beyond the last lesson still need an entry of their own.
		self::open_sections_up_to( $sections, $grouped, $current, $next, PHP_INT_MAX );

		return $grouped;
	}


	/**
	 * Open every heading positioned at or before a lesson index.
	 *
	 * @param  array  $sections Sections in course order.
	 * @param  array  $grouped  Grouping, updated in place.
	 * @param  string $current  The open heading's ID, updated in place.
	 * @param  int    $next     Index of the next unopened heading.
	 * @param  int    $index    Lesson position being placed.
	 * @return int Index of the next unopened heading.
	 */
	private static function open_sections_up_to( array $sections, array &$grouped, string &$current, int $next, int $index ): int {
		while ( isset( $sections[ $next ] ) && (int) ( $sections[ $next ]['order'] ?? 0 ) <= $index ) {
			$current             = (string) $sections[ $next ]['ID'];
			$grouped[ $current ] = $grouped[ $current ] ?? array();
			++$next;
		}

		return $next;
	}


	/**
	 * Write the resolved ordering back to the course.
	 *
	 * Lessons are renumbered by `menu_order` and each heading's `order` is set
	 * to the index at which its group starts, which is how LearnDash reads
	 * membership back.
	 *
	 * @param int   $course_id Course post ID.
	 * @param array $sections  Sections in course order.
	 * @param array $grouped   Lesson IDs keyed by section ID.
	 */
	private static function apply_order( int $course_id, array $sections, array $grouped ): void {
		$sequence = array();
		$updated  = array();

		foreach ( $grouped[''] ?? array() as $lesson_id ) {
			$sequence[] = (int) $lesson_id;
		}

		foreach ( $sections as $section ) {
			$section['order'] = count( $sequence );
			$updated[]        = $section;

			foreach ( $grouped[ (string) $section['ID'] ] ?? array() as $lesson_id ) {
				$sequence[] = (int) $lesson_id;
			}
		}

		self::write( $course_id, $updated );
		self::reorder_steps( $course_id, $sequence );

		Utils::log(
			'Reordered course lessons under section headings.',
			array(
				'course_id' => $course_id,
				'lessons'   => count( $sequence ),
			)
		);
	}


	/**
	 * Rewrite the course's lesson ordering.
	 *
	 * Two mechanisms decide lesson order, and which one applies depends on a
	 * site-wide LearnDash setting:
	 *
	 * - With **shared course steps enabled**, the order is the key order of the
	 *   step tree stored in `ld_course_steps`, queried back with `post__in`.
	 *   `menu_order` is ignored entirely.
	 * - Otherwise the order comes from `menu_order` on each lesson.
	 *
	 * Both are written, so the result is correct either way and stays correct if
	 * the setting is later changed. The step tree is rewritten through
	 * LearnDash's own `set_steps()` — with `keep_sections` so the headings just
	 * written are preserved — rather than by deleting the cached meta, which
	 * would discard the very ordering that is the source of truth.
	 *
	 * @param int   $course_id Course post ID.
	 * @param int[] $sequence  Lesson IDs in their intended order.
	 */
	private static function reorder_steps( int $course_id, array $sequence ): void {
		foreach ( $sequence as $position => $lesson_id ) {
			wp_update_post(
				array(
					'ID'         => $lesson_id,
					'menu_order' => $position,
				)
			);
		}

		$model = self::fresh_steps_model( $course_id );

		if ( $model === null ) {
			return;
		}

		$steps = (array) $model->get_steps( 'h' );
		$key   = self::LESSON_POST_TYPE;

		$lessons = self::attach_missing( (array) ( $steps[ $key ] ?? array() ), $sequence );

		if ( $lessons === null ) {
			return;
		}

		$steps[ $key ] = self::reindex( $lessons, $sequence );

		// set_steps() takes the step-type map itself, and `keep_sections` stops
		// it discarding the headings written moments earlier.
		if ( method_exists( $model, 'set_steps_keeping_sections' ) ) {
			$model->set_steps_keeping_sections( $steps );
			return;
		}

		$model->set_steps( $steps, true );
	}


	/**
	 * Get a course-steps model rebuilt from the course's current content.
	 *
	 * Reloading matters: the cached model was built before this batch created
	 * its lessons, and writing that stale tree back would detach every lesson
	 * the batch just added.
	 *
	 * @param  int $course_id Course post ID.
	 * @return object|null Null when LearnDash is unavailable or lacks the API.
	 */
	private static function fresh_steps_model( int $course_id ): ?object {
		if ( ! class_exists( '\LDLMS_Factory_Post' ) ) {
			return null;
		}

		if ( function_exists( 'learndash_course_set_steps_dirty' ) ) {
			learndash_course_set_steps_dirty( $course_id );
		}

		$model = \LDLMS_Factory_Post::course_steps( $course_id, true );

		if ( ! $model || ! method_exists( $model, 'get_steps' ) || ! method_exists( $model, 'set_steps' ) ) {
			return null;
		}

		return $model;
	}


	/**
	 * Add any lesson the batch is placing that the step tree does not yet hold.
	 *
	 * Writing the tree replaces the course's definitive step list, so a lesson
	 * absent from the read is a lesson about to be detached. Two situations
	 * produce one, and both want the same answer — put it in the tree:
	 *
	 * - The tree was read before this batch created the lesson.
	 * - The row reused a lesson that already existed in **another** course.
	 *   With shared course steps enabled a lesson can belong to several
	 *   courses, and `set_steps()` registers the step against this one, which
	 *   is exactly how the course builder shares a step.
	 *
	 * Only real lessons are added. Anything else in the sequence means a caller
	 * has gone wrong, and writing it into the tree would attach nonsense to the
	 * course, so the reorder is abandoned instead — a course left in its old
	 * order is recoverable by hand, a corrupted step tree much less so.
	 *
	 * @param  array $lessons  Lesson steps keyed by post ID.
	 * @param  int[] $sequence Lessons the batch intends to order.
	 * @return array|null      Lesson steps including the additions, or null to abandon.
	 */
	private static function attach_missing( array $lessons, array $sequence ): ?array {
		$missing = array_diff( $sequence, array_map( 'intval', array_keys( $lessons ) ) );

		if ( $missing === array() ) {
			return $lessons;
		}

		foreach ( $missing as $lesson_id ) {
			if ( get_post_type( $lesson_id ) !== self::LESSON_POST_TYPE ) {
				Utils::log(
					'Skipped reordering: something in the sequence is not a lesson.',
					array( 'post_id' => (int) $lesson_id )
				);
				return null;
			}

			$lessons[ $lesson_id ] = array();
		}

		Utils::log(
			'Attached lessons to the course step tree.',
			array( 'attached' => count( $missing ) )
		);

		return $lessons;
	}


	/**
	 * Reorder a step tree's lessons, keeping each lesson's own children.
	 *
	 * Any lesson not named in the sequence keeps its place at the end, so a
	 * course containing content this batch never touched is left intact.
	 *
	 * @param  array $lessons  Lesson steps keyed by post ID.
	 * @param  int[] $sequence Intended order.
	 * @return array Reordered lesson steps.
	 */
	private static function reindex( array $lessons, array $sequence ): array {
		$ordered = array();

		foreach ( $sequence as $lesson_id ) {
			if ( array_key_exists( $lesson_id, $lessons ) ) {
				$ordered[ $lesson_id ] = $lessons[ $lesson_id ];
			}
		}

		foreach ( $lessons as $lesson_id => $children ) {
			if ( ! array_key_exists( $lesson_id, $ordered ) ) {
				$ordered[ $lesson_id ] = $children;
			}
		}

		return $ordered;
	}


	/**
	 * A course's lesson IDs in course order.
	 *
	 * @param  int $course_id Course post ID.
	 * @return int[]
	 */
	private static function ordered_lessons( int $course_id ): array {
		if ( ! function_exists( 'learndash_course_get_steps_by_type' ) ) {
			return array();
		}

		$steps = learndash_course_get_steps_by_type( $course_id, self::LESSON_POST_TYPE );

		return is_array( $steps ) ? array_values( array_map( 'intval', $steps ) ) : array();
	}


	/**
	 * How many lessons the course currently has.
	 *
	 * @param  int $course_id Course post ID.
	 * @return int
	 */
	private static function lesson_count( int $course_id ): int {
		return count( self::ordered_lessons( $course_id ) );
	}


	/**
	 * Build a section entry.
	 *
	 * Every field LearnDash's builder writes is included; omitting the ones it
	 * does not read would still work, but the course builder round-trips this
	 * array and a partial entry invites subtle breakage on save.
	 *
	 * @param  int    $id    Section ID.
	 * @param  int    $order Index into the lesson list.
	 * @param  string $title Heading title.
	 * @return array<string, mixed>
	 */
	private static function entry( int $id, int $order, string $title ): array {
		return array(
			'order'      => $order,
			'ID'         => $id,
			'post_title' => wp_strip_all_tags( $title ),
			'url'        => '',
			'edit_link'  => '',
			'tree'       => array(),
			'expanded'   => false,
			'type'       => self::TYPE,
		);
	}


	/**
	 * The highest `order` any existing heading occupies.
	 *
	 * @param  array $sections Existing sections.
	 * @return int -1 when there are none, so the next order is 0.
	 */
	private static function highest_order( array $sections ): int {
		$highest = -1;

		foreach ( $sections as $section ) {
			$highest = max( $highest, (int) ( $section['order'] ?? 0 ) );
		}

		return $highest;
	}


	/**
	 * Mint the next section ID.
	 *
	 * LearnDash's builder uses a millisecond timestamp (P8.6a), so this matches
	 * it and steps past any existing value to stay unique within the course.
	 *
	 * @param  array $sections Existing sections.
	 * @return int
	 */
	private static function next_id( array $sections ): int {
		$highest = 0;

		foreach ( $sections as $section ) {
			$highest = max( $highest, (int) ( $section['ID'] ?? 0 ) );
		}

		return max( (int) round( microtime( true ) * 1000 ), $highest + 1 );
	}


	/**
	 * Persist the section array to post meta.
	 *
	 * @param int   $course_id Course post ID.
	 * @param array $sections  Sections to store.
	 */
	private static function write( int $course_id, array $sections ): void {
		update_post_meta(
			$course_id,
			self::META_KEY,
			wp_slash( (string) wp_json_encode( array_values( $sections ), JSON_UNESCAPED_UNICODE ) )
		);
	}


	/**
	 * Normalise a heading title for comparison.
	 *
	 * @param  string $title Raw title.
	 * @return string
	 */
	private static function normalise( string $title ): string {
		return strtolower( trim( wp_strip_all_tags( $title ) ) );
	}
}
