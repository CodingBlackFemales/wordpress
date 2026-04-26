<?php
/**
 * LearnDash Orders Admin Edit class.
 *
 * @since 4.19.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Orders\Admin;

use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Models\Invoice;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Cast;
use WP_Post;
use WP_Screen;
use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use LearnDash\Core\Modules\Payments\Orders\Admin\Actions\Send_Invoice;
use LearnDash_Settings_Page;

/**
 * LearnDash Orders Admin Edit class.
 *
 * @since 4.19.0
 *
 * @phpstan-import-type Header_Data from LearnDash_Settings_Page
 */
class Edit {
	/**
	 * The Post Type for the edit screen.
	 *
	 * @since 4.19.0
	 *
	 * @var string Post type slug.
	 */
	private string $post_type;

	/**
	 * Nonce action to use when resending invoices.
	 *
	 * @since 4.19.0
	 *
	 * @var string Nonce action.
	 */
	private static string $resend_invoice_nonce_action = 'learndash_order_resend_invoice';

	/**
	 * Constructor for the Orders Admin Edit class.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TRANSACTION );
	}

	/**
	 * Removes meta boxes from the Order Edit screen.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function remove_meta_boxes(): void {
		if ( ! $this->is_edit_screen() ) {
			return;
		}

		remove_meta_box( 'slugdiv', $this->post_type, 'normal' );
		remove_meta_box( 'postcustom', $this->post_type, 'normal' );
	}

	/**
	 * Renames the "Publish" meta box by recreating it with a different title.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function rename_publish_metabox(): void {
		global $wp_meta_boxes;

		if (
			! $this->is_edit_screen()
			|| empty( $wp_meta_boxes[ $this->post_type ] )
			|| empty( $wp_meta_boxes[ $this->post_type ]['side'] )
			|| empty( $wp_meta_boxes[ $this->post_type ]['side']['core'] )
			|| empty( $wp_meta_boxes[ $this->post_type ]['side']['core']['submitdiv'] )
		) {
			return;
		}

		$meta_box_args = $wp_meta_boxes[ $this->post_type ]['side']['core']['submitdiv'];

		// Set the arguments in the correct order and update the Title.
		$meta_box_args = [
			$meta_box_args['id'],
			__( 'Actions', 'learndash' ),
			$meta_box_args['callback'],
			$this->post_type,
			'side',
			'default', // "Core" cannot be the priority as it is being removed, so we'll use the default.
			$meta_box_args['args'],
		];

		remove_meta_box(
			'submitdiv',
			$this->post_type,
			'side'
		);

		call_user_func_array( 'add_meta_box', $meta_box_args );
	}

	/**
	 * Adds meta boxes to the Order Edit screen.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function add_meta_boxes(): void {
		$transaction = $this->get_transaction();

		if (
			! $this->is_edit_screen()
			|| ! $transaction
		) {
			return;
		}

		add_meta_box(
			'order-details',
			sprintf(
				'%1$s %2$s',
				$transaction->get_post()->post_status === 'draft'
					? __( 'Ordered', 'learndash' )
					: __( 'Completed', 'learndash' ),
				learndash_adjust_date_time_display(
					Cast::to_int(
						strtotime(
							$transaction->get_post()->post_date_gmt
						)
					)
				)
			),
			[ $this, 'details_metabox' ],
			null,
			'normal'
		);

		if (
			$transaction->get_post()->post_status !== 'draft'
			&& ! empty( $transaction->get_children() )
		) {
			add_meta_box(
				'order-items',
				__( 'Items', 'learndash' ),
				[ $this, 'items_metabox' ],
				null,
				'normal'
			);
		}
	}

	/**
	 * Adds additional Order Actions to the "Actions" metabox.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function add_order_actions(): void {
		if ( ! $this->is_edit_screen() ) {
			return;
		}

		Template::show_admin_template(
			'modules/payments/orders/edit/components/actions',
			[
				'invoice'      => $this->get_invoice(),
				'nonce_action' => self::$resend_invoice_nonce_action,
			]
		);
	}

	/**
	 * Outputs the Order Details Metabox.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function details_metabox(): void {
		$transaction = $this->get_transaction();

		if ( ! $transaction ) {
			return;
		}

		if (
			$transaction->is_parent()
			&& ! empty( $transaction->get_children() )
		) {
			$transaction = $transaction->get_first_child(); // TODO: Once multiple Child Transactions are supported, we will need to revisit this.
		}

		Template::show_admin_template(
			'modules/payments/orders/edit/details',
			[
				'transaction' => $transaction,
			]
		);
	}

	/**
	 * Outputs the Order Items Metabox.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function items_metabox(): void {
		$transaction = $this->get_transaction();

		if ( ! $transaction ) {
			return;
		}

		foreach ( $transaction->get_children() as $index => $child_transaction ) {
			Template::show_admin_template(
				'modules/payments/orders/edit/items',
				[
					'index'       => $index,
					'transaction' => $child_transaction,
				]
			);
		}
	}

	/**
	 * Outputs a Test Mode indicator on the Order Edit screen.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function add_test_mode_indicator_after_metaboxes(): void {
		$transaction = $this->get_transaction();

		if (
			! $this->is_edit_screen()
			|| ! $transaction
			|| ! $transaction->is_test_mode()
		) {
			return;
		}

		Template::show_admin_template(
			'modules/payments/orders/components/test-mode-label',
			[
				'label' => sprintf(
					// translators: placeholder: Order label.
					__( 'Test %s', 'learndash' ),
					learndash_get_custom_label( 'order' )
				),
			]
		);
	}

	/**
	 * Updates the LearnDash Header Title to match our expected format.
	 * This will also add a Test Mode indicator if the Order is in Test Mode.
	 *
	 * @since 4.19.0
	 *
	 * @param array $header_data LearnDash Header Data.
	 *
	 * @phpstan-param Header_Data $header_data
	 *
	 * @phpstan-return Header_Data
	 */
	public function update_learndash_header_title( array $header_data ): array {
		$transaction = $this->get_transaction();

		if (
			! $this->is_edit_screen()
			|| ! $transaction
		) {
			return $header_data;
		}

		if ( ! isset( $header_data['post_data'] ) ) {
			$header_data['post_data'] = [
				'builder_post_id'    => false,
				'builder_post_title' => '',
				'builder_post_type'  => $this->post_type,
			];
		}

		$header_data['post_data']['builder_post_title'] = sprintf(
			'%s #%s',
			learndash_get_custom_label( 'order' ),
			Cast::to_int( $header_data['post_data']['builder_post_id'] )
		);

		if ( $transaction->is_test_mode() ) {
			$header_data['post_data']['builder_post_title'] .= Template::get_admin_template(
				'modules/payments/orders/components/test-mode-label',
				[
					'label' => sprintf(
						// translators: placeholder: Order label.
						__( 'Test %s', 'learndash' ),
						learndash_get_custom_label( 'order' )
					),
				]
			);
		}

		return $header_data;
	}

	/**
	 * Updates the <title> tag to match our expected format.
	 *
	 * @since 4.19.0
	 *
	 * @param string $admin_title The page title, with extra context added for the current page.
	 * @param string $title       The original page title, not specific to the current page.
	 *
	 * @return string
	 */
	public function update_title_tag( $admin_title, $title ) {
		$transaction = $this->get_transaction();

		if (
			! $this->is_edit_screen()
			|| ! $transaction
		) {
			return $admin_title;
		}

		$post_title = htmlentities2( get_the_title() );

		return str_replace(
			$post_title,
			htmlentities2(
				sprintf(
					'%s #%s',
					learndash_get_custom_label( 'order' ),
					Cast::to_int( get_the_ID() )
				)
			),
			$admin_title
		);
	}

	/**
	 * Re-sends the Invoice for the Order.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function resend_invoice(): void {
		$invoice = $this->get_invoice();

		if (
			! wp_verify_nonce(
				Cast::to_string( SuperGlobals::get_get_var( 'nonce' ) ),
				self::$resend_invoice_nonce_action
			)
			|| ! SuperGlobals::get_get_var( 'resend_invoice' )
			|| ! $invoice
		) {
			return;
		}

		$send_invoice = new Send_Invoice( $invoice );

		$send_invoice->with_notice();
		$send_invoice->send();
	}

	/**
	 * Registers scripts on the Order Edit screen.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		if ( ! $this->is_edit_screen() ) {
			return;
		}

		Asset::add( 'learndash-order-edit', 'edit.js' )
			->add_dependency( 'jquery' )
			->add_to_group( 'learndash-module-payments' )
			->set_path( 'src/assets/dist/js/admin/modules/payments/orders', false )
			->register();
	}

	/**
	 * Prevents any logic that would save a custom metabox order from being ran.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function prevent_saving_metabox_order(): void {
		if (
			! check_admin_referer( 'meta-box-order', '_ajax_nonce' )
			|| SuperGlobals::get_post_var( 'page', '' ) !== $this->post_type
		) {
			return;
		}

		wp_send_json_error(
			[
				'message' => __( 'Rearranging metaboxes on this page is not allowed.', 'learndash' ),
			],
			405
		);
	}

	/**
	 * Hides post states text on the Orders list page.
	 *
	 * @since 4.19.0
	 *
	 * @param string[] $post_states An array of post display states.
	 * @param WP_Post  $post The current post object.
	 *
	 * @return string[]
	 */
	public function hide_post_states( $post_states, $post ) {
		if ( $post->post_type !== $this->post_type ) {
			return $post_states;
		}

		return [];
	}

	/**
	 * Retrieves the Transaction for the current request.
	 *
	 * @since 4.19.0
	 *
	 * @return Transaction|null Transaction Model or null on failure.
	 */
	private function get_transaction(): ?Transaction {
		$transaction_id = Cast::to_int( SuperGlobals::get_get_var( 'post' ) );

		return Transaction::find( $transaction_id );
	}

	/**
	 * Retrieves the Invoice for the current request.
	 *
	 * @since 4.19.0
	 *
	 * @return Invoice|null Invoice Model or null on failure.
	 */
	private function get_invoice(): ?Invoice {
		$transaction = $this->get_transaction();

		if ( ! $transaction ) {
			return null;
		}

		try {
			$invoice = Invoice::create_from_transaction( $transaction );
		} catch ( InvalidArgumentException $e ) {
			// If the Transaction does not have a product, we cannot create an Invoice.
			return null;
		}

		return $invoice;
	}

	/**
	 * Returns if we're currently viewing the edit screen for an Order.
	 *
	 * @since 4.19.0
	 *
	 * @return bool
	 */
	private function is_edit_screen(): bool {
		$current_screen = get_current_screen();

		return $current_screen instanceof WP_Screen
			&& $current_screen->id === $this->post_type;
	}
}
