<?php
/**
 * Alert_Form_Fields class.
 *
 * @package wp-job-manager-alerts
 */

namespace WP_Job_Manager_Alerts;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Helpers to render fields for add/edit alert form.
 *
 * @since 3.0.0
 */
class Alert_Form_Fields {

	/**
	 * The active alert form fields.
	 *
	 * @var array
	 */
	private array $active_fields;

	/**
	 * Get all default alert form fields.
	 *
	 * @return array
	 */
	public static function get_default_fields() {
		/**
		 * Filter the default alert form fields.
		 */
		return apply_filters(
			'job_manager_alerts_form_fields',
			[
				'keywords'            => __( 'Keywords', 'wp-job-manager-alerts' ),
				'location'            => __( 'Location', 'wp-job-manager-alerts' ),
				'categories'          => __( 'Categories', 'wp-job-manager-alerts' ),
				'tags'                => __( 'Tags', 'wp-job-manager-alerts' ),
				'job_type'            => __( 'Job Type', 'wp-job-manager-alerts' ),
				'permission_checkbox' => __( 'Permission Checkbox', 'wp-job-manager-alerts' ),
			]
		);
	}

	/**
	 * Alert_Form_Fields constructor.
	 */
	public function __construct() {
		$this->active_fields = $this->get_active_fields();
	}

	/**
	 * Check if a field is enabled.
	 *
	 * @param string $field Field name.
	 *
	 * @return bool
	 */
	public function is_active( $field ) {
		return ! empty( $this->active_fields[ $field ] );
	}

	/**
	 * Get the active alert form fields.
	 *
	 * @return array The active alert form fields.
	 */
	public function get_active_fields() {

		$fields = self::get_default_fields();

		$field_settings = get_option( 'job_manager_alerts_form_fields', false );

		if ( false !== $field_settings && empty( $field_settings['fields'] ) ) {
			return [];
		}
		if ( ! empty( $field_settings ) ) {
			$fields_enabled = array_fill_keys( array_keys( $field_settings['fields'] ), true );
			$fields         = array_intersect_key( $fields, $fields_enabled );
		} else {
			if ( '0' === get_option( 'job_manager_permission_checkbox' ) ) {
				$fields['permission_checkbox'] = false;
			}
		}

		if ( ! taxonomy_exists( 'job_listing_region' ) || wp_count_terms( 'job_listing_region' ) <= 0 ) {
			unset( $fields['region'] );
		}

		if ( ! taxonomy_exists( 'job_listing_tag' ) || wp_count_terms( 'job_listing_tag' ) <= 0 ) {
			unset( $fields['tags'] );
		}

		if ( ! get_option( 'job_manager_enable_categories' ) || wp_count_terms( 'job_listing_category' ) <= 0 ) {
			unset( $fields['categories'] );
		}

		if ( ! get_option( 'job_manager_enable_types' ) || wp_count_terms( 'job_listing_types' ) <= 0 ) {
			unset( $fields['job_type'] );
		}

		return $fields;
	}

	/**
	 * Render the alert opt-in checkbox or consent message field.
	 *
	 * @param bool $selected
	 *
	 * @return string
	 */
	public function alert_permission( $selected = false ) {

		if ( $this->is_active( 'permission_checkbox' ) ) {

			return '
				<input type="checkbox" class="input-checkbox" name="alert_permission" id="alert_permission"
					value="1" required ' . checked( $selected, true, false ) . ' />
				<label for="alert_permission">
				' . Settings::get_alert_consent_message( true ) . '
				</label>
';
		} else {
			return '<div class="alert_consent_message">' . Settings::get_alert_consent_message() . '</div>';
		}
	}

	/**
	 * Render the job categories dropdown.
	 *
	 * @param bool $selected
	 *
	 * @return string
	 */
	public function alert_cats( $selected = null ) {

		return job_manager_dropdown_categories(
			[
				'taxonomy'     => 'job_listing_category',
				'hierarchical' => 1,
				'echo'         => 0,
				'name'         => 'alert_cats',
				'orderby'      => 'name',
				'selected'     => $selected,
				'hide_empty'   => false,
				'placeholder'  => __( 'Any category', 'wp-job-manager-alerts' ),
			]
		);

	}

	/**
	 * Render the job tags dropdown.
	 *
	 * @param bool $selected
	 *
	 * @return string
	 */
	public function alert_tags( $selected = null ) {

		return job_manager_dropdown_categories(
			[
				'taxonomy'     => 'job_listing_tag',
				'hierarchical' => 0,
				'echo'         => 0,
				'name'         => 'alert_tags',
				'orderby'      => 'name',
				'selected'     => $selected,
				'hide_empty'   => false,
				'placeholder'  => __( 'Any tag', 'wp-job-manager-alerts' ),
			]
		);

	}

	/**
	 * Render the regions dropdown.
	 *
	 * @param bool $selected
	 *
	 * @return string
	 */
	public function alert_regions( $selected = null ) {

		return job_manager_dropdown_categories(
			[
				'taxonomy'     => 'job_listing_region',
				'hierarchical' => 0,
				'echo'         => 0,
				'name'         => 'alert_regions',
				'class'        => 'alert_regions job-manager-enhanced-select',
				'orderby'      => 'name',
				'selected'     => $selected,
				'hide_empty'   => false,
				'placeholder'  => __( 'Any region', 'wp-job-manager-alerts' ),
			]
		);

	}

	/**
	 * Render the job type dropdown.
	 *
	 * @param bool $selected
	 *
	 * @return string
	 */
	public function alert_job_type( $selected = null ) {

		return job_manager_dropdown_categories(
			[
				'taxonomy'     => 'job_listing_type',
				'hierarchical' => 0,
				'echo'         => 0,
				'name'         => 'alert_job_type',
				'orderby'      => 'name',
				'selected'     => $selected,
				'hide_empty'   => false,
				'placeholder'  => __( 'Any type', 'wp-job-manager-alerts' ),
			]
		);

	}

	/**
	 * Render the alert frequency dropdown.
	 *
	 * @param array $args {
	 *   Arguments for the alert frequency dropdown.
	 *   @type string $selected The selected option.
	 *   @type string $class    Class for the <select> element.
	 * }
	 *
	 * @return string
	 */
	public function alert_frequency( $args = [] ) {

		$selected = $args['selected'] ?? null;
		$class    = $args['class'] ?? '';

		$schedules = Notifier::get_alert_schedules();

		$html = '<select name="alert_frequency" id="alert_frequency" class="' . esc_attr( $class ) . '">';

		foreach ( $schedules as $key => $schedule ) {
			$html .= '<option
					value="' . esc_attr( $key ) . '" ' . selected( $selected, $key, false ) . '>' . esc_html( $schedule['display'] ) . '</option>';
		}
		$html .= '</select>';

		return $html;

	}

}
