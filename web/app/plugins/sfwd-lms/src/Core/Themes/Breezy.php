<?php
/**
 * LearnDash Breezy Theme.
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

namespace LearnDash\Core\Themes;

use Exception;
use LDLMS_Post_Types;
use LearnDash\Core\App;
use LearnDash\Core\Factories\Model_Factory;
use LearnDash\Core\Models\Interfaces\Course_Step;
use LearnDash\Core\Models\Post;
use LearnDash\Core\Shortcodes;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Str;
use LearnDash_Settings_Section;
use LearnDash_Theme_Register;
use WP_Post;

if ( ! class_exists( 'LearnDash_Theme_Register' ) ) {
	return;
}

/**
 * Breezy theme.
 *
 * @since 4.6.0
 */
class Breezy extends LearnDash_Theme_Register {
	/**
	 *
	 * Context for templates.
	 *
	 * @since 4.6.0
	 *
	 * @var array<mixed>
	 */
	protected $context = [];

	/**
	 * Constructor for the class.
	 *
	 * @since 4.6.0
	 *
	 * @throws Exception If the model is not found.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->theme_key          = 'breezy';
		$this->theme_name         = esc_html__( 'Breezy', 'learndash' );
		$this->theme_base_dir     = trailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . 'src/assets/themes/' . $this->theme_key;
		$this->theme_base_url     = trailingslashit( LEARNDASH_LMS_PLUGIN_URL ) . 'src/assets/themes/' . $this->theme_key;
		$this->theme_template_dir = trailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . 'src/views/themes/' . $this->theme_key;
		$this->theme_template_url = trailingslashit( LEARNDASH_LMS_PLUGIN_URL ) . 'src/views/themes/' . $this->theme_key;

		parent::__construct();
	}

	/**
	 * Load the theme files and assets.
	 *
	 * @since 4.6.0
	 *
	 * @throws Exception If the model is not found.
	 *
	 * @return void
	 */
	public function load_theme() {
		if ( $this->theme_key !== LearnDash_Theme_Register::get_active_theme_key() ) {
			return;
		}

		App::register( Shortcodes\Provider::class );

		// TODO: Review those hooks.

		add_filter( 'template_include', array( $this, 'replace_template_with_focus' ), 99 );
		add_filter( 'learndash_template_args', array( $this, 'add_jit_template_arguments' ), 9, 2 );
		add_filter( 'learndash_wrapper_class', array( $this, 'get_wrapper_class_name' ), 10, 3 );
		add_filter( 'learndash_payment_button_classes', array( $this, 'get_payment_button_class_name' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Shows the focus template when the focus mode is enabled and the current post type supports it.
	 *
	 * @since 4.6.0
	 *
	 * @param string $template_name Template name.
	 *
	 * @return string
	 */
	public function replace_template_with_focus( string $template_name ): string {
		if (
			'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' )
			&& in_array(
				get_post_type(),
				LDLMS_Post_Types::get_post_type_slug(
					array(
						LDLMS_Post_Types::LESSON,
						LDLMS_Post_Types::TOPIC,
						LDLMS_Post_Types::QUIZ,
						LDLMS_Post_Types::ASSIGNMENT,
					)
				),
				true
			)
			&& is_singular( (string) get_post_type() )
		) {
			Template::show_template( 'focus', $this->context );

			return '';
		}

		return $template_name;
	}

	/**
	 * Add JIT template variables.
	 *
	 * @since 4.6.0
	 *
	 * @param array<string,mixed> $args          Template arguments.
	 * @param string              $template_name Template name.
	 *
	 * @return array<string,mixed>
	 */
	public function add_jit_template_arguments( array $args, string $template_name ): array {
		// TODO: Remove it later.

		global $post;

		if ( ! is_singular() ) {
			return $args;
		}

		$args['user'] = wp_get_current_user();

		if (
			Str::starts_with(
				$template_name,
				array( 'group', 'lesson', 'topic', 'quiz', 'exam', 'focus', 'components' )
			)
		) {
			if ( ! array_key_exists( 'model', $args ) ) {
				try {
					$args['model'] = Model_Factory::create( $post );
				} catch ( Exception $e ) {
					return $args;
				}

				$args['model']->enable_memoization();
			}
		}

		if ( ! isset( $args['model'] ) || ! $args['model'] instanceof Post ) {
			return $args;
		}

		switch ( $template_name ) {
			case 'focus/comments':
				$args['approved_comments_number'] = intval(
					wp_count_comments( $args['model']->get_id() )->approved ?? 0
				);
				break;

			case 'focus/masthead-menu-items':
				$args['menu_items'] = array(); // TODO: Implement it.
				break;

			case 'components/enrollment-button':
				$args['payment_button'] = learndash_payment_buttons( $args['model']->get_post() );

				/** This filter is documented in themes/ld30/templates/modules/infobar/course.php */
				$args['show_login_button'] = ! is_user_logged_in() && apply_filters(
					'learndash_login_modal',
					true,
					$args['model']->get_id(),
					$args['user']->ID
				);

				// TODO: We need to remove LD30 dependency when we decide about settings.
				/** This filter is documented in themes/ld30/includes/shortcode.php */
				$args['login_url'] = apply_filters(
					'learndash_login_url',
					'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' )
						? '#login'
						: wp_login_url( (string) get_permalink() )
				);
				break;

			case 'group/progress':
				$progress = learndash_get_user_group_progress( $args['model']->get_id(), $args['user']->ID );

				$args['steps_completed_number'] = $progress['completed'];
				$args['steps_total_number']     = $progress['total'];
				$args['progress_percentage']    = $progress['percentage'];
				break;

			case 'topic/progress-bar':
			case 'topic/progress-stats':
			case 'lesson/progress-bar':
			case 'lesson/progress-stats':
				if ( ! is_subclass_of( $args['model'], Course_Step::class ) ) {
					return $args;
				}

				if ( $args['model']->get_course() ) {
					// TODO: Move to the lesson model (like in a course).
					$progress = learndash_lesson_progress( $args['model']->get_post(), $args['model']->get_course()->get_id() );

					$percentage = $progress['percentage'];
				} else {
					$percentage = 0;
				}

				$args['percentage'] = $percentage;
				break;

			default:
				break;
		}

		return $args;
	}

	/**
	 * Returns wrapper (container) class name.
	 *
	 * @since 4.6.0
	 *
	 * @param string $classes            Classes.
	 * @param mixed  $post               Post.
	 * @param string $additional_classes Additional classes.
	 *
	 * @return string
	 */
	public function get_wrapper_class_name( string $classes, $post, string $additional_classes ): string {
		$breezy_classes = 'ld-layout';

		if ( $post instanceof WP_Post ) {
			$post_type_key = LDLMS_Post_Types::get_post_type_key( $post->post_type );

			if ( ! empty( $post_type_key ) ) {
				$breezy_classes .= ' ld-layout--' . $post_type_key;
			}
		}

		return "$breezy_classes $additional_classes";
	}

	/**
	 * Returns payment button class name.
	 *
	 * @since 4.6.0
	 *
	 * @param string $classes Classes.
	 *
	 * @return string
	 */
	public function get_payment_button_class_name( string $classes ): string {
		return $classes . ' ld-button ld-button--lg ld-button--primary';
	}

	/**
	 * Enqueues assets.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$handler_suffix = is_rtl() ? '-rtl' : '';
		$handler        = 'learndash-' . $this->theme_key . $handler_suffix;
		$dist_url       = $this->theme_base_url . '/dist/';

		// Scripts.

		wp_enqueue_script(
			$handler,
			$dist_url . 'js/scripts.js',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN,
			true
		);

		$localize_data = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		];

		/**
		 * Filters the scripts localization data.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string,mixed> $localize_data Localize data for the scripts.
		 */
		$localize_data = apply_filters( 'learndash_breezy_localize_script_data', $localize_data );

		wp_localize_script( $handler, 'learndash' . ucfirst( $this->theme_key ), $localize_data );

		// Styles.

		wp_register_style(
			$handler,
			$dist_url . 'css/styles.css',
			array(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		wp_enqueue_style( $handler );
	}
}
