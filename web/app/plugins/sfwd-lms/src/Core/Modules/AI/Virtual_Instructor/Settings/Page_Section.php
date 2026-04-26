<?php
/**
 * Virtual Instructor global settings section.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor\Settings;

use LDLMS_Post_Types;
use LearnDash_Custom_Label;
use LearnDash_Settings_Section;

/**
 * Virtual Instructor global settings section.
 *
 * @since 4.13.0
 */
class Page_Section extends LearnDash_Settings_Section {
	/**
	 * Banned words matching key for "exact" option.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	const BANNED_WORDS_MATCHING_KEY_EXACT = 'exact';

	/**
	 * Banned words matching key for "any" option.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	const BANNED_WORDS_MATCHING_KEY_ANY = 'any';

	/**
	 * Post type slug.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Default option values.
	 *
	 * @since 4.13.0
	 * @since 5.0.0 Updated to protected.
	 *
	 * @var array<string, mixed>
	 */
	protected $default_values = [];

	/**
	 * Constructor.
	 *
	 * @since 4.13.0
	 */
	public function __construct() {
		$this->post_type = learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR );

		$this->settings_screen_id = $this->post_type . '_page_virtual-instructors-settings';

		$this->settings_page_id = 'virtual-instructors-settings';

		$this->setting_option_key = 'learndash_settings_virtual_instructors';

		$this->setting_field_prefix = 'learndash_settings_virtual_instructors';

		$this->settings_section_key = 'virtual_instructors';

		$this->settings_section_label = __( 'Global Settings', 'learndash' );

		$this->settings_section_description = sprintf(
			// translators: placeholder: virtual instructor.
			esc_html_x( 'Some settings can be overridden by individual %s settings.', 'placeholder: virtual instructor', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'virtual_instructor' )
		);

		$this->default_values = [
			'banned_words'          => '',
			'banned_words_matching' => self::BANNED_WORDS_MATCHING_KEY_ANY,
			'error_message'         => __( 'Oops! We can’t help you with that question. Please ask your instructor.', 'learndash' ),
		];

		parent::__construct();
	}

	/**
	 * Initialize the metabox settings values.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function load_settings_values(): void {
		parent::load_settings_values();

		// Set default values.

		foreach ( $this->default_values as $field_key => $field_args ) {
			if ( isset( $this->setting_option_values[ $field_key ] ) ) {
				continue;
			}

			$this->setting_option_values[ $field_key ] = $this->default_values[ $field_key ];
		}
	}

	/**
	 * Initialize the metabox settings fields.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function load_settings_fields(): void {
		$this->setting_option_fields = [
			'banned_words'          => [
				'name'      => 'banned_words',
				'type'      => 'textarea',
				'label'     => __( 'Banned Words', 'learndash' ),
				'help_text' => __( 'Banned words separated by comma. When students use these words, an error message will be returned.', 'learndash' ),
				'value'     => $this->setting_option_values['banned_words'],
			],
			'banned_words_matching' => [
				'name'      => 'banned_words_matching',
				'type'      => 'select',
				'label'     => __( 'Banned Words Matching', 'learndash' ),
				'help_text' => __( 'Configure how banned word detection should work. Either on any of your banned words being detected or an exact keyphrase matching.', 'learndash' ),
				'options'   => [
					self::BANNED_WORDS_MATCHING_KEY_ANY   => __( 'Any', 'learndash' ),
					self::BANNED_WORDS_MATCHING_KEY_EXACT => __( 'Exact', 'learndash' ),
				],
				'value'     => $this->setting_option_values['banned_words_matching'],
			],
			'error_message'         => [
				'name'      => 'error_message',
				'type'      => 'textarea',
				'label'     => __( 'Error Message', 'learndash' ),
				'help_text' => sprintf(
					// translators: placeholder: virtual instructor.
					esc_html_x( 'Set a message that is returned to your students when they try and use banned words with the %s.', 'placeholder: virtual instructor', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'virtual_instructor' )
				),
				'value'     => $this->setting_option_values['error_message'],
			],
		];

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

		parent::load_settings_fields();
	}
}
