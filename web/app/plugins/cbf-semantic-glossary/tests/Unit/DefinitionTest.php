<?php
/**
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossary\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossary\Entry\Definition;
use CodingBlackFemales\SemanticGlossary\Entry\Markdown;

/**
 * Covers definition layout and the Markdown subset.
 */
final class DefinitionTest extends Unit {

	public function testSingleParagraphIsUnwrapped(): void {
		$this->assertSame( 'A named line.', Definition::layout( 'A named line.' ) );
		$this->assertSame( 'A named line.', Definition::layout( '<p>A named line.</p>' ) );
	}

	public function testSeveralParagraphsAndListsAreKept(): void {
		$this->assertSame( "<p>One.</p>\n<p>Two.</p>", Definition::layout( "<p>One.</p>\n<p>Two.</p>" ) );
		$this->assertSame( '<ul><li>a</li></ul>', Definition::layout( '<ul><li>a</li></ul>' ) );
		$this->assertSame( '', Definition::layout( '  ' ) );
	}

	public function testSanitiseKeepsInlineMarkupOnly(): void {
		$this->assertSame(
			'Use <code>git</code> <strong>now</strong>.',
			Definition::sanitise( '<div>Use <code>git</code> <strong>now</strong>.<script>x()</script></div>' )
		);
	}

	public function testMarkdownInline(): void {
		$this->assertSame(
			'<p>A <code>.git</code> dir, <strong>bold</strong>, <em>it</em>, <em>also</em> and <a href="https://git-scm.com">Git</a>.</p>',
			Markdown::to_html( 'A `.git` dir, **bold**, *it*, _also_ and [Git](https://git-scm.com).' )
		);
	}

	public function testMarkdownLeavesCodeLiteral(): void {
		$this->assertSame( '<p><code>*args &lt;T&gt;</code> and snake_case_name</p>', Markdown::to_html( '`*args <T>` and snake_case_name' ) );
	}

	public function testMarkdownParagraphsAndEscaping(): void {
		$this->assertSame( '<p>One &lt;b&gt; line</p>', Markdown::to_html( "One <b>\nline" ) );
		$this->assertSame( "<p>One</p>\n<p>Two</p>", Markdown::to_html( "One\n\n\nTwo" ) );
	}

	public function testMarkdownNeutralisesUnsafeLinks(): void {
		$this->assertSame( '<p><a href="#">x</a></p>', Markdown::to_html( '[x](javascript:alert)' ) );
	}

	public function testHtmlInputPassesThrough(): void {
		$this->assertTrue( Markdown::is_html( 'A <code>x</code>' ) );
		$this->assertFalse( Markdown::is_html( 'a < b and c > d' ) );
		$this->assertSame( 'A <code>x</code>', Markdown::to_definition( 'A <code>x</code>' ) );
		$this->assertSame( '<p>A <code>x</code></p>', Markdown::to_definition( 'A `x`' ) );
	}
}
