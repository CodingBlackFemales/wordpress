<?php
/**
 * View: Order Customer Details Table Body.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Template    $this        Current instance of template engine rendering this template.
 * @var Transaction $transaction Transaction object.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Transaction;

$customer  = $transaction->get_user();

?>

<div class="ld-order-details__tbody" role="rowgroup">
	<?php if ( $customer->exists() ) : ?>
		<div role="row">
			<span role="cell">
				<?php
					echo wp_kses(
						sprintf(
							'<a href="%1$s">%2$s</a>',
							current_user_can( 'edit_user', $customer->ID )
								? esc_url( get_edit_user_link( $customer->ID ) )
								: esc_attr( '#' ),
							$customer->user_email
						),
						[
							'a' => [
								'href' => true,
							],
						]
					);
				?>
			</span>
		</div>

		<div role="row">
			<span role="cell">
				<?php echo esc_html( $customer->display_name ); ?>
			</span>
		</div>

		<div role="row">
			<span role="cell">
				<?php
				echo esc_html(
					implode(
						', ',
						array_map(
							function ( $role ) {
								return $role['name'];
							},
							array_filter(
								get_editable_roles(),
								function ( $role, $id ) use ( $customer ) {
									return in_array(
										$id,
										$customer->roles,
										true
									);
								},
								ARRAY_FILTER_USE_BOTH
							)
						)
					)
				);
				?>
			</span>
		</div>

		<?php if ( current_user_can( 'edit_user', $customer->ID ) ) : ?>
			<div role="row">
				<span role="cell">
					<a
						href="<?php echo esc_attr( get_edit_user_link( $customer->ID ) ); ?>"
						target="_blank"
						class="ld-order-details__edit-information-link"
					>
						<?php esc_html_e( 'Edit information', 'learndash' ); ?>
					</a>
				</span>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<div role="row">
			<span role="cell">
				<?php esc_html_e( 'The customer cannot be found.', 'learndash' ); ?>
			</span>
		</div>
	<?php endif; ?>
</div>
