<?php
/**
 * Reply post success.
 *
 * @package BuddyBoss
 * @subpackage Theme
 */

?>
<div class="bbp-reply-form-success-modal">
	<div class="bbp-reply-form-success bb-modal bb-modal-box">
		<div class="reply-content">
			<div class="content-title">
				<div class="process-title">
					<span class="reply-to"><?php esc_html_e( 'Reply to', 'buddyboss-theme' ); ?> </span>
					<div class="discussion-title">
						<div class="activity-list">
							<div class="bp-generic-meta activity-meta action activity-discussion-title-wrap">
								<div class="generic-button">
									<a class="button bp-secondary-action" aria-expanded="false" href="#">
										<?php esc_html_e( 'Discussion Title', 'buddyboss-theme' ); ?>
									</a>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="close">
					<a href="#" class="js-modal-close"><i class="bb-icon-l bb-icon-times"></i></a>
				</div>
			</div>
			<div class="activity-inner">
				<p><?php esc_html_e( 'Your reply has been posted to the discussion', 'buddyboss-theme' ); ?></p>
			</div>
			<div class="view-reply-button">
				<div class="generic-button"><a class="button bp-secondary-action" aria-expanded="false" href="#"><?php esc_html_e( 'View Reply', 'buddyboss-theme' ); ?></a></div>
			</div>
		</div>
	</div>
</div>
