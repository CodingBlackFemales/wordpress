<?php
/**
 * Enroll group trigger.
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash
 */

namespace LearnDash_Notification\Trigger;

use LearnDash_Notification\Notification;
use LearnDash_Notification\Trigger;

/**
 * Class Enroll_Group
 */
class Enroll_Group extends Trigger {
	/**
	 * The notification slug.
	 *
	 * @var string
	 */
	protected $trigger = 'enroll_group';

	/**
	 * Monitor group access
	 *
	 * @param int $user_id The user ID.
	 * @param int $group_id The group ID.
	 */
	public function monitor( $user_id, $group_id ) {
		$models = $this->get_notifications( $this->trigger );
		if ( empty( $models ) ) {
			return;
		}
		$this->log( '==========Job start========' );
		$this->log( sprintf( 'Processing %d notifications', count( $models ) ) );
		foreach ( $models as $model ) {
			$this->log( sprintf( '- Process notification %s', $model->post->post_title ) );
			if ( $model->is_sent( $user_id, $this->trigger, $model->post->ID, $group_id ) ) {
				$this->log( sprintf( 'An email already sent to the user #%d.', $user_id ) );
				continue;
			}

			if ( ! $this->is_valid(
				$model,
				[
					'user_id'  => $user_id,
					'group_id' => $group_id,
				]
			) ) {
				continue;
			}

			$emails = $model->gather_emails( $user_id, null, $group_id );
			$args   = [
				'user_id'  => $user_id,
				'group_id' => $group_id,
			];

			if ( absint( $model->delay ) ) {
				$this->queue_use_db( $emails, $model, $args );
			} else {
				$this->send( $emails, $model, $args );
				$model->mark_sent( $user_id, $this->trigger, $model->post->ID, $group_id );
				$this->log( 'Done, moving next if any' );
			}
		}
		$this->log( '==========Job end========' );
	}

	/**
	 * If this user been removed from the group, then clear the sent
	 *
	 * @param int $user_id The user ID.
	 * @param int $group_id The group ID.
	 */
	public function mark_unsent( $user_id, $group_id ) {
		$models = $this->get_notifications( $this->trigger );
		foreach ( $models as $model ) {
			/**
			 * If the notification set to all or specific.
			 */
			if (
				in_array( 'all', $model->group_id, true )
				|| in_array( $group_id, $model->group_id )
			) {
				$model->mark_unsent( $user_id, $this->trigger, $model->post->ID, $group_id );
				$this->log( sprintf( 'Clear sent status for user #%d in group #%d', $user_id, $group_id ) );
			}
		}
	}

	/**
	 * A base point for monitoring the events
	 *
	 * @return void
	 */
	public function listen() {
		add_action( 'ld_added_group_access', [ &$this, 'monitor' ], 10, 2 );
		add_action( 'leanrdash_notifications_send_delayed_email', [ &$this, 'send_db_delayed_email' ] );
		add_action( 'ld_removed_group_access', [ &$this, 'mark_unsent' ], 10, 2 );
	}

	/**
	 * Process after the email sent.
	 *
	 * @param Notification $model The Notification model.
	 * @param array        $args Mix args.
	 */
	protected function after_email_sent( Notification $model, array $args ) {
		$user_id  = $args['user_id'];
		$group_id = $args['group_id'];
		$model->mark_sent( $user_id, $this->trigger, $model->post->ID, $group_id );
	}

	/**
	 * Check if we can send the delayed email.
	 *
	 * @param Notification $model The Notification model.
	 * @param array        $args Mix args.
	 *
	 * @return bool
	 */
	protected function can_send_delayed_email( Notification $model, $args ) {
		$user_id  = $args['user_id'];
		$group_id = $args['group_id'];
		$ids      = learndash_get_groups_user_ids( $group_id );
		if ( ! in_array( $user_id, $ids, true ) ) {
			// user was not in the group any more.
			return false;
		}

		if ( ! $this->is_valid(
			$model,
			[
				'user_id'  => $user_id,
				'group_id' => $group_id,
			]
		) ) {
			return false;
		}

		return true;
	}
}
