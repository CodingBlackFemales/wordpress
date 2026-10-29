<?php
/**
 * Show the Revision Needs Work column on the PublishPress Content Board.
 *
 * PublishPress Statuses Pro defines the revision-needs-work status with the
 * alternate flag set, and keeps it set wherever the status is positioned in
 * the revision workflow. The Content Board skips alternate statuses when it
 * builds its columns, so revisions in that status never appear on the board.
 *
 * Clearing the flag on copies of the status objects passed to the board shows
 * the column. The originals are cached and shared with the workflow logic, so
 * they are left unchanged.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'publishpress_calendar_post_statuses',
	static function ( $statuses ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! is_admin() || ! isset( $_GET['page'] ) || $_GET['page'] !== 'pp-content-board' || ! is_array( $statuses ) ) {
			return $statuses;
		}

		foreach ( $statuses as $key => $status ) {
			if ( is_object( $status ) && ( $status->slug ?? '' ) === 'revision-needs-work' && ! empty( $status->alternate ) ) {
				$statuses[ $key ]            = clone $status;
				$statuses[ $key ]->alternate = false;
			}
		}

		return $statuses;
	},
	20
);
