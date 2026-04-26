<?php
/**
 * Meta Boxes class file.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use LearnDash\Course_Grid\Utilities;
use WP_Post;

/**
 * Meta boxes class.
 *
 * @since 4.21.4
 */
class Meta_Boxes {
	/**
	 * Constructor.
	 *
	 * @since 4.21.4
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta_boxes' ], 10, 3 );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Add meta boxes.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		$post_types = Utilities::get_post_types_slugs();

		add_meta_box(
			'learndash-course-grid-meta-box',
			__( 'LearnDash Course Grid Settings', 'learndash' ),
			[ $this, 'output_settings_meta_box' ],
			$post_types,
			'advanced',
			'low',
			[]
		);
	}

	/**
	 * Saves meta boxes values.
	 *
	 * @since 4.21.4
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    WP post object.
	 * @param bool    $update  True if post is an update.
	 *
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post, $update ) {
		$this->save_settings_meta_box( $post_id, $post, $update );
	}

	/**
	 * Outputs the settings meta box.
	 *
	 * @since 4.21.4
	 *
	 * @param array<mixed> $args Meta box arguments.
	 *
	 * @return void
	 */
	public function output_settings_meta_box( $args ) {
		global $post;
		$post_id = $post->ID;

		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = $post_type ? strtolower( $post_type->labels->singular_name ) : '';

		$description  = get_post_meta( $post_id, '_learndash_course_grid_short_description', true );
		$enable_video = get_post_meta( $post_id, '_learndash_course_grid_enable_video_preview', true );
		$embed_code   = get_post_meta( $post_id, '_learndash_course_grid_video_embed_code', true );
		$duration     = get_post_meta( $post_id, '_learndash_course_grid_duration', true );
		$duration_h   = is_numeric( $duration ) ? floor( $duration / HOUR_IN_SECONDS ) : null;
		$duration_m   = is_numeric( $duration ) ? floor( ( $duration % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ) : null;
		$button_text  = get_post_meta( $post_id, '_learndash_course_grid_custom_button_text', true );
		$ribbon_text  = get_post_meta( $post_id, '_learndash_course_grid_custom_ribbon_text', true );

		$video_html = '<video controls><source src="video-file.mp4" type="video/mp4"></video>';
		?>

		<?php wp_nonce_field( 'settings_meta_box', 'learndash_course_grid_nonce' ); ?>

		<script>
			var toggleVisibility = toggleVisibility || function( id ) {
				var e = document.getElementById( id );
				if ( e.style.display == 'block' ) {
					e.style.display = 'none';
				} else {
					e.style.display = 'block';
				}
			};

			jQuery( document ).ready( function( $ ) {
				$( window ).on( 'load', function() {
					if ( $( 'input[name="learndash_course_grid_enable_video_preview"]' ).is( ':checked' ) ) {
						$( '#learndash_course_grid_video_embed_code_field' ).show();
					}
				} );

				$( 'input[name="learndash_course_grid_enable_video_preview"]' ).change( function( e ) {
					if ( $( this ).prop( 'checked' ) ) {
						$( '#learndash_course_grid_video_embed_code_field' ).show();
					} else {
						$( '#learndash_course_grid_video_embed_code_field' ).hide();
					}
				});
			});
		</script>
		<div class="sfwd sfwd_options learndash-course-grid-settings">
			<div class="sfwd_input">
				<span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!">
						<img src="<?php echo LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'img/question.png'; ?>" onclick="toggleVisibility( 'learndash_course_grid_short_description_help_text' );">
						<label class="sfwd_label textinput" for="learndash_course_grid_short_description"><?php _e( 'Short Description', 'learndash' ); ?></label>
					</a>
					<div class="sfwd_help_text_div" style="display:none" id="learndash_course_grid_short_description_help_text">
						<label class="sfwd_help_text">
						<?php
						printf( __( 'Short description that will be displayed for this %s in the Course Grid.', 'learndash' ), $post_type_label );
						?>
						</label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<textarea name="learndash_course_grid_short_description" id="learndash_course_grid_short_description" cols="20" rows="3"><?php echo esc_textarea( $description ); ?></textarea>
					</div>
				</span>
				<p style="clear:left"></p>
			</div>
			<div class="sfwd_input">
				<span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!">
						<img src="<?php echo LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'img/question.png'; ?>" onclick="toggleVisibility( 'learndash_course_grid_duration_help_text' );">
						<label class="sfwd_label textinput" for="learndash_course_grid_duration"><?php _e( 'Duration', 'learndash' ); ?></label>
					</a>
					<div class="sfwd_help_text_div" style="display:none" id="learndash_course_grid_duration_help_text">
						<label class="sfwd_help_text">
						<?php
						printf( __( 'Duration for this %s.', 'learndash' ), $post_type_label );
						?>
						</label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<label for="learndash_course_grid_duration_hour">
							<input
								type="number"
								name="learndash_course_grid_duration_hour"
								id="learndash_course_grid_duration_hour"
								value="<?php echo esc_attr( $duration_h ); ?>"
								class="small-text"
								min="0"
							> <?php _e( 'hour(s)', 'learndash' ); ?>
						</label>
						<label for="learndash_course_grid_duration_minute" style="margin-left: 15px;">
							<input
								type="number"
								name="learndash_course_grid_duration_minute"
								id="learndash_course_grid_duration_minute"
								value="<?php echo esc_attr( $duration_m ); ?>"
								class="small-text"
								min="0"
							> <?php _e( 'minute(s)', 'learndash' ); ?>
						</label>
					</div>
				</span>
				<p style="clear:left"></p>
			</div>
			<div class="sfwd_input">
				<span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!">
						<img src="<?php echo LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'img/question.png'; ?>" onclick="toggleVisibility( 'learndash_course_grid_enable_video_preview_help_text' );">
						<label class="sfwd_label textinput" for="learndash_course_grid_enable_video_preview"><?php _e( 'Enable Video Preview', 'learndash' ); ?></label>
					</a>
					<div class="sfwd_help_text_div" style="display:none" id="learndash_course_grid_enable_video_preview_help_text">
						<label class="sfwd_help_text"><?php printf( __( 'Select this option to use a featured video for this %s in the Course Grid. If not selected, the featured image will be used.', 'learndash' ), $post_type_label ); ?></label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<input type="hidden" name="learndash_course_grid_enable_video_preview" value="0">
						<input type="checkbox" name="learndash_course_grid_enable_video_preview" id="learndash_course_grid_enable_video_preview" value="1" <?php checked( $enable_video, 1, true ); ?>>
					</div>
				</span>
				<p style="clear:left"></p>
			</div>
			<div class="sfwd_input" style="display: none;" id="learndash_course_grid_video_embed_code_field">
				<span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!">
						<img src="<?php echo LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'img/question.png'; ?>" onclick="toggleVisibility( 'learndash_course_grid_video_embed_code_help_text' );">
						<label class="sfwd_label textinput" for="learndash_course_grid_video_embed_code"><?php _e( 'Video URL or Embed Code', 'learndash' ); ?></label>
					</a>
					<div class="sfwd_help_text_div" style="display:none" id="learndash_course_grid_video_embed_code_help_text">
						<label class="sfwd_help_text"><?php printf( __( 'Embed code of the video you want to use for this %1$s in the Course Grid. If you have a video file URL, you can use the following HTML tag to embed your video: %2$s', 'learndash' ), $post_type_label, '<code>' . esc_html( $video_html ) . '</code>' ); ?>
						</label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<textarea name="learndash_course_grid_video_embed_code" id="learndash_course_grid_video_embed_code" rows="2" cols="57"><?php echo esc_textarea( $embed_code ); ?></textarea>
					</div>
				</span>
				<p style="clear:left"></p>
			</div>
			<div class="sfwd_input" id="learndash_course_grid_custom_button_text_field">
				<span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!">
						<img src="<?php echo LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'img/question.png'; ?>" onclick="toggleVisibility( 'learndash_course_grid_custom_button_text_help_text' );">
						<label class="sfwd_label textinput" for="learndash_course_grid_custom_button_text"><?php _e( 'Custom Button Text', 'learndash' ); ?></label>
					</a>
					<div class="sfwd_help_text_div" style="display:none" id="learndash_course_grid_custom_button_text_help_text">
						<label class="sfwd_help_text"><?php _e( 'Use this field to change the default "See More..." button text in the Course Grid.', 'learndash' ); ?>
						</label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<input name="learndash_course_grid_custom_button_text" id="learndash_course_grid_custom_button_text" type="text" value="<?php echo esc_attr( $button_text ); ?>"></textarea>
					</div>
				</span>
				<p style="clear:left"></p>
			</div>
			<div class="sfwd_input" id="learndash_course_grid_custom_ribbon_text_field">
				<span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!">
						<img src="<?php echo LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL . 'img/question.png'; ?>" onclick="toggleVisibility( 'learndash_course_grid_custom_ribbon_text_help_text' );">
						<label class="sfwd_label textinput" for="learndash_course_grid_custom_ribbon_text"><?php _e( 'Custom Ribbon Text', 'learndash' ); ?></label>
					</a>
					<div class="sfwd_help_text_div" style="display:none" id="learndash_course_grid_custom_ribbon_text_help_text">
						<label class="sfwd_help_text"><?php _e( 'Use this field to change the default ribbon text in the Course Grid.', 'learndash' ); ?>
						</label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<input name="learndash_course_grid_custom_ribbon_text" id="learndash_course_grid_custom_ribbon_text" type="text" value="<?php echo esc_attr( $ribbon_text ); ?>"></textarea>
					</div>
				</span>
				<p style="clear:left"></p>
			</div>
		</div>

		<?php
	}

	/**
	 * Save course grid meta box fields.
	 *
	 * @since 4.21.4
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post    WP post object.
	 * @param bool   $update  True if post is an update.
	 *
	 * @return void
	 */
	public function save_settings_meta_box( $post_id, $post, $update ) {
		if ( ! in_array( $post->post_type, Utilities::get_post_types_slugs() ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['learndash_course_grid_nonce'] ) || ! wp_verify_nonce( $_POST['learndash_course_grid_nonce'], 'settings_meta_box' ) ) {
			return;
		}

		$allowed_html = wp_kses_allowed_html( 'learndash_course_grid_embed_code' );

		if ( isset( $_POST['learndash_course_grid_short_description'] ) ) {
			update_post_meta( $post_id, '_learndash_course_grid_short_description', wp_kses_post( $_POST['learndash_course_grid_short_description'] ) );
			learndash_update_setting( $post_id, 'course_short_description', wp_kses_post( $_POST['learndash_course_grid_short_description'] ) );
		}

		$duration_second = '';
		if ( ! empty( $_POST['learndash_course_grid_duration_hour'] ) || ! empty( $_POST['learndash_course_grid_duration_minute'] ) ) {
			$duration_second = ( intval( $_POST['learndash_course_grid_duration_hour'] ) * HOUR_IN_SECONDS ) + ( intval( $_POST['learndash_course_grid_duration_minute'] ) * MINUTE_IN_SECONDS );
		}

		update_post_meta( $post_id, '_learndash_course_grid_duration', $duration_second );

		if ( isset( $_POST['learndash_course_grid_enable_video_preview'] ) ) {
			update_post_meta( $post_id, '_learndash_course_grid_enable_video_preview', wp_filter_kses( $_POST['learndash_course_grid_enable_video_preview'] ) );
		}

		if ( isset( $_POST['learndash_course_grid_video_embed_code'] ) ) {
			update_post_meta( $post_id, '_learndash_course_grid_video_embed_code', wp_kses( $_POST['learndash_course_grid_video_embed_code'], $allowed_html ) );
		}

		if ( isset( $_POST['learndash_course_grid_custom_button_text'] ) ) {
			update_post_meta( $post_id, '_learndash_course_grid_custom_button_text', sanitize_text_field( trim( $_POST['learndash_course_grid_custom_button_text'] ) ) );
		}

		if ( isset( $_POST['learndash_course_grid_custom_ribbon_text'] ) ) {
			update_post_meta( $post_id, '_learndash_course_grid_custom_ribbon_text', sanitize_text_field( trim( $_POST['learndash_course_grid_custom_ribbon_text'] ) ) );
		}
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( 'post' === $screen->base && ! defined( 'LEARNDASH_VERSION' ) ) {
			wp_enqueue_style(
				'learndash-course-grid-meta-box',
				LEARNDASH_COURSE_GRID_PLUGIN_URL . 'assets/css/meta-box.css',
				[],
				LEARNDASH_VERSION,
				'all'
			);
		}
	}
}
