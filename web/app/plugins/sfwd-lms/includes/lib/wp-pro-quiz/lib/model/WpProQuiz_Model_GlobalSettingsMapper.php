<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Model_GlobalSettingsMapper extends WpProQuiz_Model_Mapper {

	public function fetchAll() {
		$s = new WpProQuiz_Model_GlobalSettings();

		// Why are we doing this? When the option does not exists it causes WP to execute the SQL to the wp_options table for
		// each access attempt. By settings a default value the option will be auto loaded when WP initialized. Then when
		// we call get_option we are just accessing the WP global settings array instead of causing a SQL each time.
		// Saves a few time slices.
		$wpProQuiz_addRawShortcode = get_option( 'wpProQuiz_addRawShortcode' );
		if ( false === $wpProQuiz_addRawShortcode ) {
			update_option( 'wpProQuiz_addRawShortcode', '' );
		}

		$wpProQuiz_jsLoadInHead = get_option( 'wpProQuiz_jsLoadInHead' );
		if ( false === $wpProQuiz_jsLoadInHead ) {
			update_option( 'wpProQuiz_jsLoadInHead', '' );
		}

		$wpProQuiz_touchLibraryDeactivate = get_option( 'wpProQuiz_touchLibraryDeactivate' );
		if ( false === $wpProQuiz_touchLibraryDeactivate ) {
			update_option( 'wpProQuiz_touchLibraryDeactivate', '' );
		}

		$wpProQuiz_corsActivated = get_option( 'wpProQuiz_corsActivated' );
		if ( false === $wpProQuiz_corsActivated ) {
			update_option( 'wpProQuiz_corsActivated', '' );
		}

		$s->setAddRawShortcode( $wpProQuiz_addRawShortcode )
			->setJsLoadInHead( $wpProQuiz_jsLoadInHead )
			->setTouchLibraryDeactivate( $wpProQuiz_touchLibraryDeactivate )
			->setCorsActivated( $wpProQuiz_corsActivated );

		return $s;
	}

	public function save( WpProQuiz_Model_GlobalSettings $settings ) {

		if ( add_option( 'wpProQuiz_addRawShortcode', $settings->isAddRawShortcode() ) === false ) {
			update_option( 'wpProQuiz_addRawShortcode', $settings->isAddRawShortcode() );
		}

		if ( add_option( 'wpProQuiz_jsLoadInHead', $settings->isJsLoadInHead() ) === false ) {
			update_option( 'wpProQuiz_jsLoadInHead', $settings->isJsLoadInHead() );
		}

		if ( add_option( 'wpProQuiz_touchLibraryDeactivate', $settings->isTouchLibraryDeactivate() ) === false ) {
			update_option( 'wpProQuiz_touchLibraryDeactivate', $settings->isTouchLibraryDeactivate() );
		}

		if ( add_option( 'wpProQuiz_corsActivated', $settings->isCorsActivated() ) === false ) {
			update_option( 'wpProQuiz_corsActivated', $settings->isCorsActivated() );
		}
	}

	public function delete() {
		delete_option( 'wpProQuiz_addRawShortcode' );
		delete_option( 'wpProQuiz_jsLoadInHead' );
		delete_option( 'wpProQuiz_touchLibraryDeactivate' );
		delete_option( 'wpProQuiz_corsActivated' );
	}

	public function getEmailSettings() {
		$e = get_option( 'wpProQuiz_emailSettings', null );

		if ( null === $e ) {
			$e['to']   = '';
			$e['from'] = '';
			// translators: placeholder: Quiz, quiz.
			$e['subject'] = sprintf( esc_html_x( 'LearnDash %1$s: One user completed a %2$s', 'placeholder: Quiz, quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ), learndash_get_custom_label_lower( 'quiz' ) );
			$e['html']    = false;
			$e['message'] = sprintf(
				// translators: placeholder: Quiz, quiz.
				esc_html_x(
					'LearnDash %1$s

The user "$username" has completed "$quizname" the %2$s.

Points: $points
Result: $result

',
					'placeholders: Quiz, quiz',
					'learndash'
				),
				LearnDash_Custom_Label::get_label( 'quiz' ),
				learndash_get_custom_label_lower( 'quiz' )
			);

		}

		return $e;
	}

	public function saveEmailSettiongs( $data ) {
		if ( isset( $data['html'] ) && $data['html'] ) {
			$data['html'] = true;
		} else {
			$data['html'] = false;
		}

		if ( add_option( 'wpProQuiz_emailSettings', $data, '', 'no' ) === false ) {
			update_option( 'wpProQuiz_emailSettings', $data );
		}
	}

	public function getUserEmailSettings() {
		$e = get_option( 'wpProQuiz_userEmailSettings', null );

		if ( null === $e ) {
			$e['from'] = '';
			// translators: placeholder: Quiz, quiz.
			$e['subject'] = sprintf( esc_html_x( 'LearnDash %1$s: One user completed a %2$s', 'placeholder: Quiz, quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ), learndash_get_custom_label_lower( 'quiz' ) );
			$e['html']    = false;
			$e['message'] = sprintf(
				// translators: placeholder: Quiz, quiz.
				esc_html_x(
					'LearnDash %1$s

You have completed the %2$s "$quizname".

Points: $points
Result: $result

',
					'placeholders: Quiz, quiz',
					'learndash'
				),
				LearnDash_Custom_Label::get_label( 'quiz' ),
				learndash_get_custom_label_lower( 'quiz' )
			);

		}

		return $e;

	}

	public function saveUserEmailSettiongs( $data ) {
		if ( isset( $data['html'] ) && $data['html'] ) {
			$data['html'] = true;
		} else {
			$data['html'] = false;
		}

		if ( add_option( 'wpProQuiz_userEmailSettings', $data, '', 'no' ) === false ) {
			update_option( 'wpProQuiz_userEmailSettings', $data );
		}
	}
}
