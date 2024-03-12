<?php

namespace WPForms\SmartTags\SmartTag;

use WP_User;

/**
 * Class SmartTag.
 *
 * @since 1.6.7
 */
abstract class SmartTag {

	/**
	 * Full smart tag.
	 * For example: {smart_tag attr="1" attr2="true"}.
	 *
	 * @since 1.6.7
	 *
	 * @var string
	 */
	protected $smart_tag;

	/**
	 * Context usage.
	 *
	 * @since 1.8.7
	 *
	 * @var string
	 */
	protected $context;

	/**
	 * List of attributes.
	 *
	 * @since 1.6.7
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * SmartTag constructor.
	 *
	 * @since 1.6.7
	 * @since 1.8.7 Added $context parameter.
	 *
	 * @param string $smart_tag Full smart tag.
	 * @param string $context   Context usage.
	 */
	public function __construct( $smart_tag, $context = '' ) {

		$this->smart_tag = $smart_tag;
		$this->context   = $context;
	}

	/**
	 * Get smart tag value.
	 *
	 * @since 1.6.7
	 *
	 * @param array  $form_data Form data.
	 * @param array  $fields    List of fields.
	 * @param string $entry_id  Entry ID.
	 *
	 * @return string
	 */
	abstract public function get_value( $form_data, $fields = [], $entry_id = '' );

	/**
	 * Get list of smart tag attributes.
	 *
	 * @since 1.6.7
	 *
	 * @return array
	 */
	public function get_attributes() {

		if ( ! empty( $this->attributes ) ) {
			return $this->attributes;
		}

		/**
		 * (\w+) an attribute name and also the first capturing group. Lowercase or uppercase letters, digits, underscore.
		 * = the equal sign.
		 * (["\']) single or double quote, the second capturing group.
		 * (.+?) an attribute value within the quotes, and also the third capturing group. Any number of any characters except new line. Lazy mode - match as few characters as possible to allow multiple attributes on one line.
		 * \2 - repeat the second capturing group.
		 */
		preg_match_all( '/(\w+)=(["\'])(.+?)\2/', $this->smart_tag, $attributes );
		$this->attributes = array_combine( $attributes[1], $attributes[3] );

		return $this->attributes;
	}

	/**
	 * Get current user.
	 *
	 * @since 1.8.7
	 *
	 * @param string|int $entry_id Entry ID.
	 *
	 * @return WP_User|string
	 */
	public function get_user( $entry_id ) {

		if ( is_user_logged_in() ) {
			return wp_get_current_user();
		}

		if ( empty( $entry_id ) ) {
			return '';
		}

		// If user is not logged in, try to get the user from the entry.
		// Needed if we try to get the user during cron.
		$entry = wpforms()->get( 'entry' );

		if ( empty( $entry ) ) {
			return '';
		}

		$user          = null;
		$entry_data    = $entry->get( $entry_id );
		$entry_user_id = $entry_data->user_id ?? 0;

		if ( ! empty( $entry_user_id ) ) {
			$user = get_user_by( 'id', $entry_user_id );
		}

		if ( ! $user instanceof WP_User ) {
			return '';
		}

		return $user;
	}

	/**
	 * Get author.
	 *
	 * @since 1.8.7
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return WP_User|false WP_User object on success, false on failure.
	 */
	public function get_author( $form_id ) {

		$author_id = get_post_field( 'post_author', $form_id );

		return get_user_by( 'id', $author_id );
	}

	/**
	 * Get entry meta.
	 *
	 * @since 1.8.7
	 *
	 * @param string|int $entry_id Entry ID.
	 * @param string     $meta_key Meta key.
	 *
	 * @return string Meta value.
	 */
	public function get_meta( $entry_id, string $meta_key ) {

		if ( empty( $entry_id ) ) {
			return '';
		}

		$entry_meta = wpforms()->get( 'entry_meta' );

		if ( empty( $entry_meta ) ) {
			return '';
		}

		$meta = $entry_meta->get_meta(
			[
				'entry_id' => $entry_id,
				'type'     => $meta_key,
				'number'   => 1,
			]
		);

		return $meta[0]->data ?? '';
	}
}
