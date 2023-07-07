<?php
/**
 * View: Progress Donut.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var int $value Progress percentage.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

$stroke_width  = 23; // The width of the stroke around the circle.
$radius        = 50 - ( $stroke_width / 2 ); // The radius of the circle, adjusted for the stroke width.
$circumference = 2 * M_PI * $radius; // The circumference of the circle.
$stroke_dash   = ( $value / 100 ) * $circumference; // The length of the dash in the stroke, based on the percentage value passed in.
?>
<div
	class="ld-progress-donut"
	role="progressbar"
	aria-valuemin="0"
	aria-valuemax="100"
	aria-valuenow="<?php echo esc_attr( (string) $value ); ?>"
>
	<svg viewBox="0 0 100 100" role="img">
		<circle
			class="ld-progress-donut__circle-bg"
			cx="50"
			cy="50"
			r="<?php echo esc_attr( strval( $radius ) ); ?>"
			style="stroke-width: <?php echo esc_attr( (string) $stroke_width ); ?>%"
		/>
		<circle
			class="ld-progress-donut__circle-fg"
			style="stroke-width: <?php echo esc_attr( (string) $stroke_width ); ?>%; stroke-dasharray: <?php echo esc_attr( (string) $stroke_dash ); ?> <?php echo esc_attr( (string) $circumference ); ?>;"
			cx="50"
			cy="50"
			r="<?php echo esc_attr( strval( $radius ) ); ?>"
		/>
	</svg>
</div>
