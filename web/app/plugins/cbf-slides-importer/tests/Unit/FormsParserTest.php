<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Forms\Parser;

/**
 * Covers the mapping from a Forms API response to quiz questions.
 */
final class FormsParserTest extends Unit {

	/** @var array */
	private array $quiz;

	protected function _before(): void {
		$parsed = Parser::parse( codecept_data_dir( 'bin/form.gform' ) );
		$this->assertIsArray( $parsed );
		$this->quiz = $parsed;
	}

	public function testReadsFormMetadata(): void {
		$this->assertSame( 'gform', $this->quiz['source_format'] );
		$this->assertSame( 'question', $this->quiz['unit_label'] );
		$this->assertSame( 'Containerisation Skills Check', $this->quiz['title'] );
		$this->assertTrue( $this->quiz['is_quiz'] );
		$this->assertSame( array(), $this->quiz['slides'] );
	}

	public function testNonQuestionItemsAreDroppedSilently(): void {
		// Text block and page break: neither imported nor reported.
		$this->assertCount( 3, $this->quiz['questions'] );
		$this->assertCount( 3, $this->quiz['skipped'] );
	}

	public function testRadioBecomesSingleAnswerWithKey(): void {
		$q = $this->quiz['questions'][0];

		$this->assertSame( Parser::TYPE_SINGLE, $q['type'] );
		$this->assertSame( 1, $q['index'] );
		$this->assertSame( 2, $q['points'] );
		$this->assertSame( 'Pick one.', $q['description'] );
		$this->assertSame( 'Correct!', $q['correct_msg'] );
		$this->assertSame( 'Review images vs containers.', $q['incorrect_msg'] );
		$this->assertSame(
			array(
				array(
					'text'    => 'A running instance of an image',
					'correct' => true,
				),
				array(
					'text'    => 'A virtual machine',
					'correct' => false,
				),
			),
			$q['answers']
		);
	}

	public function testOtherOptionIsDroppedWithWarning(): void {
		$this->assertCount( 1, $this->quiz['questions'][0]['warnings'] );
	}

	public function testCheckboxBecomesMultipleAnswer(): void {
		$q = $this->quiz['questions'][1];

		$this->assertSame( Parser::TYPE_MULTIPLE, $q['type'] );
		$this->assertSame( 3, $q['points'] );
		$this->assertSame( array( true, true, false ), array_column( $q['answers'], 'correct' ) );
	}

	public function testTextQuestionBecomesEssay(): void {
		$q = $this->quiz['questions'][2];

		$this->assertSame( Parser::TYPE_ESSAY, $q['type'] );
		$this->assertSame( 5, $q['points'] );
		$this->assertSame( array(), $q['answers'] );
	}

	public function testUngradableAndUnsupportedQuestionsAreSkippedWithReasons(): void {
		$skipped = $this->quiz['skipped'];

		$this->assertSame( array( 4, 5, 6 ), array_column( $skipped, 'index' ) );
		$this->assertStringContainsString( 'No correct answer', $skipped[0]['reason'] );
		$this->assertStringContainsString( 'not supported', $skipped[1]['reason'] );
		$this->assertStringContainsString( 'Grid', $skipped[2]['reason'] );
	}

	public function testSingleChoiceWithSeveralCorrectAnswersBecomesMultiple(): void {
		$quiz = Parser::from_form(
			array(
				'items' => array(
					array(
						'title'        => 'Pick',
						'questionItem' => array(
							'question' => array(
								'grading'        => array( 'correctAnswers' => array( 'answers' => array( array( 'value' => 'a' ), array( 'value' => 'b' ) ) ) ),
								'choiceQuestion' => array(
									'type'    => 'RADIO',
									'options' => array( array( 'value' => 'a' ), array( 'value' => 'b' ) ),
								),
							),
						),
					),
				),
			)
		);

		$this->assertSame( Parser::TYPE_MULTIPLE, $quiz['questions'][0]['type'] );
	}

	public function testUnreadableFileIsAnError(): void {
		$this->assertInstanceOf( \WP_Error::class, Parser::parse( '/nonexistent.gform' ) );
	}

	public function testFormatsAreRoutedButNeverUploadable(): void {
		$this->assertSame( 'gform', ParserFactory::detect_format( '/tmp/abc.gform' ) );
		$this->assertNull( ParserFactory::detect_upload_format( 'abc.gform' ) );
		$this->assertSame( 'gform', ParserFactory::format_for_mime( 'application/vnd.google-apps.form' ) );
		$this->assertNull( ParserFactory::format_for_mime( '' ) );
		$this->assertNull( ParserFactory::format_for_mime( 'application/json' ) );
		$this->assertNotContains( 'gform', ParserFactory::extensions() );
		$this->assertContains( 'application/vnd.google-apps.form', ParserFactory::picker_mime_types() );
		$this->assertNotContains( '', ParserFactory::picker_mime_types() );
	}
}
