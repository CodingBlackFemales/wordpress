<?php
/**
 * A base class for all views.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Template\Views;

use LDLMS_Post_Types;
use LearnDash\Core\Template\Template;
use LearnDash_Custom_Label;

/**
 * A base class for all views.
 *
 * @since 4.6.0
 */
abstract class View {
	/**
	 * View slug.
	 *
	 * @var string
	 */
	protected $view_slug;

	/**
	 * Context.
	 *
	 * @var array<string, mixed>
	 */
	protected $context;

	/**
	 * Template.
	 *
	 * @var ?Template
	 */
	protected $template;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param string       $view_slug View slug.
	 * @param array<mixed> $context   Context.
	 */
	public function __construct( string $view_slug, array $context = array() ) {
		$this->view_slug = $view_slug;

		$this->context = array_merge(
			$context,
			array(
				'user' => wp_get_current_user(),
			)
		);

		add_action( 'wp_enqueue_scripts', array( $this, 'manage_assets' ) );
	}

	/**
	 * Gets the breadcrumbs base.
	 *
	 * @since 4.6.0
	 *
	 * @return array<string, string>[]
	 */
	protected function get_breadcrumbs_base(): array {
		$course_slug = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE );

		$breadcrumbs = [
			[
				'url'   => learndash_post_type_has_archive( $course_slug ) ? (string) get_post_type_archive_link( $course_slug ) : '',
				'label' => LearnDash_Custom_Label::get_label( 'courses' ),
				'id'    => 'courses',
			],
		];

		/**
		 * Filters the breadcrumbs base.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, string>[] $breadcrumbs The breadcrumbs base.
		 * @param string                  $view_slug   The view slug.
		 * @param View                    $view        The view object.
		 *
		 * @ignore
		 */
		return (array) apply_filters(
			'learndash_template_views_breadcrumbs_base',
			$breadcrumbs,
			$this->view_slug,
			$this
		);
	}

	/**
	 * Gets the view HTML.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_html(): string {
		$template = new Template( $this->view_slug, $this->context, $this );

		$this->set_template( $template );

		return $template->get_content();
	}

	/**
	 * Gets the template object.
	 *
	 * @since 4.6.0
	 *
	 * @return Template|null
	 */
	public function get_template(): ?Template {
		return $this->template;
	}

	/**
	 * Sets the template object.
	 *
	 * @since 4.6.0
	 *
	 * @param Template $template The template object.
	 *
	 * @return View
	 */
	public function set_template( Template $template ): View {
		$this->template = $template;

		return $this;
	}

	/**
	 * Manages assets.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function manage_assets(): void {
		wp_dequeue_style( 'learndash_template_style_css' );
	}
}
