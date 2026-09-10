<?php
/**
 * Regenerates the binary fixtures under tests/_data/.
 *
 * Run from the plugin root: `php tests/_data/build-fixtures.php`
 *
 * The fixtures are committed so the suite is deterministic and needs no network
 * or Office install. They are small and synthetic on purpose — each one exists
 * to exercise specific parser behaviour, and the assertions in tests/Unit name
 * the behaviour they check. Real CBF decks are far larger and cannot be
 * committed; point CBF_SI_FIXTURE_DIR at a directory of them to run the
 * corpus test in addition to these.
 *
 * DOCX and PDF are written as raw bytes rather than through a library, because
 * both need structure the writers will not produce on demand: exact numbering
 * definitions for ordered lists, and precise glyph positions with a footer-band
 * image for the PDF text and image extractors.
 *
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

require dirname( __DIR__, 2 ) . '/vendor/autoload.php';

/** Synthetic advance per glyph, as a fraction of the font size. */
const PDF_GLYPH_EM = 0.5;

/** Word-gap advance, as a multiple of one glyph advance. */
const PDF_WORD_GAP_EM = 2.5;

$out = __DIR__ . '/bin';
if ( ! is_dir( $out ) ) {
	mkdir( $out, 0755, true );
}

build_pptx( $out . '/deck.pptx' );
build_docx( $out . '/document.docx' );
build_pdf( $out . '/slides.pdf' );

foreach ( glob( $out . '/*' ) as $file ) {
	printf( "%-16s %7d bytes\n", basename( $file ), filesize( $file ) );
}


/**
 * A 960x540 deck: cover, a two-column slide, a bulleted slide with a code run,
 * a hidden slide, and a slide with a content image plus a footer-band image.
 *
 * @param string $path Destination.
 */
function build_pptx( string $path ): void {
	$prs = new \PhpOffice\PhpPresentation\PhpPresentation();
	$prs->getLayout()->setDocumentLayout(
		\PhpOffice\PhpPresentation\DocumentLayout::LAYOUT_SCREEN_16X9
	);

	// Slide 1 — cover.
	$cover = $prs->getActiveSlide();
	pptx_title( $cover, 'Fixture Deck' );

	// Slide 2 — title plus two side-by-side text boxes.
	$columns = $prs->createSlide();
	pptx_title( $columns, 'Two Columns' );
	pptx_text( $columns, 'Left body text.', 60, 140, 380, 200, 18 );
	pptx_text( $columns, 'Right body text.', 520, 140, 380, 200, 18 );

	// Slide 3 — bullets, plus a monospaced paragraph and an inline code run.
	$bullets = $prs->createSlide();
	pptx_title( $bullets, 'Bullets' );
	$shape = pptx_shape( $bullets, 60, 140, 840, 240 );
	pptx_bullet( $shape, 'First item', 18 );
	pptx_bullet( $shape, 'Second item', 18 );
	pptx_run( pptx_paragraph( $shape ), 'composer install', 18, array( 'name' => 'Consolas' ) );
	$mixed = pptx_paragraph( $shape );
	pptx_run( $mixed, 'Run ', 18 );
	pptx_run( $mixed, 'npm test', 18, array( 'name' => 'Consolas' ) );
	pptx_run( $mixed, ' first.', 18 );

	// Slide 4 — hidden; the show="0" attribute is written in below.
	$hidden = $prs->createSlide();
	pptx_title( $hidden, 'Hidden Slide' );
	pptx_text( $hidden, 'Must not be imported.', 60, 140, 840, 200, 18 );

	// Slide 5 — one image in the content area, one in the footer band.
	$images = $prs->createSlide();
	pptx_title( $images, 'With Images' );
	$png = png_bytes( 64, 48 );
	pptx_image( $images, $png, 60, 140, 160, 120 );
	pptx_image( $images, $png, 880, 490, 40, 30 );

	( new \PhpOffice\PhpPresentation\Writer\PowerPoint2007( $prs ) )->save( $path );

	mark_slide_hidden( $path, 4 );
}

/**
 * Add a slide's title placeholder.
 *
 * The parser reads titles from the placeholder type, not from position, so the
 * fixture needs a real one rather than an ordinary text box.
 *
 * @param object $slide PhpPresentation slide.
 * @param string $text  Title text.
 */
function pptx_title( $slide, string $text ): void {
	$shape = pptx_text( $slide, $text, 60, 30, 840, 60, 32 );
	$shape->setPlaceHolder(
		new \PhpOffice\PhpPresentation\Shape\Placeholder(
			\PhpOffice\PhpPresentation\Shape\Placeholder::PH_TYPE_TITLE
		)
	);
}

/**
 * Start a paragraph with no bullet.
 *
 * RichText::createParagraph() clones the previous paragraph's style, so a
 * paragraph following a bulleted one inherits its bullet unless reset.
 *
 * @param object $shape Rich-text shape.
 */
function pptx_paragraph( $shape ) {
	$paragraph = $shape->createParagraph();
	$paragraph->getBulletStyle()->setBulletType( \PhpOffice\PhpPresentation\Style\Bullet::TYPE_NONE );
	return $paragraph;
}

/** Add a text box holding one paragraph. */
function pptx_text( $slide, string $text, int $x, int $y, int $w, int $h, int $size, array $font = array() ) {
	$shape = pptx_shape( $slide, $x, $y, $w, $h );
	pptx_run( pptx_paragraph( $shape ), $text, $size, $font );
	return $shape;
}

/** Add an empty rich-text box at a position. */
function pptx_shape( $slide, int $x, int $y, int $w, int $h ) {
	$shape = $slide->createRichTextShape();
	$shape->setOffsetX( $x )->setOffsetY( $y )->setWidth( $w )->setHeight( $h );
	// createRichTextShape() seeds an empty paragraph; drop it so callers control content.
	$shape->setParagraphs( array() );
	return $shape;
}

/** Append a styled run to a paragraph. */
function pptx_run( $paragraph, string $text, int $size, array $font = array() ): void {
	$run = $paragraph->createTextRun( $text );
	$run->getFont()->setSize( $size );
	if ( ! empty( $font['bold'] ) ) {
		$run->getFont()->setBold( true );
	}
	if ( ! empty( $font['name'] ) ) {
		$run->getFont()->setName( $font['name'] );
	}
}

/** Append a bulleted paragraph. */
function pptx_bullet( $shape, string $text, int $size ): void {
	$paragraph = $shape->createParagraph();
	$paragraph->getBulletStyle()->setBulletType( \PhpOffice\PhpPresentation\Style\Bullet::TYPE_BULLET );
	pptx_run( $paragraph, $text, $size );
}

/** Place a PNG on a slide. */
function pptx_image( $slide, string $png, int $x, int $y, int $w, int $h ): void {
	$drawing = new \PhpOffice\PhpPresentation\Shape\Drawing\Gd();
	$drawing->setImageResource( imagecreatefromstring( $png ) )
		->setRenderingFunction( \PhpOffice\PhpPresentation\Shape\Drawing\Gd::RENDERING_PNG )
		->setMimeType( \PhpOffice\PhpPresentation\Shape\Drawing\Gd::MIMETYPE_PNG )
		->setOffsetX( $x )->setOffsetY( $y )->setWidth( $w )->setHeight( $h );
	$slide->addShape( $drawing );
}

/**
 * Set show="0" on a slide inside the saved package.
 *
 * PhpPresentation has no API for hidden slides, and the parser reads the raw
 * OOXML attribute, so the fixture has to carry it.
 *
 * @param string $path   PPTX path.
 * @param int    $number 1-based slide number.
 */
function mark_slide_hidden( string $path, int $number ): void {
	$entry = "ppt/slides/slide{$number}.xml";
	$zip   = new ZipArchive();
	$zip->open( $path );
	$xml = $zip->getFromName( $entry );
	$xml = preg_replace( '/<p:sld\b(?![^>]*\bshow=)/', '<p:sld show="0"', $xml, 1 );
	$zip->addFromString( $entry, $xml );
	$zip->close();
}


/**
 * A Word document written as raw OOXML.
 *
 * Carries a title, two Heading 2 parts, a Heading 3, a bulleted list and a
 * numbered list backed by real numbering definitions, a table with a bold
 * header row, an inline image, and text with XML entities.
 *
 * @param string $path Destination.
 */
function build_docx( string $path ): void {
	$ns = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
		. ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
		. ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
		. ' xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"'
		. ' xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"';

	$body = w_heading( 'Fixture Guide', 1 )
		. w_heading( 'Part One', 2 )
		. w_para( 'Intro mentioning R&amp;D and &quot;quotes&quot;.' )
		. w_heading( 'Nested Detail', 3 )
		. w_para( 'Detail body.' )
		. w_list_item( 'First bullet', 1 )
		. w_list_item( 'Second bullet', 1 )
		. w_list_item( 'Step one', 2 )
		. w_list_item( 'Step two', 2 )
		. w_table()
		. w_heading( 'Part Two', 2 )
		. w_para( 'Closing paragraph.' )
		. w_image_para();

	$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. "<w:document {$ns}><w:body>{$body}</w:body></w:document>";

	// numId 1 → bullets, numId 2 → decimal. The parser resolves list type from
	// here because PhpWord's reader does not.
	$numbering = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. "<w:numbering {$ns}>"
		. w_abstract_num( 10, 'bullet' )
		. w_abstract_num( 20, 'decimal' )
		. '<w:num w:numId="1"><w:abstractNumId w:val="10"/></w:num>'
		. '<w:num w:numId="2"><w:abstractNumId w:val="20"/></w:num>'
		. '</w:numbering>';

	$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. "<w:styles {$ns}>"
		. w_heading_style( 1 ) . w_heading_style( 2 ) . w_heading_style( 3 )
		. '</w:styles>';

	$zip = new ZipArchive();
	@unlink( $path );
	$zip->open( $path, ZipArchive::CREATE );
	$zip->addFromString( '[Content_Types].xml', docx_content_types() );
	$zip->addFromString( '_rels/.rels', docx_root_rels() );
	$zip->addFromString( 'word/document.xml', $document );
	$zip->addFromString( 'word/_rels/document.xml.rels', docx_document_rels() );
	$zip->addFromString( 'word/numbering.xml', $numbering );
	$zip->addFromString( 'word/styles.xml', $styles );
	$zip->addFromString( 'word/media/image1.png', png_bytes( 48, 32 ) );
	$zip->close();
}

/** A heading paragraph at the given depth. */
function w_heading( string $text, int $depth ): string {
	return '<w:p><w:pPr><w:pStyle w:val="Heading' . $depth . '"/></w:pPr>'
		. '<w:r><w:t xml:space="preserve">' . $text . '</w:t></w:r></w:p>';
}

/** A body paragraph. */
function w_para( string $text ): string {
	return '<w:p><w:r><w:t xml:space="preserve">' . $text . '</w:t></w:r></w:p>';
}

/** A list item bound to a numbering definition. */
function w_list_item( string $text, int $num_id ): string {
	return '<w:p><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="' . $num_id . '"/></w:numPr></w:pPr>'
		. '<w:r><w:t xml:space="preserve">' . $text . '</w:t></w:r></w:p>';
}

/** A two-column table with a bold header row. */
function w_table(): string {
	$cell = static function ( string $text, bool $bold ): string {
		$props = $bold ? '<w:rPr><w:b/></w:rPr>' : '';
		return '<w:tc><w:p><w:r>' . $props . '<w:t xml:space="preserve">' . $text . '</w:t></w:r></w:p></w:tc>';
	};

	return '<w:tbl>'
		. '<w:tr>' . $cell( 'Column A', true ) . $cell( 'Column B', true ) . '</w:tr>'
		. '<w:tr>' . $cell( 'a1', false ) . $cell( 'b1', false ) . '</w:tr>'
		. '</w:tbl>';
}

/** A paragraph holding an inline image. */
function w_image_para(): string {
	return '<w:p><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
		. '<wp:extent cx="457200" cy="304800"/><wp:docPr id="1" name="Picture 1"/>'
		. '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
		. '<pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="image1.png"/><pic:cNvPicPr/></pic:nvPicPr>'
		. '<pic:blipFill><a:blip r:embed="rId5"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
		. '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="457200" cy="304800"/></a:xfrm>'
		. '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic>'
		. '</a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
}

/** An abstract numbering definition using one format at every level. */
function w_abstract_num( int $id, string $format ): string {
	$levels = '';
	for ( $i = 0; $i < 3; $i++ ) {
		$levels .= '<w:lvl w:ilvl="' . $i . '"><w:numFmt w:val="' . $format . '"/>'
			. '<w:lvlText w:val="%' . ( $i + 1 ) . '."/></w:lvl>';
	}
	return '<w:abstractNum w:abstractNumId="' . $id . '">' . $levels . '</w:abstractNum>';
}

/** A heading style definition, so PhpWord reports the right depth. */
function w_heading_style( int $depth ): string {
	return '<w:style w:type="paragraph" w:styleId="Heading' . $depth . '">'
		. '<w:name w:val="heading ' . $depth . '"/>'
		. '<w:pPr><w:outlineLvl w:val="' . ( $depth - 1 ) . '"/></w:pPr>'
		. '</w:style>';
}

/** [Content_Types].xml for the DOCX fixture. */
function docx_content_types(): string {
	return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
		. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
		. '<Default Extension="xml" ContentType="application/xml"/>'
		. '<Default Extension="png" ContentType="image/png"/>'
		. '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
		. '<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>'
		. '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
		. '</Types>';
}

/** Package relationships. */
function docx_root_rels(): string {
	return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
		. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
		. '</Relationships>';
}

/** Document part relationships. */
function docx_document_rels(): string {
	$base = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/';
	return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
		. '<Relationship Id="rId2" Type="' . $base . 'numbering" Target="numbering.xml"/>'
		. '<Relationship Id="rId3" Type="' . $base . 'styles" Target="styles.xml"/>'
		. '<Relationship Id="rId5" Type="' . $base . 'image" Target="media/image1.png"/>'
		. '</Relationships>';
}

/** A small solid-colour PNG. */
function png_bytes( int $w, int $h ): string {
	$img = imagecreatetruecolor( $w, $h );
	imagefill( $img, 0, 0, imagecolorallocate( $img, 32, 96, 160 ) );
	ob_start();
	imagepng( $img );
	return (string) ob_get_clean();
}

/** A small solid-colour JPEG. */
function jpeg_bytes( int $w, int $h ): string {
	$img = imagecreatetruecolor( $w, $h );
	imagefill( $img, 0, 0, imagecolorallocate( $img, 200, 60, 60 ) );
	ob_start();
	imagejpeg( $img, null, 80 );
	return (string) ob_get_clean();
}


/**
 * A two-page 720x405pt PDF, written byte by byte.
 *
 * Page 1 is a cover. Page 2 carries a large title, wrapped body lines, a
 * bulleted line, a monospaced line, a copyright line inside the footer band,
 * a content image and a second image inside the footer band. Between them
 * those cover the title heuristic, line and block grouping, wrapped-line
 * rejoining, bullet splitting, footer exclusion for both text and images, and
 * code detection on a page whose body font is proportional.
 *
 * Text is drawn one word per Tj with explicit positioning, which is what makes
 * the extractor reconstruct spacing rather than read it from the stream.
 *
 * @param string $path Destination.
 */
function build_pdf( string $path ): void {
	$jpeg = jpeg_bytes( 160, 120 );

	$cover = pdf_text_block( 24, 'F1', array( array( 60, 200, 'Fixture Slides' ) ) );

	// y is measured from the bottom; the page is 405pt tall, so the footer band
	// (below 13% of the height) starts under y = 53.
	$body = pdf_text_block( 26, 'F1', array( array( 60, 340, 'Reconstructed Page' ) ) )
		. pdf_text_block(
			12,
			'F2',
			array(
				array( 60, 280, 'This sentence is long enough that it wraps onto the' ),
				array( 60, 262, 'following line and must be rejoined.' ),
				array( 60, 230, "\x95 A bullet item" ),
				array( 60, 200, 'A separate paragraph.' ),
			)
		)
		. pdf_text_block( 12, 'F3', array( array( 60, 170, 'composer install' ) ) )
		. pdf_text_block( 6, 'F2', array( array( 60, 20, 'Copyright 2026 Coding Black Females.' ) ) )
		. pdf_image( 'Im1', 320, 200, 200, 150 )
		. pdf_image( 'Im1', 640, 8, 40, 30 );

	$objects = array(
		1 => '<< /Type /Catalog /Pages 2 0 R >>',
		2 => '<< /Type /Pages /Kids [3 0 R 4 0 R] /Count 2 /MediaBox [0 0 720 405] >>',
		3 => '<< /Type /Page /Parent 2 0 R /Resources 5 0 R /Contents 6 0 R >>',
		4 => '<< /Type /Page /Parent 2 0 R /Resources 5 0 R /Contents 7 0 R >>',
		5 => '<< /Font << /F1 8 0 R /F2 9 0 R /F3 10 0 R >> /XObject << /Im1 11 0 R >> >>',
		6 => pdf_stream( $cover ),
		7 => pdf_stream( $body ),
		8 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
		9 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
		10 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
		11 => '<< /Type /XObject /Subtype /Image /Width 160 /Height 120 /ColorSpace /DeviceRGB'
			. ' /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen( $jpeg ) . " >>\nstream\n" . $jpeg . "\nendstream",
	);

	file_put_contents( $path, pdf_assemble( $objects ) );
}

/**
 * Wrap content-stream text in a BT/ET block at a given size and font.
 *
 * Each glyph is positioned individually and word gaps are expressed as
 * whitespace in the positioning rather than drawn space characters, which is
 * how several real exporters behave. That is what makes the extractor's space
 * reconstruction do actual work here instead of reading spaces off the stream.
 *
 * @param int    $size  Font size in points.
 * @param string $font  Resource name of the font.
 * @param array  $lines Tuples of [x, y, text].
 */
function pdf_text_block( int $size, string $font, array $lines ): string {
	$glyph = $size * PDF_GLYPH_EM;
	$out   = "BT\n/{$font} {$size} Tf\n";

	foreach ( $lines as list( $x, $y, $text ) ) {
		$cursor = $x;
		foreach ( mb_str_split( $text ) as $char ) {
			if ( $char === ' ' ) {
				// A gap wide enough to read as a word break rather than a wide glyph.
				$cursor += $glyph * PDF_WORD_GAP_EM;
				continue;
			}
			$out    .= sprintf( "1 0 0 1 %.2f %.2f Tm (%s) Tj\n", $cursor, $y, pdf_escape( $char ) );
			$cursor += $glyph;
		}
	}

	return $out . "ET\n";
}

/** Draw an image XObject at a position and size. */
function pdf_image( string $name, float $x, float $y, float $w, float $h ): string {
	return sprintf( "q %.2f 0 0 %.2f %.2f %.2f cm /%s Do Q\n", $w, $h, $x, $y, $name );
}

/** Escape a PDF literal string. */
function pdf_escape( string $text ): string {
	return str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
}

/** Wrap a content stream in its dictionary. */
function pdf_stream( string $content ): string {
	return '<< /Length ' . strlen( $content ) . " >>\nstream\n" . $content . 'endstream';
}

/**
 * Serialise numbered objects into a PDF with a correct xref table.
 *
 * PdfParser refuses a file without a resolvable startxref, so the byte offsets
 * have to be real.
 *
 * @param array<int, string> $objects Object bodies keyed by number.
 */
function pdf_assemble( array $objects ): string {
	ksort( $objects );

	$pdf     = "%PDF-1.7\n";
	$offsets = array();

	foreach ( $objects as $number => $body ) {
		$offsets[ $number ] = strlen( $pdf );
		$pdf               .= "{$number} 0 obj\n{$body}\nendobj\n";
	}

	$start = strlen( $pdf );
	$count = count( $objects ) + 1;

	$pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
	foreach ( $objects as $number => $body ) {
		$pdf .= sprintf( "%010d 00000 n \n", $offsets[ $number ] );
	}

	$pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$start}\n%%EOF\n";

	return $pdf;
}
