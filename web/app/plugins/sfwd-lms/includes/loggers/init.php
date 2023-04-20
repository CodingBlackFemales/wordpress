<?php
/**
 * LearnDash logger.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LEARNDASH_LOGGERS_PATH = LEARNDASH_LMS_PLUGIN_DIR . 'includes/loggers/';
require_once LEARNDASH_LOGGERS_PATH . 'class-learndash-logger.php';

// Requires all loggers. Please don't forget to create an instance of the loggers below, if needed.
require_once LEARNDASH_LOGGERS_PATH . 'class-learndash-transaction-logger.php';
require_once LEARNDASH_LOGGERS_PATH . 'class-learndash-import-export-logger.php';

Learndash_Logger::init_log_directory();

add_action(
	'init',
	function () {
		/**
		 * Filters the list of loggers.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Logger[] $loggers List of logger instances.
		 *
		 * @return Learndash_Logger[] List of logger instances.
		 */
		foreach ( apply_filters( 'learndash_loggers', array() ) as $logger ) {
			if ( ! $logger instanceof Learndash_Logger ) {
				continue;
			}

			$logger->init();
		}
	}
);
