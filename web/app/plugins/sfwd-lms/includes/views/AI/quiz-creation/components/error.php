<?php
/**
 * Quiz creation AI error component caused by empty API key.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Modules\AI\Quiz_Creation
 *
 * @var string $ld_error LearnDash error message.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="notice notice-error">
	<p>
		<?php echo wp_kses_post( $ld_error ); ?>
	</p>
</div>
