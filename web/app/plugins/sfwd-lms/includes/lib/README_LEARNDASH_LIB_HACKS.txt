tcpdf - 6.6.2

LearnDash 4.5.2
File tcpdf/tcpdf.php
Function: getHtmlDomArray()
Description of changes: Added condition wrapper around line 16516 to prevent replacing multiple spaces with a single space.

Original:
	$html = preg_replace('/'.$this->re_space['p'].'+/'.$this->re_space['m'], chr(32), $html); // replace multiple spaces with a single space

Changed:
	if ( ( ! defined( 'LEARNDASH_TCPDF_LEGACY_LD322' ) ) || ( true !== LEARNDASH_TCPDF_LEGACY_LD322 ) ) {
		$html = preg_replace('/'.$this->re_space['p'].'+/'.$this->re_space['m'], chr(32), $html); // replace multiple spaces with a single space
	}

File tcpdf/tcpdf.php
Function: getHTMLUnitToUnits()
Description of changes: Added condition wrapper around lines 20468-20472.

Original:
	if ($points) {
		$k = 1;
	} else {
		$k = $this->k;
	}

Changed:
	if ( ( ! defined( 'LEARNDASH_TCPDF_LEGACY_LD322' ) ) || ( true !== LEARNDASH_TCPDF_LEGACY_LD322 ) ) {
		if ($points) {
			$k = 1;
		} else {
			$k = $this->k;
		}
	}

File: tcpdf/tcpdf.php
Function: getHtmlDomArray()
Description of changes: Added condition wrapper around lines 16777.

Original:
	$dom[$key]['line-height'] = (($dom[$key]['line-height'] - $this->cell_padding['T'] - $this->cell_padding['B']) / $dom[$key]['fontsize']);

Changed:
	if ( ( ! defined( 'LEARNDASH_TCPDF_LEGACY_LD322' ) ) || ( true !== LEARNDASH_TCPDF_LEGACY_LD322 ) ) {
		$dom[$key]['line-height'] = (($dom[$key]['line-height'] - $this->cell_padding['T'] - $this->cell_padding['B']) / $dom[$key]['fontsize']);
	}

File: tcpdf/tcpdf.php
Function: getHtmlDomArray()
Description of changes: Changed line 16443 to remove invalid tags '<marker/><br/><hr/>' from call to wp_strip_all_tags() function.

Original:
	$html = wp_strip_all_tags($html, '<marker/><a><b><blockquote><body><br><br/><dd><del><div><dl><dt><em><font><form><h1><h2><h3><h4><h5><h6><hr><hr/><i><img><input><label><li><ol><option><p><pre><s><select><small><span><strike><strong><sub><sup><table><tablehead><tcpdf><td><textarea><th><thead><tr><tt><u><ul>');

Changed:
	$html = wp_strip_all_tags($html, '<marker><a><b><blockquote><body><br><dd><del><div><dl><dt><em><font><form><h1><h2><h3><h4><h5><h6><hr><hr><i><img><input><label><li><ol><option><p><pre><s><select><small><span><strike><strong><sub><sup><table><tablehead><tcpdf><td><textarea><th><thead><tr><tt><u><ul>');

File: includes/lib/tcpdf/tcpdf.php
Description of changes: LEARNDASH-4582 #3 issue. For security reasons disabled support for K_TCPDF_CALLS_IN_HTML. Lines 19509-19523 commented out.

tcpdf - 6.3.2

---
LearnDash 3.2.2 2020-07-07
File: tcpdf/tcpdf.php
Function: inside getHtmlDomArray()
Description of changes: Added condition wrapper around line 16436 to prevent replacing multiple spaces with single space.

Original:
	$html = preg_replace('/'.$this->re_space['p'].'+/'.$this->re_space['m'], chr(32), $html); // replace multiple spaces with a single space

Changed:
	if ( ( ! defined( 'LEARNDASH_TCPDF_LEGACY_LD322' ) ) || ( true !== LEARNDASH_TCPDF_LEGACY_LD322 ) ) {
		$html = preg_replace('/'.$this->re_space['p'].'+/'.$this->re_space['m'], chr(32), $html); // replace multiple spaces with a single space
	}


---

LearnDash 3.2.2 2020-07-07
File: tcpdf/tcpdf.php
Function: inside getHTMLUnitToUnits()
Description of changes: Added condition wrapper around lines 20399-20404.

Original:
	if ($points) {
		error_log( 'this->k[' . $this->k . ']' );
		$retval *= $this->k;
	}

Changed:
	if ( ( ! defined( 'LEARNDASH_TCPDF_LEGACY_LD322' ) ) || ( true !== LEARNDASH_TCPDF_LEGACY_LD322 ) ) {
		if ($points) {
			error_log( 'this->k[' . $this->k . ']' );
			$retval *= $this->k;
		}
	}

---

LearnDash 3.2.2 2020-07-07
File: tcpdf/tcpdf.php
Function: getHtmlDomArray()
Description of changes: Added condition wrapper around lines 16690-16692.

Original:
	$dom[$key]['line-height'] = (($dom[$key]['line-height'] - $this->cell_padding['T'] - $this->cell_padding['B']) / $dom[$key]['fontsize']);

Changed:
	if ( ( ! defined( 'LEARNDASH_TCPDF_LEGACY_LD322' ) ) || ( true !== LEARNDASH_TCPDF_LEGACY_LD322 ) ) {
		$dom[$key]['line-height'] = (($dom[$key]['line-height'] - $this->cell_padding['T'] - $this->cell_padding['B']) / $dom[$key]['fontsize']);
	}

---

File: tcpdf/tcpdf.php
Function: getHtmlDomArray()
Description of changes: Changed line 16362 to remove invalid tags '<marker/><br/><hr/>' from call to wp_strip_all_tags() function.

Original:
	$html = wp_strip_all_tags($html, '<marker/><a><b><blockquote><body><br><br/><dd><del><div><dl><dt><em><font><form><h1><h2><h3><h4><h5><h6><hr><hr/><i><img><input><label><li><ol><option><p><pre><s><select><small><span><strike><strong><sub><sup><table><tablehead><tcpdf><td><textarea><th><thead><tr><tt><u><ul>');

Changed:
	$html = wp_strip_all_tags($html, '<marker><a><b><blockquote><body><br><dd><del><div><dl><dt><em><font><form><h1><h2><h3><h4><h5><h6><hr><hr><i><img><input><label><li><ol><option><p><pre><s><select><small><span><strike><strong><sub><sup><table><tablehead><tcpdf><td><textarea><th><thead><tr><tt><u><ul>');

---

File: tcpdf/include/barcodes/pdf417.php
Description of changes: PHP 7.4 scan found curly brace syntax for accessing array.

Errors:
 881 | WARNING | [x] Curly brace syntax for accessing array elements and string offsets has been deprecated in PHP 7.4. Found: $code{$i}
 891 | WARNING | [x] Curly brace syntax for accessing array elements and string offsets has been deprecated in PHP 7.4. Found: $code{($i + 1)}
 955 | WARNING | [x] Curly brace syntax for accessing array elements and string offsets has been deprecated in PHP 7.4. Found: $code{$i}

---

File: tcpdf/include/barcodes/datamatrix.php
Description of changes: PHP 7.4 scan found curly brace syntax for accessing array.

Errors:
 632 | WARNING | [x] Curly brace syntax for accessing array elements and string offsets has been deprecated in PHP 7.4. Found: $data{$k}

---

File: includes/lib/tcpdf/tcpdf.php
Description of changes: LEARNDASH-4582 #3 issue. For security reasons disabled support for K_TCPDF_CALLS_IN_HTML. Lines 19374-19388 commented out.

File: includes/lib/tcpdf/config/tcpdf_config.php
Description of changes: LEARNDASH-4582 #3 issue. For security reasons disabled support for K_TCPDF_CALLS_IN_HTML. Line 330 changed define value to false.

---

File: includes/lib/tcpdf/include/tcpdf_static.php
Description of changes: added phpcs ignore for lines 143,159,453,454,475,476,493,1835 and 1946

File: includes/lib/tcpdf/tcpdf.php
Description of changes: added phpcs ignore for lines 10929,16361

---
stripe-php - 7.107.0

File: includes/lib/stripe-php/lib/HttpClient/CurlClient.php
Description of changes: added phpcs ignore for line 202
