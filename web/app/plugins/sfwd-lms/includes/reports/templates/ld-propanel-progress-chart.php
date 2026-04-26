<?php
/**
 * Learndash ProPanel Progress Chart.
 *
 * @since 4.17.0
 * @version 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="clearfix propanel-admin-row">

	<div class="col-1-2 ld-propanel-progress-chart-progress-distribution">
		<div class="title"><span><?php esc_html_e( 'Progress Distribution', 'learndash' ); ?></span></div>
		<div class="canvas-wrap">
			<div class="proPanelDefaultMessage" id="proPanelProgressAllDefaultMessage"><strong><?php esc_html_e( 'No All-Progress items found', 'learndash' ); ?></strong></div>
			<canvas id="proPanelProgressAll" width="400" height="400"></canvas>
		</div>
	</div>

	<div class="col-1-2 ld-propanel-progress-chart-progress-breakdown">
		<div class="title"><span><?php esc_html_e( 'In Progress Breakdown', 'learndash' ); ?></span></div>
		<div class="canvas-wrap">
			<div class="proPanelDefaultMessage" id="proPanelProgressInMotionDefaultMessage"><strong><?php esc_html_e( 'No In-Progress items found', 'learndash' ); ?></strong></div>
			<canvas id="proPanelProgressInMotion" width="400" height="400"></canvas>
		</div>
	</div>

</div>
