<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Controller_ImportExport extends WpProQuiz_Controller_Controller {

	/**
	 * View instance
	 * @var object $view.
	 */
	protected $view;

	public function route() {

		@set_time_limit( 0 );
		@ini_set( 'memory_limit', '128M' );

		if ( ! isset( $_GET['action'] ) || 'import' != $_GET['action'] && 'export' != $_GET['action'] ) {
			wp_die( 'Error' );
		}

		if ( 'export' == $_GET['action'] ) {
			$this->handleExport();
		} else {
			$this->handleImport();
		}
	}

	private function handleExport() {

		if ( ! current_user_can( 'wpProQuiz_export' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		if ( isset( $this->_post ['exportType'] ) && 'xml' == $this->_post ['exportType'] ) {
			$export   = new WpProQuiz_Helper_ExportXml();
			$filename = 'WpProQuiz_export_' . time() . '.xml';
		} else {
			$export   = new WpProQuiz_Helper_Export();
			$filename = 'WpProQuiz_export_' . time() . '.wpq';
		}

		$a = $export->export( $this->_post['exportIds'] );

		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		echo $a; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	private function handleImport() {

		if ( ! current_user_can( 'wpProQuiz_import' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
		}

		$this->view        = new WpProQuiz_View_Import();
		$this->view->error = false;

		if ( ! defined( 'LEARNDASH_PROQUIZ_IMPORT' ) ) {
			/**
			 * @ignore
			 */
			define( 'LEARNDASH_PROQUIZ_IMPORT', true );
		}
		if ( isset( $_FILES, $_FILES['import'] ) && substr( $_FILES['import']['name'], -3 ) == 'xml' || isset( $this->_post['importType'] ) && 'xml' == $this->_post['importType'] ) {
			$import     = new WpProQuiz_Helper_ImportXml();
			$importType = 'xml';
		} else {
			$import     = new WpProQuiz_Helper_Import();
			$importType = 'wpq';
		}

		$this->view->importType = $importType;

		if ( isset( $_FILES, $_FILES['import'] ) && 0 == $_FILES['import']['error'] ) {
			if ( $import->setImportFileUpload( $_FILES['import'] ) === false ) {
				$this->view->error = $import->getError();
			} else {
				$data = $import->getImportData();

				if ( false === $data ) {
					$this->view->error = $import->getError();
				}

				$this->view->import     = $data;
				$this->view->importData = $import->getContent();

				unset( $data );
			}
		} elseif ( isset( $this->_post, $this->_post['importSave'] ) ) {
			if ( $import->setImportString( $this->_post['importData'] ) === false ) {
				$this->view->error = $import->getError();
			} else {
				$ids = isset( $this->_post['importItems'] ) ? $this->_post['importItems'] : false;

				if ( false !== $ids && false === $import->saveImport( $ids ) ) {
					$this->view->error = $import->getError();
				} else {
					$this->view->finish         = true;
					$this->view->import_post_id = absint( $import->import_post_id );
				}
			}
		} else {
			$this->view->error = esc_html__( 'File cannot be processed', 'learndash' );
		}

		$this->view->show();
	}
}
