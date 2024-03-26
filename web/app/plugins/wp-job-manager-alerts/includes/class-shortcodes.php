<?php
/**
 * Job Alerts shortcode and handlers.
 *
 * @package wp-job-manager-alerts
 */

namespace WP_Job_Manager_Alerts;

use WP_Job_Manager\Guest_Session;
use WP_Job_Manager\Guest_User;
use WP_Job_Manager\UI\Notice;
use WP_Job_Manager\UI\Redirect_Message;
use WP_Job_Manager_Alerts\Emails\Confirmation_Email;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WP_Job_Manager_Alerts\Shortcodes class.
 */
class Shortcodes {

	use Singleton;

	/**
	 * Alert feedback message set as a result of an action.
	 *
	 * @var string
	 */
	private $alert_message = null;

	/**
	 * Alert action being handled.
	 *
	 * @var string
	 */
	private $action = '';

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'shortcode_action_handler' ] );

		add_shortcode( 'job_alerts', [ $this, 'job_alerts' ] );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		$this->action = isset( $_REQUEST['action'] ) ? sanitize_title( wp_unslash( $_REQUEST['action'] ) ) : '';
	}

	/**
	 * Handle actions which need to be run before the shortcode e.g. post actions
	 */
	public function shortcode_action_handler() {
		global $post;

		if ( ! empty( $post->post_content ) && str_contains( $post->post_content, '[job_alerts' ) ) {
			$this->job_alerts_handler();
		}
	}

	/**
	 * Handles actions for an alert.
	 *
	 * phpcs:ignore Squiz.Commenting.FunctionCommentThrowTag.Missing -- Exception is handled in method.
	 */
	public function job_alerts_handler() {

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended -- Input used for comparison.
		$this->action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : '';

		if ( empty( $this->action ) && WP_Job_Manager_Alerts::instance()->can_user_add_alert() ) {
			$guest_user = Guest_Session::get_current_guest();

			if ( ! is_user_logged_in() && empty( $guest_user ) && empty( $_REQUEST['updated'] ) ) {
				wp_safe_redirect( add_query_arg( [ 'action' => 'add_alert' ] ) );
				exit;
			}
		}

		try {
			/**
			 * Actions without nonce check, allowed to come from external links. (E-mails)
			 */
			switch ( $this->action ) {

				case 'unsubscribe':
					$this->handle_unsubscribe();
					break;

				case 'confirm':
					$alert_id = empty( $_REQUEST['alert_id'] ) ? 0 : absint( $_REQUEST['alert_id'] );
					$alert    = $this->get_alert( $alert_id );
					$this->handle_confirm( $alert );
					break;
			}

			/**
			 * Actions with nonce check.
			 */

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce check.
			if ( ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'job_manager_alert_actions' ) ) {

				if ( ! WP_Job_Manager_Alerts::instance()->can_user_add_alert() ) {
					throw new \Exception( __( 'You need to be logged in to create alerts.', 'wp-job-manager-alerts' ) );
				}

				$alert_id = empty( $_REQUEST['alert_id'] ) ? 0 : absint( $_REQUEST['alert_id'] );

				$alert = null;

				if ( 'add_alert' !== $this->action ) {
					$alert = $this->get_alert( $alert_id );
				}

				switch ( $this->action ) {
					case 'add_alert':
						if ( isset( $_POST['submit-job-alert'] ) ) {
							$this->handle_add_alert();
						}
						break;

					case 'edit':
						if ( isset( $_POST['submit-job-alert'] ) ) {
							$this->handle_edit_alert( $alert );
						}
						break;
					case 'toggle_status':
						$this->handle_toggle_status( $alert );
						break;
					case 'delete':
						$this->handle_delete( $alert );
						break;
					case 'email':
						$this->handle_send_now( $alert );
						break;
					default:
						break;
				}
			}
		} catch ( \Exception $e ) {
			$this->alert_message = Notice::error( $e->getMessage() );
		}
	}

	/**
	 * Check permissions and load the alert.
	 *
	 * @param int|null $alert_id Alert ID.
	 * @param int|null $user_id Owner user to check. Defaults to current user or guest.
	 *
	 * @return Alert
	 * @throws \Exception Error if the alert ID is invalid or the user does not own the alert.
	 */
	protected function get_alert( $alert_id, $user_id = null ) {
		$alert = Alert::load( $alert_id );

		if ( ! $alert || ! $alert->check_ownership( $user_id ) ) {
			throw new \Exception( __( 'Invalid Alert', 'wp-job-manager-alerts' ) );
		}

		return $alert;
	}

	/**
	 * Delete alert.
	 *
	 * @param Alert $alert Current alert.
	 *
	 * @throws \Exception When the alert belongs to a different user.
	 */
	private function handle_delete( Alert $alert ): void {
		$alert->delete();

		// translators: %s is the alert name.
		$alert_message = sprintf( __( '%s: Alert deleted.', 'wp-job-manager-alerts' ), $alert->get_name() );

		$this->redirect( $alert_message );
	}


	/**
	 * Shortcode for the alerts page
	 */
	public function job_alerts() {

		$alert_message = $this->get_alert_message();

		ob_start();

		if ( ! empty( $alert_message ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $alert_message;
		}

		if ( ! WP_Job_Manager_Alerts::instance()->can_user_add_alert() ) {

			get_job_manager_template( 'my-alerts-login.php', [], 'wp-job-manager-alerts', JOB_MANAGER_ALERTS_PLUGIN_DIR . '/templates/' );

			return ob_get_clean();
		}

		wp_enqueue_script( 'job-alerts' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		$alert_id = isset( $_REQUEST['alert_id'] ) ? absint( $_REQUEST['alert_id'] ) : '';

		switch ( $this->action ) {
			case 'add_alert':
				$this->add_alert();
				break;
			case 'edit':
				$this->edit_alert( $alert_id );
				break;
			case 'view':
				$this->view_results( $alert_id );
				break;
			case 'unsubscribe':
				break;
			case 'confirm':
				if ( empty( $this->alert_message ) ) {
					$this->view_alerts();
				}
				break;
			default:
				$this->view_alerts();

		}

		return ob_get_clean();
	}

	/**
	 * List the current user's alerts.
	 */
	public function view_alerts() {

		$user = wp_get_current_user();

		if ( ! $user->ID ) {
			$user = Guest_Session::get_current_guest();
		}

		if ( empty( $user ) ) {
			return;
		}

		$alerts = Alert::get_user_alerts();

		get_job_manager_template(
			'my-alerts.php',
			[
				'alerts' => $alerts,
				'user'   => $user,
			],
			'wp-job-manager-alerts',
			JOB_MANAGER_ALERTS_PLUGIN_DIR . '/templates/'
		);
	}

	/**
	 * Add alert form
	 */
	public function add_alert() {

		$form_data = $this->get_alert_form_data();

		$user_email = self::get_user_email();

		if ( ! empty( $user_email ) ) {
			$form_data['alert_email'] = $user_email;
		}

		get_job_manager_template(
			'alert-form.php',
			array_merge(
				[
					'alert_id'        => null,
					'show_alert_name' => false,
				],
				$form_data
			),
			'wp-job-manager-alerts',
			JOB_MANAGER_ALERTS_PLUGIN_DIR . '/templates/'
		);
	}

	/**
	 * Display the edit alert form.
	 *
	 * @param int $alert_id Alert ID.
	 */
	public function edit_alert( $alert_id ) {

		try {
			$alert = $this->get_alert( $alert_id );
			$user  = $alert->get_user();

			$search_terms = Post_Types::get_alert_search_terms( $alert_id );

			$form_data = $this->get_alert_form_data();

			$post = $alert->get_post();

			get_job_manager_template(
				'alert-form.php',
				[
					'alert_id'        => $alert_id,
					'alert_name'      => $form_data['alert_name'] ?? $alert->get_name(),
					'alert_keyword'   => $form_data['alert_keyword'] ?? $post->alert_keyword,
					'alert_location'  => $form_data['alert_location'] ?? $post->alert_location,
					'alert_frequency' => $form_data['alert_frequency'] ?? $post->alert_frequency,
					'alert_cats'      => $form_data['alert_cats'] ?? $search_terms['categories'],
					'alert_regions'   => $form_data['alert_regions'] ?? $search_terms['regions'],
					'alert_tags'      => $form_data['alert_tags'] ?? $search_terms['tags'],
					'alert_job_type'  => $form_data['alert_job_type'] ?? $search_terms['types'],
					'alert_email'     => $user->user_email ?? '',
					'show_alert_name' => true,
				],
				'wp-job-manager-alerts',
				JOB_MANAGER_ALERTS_PLUGIN_DIR . '/templates/'
			);
		} catch ( \Exception $e ) {
			$this->alert_message = '<div class="job-manager-error">' . $e->getMessage() . '</div>';
		}
	}

	/**
	 * Display the alert results.
	 *
	 * @param int $alert_id Alert ID.
	 */
	public function view_results( $alert_id ) {

		try {
			$alert = $this->get_alert( $alert_id );

			$jobs = $alert->get_matching_jobs( true );

			// Translators: placeholder is the alert name.
			echo wp_kses_post( wpautop( sprintf( __( 'Jobs matching your "%s" alert:', 'wp-job-manager-alerts' ), $alert->get_name() ) ) );

			if ( $jobs->have_posts() ) {
				?>

				<ul class="job_listings">

					<?php
					while ( $jobs->have_posts() ) :
						$jobs->the_post();
						?>

						<?php get_job_manager_template_part( 'content', 'job_listing' ); ?>

					<?php endwhile; ?>

				</ul>
				<?php
			} else {
				echo wp_kses_post( wpautop( __( 'No jobs found', 'wp-job-manager-alerts' ) ) );
			}

			wp_reset_postdata();
		} catch ( \Exception $e ) {
			echo '<div class="job-manager-error">' . esc_html( $e->getMessage() ) . '</div>';
		}
	}

	/**
	 * Handle a user clicking the unsubscribe button in the email.
	 *
	 * @return void
	 * @throws \Exception When the verification token is invalid.
	 */
	private function handle_unsubscribe(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Input is used safely.
		$alert_id = empty( $_REQUEST['alert_id'] ) ? '' : absint( $_REQUEST['alert_id'] );
		$user_id  = empty( $_REQUEST['user_id'] ) ? '' : absint( $_REQUEST['user_id'] );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input is used for comparison.
		$token = empty( $_REQUEST['token'] ) ? '' : wp_unslash( $_REQUEST['token'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$guest_user = empty( $user_id ) ? Guest_Session::get_current_guest() : false;

		$token_valid = ! empty( $user_id ) && ! empty( $alert_id ) && WP_Job_Manager_Alerts::instance()->verify_alert_token( $token, $alert_id, $user_id );

		if ( ! $guest_user && ! $token_valid ) {
			throw new \Exception( __( 'Invalid Alert', 'wp-job-manager-alerts' ) );
		}

		$alert = $this->get_alert( $alert_id, $user_id ?? $guest_user->ID );

		$alert->delete();

		$alert_message = [
			'title'   => __( 'Alert deleted', 'wp-job-manager-alerts' ),
			'message' => __( 'You will no longer receive e-mails for this search.', 'wp-job-manager-alerts' ),
			'buttons' => [
				[
					'url'   => remove_query_arg(
						[
							'action',
							'alert_id',
							'user_id',
							'token',
						]
					),
					'label' => __( 'Manage Alerts', 'wp-job-manager-alerts' ),
				],
			],
		];

		$this->alert_message = Notice::success( $alert_message );

	}

	/**
	 * Handles changes the status of an alert.
	 *
	 * @param Alert $alert Current alert.
	 *
	 * @return void
	 * @throws \Exception Throws an exception when the current user is not the owner of the alert.
	 */
	private function handle_toggle_status( Alert $alert ): void {
		if ( $alert->is_enabled() ) {
			$alert->disable();
		} else {
			$alert->enable();
		}

		// translators: %1$ is the alert title, %2$s is Enabled or Disabled.
		$alert_message = sprintf( __( '%1$s: Alert %2$s.', 'wp-job-manager-alerts' ), $alert->get_name(), $alert->is_enabled() ? __( 'enabled', 'wp-job-manager-alerts' ) : __( 'disabled', 'wp-job-manager-alerts' ) );

		$this->redirect( $alert_message );

	}

	/**
	 * Handles changes the status of an alert.
	 *
	 * @param Alert $alert Current alert.
	 *
	 * @return void
	 * @throws \Exception Throws an exception when the current user is not the owner of the alert.
	 */
	private function handle_confirm( Alert $alert ): void {

		if ( $alert->is_enabled() ) {
			return;
		}

		$alert->enable();

		$alert_message = [
			'title'   => __( 'Alert confirmed', 'wp-job-manager-alerts' ),
			'message' => __( 'You will start receiving new job listings matching your search.', 'wp-job-manager-alerts' ),
			'links'   => [
				[
					'url'   => remove_query_arg(
						[
							'action',
							'alert_id',
						]
					),
					'label' => __( 'Manage Alerts', 'wp-job-manager-alerts' ),
				],
			],
		];

		$this->alert_message = Notice::success( $alert_message );

	}

	/**
	 * Handles the action to trigger an alert.
	 *
	 * @param Alert $alert Current alert.
	 */
	private function handle_send_now( Alert $alert ): void {
		$alert->send_now();

		// translators: %s is the alert name.
		$alert_message = sprintf( __( '%s: Alert e-mail sent.', 'wp-job-manager-alerts' ), $alert->get_name() );

		$this->redirect( $alert_message );
	}

	/**
	 * Handles the action to add an alert.
	 *
	 * @throws \Exception When the form is invalid.
	 */
	private function handle_add_alert() {

		$alert_data = $this->get_alert_form_data();

		$alerts_form_fields           = get_option( 'job_manager_alerts_form_fields', [] );
		$permission_checkbox_required = isset( $alerts_form_fields['fields']['permission_checkbox'] );

		if ( $permission_checkbox_required && empty( $alert_data['alert_permission'] ) ) {
			throw new \Exception( __( 'You need to approve receiving emails for this alert.', 'wp-job-manager-alerts' ) );
		}

		if ( empty( $alert_data['alert_name'] ) ) {
			$alert_data['alert_name'] = self::generate_alert_name( $alert_data );
		}

		$current_user = wp_get_current_user();

		if ( ! $current_user->exists() ) {
			$current_user = Guest_Session::get_current_guest();
		}

		if ( false === $current_user ) {
			$this->create_new_guest_alert( $alert_data );
		} else {

			Alert::create( $alert_data, $current_user );

			$alert_message = [
				'title'   => __( 'Alert created', 'wp-job-manager-alerts' ),
				'message' => __( 'You will start receiving new job listings matching your search.', 'wp-job-manager-alerts' ),
			];

			$this->redirect( $alert_message );
		}
	}

	/**
	 * Create an alert for a new guest user, and set up guest account and confirmation.
	 *
	 * @param array $alert_data Alert data.
	 *
	 * @throws \Exception When the email address is invalid.
	 */
	private function create_new_guest_alert( $alert_data ) {
		$email = sanitize_email( $alert_data['alert_email'] );

		if ( get_user_by( 'email', $email ) ) {
			$this->alert_message = Notice::error(
				[
					'classes' => [ 'actions-right' ],
					'message' => __( 'A user account already exists for this e-mail.', 'wp-job-manager-alerts' ),
					'buttons' => [
						[
							'url'   => apply_filters( 'job_manager_alerts_login_url', wp_login_url( get_permalink() ) ),
							'label' => __( 'Sign in', 'wp-job-manager-alerts' ),
							'class' => [],
						],
					],
				]
			);

			return;
		}

		$owner = Guest_User::create( $email );

		if ( ! $owner ) {
			throw new \Exception( __( 'Invalid email address.', 'wp-job-manager-alerts' ) );
		}

		$alert_data['post_status'] = 'draft';

		$alert = Alert::create( $alert_data, $owner );

		Confirmation_Email::send(
			[
				'email' => $owner->user_email,
				'alert' => $alert->get_post(),
				'guest' => $owner,
				'token' => $owner->create_token(),
			]
		);

		list( , $domain ) = explode( '@', $email, 2 );

		$alert_message = [
			'title'   => __( 'Alert created', 'wp-job-manager-alerts' ),
			'message' => __( 'A confirmation e-mail has been sent to your e-mail address.', 'wp-job-manager-alerts' ),
			'buttons' => [
				[
					'url'   => 'https://' . $domain,
					// Translators: %s is the user's e-mail domain.
					'label' => sprintf( __( 'Open %s', 'wp-job-manager-alerts' ), $domain ),
				],
			],
		];

		$this->redirect( $alert_message );

	}

	/**
	 * Handles the action to edit an alert.
	 *
	 * @param Alert $alert Current alert.
	 */
	private function handle_edit_alert( Alert $alert ) {
		$alert_data = $this->get_alert_form_data();

		if ( empty( $alert_data['alert_name'] ) ) {
			$alert_data['alert_name'] = self::generate_alert_name( $alert_data );
		}

		$alert->update( $alert_data );

		// translators: %s is the alert name.
		$alert_message = sprintf( __( '%s: Alert updated.', 'wp-job-manager-alerts' ), $alert->get_name() );

		$this->redirect( $alert_message );
	}

	/**
	 * Redirect after action is successful.
	 *
	 * @param string $message Feedback message.
	 *
	 * @return void
	 */
	private function redirect( $message = null ) {
		$url = remove_query_arg(
			[
				'action',
				'alert_id',
				'_wpnonce',
				'alert_job_type',
				'alert_location',
				'alert_cats',
				'alert_keyword',
				'alert_regions',
				'token',
			]
		);

		$alert_notice = Notice::success( $message );

		Redirect_Message::redirect( $url, $alert_notice, 'updated' );

	}

	/**
	 * Get alert message set by the action handler.
	 *
	 * @return string|null
	 */
	private function get_alert_message(): ?string {
		$alert_message = $this->alert_message;
		if ( ! $alert_message ) {
			$alert_message = Redirect_Message::get_message( 'updated' );
		}

		return $alert_message;
	}

	/**
	 * Get the input data for an alert from an add alert or edit alert form.
	 *
	 * @return array
	 */
	private function get_alert_form_data() {

		return [
			'alert_name'       => self::get_text_field( 'alert_name' ),
			'alert_email'      => self::get_text_field( 'alert_email' ),
			'alert_keyword'    => self::get_text_field( 'alert_keyword' ),
			'alert_location'   => self::get_text_field( 'alert_location' ),
			'alert_frequency'  => self::get_text_field( 'alert_frequency' ),
			'alert_cats'       => self::get_array_field( 'alert_cats' ),
			'alert_regions'    => self::get_array_field( 'alert_regions' ),
			'alert_tags'       => self::get_array_field( 'alert_tags' ),
			'alert_job_type'   => self::get_array_field( 'alert_job_type' ),
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Check nonce in action handler.
			'alert_permission' => isset( $_REQUEST['alert_permission'] ),
		];

		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get sanitized text input.
	 *
	 * @param string $key Input name.
	 */
	private static function get_text_field( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Check nonce in action handler.
		return isset( $_REQUEST[ $key ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) ) : null;
	}

	/**
	 * Get sanitized array input.
	 *
	 * @param string $key Input name.
	 *
	 * @return array
	 */
	private static function get_array_field( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Check nonce in action handler.
		return isset( $_REQUEST[ $key ] ) ? array_filter( array_map( 'absint', (array) $_REQUEST[ $key ] ) ) : null;
	}

	/**
	 * Get the management actions for an alert.
	 *
	 * @param Alert $alert
	 *
	 * @return array|mixed
	 */
	public static function get_alert_actions( Alert $alert ) {
		/**
		 * Filters the management actions available for an alert.
		 *
		 * @param array    $actions The actions.
		 * @param \WP_Post $post The alert post.
		 * @param Alert    $alert The alert post model.
		 */
		$actions = apply_filters(
			'job_manager_alert_actions',
			[
				'view'          => [
					'label' => __( 'Results', 'wp-job-manager-alerts' ),
					'nonce' => false,
				],
				'email'         => [
					'label' => __( 'Send&nbsp;Now', 'wp-job-manager-alerts' ),
					'nonce' => true,
				],
				'edit'          => [
					'label' => __( 'Edit', 'wp-job-manager-alerts' ),
					'nonce' => false,
				],
				'toggle_status' => [
					'label' => $alert->is_enabled() ? __( 'Disable', 'wp-job-manager-alerts' ) : __( 'Enable', 'wp-job-manager-alerts' ),
					'nonce' => true,
				],
				'delete'        => [
					'label' => __( 'Delete', 'wp-job-manager-alerts' ),
					'nonce' => true,
				],
			],
			$alert->get_post(),
			$alert
		);

		foreach ( $actions as $key => &$action ) {
			$action_url = add_query_arg(
				[
					'action'   => $key,
					'alert_id' => $alert->ID,
					'updated'  => null,
				]
			);

			if ( $action['nonce'] ) {
				$action_url = wp_nonce_url( $action_url, 'job_manager_alert_actions' );
			}

			$action['url'] = $action_url;
		}

		return $actions;

	}

	/**
	 * Generate alert name from keyword, location and search terms.
	 *
	 * @param array $alert_data Alert input data.
	 *
	 * @return string The generated alert name.
	 */
	public static function generate_alert_name( array $alert_data ): string {
		$alert_name = [];
		if ( ! empty( $alert_data['alert_keyword'] ) ) {
			$alert_name[] = trim( $alert_data['alert_keyword'] );
		}
		if ( ! empty( $alert_data['alert_location'] ) ) {
			$alert_name[] = trim( $alert_data['alert_location'] );
		}
		if ( ! empty( $alert_data['alert_cats'] ) ) {
			$cats         = Post_Types::get_terms( array_slice( $alert_data['alert_cats'], 0, 3 ) );
			$alert_name[] = implode( ', ', $cats );
		}
		if ( ! empty( $alert_data['alert_tags'] ) ) {
			$tags         = Post_Types::get_terms( array_slice( $alert_data['alert_tags'], 0, 3 ) );
			$alert_name[] = implode( ', ', $tags );
		}
		if ( empty( $alert_name ) ) {
			$alert_name[] = __( 'All Jobs', 'wp-job-manager-alerts' );
		}

		$alert_name = array_slice( $alert_name, 0, 3 );
		$alert_name = mb_convert_case( implode( ', ', $alert_name ), MB_CASE_TITLE );

		return $alert_name;
	}

	/**
	 * Get the email of the current user or guest.
	 *
	 * @return string
	 */
	public static function get_user_email() {
		$user = wp_get_current_user();
		if ( ! empty( $user->ID ) ) {
			return $user->user_email;
		} else {
			$guest_user = Guest_Session::get_current_guest();

			if ( false !== $guest_user ) {
				return $guest_user->user_email;
			}
		}

		return null;
	}

	/**
	 * Get the URL of the alerts page.
	 *
	 * @return string
	 */
	public static function get_page_url() {
		return get_permalink( Settings::instance()->get_alerts_page() );
	}
}
