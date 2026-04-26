<?php
/**
 * The following class Handles Site Health data.
 *
 * @since 4.4.1
 *
 * @package LearnDash\GDPR
 */

use LearnDash\Core\Themes\LD30\Presenter_Mode\Settings as Presenter_Mode_Settings;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway as Paypal_Checkout_Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Site_Health' ) ) {
	/**
	 * Handles Site Health data.
	 *
	 * @since 4.4.1
	 */
	class Learndash_Site_Health {
		private const SITE_HEALTH_KEY = 'learndash';

		/**
		 * Instance of this class.
		 *
		 * @since 4.4.1
		 *
		 * @var self|null
		 */
		private static $instance = null;

		/**
		 * Fields.
		 *
		 * @var array[]
		 */
		private $fields = [];

		/**
		 * Constructor.
		 *
		 * @since 4.4.1
		 *
		 * @return void
		 */
		public function __construct() {
			add_filter( 'debug_information', [ $this, 'add_site_health_info' ] );
		}

		/**
		 * Initialize the class.
		 *
		 * @since 4.4.1
		 *
		 * @return void
		 */
		public static function init(): void {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
		}

		/**
		 * Add Telemetry info to Site Health.
		 *
		 * @since 4.4.1
		 *
		 * @param array $debug_info Info.
		 *
		 * @return array Debug info.
		 */
		public function add_site_health_info( array $debug_info ): array {
			$debug_info[ self::SITE_HEALTH_KEY ] = [
				'label'  => __( 'LearnDash', 'learndash' ),
				'fields' => $this->get_fields(),
			];

			return $debug_info;
		}

		/**
		 * Maps the telemetry data to the Site Health fields.
		 *
		 * @since 4.4.1
		 *
		 * @return array
		 */
		private function get_fields(): array {
			if ( ! empty( $this->fields ) ) {
				return $this->fields;
			}

			$this->fields = array_merge(
				$this->map_general_fields(),
				$this->map_post_count_fields(),
				$this->map_settings_fields(),
				$this->map_statistics_fields(),
				$this->map_constant_fields()
			);

			return $this->fields;
		}

		/**
		 * Maps general fields.
		 *
		 * @since 4.4.1
		 *
		 * @return array
		 */
		private function map_general_fields(): array {
			$license_is_valid = learndash_is_learndash_license_valid();
			$last_updated     = array_keys( learndash_data_upgrades_setting( 'version_history' ) )[0] ?? 0;

			return [
				'version'           => [
					'label' => __( 'Version', 'learndash' ),
					'value' => LEARNDASH_VERSION,
				],
				'last_updated'      => [
					'label' => __( 'Last updated', 'learndash' ),
					'value' => $last_updated > 0 ? learndash_adjust_date_time_display( $last_updated ) : __( 'Never', 'learndash' ),
					'debug' => $last_updated,
				],
				'previous_version'  => [
					'label' => __( 'Previous version', 'learndash' ),
					'value' => learndash_data_upgrades_setting( 'prior_version' ),
				],
				'license_validated' => [
					'label' => __( 'License validated', 'learndash' ),
					'value' => $this->bool_to_yes_no_string( $license_is_valid ),
					'debug' => $license_is_valid,
				],
			];
		}

		/**
		 * Maps fields with post counts.
		 *
		 * @since 4.4.1
		 *
		 * @return array[]
		 */
		private function map_post_count_fields(): array {
			$courses_label      = LearnDash_Custom_Label::label_to_lower( 'courses' );
			$groups_label       = LearnDash_Custom_Label::label_to_lower( 'groups' );
			$lessons_label      = LearnDash_Custom_Label::label_to_lower( 'lessons' );
			$topics_label       = LearnDash_Custom_Label::label_to_lower( 'topics' );
			$quizzes_label      = LearnDash_Custom_Label::label_to_lower( 'quizzes' );
			$questions_label    = LearnDash_Custom_Label::label_to_lower( 'questions' );
			$exams_label        = LearnDash_Custom_Label::label_to_lower( 'exams' );
			$certificates_label = LearnDash_Custom_Label::label_to_lower( 'certificates' );

			return [
				'course_count'      => [
					'label' => sprintf(
						/* translators: %1$s = Courses custom label */
						__( 'Number of %1$s', 'learndash' ),
						$courses_label
					),
					'value' => $this->count_by_post_type( LDLMS_Post_Types::COURSE ),
				],
				'group_count'       => [
					'label' => sprintf(
						/* translators: %1$s = Groups custom label */
						__( 'Number of %1$s', 'learndash' ),
						$groups_label
					),
					'value' => $this->count_by_post_type( LDLMS_Post_Types::GROUP ),
				],
				'lesson_count'      => [
					'label' => sprintf(
						/* translators: %1$s = Lessons custom label */
						__( 'Number of %1$s', 'learndash' ),
						$lessons_label
					),
					'value' => $this->count_by_post_type( LDLMS_Post_Types::LESSON ),
				],
				'topic_count'       => [
					'label' => sprintf(
						/* translators: %1$s = Topics custom label */
						__( 'Number of %1$s', 'learndash' ),
						$topics_label
					),
					'value' => $this->count_by_post_type( LDLMS_Post_Types::TOPIC ),
				],
				'quiz_count'        => [
					'label' => sprintf(
						/* translators: %1$s = Quizzes custom label */
						__( 'Number of %1$s', 'learndash' ),
						$quizzes_label
					),
					'value' => $this->count_by_post_type( LDLMS_Post_Types::QUIZ ),
				],
				'question_count'    => [
					'label' => sprintf(
						/* translators: %1$s = Questions custom label */
						__( 'Number of %1$s', 'learndash' ),
						$questions_label
					),
					'value' => $this->count_by_post_type( LDLMS_Post_Types::QUESTION ),
				],
				'exam_count'        => [
					'label' => sprintf(
						/* translators: %1$s = Challenge Exams custom label */
						__( 'Number of %1$s', 'learndash' ),
						$exams_label
					),
					'value' => $this->count_by_post_type( LDLMS_Post_Types::EXAM ),
				],
				'certificate_count' => [
					'label' => sprintf(
						/* translators: %1$s = Certificates custom label */
						__( 'Number of %1$s', 'learndash' ),
						$certificates_label
					),
					'value' => $this->count_by_post_type( LDLMS_Post_Types::CERTIFICATE ),
				],
			];
		}

		/**
		 * Maps settings fields.
		 *
		 * @since 4.4.1
		 *
		 * @return array
		 */
		private function map_settings_fields(): array {
			// Settings.

			$shared_steps_enabled        = learndash_is_course_shared_steps_enabled();
			$focus_mode_enabled          = $this->focus_mode_is_enabled();
			$is_rtl                      = is_rtl();
			$registration_page_set       = learndash_registration_page_is_set();
			$currency_code               = learndash_get_currency_code();
			$nested_urls_enabled         = 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' );
			$modern_course_enabled       = LearnDash_Settings_Section_General_Appearance::get_setting( 'course_enabled' ) === 'yes';
			$modern_group_enabled        = LearnDash_Settings_Section_General_Appearance::get_setting( 'group_enabled' ) === 'yes';
			$modern_registration_enabled = LearnDash_Settings_Section_General_Appearance::get_setting( 'registration_enabled' ) === 'yes';

			// Gateways.

			$paypal_gateway    = Learndash_Paypal_IPN_Gateway::get_initiated_instance();
			$paypal_configured = $paypal_gateway && $paypal_gateway->is_ready();

			$stripe_gateway    = Learndash_Stripe_Gateway::get_initiated_instance();
			$stripe_configured = $stripe_gateway && $stripe_gateway->is_ready();

			$razorpay_gateway    = Learndash_Razorpay_Gateway::get_initiated_instance();
			$razorpay_configured = $razorpay_gateway && $razorpay_gateway->is_ready();

			$paypal_checkout_gateway    = Paypal_Checkout_Gateway::get_initiated_instance();
			$paypal_checkout_configured = $paypal_checkout_gateway && $paypal_checkout_gateway->is_ready();

			// Labels.

			$course_label = LearnDash_Custom_Label::get_label( 'course' );
			$group_label  = LearnDash_Custom_Label::get_label( 'group' );

			$presenter_mode_settings = Presenter_Mode_Settings::get();

			return [
				'shared_course_steps'             => [
					'label' => sprintf(
						/* translators: %1$s = Course custom label */
						__( 'Shared %1$s steps', 'learndash' ),
						LearnDash_Custom_Label::label_to_lower( 'course' )
					),
					'value' => $this->bool_to_on_off_string( $shared_steps_enabled ),
					'debug' => $shared_steps_enabled,
				],
				'active_template'                 => [
					'label' => __( 'Active template', 'learndash' ),
					'value' => esc_html( LearnDash_Theme_Register::get_active_theme_name() ),
					'debug' => esc_attr( LearnDash_Theme_Register::get_active_theme_key() ),
				],
				'focus_mode'                      => [
					'label' => __( 'Focus mode', 'learndash' ),
					'value' => $this->bool_to_on_off_string( $focus_mode_enabled ),
					'debug' => $focus_mode_enabled,
				],
				'rtl'                             => [
					'label' => __( 'RTL', 'learndash' ),
					'value' => $this->bool_to_yes_no_string( $is_rtl ),
					'debug' => $is_rtl,
				],
				'registration_page'               => [
					'label' => __( 'Registration page', 'learndash' ),
					'value' => $this->bool_to_on_off_string( $registration_page_set ),
					'debug' => $registration_page_set,
				],
				'currency'                        => [
					'label' => __( 'Currency', 'learndash' ),
					'value' => $currency_code ? $currency_code : __( 'Not set', 'learndash' ),
					'debug' => $currency_code,
				],
				'nested_urls'                     => [
					'label' => __( 'Nested URLs', 'learndash' ),
					'value' => $this->bool_to_on_off_string( $nested_urls_enabled ),
					'debug' => $nested_urls_enabled,
				],
				'payment_gateway_paypal_ipn'      => [
					'label' => __( 'PayPal Standard configured', 'learndash' ),
					'value' => $this->bool_to_yes_no_string( $paypal_configured ),
					'debug' => $paypal_configured,
				],
				'payment_gateway_paypal_checkout' => [
					'label' => __( 'PayPal Checkout configured', 'learndash' ),
					'value' => $this->bool_to_yes_no_string( $paypal_checkout_configured ),
					'debug' => $paypal_checkout_configured,
				],
				'payment_gateway_stripe_connect'  => [
					'label' => __( 'Stripe Connect configured', 'learndash' ),
					'value' => $this->bool_to_yes_no_string( $stripe_configured ),
					'debug' => $stripe_configured,
				],
				'payment_gateway_razorpay'        => [
					'label' => __( 'Razorpay configured', 'learndash' ),
					'value' => $this->bool_to_yes_no_string( $razorpay_configured ),
					'debug' => $razorpay_configured,
				],
				'modern_registration_enabled'     => [
					'label' => __( 'Modern Registration Page', 'learndash' ),
					'value' => $this->bool_to_on_off_string( $modern_registration_enabled ),
					'debug' => $modern_registration_enabled,
				],
				'modern_course_enabled'           => [
					'label' => sprintf(
						/* translators: %1$s = Course custom label */
						__( 'Modern %1$s Page', 'learndash' ),
						$course_label
					),
					'value' => $this->bool_to_on_off_string( $modern_course_enabled ),
					'debug' => $modern_course_enabled,
				],
				'presenter_mode_enabled'          => [
					'label' => __( 'Presenter Mode', 'learndash' ),
					'value' => $this->bool_to_on_off_string( (bool) $presenter_mode_settings['presenter_mode_enabled'] ),
					'debug' => $presenter_mode_settings['presenter_mode_enabled'],
				],
				'presenter_mode_icon_position'    => [
					'label' => __( 'Presenter Mode Icon Position', 'learndash' ),
					'value' => $presenter_mode_settings['presenter_mode_icon_position'],
					'debug' => $presenter_mode_settings['presenter_mode_icon_position'],
				],
				'modern_group_enabled'            => [
					'label' => sprintf(
						/* translators: %1$s = Group custom label */
						__( 'Modern %1$s Page', 'learndash' ),
						$group_label
					),
					'value' => $this->bool_to_on_off_string( $modern_group_enabled ),
					'debug' => $modern_group_enabled,
				],
			];
		}

		/**
		 * Maps statistics fields.
		 *
		 * @since 4.4.1
		 *
		 * @return array
		 */
		private function map_statistics_fields(): array {
			$courses_label  = LearnDash_Custom_Label::label_to_lower( 'courses' );
			$groups_label   = LearnDash_Custom_Label::label_to_lower( 'groups' );
			$lessons_label  = LearnDash_Custom_Label::label_to_lower( 'lessons' );
			$topics_label   = LearnDash_Custom_Label::label_to_lower( 'topics' );
			$quizzes_label  = LearnDash_Custom_Label::label_to_lower( 'quizzes' );
			$question_label = LearnDash_Custom_Label::label_to_lower( 'question' );

			return [
				'course_using_free_form_progression_count' => [
					'label' => sprintf(
						/* translators: %1$s = Courses custom label */
						__( 'Number of %1$s using free form progression', 'learndash' ),
						$courses_label
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::COURSE, 'course_disable_lesson_progression', 'on' ),
				],
				'course_using_linear_progression_count'    => [
					'label' => sprintf(
						/* translators: %1$s = Courses custom label */
						__( 'Number of %1$s using linear progression', 'learndash' ),
						$courses_label
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::COURSE, 'course_disable_lesson_progression' ),
				],
				'course_open_count'                        => [
					'label' => sprintf(
						/* translators: %1$s = Courses custom label */
						__( 'Number of %1$s with open enrollment', 'learndash' ),
						$courses_label
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::COURSE, 'course_price_type', LEARNDASH_PRICE_TYPE_OPEN ),
				],
				'course_free_count'                        => [
					'label' => sprintf(
						/* translators: %1$s = Courses custom label */
						__( 'Number of %1$s with free enrollment', 'learndash' ),
						$courses_label
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::COURSE, 'course_price_type', LEARNDASH_PRICE_TYPE_FREE ),
				],
				'course_buynow_count'                      => [
					'label' => sprintf(
						/* translators: %1$s = Courses custom label */
						__( 'Number of %1$s with buy now enrollment', 'learndash' ),
						$courses_label
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::COURSE, 'course_price_type', LEARNDASH_PRICE_TYPE_PAYNOW ),
				],
				'course_recurring_count'                   => [
					'label' => sprintf(
						/* translators: %1$s = Courses custom label */
						__( 'Number of %1$s with recurring enrollment', 'learndash' ),
						$courses_label
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::COURSE, 'course_price_type', LEARNDASH_PRICE_TYPE_SUBSCRIBE ),
				],
				'course_closed_count'                      => [
					'label' => sprintf(
						/* translators: %1$s = Courses custom label */
						__( 'Number of %1$s with closed enrollment', 'learndash' ),
						$courses_label
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::COURSE, 'course_price_type', LEARNDASH_PRICE_TYPE_CLOSED ),
				],
				'group_free_count'                         => [
					'label' => sprintf(
						/* translators: %1$s = Group custom label */
						__( 'Number of %1$s with free enrollment', 'learndash' ),
						$groups_label
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::GROUP, 'group_price_type', LEARNDASH_PRICE_TYPE_FREE ),
				],
				'group_buynow_count'                       => [
					'label' => sprintf(
						/* translators: %1$s = Group custom label */
						__( 'Number of %1$s with buy now enrollment', 'learndash' ),
						$groups_label,
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::GROUP, 'group_price_type', LEARNDASH_PRICE_TYPE_PAYNOW ),
				],
				'group_recurring_count'                    => [
					'label' => sprintf(
						/* translators: %1$s = Group custom label */
						__( 'Number of %1$s with recurring enrollment', 'learndash' ),
						$groups_label,
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::GROUP, 'group_price_type', LEARNDASH_PRICE_TYPE_SUBSCRIBE ),
				],
				'group_closed_count'                       => [
					'label' => sprintf(
						/* translators: %1$s = Group custom label */
						__( 'Number of %1$s with closed enrollment', 'learndash' ),
						$groups_label,
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::GROUP, 'group_price_type', LEARNDASH_PRICE_TYPE_CLOSED ),
				],
				'lesson_using_video_progression_count'     => [
					'label' => sprintf(
						/* translators: %1$s = Lessons custom label */
						__( 'Number of %1$s using video progression', 'learndash' ),
						$lessons_label,
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::LESSON, 'lesson_video_enabled', 'on' ),
				],
				'lesson_using_drip_content_count'          => [
					'label' => sprintf(
						/* translators: %1$s = Lessons custom label */
						__( 'Number of %1$s using drip content', 'learndash' ),
						$lessons_label
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::LESSON, 'lesson_schedule', '[a-z_]+' ),
				],
				'topic_using_drip_content_count'           => [
					'label' => sprintf(
						/* translators: %1$s = Topics custom label */
						__( 'Number of %1$s using drip content', 'learndash' ),
						$topics_label
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::TOPIC, 'lesson_schedule', '[a-z_]+' ),
				],
				'quiz_using_randomized_question_ordering_count' => [
					'label' => sprintf(
						/* translators: %1$s = Quizzes custom label, %2$s = Question custom label */
						__( 'Number of %1$s using randomized %2$s ordering', 'learndash' ),
						$quizzes_label,
						$question_label,
					),
					'value' => $this->count_by_post_type_and_setting( LDLMS_Post_Types::QUIZ, 'questionRandom', '1' ),
				],
				'enrolled_user_count'                      => [
					'label' => __( 'Number of enrolled users', 'learndash' ),
					'value' => $this->get_enrolled_user_count(),
				],
			];
		}

		/**
		 * Maps fields with LD constants.
		 *
		 * @since 4.4.1
		 *
		 * @return array
		 */
		private function map_constant_fields(): array {
			$learndash_debug        = defined( 'LEARNDASH_DEBUG' ) && LEARNDASH_DEBUG; // @phpstan-ignore-line -- Constant can be true/false.
			$learndash_script_debug = defined( 'LEARNDASH_SCRIPT_DEBUG' ) && LEARNDASH_SCRIPT_DEBUG; // @phpstan-ignore-line -- Constant can be true/false.

			return [
				'LEARNDASH_DEBUG'        => [
					'label' => __( 'LEARNDASH_DEBUG', 'learndash' ),
					'value' => $this->bool_to_yes_no_string( $learndash_debug ),
					'debug' => $learndash_debug,
				],
				'LEARNDASH_SCRIPT_DEBUG' => [
					'label' => __( 'LEARNDASH_SCRIPT_DEBUG', 'learndash' ),
					'value' => $this->bool_to_yes_no_string( $learndash_script_debug ),
					'debug' => $learndash_script_debug,
				],
			];
		}

		/**
		 * Returns a number of published posts by a post type.
		 *
		 * @since 4.4.1
		 *
		 * @param string $post_type_key Post type key.
		 *
		 * @return int
		 */
		protected function count_by_post_type( string $post_type_key ): int {
			$post_type = LDLMS_Post_Types::get_post_type_slug( $post_type_key );

			return wp_count_posts( $post_type )->publish;
		}

		/**
		 * Returns a number of published posts filtered by a post type and a setting.
		 *
		 * @since 4.4.1
		 *
		 * @param string $post_type_key Post type key.
		 * @param string $setting_key   Setting key to filter by.
		 * @param string $setting_value Optional. Setting value to filter by. Can be a regular expression. Default empty string.
		 *
		 * @return int
		 */
		protected function count_by_post_type_and_setting( string $post_type_key, string $setting_key, string $setting_value = '' ): int {
			$query_args = [
				'post_type'    => LDLMS_Post_Types::get_post_type_slug( $post_type_key ),
				'post_status'  => 'publish',
				'fields'       => 'ids',
				'nopaging'     => true,
				'meta_key'     => '_' . LDLMS_Post_Types::get_post_type_slug( $post_type_key ),
				'meta_value'   => $this->map_meta_value_from_setting( $setting_key, $setting_value ),
				'meta_compare' => 'RLIKE',
			];

			$query = new WP_Query( $query_args );

			return $query->found_posts;
		}

		/**
		 * Returns a number of enrolled users.
		 *
		 * @since 4.4.1
		 *
		 * @return int
		 */
		protected function get_enrolled_user_count(): int {
			// Get course IDs for courses with a price type "open".
			$open_course_query = new WP_Query(
				[
					'post_type'   => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ),
					'post_status' => 'publish',
					'fields'      => 'ids',
					'meta_key'    => '_ld_price_type',
					'meta_value'  => 'open',
				]
			);

			// If there is at least one open course, just return the number of users without an administrator.
			if ( $open_course_query->found_posts > 0 ) {
				return learndash_students_enrolled_count();
			}

			// Get course IDs for all courses, except with a price type "open".
			$not_open_course_query = new WP_Query(
				[
					'post_type'    => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ),
					'post_status'  => 'publish',
					'fields'       => 'ids',
					'nopaging'     => true,
					'meta_key'     => '_ld_price_type',
					'meta_value'   => 'open',
					'meta_compare' => '!=',
				]
			);

			if ( 0 === $not_open_course_query->found_posts ) {
				return 0;
			}

			global $wpdb;

			return (int) $wpdb->get_var(
				// The 1st select is: user ids that were directly enrolled.
				// The 2nd select is: user ids that were "enrolled" via group -> course access.
				$wpdb->prepare(
					"
						SELECT count(user_id) FROM (
							SELECT user_id FROM $wpdb->usermeta WHERE meta_key LIKE %s
							UNION
							SELECT user_id FROM $wpdb->usermeta WHERE meta_key LIKE %s AND meta_value in (
								SELECT DISTINCT REPLACE( meta_key, %s, '' ) FROM $wpdb->postmeta WHERE meta_key LIKE %s
							)
						) as enrolled_users;
					",
					$wpdb->esc_like( 'course_' ) . '%' . $wpdb->esc_like( '_access_from' ),
					$wpdb->esc_like( 'learndash_group_users_' ) . '%',
					'learndash_group_enrolled_',
					$wpdb->esc_like( 'learndash_group_enrolled_' ) . '%'
				)
			);
		}

		/**
		 * Converts a boolean value to the "On" or "Off" string.
		 *
		 * @since 4.4.1
		 *
		 * @param bool $value Value.
		 *
		 * @return string
		 */
		protected function bool_to_on_off_string( bool $value ): string {
			return $value ? __( 'On', 'learndash' ) : __( 'Off', 'learndash' );
		}

		/**
		 * Converts a boolean value to the "Yes" or "No" string.
		 *
		 * @since 4.4.1
		 *
		 * @param bool $value Value.
		 *
		 * @return string
		 */
		protected function bool_to_yes_no_string( bool $value ): string {
			return $value ? __( 'Yes', 'learndash' ) : __( 'No', 'learndash' );
		}

		/**
		 * Returns true if the focus mode is enabled.
		 *
		 * @since 4.4.1
		 *
		 * @return bool
		 */
		private function focus_mode_is_enabled(): bool {
			if ( learndash_is_active_theme( 'legacy' ) ) {
				return false;
			}

			return 'yes' === LearnDash_Settings_Section::get_section_setting(
				'LearnDash_Settings_Theme_LD30',
				'focus_mode_enabled'
			);
		}

		/**
		 * Maps the regexp meta value from the setting key and value.
		 *
		 * @since 4.4.1
		 *
		 * @param string $setting_key   Setting key.
		 * @param string $setting_value Setting value.
		 *
		 * @return string
		 */
		protected function map_meta_value_from_setting( string $setting_key, string $setting_value ): string {
			// Regular expressions are explained in includes/admin/classes-filters/class-learndash-admin-filter-meta.php.
			$regexp_part = '' !== $setting_value
				? '";.:[^;]*:?"?' . $setting_value . '"?;'
				: '";.:[^;]*:?"";';

			return $setting_key . $regexp_part;
		}
	}
}
