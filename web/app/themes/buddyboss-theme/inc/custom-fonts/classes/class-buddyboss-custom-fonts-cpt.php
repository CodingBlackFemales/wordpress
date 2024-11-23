<?php
/**
 * BuddyBoss Custom Fonts Taxonomy
 *
 * @since  1.2.10
 * @package BuddyBoss_Custom_Fonts
 */

namespace BuddyBossTheme;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\BuddyBossTheme\BuddyBoss_Custom_Fonts_CPT' ) ) :

	/**
	 * BuddyBoss_Custom_Fonts_CPT
	 */
	class BuddyBoss_Custom_Fonts_CPT {
		/**
		 * Instance of BuddyBoss_Custom_Fonts_CPT
		 *
		 * @since  1.2.10
		 * @var (Object) BuddyBoss_Custom_Fonts_CPT
		 */
		private static $_instance = null;

		/**
		 * Fonts
		 *
		 * @since  1.2.10
		 * @var (string) $fonts
		 */
		public static $fonts = null;

		/**
		 * Capability required for this menu to be displayed
		 *
		 * @since  1.0.0
		 * @var (string) $capability
		 */
		public static $capability = 'edit_theme_options';

		/**
		 * Register CPT
		 *
		 * @since  1.0.0
		 * @var (string) $register_cpt
		 */
		public static $register_cpt_slug = 'buddyboss_fonts';

		/**
		 * Instance of BuddyBoss_Custom_Fonts_CPT.
		 *
		 * @return object Class object.
		 * @since  1.2.10
		 */
		public static function get_instance() {
			if ( ! isset( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Constructor.
		 *
		 * @since  1.2.10
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'create_custom_fonts_cpt' ) );
			add_action( 'add_meta_boxes_' . $this::$register_cpt_slug, array( $this, 'add_meta_box' ) );
			add_action( 'save_post_' . $this::$register_cpt_slug, array( $this, 'save_post_meta' ), 10, 3 );
			add_action( 'admin_head', array( $this, 'open_theme_options_tab' ) );
		}
		/**
		 * Register CPT
		 *
		 * @since 1.2.10
		 */
		public function create_custom_fonts_cpt() {
			$labels = array(
				'name'               => esc_html__( 'Custom Fonts', 'buddyboss-theme' ),
				'menu_name'          => esc_html__( 'All Custom Fonts', 'buddyboss-theme' ),
				'singular_name'      => esc_html__( 'Font', 'buddyboss-theme' ),
				'all_items'          => esc_html__( 'All Fonts', 'buddyboss-theme' ),
				'add_new'            => esc_html__( 'New Font', 'buddyboss-theme' ),
				'add_new_item'       => esc_html__( 'Create New Font', 'buddyboss-theme' ),
				'edit'               => esc_html__( 'Upload', 'buddyboss-theme' ),
				'edit_item'          => esc_html__( 'Edit Font', 'buddyboss-theme' ),
				'new_item'           => esc_html__( 'New Font', 'buddyboss-theme' ),
				'view'               => esc_html__( 'View Font', 'buddyboss-theme' ),
				'view_item'          => esc_html__( 'View Font', 'buddyboss-theme' ),
				'search_items'       => esc_html__( 'Search Fonts', 'buddyboss-theme' ),
				'not_found'          => esc_html__( 'No fonts found', 'buddyboss-theme' ),
				'not_found_in_trash' => esc_html__( 'No fonts found in trash', 'buddyboss-theme' ),
				'parent_item_colon'  => esc_html__( 'Parent Font:', 'buddyboss-theme' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => $this::$register_cpt_slug ),
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title' ),
			);

			register_post_type(
				$this::$register_cpt_slug,
				$args
			);
		}

		/**
		 * runs on font post save and calls the font type handler save meta method
		 *
		 * @param int      $post_id
		 * @param \WP_Post $post
		 * @param bool     $update
		 *
		 * @return mixed
		 */
		public function save_post_meta( $post_id, $post, $update ) {
			// If this is an autosave, our form has not been submitted,
			// so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			// Check the user's permissions.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}

			// Check if our nonce is set.
			if ( ! isset( $_POST[ self::$register_cpt_slug . '_nonce' ] ) ) {
				return $post_id;
			}

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST[ self::$register_cpt_slug . '_nonce' ], self::$register_cpt_slug ) ) {
				return $post_id;
			}

			if ( buddyboss_theme_get_theme_sudharo() ) {
				return $post_id;
			}

			update_post_meta( $post_id, 'buddyboss_font_face', $_POST['font_face'] );
		}

		/**
		 * Add font face metabox for cpt
		 *
		 * @since 1.2.10
		 */
		public function add_meta_box() {
			add_meta_box(
				'buddyboss-fonts-metabox',
				__( 'Font Variations', 'buddyboss-theme' ),
				array( $this, 'render_metabox' ),
				$this::$register_cpt_slug,
				'normal',
				'default'
			);

			add_meta_box(
				'buddyboss-fonts-description',
				__( 'Font description', 'buddyboss-theme' ),
				array( $this, 'render_description_metabox' ),
				$this::$register_cpt_slug,
				'normal',
				'default'
			);

			if ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) && buddypress()->buddyboss ) {
				add_meta_box(
					'buddyboss-fonts-tutorial',
					__( 'Tutorial', 'buddyboss-theme' ),
					array( $this, 'render_tutorial_metabox' ),
					$this::$register_cpt_slug,
					'side',
					'default'
				);
			}
		}

		/**
		 * Render description for the fonts cpt
		 *
		 * @since 1.2.10
		 */
		public function render_description_metabox() {
			?>
			<p class="description">
				<?php echo sprintf( __( 'You will be able to select these fonts in the %s section in theme options.', 'buddyboss-theme' ), '<a href="' . admin_url( 'admin.php?page=buddyboss_theme_options&tab=4' ) . '">' . esc_html__( 'Typography', 'buddyboss-theme' ) . '</a>' ); ?>
				<?php echo sprintf( __( 'Try %s for downloading free web fonts.', 'buddyboss-theme' ), '<a href="https://www.fontsquirrel.com/fonts/list/find_fonts?filter%5Blicense%5D%5B0%5D=web" target="_blank">' . esc_html__( 'Font Squirrel', 'buddyboss-theme' ) . '</a>' ); ?>
			</p>

			<?php
		}

		/**
		 * Render tutorial metabox for the fonts cpt
		 *
		 * @since 1.2.10
		 */
		public function render_tutorial_metabox() {
			?>
			<a href="
			<?php
			echo bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => '85240',
					),
					'admin.php'
				)
			);
			?>
			" class="button"><?php esc_html_e( 'View Tutorial', 'buddyboss-theme' ); ?></a>
			<?php
		}

		/**
		 * Render metabox for the fonts cpt
		 *
		 * @since 1.2.10
		 *
		 * @param $post
		 */
		public function render_metabox( $post ) {
			wp_enqueue_media();
			$minified_js = buddyboss_theme_get_option( 'boss_minified_js' );
			$minjs       = $minified_js ? '.min' : '';

			$minified_css = buddyboss_theme_get_option( 'boss_minified_css' );
			$mincss       = $minified_css ? '.min' : '';

			wp_enqueue_style( 'buddyboss-admin-css', get_template_directory_uri() . '/inc/admin/assets/css/boss-custom-fonts' . $mincss . '.css' );
			wp_enqueue_script( 'buddyboss-admin-js', get_template_directory_uri() . '/assets/js/admin' . $minjs . '.js', array( 'jquery' ), buddyboss_theme()->version() );
			$js_id = 'buddyboss_repeater_' . dechex( rand() );

			$font_face_data = get_post_meta( $post->ID, 'buddyboss_font_face', true );

			if ( buddyboss_theme_get_theme_sudharo() ) {
				$licensed_section_url = is_multisite() ? network_admin_url( 'admin.php?page=buddyboss-updater' ) : admin_url( 'admin.php?page=buddyboss-updater' );
				?>
				<div class="unlicensed-theme">
					<div class="block">
						<p><?php echo sprintf( __( 'Warning: Access Restricted. Please enable BuddyBoss Theme License key <a href="%s">here</a> and try again.', 'buddyboss-theme' ), esc_url( $licensed_section_url ) ); ?></p>
					</div>
				<?php
			}
			?>
			<div class="buddyboss-metabox-content">
				<div class="buddyboss-field font_face buddyboss-field-repeater">
					<?php if ( ! empty( $font_face_data ) ) : ?>
						<?php foreach ( $font_face_data as $key => $font_face ) : ?>
							<div class="repeater-block">
								<div class="repeater-content form-table">
									<div class="repeater-content-top">
										<div class="buddyboss-field font_face buddyboss-field-select">
											<p class="buddyboss-field-label">
												<label for="font_face[<?php echo $key; ?>][font_weight]"><?php esc_html_e( 'Weight:', 'buddyboss-theme' ); ?></label>
											</p>
											<select name="font_face[<?php echo $key; ?>][font_weight]" id="font_face[<?php echo $key; ?>][font_weight]" class="font_weight">
												<option <?php echo '100' === $font_face['font_weight'] ? 'selected="selected"' : ''; ?> value="100"><?php esc_html_e( '100 - Thin', 'buddyboss-theme' ); ?></option>
												<option <?php echo '200' === $font_face['font_weight'] ? 'selected="selected"' : ''; ?> value="200"><?php esc_html_e( '200 - Extra Light', 'buddyboss-theme' ); ?></option>
												<option <?php echo '300' === $font_face['font_weight'] ? 'selected="selected"' : ''; ?> value="300"><?php esc_html_e( '300 - Light', 'buddyboss-theme' ); ?></option>
												<option <?php echo '400' === $font_face['font_weight'] ? 'selected="selected"' : ''; ?> value="400"><?php esc_html_e( '400 - Normal', 'buddyboss-theme' ); ?></option>
												<option <?php echo '500' === $font_face['font_weight'] ? 'selected="selected"' : ''; ?> value="500"><?php esc_html_e( '500 - Medium', 'buddyboss-theme' ); ?></option>
												<option <?php echo '600' === $font_face['font_weight'] ? 'selected="selected"' : ''; ?> value="600"><?php esc_html_e( '600 - Semi Bold', 'buddyboss-theme' ); ?></option>
												<option <?php echo '700' === $font_face['font_weight'] ? 'selected="selected"' : ''; ?> value="700"><?php esc_html_e( '700 - Bold', 'buddyboss-theme' ); ?></option>
												<option <?php echo '800' === $font_face['font_weight'] ? 'selected="selected"' : ''; ?> value="800"><?php esc_html_e( '800 - Extra Bold', 'buddyboss-theme' ); ?></option>
												<option <?php echo '900' === $font_face['font_weight'] ? 'selected="selected"' : ''; ?> value="900"><?php esc_html_e( '900 - Black', 'buddyboss-theme' ); ?></option>
											</select>
											<p class="buddyboss-field-label">
												<label for="font_face[<?php echo $key; ?>][font_style]"><?php esc_html_e( 'Style:', 'buddyboss-theme' ); ?></label>
											</p>
											<select name="font_face[<?php echo $key; ?>][font_style]" id="font_face[<?php echo $key; ?>][font_style]" class="font_style">
												<option <?php echo 'normal' === $font_face['font_style'] ? 'selected="selected"' : ''; ?> value="normal"><?php esc_html_e( 'Normal', 'buddyboss-theme' ); ?></option>
												<option <?php echo 'italic' === $font_face['font_style'] ? 'selected="selected"' : ''; ?> value="italic"><?php esc_html_e( 'Italic', 'buddyboss-theme' ); ?></option>
											</select>
											<p class="buddyboss-field-label">
												<label><?php esc_html_e( 'Font Files:', 'buddyboss-theme' ); ?></label>
											</p>
											<span class="buddyboss-repeater-tool-btn button-secondary close-repeater-row" title="<?php esc_attr_e( 'Close', 'buddyboss-theme' ); ?>">
												<?php esc_html_e( 'Close', 'buddyboss-theme' ); ?>
											</span>
											<span class="buddyboss-repeater-tool-btn button-secondary toggle-repeater-row" title="<?php esc_attr_e( 'Edit', 'buddyboss-theme' ); ?>">
												<?php esc_html_e( 'Edit', 'buddyboss-theme' ); ?>
											</span>
										</div>
										<div class="buddyboss-field font_face buddyboss-field-toolbar">
											<span class="buddyboss-repeater-tool-btn remove-repeater-row" data-confirm="<?php esc_attr_e( 'Are you sure?', 'buddyboss-theme' ); ?>" title="<?php esc_attr_e( 'Delete Font Variation', 'buddyboss-theme' ); ?>">
												<?php esc_html_e( 'Delete Font Variation', 'buddyboss-theme' ); ?>
											</span>
										</div>
									</div>
									<div class="repeater-content-bottom" style="display: none;">
										<div class="buddyboss-field font_face buddyboss-field-file">
											<p class="buddyboss-field-label">
												<label for="font_face[<?php echo $key; ?>][woff]file"><?php esc_html_e( 'WOFF File', 'buddyboss-theme' ); ?></label>
											</p>
											<input type="hidden" name="font_face[<?php echo $key; ?>][woff][id]" value="<?php echo ! empty( $font_face['woff']['id'] ) ? $font_face['woff']['id'] : ''; ?>" />
											<input type="text" name="font_face[<?php echo $key; ?>][woff][url]" value="<?php echo ! empty( $font_face['woff']['url'] ) ? $font_face['woff']['url'] : ''; ?>" placeholder="<?php esc_attr_e( 'The Web Open Font Format, Used by Modern Browsers', 'buddyboss-theme' ); ?>" class="buddyboss-field-input" />
											<input type="button" class="button buddyboss-button <?php echo ! empty( $font_face['woff']['id'] ) ? 'buddyboss-upload-clear-btn' : 'buddyboss-upload-btn'; ?>" name="font_face[<?php echo $key; ?>][woff]" id="font_face[<?php echo $key; ?>][woff]" value="<?php echo ! empty( $font_face['woff']['id'] ) ? esc_html__( 'Remove', 'buddyboss-theme' ) : esc_html__( 'Upload', 'buddyboss-theme' ); ?>" data-preview_anchor="none" data-mime_type="font/woff,application/font-woff,application/x-font-woff,application/octet-stream" data-ext="woff" data-upload_text="<?php esc_attr_e( 'Upload', 'buddyboss-theme' ); ?>" data-remove_text="<?php esc_attr_e( 'Remove', 'buddyboss-theme' ); ?>" data-box_title="<?php esc_attr_e( 'Upload font .woff file', 'buddyboss-theme' ); ?>" data-box_action="<?php esc_attr_e( 'Select .woff file', 'buddyboss-theme' ); ?>" />
										</div>
										<div class="buddyboss-field font_face buddyboss-field-file">
											<p class="buddyboss-field-label">
												<label for="font_face[<?php echo $key; ?>][woff2]file"><?php esc_html_e( 'WOFF2 File', 'buddyboss-theme' ); ?></label>
											</p>
											<input type="hidden" name="font_face[<?php echo $key; ?>][woff2][id]" value="<?php echo ! empty( $font_face['woff2']['id'] ) ? $font_face['woff2']['id'] : ''; ?>" />
											<input type="text" name="font_face[<?php echo $key; ?>][woff2][url]" value="<?php echo ! empty( $font_face['woff2']['url'] ) ? $font_face['woff2']['url'] : ''; ?>" placeholder="<?php esc_html_e( 'The Web Open Font Format 2, Used by Modern Browsers', 'buddyboss-theme' ); ?>" class="buddyboss-field-input" />
											<input type="button" class="button buddyboss-button <?php echo ! empty( $font_face['woff2']['id'] ) ? 'buddyboss-upload-clear-btn' : 'buddyboss-upload-btn'; ?>" name="font_face[<?php echo $key; ?>][woff2]" id="font_face[<?php echo $key; ?>][woff2]" value="<?php echo ! empty( $font_face['woff2']['id'] ) ? esc_html__( 'Delete', 'buddyboss-theme' ) : esc_html__( 'Upload', 'buddyboss-theme' ); ?>" data-preview_anchor="none" data-mime_type="font/woff2,application/octet-stream,font/x-woff2,application/x-font-woff2" data-ext="woff2" data-upload_text="<?php esc_attr_e( 'Upload', 'buddyboss-theme' ); ?>" data-remove_text="<?php esc_attr_e( 'Delete', 'buddyboss-theme' ); ?>" data-box_title="<?php esc_attr_e( 'Upload font .woff2 file', 'buddyboss-theme' ); ?>" data-box_action="<?php esc_attr_e( 'Select .woff2 file', 'buddyboss-theme' ); ?>" />
										</div>
										<div class="buddyboss-field font_face buddyboss-field-file">
											<p class="buddyboss-field-label">
												<label for="font_face[<?php echo $key; ?>][ttf]file"><?php esc_html_e( 'TTF File', 'buddyboss-theme' ); ?></label>
											</p>
											<input type="hidden" name="font_face[<?php echo $key; ?>][ttf][id]" value="<?php echo ! empty( $font_face['ttf']['id'] ) ? $font_face['ttf']['id'] : ''; ?>"/>
											<input type="text" name="font_face[<?php echo $key; ?>][ttf][url]" value="<?php echo ! empty( $font_face['ttf']['url'] ) ? $font_face['ttf']['url'] : ''; ?>" placeholder="<?php esc_attr_e( 'TrueType Fonts, Used for better supporting Safari, Android, iOS', 'buddyboss-theme' ); ?>" class="buddyboss-field-input" />
											<input type="button" class="button buddyboss-button <?php echo ! empty( $font_face['ttf']['id'] ) ? 'buddyboss-upload-clear-btn' : 'buddyboss-upload-btn'; ?>" name="font_face[<?php echo $key; ?>][ttf]" id="font_face[<?php echo $key; ?>][ttf]" value="<?php echo ! empty( $font_face['ttf']['id'] ) ? esc_html__( 'Delete', 'buddyboss-theme' ) : esc_html__( 'Upload', 'buddyboss-theme' ); ?>" data-preview_anchor="none" data-mime_type="application/x-font-ttf,application/octet-stream,font/ttf" data-ext="ttf" data-upload_text="<?php esc_attr_e( 'Upload', 'buddyboss-theme' ); ?>" data-remove_text="<?php esc_attr_e( 'Delete', 'buddyboss-theme' ); ?>" data-box_title="<?php esc_attr_e( 'Upload font .ttf file', 'buddyboss-theme' ); ?>" data-box_action="<?php esc_attr_e( 'Select .ttf file', 'buddyboss-theme' ); ?>" />
										</div>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
			<div>
				<input type="hidden" name="<?php echo self::$register_cpt_slug . '_nonce'; ?>" value="<?php echo wp_create_nonce( self::$register_cpt_slug ); ?>" />
				<input type="button" class="button-primary buddyboss-button add-repeater-row" value="<?php esc_html_e( 'Add New Variation', 'buddyboss-theme' ); ?>" data-template-id="<?php echo esc_attr( $js_id . '_block' ); ?>" />
			</div>
			<script type="text/template" id="<?php echo esc_attr( $js_id . '_block' ); ?>">
				<div class="repeater-block block-visible">
					<div class="repeater-content form-table">
						<div class="repeater-content-top">
							<div class="buddyboss-field font_face buddyboss-field-select">
								<p class="buddyboss-field-label">
									<label for="font_face[__counter__][font_weight]"><?php esc_html_e( 'Weight:', 'buddyboss-theme' ); ?></label>
								</p>
								<select name="font_face[__counter__][font_weight]" id="font_face[__counter__][font_weight]" class="font_weight">
									<option value="100"><?php esc_html_e( '100 - Thin', 'buddyboss-theme' ); ?></option>
									<option value="200"><?php esc_html_e( '200 - Extra Light', 'buddyboss-theme' ); ?></option>
									<option value="300"><?php esc_html_e( '300 - Light', 'buddyboss-theme' ); ?></option>
									<option value="400"><?php esc_html_e( '400 - Normal', 'buddyboss-theme' ); ?></option>
									<option value="500"><?php esc_html_e( '500 - Medium', 'buddyboss-theme' ); ?></option>
									<option value="600"><?php esc_html_e( '600 - Semi Bold', 'buddyboss-theme' ); ?></option>
									<option value="700"><?php esc_html_e( '700 - Bold', 'buddyboss-theme' ); ?></option>
									<option value="800"><?php esc_html_e( '800 - Extra Bold', 'buddyboss-theme' ); ?></option>
									<option value="900"><?php esc_html_e( '900 - Black', 'buddyboss-theme' ); ?></option>
								</select>
								<p class="buddyboss-field-label">
									<label for="font_face[__counter__][font_style]"><?php esc_html_e( 'Style:', 'buddyboss-theme' ); ?></label>
								</p>
								<select name="font_face[__counter__][font_style]" id="font_face[__counter__][font_style]" class="font_style">
									<option value="normal"><?php esc_html_e( 'Normal', 'buddyboss-theme' ); ?></option>
									<option value="italic"><?php esc_html_e( 'Italic', 'buddyboss-theme' ); ?></option>
								</select>
								<p class="buddyboss-field-label">
									<label><?php esc_html_e( 'Font Files:', 'buddyboss-theme' ); ?></label>
								</p>
								<span class="buddyboss-repeater-tool-btn button-secondary close-repeater-row" title="<?php esc_attr_e( 'Close', 'buddyboss-theme' ); ?>">
									<?php esc_html_e( 'Close', 'buddyboss-theme' ); ?>
								</span>
								<span class="buddyboss-repeater-tool-btn button-secondary toggle-repeater-row" title="<?php esc_attr_e( 'Edit', 'buddyboss-theme' ); ?>">
									<?php esc_html_e( 'Edit', 'buddyboss-theme' ); ?>
								</span>
							</div>
							<div class="buddyboss-field font_face buddyboss-field-toolbar">
								<span class="buddyboss-repeater-tool-btn remove-repeater-row" data-confirm="<?php esc_attr_e( 'Are you sure?', 'buddyboss-theme' ); ?>" title="<?php esc_attr_e( 'Delete Font Variation', 'buddyboss-theme' ); ?>">
									<?php esc_html_e( 'Delete Font Variation', 'buddyboss-theme' ); ?>
								</span>
							</div>
						</div>
						<div class="repeater-content-bottom">
							<div class="buddyboss-field font_face buddyboss-field-file">
								<p class="buddyboss-field-label">
									<label for="font_face[__counter__][woff]file"><?php esc_html_e( 'WOFF File', 'buddyboss-theme' ); ?></label>
								</p>
								<input type="hidden" name="font_face[__counter__][woff][id]" value="" />
								<input type="text" name="font_face[__counter__][woff][url]" value="" placeholder="<?php esc_attr_e( 'The Web Open Font Format, Used by Modern Browsers', 'buddyboss-theme' ); ?>" class="buddyboss-field-input" />
								<input type="button" class="button buddyboss-button buddyboss-upload-btn" name="font_face[__counter__][woff]" id="font_face[__counter__][woff]" value="<?php esc_html_e( 'Upload', 'buddyboss-theme' ); ?>" data-preview_anchor="none" data-mime_type="font/woff,application/font-woff,application/x-font-woff,application/octet-stream" data-ext="woff" data-upload_text="<?php esc_attr_e( 'Upload', 'buddyboss-theme' ); ?>" data-remove_text="<?php esc_attr_e( 'Remove', 'buddyboss-theme' ); ?>" data-box_title="<?php esc_attr_e( 'Upload font .woff file', 'buddyboss-theme' ); ?>" data-box_action="<?php esc_attr_e( 'Select .woff file', 'buddyboss-theme' ); ?>" />
							</div>
							<div class="buddyboss-field font_face buddyboss-field-file">
								<p class="buddyboss-field-label">
									<label for="font_face[__counter__][woff2]file"><?php esc_html_e( 'WOFF2 File', 'buddyboss-theme' ); ?></label>
								</p>
								<input type="hidden" name="font_face[__counter__][woff2][id]" value="" />
								<input type="text" name="font_face[__counter__][woff2][url]" value="" placeholder="<?php esc_attr_e( 'The Web Open Font Format 2, Used by Modern Browsers', 'buddyboss-theme' ); ?>" class="buddyboss-field-input" />
								<input type="button" class="button buddyboss-button buddyboss-upload-btn" name="font_face[__counter__][woff2]" id="font_face[__counter__][woff2]" value="<?php esc_html_e( 'Upload', 'buddyboss-theme' ); ?>" data-preview_anchor="none" data-mime_type="font/woff2,application/octet-stream,font/x-woff2,application/x-font-woff2" data-ext="woff2" data-upload_text="<?php esc_attr_e( 'Upload', 'buddyboss-theme' ); ?>" data-remove_text="<?php esc_attr_e( 'Remove', 'buddyboss-theme' ); ?>" data-box_title="<?php esc_attr_e( 'Upload font .woff2 file', 'buddyboss-theme' ); ?>" data-box_action="<?php esc_attr_e( 'Select .woff2 file', 'buddyboss-theme' ); ?>" />
							</div>
							<div class="buddyboss-field font_face buddyboss-field-file">
								<p class="buddyboss-field-label">
									<label for="font_face[__counter__][ttf]file"><?php esc_html_e( 'TTF File', 'buddyboss-theme' ); ?></label>
								</p>
								<input type="hidden" name="font_face[__counter__][ttf][id]" value=""/>
								<input type="text" name="font_face[__counter__][ttf][url]" value="" placeholder="<?php esc_attr_e( 'TrueType Fonts, Used for better supporting Safari, Android, iOS', 'buddyboss-theme' ); ?>" class="buddyboss-field-input" />
								<input type="button" class="button buddyboss-button buddyboss-upload-btn" name="font_face[__counter__][ttf]" id="font_face[__counter__][ttf]" value="<?php esc_html_e( 'Upload', 'buddyboss-theme' ); ?>" data-preview_anchor="none" data-mime_type="application/x-font-ttf,application/octet-stream,font/ttf" data-ext="ttf" data-upload_text="<?php esc_attr_e( 'Upload', 'buddyboss-theme' ); ?>" data-remove_text="<?php esc_attr_e( 'Remove', 'buddyboss-theme' ); ?>" data-box_title="<?php esc_attr_e( 'Upload font .ttf file', 'buddyboss-theme' ); ?>" data-box_action="<?php esc_attr_e( 'Select .ttf file', 'buddyboss-theme' ); ?>" />
							</div>
						</div>
					</div>
				</div>
			</script>
			<?php
			if ( buddyboss_theme_get_theme_sudharo() ) {
				?>
				</div>
				<?php
			}
		}

		/**
		 * Get fonts
		 *
		 * @return array $fonts fonts array of fonts.
		 * @since 1.2.10
		 */
		public static function get_fonts() {
			if ( buddyboss_theme_get_theme_sudharo() ) {
				return array();
			}

			if ( is_null( self::$fonts ) ) {
				self::$fonts = array();

				$args = array(
					'post_type'      => self::$register_cpt_slug,
					'posts_per_page' => - 1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
				);

				$fonts_query = new \WP_Query( $args );

				if ( ! empty( $fonts_query->posts ) ) {
					foreach ( $fonts_query->posts as $font_id ) {
						$font_face_data = get_post_meta( $font_id, 'buddyboss_font_face', true );

						if ( ! empty( $font_face_data ) ) {
							foreach ( $font_face_data as $key => $font_face ) {
								if ( empty( $font_face['woff']['url'] ) && empty( $font_face['woff2']['url'] ) && empty( $font_face['ttf']['url'] ) ) {
									unset( $font_face_data[ $key ] );
								}
							}

							$font_face_data = array_values( $font_face_data );

							if ( ! empty( $font_face_data ) ) {
								self::$fonts[ $font_id ] = array(
									'name'      => get_the_title( $font_id ),
									'font_face' => self::get_font_data( $font_id, $font_face_data ),
								);
							}
						}
					}
				}
			}

			return self::$fonts;
		}

		/**
		 * Get font data
		 *
		 * @param string     $font_id custom font id.
		 * @param array|bool $font_face_data custom font data.
		 *
		 * @return array|bool $font_face_data or false if no data found.
		 * @since 1.2.10
		 */
		public static function get_font_data( $font_id, $font_face_data = false ) {
			if ( empty( $font_face_data ) ) {
				$font_face_data = get_post_meta( $font_id, 'buddyboss_font_face', true );
			}

			if ( ! empty( $font_face_data ) ) {
				return $font_face_data;
			}

			return false;
		}

		/**
		 * open a redux group tab based on url GET param tab
		 */
		public function open_theme_options_tab() {
			if ( ! empty( $_GET['page'] ) && 'buddyboss_theme_options' === sanitize_text_field( $_GET['page'] ) && ! empty( $_GET['tab'] ) ) {
				?>
				<script>
					jQuery(document).ready(function(){
						setTimeout(function(){
							jQuery('.redux-group-tab-link-li .redux-group-tab-link-a[data-key="<?php echo sanitize_text_field( $_GET['tab'] ); ?>"]').trigger('click');
						},500);
					});
				</script>
				<?php
			}
		}
	}

	/**
	 *  Kicking this off by calling 'get_instance()' method
	 */
	BuddyBoss_Custom_Fonts_CPT::get_instance();

endif;
