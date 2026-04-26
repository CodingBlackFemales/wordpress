<?php
/**
 * LearnDash LD30 Modern Course Template Tweaks.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Course;

use LDLMS_Post_Types;
use LearnDash\Core\Models;
use LearnDash\Core\Models\Course;
use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\Template as TemplateEngine;
use LearnDash\Core\Template\View as View_Base;
use LearnDash\Core\Template\Views;
use Learndash_Payment_Button;
use WP_User;
use LearnDash_Settings_Section;
use LearnDash\Core\Template\Progression;
use LearnDash\Core\Template\Alerts;

/**
 * LearnDash LD30 Modern Course Template Tweaks.
 *
 * @since 4.21.0
 *
 * @phpstan-import-type Payment_Params from Learndash_Payment_Button
 */
class Template {
	/**
	 * Course model object.
	 *
	 * @since 4.21.0
	 *
	 * @var Models\Course|null
	 */
	private ?Models\Course $course = null;

	/**
	 * Number of lessons per page.
	 *
	 * @since 4.21.0
	 *
	 * @var int
	 */
	private int $lessons_per_page = 0;

	/**
	 * Adds additional context to the course view.
	 *
	 * @since 4.21.0
	 *
	 * @param array<string,mixed> $context   The current context.
	 * @param string              $view_slug The view slug.
	 * @param bool                $is_admin  Whether the view is for an admin page.
	 * @param WP_User             $user      The user object.
	 * @param View_Base           $view      The view object.
	 *
	 * @return array<string, mixed>
	 */
	public function add_additional_context( $context, string $view_slug, bool $is_admin, WP_User $user, View_Base $view ): array {
		if ( ! $view instanceof Views\Course ) {
			return $context;
		}

		$this->course           = $view->get_model();
		$this->lessons_per_page = learndash_get_course_lessons_per_page( $this->course->get_id() );

		// Build a product.
		$product = $this->course->get_product();

		// Add pagination context to the global context.
		$context = $this->add_pagination_context( $context );

		// Map lessons.
		$lessons = $this->course->get_lessons(
			$this->lessons_per_page,
			$this->lessons_per_page * ( $context['pagination'][ LDLMS_Post_Types::LESSON ]['paged'] - 1 )
		);

		$product_access_options_mapper = new Product_Access_Options_Mapper();

		$custom_login_enabled = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' ) === 'yes'
			/** This filter is documented in themes/ld30/templates/modules/infobar/course.php */
			&& apply_filters( 'learndash_login_modal', true, $this->course->get_id(), $user->ID );

		$context = array_merge(
			$context,
			[
				'access_options'             => $product_access_options_mapper->map( $product ),
				/** This filter is documented in themes/ld30/templates/course.php */
				'content_is_expanded'        => apply_filters(
					'learndash_course_steps_expand_all',
					false,
					$this->course->get_id(),
					'course_lessons_listing_main'
				),
				'custom_login_enabled'       => $custom_login_enabled,
				'final_quizzes'              => $this->course->get_quizzes(),
				'is_content_visible'         => $product->is_content_visible(),
				'lessons'                    => $lessons,
				'lesson_progression_enabled' => learndash_lesson_progression_enabled( $this->course->get_id() ),
				'requirements'               => [
					'points'        => learndash_get_course_points_enabled( $this->course->get_id() )
						? learndash_get_course_points_access( $this->course->get_id() )
						: 0,
					'prerequisites' => [
						'type'       => learndash_get_course_prerequisite_enabled( $this->course->get_id() )
							? strtolower( learndash_get_course_prerequisite_compare( $this->course->get_id() ) )
							: '',
						'course_ids' => learndash_get_course_prerequisite( $this->course->get_id() ),
					],
				],
				'sections'                   => array_map(
					fn( $section ) => $section->post_title,
					learndash_30_get_course_sections( $this->course->get_id() )
				),
				'show_sidebar'               => $this->should_show_sidebar( $view, $user ),
			]
		);

		$context['show_header'] = $this->should_show_header( $view, $user, $context );

		return $context;
	}

	/**
	 * Changes the payment button label in the course page.
	 *
	 * @since 4.21.0
	 *
	 * @param string       $label   The label of the payment button.
	 * @param Product|null $product The product model.
	 * @param WP_User      $user    The user object.
	 *
	 * @return string
	 */
	public function change_payment_button_label( $label, ?Product $product, WP_User $user ): string {
		// Don't change the label if the product is not a course or the user has access to the course.
		if (
			! $product
			|| ! is_singular( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ) )
			|| $product->user_has_access( $user )
		) {
			return $label;
		}

		// Pre-order.

		if ( ! $product->has_started() ) {
			return sprintf(
				// translators: placeholder: Course label.
				esc_html_x( 'Pre-order this %s', 'placeholder: Course label', 'learndash' ),
				$product->get_type_label( true )
			);
		}

		return sprintf(
			// translators: placeholder: Course label.
			esc_html_x( 'Enroll in this %s', 'placeholder: Course label', 'learndash' ),
			$product->get_type_label( true )
		);
	}

	/**
	 * Adds the 'ld-enrollment__join-button' class to the payment button on Modern Course Pages.
	 *
	 * @since 4.21.0
	 *
	 * @param string $classes CSS classes for the payment button.
	 *
	 * @return string
	 */
	public function change_payment_button_classes( $classes ): string {
		if (
			! is_singular( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ) )
		) {
			return $classes;
		}

		return "ld-enrollment__join-button {$classes}";
	}

	/**
	 * Builds and adds the Pagination Context with properly nested defaults.
	 *
	 * @since 4.21.0
	 *
	 * @param array<string, mixed> $context Context array.
	 *
	 * @return array{pagination:array{lesson:array{paged:int,pages_total:int}}}
	 */
	private function add_pagination_context( array $context ): array {
		$context = wp_parse_args(
			$context,
			[
				'pagination' => [],
			]
		);

		$context['pagination'] = wp_parse_args(
			$context['pagination'],
			[
				LDLMS_Post_Types::LESSON => [],
			]
		);

		$context['pagination'][ LDLMS_Post_Types::LESSON ] = wp_parse_args(
			$context['pagination'][ LDLMS_Post_Types::LESSON ],
			[
				'paged'       => 1,
				'pages_total' => $this->course instanceof Models\Course
					? (int) ceil( $this->course->get_lessons_number() / $this->lessons_per_page )
					: 0,
			]
		);

		/**
		 * Modified View Context to include Pagination Context.
		 *
		 * @var array{pagination:array{lesson:array{paged:int,pages_total:int}}} $context
		 */
		return $context;
	}

	/**
	 * Updates the free payment button when a user is not logged in.
	 *
	 * @since 4.21.0
	 *
	 * @param string         $button_html The free payment button HTML.
	 * @param Payment_Params $params      Payment parameters.
	 *
	 * @return string
	 */
	public function change_free_payment_button( $button_html, array $params ): string { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- It's correct.
		if (
			! is_singular( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ) )
			|| is_user_logged_in()
		) {
			return $button_html;
		}

		// Define the registration URL.

		// If LD registration page is set, use that URL.
		if ( learndash_registration_page_is_set() ) {
			$registration_url = learndash_registration_page_build_url( $params['product_id'] );
		} else {
			// Otherwise, use the default WP registration or login URL, depending on the registration setting.
			$registration_url = get_option( 'users_can_register' )
				? wp_registration_url()
				: wp_login_url( (string) get_permalink( $params['product_id'] ) );
		}

		return TemplateEngine::get_template(
			'modern/course/enrollment/join/registration-button',
			[
				'registration_url' => $registration_url,
				'button_label'     => $params['button_label'],
			]
		);
	}

	/**
	 * Determines whether the header should be shown on the course page.
	 *
	 * @since 4.21.0
	 * @since 4.24.0 Added the $context parameter.
	 *
	 * @param Views\Course         $view    The course view.
	 * @param WP_User              $user    The user object.
	 * @param array<string, mixed> $context The view context array. Default is empty array.
	 *
	 * @return bool
	 */
	private function should_show_header( Views\Course $view, WP_User $user, array $context = [] ) {
		$course      = $view->get_model();
		$show_header = false;

		if (
			(
				isset( $context['progress_bar'] )
				&& $context['progress_bar'] instanceof Progression\Bar
				&& $context['progress_bar']->should_show()
				&& $user->exists()
				&& $course->get_product()->user_has_access( $user )
			)
			|| (
				isset( $context['alerts'] )
				&& $context['alerts'] instanceof Alerts\Alerts
				&& ! $context['alerts']->is_empty()
			)
		) {
			$show_header = true;
		}

		/**
		 * Filters whether the course header should be shown on the course page.
		 *
		 * @since 4.21.0
		 * @since 4.24.0 Added the $context parameter.
		 *
		 * @param bool                 $show_header Whether the course header should be shown. Default is false when neither the Progress Bar or Alerts are visible.
		 * @param Views\Course         $view        The course view.
		 * @param WP_User              $user        The user object.
		 * @param Course               $course      The course model object.
		 * @param array<string, mixed> $context     The view context array. Default is empty array.
		 *
		 * @return bool
		 */
		return apply_filters(
			'learndash_ld30_modern_course_show_header',
			$show_header,
			$view,
			$user,
			$course,
			$context
		);
	}

	/**
	 * Determines whether the sidebar should be shown on the course page.
	 *
	 * @since 4.21.0
	 *
	 * @param Views\Course $view The course view.
	 * @param WP_User      $user The user object.
	 *
	 * @return bool
	 */
	private function should_show_sidebar( Views\Course $view, WP_User $user ) {
		$course  = $view->get_model();
		$product = $course->get_product();

		/**
		 * Filters whether the course sidebar should be shown on the course page.
		 *
		 * @since 4.21.0
		 *
		 * @param bool         $show_sidebar Whether the course sidebar should be shown. If the User doesn't have access to the Course, this defaults to true.
		 * @param Views\Course $view   The course view.
		 * @param WP_User      $user   The user object.
		 * @param Course       $course The course model object.
		 * @param Product      $product The product model object.
		 *
		 * @return bool
		 */
		return apply_filters(
			'learndash_ld30_modern_course_show_sidebar',
			! $product->user_has_access( $user ),
			$view,
			$user,
			$course,
			$product
		);
	}
}
