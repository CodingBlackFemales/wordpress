<?php
/**
 * `wp glossary course`.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Cli;

use CodingBlackFemales\SemanticGlossary\Audit\Auditor;
use CodingBlackFemales\SemanticGlossary\Cli\Output;
use CodingBlackFemales\SemanticGlossary\Entry\Repository;
use CodingBlackFemales\SemanticGlossary\Reference\Index;
use CodingBlackFemales\SemanticGlossary\Reference\PostFields;
use CodingBlackFemales\SemanticGlossary\Render\GlossaryBuilder;
use CodingBlackFemales\SemanticGlossary\Render\PostGlossary;
use CodingBlackFemales\SemanticGlossary\Settings;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Cache;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\CourseAudit;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\CourseTerms;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Resolver;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Steps;
use CodingBlackFemales\SemanticGlossaryLearnDash\Render\CourseGlossary;
use CodingBlackFemales\SemanticGlossaryLearnDash\Render\CourseOptions;
use WP_CLI;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inspect course glossaries: what each learner sees, where entries are introduced, and what breaks the first-mention rule.
 *
 * Read-only apart from cache-flush: course structure and enrolment are
 * edited through LearnDash.
 *
 * ## EXAMPLES
 *
 *     wp glossary course list
 *     wp glossary course terms 5943 --as-user=learner@example.com
 *     wp glossary course first-use 5943 --term=branch
 *     wp glossary course audit 5943
 *     wp glossary course render 5943 --as-user=42 > glossary.html
 *     wp glossary course cache-flush
 */
final class CourseCommand {

	/**
	 * Courses with the number of glossary entries their lessons and topics reference.
	 *
	 * ## OPTIONS
	 *
	 * [--fields=<fields>]
	 * : Comma-separated fields.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - ids
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp glossary course list --format=json
	 *
	 * @subcommand list
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function list_( array $args, array $assoc_args ): void {
		$items = array_map(
			fn ( int $id ) => array(
				'id'      => $id,
				'title'   => Steps::title( $id ),
				'status'  => get_post_status( $id ),
				'steps'   => count( Steps::ids( $id ) ),
				'entries' => count( Resolver::first_use( $id ) ),
			),
			Steps::course_ids()
		);

		Output::items( $assoc_args, $items, array( 'id', 'title', 'status', 'steps', 'entries' ) );
	}


	/**
	 * The resolved course glossary, alphabetically.
	 *
	 * ## OPTIONS
	 *
	 * <course-id>
	 * : Course.
	 *
	 * [--as-user=<user>]
	 * : Apply this learner's enrolment, drip schedule and linear progression (ID, login or email). Without it, every entry is listed, as an editor sees the course.
	 *
	 * [--lesson=<id>...]
	 * : Only entries introduced in these lessons or topics. Repeat for several.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated fields.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - ids
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp glossary course terms 5943
	 *     wp glossary course terms 5943 --as-user=learner --format=count
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function terms( array $args, array $assoc_args ): void {
		$terms   = self::resolve( self::course( $args ), $assoc_args );
		$lessons = array_map( 'intval', Output::values( $assoc_args, 'lesson' ) );
		$items   = array();

		foreach ( GlossaryBuilder::items( $terms->entries(), $terms->inline_ids() ) as $item ) {
			$step = $terms->source( $item->entry->id );

			if ( $step === null || ( $lessons !== array() && ! in_array( $step->id, $lessons, true ) ) ) {
				continue;
			}

			$items[] = array(
				'id'         => $item->entry->id,
				'slug'       => $item->entry->slug,
				'display'    => $item->entry->display(),
				'lesson_id'  => $step->id,
				'lesson'     => $step->title,
				'inline'     => Output::yes_no( $item->has_inline ),
				'lesson_url' => $step->url,
			);
		}

		Output::items( $assoc_args, $items, array( 'id', 'display', 'lesson_id', 'lesson', 'inline' ) );

		if ( empty( $assoc_args['format'] ) || $assoc_args['format'] === 'table' ) {
			WP_CLI::log( sprintf( '%d listed, %d still locked.', count( $items ), $terms->locked_count() ) );
		}
	}


	/**
	 * For each entry, the lesson or topic that introduces it in course order.
	 *
	 * This is what the "Introduced in" link and the entry screen's "First used in" use.
	 *
	 * ## OPTIONS
	 *
	 * <course-id>
	 * : Course.
	 *
	 * [--term=<term>]
	 * : One entry, by ID or slug.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated fields.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - ids
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp glossary course first-use 5943
	 *     wp glossary course first-use 5943 --term=branch
	 *
	 * @subcommand first-use
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function first_use( array $args, array $assoc_args ): void {
		$term  = (string) ( $assoc_args['term'] ?? '' );
		$items = array();

		foreach ( Resolver::first_use( self::course( $args ) ) as $row ) {
			if ( $term !== '' && $term !== (string) $row['id'] && $term !== $row['slug'] ) {
				continue;
			}

			$items[] = array(
				'id'        => $row['id'],
				'slug'      => $row['slug'],
				'display'   => $row['display'],
				'lesson_id' => $row['post_id'],
				'lesson'    => Steps::title( (int) $row['post_id'] ),
				'inline'    => Output::yes_no( $row['inline'] ),
				'text'      => (string) $row['text'],
			);
		}

		if ( $term !== '' && $items === array() ) {
			WP_CLI::error( sprintf( 'No entry "%s" is referenced in this course.', $term ) );
		}

		Output::items( $assoc_args, $items, array( 'id', 'display', 'lesson_id', 'lesson', 'inline', 'text' ) );
	}


	/**
	 * Check a course against the first-mention rule.
	 *
	 * Reports:
	 *
	 * - introduced-late: an entry marked in a later lesson than one that already uses it in prose, unmarked;
	 * - no-glossary: a lesson or topic with references but no Glossary block;
	 * - no-course: a lesson or topic anywhere on the site with references but no course.
	 *
	 * ## OPTIONS
	 *
	 * <course-id>
	 * : Course.
	 *
	 * [--strict]
	 * : Exit with status 1 when anything is reported.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated fields.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp glossary course audit 5943 --strict
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function audit( array $args, array $assoc_args ): void {
		$course_id = self::course( $args );
		$items     = array_merge( self::late_introductions( $course_id ), self::missing_glossaries( $course_id ), self::steps_without_course() );

		Output::items( $assoc_args, $items, array( 'post_id', 'post_title', 'issue', 'entry_id', 'text', 'detail' ) );

		if ( ! empty( $assoc_args['strict'] ) && $items !== array() ) {
			WP_CLI::halt( 1 );
		}
	}


	/**
	 * Print the Course Glossary block's front-end HTML, for snapshot tests of the visibility logic.
	 *
	 * ## OPTIONS
	 *
	 * <course-id>
	 * : Course.
	 *
	 * [--as-user=<user>]
	 * : Render for this learner (ID, login or email). Without it, every entry is listed.
	 *
	 * ## EXAMPLES
	 *
	 *     wp glossary course render 5943 --as-user=42 > glossary.html
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function render( array $args, array $assoc_args ): void {
		$terms = self::resolve( self::course( $args ), $assoc_args );

		WP_CLI::line( CourseGlossary::render( $terms, CourseOptions::from_block( array(), 'course-glossary-heading' ) ) );
	}


	/**
	 * Clear the cached step order and first-use maps, e.g. after a bulk import.
	 *
	 * ## OPTIONS
	 *
	 * [<course-id>]
	 * : Only this course. Without it, every course.
	 *
	 * ## EXAMPLES
	 *
	 *     wp glossary course cache-flush
	 *
	 * @subcommand cache-flush
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function cache_flush( array $args, array $assoc_args ): void {
		$course_id = isset( $args[0] ) ? self::course( $args ) : 0;

		Cache::flush( $course_id );

		WP_CLI::success( $course_id > 0 ? sprintf( 'Flushed the glossary caches for course %d.', $course_id ) : 'Flushed the glossary caches for every course.' );
	}


	/**
	 * The course named by the first argument, or exit.
	 *
	 * @param string[] $args Positional arguments.
	 */
	private static function course( array $args ): int {
		$course_id = (int) ( $args[0] ?? 0 );

		if ( ! Steps::is_course( $course_id ) ) {
			WP_CLI::error( sprintf( '%s is not a LearnDash course.', $args[0] ?? '(none)' ) );
		}

		return $course_id;
	}


	/**
	 * The course glossary for --as-user, or the full set.
	 *
	 * The learner also becomes the current user: some LearnDash checks read
	 * the current user rather than the one they are given.
	 *
	 * @param int                  $course_id  Course.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	private static function resolve( int $course_id, array $assoc_args ): CourseTerms {
		if ( empty( $assoc_args['as-user'] ) ) {
			return Resolver::full( $course_id );
		}

		$value = (string) $assoc_args['as-user'];
		$user  = is_numeric( $value ) ? get_user_by( 'id', (int) $value ) : get_user_by( is_email( $value ) ? 'email' : 'login', $value );

		if ( ! $user ) {
			WP_CLI::error( sprintf( 'No user "%s".', $value ) );
		}

		wp_set_current_user( $user->ID );

		return Resolver::for_learner( $course_id, $user );
	}


	/**
	 * Entries used unmarked before the lesson that introduces them.
	 *
	 * @param int $course_id Course.
	 * @return array<int, array<string, mixed>>
	 */
	private static function late_introductions( int $course_id ): array {
		$step_ids  = Steps::ids( $course_id );
		$first_use = array_column( Resolver::first_use( $course_id ), 'post_id', 'id' );
		$unmarked  = array();

		foreach ( $step_ids as $step_id ) {
			foreach ( Auditor::audit( (string) get_post_field( 'post_content', $step_id ), Repository::instance(), PostFields::ignored( $step_id ) ) as $finding ) {
				if ( $finding['type'] === Auditor::UNMARKED ) {
					$unmarked[ $step_id ][ $finding['entry_id'] ] ??= $finding['text'];
				}
			}
		}

		return array_map(
			fn ( array $late ) => self::finding(
				$late['step_id'],
				CourseAudit::INTRODUCED_LATE,
				$late['entry_id'],
				$late['text'],
				sprintf( 'marked first in %d (%s)', $late['introduced_in'], Steps::title( $late['introduced_in'] ) )
			),
			CourseAudit::introduced_late( $step_ids, $first_use, $unmarked )
		);
	}


	/**
	 * Lessons and topics with references but no Glossary block.
	 *
	 * @param int $course_id Course.
	 * @return array<int, array<string, mixed>>
	 */
	private static function missing_glossaries( int $course_id ): array {
		$items = array();

		foreach ( Steps::ids( $course_id ) as $step_id ) {
			$post = get_post( $step_id );

			if ( $post instanceof WP_Post && PostGlossary::has_references( $post ) && ! PostGlossary::has_glossary( $post ) ) {
				$items[] = self::finding( $step_id, CourseAudit::NO_GLOSSARY, 0, '', Settings::auto_append() ? 'appended automatically by the site setting' : 'no glossary is shown' );
			}
		}

		return $items;
	}


	/**
	 * Lessons and topics anywhere on the site with references but no course.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function steps_without_course(): array {
		$items = array();

		foreach ( Steps::TYPES as $type ) {
			foreach ( Index::post_ids( $type ) as $post_id ) {
				if ( (int) learndash_get_course_id( (int) $post_id, true ) === 0 ) {
					$items[] = self::finding( (int) $post_id, CourseAudit::NO_COURSE, 0, '', 'references are not in any course glossary' );
				}
			}
		}

		return $items;
	}


	/**
	 * One audit row.
	 *
	 * @param int    $post_id  Post.
	 * @param string $issue    Finding type.
	 * @param int    $entry_id Entry, or 0.
	 * @param string $text     Text found, or empty.
	 * @param string $detail   Explanation.
	 * @return array<string, mixed>
	 */
	private static function finding( int $post_id, string $issue, int $entry_id, string $text, string $detail ): array {
		return array(
			'post_id'    => $post_id,
			'post_title' => Steps::title( $post_id ),
			'issue'      => $issue,
			'entry_id'   => $entry_id > 0 ? $entry_id : '',
			'text'       => $text,
			'detail'     => $detail,
		);
	}
}
