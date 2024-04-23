<?php
/**
 * Reactions emotions picker.
 *
 * @since   2.4.50
 *
 * @package BuddyBossPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emotion picker class.
 */
class BB_Reactions_Picker {

	/**
	 * Class instance.
	 *
	 * @since 2.4.50
	 *
	 * @var $instance
	 */
	public static $instance;

	/**
	 * Emotion picker constructor.
	 *
	 * @since 2.4.50
	 */
	public function __construct() {
		add_action( 'wp_ajax_bbpro_icon_picker_upload', array( $this, 'upload_custom_icon' ) );
		add_action( 'wp_ajax_bbpro_delete_custom_icon', array( $this, 'delete_custom_icon' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 9 );
	}

	/**
	 * Get the instance of the class.
	 *
	 * @since 2.4.50
	 *
	 * @return BB_Reactions_Picker
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Load scripts on admin side.
	 *
	 * @since 2.4.50
	 */
	public function enqueue_scripts() {

		$page        = ! empty( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_tab = ! empty( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'bp-settings' !== $page || 'bp-reactions' !== $current_tab ) {
			return;
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'bbpro_croppie', bb_reaction_url( '/assets/libs/croppie/croppie' . $min . '.js' ), array( 'jquery' ), '2.6.4', true );
		wp_enqueue_style( 'bbpro_croppie', bb_reaction_url( '/assets/libs/croppie/croppie' . $min . '.css' ), array(), '2.6.4' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'bb-emotion-picker', bb_reaction_url( '/assets/js/admin/bb-emotion-picker' . $min . '.js' ), array( 'wp-util', 'wp-color-picker' ), bb_platform_pro()->version );

		$icons_path    = $this->bb_emojis_icon_url();
		$bb_icons_path = $this->bb_icons_url();

		// Localize.
		wp_localize_script(
			'bb-emotion-picker',
			'bbEmotionsEditor',
			array(
				'ajaxurl'                => admin_url( 'admin-ajax.php' ),
				'siteurl'                => site_url(),
				'resturl'                => get_rest_url(),
				'icon_path'              => $icons_path,
				'bb_icon_path'           => $bb_icons_path,
				'invalid_upload_notice'  => esc_html__( 'Unsupported image format. You must provide an image in an accepted format (PNG).', 'buddyboss-pro' ),
				'max_upload_size_notice' => esc_html__( 'Sorry - you\'re not allowed to upload icon bigger than 400KB.', 'buddyboss-pro' ),
				'no_data_found'          => esc_html__( 'No data found.', 'buddyboss-pro' ),
				'icon_label_required'    => esc_html__( 'Emotion label is required.', 'buddyboss-pro' ),
				'icon_label__length'     => esc_html__( 'Emotion label characters should be within limit.', 'buddyboss-pro' ),
				'upload_error_notice'    => esc_html__( 'Error while uploading, please try again.', 'buddyboss-pro' ),
			)
		);

		add_action( 'admin_footer', array( $this, 'load_emotion_picker_template' ) );
	}

	/**
	 * Load emotion picker related templates.
	 *
	 * @since 2.4.50
	 */
	public function load_emotion_picker_template() {
		include bb_reaction_path( 'templates/admin/emotion-picker/render-emotion-icons.php' );
	}

	/**
	 * Ajax handler to upload custom icons into upload folder.
	 *
	 * @since 2.4.50
	 *
	 * @return void
	 */
	public function upload_custom_icon() {
		$icons_size = 200;

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You don\'t have permission to upload a custom icon', 'buddyboss-pro' ) );
		}

		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'bbpro-upload-custom-icon' ) ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$dir = $this->bb_custom_icon_dir();
			$url = $this->bb_custom_icon_url();

			if ( empty( $_FILES['icon'] ) ) {
				wp_send_json_error( __( 'Icon not found on file object.', 'buddyboss-pro' ) );
			}

			$file_data = map_deep( $_FILES['icon'], 'sanitize_text_field' ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$file_data = BB_Reactions_File::read_file( $file_data['tmp_name'] );

			if ( empty( $file_data ) ) {
				wp_send_json_error( __( 'Icon you provided is invalid.', 'buddyboss-pro' ) );
			}

			$file_hash = uniqid() . '.png';
			$im        = imagecreatefromstring( $file_data );

			if ( ! $im ) {
				wp_send_json_error( __( 'Icon you provided is invalid.', 'buddyboss-pro' ) );
			}

			$width  = imagesx( $im );
			$height = imagesy( $im );

			if ( $width !== $icons_size || $height !== $icons_size ) {
				wp_send_json_error( __( 'Please provide an icon with correct size.', 'buddyboss-pro' ) );
			}

			$out_location = "{$dir}/{$file_hash}";

			// Save the image.
			$new = imagecreatetruecolor( $width, $height );
			imagecolortransparent( $new, imagecolorallocatealpha( $new, 0, 0, 0, 127 ) );
			imagealphablending( $new, false );
			imagesavealpha( $new, true );
			imagecopyresampled( $new, $im, 0, 0, 0, 0, $icons_size, $icons_size, $width, $height );
			imagepng( $new, $out_location );
			imagedestroy( $im );

			$name = "custom/{$file_hash}";

			wp_send_json_success(
				array(
					'url'  => "{$url}/{$file_hash}",
					'name' => $name,
					'id'   => basename( $name, '.png' ),
				)
			);
		}
	}

	/**
	 * Delete custom icon selected.
	 *
	 * @since 2.4.50
	 *
	 * @return void
	 */
	public function delete_custom_icon() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( "You don't have permission to delete a custom icon", 'buddyboss-pro' ) );
		}

		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'bbpro-delete-custom-icon' ) ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$task       = ! empty( $_POST['task'] ) ? sanitize_text_field( wp_unslash( $_POST['task'] ) ) : '';
			$icon_data_val  = ! empty( $_POST['elm1_data_val'] ) ? sanitize_text_field( wp_unslash( $_POST['elm1_data_val'] ) ) : '';

			if ( empty( $icon_data_val ) ) {
				wp_send_json_error( __( 'Please choose custom icon to delete.', 'buddyboss-pro' ) );
			}

			$icon_data = explode( '/', $icon_data_val );
			$icon      = isset( $icon_data[1] ) ? $icon_data[1] : '';

			if ( empty( $icon ) ) {
				wp_send_json_error( __( 'Please choose custom icon to delete.', 'buddyboss-pro' ) );
			}

			if ( 'delete_custom_icon' === $task ) {
				$dir          = $this->bb_custom_icon_dir();
				$icon_path    = "{$dir}/{$icon}.png";
				$all_emotions = bb_load_reaction()->bb_get_reactions( 'emotions', false );

				$is_icon_assigned = false;
				if ( ! empty( $all_emotions ) ) {
					$all_emotions = array_column( $all_emotions, null, 'id' );

					foreach ( $all_emotions as $emotion ) {
						if (
							'custom' === $emotion['type'] &&
							$icon_data_val === $emotion['icon']
						) {
							$is_icon_assigned = true;
							break;
						}
					}
				}

				if ( $is_icon_assigned ) {
					wp_send_json_error( __( 'The icon is already assigned to another emotion. Please update first and then delete.', 'buddyboss-pro' ) );
				}

				if ( file_exists( $icon_path ) ) {
					wp_delete_file( $icon_path );
					wp_send_json_success( __( 'Icon deleted.', 'buddyboss-pro' ) );
				}
				wp_send_json_error( __( 'There is no icon for deleting.', 'buddyboss-pro' ) );
			}
			wp_send_json_error( __( 'There was a problem deleting the icon.', 'buddyboss-pro' ) );
		}

		wp_send_json_error( esc_html__( 'Security check failed.', 'buddyboss-pro' ) );
	}

	/**
	 * Emojis Icon dir path.
	 *
	 * @since 2.4.50
	 *
	 * @return string
	 */
	public function bb_emojis_icon_dir() {
		$bb_platform_pro = bb_platform_pro();

		return trailingslashit( $bb_platform_pro->reactions_dir ) . 'assets/icons/emojis/';
	}

	/**
	 * Emojis Icon url path.
	 *
	 * @since 2.4.50
	 *
	 * @return string
	 */
	public function bb_emojis_icon_url() {
		$bb_platform_pro = bb_platform_pro();

		return trailingslashit( $bb_platform_pro->reactions_url ) . 'assets/icons/emojis/';
	}

	/**
	 * BuddyBoss icon dir path.
	 *
	 * @since 2.4.50
	 *
	 * @return void
	 */
	public function bb_icons_dir() {
		$bb_platform_pro = bb_platform_pro();

		include trailingslashit( $bb_platform_pro->reactions_dir ) . '/assets/icons/bb-icons/';
	}

	/**
	 * BuddyBoss icon url path.
	 *
	 * @since 2.4.50
	 *
	 * @return string
	 */
	public function bb_icons_url() {
		$bb_platform_pro = bb_platform_pro();

		return trailingslashit( $bb_platform_pro->reactions_url ) . '/assets/icons/bb-icons/';
	}

	/**
	 * Returns the directory of custom icons.
	 *
	 * @since 2.4.50
	 *
	 * @return string
	 */
	public function bb_custom_icon_dir() {
		$upload_dir = wp_upload_dir();
		$dir        = $upload_dir['basedir'] . '/bb-reactions-media';
		if ( ! file_exists( $dir ) ) {
			BB_Reactions_File::create_dir( dirname( $dir ) );
			BB_Reactions_File::create_dir( $dir );
		}

		return $dir;
	}

	/**
	 * Returns the url of custom icons directory.
	 *
	 * @since 2.4.50
	 *
	 * @return string
	 */
	public function bb_custom_icon_url() {
		$upload_dir = wp_upload_dir();
		$dir        = $upload_dir['baseurl'] . '/bb-reactions-media';

		return $dir;
	}

	/**
	 * Return the list of custom icons uploaded.
	 *
	 * @since 2.4.50
	 *
	 * @return array
	 */
	public function bb_custom_icon_list() {
		$dir   = $this->bb_custom_icon_dir();
		$icons = glob( $dir . '/*.png' );
		$list  = array();
		$url   = $this->bb_custom_icon_url();

		if ( ! empty( $icons ) ) {
			foreach ( $icons as $icon ) {
				$icon = basename( $icon, '.png' );

				$list[] = array(
					'id'       => 'custom/' . $icon,
					'icon_url' => $url . '/' . $icon . '.png',
				);
			}
		}

		return $list;
	}

	/**
	 * Loads the bb icons category dropdown.
	 *
	 * @since 2.4.50
	 */
	public function render_bb_icons_category_dropdown() {
		$font_map = $this->bb_icon_font_map( 'groups' );
		?>
		<select class="bbpro-icon-category-filter-select">
			<option value="all"><?php esc_html_e( 'All Categories', 'buddyboss-pro' ); ?></option>
			<?php
			if ( ! empty( $font_map ) ) {
				foreach ( $font_map as $font_map_cat ) {
					echo sprintf( '<option value="%1$s">%2$s</option>', esc_attr( $font_map_cat['id'] ), esc_html( $font_map_cat['label'] ) );
				}
			}
			?>
		</select>
		<?php
	}

	/**
	 * Fetch bb icons data.
	 *
	 * @since 2.4.50
	 *
	 * @param string $key Array key.
	 *
	 * @return array
	 */
	public function bb_icon_font_map( $key = '' ) {
		global $bbpro_icons;

		$bb_platform_pro = bb_platform_pro();
		include trailingslashit( $bb_platform_pro->reactions_dir ) . 'assets/icons/bb-icons/font-map.php';

		return ! empty( $key ) ? ( $bbpro_icons[ $key ] ?? false ) : $bbpro_icons;
	}

	/**
	 * Get emojis list.
	 *
	 * @since 2.4.50
	 *
	 * @return array
	 */
	public function bb_get_emojis_list() {
		static $emoji_icons = array();
		if ( ! empty( $emoji_icons ) ) {
			return $emoji_icons;
		}

		$icons_path = $this->bb_emojis_icon_dir();

		$emoji_icons_data = BB_Reactions_File::read_file( "{$icons_path}emojis.json" );
		$emoji_icons_data = (array) json_decode( $emoji_icons_data, true );

		$emoji_icons = apply_filters( 'bb_get_emojis_list', $emoji_icons_data );

		return $emoji_icons;
	}

	/**
	 * Get emojis category list.
	 *
	 * @since 2.4.50
	 *
	 * @return array
	 */
	public function bb_get_emojis_category_list() {
		$emoji_icons = $this->bb_get_emojis_list();

		if ( empty( $emoji_icons ) ) {
			return array();
		}

		$emoji_categories = array_reduce(
			$emoji_icons,
			function ( $result, $icon ) {
				$result[ $icon['category'] ] = $icon['category'];

				return $result;
			},
			array()
		);

		return apply_filters( 'bb_get_emojis_category_list', array_keys( $emoji_categories ) );
	}

	/**
	 * Render the emoji category list in dropdown.
	 *
	 * @since 2.4.50
	 *
	 * @return void
	 */
	public function render_emoji_category_list() {
		$emoji_categories = $this->bb_get_emojis_category_list();

		?>
		<select class="bbpro-icon-category-filter-select" id="emoji-categories">
			<option value="all"><?php esc_html_e( 'All Categories', 'buddyboss-pro' ); ?></option>
			<?php
			foreach ( $emoji_categories as $emoji_category ) {
				echo sprintf( '<option value="%1$s">%2$s</option>', esc_attr( $emoji_category ), esc_html( $emoji_category ) );
			}
			?>
		</select>
		<?php
	}
}
