<?php
/**
 * LearnDash Admin Coupon Edit.
 *
 * @since 4.1.0
 * @package LearnDash\Coupon\Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Post_Edit' ) &&
	! class_exists( 'Learndash_Admin_Coupon_Edit' )
) {
	/**
	 * Class LearnDash Admin Coupon Edit.
	 *
	 * @since 4.1.0
	 * @uses Learndash_Admin_Post_Edit
	 */
	class Learndash_Admin_Coupon_Edit extends Learndash_Admin_Post_Edit {
		/**
		 * Public constructor for class.
		 *
		 * @since 4.1.0
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug(
				LDLMS_Post_Types::COUPON
			);

			parent::__construct();
		}

		/**
		 * On Load handler function for this post type edit.
		 * This function is called by a WP action when the admin
		 * page 'post.php' or 'post-new.php' are loaded.
		 *
		 * @since 4.1.0
		 */
		public function on_load() {
			if ( $this->post_type_check() ) {
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-coupon-settings.php';

				parent::on_load();
			}
		}

		/**
		 * Save metabox handler function.
		 *
		 * @since 4.1.0
		 *
		 * @param integer $post_id Post ID Question being edited.
		 * @param WP_Post $post WP_Post Question being edited.
		 * @param boolean $update If update true, else false.
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
}

new Learndash_Admin_Coupon_Edit();
