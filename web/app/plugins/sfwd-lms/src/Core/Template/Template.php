<?php
/**
 * LearnDash Template class.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template;

use LearnDash\Core\Template\View;
use LearnDash\Core\Utilities\Str;
use SFWD_LMS;

/**
 * A class to handle LearnDash templates.
 *
 * @since 4.6.0
 */
class Template {
	/**
	 * Template name.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Current rendering template name.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	private $current_rendering_name;

	/**
	 * Template arguments.
	 *
	 * @since 4.6.0
	 *
	 * @var array<string,mixed>
	 */
	private $args;

	/**
	 * Current rendering template arguments.
	 *
	 * @since 4.6.0
	 *
	 * @var array<string,mixed>
	 */
	private $current_rendering_args;

	/**
	 * View instance or null.
	 *
	 * @since 4.6.0
	 *
	 * @var View|null
	 */
	private $view;

	/**
	 * Whether the current template is an admin template.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	private $is_admin;

	/**
	 * Breakpoint pointer for the current template.
	 *
	 * @since 4.16.0
	 *
	 * @var string
	 */
	private $breakpoint_pointer;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param string              $name Template name.
	 * @param array<string,mixed> $args Template arguments.
	 * @param bool                $is_admin Whether the current template is an admin template. Default false.
	 * @param View|null           $view View instance or null.
	 */
	public function __construct(
		string $name,
		array $args = array(),
		bool $is_admin = false,
		View $view = null
	) {
		$this->name     = $name;
		$this->args     = $args;
		$this->is_admin = $is_admin;
		$this->view     = $view;

		// Set the current rendering template name and arguments.
		$this->current_rendering_name = $this->name;
		$this->current_rendering_args = $this->args;
	}

	/**
	 * Gets the template file path.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_file_path(): string {
		$skip_rendering = $this->skip_rendering( false, true );

		if ( $skip_rendering ) {
			return '';
		}

		return $this->get_template_path( false, true );
	}

	/**
	 * Gets a breakpoint pointer.
	 *
	 * @since 4.16.0
	 *
	 * @return string
	 */
	public function get_breakpoint_pointer(): string {
		if ( empty( $this->breakpoint_pointer ) ) {
			$this->breakpoint_pointer = Breakpoints::get_pointer();
		}

		return $this->breakpoint_pointer;
	}

	/**
	 * Gets the template breakpoints JSON.
	 *
	 * @since 4.16.0
	 *
	 * @return string
	 */
	public function get_breakpoints_json(): string {
		return (string) json_encode( [ 'breakpoints' => Breakpoints::get() ] );
	}

	/**
	 * Gets the template content.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_content(): string {
		return $this->get_template_output( false, false );
	}

	/**
	 * Gets the template context.
	 *
	 * @since 4.16.0
	 *
	 * @return array<string,mixed>
	 */
	public function get_context(): array {
		return $this->args;
	}

	/**
	 * Shows the template.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function show(): void {
		echo $this->get_template_output( true, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- We need to output the template content.
	}

	/**
	 * Gets the current view rendering instance.
	 *
	 * @since 4.6.0
	 *
	 * @return View|null
	 */
	public function get_current_view(): ?View {
		return $this->view;
	}

	/**
	 * Outputs a partial template, using the current context.
	 *
	 * @since 4.6.0
	 *
	 * @param string       $template_name Template name.
	 * @param array<mixed> $args          Template arguments.
	 *
	 * @return void
	 */
	public function template( string $template_name, array $args = array() ): void {
		// previous rendering template name and arguments.
		$previous_rendering_name = $this->current_rendering_name;
		$previous_rendering_args = $this->current_rendering_args;

		// Set the current rendering template name and arguments.
		$this->current_rendering_name = $template_name;
		$this->current_rendering_args = array_merge( $this->current_rendering_args, $args );

		$this->show();

		// Restore the current rendering template name and arguments.
		$this->current_rendering_name = $previous_rendering_name;
		$this->current_rendering_args = $previous_rendering_args;
	}

	/**
	 * Returns the template content.
	 *
	 * @since 4.6.0
	 *
	 * @param string              $template_name Template name.
	 * @param array<string,mixed> $args          Template arguments.
	 *
	 * @return string
	 */
	public static function get_template( string $template_name, array $args = array() ): string {
		return ( new self( $template_name, $args ) )->get_content();
	}

	/**
	 * Returns the admin template content.
	 *
	 * @since 4.9.0
	 *
	 * @param string              $template_name Template name.
	 * @param array<string,mixed> $args          Template arguments.
	 *
	 * @return string
	 */
	public static function get_admin_template( string $template_name, array $args = array() ): string {
		return ( new self( $template_name, $args, true ) )->get_content();
	}

	/**
	 * Prints the template content.
	 *
	 * @since 4.6.0
	 *
	 * @param string              $template_name Template name.
	 * @param array<string,mixed> $args          Template arguments.
	 *
	 * @return void
	 */
	public static function show_template( string $template_name, array $args = array() ): void {
		( new self( $template_name, $args ) )->show();
	}

	/**
	 * Prints the admin template content.
	 *
	 * @since 4.9.0
	 *
	 * @param string              $template_name Template name.
	 * @param array<string,mixed> $args          Template arguments.
	 *
	 * @return void
	 */
	public static function show_admin_template( string $template_name, array $args = array() ): void {
		( new self( $template_name, $args, true ) )->show();
	}

	/**
	 * Gets the template file name.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $echo             Whether to echo the template output or not.
	 * @param bool $return_file_path Whether to return the template file path or not.
	 *
	 * @return string
	 */
	protected function get_template_filename( bool $echo, bool $return_file_path ): string {
		$file_extension    = pathinfo( $this->current_rendering_name, PATHINFO_EXTENSION );
		$template_filename = empty( $file_extension ) ? $this->current_rendering_name . '.php' : $this->current_rendering_name;

		/**
		 * Filters template file name.
		 *
		 * @since 3.0.0
		 * @since 4.6.0 Added `$instance` parameter.
		 *
		 * @param string        $template_filename Template file name.
		 * @param string        $name              Template name.
		 * @param array         $args              Template data.
		 * @param bool          $echo              Whether to echo the template output or not.
		 * @param bool          $return_file_path  Whether to return the template file path or not.
		 * @param Template|null $instance          Current Instance of template engine rendering this template or null if not available (legacy).
		 */
		return apply_filters(
			'learndash_template_filename',
			$template_filename,
			$this->current_rendering_name,
			$this->current_rendering_args,
			$echo,
			$return_file_path,
			$this
		);
	}

	/**
	 * Checks if the template should be skipped.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $echo             Whether to echo the template output or not.
	 * @param bool $return_file_path Whether to return the template file path or not.
	 *
	 * @return bool
	 */
	private function skip_rendering( bool $echo, bool $return_file_path ): bool {
		/**
		 * Allow users to disable templates before rendering it.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param bool     $skip_rendering   Whether to skip rendering the template or not. Default false.
		 * @param string   $name             Template name.
		 * @param array    $args             Template data.
		 * @param bool     $echo             Whether to echo the template output or not.
		 * @param bool     $return_file_path Whether to return the template file path or not.
		 * @param Template $instance         Current Instance of template engine rendering this template.
		 */
		return apply_filters(
			'learndash_template_skip_rendering',
			false,
			$this->current_rendering_name,
			$this->current_rendering_args,
			$echo,
			$return_file_path,
			$this
		);
	}

	/**
	 * Gets the template path in the disk.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $echo             Whether to echo the template output or not.
	 * @param bool $return_file_path Whether to return the template file path or not.
	 *
	 * @return string
	 */
	protected function get_template_path( bool $echo, bool $return_file_path ): string {
		$template_filename = $this->get_template_filename( $echo, $return_file_path );

		if ( empty( $template_filename ) ) {
			return '';
		}

		$template_paths = ! $this->is_admin
						? $this->get_template_paths( $template_filename )
						: $this->get_admin_template_paths( $template_filename );
		$file_path      = '';

		if ( ! empty( $template_paths['theme'] ) ) {
			$file_path = locate_template( $template_paths['theme'] );
		}

		if ( empty( $file_path ) && ! empty( $template_paths['templates'] ) ) {
			foreach ( $template_paths['templates'] as $template ) {
				if ( file_exists( $template ) ) {
					$file_path = $template;
					break;
				}
			}
		}

		/** This filter is documented in includes/class-ld-lms.php */
		$file_path = apply_filters(
			'learndash_template',
			$file_path,
			$this->current_rendering_name,
			$this->current_rendering_args,
			$echo,
			$return_file_path
		);

		/**
		 * Filters file path for the learndash template.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param string              $file_path         File path for the learndash template.
		 * @param string              $template_filename Template file name.
		 * @param string              $name              Template name.
		 * @param array<string,mixed> $args              Template data.
		 * @param Template            $instance          Current Instance of template engine rendering this template.
		 */
		return apply_filters(
			'learndash_template_filepath',
			$file_path,
			$template_filename,
			$this->current_rendering_name,
			$this->current_rendering_args,
			$this
		);
	}

	/**
	 * Returns the template paths for frontend templates.
	 *
	 * @since 4.13.0
	 *
	 * @param string $file_name Template file name.
	 *
	 * @return array{theme:string[],templates:string[]}
	 */
	protected function get_template_paths( string $file_name ): array {
		$paths = [
			'theme'     => [],
			'templates' => [],
		];

		if ( ! empty( $file_name ) ) {
			$paths = SFWD_LMS::get_template_paths( $file_name );

			// Add src/views/ directory to the paths.

			$template_dir  = LEARNDASH_LMS_PLUGIN_DIR . 'src/views/';
			$file_pathinfo = pathinfo( $file_name );

			// Normalize path info.

			if ( empty( $file_pathinfo['dirname'] ) ) {
				$file_pathinfo['dirname'] = '';
			}

			if ( empty( $file_pathinfo['extension'] ) ) {
				$file_pathinfo['extension'] = '';
			}

			// Add index suffix to file name.

			$template_file_dir  = ! empty( $file_pathinfo['dirname'] ) && '.' !== $file_pathinfo['dirname']
								? trailingslashit( $file_pathinfo['dirname'] )
								: '';
			$template_file_name = $template_file_dir . $file_pathinfo['filename'] . '.' . $file_pathinfo['extension'];

			if ( ! is_file( $template_dir . $template_file_name ) ) {
				if ( is_dir( $template_dir . $template_file_dir ) ) {
					$template_file_name = $template_file_dir . $file_pathinfo['filename'] . '/index.' . $file_pathinfo['extension'];

					if ( ! is_file( $template_dir . $template_file_name ) ) {
						$template_file_name = '';
					}
				} else {
					$template_file_name = '';
				}
			}

			// Add template file name to paths.

			if ( ! empty( $template_file_name ) ) {
				$paths['templates'][] = $template_dir . $template_file_name;
			}
		}

		/**
		 * Filters the template paths for frontend templates.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates because they don't use this engine to render templates.
		 *
		 * @since 4.13.0
		 *
		 * @param array{theme:string[],templates:string[]} $paths     Template paths.
		 * @param string                                   $file_name Template file name.
		 * @param string                                   $name      Template name.
		 * @param array<string,mixed>                      $args      Template data.
		 * @param Template                                 $instance  Current Instance of template engine rendering this template.
		 */
		return apply_filters(
			'learndash_template_template_paths',
			$paths,
			$file_name,
			$this->current_rendering_name,
			$this->current_rendering_args,
			$this
		);
	}

	/**
	 * Update a rendering arg in the current template hierarchy so that it cascades down.
	 *
	 * @since 4.16.0
	 *
	 * @param string $arg_name  Argument name.
	 * @param mixed  $arg_value Argument value.
	 *
	 * @return void
	 */
	public function update_arg( string $arg_name, $arg_value ): void {
		$this->current_rendering_args[ $arg_name ] = $arg_value;
	}

	/**
	 * Returns the template paths for admin templates.
	 *
	 * @since 4.9.0
	 *
	 * @param string $file_name Template file name.
	 *
	 * @return array{theme:string[],templates:string[]}
	 */
	protected function get_admin_template_paths( string $file_name ): array {
		$paths = [
			'theme'     => [],
			'templates' => [],
		];

		if ( ! empty( $file_name ) ) {
			$admin_template_dir = LEARNDASH_LMS_PLUGIN_DIR . 'src/admin_views/';
			$file_pathinfo      = pathinfo( $file_name );

			// Normalize path info.

			if ( empty( $file_pathinfo['dirname'] ) ) {
				$file_pathinfo['dirname'] = '';
			}

			if ( empty( $file_pathinfo['extension'] ) ) {
				$file_pathinfo['extension'] = '';
			}

			// Add index suffix to file name.

			$template_file_dir  = ! empty( $file_pathinfo['dirname'] ) && '.' !== $file_pathinfo['dirname']
								? trailingslashit( $file_pathinfo['dirname'] )
								: '';
			$template_file_name = $template_file_dir . $file_pathinfo['filename'] . '.' . $file_pathinfo['extension'];

			if ( ! is_file( $admin_template_dir . $template_file_name ) ) {
				if ( is_dir( $admin_template_dir . $template_file_dir ) ) {
					$template_file_name = $template_file_dir . $file_pathinfo['filename'] . '/index.' . $file_pathinfo['extension'];

					if ( ! is_file( $admin_template_dir . $template_file_name ) ) {
						$template_file_name = '';
					}
				} else {
					$template_file_name = '';
				}
			}

			// Add template file name to paths.

			if ( ! empty( $template_file_name ) ) {
				$paths['templates'][] = $admin_template_dir . $template_file_name;
			}
		}

		/**
		 * Filters the template paths for admin templates.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.9.0
		 *
		 * @param array{theme:string[],templates:string[]} $paths     Template paths.
		 * @param string                                   $file_name Template file name.
		 * @param string                                   $name      Template name.
		 * @param array<string,mixed>                      $args      Template data.
		 * @param Template                                 $instance  Current Instance of template engine rendering this template.
		 */
		return apply_filters(
			'learndash_template_admin_template_paths',
			$paths,
			$file_name,
			$this->current_rendering_name,
			$this->current_rendering_args,
			$this
		);
	}

	/**
	 * Applies the pre HTML filters.
	 *
	 * @since 4.6.0
	 *
	 * @param bool   $echo      Whether to echo the template output or not.
	 * @param string $file_path Template file path.
	 *
	 * @return string
	 */
	private function pre_html_filters( bool $echo, string $file_path ): string {
		/**
		 * Allow users to filter the HTML before rendering.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param string              $html      The initial HTML
		 * @param string              $name      Template name.
		 * @param string              $file_path Complete path to include the PHP File.
		 * @param array<string,mixed> $args      Template data.
		 * @param bool                $echo      Whether to echo the template output or not.
		 * @param Template            $instance  Current Instance of template engine rendering this template.
		 */
		$pre_html = apply_filters(
			'learndash_template_pre_html',
			'',
			$this->current_rendering_name,
			$file_path,
			$this->current_rendering_args,
			$echo,
			$this
		);

		/**
		 * Allow users to filter the HTML by the name before rendering.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * E.g.:
		 *    `learndash_template_pre_html:topic/infobar`
		 *    `learndash_template_pre_html:course/infobar-enrolled`
		 *    `learndash_template_pre_html:shortcodes/profile`
		 *
		 * @since 4.6.0
		 *
		 * @param string              $html      The initial HTML
		 * @param string              $name      Template name.
		 * @param string              $file_path Complete path to include the PHP File.
		 * @param array<string,mixed> $args      Template data.
		 * @param bool                $echo      Whether to echo the template output or not.
		 * @param Template            $instance  Current Instance of template engine rendering this template.
		 */
		return apply_filters(
			"learndash_template_pre_html:{$this->current_rendering_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$pre_html,
			$this->current_rendering_name,
			$file_path,
			$this->current_rendering_args,
			$echo,
			$this
		);
	}

	/**
	 * Applies the args filters.
	 *
	 * @since 4.6.0
	 *
	 * @param bool   $echo      Whether to echo the template output or not.
	 * @param string $file_path Template file path.
	 *
	 * @return void
	 */
	private function args_filters( bool $echo, string $file_path ): void {
		/** This filter is documented in includes/class-ld-lms.php */
		$this->current_rendering_args = apply_filters(
			'ld_template_args_' . $this->current_rendering_name,
			$this->current_rendering_args,
			$file_path,
			$echo
		);

		/**
		 * Filters template arguments.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string,mixed> $args      Template arguments.
		 * @param string              $name      Template name.
		 * @param string              $file_path Template file path.
		 * @param bool                $echo      Whether to echo the template output or not.
		 * @param Template            $instance  Current Instance of template engine rendering this template.
		 *
		 * @return array Template arguments.
		 */
		$this->current_rendering_args = apply_filters(
			'learndash_template_args',
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);

		/**
		 * Filters template arguments.
		 * The dynamic part of the hook refers to the name of the template.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string,mixed> $args      Template arguments.
		 * @param string              $name      Template name.
		 * @param string              $file_path Template file path.
		 * @param bool                $echo      Whether to echo the template output or not.
		 * @param Template            $instance  Current Instance of template engine rendering this template.
		 */
		$this->current_rendering_args = apply_filters(
			"learndash_template_args:{$this->current_rendering_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);
	}

	/**
	 * Applies actions before the template is rendered.
	 *
	 * @since 4.6.0
	 *
	 * @param bool   $echo      Whether to echo the template output or not.
	 * @param string $file_path Template file path.
	 *
	 * @return string
	 */
	private function actions_before_template( bool $echo, string $file_path ): string {
		ob_start();

		/**
		 * Fires an Action before including the template file.
		 *
		 * This action hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string,mixed> $args      Template arguments.
		 * @param string              $name      Template name.
		 * @param string              $file_path Template file path.
		 * @param bool                $echo      Whether to echo the template output or not.
		 * @param Template            $instance  Current Instance of template engine rendering this template.
		 */
		do_action(
			'learndash_template_before_include',
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);

		/**
		 * Fires an Action for a given template name before including the template file.
		 *
		 * This action hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * E.g.:
		 *    `learndash_template_before_include:topic/infobar`
		 *    `learndash_template_before_include:course/infobar-enrolled`
		 *    `learndash_template_before_include:shortcodes/profile`
		 *
		 * @since 4.6.0
		 *
		 * @param array<string,mixed> $args      Template arguments.
		 * @param string              $name      Template name.
		 * @param string              $file_path Template file path.
		 * @param bool                $echo      Whether to echo the template output or not.
		 * @param Template            $instance  Current Instance of template engine rendering this template.
		 */
		do_action(
			"learndash_template_before_include:{$this->current_rendering_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);

		return (string) ob_get_clean();
	}

	/**
	 * Applies actions after the template is rendered.
	 *
	 * @since 4.6.0
	 *
	 * @param bool   $echo      Whether to echo the template output or not.
	 * @param string $file_path Template file path.
	 *
	 * @return string
	 */
	private function actions_after_template( bool $echo, string $file_path ): string {
		ob_start();

		/**
		 * Fires an Action after including the template file.
		 *
		 * This action hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string,mixed> $args      Template arguments.
		 * @param string              $name      Template name.
		 * @param string              $file_path Template file path.
		 * @param bool                $echo      Whether to echo the template output or not.
		 * @param Template            $instance  Current Instance of template engine rendering this template.
		 */
		do_action(
			'learndash_template_after_include',
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);

		/**
		 * Fires an Action for a given template name after including the template file.
		 *
		 * This action hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * E.g.:
		 *    `learndash_template_before_include:topic/infobar`
		 *    `learndash_template_before_include:course/infobar-enrolled`
		 *    `learndash_template_before_include:shortcodes/profile`
		 *
		 * @since 4.6.0
		 *
		 * @param array<string,mixed> $args      Template arguments.
		 * @param string              $name      Template name.
		 * @param string              $file_path Template file path.
		 * @param bool                $echo      Whether to echo the template output or not.
		 * @param Template            $instance  Current Instance of template engine rendering this template.
		 */
		do_action(
			"learndash_template_after_include:{$this->current_rendering_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);

		return (string) ob_get_clean();
	}

	/**
	 * Includes the template file and returns the output.
	 *
	 * @since 4.6.0
	 *
	 * @param bool   $echo      Whether to echo the template output or not.
	 * @param string $file_path Template file path.
	 *
	 * @return string
	 */
	private function template_include( bool $echo, string $file_path ): string {
		ob_start();

		$this->args_filters( $echo, $file_path );

		if ( ! empty( $this->current_rendering_args ) ) {
			extract( $this->current_rendering_args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Maintaining backwards compatibility.
		}

		include $file_path;

		return (string) ob_get_clean();
	}

	/**
	 * Filters the HTML for the before include actions.
	 *
	 * @since 4.6.0
	 *
	 * @param string $before_include_html Before include HTML.
	 * @param bool   $echo                Whether to echo the template output or not.
	 * @param string $file_path           Template file path.
	 *
	 * @return string
	 */
	private function filters_before_include_html( string $before_include_html, bool $echo, string $file_path ): string {
		/**
		 * Allow users to filter the Before include actions.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param string   $html      Template HTML.
		 * @param array    $args      Template arguments.
		 * @param string   $name      Template name.
		 * @param string   $file_path Template file path.
		 * @param bool     $echo      Whether to echo the template output or not.
		 * @param Template $instance  Current Instance of template engine rendering this template.
		 */
		$html = apply_filters(
			'learndash_template_before_include_html',
			$before_include_html,
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);

		/**
		 * Allow users to filter the Before include actions by name.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * E.g.:
		 *    `learndash_template_before_include_html:topic/infobar`
		 *    `learndash_template_before_include_html:course/infobar-enrolled`
		 *    `learndash_template_before_include_html:shortcodes/profile`
		 *
		 * @since 4.6.0
		 *
		 * @param string   $html      Template HTML.
		 * @param array    $args      Template arguments.
		 * @param string   $name      Template name.
		 * @param string   $file_path Template file path.
		 * @param bool     $echo      Whether to echo the template output or not.
		 * @param Template $instance  Current Instance of template engine rendering this template.
		 */
		return apply_filters(
			"learndash_template_before_include_html:{$this->current_rendering_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$html,
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);
	}

	/**
	 * Filters the HTML for the after include actions.
	 *
	 * @since 4.6.0
	 *
	 * @param string $after_include_html After include HTML.
	 * @param bool   $echo               Whether to echo the template output or not.
	 * @param string $file_path          Template file path.
	 *
	 * @return string
	 */
	private function filters_after_include_html( string $after_include_html, bool $echo, string $file_path ): string {
		/**
		 * Allow users to filter the After include actions.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param string   $html      Template HTML.
		 * @param array    $args      Template arguments.
		 * @param string   $name      Template name.
		 * @param string   $file_path Template file path.
		 * @param bool     $echo      Whether to echo the template output or not.
		 * @param Template $instance  Current Instance of template engine rendering this template.
		 */
		$html = apply_filters(
			'learndash_template_after_include_html',
			$after_include_html,
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);

		/**
		 * Allow users to filter the After include actions by name.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * E.g.:
		 *    `learndash_template_after_include_html:topic/infobar`
		 *    `learndash_template_after_include_html:course/infobar-enrolled`
		 *    `learndash_template_after_include_html:shortcodes/profile`
		 *
		 * @since 4.6.0
		 *
		 * @param string   $html      Template HTML.
		 * @param array    $args      Template arguments.
		 * @param string   $name      Template name.
		 * @param string   $file_path Template file path.
		 * @param bool     $echo      Whether to echo the template output or not.
		 * @param Template $instance  Current Instance of template engine rendering this template.
		 */
		return apply_filters(
			"learndash_template_after_include_html:{$this->current_rendering_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$html,
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);
	}

	/**
	 * Filters the HTML for the template include.
	 *
	 * @since 4.6.0
	 *
	 * @param string $include_html Template include HTML.
	 * @param bool   $echo         Whether to echo the template output or not.
	 * @param string $file_path    Template file path.
	 *
	 * @return string
	 */
	private function filters_include_html( string $include_html, bool $echo, string $file_path ): string {
		/**
		 * Allow users to filter the template include HTML.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param string   $html      Template HTML.
		 * @param array    $args      Template arguments.
		 * @param string   $name      Template name.
		 * @param string   $file_path Template file path.
		 * @param bool     $echo      Whether to echo the template output or not.
		 * @param Template $instance  Current Instance of template engine rendering this template.
		 */
		$html = apply_filters(
			'learndash_template_include_html',
			$include_html,
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);

		/**
		 * Allow users to filter the template include HTML by name.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * E.g.:
		 *    `learndash_template_include_html:topic/infobar`
		 *    `learndash_template_include_html:course/infobar-enrolled`
		 *    `learndash_template_include_html:shortcodes/profile`
		 *
		 * @since 4.6.0
		 *
		 * @param string              $html      Template HTML.
		 * @param array<string,mixed> $args      Template arguments.
		 * @param string              $name      Template name.
		 * @param string              $file_path Template file path.
		 * @param bool                $echo      Whether to echo the template output or not.
		 * @param Template            $instance  Current Instance of template engine rendering this template.
		 */
		return apply_filters(
			"learndash_template_include_html:{$this->current_rendering_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$html,
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);
	}

	/**
	 * Filters the final HTML for the template.
	 *
	 * @since 4.6.0
	 *
	 * @param string $final_html Final template HTML.
	 * @param bool   $echo       Whether to echo the template output or not.
	 * @param string $file_path  Template file path.
	 *
	 * @return string
	 */
	private function filters_final_html( string $final_html, bool $echo, string $file_path ): string {
		/**
		 * Allow users to filter the final template HTML.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since 4.6.0
		 *
		 * @param string   $html      Template HTML.
		 * @param array    $args      Template arguments.
		 * @param string   $name      Template name.
		 * @param string   $file_path Template file path.
		 * @param bool     $echo      Whether to echo the template output or not.
		 * @param Template $instance  Current Instance of template engine rendering this template.
		 */
		$html = apply_filters(
			'learndash_template_html',
			$final_html,
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);

		/**
		 * Allow users to filter the final template HTML by name.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * E.g.:
		 *    `learndash_template_final_html:topic/infobar`
		 *    `learndash_template_final_html:course/infobar-enrolled`
		 *    `learndash_template_final_html:shortcodes/profile`
		 *
		 * @since 4.6.0
		 *
		 * @param string   $html      Template HTML.
		 * @param array    $args      Template arguments.
		 * @param string   $name      Template name.
		 * @param string   $file_path Template file path.
		 * @param bool     $echo      Whether to echo the template output or not.
		 * @param Template $instance  Current Instance of template engine rendering this template.
		 */
		return apply_filters(
			"learndash_template_html:{$this->current_rendering_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$html,
			$this->current_rendering_args,
			$this->current_rendering_name,
			$file_path,
			$echo,
			$this
		);
	}

	/**
	 * Process the template including and return the output.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $echo             Whether to echo the template output or not.
	 * @param bool $return_file_path Whether to return the template file path or not.
	 *
	 * @return string
	 */
	private function get_template_output( bool $echo, bool $return_file_path ): string {
		$skip_rendering = $this->skip_rendering( $echo, $return_file_path );

		if ( $skip_rendering ) {
			return '';
		}

		$file_path = $this->get_template_path( $echo, $return_file_path );

		if ( empty( $file_path ) ) {
			return '';
		}

		$pre_html = $this->pre_html_filters( $echo, $file_path );

		if ( ! empty( $pre_html ) ) {
			return $pre_html;
		}

		// Bail if the file doesn't exist.
		if ( ! is_file( $file_path ) ) {
			return '';
		}

		// Template output.

		$before_include_html = $this->actions_before_template( $echo, $file_path );
		$before_include_html = $this->filters_before_include_html( $before_include_html, $echo, $file_path );

		$include_html = $this->template_include( $echo, $file_path );
		$include_html = $this->filters_include_html( $include_html, $echo, $file_path );

		$after_include_html = $this->actions_after_template( $echo, $file_path );
		$after_include_html = $this->filters_after_include_html( $after_include_html, $echo, $file_path );

		$final_html = $before_include_html . $include_html . $after_include_html;
		$final_html = $this->filters_final_html( $final_html, $echo, $file_path );

		// try to add default entry points for the container.
		return $this->maybe_add_container_entry_points( $final_html );
	}

	/**
	 * Tries to add entry points for the HTML container (if it exists).
	 *
	 * A container is defined as the first HTML tag in the template and it is valid if it has the same closing tag at the end of the template.
	 *
	 * Example of a valid template (with a container):
	 *
	 * <div class="container">
	 *  <h1>My Template</h1>
	 * </div>
	 *
	 * In the example above, the container is the `<div>` tag. Then, this code will add the entry points like this:
	 *
	 * <div class="container">
	 *  <after_container_open>
	 *  <h1>My Template</h1>
	 *  <before_container_close>
	 * </div>
	 *
	 * Example of a invalid template (without a container):
	 *
	 * <div class="container">
	 *  <h1>My Template</h1>
	 * </div>
	 * <a href="#">Link</a>
	 *
	 * In the example above, there is no container. So, this code will not add any entry points.
	 *
	 * @since 4.6.0
	 *
	 * @param string $html Template HTML.
	 *
	 * @return string
	 */
	private function maybe_add_container_entry_points( string $html ): string {
		$matches      = $this->get_html_tags_matches( $html );
		$html_matches = $matches[0];

		if ( 0 === count( $html_matches ) ) {
			return $html;
		}

		$html_tags      = $matches['tag'];
		$html_tags_ends = $matches['is_end'];

		// Get first and last tags.
		$first_tag = reset( $html_tags );
		$last_tag  = end( $html_tags );

		// Determine if first last tags are tag ends.
		$first_tag_is_end = '/' === reset( $html_tags_ends );
		$last_tag_is_end  = '/' === end( $html_tags_ends );

		// When first and last tag are not the same, bail.
		if ( $first_tag !== $last_tag ) {
			return $html;
		}

		// If the first tag is a html tag end, bail.
		if ( $first_tag_is_end ) {
			return $html;
		}

		// If the last tag is not and html tag end, bail.
		if ( ! $last_tag_is_end ) {
			return $html;
		}

		$first_tag_html = reset( $html_matches );
		$last_tag_html  = end( $html_matches );

		$open_container_entry_point_html  = $this->get_entry_point_content( 'after_container_open' );
		$close_container_entry_point_html = $this->get_entry_point_content( 'before_container_close' );

		$html = Str::replace_first( $first_tag_html, $first_tag_html . $open_container_entry_point_html, $html );

		return Str::replace_last( $last_tag_html, $close_container_entry_point_html . $last_tag_html, $html );
	}

	/**
	 * Gets all the HTML tags from the html.
	 *
	 * @since 4.6.0
	 *
	 * @param string $html The html of the current template.
	 *
	 * @return array{
	 *  0: array<string>,
	 *  tag: array<string>,
	 *  is_end: array<string>,
	 * } An array of matches from the regular expression.
	 */
	private function get_html_tags_matches( string $html ): array {
		/**
		 * This regular expression is used to match HTML tags in a text string,
		 * capturing the tag name in the "tag" capture group,
		 * and indicating whether it is an opening or closing tag with the "is_end" capture group.
		 */
		$regexp = '/<(?<is_end>\/)*(?<tag>[A-Z0-9]*)(?:\b)*[^>]*>/mi';

		preg_match_all( $regexp, $html, $matches );

		/**
		 * The matches array.
		 *
		 * @var array{
		 *  0: array<string>,
		 *  tag: array<string>,
		 *  is_end: array<string>,
		 * } $matches
		 */
		return $matches;
	}

	/**
	 * Gets the entry point content.
	 *
	 * @since 4.6.0
	 *
	 * @param string              $entry_point_name The name of the entry point.
	 * @param array<string,mixed> $args             The arguments to pass to the entry point action/filter.
	 *
	 * @return string
	 */
	public function get_entry_point_content( string $entry_point_name, array $args = array() ): string {
		ob_start();

		$this->do_entry_point( $entry_point_name, $args );

		return (string) ob_get_clean();
	}

	/**
	 * Runs the entry point hooks and filters.
	 *
	 * @since 4.6.0
	 *
	 * @param string              $entry_point_name The name of the entry point.
	 * @param array<string,mixed> $args             The arguments to pass to the entry point action/filter.
	 *
	 * @return void
	 */
	public function do_entry_point( string $entry_point_name, array $args = array() ): void {
		/**
		 * Filter if the entry points are enabled.
		 *
		 * This filter hook is active exclusively within the new ‘src’ structure.
		 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
		 *
		 * @since @4.6.0
		 *
		 * @param bool                $is_enabled       Is entry_point enabled.
		 * @param string              $template_name    For which template include this entry point belongs.
		 * @param string              $entry_point_name Which entry point specifically we are triggering.
		 * @param array<string,mixed> $args             The arguments to pass to the entry point actions/filters.
		 * @param Template            $instance         Current Instance of template engine rendering this template.
		 */
		$is_entry_point_enabled = apply_filters(
			'learndash_template_entry_point_is_enabled',
			true,
			$this->current_rendering_name,
			$entry_point_name,
			$args,
			$this
		);

		if ( ! $is_entry_point_enabled ) {
			return;
		}

		ob_start();

		if ( has_action( "learndash_template_entry_point:{$this->current_rendering_name}" ) ) {
			/**
			 * Generic entry point action for the current template.
			 *
			 * This action hook is active exclusively within the new ‘src’ structure.
			 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
			 *
			 * @since 4.6.0
			 *
			 * @param string              $template_name    For which template include this entry point belongs.
			 * @param string              $entry_point_name Which entry point specifically we are triggering.
			 * @param array<string,mixed> $args             The arguments to pass to the entry point actions/filters.
			 * @param Template            $instance         Current Instance of template engine rendering this template.
			 */
			do_action(
				"learndash_template_entry_point:{$this->current_rendering_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
				$this->current_rendering_name,
				$entry_point_name,
				$args,
				$this
			);
		}

		if ( has_action( "learndash_template_entry_point:{$this->current_rendering_name}:{$entry_point_name}" ) ) {
			/**
			 * Specific named entry point action called.
			 *
			 * This action hook is active exclusively within the new ‘src’ structure.
			 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
			 *
			 * @since 4.6.0
			 *
			 * @param string              $template_name    For which template include this entry point belongs.
			 * @param string              $entry_point_name Which entry point specifically we are triggering.
			 * @param array<string,mixed> $args             The arguments to pass to the entry point actions/filters.
			 * @param Template            $instance         Current Instance of template engine rendering this template.
			 */
			do_action(
				"learndash_template_entry_point:{$this->current_rendering_name}:{$entry_point_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
				$this->current_rendering_name,
				$entry_point_name,
				$args,
				$this
			);
		}

		$html = (string) ob_get_clean();

		if ( has_filter( "learndash_template_entry_point_html:{$this->current_rendering_name}" ) ) {
			/**
			 * Generic entry point action for the current template.
			 *
			 * This filter hook is active exclusively within the new ‘src’ structure.
			 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
			 *
			 * @since 4.6.0
			 *
			 * @param string              $html             HTML returned for this entry point.
			 * @param string              $template_name    For which template include this entry point belongs.
			 * @param string              $entry_point_name Which entry point specifically we are triggering.
			 * @param array<string,mixed> $args             The arguments to pass to the entry point actions/filters.
			 * @param Template            $instance         Current Instance of template engine rendering this template.
			 */
			$html = apply_filters(
				"learndash_template_entry_point_html:{$this->current_rendering_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
				$html,
				$this->current_rendering_name,
				$entry_point_name,
				$args,
				$this
			);
		}

		if ( has_filter( "learndash_template_entry_point_html:{$this->current_rendering_name}:{$entry_point_name}" ) ) {
			/**
			 * Specific named entry point action called.
			 *
			 * This filter hook is active exclusively within the new ‘src’ structure.
			 * It won’t trigger in ‘LD 30’ and ‘Legacy’ templates, nor in views located within the ‘includes/views’ directory.
			 *
			 * @since 4.6.0
			 *
			 * @param string              $html             HTML returned for this entry point.
			 * @param string              $template_name    For which template include this entry point belongs.
			 * @param string              $entry_point_name Which entry point specifically we are triggering.
			 * @param array<string,mixed> $args             The arguments to pass to the entry point actions/filters.
			 * @param Template            $instance         Current Instance of template engine rendering this template.
			 */
			$html = apply_filters(
				"learndash_template_entry_point_html:{$this->current_rendering_name}:{$entry_point_name}", // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
				$html,
				$this->current_rendering_name,
				$entry_point_name,
				$args,
				$this
			);
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- We need to output the HTML.
	}
}
