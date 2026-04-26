<?php
/**
 * ProPanel Shortcodes Section.
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_propanel' ) ) ) {
	class LearnDash_Shortcodes_Section_ld_propanel extends LearnDash_Shortcodes_Section {
		function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key         = 'ld_propanel';
			$this->shortcodes_section_title       = esc_html__( 'Reports', 'learndash' );
			$this->shortcodes_section_type        = 1;
			$this->shortcodes_section_description = esc_html__( 'This shortcode displays reporting widgets.', 'learndash' );

			parent::__construct();
		}

		function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'widget'         => array(
					'id'        => $this->shortcodes_section_key . '_widget',
					'name'      => 'widget',
					'type'      => 'select',
					// translators: Reports Widget.
					'label'     => esc_html_x( 'Widget', 'Reports Widget', 'learndash' ),
					'help_text' => esc_html__( 'Select which Reports widget to display', 'learndash' ),
					'value'     => '',
					'options'   => array(
						// translators: Reports widget shortcode.
						'link'           => esc_html_x( 'Link to Reports Full Page', 'Reports widget shortcode', 'learndash' ),
						// translators: Reports widget shortcode.
						'overview'       => esc_html_x( 'Overview Widget', 'Reports widget shortcode', 'learndash' ),
						// translators: Reports widget shortcode.
						'filtering'      => esc_html_x( 'Filtering Widget', 'Reports widget shortcode', 'learndash' ),
						// translators: Reports widget shortcode.
						'reporting'      => esc_html_x( 'Reporting Widget', 'Reports widget shortcode', 'learndash' ),
						// translators: Reports widget shortcode.
						'activity'       => esc_html_x( 'Activity Widget', 'Reports widget shortcode', 'learndash' ),
						// translators: Reports widget shortcode.
						'progress_chart' => esc_html_x( 'Progress Chart Widget', 'Reports widget shortcode', 'learndash' ),
					),
				),
				'filter_groups'  => array(
					'id'        => $this->shortcodes_section_key . '_filter_groups',
					'name'      => 'filter_groups',
					'type'      => 'number',
					'label'     => esc_html__( 'Filter Groups', 'learndash' ),
					'help_text' => esc_html__( 'Filter Widget by Group ID', 'learndash' ),
					'value'     => '',
				),

				'filter_courses' => array(
					'id'        => $this->shortcodes_section_key . '_filter_courses',
					'name'      => 'filter_courses',
					'type'      => 'number',
					'label'     => sprintf(
										// translators: placeholder: Course.
						esc_html_x( 'Filter %s', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'help_text' => sprintf(
										// translators: placeholder: Course.
						esc_html_x( 'Filter Widget by %s ID', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'     => '',
				),

				'filter_users'   => array(
					'id'        => $this->shortcodes_section_key . '_filter_users',
					'name'      => 'filter_users',
					'type'      => 'number',
					'label'     => esc_html__( 'Filter Users', 'learndash' ),
					'help_text' => esc_html__( 'Filter Widget by User ID', 'learndash' ),
					'value'     => '',
				),

				'filter_status'  => array(
					'id'        => $this->shortcodes_section_key . '_filter_status',
					'name'      => 'filter_status',
					'type'      => 'select',
					'label'     => sprintf(
										// translators: placeholder: Course.
						esc_html_x( 'Filter %s Status', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'help_text' => sprintf(
										// translators: placeholder: Course.
						esc_html_x( 'Filter Widget by %s Status', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'     => '',
					// 'attrs'           =>  array( 'multiple' => 'multiple' ),
					'options'   => array(
						// translators: Course status - All Statuses.
						''            => esc_html_x( 'All Statuses', 'Course status - All Statuses', 'learndash' ),
						// translators: Course status - Not Started.
						'not-started' => esc_html_x( 'Not Started', 'Course status - Not Started', 'learndash' ),
						// translators: Course status - In Progress.
						'in-progress' => esc_html_x( 'In Progress', 'Course status - In Progress', 'learndash' ),
						// translators: Course status - Completed.
						'completed'   => esc_html_x( 'Completed', 'Course status - Completed', 'learndash' ),
					),

				),

				'display_chart'  => array(
					'id'        => $this->shortcodes_section_key . '_display_chart',
					'name'      => 'display_chart',
					'type'      => 'select',
					'label'     => esc_html__( 'Display Chart', 'learndash' ),
					'help_text' => esc_html__( 'Display Chart Orientation', 'learndash' ),
					'value'     => '',
					'options'   => array(
						// translators: Chart orientation.
						''             => esc_html_x( 'Stacked (default)', 'Chart orientation', 'learndash' ),
						// translators: Chart orientation.
						'side-by-side' => esc_html_x( 'Side by Side', 'Chart orientation', 'learndash' ),
					),

				),
				'per_page'       => array(
					'id'        => $this->shortcodes_section_key . '_per_page',
					'name'      => 'per_page',
					'type'      => 'number',
					// translators: Pagination for Widget output.
					'label'     => esc_html_x( 'Per Page', 'Pagination for Widget output', 'learndash' ),
					// translators: Reports Widget.
					'help_text' => esc_html_x( 'Pagination for Widget output', 'Reports Widget', 'learndash' ),
					'value'     => '',
				),
			);

			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}

		function show_shortcodes_section_footer_extra() {
			?>
			<script>
				jQuery(document).ready(function() {
					if ( jQuery( 'form#learndash_shortcodes_form_ld_propanel select#ld_propanel_widget' ).length) {
						jQuery( 'form#learndash_shortcodes_form_ld_propanel select#ld_propanel_widget').change( function() {
							var selected_widget = jQuery(this).val();

							jQuery( 'form#learndash_shortcodes_form_ld_propanel input#ld_propanel_filter_groups').val('');
							jQuery( 'form#learndash_shortcodes_form_ld_propanel input#ld_propanel_filter_courses').val('');
							jQuery( 'form#learndash_shortcodes_form_ld_propanel input#ld_propanel_filter_users').val('');
							jQuery( 'form#learndash_shortcodes_form_ld_propanel select#ld_propanel_filter_status').val('');
							jQuery( 'form#learndash_shortcodes_form_ld_propanel select#ld_propanel_display_chart').val('');
							jQuery( 'form#learndash_shortcodes_form_ld_propanel input#ld_propanel_per_page').val('');

							if ( ( selected_widget == 'reporting' ) || ( selected_widget == 'activity' ) || ( selected_widget == 'progress_chart' ) ) {
								jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_filter_groups_field').slideDown();
								jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_filter_courses_field').slideDown();
								jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_filter_users_field').slideDown();

								if ( selected_widget == 'progress_chart' ) {
									jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_display_chart_field').slideDown();
									jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_filter_status_field').hide();
									jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_per_page_field').hide();
								} else {
									jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_display_chart_field').hide();
									jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_filter_status_field').slideDown();
									jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_per_page_field').slideDown();
								}

								if ( ( selected_widget == 'progress_chart' ) || ( selected_widget == 'reporting' ) ) {
									if ( !jQuery( 'form#learndash_shortcodes_form_ld_propanel p#required-message').length ) {
										jQuery( '<p id="required-message" style="color: red;"><?php esc_html_e( 'When using the "reporting" or "progress_chart" widget shortcodes, a selection from the Group, Course or User filters is recommended unless also using the "filtering" widget shortcode on the same page.', 'learndash' ); ?></p>' ).insertBefore( 'form#learndash_shortcodes_form_ld_propanel .learndash_shortcodes_section' );
									}
									jQuery( 'form#learndash_shortcodes_form_ld_propanel p#required-message').show();
								} else {
									if ( jQuery( 'form#learndash_shortcodes_form_ld_propanel p#required-message').length ) {
										jQuery( 'form#learndash_shortcodes_form_ld_propanel p#required-message').hide();
									}
								}

							} else {
								jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_filter_groups_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_filter_courses_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_filter_users_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_filter_status_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_display_chart_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_propanel #ld_propanel_per_page_field').hide();

								if ( jQuery( 'form#learndash_shortcodes_form_ld_propanel p#required-message').length ) {
									jQuery( 'form#learndash_shortcodes_form_ld_propanel p#required-message').hide();
								}
							}
						});
						jQuery( 'form#learndash_shortcodes_form_ld_propanel select#ld_propanel_widget').change();
					}
				});
			</script>
			<?php
		}
	}
}

