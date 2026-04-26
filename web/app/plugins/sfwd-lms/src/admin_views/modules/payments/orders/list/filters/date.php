<?php
/**
 * View: List date range filter.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var array<string, mixed> $args Field args.
 *
 * @package LearnDash\Core
 */

?>
<div class="ld-date-range-picker-wrapper">
	<label for="from_date"><?php esc_html_e( 'From', 'learndash' ); ?><input type="text" class="ld-date-picker ld-filter" name="from_date" id="from_date" placeholder="YYYY-MM-DD" size="10"></label>

	<label for="to_date"><?php esc_html_e( 'To', 'learndash' ); ?><input type="text" class="ld-filter" name="to_date" id="to_date" placeholder="YYYY-MM-DD" size="10"></label>
</div>
