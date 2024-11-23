<?php

// phpcs:disable Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection AutoloadingIssuesInspection */
// phpcs:enable Generic.Commenting.DocComment.MissingShort

namespace WPForms\Pro\Integrations\AI\Admin\Builder;

use WPForms\Pro\Integrations\AI\Admin\Ajax\Forms;
use WPForms\Pro\Integrations\AI\Helpers;

/**
 * Enqueue assets on the Form Builder screen in Pro.
 *
 * @since 1.9.2
 */
class Enqueues {

	/**
	 * Initialize.
	 *
	 * @since 1.9.2
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.9.2
	 */
	private function hooks() {

		add_action( 'wpforms_builder_enqueues', [ $this, 'enqueues' ] );
		add_filter( 'wpforms_integrations_ai_admin_builder_enqueues_localize_chat_strings', [ $this, 'add_localize_chat_data' ] );
		add_filter( 'wpforms_builder_template_active', [ $this, 'template_active' ], 10, 2 );
	}

	/**
	 * Enqueue styles and scripts.
	 *
	 * @since 1.9.2
	 *
	 * @param string|null $view Current view (panel).
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function enqueues( $view ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		$this->enqueue_styles();
		$this->enqueue_scripts();
	}

	/**
	 * Enqueue styles.
	 *
	 * @since 1.9.2
	 */
	private function enqueue_styles() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-ai-forms',
			WPFORMS_PLUGIN_URL . "assets/pro/css/integrations/ai/ai-forms{$min}.css",
			[],
			WPFORMS_VERSION
		);
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.9.2
	 */
	private function enqueue_scripts() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-ai-form-generator',
			WPFORMS_PLUGIN_URL . "assets/pro/js/integrations/ai/form-generator/form-generator{$min}.js",
			[],
			WPFORMS_VERSION,
			true
		);

		wp_localize_script(
			'wpforms-ai-form-generator',
			'wpforms_ai_form_generator',
			$this->get_localize_form_generator_data()
		);
	}

	/**
	 * Set active form template.
	 *
	 * @since 1.9.2
	 *
	 * @param array|mixed $details Details.
	 * @param object      $form    Form data.
	 *
	 * @return array|void
	 */
	public function template_active( $details, $form ) {

		$details = (array) $details;

		if ( empty( $form ) ) {
			return;
		}

		$form_data = wpforms_decode( $form->post_content );

		if ( empty( $form_data['meta']['template'] ) || $form_data['meta']['template'] !== 'generate' ) {
			return $details;
		}

		return [
			'name'          => esc_html__( 'Generate With AI', 'wpforms' ),
			'slug'          => 'generate',
			'description'   => '',
			'includes'      => '',
			'icon'          => '',
			'modal'         => '',
			'modal_display' => false,
		];
	}

	/**
	 * Get form generator localize data.
	 *
	 * @since 1.9.2
	 *
	 * @return array
	 */
	private function get_localize_form_generator_data(): array {

		$min          = wpforms_get_min_suffix();
		$addons_data  = $this->get_required_addons_data();
		$modules_path = WPFORMS_PLUGIN_URL . 'assets/pro/js/integrations/ai/form-generator/modules/';

		return [
			'nonce'           => wp_create_nonce( 'wpforms-ai-nonce' ),
			'adminNonce'      => wp_create_nonce( 'wpforms-admin' ),
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'addonsData'      => $addons_data,
			'addonsAction'    => $this->get_required_addons_action( $addons_data ),
			'dismissed'       => $this->get_dismissed_elements(),
			'isLicenseActive' => Helpers::is_license_active(),
			'newFormUrl'      => admin_url( 'admin.php?page=wpforms-builder&view=setup&ai-form' ),
			'modules'         => [
				'main'    => $modules_path . "main{$min}.js?ver=" . WPFORMS_VERSION,
				'preview' => $modules_path . "preview{$min}.js?ver=" . WPFORMS_VERSION,
				'modals'  => $modules_path . "modals{$min}.js?ver=" . WPFORMS_VERSION,
			],
			'templateCard'    => [
				'imageSrc'           => WPFORMS_PLUGIN_URL . 'assets/images/integrations/ai/ai-feature-icon.svg',
				'name'               => esc_html__( 'Generate With AI', 'wpforms' ),
				'desc'               => esc_html__( 'Write simple prompts to create complex forms catered to your specific needs.', 'wpforms' ),
				'buttonTextInit'     => esc_html__( 'Generate Form', 'wpforms' ),
				'buttonTextContinue' => esc_html__( 'Continue Generating', 'wpforms' ),
				'new'                => esc_html__( 'NEW!', 'wpforms' ),
			],
			'panel'           => [
				'backToTemplates' => esc_html__( 'Back to Templates', 'wpforms' ),
				'emptyStateTitle' => esc_html__( 'Build Your Form Fast With the Help of AI', 'wpforms' ),
				'emptyStateDesc'  => esc_html__( 'Not sure where to begin? Use our Generative AI tool to get started or take your pick from our wide variety of fields and start building out your form!', 'wpforms' ),
				'submitButton'    => esc_html__( 'Submit', 'wpforms' ),
				'tooltipTitle'    => esc_html__( 'This is just a preview of your form.', 'wpforms' ),
				'tooltipText'     => esc_html__( 'Click "Use This Form" to start editing.', 'wpforms' ),
			],
			'addons'          => [
				'installTitle'              => esc_html__( 'Before We Proceed', 'wpforms' ),
				'installContent'            => esc_html__( 'In order to build the best forms possible, we need to install some addons. Would you like to install the recommended addons?', 'wpforms' ),
				'activateContent'           => esc_html__( 'In order to build the best forms possible, we need to activate some addons. Would you like to activate the recommended addons?', 'wpforms' ),
				'installConfirmButton'      => esc_html__( 'Yes, Install', 'wpforms' ),
				'activateConfirmButton'     => esc_html__( 'Yes, Activate', 'wpforms' ),
				'cancelButton'              => esc_html__( 'No, Thanks', 'wpforms' ),
				'dontShow'                  => esc_html__( 'Don\'t show this again', 'wpforms' ),
				'okay'                      => esc_html__( 'Okay', 'wpforms' ),
				'installing'                => esc_html__( 'Installing...', 'wpforms' ),
				'activating'                => esc_html__( 'Activating...', 'wpforms' ),
				'addonsInstalledTitle'      => esc_html__( 'Addons Installed', 'wpforms' ),
				'addonsActivatedTitle'      => esc_html__( 'Addons Activated', 'wpforms' ),
				'addonsInstalledContent'    => esc_html__( 'You’re all set. We’re going to reload the builder and you can start building your form.', 'wpforms' ),
				'addonsInstallErrorTitle'   => esc_html__( 'Addons Installation Error', 'wpforms' ),
				'addonsActivateErrorTitle'  => esc_html__( 'Addons Activation Error', 'wpforms' ),
				'addonsInstallError'        => esc_html__( 'Can\'t install or activate the required addons.', 'wpforms' ),
				'addonsInstallErrorNetwork' => esc_html__( 'There appears to be a network error.', 'wpforms' ),
				'dismissErrorTitle'         => esc_html__( 'Error', 'wpforms' ),
				'dismissError'              => esc_html__( 'Can\'t dismiss the modal window.', 'wpforms' ),
			],
			'misc'            => [
				'warningExistingForm' => esc_html__( 'You’re about to overwrite your existing form. This will delete all fields and reset external connections. Are you sure you want to continue?', 'wpforms' ),
			],
		];
	}

	/**
	 * Add chat element localize data.
	 *
	 * @since 1.9.2
	 *
	 * @param array $strings Strings.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection HtmlUnknownTarget
	 */
	public function add_localize_chat_data( $strings ): array {

		$strings['forms'] = [
			'title'               => esc_html__( 'Generate a Form', 'wpforms' ),
			'description'         => esc_html__( 'Describe the form you would like to create or use one of the example prompts below to get started.', 'wpforms' ),
			'descrEndDot'         => '',
			'learnMore'           => esc_html__( 'Learn more about WPForms AI', 'wpforms' ),
			'learnMoreUrl'        => wpforms_utm_link( 'https://wpforms.com/features/wpforms-ai/', 'Builder - Settings', 'Learn more - AI Choices modal' ),
			'inactiveAnswerTitle' => esc_html__( 'Go back to this version of the form', 'wpforms' ),
			'useForm'             => esc_html__( 'Use This Form', 'wpforms' ),
			'placeholder'         => esc_html__( 'What would you like to create?', 'wpforms' ),
			'waiting'             => esc_html__( 'Just a minute...', 'wpforms' ),
			'errors'              => [
				'default'    => esc_html__( 'An error occurred while generating form.', 'wpforms' ),
				'rate_limit' => esc_html__( 'Sorry, you\'ve reached your daily limit for generating forms.', 'wpforms' ),
			],
			'footer'              => [
				esc_html__( 'What do you think of the form I created for you? If you’re happy with it, you can use this form. Otherwise, make changes by entering additional prompts.', 'wpforms' ),
				esc_html__( 'How’s that? Are you ready to use this form?', 'wpforms' ),
				esc_html__( 'Does this look good? Are you ready to implement this form?', 'wpforms' ),
				esc_html__( 'Is this what you had in mind? Are you satisfied with the results?', 'wpforms' ),
				esc_html__( 'Happy with the form? Ready to move forward?', 'wpforms' ),
				esc_html__( 'Is this form a good fit for your needs? Can we proceed?', 'wpforms' ),
				esc_html__( 'Are you pleased with the outcome? Ready to use this form?', 'wpforms' ),
				esc_html__( 'Does this form meet your expectations? Can we move on to the next step?', 'wpforms' ),
				esc_html__( 'Is this form what you were envisioning? Are you ready to use it?', 'wpforms' ),
				esc_html__( 'Satisfied with the form? Let\'s use it!?', 'wpforms' ),
				esc_html__( 'Does this form align with your goals? Are you ready to implement it?', 'wpforms' ),
				esc_html__( 'Happy with the results? Let\'s put this form to work!', 'wpforms' ),
			],
			'reasons'             => [
				'default'    => sprintf(
					wp_kses( /* translators: %1$s - Reload link class. */
						__( '<a href="#" class="%1$s">Reload this window</a> and try again.', 'wpforms' ),
						[
							'a' => [
								'href'  => [],
								'class' => [],
							],
						]
					),
					'wpforms-ai-chat-reload-link'
				),
				'rate_limit' => sprintf(
					wp_kses( /* translators: %s - WPForms contact support link. */
						__( 'You may only generate choices 50 times per day. If you believe this is an error, <a href="%s" target="_blank" rel="noopener noreferrer">please contact WPForms support</a>.', 'wpforms' ),
						[
							'a' => [
								'href'   => [],
								'target' => [],
								'rel'    => [],
							],
						]
					),
					wpforms_utm_link( 'https://wpforms.com/account/support/', 'AI Feature' )
				),
			],
			'samplePrompts'       => [
				[
					'icon'  => 'wpforms-ai-chat-sample-restaurant',
					'title' => esc_html__( 'restaurant customer satisfaction survey', 'wpforms' ),
				],
				[
					'icon'  => 'wpforms-ai-chat-sample-ticket',
					'title' => esc_html__( 'online event registration', 'wpforms' ),
				],
				[
					'icon'  => 'wpforms-ai-chat-sample-design',
					'title' => esc_html__( 'job application for a web designer', 'wpforms' ),
				],
				[
					'icon'  => 'wpforms-ai-chat-sample-stop',
					'title' => esc_html__( 'cancelation survey for a subscription', 'wpforms' ),
				],
				[
					'icon'  => 'wpforms-ai-chat-sample-pizza',
					'title' => esc_html__( 'takeout order for a pizza store', 'wpforms' ),
				],
				[
					'icon'  => 'wpforms-ai-chat-sample-market',
					'title' => esc_html__( 'market vendor application', 'wpforms' ),
				],
			],
		];

		$user_id = get_current_user_id();

		// Get the chat session stored in user meta.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['session'] ) ) {
			$session_id = sanitize_text_field( wp_unslash( $_GET['session'] ) );
			$meta       = get_user_meta( $user_id, 'wpforms_builder_ai_form_chat_' . $session_id, true );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// If we have the meta-data, add it to the strings.
		if ( ! empty( $meta ) ) {
			// Remove user meta after using it.
			delete_user_meta( $user_id, 'wpforms_builder_ai_form_chat_' . ( $session_id ?? '' ) );

			$strings['forms']['chatHtml']        = $meta['chatHtml'];
			$strings['forms']['responseHistory'] = $meta['responseHistory'];
		}

		return $strings;
	}

    /**
     * Get required addons' data.
     *
     * @since 1.9.2
     *
     * @return array
     */
    private function get_required_addons_data(): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// The addon installation procedure has floating issues in PHP 7.0.
		// It's better to skip the installation in this case to avoid addon installation errors.
		if ( PHP_VERSION_ID < 70400 ) {
			return [];
		}

        $addons_obj = wpforms()->obj( 'addons' );

		if ( ! $addons_obj ) {
			return [];
		}

		$urls = [];

		// Get the URLs for the required addons.
		foreach ( Forms::FORM_GENERATOR_REQUIRED_ADDONS as $slug ) {
			$addon = $addons_obj->get_addon( $slug );

			if (
				empty( $addon ) || // Exceptional case when `addons.json` is not loaded.

				// This means that addon is already installed and active.
				( isset( $addon['status'] ) && $addon['status'] === 'active' ) ||

				// This means that addon is not available in the current license.
				// We should skip in this case as it is impossible to install or activate the addon.
				( isset( $addon['action'] ) && $addon['action'] === 'upgrade' )
			) {
				continue;
			}

			$urls[ $slug ] = [
				'url'  => $addon['url'] ?? '',
				'path' => $addon['path'] ?? '',
			];
		}

		return $urls;
    }

    /**
     * Get required addons' action.
     *
     * @since 1.9.2
	 *
	 * @param array $addons_data Addons data.
     *
     * @return string
     */
    private function get_required_addons_action( array $addons_data ): string {

		if ( empty( $addons_data ) ) {
			return '';
		}

		foreach ( $addons_data as $data ) {
			if ( ! empty( $data['url'] ) ) {
				return 'install';
			}
		}

		return 'activate';
	}

    /**
     * Get dismissed elements data.
     *
     * @since 1.9.2
     *
     * @return array
     */
    private function get_dismissed_elements(): array {

		$user_id = get_current_user_id();

		// Dismissed elements.
		$dismissed = get_user_meta( $user_id, 'wpforms_dismissed', true );

		return [
			'installAddons' => ! empty( $dismissed['ai-forms-install-addons-modal'] ),
		];
	}
}
