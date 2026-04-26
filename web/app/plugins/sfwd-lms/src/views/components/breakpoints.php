<?php
/**
 * View: Breakpoints
 *
 * @since 4.16.0
 * @version 4.16.0
 *
 * @var bool     $is_initial_load Boolean on whether view is being loaded for the first time.
 * @var Template $this            The Template object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( ! $is_initial_load ) {
	return;
}
?>
<script class="learndash-breakpoints">
	( function () {
		let completed = false;

		/**
		 * Initializes the LearnDash Breakpoints.
		 *
		 * @since 4.16.0
		 */
		function initBreakpoints() {
			if ( completed ) {
				// This was fired already and completed no need to attach to the event listener.
				document.removeEventListener( 'DOMContentLoaded', initBreakpoints );
				return;
			}

			if (
				'undefined' === typeof window.learndash ||
				'undefined' === typeof window.learndash.views ||
				'undefined' === typeof window.learndash.views.breakpoints ||
				'function' !== typeof (window.learndash.views.breakpoints.setup)
			) {
				return;
			}

			const container = document.querySelector(
				'[data-learndash-breakpoint-pointer="<?php echo esc_js( $this->get_breakpoint_pointer() ); ?>"]'
			);

			if ( ! container ) {
				return;
			}

			window.learndash.views.breakpoints.setup( container );
			completed = true;
			// This was fired already and completed no need to attach to the event listener.
			document.removeEventListener( 'DOMContentLoaded', initBreakpoints );
		}

		// Try to init the breakpoints right away.
		initBreakpoints();
		document.addEventListener( 'DOMContentLoaded', initBreakpoints );
	})();
</script>
