<?php
/**
 * Quiz creation AI notices component.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Modules\AI\Quiz_Creation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Modules\AI\Quiz_Creation;

$ld_wrapper_class = 'ld-mb-4 ld-p-2 ld-text-sm ld-font-semibold ld-rounded ld-border ld-border-solid';
$ld_messages      = get_transient( Quiz_Creation::$transient_key_messages );
?>

<?php if ( isset( $ld_messages ) && is_array( $ld_messages ) ) : ?>
	<?php foreach ( $ld_messages as $ld_message ) : ?>
		<?php
		$ld_color = isset( $ld_message['is_success'] )
		&& $ld_message['is_success']
			? 'green'
			: 'red';

		$ld_prefix = isset( $ld_message['is_success'] )
			&& $ld_message['is_success']
				? __( 'Success:', 'learndash' )
				: __( 'Error:', 'learndash' );

		$ld_wrapper_class .= " ld-text-{$ld_color}-800 ld-bg-{$ld_color}-50 ld-border-{$ld_color}-500";
		?>
		<div class="<?php echo esc_attr( $ld_wrapper_class ); ?>">
			<span>
				<?php echo wp_kses_post( $ld_prefix . ' ' . wp_unslash( $ld_message['message'] ) ); ?>
			</span>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
