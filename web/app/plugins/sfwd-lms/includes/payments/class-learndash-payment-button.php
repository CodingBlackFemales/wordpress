<?php
/**
 * Class for mapping payment button markup.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Payment_Button' ) ) {
	/**
	 * Class for mapping payment button HTML markup.
	 *
	 * @since 4.5.0
	 */
	class Learndash_Payment_Button {
		private const BUTTON_CLASS = 'btn-join';
		private const BUTTON_ID    = 'btn-join';

		/**
		 * Current post.
		 *
		 * @since 4.5.0
		 *
		 * @var WP_Post|null
		 */
		private $post;

		/**
		 * Current user.
		 *
		 * @since 4.5.0
		 *
		 * @var WP_User
		 */
		private $current_user;

		/**
		 * Default payment button params for filters.
		 *
		 * @since 4.5.0
		 *
		 * @var array{
		 *     type?: string,
		 *     price?: float,
		 * }
		 */
		private $default_payment_params = array();

		/**
		 * Registration page ID.
		 *
		 * @since 4.5.0
		 *
		 * @var int
		 */
		private $registration_page_id;

		/**
		 * True if the registration page is set, otherwise false.
		 *
		 * @since 4.5.0
		 *
		 * @var bool
		 */
		private $registration_page_is_set;

		/**
		 * True if a user is on the registration page, otherwise false.
		 *
		 * @since 4.5.0
		 *
		 * @var bool
		 */
		private $is_on_registration_page;

		/**
		 * Button label.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $button_label;

		/**
		 * Construct.
		 *
		 * @since 4.5.0
		 *
		 * @param int|WP_Post|null $post Post ID or `WP_Post` object.
		 *
		 * @return void
		 */
		public function __construct( $post ) {
			if ( $post instanceof WP_Post ) {
				$this->post = $post;
			} elseif ( is_numeric( $post ) ) {
				$this->post = get_post( $post );
			} else {
				$this->post = get_post();
			}

			$this->current_user = wp_get_current_user();

			$this->registration_page_id     = learndash_registration_page_get_id();
			$this->registration_page_is_set = learndash_registration_page_is_set();
			$this->is_on_registration_page  = $this->is_on_registration_page();

			$this->button_label = $this->map_label();
		}

		/**
		 * Returns payment button class name.
		 *
		 * @since 4.5.0
		 *
		 * @param string $additional_class Additional class. Default empty string.
		 *
		 * @return string
		 */
		public static function map_button_class_name( string $additional_class = '' ): string {
			$result = self::BUTTON_CLASS;

			if ( ! empty( $additional_class ) ) {
				$result .= " $additional_class";
			}

			return $result;
		}

		/**
		 * Returns payment button ID.
		 *
		 * @since 4.5.0
		 *
		 * @param string $additional_id Additional ID. Default empty string.
		 *
		 * @return string
		 */
		public static function map_button_id( string $additional_id = '' ): string {
			$result = self::BUTTON_ID;

			if ( ! empty( $additional_id ) ) {
				$result .= " $additional_id";
			}

			return $result;
		}

		/**
		 * Returns payment button HTML output.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public function map(): string {
			if ( ! $this->post instanceof WP_Post ) {
				return '';
			}

			try {
				$product = Learndash_Product_Model::create_from_post( $this->post );
			} catch ( Exception $e ) {
				return '';
			}

			if ( $product->user_has_access( $this->current_user ) ) {
				return '';
			}

			try {
				$product_pricing = $product->get_pricing( $this->current_user );
			} catch ( Learndash_DTO_Validation_Exception $e ) {
				return '';
			}

			$product_pricing_type = $product->get_pricing_type();

			$this->default_payment_params = array(
				'type'  => $product_pricing_type,
				'price' => $product_pricing->price,
			);

			switch ( $product_pricing_type ) {
				case LEARNDASH_PRICE_TYPE_OPEN:
					return $this->button_open();

				case LEARNDASH_PRICE_TYPE_CLOSED:
					return $this->button_closed();

				case LEARNDASH_PRICE_TYPE_FREE:
					return $this->button_free();

				case LEARNDASH_PRICE_TYPE_PAYNOW:
				case LEARNDASH_PRICE_TYPE_SUBSCRIBE:
					if ( empty( $product_pricing->price ) ) {
						return '';
					} elseif ( $this->registration_page_is_set && ! $this->is_on_registration_page ) {
						return $this->button_registration_page_link();
					} else {
						return $this->button_paid();
					}

				default:
					return '';
			}
		}

		/**
		 * Returns a button for open posts.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		private function button_open(): string {
			/**
			 * Filters payment button HTML markup for open price type.
			 *
			 * @since 4.5.0
			 *
			 * @param string $button Payment button HTML markup for open price type. Default empty.
			 * @param array  $params Payment parameters.
			 *
			 * @return string Payment button HTML markup.
			 */
			return (string) apply_filters( 'learndash_payment_button_open', '', $this->default_payment_params );
		}

		/**
		 * Returns a button for closed posts.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		private function button_closed(): string {
			/**
			 * Post settings.
			 *
			 * @var array{
			 *     custom_button_url?: string
			 * } $post_settings
			 */
			$post_settings = learndash_get_setting( $this->post->ID ); // @phpstan-ignore-line -- $this->post is checked before in the map().
			$button_url    = $post_settings['custom_button_url'] ?? '';

			$button = '';

			if ( ! empty( $button_url ) ) {
				// If the value does NOT start with [http://, https://, /] we prepend the home URL.
				if (
					stripos( $button_url, 'http://', 0 ) !== 0
					&& stripos( $button_url, 'https://', 0 ) !== 0
					&& strpos( $button_url, '/' ) !== 0
				) {
					$button_url = get_home_url( null, $button_url );
				}

				$button = sprintf(
					'<a class="%s" id="%s" href="%s">%s</a>',
					esc_attr( $this->map_button_class_name( 'learndash-button-closed' ) ),
					esc_attr( $this->map_button_id() ),
					esc_url( $button_url ),
					$this->button_label
				);
			}

			/**
			 * Filters payment button HTML markup for closed price type.
			 *
			 * @since 2.1.0
			 * @deprecated 4.5.0 Use the {@see 'learndash_payment_button_closed'} filter instead.
			 *
			 * @param string $button         Payment button HTML markup.
			 * @param array  $payment_params Payment parameters.
			 *
			 * @return string Payment button HTML markup.
			 */
			$button = (string) apply_filters_deprecated(
				'learndash_payment_closed_button',
				array(
					$button,
					array_merge(
						$this->default_payment_params,
						array(
							'custom_button_url' => $button_url,
						)
					),
				),
				'4.5.0',
				'learndash_payment_button_closed'
			);

			/**
			 * Filters payment button HTML markup for closed price type.
			 *
			 * @since 4.5.0
			 *
			 * @param string $button Payment button HTML markup.
			 * @param array  $params Payment parameters.
			 *
			 * @return string Payment button HTML markup.
			 */
			return (string) apply_filters(
				'learndash_payment_button_closed',
				$button,
				array_merge(
					$this->default_payment_params,
					array(
						'custom_button_url' => $button_url,
					)
				)
			);
		}

		/**
		 * Returns a button for free posts.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		private function button_free(): string {
			$post_type_prefix = LDLMS_Post_Types::get_post_type_key( $this->post->post_type ); // @phpstan-ignore-line -- $this->post is checked before in the map().
			$post_id          = $this->post->ID; // @phpstan-ignore-line -- $this->post is checked before in the map().
			$permalink        = (string) get_permalink( $this->post ); // @phpstan-ignore-line -- $this->post is checked before in the map().

			$button = '
				<form action="' . esc_url( $permalink ) . '" method="post">
					<input type="hidden" value="' . esc_attr( (string) $post_id ) . '" name="' . esc_attr( $post_type_prefix . '_id' ) . '" />
					<input type="hidden" name="' . esc_attr( $post_type_prefix . '_join' ) . '" value="' . esc_attr( wp_create_nonce( $post_type_prefix . '_join_' . $this->current_user->ID . '_' . $post_id ) ) . '" />
					<input type="submit" class="' . esc_attr( $this->map_button_class_name( 'learndash-button-free' ) ) . '" id="' . esc_attr( $this->map_button_id() ) . '" value="' . esc_attr( $this->button_label ) . '" />
				</form>
			';

			/**
			 * Filters payment button HTML markup for free price type.
			 *
			 * @since 4.5.0
			 *
			 * @param string $button Payment button HTML markup.
			 * @param array  $params Payment parameters.
			 *
			 * @return string Payment button HTML markup.
			 */
			return (string) apply_filters( 'learndash_payment_button_free', $button, $this->default_payment_params );
		}

		/**
		 * Returns a button for paid posts.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		private function button_paid(): string {
			/**
			 * Filters payment buttons list.
			 *
			 * @since 4.5.0
			 *
			 * @param array   $buttons Payment buttons. An associative array where a key is a payment gateway name, and a value is payment button HTML markup.
			 * @param WP_Post $post    Post being processing.
			 * @param array   $params  Payment params.
			 *
			 * @return array Payment buttons list.
			 */
			$buttons = (array) apply_filters( // @phpstan-ignore-line -- $this->post is checked before in the map().
				'learndash_payment_buttons',
				array(),
				$this->post,
				$this->default_payment_params
			);

			$buttons = array_filter( $buttons );

			if ( empty( $buttons ) ) {
				/**
				 * Filters payment button HTML markup.
				 *
				 * @since 2.1.0
				 *
				 * @param string $payment_button Payment button HTML markup.
				 * @param array  $params         Payment parameters.
				 *
				 * @return string Payment button HTML markup.
				 */
				return (string) apply_filters( 'learndash_payment_button', '', $this->default_payment_params );
			}

			$wrapper_start = '<div class="learndash_checkout_buttons">';
			$wrapper_end   = '</div>';

			/** This filter is documented in includes/payments/class-learndash-payment-button.php */
			$button_html = (string) apply_filters(
				'learndash_payment_button',
				implode(
					'',
					array_map(
						function( string $button ) {
							return '<div>' . $button . '</div>';
						},
						$buttons
					)
				),
				$this->default_payment_params
			);

			if ( 1 === count( $buttons ) ) {
				return $wrapper_start . $button_html . $wrapper_end;
			}

			if ( ! $this->registration_page_is_set || $this->is_on_registration_page ) {
				$this->print_dropdown_buttons_in_footer( $button_html );
			}

			$button_dropdown_trigger = sprintf(
				'<button class="%s" id="%s" data-jq-dropdown="#learndash-payment-button-dropdown">%s</button>',
				esc_attr( $this->map_button_class_name( 'learndash_checkout_button' ) ),
				esc_attr( $this->map_button_id() ),
				esc_attr( $this->button_label )
			);

			/**
			 * Filters dropdown payment button HTML markup.
			 *
			 * @deprecated 4.5.0 Use the {@see 'learndash_payment_button_dropdown_trigger'} filter instead.
			 *
			 * @param string $button Dropdown payment button HTML markup.
			 *
			 * @return string Dropdown payment button HTML markup.
			 */
			$button = (string) apply_filters_deprecated(
				'learndash_dropdown_payment_button',
				array( $wrapper_start . $button_dropdown_trigger . $wrapper_end ),
				'4.5.0',
				'learndash_payment_button_dropdown_trigger'
			);

			/**
			 * Filters payment button dropdown trigger HTML markup.
			 *
			 * @since 4.5.0
			 *
			 * @param string $button Payment button dropdown trigger HTML markup.
			 *
			 * @return string Payment button dropdown trigger HTML markup.
			 */
			return (string) apply_filters( 'learndash_payment_button_dropdown_trigger', $button );
		}

		/**
		 * Maps the payment button label.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		private function map_label(): string {
			if ( learndash_is_course_post( $this->post ) ) {
				$label_key = LearnDash_Custom_Label::$button_take_course;
			} elseif ( learndash_is_group_post( $this->post ) ) {
				$label_key = LearnDash_Custom_Label::$button_take_group;
			} else {
				return '';
			}

			return LearnDash_Custom_Label::get_label( $label_key );
		}

		/**
		 * Prints dropdown buttons in footer.
		 *
		 * @since 4.5.0
		 *
		 * @param string $button_html Payment button HTML markup.
		 *
		 * @return void
		 */
		private function print_dropdown_buttons_in_footer( string $button_html ): void {
			$dropdown_buttons  = '<div id="learndash-payment-button-dropdown" class="jq-dropdown jq-dropdown-tip checkout-dropdown-button">';
			$dropdown_buttons .= '<ul class="jq-dropdown-menu"><li>' . $button_html . '</li></ul>';
			$dropdown_buttons .= '</div>';

			/**
			 * Filters payment button dropdown HTML markup.
			 *
			 * @since 4.5.0
			 *
			 * @param string $dropdown_buttons Payment button dropdown HTML markup.
			 *
			 * @return string Payment button dropdown HTML markup.
			 */
			$dropdown_buttons = (string) apply_filters( 'learndash_payment_button_dropdown', $dropdown_buttons );

			add_action(
				'wp_footer',
				function () use ( $dropdown_buttons ) {
					echo $dropdown_buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
				}
			);
		}

		/**
		 * Returns a button link to the registration page.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		private function button_registration_page_link(): string {
			$button = '<form action="' . esc_url( (string) get_permalink( $this->registration_page_id ) ) . '" method="get">';
			if ( empty( get_option( 'permalink_structure' ) ) ) {
				$button .= '<input type="hidden" value="' . esc_attr( (string) $this->registration_page_id ) . '" name="page_id" />';
			}
			$button .= '<input type="hidden" value="' . esc_attr( (string) $this->post->ID ) . '" name="ld_register_id" />'; // @phpstan-ignore-line -- $this->post is checked before in the map().
			$button .= '<input type="submit" class="' . esc_attr( $this->map_button_class_name() ) . '" id="' . esc_attr( $this->map_button_id() ) . '" value="' . esc_attr( $this->button_label ) . '" />';
			$button .= '</form>';

			return $button;
		}

		/**
		 * Returns true if a user is on the registration page.
		 *
		 * @since 4.5.0
		 *
		 * @return bool
		 */
		private function is_on_registration_page(): bool {
			if ( ! $this->registration_page_is_set ) {
				return false;
			}

			return (int) get_the_ID() === $this->registration_page_id;
		}
	}
}
