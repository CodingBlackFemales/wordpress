<?php
// Solution from https://stackoverflow.com/a/55566407/494224
// Paste corrupt string below
$data = <<<STRING
STRING;
$repaired = preg_replace_callback(
	'/s:\d+:"(.*?)";/s',
	//  ^^^- matched/consumed but not captured because not used in replacement
	function ( $m ) {
		return 's:' . strlen( $m[1] ) . ":\"{$m[1]}\";";
	},
	$data
);
// var_dump( unserialize( $data ) );
// var_dump( unserialize($repaired) );
echo $repaired, "\n";
