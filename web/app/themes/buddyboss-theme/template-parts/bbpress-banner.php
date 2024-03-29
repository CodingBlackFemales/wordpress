<?php
$bbp_is_forum_group_forum = function_exists( 'bbp_is_forum_group_forum' ) && bbp_is_forum_group_forum( bbp_get_forum_id() );

$bbpress_banner = buddyboss_theme_get_option( 'bbpress_banner_switch' );

$class = 'hide';
if ( $bbpress_banner && ( bbp_is_forum_archive() || bbp_is_topic_archive() || bbp_is_search() ) ) {
	$class = 'show';
}

if ( ! $bbp_is_forum_group_forum && $bbpress_banner ) {
	?>
	<div id="bs-bbpress-banner-wrapper" class="flex-full bs-forums-banner-wrap <?php echo $class; ?>">
		<?php
		$bbpress_banner_image       = buddyboss_theme_get_option( 'bbpress_banner_image' );
		$bbpress_banner_image_url   = $bbpress_banner_image['url'];
		if ( $bbpress_banner_image_url ) {
			$bbpress_banner_image = $bbpress_banner_image_url;
		} else {
			$bbpress_banner_image = '';
		}

		$bbpress_banner_overlay     = buddyboss_theme_get_option( 'bbpress_banner_overlay' );
		$bbpress_banner_overlay_opacity     = buddyboss_theme_get_option( 'bbpress_banner_overlay_opacity' );
		if ( ! $bbpress_banner_overlay_opacity ) {
			$bbpress_banner_overlay_opacity = 0;
		}
		$opacity = $bbpress_banner_overlay_opacity / 100;
		$bbpress_banner_title       = buddyboss_theme_get_option( 'bbpress_banner_title' );
		$bbpress_banner_description = buddyboss_theme_get_option( 'bbpress_banner_description' );
		$bbpress_banner_search      = buddyboss_theme_get_option( 'bbpress_banner_search' );
		$bbpress_banner_text_color  = buddyboss_theme_get_option( 'bbpress_banner_text' );

		$bbpress_banner_text_color_style = '';
		if ( $bbpress_banner_text_color !== '' ) {
			$bbpress_banner_text_color_style = 'color:' . $bbpress_banner_text_color;
		}

		/* Convert hexdec color string to rgb(a) string */

		function hex2rgba( $color, $opacity = false ) {

			$default = 'rgb( 0, 0, 0 )';

			/**
			 * Return default if no color provided
			 */
			if ( empty( $color ) ) {
				return $default;
			}

			/**
			 * Sanitize $color if "#" is provided
			 */
			if ( $color[0] == '#' ) {
				$color = substr( $color, 1 );
			}

			/**
			 * Check if color has 6 or 3 characters and get values
			 */
			if ( strlen( $color ) == 6 ) {
				$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
			} elseif ( strlen( $color ) == 3 ) {
				$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
			} else {
				return $default;
			}

			/**
			 * [$rgb description]
			 * @var array
			 */
			$rgb = array_map( 'hexdec', $hex );

			/**
			 * Check if opacity is set(rgba or rgb)
			 */
			if ( $opacity ) {
				if ( abs( $opacity ) > 1 ) {
					$opacity = 1.0;
				}
				$output = 'rgba( ' . implode( ',', $rgb ) . ',' . $opacity . ' )';
			} else {
				$output = 'rgba( ' . implode( ',', $rgb ) . ', 0 )';
			}

			/**
			 * Return rgb(a) color string
			 */
			return $output;
		}

		if ( $bbpress_banner_overlay === 'transparent' ) {
			$rgba = 'transparent';
		} else {
			$rgba = hex2rgba( $bbpress_banner_overlay, $opacity );
		}
		?>
		<div class="bs-forums-banner has-banner-img container-full <?php echo ( $bbpress_banner_image == '' ) ? 'thin-banner-img' : ''; ?> <?php echo ( abs( $bbpress_banner_overlay_opacity / 100 ) < 0.2 ) ? 'lucid-banner-img' : ''; ?>"
			 style="background-image: url(<?php echo $bbpress_banner_image; ?>);box-shadow: inset 0 0 0 1000px <?php echo $rgba; ?>;">
			<div class="container text-center">
				<?php
				if ( ! empty( $bbpress_banner_title ) ) {
					?>
					<h1 class="bb-banner-title" style="<?php echo esc_attr( $bbpress_banner_text_color_style ); ?>"><?php echo $bbpress_banner_title; ?></h1>
					<?php
				}

				if ( ! empty( $bbpress_banner_description ) ) {
					?>
					<p class="bb-banner-desc" style="<?php echo esc_attr( $bbpress_banner_text_color_style ); ?>"><?php echo $bbpress_banner_description; ?></p>
					<?php
				}
				?>

				<?php if ( $bbpress_banner_search ) : ?>
					<div id="forums-dir-search" role="search" class="bs-dir-search bs-forums-search">
						<form class="bs-search-form search-form-has-reset" role="search" method="get" id="bbp-search-form" action="<?php bbp_search_url(); ?>">
							<input type="hidden" name="action" value="bbp-search-request" />
							<input tabindex="<?php bbp_tab_index(); ?>" type="text" value="<?php echo esc_attr( bbp_get_search_terms() ); ?>" name="bbp_search" id="bbp_search" placeholder="<?php esc_attr_e( 'Search forums...', 'buddyboss-theme' ); ?>" />
							<input tabindex="<?php bbp_tab_index(); ?>" class="button hide search-form_submit" type="submit" id="bbp_search_submit" value="<?php esc_attr_e( 'Search', 'buddyboss-theme' ); ?>" />
							<button type="reset" class="search-form_reset">
								<span class="bb-icon-rf bb-icon-times" aria-hidden="true"></span>
								<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss-theme' ); ?></span>
							</button>
						</form>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}
