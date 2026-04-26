<?php
/**
 * Template for showing a Star Rating for a Review or group of Reviews.
 *
 * @since 4.25.1
 * @version 1.0.0
 *
 * @var float $rating Star Rating.
 *
 * @package LearnDash\Course_Reviews
 *
 * cSpell:ignore allowedentitynames
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

ob_start();

$uuid = wp_generate_uuid4();

// Clean up the string representation of our Stars, used for accessibility.
$rating_string = rtrim(
	str_replace(
		'.00',
		'',
		number_format(
			$rating,
			2
		)
	),
	'0'
);

?>

<style type="text/css">
	#review-stars-<?php echo esc_attr( $uuid ); ?>::after {
		max-width: <?php echo esc_attr( strval( ( $rating / 5 ) * 100 ) ); ?>%;
	}
</style>

<div
	class="learndash-course-reviews-review-stars"
	id="review-stars-<?php echo esc_attr( $uuid ); ?>"
	aria-label="
	<?php
	echo esc_attr(
		sprintf(
			// translators: Number out of 5 stars.
			__( '%d out of 5 stars', 'learndash' ),
			intval( $rating_string )
		)
	);
	?>
	"
>
	<?php for ( $index = 0; $index < 5; $index++ ) : ?>
		&starf;
	<?php endfor; ?>
</div>

<?php

$content = strval( ob_get_clean() );

// Remove any line breaks and spaces between stars from the output as it will otherwise break the overlaid styling.
$content = preg_replace( '/\n/', '', $content );
$content = preg_replace( '/\s*&starf;\s*/', '&starf;', strval( $content ) );


$allowed_html = wp_kses_allowed_html( 'post' );

global $allowedentitynames;
$allowedentitynames[] = 'starf'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Unfortunately necessary to allow &starf; to be used. See wp_kses(), wp_kses_normalize_entities(), and wp_kses_named_entities().

echo wp_kses(
	strval( $content ),
	array_merge(
		$allowed_html,
		array(
			'style' => array(
				'type' => true,
			),
		)
	)
);
