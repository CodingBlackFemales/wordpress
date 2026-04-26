<?php
/**
 * A base class for all views.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template;

use LearnDash\Core\Template\View as View_Base;
use WP_User;

/**
 * A base class for all views.
 *
 * @since 4.9.0
 */
abstract class View {
	/**
	 * View slug.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $view_slug;

	/**
	 * Context.
	 *
	 * @since 4.9.0
	 *
	 * @var array<string, mixed>
	 */
	protected $context;

	/**
	 * Template.
	 *
	 * @since 4.9.0
	 *
	 * @var ?Template
	 */
	protected $template;

	/**
	 * Whether the view is for an admin page.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	protected $is_admin;

	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param string       $view_slug View slug.
	 * @param array<mixed> $context   Context.
	 * @param bool         $is_admin  Whether the view is for an admin page. Default false.
	 */
	public function __construct( string $view_slug, array $context = [], bool $is_admin = false ) {
		$this->view_slug = $view_slug;
		$this->is_admin  = $is_admin;
		$user            = wp_get_current_user();

		/**
		 * Filters the view context.
		 *
		 * @since 4.21.0
		 *
		 * @param array<string, mixed> $context    Context.
		 * @param string               $view_slug  View slug.
		 * @param bool                 $is_admin   Whether the view is for an admin page.
		 * @param WP_User              $user       The user object.
		 * @param View_Base            $view       The view object.
		 *
		 * @return array<string, mixed>
		 */
		$this->context = apply_filters(
			'learndash_template_view_context',
			array_merge(
				$context,
				[
					'user' => $user, // Always include the current user.
				]
			),
			$view_slug,
			$is_admin,
			$user,
			$this
		);
	}

	/**
	 * Returns the view context.
	 *
	 * @since 4.21.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_context(): array {
		return $this->context;
	}

	/**
	 * Gets the view HTML.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_html(): string {
		$template = new Template( $this->view_slug, $this->context, $this->is_admin, $this );

		$this->set_template( $template );

		return $template->get_content();
	}

	/**
	 * Outputs the view HTML.
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	public function show_html(): void {
		echo $this->get_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- We need to output the HTML.
	}

	/**
	 * Gets the template object.
	 *
	 * @since 4.9.0
	 *
	 * @return Template|null
	 */
	public function get_template(): ?Template {
		return $this->template;
	}

	/**
	 * Sets the template object.
	 *
	 * @since 4.9.0
	 *
	 * @param Template $template The template object.
	 *
	 * @return View
	 */
	public function set_template( Template $template ): View {
		$this->template = $template;

		return $this;
	}
}
