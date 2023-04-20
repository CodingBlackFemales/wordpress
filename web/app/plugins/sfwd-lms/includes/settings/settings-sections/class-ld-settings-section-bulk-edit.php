<?php
/**
 * LearnDash Settings Section for Bulk Edit.
 *
 * @since   4.2.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LearnDash_Settings_Section' ) && ! class_exists( 'LearnDash_Settings_Section_Bulk_Edit' ) ) {
	/**
	 * Class LearnDash Settings Section for Bulk Edit.
	 *
	 * @since 4.2.0
	 */
	class LearnDash_Settings_Section_Bulk_Edit extends LearnDash_Settings_Section {
		/**
		 * Protected constructor for class
		 *
		 * @since 4.2.0
		 */
		public function __construct() {
			$this->settings_page_id = 'learndash_lms_advanced';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_bulk_edit';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_bulk_edit';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_bulk_edit';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Bulk Edit', 'learndash' );

			parent::__construct();

			add_filter(
				'learndash_admin_settings_advanced_sections_with_hidden_metaboxes',
				function( array $section_keys ) {
					$section_keys[] = $this->settings_section_key;

					return $section_keys;
				}
			);
		}

		/**
		 * Shows the admin page content.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public function show_meta_box(): void {
			?>
			<div id="learndash-bulk-edit" class="sfwd_options">

				<h4>
					<?php esc_html_e( 'What do you want to update?', 'learndash' ); ?>
				</h4>

				<div class="tabs">
				<?php foreach ( Learndash_Admin_Bulk_Edit_Actions::get_classes() as $action ) : ?>
					<div data-target-id="#<?php echo esc_attr( $action->get_post_type() ); ?>" class="tab">
						<?php echo esc_html( $action->get_tab_name() ); ?>
					</div>
				<?php endforeach; ?>
				</div>

				<div>
				<?php foreach ( Learndash_Admin_Bulk_Edit_Actions::get_classes() as $action ) : ?>
					<div
						id="<?php echo esc_attr( $action->get_post_type() ); ?>"
						data-action-get-affected-posts-number="<?php echo esc_attr( $action->get_ajax_action_get_affected_posts_number() ); ?>"
						data-action-get-affected-posts-number-nonce="<?php echo esc_attr( $action->get_ajax_action_get_affected_posts_number_nonce() ); ?>"
						data-action-update-posts="<?php echo esc_attr( $action->get_ajax_action_update_posts() ); ?>"
						data-action-update-posts-nonce="<?php echo esc_attr( $action->get_ajax_action_update_posts_nonce() ); ?>"
						class="tab-content"
						style="display: none;"
					>
						<h4>
							<?php esc_html_e( 'Which', 'learndash' ); ?> <?php echo esc_html( mb_strtolower( $action->get_tab_name() ) ); ?>?
						</h4>

						<div class="bulk-edit-filters">
						<?php foreach ( $action->get_filters() as $filter_parameter => $filter ) : ?>
							<div class="bulk-edit-filter">
								<input
									type="checkbox"
									id="filter-<?php echo esc_attr( $action->get_post_type() . '-' . $filter_parameter ); ?>"
									data-target-id="#real-filter-container-<?php echo esc_attr( $action->get_post_type() . '-' . $filter_parameter ); ?>"
									class="bulk-edit-display-switcher"
									autocomplete="off"
								>
								<label for="filter-<?php echo esc_attr( $action->get_post_type() . '-' . $filter_parameter ); ?>">
									<?php echo esc_html( $filter->get_label() ); ?>
								</label>

								<div
									id="real-filter-container-<?php echo esc_attr( $action->get_post_type() . '-' . $filter_parameter ); ?>"
									class="bulk-edit-real-filter-container"
									style="display: none;"
								>
									<?php $filter->display(); ?>
								</div>
							</div>
						<?php endforeach; ?>
						</div>

						<h4>
							<?php esc_html_e( 'Which fields?', 'learndash' ); ?>
						</h4>

						<div class="bulk-edit-fields">
						<?php foreach ( $action->get_fields() as $field ) : ?>
							<div class="bulk-edit-field">
								<input
									type="checkbox"
									id="field-<?php echo esc_attr( $field->get_id() ); ?>"
									data-target-id="#real-field-container-<?php echo esc_attr( $field->get_id() ); ?>"
									data-field-name="<?php echo esc_attr( $field->get_name() ); ?>"
									class="bulk-edit-display-switcher"
									autocomplete="off"
								>
								<label for="field-<?php echo esc_attr( $field->get_id() ); ?>">
									<?php echo esc_html( $field->get_label() ); ?>
								</label>

								<div
									id="real-field-container-<?php echo esc_attr( $field->get_id() ); ?>"
									class="bulk-edit-real-field-container"
									style="display: none;"
								>
									<?php $field->display(); ?>
								</div>
							</div>
						<?php endforeach; ?>
						</div>

						<button class="bulk-edit-button button button-primary" disabled>
							<?php esc_html_e( 'Update', 'learndash' ); ?>
							<span class="posts-number"></span>
							<?php echo esc_html( mb_strtolower( $action->get_tab_name() ) ); ?>
						</button>
					</div>
				<?php endforeach; ?>
				</div>

			</div>
			<?php
		}

		/**
		 * Enqueues assets.
		 *
		 * @since 4.2.0
		 *
		 * @param string $settings_screen_id Settings Screen ID.
		 * @param string $settings_page_id   Settings Page ID.
		 */
		public function enqueue_assets( string $settings_screen_id = '', string $settings_page_id = '' ) {
			if ( $settings_page_id !== $this->settings_page_id ) {
				return;
			}

			global $learndash_assets_loaded;

			if ( ! isset( $learndash_assets_loaded['styles']['learndash-select2-jquery-style'] ) ) {
				wp_enqueue_style(
					'learndash-select2-jquery-style',
					LEARNDASH_LMS_PLUGIN_URL . 'assets/vendor-libs/select2-jquery/css/select2.min.css',
					array(),
					LEARNDASH_SCRIPT_VERSION_TOKEN
				);
				$learndash_assets_loaded['styles']['learndash-select2-jquery-style'] = __FUNCTION__;
			}

			if ( ! isset( $learndash_assets_loaded['scripts']['learndash-select2-jquery-script'] ) ) {
				wp_enqueue_script(
					'learndash-select2-jquery-script',
					LEARNDASH_LMS_PLUGIN_URL . 'assets/vendor-libs/select2-jquery/js/select2.full.min.js',
					array( 'jquery' ),
					LEARNDASH_SCRIPT_VERSION_TOKEN,
					true
				);
				$learndash_assets_loaded['scripts']['learndash-select2-jquery-script'] = __FUNCTION__;
			}
		}
	}

	add_action(
		'learndash_settings_sections_init',
		array( LearnDash_Settings_Section_Bulk_Edit::class, 'add_section_instance' ),
		9
	);

	add_action(
		'init',
		array( Learndash_Admin_Bulk_Edit_Actions::class, 'init_classes' )
	);

	add_action(
		'learndash_settings_page_load',
		function( ...$args ) {
			( new LearnDash_Settings_Section_Bulk_Edit() )->enqueue_assets( ...$args );
		},
		30,
		2
	);
}
