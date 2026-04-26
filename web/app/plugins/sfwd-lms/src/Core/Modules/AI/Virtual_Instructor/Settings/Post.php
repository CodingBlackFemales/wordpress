<?php
/**
 * LearnDash Admin Virtual Instructor post edit screen.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor\Settings;

use LDLMS_Post_Types;
use Learndash_Admin_Post_Edit;
use WP_Post;

/**
 * Class LearnDash Admin Virtual Instructor edit screen.
 *
 * @since 4.13.0
 */
class Post extends Learndash_Admin_Post_Edit {
	/**
	 * Public constructor for class.
	 *
	 * @since 4.13.0
	 */
	public function __construct() {
		$this->post_type = learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR );

		parent::__construct();
	}

	/**
	 * Initialize the class.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public static function init(): void {
		new self();
	}

	/**
	 * Save metabox handler function.
	 *
	 * @since 4.13.0
	 *
	 * @param int     $post_id Post ID of virtual instructor being edited.
	 * @param WP_Post $post    WP_Post of virtual instructor being edited.
	 * @param bool    $update  True if this is an update of an existing virtual instructor, false otherwise.
	 *
	 * @return bool
	 */
	public function save_post( $post_id = 0, $post = null, $update = false ): bool {
		if ( ! $this->post_type_check( $post ) ) {
			return false;
		}

		if ( ! parent::save_post( $post_id, $post, $update ) ) {
			return false;
		}

		if ( empty( $this->_metaboxes ) ) {
			return true;
		}

		foreach ( $this->_metaboxes as $metabox ) {
			$settings_fields = $metabox->get_post_settings_field_updates(
				$post_id,
				$post,
				$update
			);

			$metabox->save_post_meta_box( $post_id, $post, $update, $settings_fields );
		}

		return true;
	}
}
