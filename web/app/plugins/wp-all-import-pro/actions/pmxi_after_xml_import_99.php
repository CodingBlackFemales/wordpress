<?php

function pmxi_pmxi_after_xml_import_99( $import_id, $import ) {

	// Handle delayed image resizing.
	\Wpai\WordPress\AttachmentHandler::after_xml_import( $import_id, $import );

}