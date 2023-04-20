<?php
/**
 * Handles all server side logic for the ld-payment-buttons Gutenberg Block. This block is functionally the same
 * as the learndash_payment_buttons shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Payment_Buttons' ) ) ) {
	/**
	 * Class for handling LearnDash Payment Buttons Block
	 */
	class LearnDash_Gutenberg_Payment_Buttons extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {

			$this->shortcode_slug   = 'learndash_payment_buttons';
			$this->block_slug       = 'ld-payment-buttons';
			$this->block_attributes = array(
				'display_type'      => array(
					'type' => 'string',
				),
				'course_id'         => array(
					'type' => 'string',
				),
				'group_id'          => array(
					'type' => 'string',
				),
				'preview_show'      => array(
					'type' => 'boolean',
				),
				'preview_user_id'   => array(
					'type' => 'string',
				),
				'editing_post_meta' => array(
					'type' => 'object',
				),
			);
			$this->self_closing     = true;

			$this->init();
		}

		/**
		 * Render Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content. In the case of this function the rendered output will be for the
		 * [ld_profile] shortcode.
		 *
		 * @since 2.5.9
		 *
		 * @param array         $block_attributes The block attributes.
		 * @param string        $block_content    The block content.
		 * @param WP_Block|null $block            The block object.
		 *
		 * @return string
		 */
		public function render_block( $block_attributes = array(), $block_content = '', WP_Block $block = null ) {
			$course_post = null;

			$block_attributes = $this->preprocess_block_attributes( $block_attributes );

			// Only the 'editing_post_meta' element will be sent from within the post edit screen.
			if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
				$block_attributes['course_id'] = $this->block_attributes_get_post_id( $block_attributes, 'course' );
				$block_attributes['group_id']  = $this->block_attributes_get_post_id( $block_attributes, 'group' );

				if ( ( empty( $block_attributes['course_id'] ) ) && ( empty( $block_attributes['group_id'] ) ) ) {
					$edit_post_type = $this->block_attributes_get_editing_post_type( $block_attributes );
					$edit_post_id   = $this->block_attributes_get_editing_post_id( $block_attributes );

					if ( learndash_get_post_type_slug( 'group' ) === $edit_post_type ) {
						if ( ! empty( $edit_post_id ) ) {
							$block_attributes['group_id'] = $edit_post_id;
						}
					}

					if ( learndash_get_post_type_slug( 'course' ) === $edit_post_type ) {
						if ( ! empty( $edit_post_id ) ) {
							$block_attributes['course_id'] = $edit_post_id;
						}
					}
				}

				if ( ( empty( $block_attributes['course_id'] ) ) && ( empty( $block_attributes['group_id'] ) ) ) {
					return $this->render_block_wrap(
						'<span class="learndash-block-error-message">' . sprintf(
						// translators: placeholder: Course, Course.
							_x( '%1$s ID is required when not used within a %2$s.', 'placeholder: Course, Course', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'course' ),
							LearnDash_Custom_Label::get_label( 'course' )
						) . '</span>'
					);
				}

				if ( ! empty( $block_attributes['course_id'] ) ) {
					$course_post = get_post( (int) $block_attributes['course_id'] );
					if ( ( ! is_a( $course_post, 'WP_Post' ) ) || ( 'sfwd-courses' !== $course_post->post_type ) ) {
						return $this->render_block_wrap(
							'<span class="learndash-block-error-message">' . sprintf(
							// translators: placeholder: Course.
								_x( 'Invalid %1$s ID.', 'placeholder: Course', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'course' )
							) . '</span>'
						);
					}
				}

				if ( ! empty( $block_attributes['group_id'] ) ) {
					$group_post = get_post( (int) $block_attributes['group_id'] );
					if ( ( ! is_a( $group_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'group' ) !== $group_post->post_type ) ) {
						return $this->render_block_wrap(
							'<span class="learndash-block-error-message">' . sprintf(
							// translators: placeholder: Group.
								_x( 'Invalid %1$s ID.', 'placeholder: Group', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'group' )
							) . '</span>'
						);
					}
				}
			}

			if ( ( empty( $atts['course_id'] ) ) && ( empty( $atts['course_id'] ) ) ) {
				$viewed_post_id = (int) get_the_ID();
				if ( ! empty( $viewed_post_id ) ) {
					if ( in_array( get_post_type( $viewed_post_id ), learndash_get_post_types( 'course' ), true ) ) {
						$block_attributes['course_id'] = learndash_get_course_id( $viewed_post_id );
					} elseif ( get_post_type( $viewed_post_id ) === learndash_get_post_type_slug( 'group' ) ) {
						$block_attributes['group_id'] = $viewed_post_id;
					}
				}
			}

			$shortcode_out = '';

			if ( ! empty( $block_attributes['course_id'] ) ) {
				$course_price_type = learndash_get_setting( $course_post, 'course_price_type' );
				if ( empty( $course_price_type ) ) {
					$course_price_type = LEARNDASH_DEFAULT_COURSE_PRICE_TYPE;
				}

				if ( ! in_array( $course_price_type, array( 'free', 'paynow', 'subscribe' ), true ) ) {
					if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
						return $this->render_block_wrap(
							'<span class="learndash-block-error-message">' . sprintf(
							// translators: placeholder: Course.
								esc_html_x( '%s Price Type must be Free, PayNow or Subscribe.', 'placeholder: Course', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'course' )
							) . '</span>'
						);
					}
				}

				$shortcode_str = $this->build_block_shortcode( $block_attributes, $block_content );
				if ( ! empty( $shortcode_str ) ) {
					$shortcode_out = do_shortcode( $shortcode_str );

					// In case the button shortcode does not render and if we are editing we show a default button for the output.
					if ( ( empty( $shortcode_out ) ) && ( $this->block_attributes_is_editing_post( $block_attributes ) ) ) {
						$button_text = LearnDash_Custom_Label::get_label( 'button_take_this_course' );
						if ( ! empty( $button_text ) ) {
							$shortcode_out = '<a class="btn-join" href="#" id="btn-join">' . $button_text . '</a>';
							if ( ! empty( $shortcode_out ) ) {
								$shortcode_out = $this->render_block_wrap( $shortcode_out );
							}
						}
					}
				}

				return $shortcode_out;
			} elseif ( ! empty( $block_attributes['group_id'] ) ) {
				$group_price_type = learndash_get_setting( $block_attributes['group_id'], 'group_price_type' );
				if ( empty( $group_price_type ) ) {
					$group_price_type = LEARNDASH_DEFAULT_GROUP_PRICE_TYPE;
				}

				if ( ! in_array( $group_price_type, array( 'free', 'paynow', 'subscribe' ), true ) ) {
					if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
						return $this->render_block_wrap(
							'<span class="learndash-block-error-message">' . sprintf(
							// translators: placeholder: Group.
								esc_html_x( '%s Price Type must be Free, PayNow or Subscribe.', 'placeholder: Group', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'group' )
							) . '</span>'
						);
					}
				}

				$shortcode_str = $this->build_block_shortcode( $block_attributes, $block_content );
				if ( ! empty( $shortcode_str ) ) {
					$shortcode_out = do_shortcode( $shortcode_str );

					// In case the button shortcode does not render and if we are editing we show a default button for the output.
					if ( ( empty( $shortcode_out ) ) && ( $this->block_attributes_is_editing_post( $block_attributes ) ) ) {
						$button_text = LearnDash_Custom_Label::get_label( 'button_take_this_group' );
						if ( ! empty( $button_text ) ) {
							$shortcode_out = '<a class="btn-join" href="#" id="btn-join">' . $button_text . '</a>';
							if ( ! empty( $shortcode_out ) ) {
								$shortcode_out = $this->render_block_wrap( $shortcode_out );
							}
						}
					}
				}

				return $shortcode_out;
			}

			return '';
		}

		// End of functions.
	}
}
new LearnDash_Gutenberg_Payment_Buttons();
