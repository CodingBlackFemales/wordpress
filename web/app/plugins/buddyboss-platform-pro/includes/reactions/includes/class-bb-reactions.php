<?php
/**
 * BuddyBoss Pro Reactions.
 *
 * @since   2.4.50
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp reaction class.
 *
 * @since 2.4.50
 */
class BB_Reactions {

	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	public static $instance;

	/**
	 * Unique ID for the reaction.
	 *
	 * @var string reaction.
	 *
	 * @since 2.4.50
	 */
	public $id = 'reactions';

	/**
	 * Reaction Constructor.
	 *
	 * @since 2.4.50
	 */
	public function __construct() {
		// Include the code.
		$this->includes();
		$this->setup_actions();

		// Instantiate the emotion picker class.
		BB_Reactions_Picker::instance();
	}

	/**
	 * Get the instance of the class.
	 *
	 * @return BB_Reactions
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Setup actions for reaction.
	 *
	 * @since 2.4.50
	 */
	public function setup_actions() {
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_script' ) );

		// Save settings.
		add_action( 'bp_admin_tab_setting_save', array( $this, 'bb_admin_reaction_setting_fields_save' ), 10, 1 );

		// Add Migration popup into footer
		add_action( 'bp_admin_tab_form_html', array( $this, 'bb_reaction_migration_popup' ), 10, 2 );
	}

	/**
	 * Enqueue admin related scripts and styles.
	 *
	 * @since 2.4.50
	 */
	public function enqueue_scripts_styles() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		if ( 'bp-reactions' === $current_tab ) {
			$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$rtl_css = is_rtl() ? '-rtl' : '';

			wp_enqueue_style( 'bb-reactions-admin', bb_reaction_url( '/assets/css/bb-reactions-admin' . $rtl_css . $min . '.css' ), array(), bb_platform_pro()->version );
			wp_enqueue_script( 'bb-reaction-admin', bb_reaction_url( '/assets/js/admin/bb-reaction-admin' . $min . '.js' ), array(), bb_platform_pro()->version ); // phpcs:ignore
			wp_localize_script(
				'bb-reaction-admin',
				'bbReactionAdminVars',
				array(
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'wizard_label'     => __( 'Migration wizard', 'buddyboss-pro' ),
					'migration_status' => bb_pro_reaction_get_migration_status(),
					'nonce'            => array(
						'check_delete_emotion'       => wp_create_nonce( 'bb-pro-check-delete-emotion' ),
						'footer_migration'           => wp_create_nonce( 'bb-pro-check-footer-migration' ),
						'check_migration'            => wp_create_nonce( 'bb-pro-check-reaction-migration' ),
						'dismiss_migration_notice'   => wp_create_nonce( 'bb-pro-reaction-dismiss-migration-notice' ),
						'migration_start_conversion' => wp_create_nonce( 'bb-pro-reaction-migration-start-conversion' ),
						'migration_stop_conversion'  => wp_create_nonce( 'bb-pro-reaction-migration-stop-conversion' ),
						'migration_do_later'         => wp_create_nonce( 'bb-pro-reaction-migration-do-later' ),
					),
				)
			);
		}
	}

	/**
	 * Enqueue related scripts and styles.
	 *
	 * @since 2.4.50
	 */
	public function enqueue_script() {
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'bb-reaction', bb_reaction_url( '/assets/js/bb-reaction' . $min . '.js'  ), array(), bb_platform_pro()->version );
		wp_localize_script(
			'bb-reaction',
			'bbReactionVars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Includes files.
	 *
	 * @param array $includes list of the files.
	 *
	 * @since 2.4.50
	 */
	public function includes( $includes = array() ) {

		$bb_platform_pro = bb_platform_pro();
		$slashed_path    = trailingslashit( $bb_platform_pro->reactions_dir );

		$includes = array(
			'cache',
			'functions',
			'filters',
			'actions',
		);

		// Loop through files to be included.
		foreach ( (array) $includes as $file ) {

			if ( empty( $this->bb_reaction_check_has_licence() ) ) {
				if ( in_array( $file, array( 'filters', 'rest-filters' ), true ) ) {
					continue;
				}
			}

			$paths = array(

				// Passed with no extension.
				'bb-' . $this->id . '-' . $file . '.php',
				'bb-' . $this->id . '/' . $file . '.php',

				// Passed with extension.
				$file,
				'bb-' . $this->id . '-' . $file,
				'bb-' . $this->id . '/' . $file,
			);

			foreach ( $paths as $path ) {
                // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( @is_file( $slashed_path . $path ) ) {
					require $slashed_path . $path;
					break;
				}
			}
		}
	}

	/**
	 * Function to return the default value if no licence.
	 *
	 * @param bool $has_access Whether has access.
	 *
	 * @since 2.4.50
	 *
	 * @return mixed Return the default.
	 */
	protected function bb_reaction_check_has_licence( $has_access = true ) {

		if ( ! bbp_pro_is_license_valid() ) {
			return false;
		}

		return $has_access;

	}

	/**
	 * Save Reaction emotions to DB.
	 *
	 * @since 2.4.50
	 *
	 * @param string $current_tab Current setting tab.
	 */
	public function bb_admin_reaction_setting_fields_save( $current_tab ) {

		if ( 'bp-reactions' !== $current_tab ) {
			return;
		}

		if ( ! check_admin_referer( $current_tab . '-options' ) ) {
			return;
		}

		$bb_reaction      = BB_Reaction::instance();
		$reaction_mode    = ! empty( $_POST['bb_reaction_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_reaction_mode'] ) ) : '';
		$migration_action = ! empty( $_POST['migration_action'] ) ? sanitize_text_field( wp_unslash( $_POST['migration_action'] ) ) : 'no';
		$action_status    = array( 'updated' => true );

		// Add and update reaction post and settings.
		if (
			'no' === $migration_action &&
			'emotions' === $reaction_mode &&
			class_exists( 'BB_Reaction' )
		) {
			$new_emotion_ids = array();
			if ( ! empty( $_POST['reaction_items'] ) ) {

				$reaction_items  = $_POST['reaction_items'];
				$reaction_checks = ! empty( $_POST['reaction_checks'] ) ? array_map( 'sanitize_text_field', $_POST['reaction_checks'] ) : array();

				$index = 1;
				foreach ( $reaction_items as $id => $reaction_item ) {
					$reaction_item = array_map( 'sanitize_text_field', json_decode( wp_unslash( $reaction_item ), true ) );

					$reaction_item['mode']       = 'emotions';
					$reaction_item['menu_order'] = $index++;

					// If reaction id exists, update the reaction otherwise create a new one.
					if ( ! empty( $reaction_item['id'] ) ) {
						$reaction_id                        = absint( $reaction_item['id'] );
						$reaction_item['is_emotion_active'] = ! empty( $reaction_checks[ $id ] );
						$bb_reaction->bb_update_reaction( $reaction_id, $reaction_item );
					} else {
						unset( $reaction_item['id'] );
						$reaction_item['is_emotion_active'] = true;

						$reaction_id = $bb_reaction->bb_add_reaction( $reaction_item );
					}

					$new_emotion_ids[] = $reaction_id;
				}
			}

			$all_emotions       = bb_pro_get_reactions( 'emotions', false );
			$all_emotion_ids    = ! empty( $all_emotions ) ? array_column( $all_emotions, 'id' ) : array();
			$all_emotion_names  = ! empty( $all_emotions ) ? array_column( array_filter( $all_emotions ), 'icon_text', 'id' ) : array();
			$remove_emotion_ids = array_diff( $all_emotion_ids, $new_emotion_ids );

			if ( ! empty( $remove_emotion_ids ) ) {

				$deleted_emotion_names = '';
				foreach ( $remove_emotion_ids as $reaction_id ) {
					$bb_reaction->bb_remove_reaction( $reaction_id );
					$deleted_emotion_names .= ! empty( $all_emotion_names[ $reaction_id ] ) ? $all_emotion_names[ $reaction_id ] . ', ' : '';
				}

				// Register a background job for delete the emotion data.
				bb_pro_reaction_dispatch_migration( $remove_emotion_ids, 'delete' );

				// Set emotion delete message.
				$action_status         = array( 'updated' => 'emotion_deleted' );
				$deleted_emotion_names = rtrim( $deleted_emotion_names, ', ' );
				if ( ! empty( $deleted_emotion_names ) ) {
					$message = sprintf(
					/* translators: Emotion names with comma separator. */
						__( 'The %s Emotion was successfully deleted.', 'buddyboss-pro' ),
						'<b>' . $deleted_emotion_names . '</b>'
					);
					set_transient( $action_status['updated'], $message );
				}
			}

		} else if ( in_array( $migration_action, array( 'footer', 'switch' ), true ) ) {

			// Defined variables.
			$from_emotions  = array();
			$to_emotions    = 0;
			$migration_data = bb_pro_reaction_get_migration_action();
			$likes_id       = (int) $bb_reaction->bb_reactions_get_like_reaction_id();

			// Get posted values form pop-up.
			$checkbox_emotions = array();
			if ( isset( $_POST['from_all_emotions'] ) ) {
				$all_emotions      = bb_pro_get_reactions( 'emotions', false );
				$checkbox_emotions = ! empty( $all_emotions ) ? array_column( $all_emotions, 'id' ) : array();
			}

			if ( isset( $_POST['from_reactions'] ) ) {
				$from_reactions    = ! empty( $_POST['from_reactions'] ) ? array_map( 'sanitize_text_field', $_POST['from_reactions'] ) : array();
				$checkbox_emotions = array_merge( $checkbox_emotions, $from_reactions );
			}

			$select_emotions = (int) ( ! empty( $_POST['to_reactions'] ) ? sanitize_text_field( wp_unslash( $_POST['to_reactions'] ) ) : 0 );


			// Arrange values based on type of migration.
			if ( 'footer' === $migration_action ) {
				$reaction_mode = bb_get_reaction_mode();
				$from_emotions = $checkbox_emotions;

				$to_emotions = $select_emotions;
				if ( 'likes' === $reaction_mode ) {
					$to_emotions = $likes_id;
				}

				$migration_data = array(
					'action' => $reaction_mode,
					'type'   => 'footer',
				);

			} elseif ( 'switch' === $migration_action ) {
				if ( ! empty( $migration_data ) && 'like_to_emotions_action' === $migration_data['action'] ) {
					$from_emotions = array( $likes_id );
					$to_emotions   = $select_emotions;
				} elseif ( ! empty( $migration_data ) && 'emotions_to_like_action' === $migration_data['action'] ) {
					$from_emotions = $checkbox_emotions;
					$to_emotions   = $likes_id;
				}
			}

			if ( ! empty( $from_emotions ) && ! empty( $to_emotions ) ) {
				$from_emotions = array_filter( array_diff( $from_emotions, array( $to_emotions ) ) );

				if ( ! empty( $from_emotions ) ) {

					// Updated current migration status.
					$migration_data['from_emotions']   = $from_emotions;
					$migration_data['to_emotions']     = $to_emotions;
					$migration_data['status']          = 'running';
					$migration_data['total_reactions'] = bb_load_reaction()->bb_get_user_reactions_count(
						array(
							'reaction_id' => $from_emotions
						)
					);

					if ( isset( $migration_data['total_reactions'] ) && 0 < $migration_data['total_reactions'] ) {
						if ( (int) $likes_id === (int) $to_emotions ) {
							$migration_data['from_emotions_name'] = __( 'reactions', 'buddyboss-pro' );
							$migration_data['to_emotions_name']   = __( 'Likes', 'buddyboss-pro' );
						} else {
							$migration_data['from_emotions_name'] = __( 'Likes', 'buddyboss-pro' );
							$migration_data['to_emotions_name']   = '';

							$all_emotions = bb_pro_get_reactions( 'emotions', false );
							$all_emotions = array_column( $all_emotions, 'icon_text', 'id' );
							if (
								! empty( $all_emotions ) &&
								! empty( $all_emotions[ $to_emotions ] )
							) {
								$migration_data['to_emotions_name'] = $all_emotions[ $to_emotions ];
							}
						}

						bb_pro_reaction_update_migration_action( $migration_data );

						// Show site-wide notice.
						bp_update_option( 'bb_pro_reaction_migration_notice', 'yes' );

						// Register a background job for migrate the emotion data.
						bb_pro_reaction_dispatch_migration( $from_emotions, $to_emotions );
					} else {
						// If no reaction found then delete the migration notice.
						bb_pro_reaction_delete_migration();
					}
				}
			}

			// No success message show.
			$action_status = array( 'updated' => 'no_message' );
		}

		bp_core_redirect( bp_core_admin_setting_url( $current_tab, $action_status ) );
	}

	/**
	 * Empty callback of blocks.
	 *
	 * @since 2.4.50
	 */
	public function bb_reaction_migration_popup( $tab_name, $tab ) {
		// Validate the current screen.
		if ( empty( $tab_name ) || 'bp-reactions' !== $tab_name ) {
			return;
		}
		?>
		<input type="hidden" name="migration_action" id="migration_action" value="no">
		<div id="bbpro_migration_wizard" class="bbpro-modal-box bbpro-modal-box_detached">
			<div class="media-modal-backdrop"></div>
			<div class="media-modal">
				<div class="media-modal-content">
					<div class="bbpro-modal-box__header">
						<h3 class="wizard-label"><?php echo __( 'Migration wizard', 'buddyboss-pro' ); ?></h3>
						<button type="button" id="bbpro_icon_modal_close" class="media-modal-close">
							<span class="media-modal-icon">
								<span class="screen-reader-text"><?php echo __( 'Close', 'buddyboss-pro' ); ?></span>
							</span>
						</button>
					</div>
					<div class="modal-content">
						<div class="bbpro-modal-box_loader">
							<span class="bb-icons bb-icon-spinner animate-spin"></span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div id="bbpro_reaction_delete_confirmation" class="bbpro-modal-box bbpro_reaction_delete_confirmation bbpro-modal-box_detached">
			<div class="media-modal-backdrop"></div>
			<div class="media-modal">
				<div class="media-modal-content">
					<div class="bbpro-modal-box__header">
						<h3><?php echo __( 'Delete Emotion', 'buddyboss-pro' ); ?></h3>
						<button type="button" id="bbpro_icon_modal_close" class="media-modal-close">
							<span class="media-modal-icon">
								<span class="screen-reader-text"><?php echo __( 'Close', 'buddyboss-pro' ); ?></span>
							</span>
						</button>
					</div>
					<div class="bb-reaction-delete-modal-content">
						<div class="bb-reaction-delete-modal__content">
							<div class="bbpro-modal-box_loader">
								<span class="bb-icons bb-icon-spinner animate-spin"></span>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
		<?php
	}
}
