<?php
/**
 * Admin Subscription Page template.
 *
 * @since 5.2.0
 * @version 5.2.0
 *
 * @package LearnDash\Notifications
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action(
	'wp_footer',
	function () {
		?>
	<script type="text/javascript">
		jQuery( document ).ready( function( $ ) {
			$( '.select-all' ).on( 'click', function() {
				if ( $( this ) .is( ':checked' ) ) {
					$( 'input[type="checkbox"]' ).prop( 'checked', true );
				} else {
					$( 'input[type="checkbox"]' ).prop( 'checked', false );
				}
			} );
		});
	</script>
		<?php
	}
);

get_header();

$learndash_notifications_triggers      = learndash_notifications_get_triggers();
$learndash_notifications_subscriptions = get_user_meta( get_current_user_id(), 'learndash_notifications_subscription', true );
$learndash_notifications_subscriptions = is_array( $learndash_notifications_subscriptions ) ? $learndash_notifications_subscriptions : [];
?>

<style scoped="scoped">
	.primary {
		margin-bottom: 30px;
		max-width: 600px;
		margin: 0 auto;
	}

	h1 {
		text-align: center;
		margin-bottom: 40px;
	}

	.message {
		color: #fff;
		padding: 5px 15px;
		margin-bottom: 20px;
	}

	.message.success {
		background-color: green;
	}

	.message.fail {
		background-color: red;
	}

	.triggers {
		display: flex;
		flex-direction: column;
	}

	.item {
		display: flex;
		flex-direction: row;
	}

	.item.alternate {
		background-color: #f5f5f5;
	}

	.item.submit {
		margin-top: 20px;
	}

	.header {
		font-weight: bold;
	}

	.child {
		width: 80%;
		padding: 5px 10px;
	}

	.child.cb {
		width: 200px;
		text-align: center;
	}

	@media screen {

	}
</style>

<main class="learndash-notifications primary">
	<h1><?php esc_html_e( 'LearnDash Notifications Subscription', 'learndash' ); ?></h1>
	<?php
	if ( isset( $_GET['message'] ) ) {
		$learndash_notifications_class = '';

		switch ( $_GET['message'] ) {
			case 'success':
				$learndash_notifications_class   = 'success';
				$learndash_notifications_message = __( 'Your notification settings has been successfully saved.', 'learndash' );
				break;

			case 'fail':
				$learndash_notifications_class   = 'fail';
				$learndash_notifications_message = __( 'There is something wrong. Please try again later.', 'learndash' );
				break;

			default:
				$learndash_notifications_message = false;
				break;
		}

		if ( $learndash_notifications_message ) {
			?>
			<div class="message <?php echo esc_attr( $learndash_notifications_class ); ?>"><?php echo esc_html( $learndash_notifications_message ); ?></div>
			<?php
		}
	}
	?>
	<div class="triggers">
		<form action="" method="POST">
			<input type="hidden" name="action" value="learndash_notifications_subscription">
			<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) get_current_user_id() ); ?>">
			<?php wp_nonce_field( 'learndash_notifications_subscription', 'ld_nonce' ); ?>
			<div class="item">
				<div class="header child"><?php esc_html_e( 'Triggers', 'learndash' ); ?></div>
				<div class="header cb child">
					<div>
						<?php esc_html_e( 'Enabled', 'learndash' ); ?>
					</div>
					<div class="input">
						<input type="checkbox" name="select-all" class="select-all" title="<?php esc_attr_e( 'Select All', 'learndash' ); ?>">
					</div>
				</div>
			</div>
			<?php $learndash_notifications_count = 0; ?>
			<?php foreach ( $learndash_notifications_triggers as $learndash_notifications_trigger_key => $learndash_notifications_trigger_label ) : ?>
				<?php ++$learndash_notifications_count; ?>
				<?php $learndash_notifications_checked = ! isset( $learndash_notifications_subscriptions[ $learndash_notifications_trigger_key ] ) || $learndash_notifications_subscriptions[ $learndash_notifications_trigger_key ]; ?>
				<div class="item <?php echo $learndash_notifications_count % 2 ? 'alternate' : ''; ?>">
					<div class="label child"><?php echo esc_textarea( $learndash_notifications_trigger_label ); ?></div>
					<div class="cb child"><input type="checkbox" name="<?php echo esc_attr( $learndash_notifications_trigger_key ); ?>" value="1" <?php checked( $learndash_notifications_checked ); ?>></div>
				</div>
			<?php endforeach; ?>
			<div class="item submit">
				<div class="child"></div>
				<div class="child cb">
					<input class="submit" type="submit" value="<?php esc_attr_e( 'Save Changes', 'learndash' ); ?>">
				</div>
			</div>
		</form>
	</div>
</main>

<?php
get_footer();
