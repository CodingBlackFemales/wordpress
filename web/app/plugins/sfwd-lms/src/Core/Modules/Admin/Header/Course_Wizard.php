<?php
/**
 * LearnDash Admin Header Course Wizard service class.
 *
 * @since 4.23.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Admin\Header;

use LDLMS_Post_Types;

/**
 * Service class for Admin Header Course Wizard additions.
 *
 * @since 4.23.1
 */
class Course_Wizard {
	/**
	 * Add header buttons to the course admin page.
	 *
	 * @since 4.23.1
	 *
	 * @param array<string,mixed> $buttons Array of header buttons.
	 *
	 * @return array<int|string,mixed> Modified array of header buttons.
	 */
	public function add_header_buttons( $buttons = [] ) {
		$screen    = get_current_screen();
		$post_type = learndash_get_post_type_slug( LDLMS_Post_Types::COURSE );

		if ( is_object( $screen ) && 'edit-' . $post_type === $screen->id ) {
			$buttons[] = [
				'text' => esc_html__( 'Create from Video Playlist', 'learndash' ),
				'href' => esc_url( admin_url( 'admin.php?page=learndash-course-wizard' ) ),
			];
		}

		return $buttons;
	}
}
