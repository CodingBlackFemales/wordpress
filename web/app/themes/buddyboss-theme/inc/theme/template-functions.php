<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package BuddyBoss_Theme
 */

if ( ! function_exists( 'buddyboss_theme_pingback_header' ) ) {

	/**
	 * Add a pingback url auto-discovery header for singularly identifiable articles.
	 */
	function buddyboss_theme_pingback_header() {
		if ( is_singular() && pings_open() ) {
			echo '<link rel="pingback" href="', esc_url( get_bloginfo( 'pingback_url' ) ), '">';
		}
	}

	add_action( 'wp_head', 'buddyboss_theme_pingback_header' );
}


if ( ! function_exists( 'buddyboss_theme_viewport_meta' ) ) {

	/**
	 * Add a viewport meta.
	 */
	function buddyboss_theme_viewport_meta() {
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=3.0, user-scalable=1" />';
	}

	add_action( 'wp_head', 'buddyboss_theme_viewport_meta' );
}


if ( ! function_exists( 'buddyboss_theme_body_classes' ) ) {

	/**
	 * Adds custom classes to the array of body classes.
	 *
	 * @param array $classes Classes for the body element.
	 *
	 * @return array
	 */
	function buddyboss_theme_body_classes( $classes ) {
		global $post, $wp_query;

		// BuddyBoss theme class.
		$classes[] = 'buddyboss-theme';

		// BuddyBoss theme template class.
		$template_type = '1';
		$template_type = apply_filters( 'bb_template_type', $template_type );
		$classes[]     = 'bb-template-v' . $template_type;

		// BuddyPanel Class.
		$show_buddypanel          = buddyboss_theme_get_option( 'buddypanel' );
		$buddypanel_default_state = buddyboss_theme_get_option( 'buddypanel_state' );
		$header                   = (int) buddyboss_theme_get_option( 'buddyboss_header' );

		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( 3 === $header ) {
			$buddypanel_side = buddyboss_theme_get_option( 'buddypanel_position_h3' );
		} else {
			$buddypanel_side = buddyboss_theme_get_option( 'buddypanel_position' );
		}
		$menu = is_user_logged_in() ? 'buddypanel-loggedin' : 'buddypanel-loggedout';

		if ( ! is_page_template( 'page-fullscreen.php' ) ) {
			if ( $show_buddypanel && has_nav_menu( $menu ) ) {
				$classes[] = 'bb-buddypanel';

				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				if ( $buddypanel_side && 'right' == $buddypanel_side ) {
					$classes[] = 'bb-buddypanel-right';
				} else {
					$classes[] = 'bb-buddypanel-left';
				}

				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				if (
					(
						isset( $_COOKIE['buddypanel'] ) &&
						'open' === $_COOKIE['buddypanel']
					) ||
					(
						'open' === $buddypanel_default_state &&
						! isset( $_COOKIE['buddypanel'] )
					)
				) {
					$classes[] = 'buddypanel-open';
				}
			}

			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if (
				has_nav_menu( $menu ) &&
				$show_buddypanel &&
				3 === $header
			) {
				$classes[] = 'bb-buddypanel';

				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				if ( $buddypanel_side && 'right' == $buddypanel_side ) {
					$classes[] = 'bb-buddypanel-right';
				}
			}

			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if (
				has_nav_menu( $menu ) &&
				! buddyboss_is_learndash_inner() &&
				$show_buddypanel &&
				'open' === $buddypanel_default_state &&
				3 === $header
			) {
				$classes[] = 'buddypanel-header'; // buddypanel-open.
			}

			$buddypanel_logo   = buddyboss_theme_get_option( 'buddypanel_show_logo' );
			$buddypanel_toggle = buddyboss_theme_get_option( 'buddypanel_toggle' );
			if ( $buddypanel_logo ) {
				$classes[] = 'buddypanel-logo';
			} else {
				$classes[] = 'buddypanel-logo-off';
			}

			if ( ! $buddypanel_toggle ) {
				$classes[] = 'buddypanel-toggle-off';
			}

			if (
				(
					class_exists( 'SFWD_LMS' ) &&
					buddyboss_is_learndash_inner()
				) ||
				(
					class_exists( 'LifterLMS' ) &&
					buddypanel_is_lifterlms_inner()
				) ||
				(
					function_exists( 'tutor' ) &&
					buddyboss_is_tutorlms_inner()
				)
			) {
				$classes[] = 'bb-sfwd-aside';

				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				if ( 3 === $header ) {
					$classes[] = 'buddypanel-header';
				}
			}
		}

		$custom_font = buddyboss_theme_get_option( 'custom_typography' );
		if ( ! $custom_font ) {
			$classes[] = 'bb-custom-typo';
		}

		// Sidebar Classes.
		if ( is_active_sidebar( 'sidebar' ) && ! is_page() && ( is_singular( 'post' ) || is_singular( 'attachment' ) || is_post_type_archive( 'post' ) || is_home() ) ) {
			// Blog Sidebar.
			$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'sidebar' );
			$classes[] = 'has-sidebar blog-sidebar' . $sidebar;
		} elseif ( is_active_sidebar( 'search' ) && is_search() ) {
			// Search Sidebar.
			$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'search' );
			$classes[] = 'has-sidebar search-sidebar' . $sidebar;
		} elseif ( is_active_sidebar( 'activity_left' ) && buddyboss_is_bp_active() && bp_is_current_component( 'activity' ) && ! bp_is_user() && ! is_page_template( 'page-fullwidth.php' ) && ! is_page_template( 'page-fullscreen.php' ) ) {
			// Activity sidebar left.
			$classes[] = 'has-sidebar activity-sidebar-left';
		} elseif ( is_active_sidebar( 'activity_right' ) && buddyboss_is_bp_active() && bp_is_current_component( 'activity' ) && ! bp_is_user() && ! is_page_template( 'page-fullwidth.php' ) && ! is_page_template( 'page-fullscreen.php' ) ) {
			// Activity sidebar right.
			$classes[] = 'has-sidebar activity-sidebar-right';
		} elseif ( ( is_active_sidebar( 'members' ) || ( function_exists( 'bp_disable_advanced_profile_search' ) && ! bp_disable_advanced_profile_search() ) ) && function_exists( 'bp_is_members_directory' ) && bp_is_members_directory() && ! is_page_template( 'page-fullwidth.php' ) && ! is_page_template( 'page-fullscreen.php' ) ) {
			// Members directory sidebar.
			$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'members' );
			$classes[] = 'has-sidebar members-sidebar' . $sidebar;
		} elseif ( is_active_sidebar( 'profile' ) && function_exists( 'bp_is_user' ) && bp_is_user() && ! bp_is_user_settings() && ! bp_is_user_profile_edit() && ! bp_is_user_change_avatar() && ! bp_is_user_change_cover_image() && ! bp_is_user_front() && ! bp_is_user_notifications() && ! bp_is_user_messages() ) {
			// Member profile sidebar.
			$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'profile' );
			$classes[] = 'has-sidebar profile-sidebar' . $sidebar;
		} elseif ( is_active_sidebar( 'groups' ) && function_exists( 'bp_is_groups_directory' ) && bp_is_groups_directory() && ! is_page_template( 'page-fullwidth.php' ) && ! is_page_template( 'page-fullscreen.php' ) ) {
			// Groups directory sidebar.
			$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'groups' );
			$classes[] = 'has-sidebar groups-sidebar' . $sidebar;
		} elseif ( is_active_sidebar( 'group' ) && function_exists( 'bp_is_group_single' ) && bp_is_group_single() ) {
			// Group single sidebar.
			$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'group' );
			$classes[] = 'has-sidebar group-sidebar' . $sidebar;
		} elseif ( is_active_sidebar( 'forums' ) && function_exists( 'is_bbpress' ) && is_bbpress() && ! ( function_exists( 'bp_is_user' ) && bp_is_user() ) ) {
			// Forums sidebar.
			$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'forums' );
			$classes[] = 'has-sidebar forums-sidebar' . $sidebar;
		} elseif ( is_active_sidebar( 'woo_sidebar' ) && buddyboss_is_woocommerce() ) {
			// WooCommerce sidebar.
			$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'woocommerce' );
			$classes[] = 'has-sidebar woo-sidebar' . $sidebar;
		} elseif ( is_active_sidebar( 'learndash_sidebar' ) && buddyboss_is_learndash() ) {
			// LearnDash sidebar.
			$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'learndash' );
			$classes[] = 'has-sidebar sfwd-sidebar' . $sidebar;
		} elseif ( buddyboss_is_lifterlms() ) {
			// LifterLMS class.
			$classes[] = 'llms-pointer';
			if ( buddyboss_is_llms_courses() ) {
				$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'lifterlms' );
				$classes[] = $sidebar;
				if ( is_active_sidebar( 'lifter_sidebar' ) ) {
					$classes[] = 'has-sidebar';
				}
			} elseif ( buddyboss_is_llms_inner() ) {
				$classes[] = 'llms-inner';
			} elseif ( buddyboss_is_llms_page() ) {
				if ( is_active_sidebar( 'page' ) ) {
					$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'page' );
					$classes[] = 'has-sidebar page-sidebar' . $sidebar;
				}
			} elseif ( buddyboss_is_llms_post() ) {
				if ( is_active_sidebar( 'sidebar' ) ) {
					$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'sidebar' );
					$classes[] = 'has-sidebar page-sidebar' . $sidebar;
				}
			}
		} elseif ( is_active_sidebar( 'page' ) && is_page() && ! is_page_template( 'page-fullwidth.php' ) && ! is_page_template( 'page-fullscreen.php' ) && ( function_exists( 'bp_is_user' ) && ! bp_is_user() ) && ( function_exists( 'bp_is_group' ) && ! bp_is_group() && ( function_exists( 'bp_is_register_page' ) && ! bp_is_register_page() ) && ( function_exists( 'bp_is_directory' ) && ! bp_is_directory() ) && ( function_exists( 'bp_is_group_create' ) && ! bp_is_group_create() ) ) ) {
			// Page Sidebar.
			$sidebar   = ' sidebar-' . buddyboss_theme_get_option( 'page' );
			$classes[] = 'has-sidebar page-sidebar' . $sidebar;
		}

		// Add class for blog featured image layout.
		$featured_img_style = buddyboss_theme_get_option( 'blog_featured_img' );
		if ( is_single() && ! empty( $featured_img_style ) ) {
			$classes[] = $featured_img_style;
		}

		// Add header style class
		$header_style = ' header-style-' . buddyboss_theme_get_option( 'buddyboss_header' );
		$classes[]    = $header_style;

		// Add menu style class
		$menu_style = ' menu-style-' . buddyboss_theme_get_option( 'menu_style' );
		$classes[]  = $menu_style;

		// Custom login.
		$admin_custom_login     = buddyboss_theme_get_option( 'boss_custom_login' );
		$login_admin_background = buddyboss_theme_get_option( 'admin_login_background_switch' );
		if ( $admin_custom_login && $login_admin_background && function_exists( 'bp_is_register_page' ) && bp_is_register_page() && ! is_singular( 'memberpressproduct' ) ) {
			$classes[] = 'login-split-page';
		} elseif ( $admin_custom_login && $login_admin_background && function_exists( 'bp_is_activation_page' ) && bp_is_activation_page() && ! is_singular( 'memberpressproduct' ) ) {
			$classes[] = 'login-split-page';
		}

		// If single forum has cover image
		if ( function_exists( 'bbp_is_single_forum' ) && bbp_is_single_forum() ) {
			if ( ! empty( wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) ) ) ) {
				$classes[] = 'single-forum-cover-image';
			}
		}

		$header_sticky = buddyboss_theme_get_option( 'header_sticky' );
		if ( ! empty( $header_sticky ) ) {
			$classes[] = 'sticky-header';
		}

		if ( class_exists( 'MeprOptions' ) ) {
			$current_id = false;
			if ( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) {
				$current_id = $post->ID;
			} elseif ( isset( $wp_query->post ) && is_object( $wp_query->post ) && isset( $wp_query->post->ID ) ) {
				$current_id = $wp_query->post->ID;
			}
			$mepr_options     = MeprOptions::fetch();
			$login_page_id    = ( ! empty( $mepr_options->login_page_id ) && $mepr_options->login_page_id > 0 ) ? $mepr_options->login_page_id : 0;
			$account_page_id  = ( ! empty( $mepr_options->account_page_id ) && $mepr_options->account_page_id > 0 ) ? $mepr_options->account_page_id : 0;
			$thankyou_page_id = ( ! empty( $mepr_options->thankyou_page_id ) && $mepr_options->thankyou_page_id > 0 ) ? $mepr_options->thankyou_page_id : 0;

			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if ( $current_id == $login_page_id ) {
				$classes[] = 'mepr-login-page';

				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison, WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['action'] ) && 'forgot_password' == $_GET['action'] ) {
					$classes[] = 'mepr-forgot-password-page';
				}
			}

			if ( ! current_user_can( 'memberpress_authorized' ) ) {
				$classes[] = 'mepr-login-page';
			}

			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if ( $current_id == $account_page_id ) {
				$classes[] = 'mepr-account-page';
			}

			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if ( $current_id == $thankyou_page_id ) {
				$classes[] = 'mepr-thankyou-page';
			}
		}

		if ( class_exists( 'GamiPress' ) && gamipress_is_post_type() ) {
			$classes[] = 'bb-gamipress';
		}

		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( ( isset( $_COOKIE['lessonpanel'] ) && 'closed' == $_COOKIE['lessonpanel'] && buddyboss_is_learndash_inner() ) ) {
			$classes[] = 'lms-side-panel-close';
		}

		if ( buddyboss_is_learndash_inner() ) {
			// LearnDash lesson sidebar
			$sidebar   = ' sfwd-single-sidebar-' . buddyboss_theme_get_option( 'learndash_single_sidebar' );
			$classes[] = 'has-sidebar sfwd-sidebar' . $sidebar;
		 
			if ( buddyboss_is_learndash_brand_logo() && buddyboss_theme_ld_focus_mode() ) {
				$classes[] = 'bb-custom-ld-logo-enabled';
			}
		}

		if ( function_exists( 'tutor' ) ) {

			if ( function_exists( 'get_tutor_option' ) && 'default' == get_tutor_option( 'color_preset_type' ) ) {
				$classes[] = 'tutor-lms-custom-colors';
			}

			if ( ( isset( $_COOKIE['bbtheme'] ) && 'dark' == $_COOKIE['bbtheme'] && is_user_logged_in() ) && buddyboss_is_tutorlms_inner() ) {
				$classes[] = 'bb-dark-theme';
			}
		}

		return $classes;
	}

	add_filter( 'body_class', 'buddyboss_theme_body_classes' );
}

if ( ! function_exists( 'buddyboss_theme_entry_header' ) ) {

	/**
	 * Buddyboss entry header content.
	 *
	 * @param array  $post Post array.
	 * @param string $args aditional params.
	 *
	 * @return string
	 */
	function buddyboss_theme_entry_header( $post, $args = '' ) {

		$defaults = array(
			'echo'     => true,
			'type'     => '',
			'fallback' => 'image',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $post ) ) {
			return false;
		}

		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		if ( empty( $args['type'] ) ) {
			$args['type'] = get_post_format( $post );
		}

		switch ( $args['type'] ) {
			case 'video':
				$content = buddyboss_theme_entry_header_video( $post, $args );
				break;
			case 'audio':
				$content = buddyboss_theme_entry_header_audio( $post, $args );
				break;
			case 'image':
				$content = buddyboss_theme_entry_header_image( $post, $args );
				break;
			default:
				$content = buddyboss_theme_entry_header_thumbnail( $post, $args );
				break;
		}

		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( empty( $content ) && 'image' == $args['fallback'] ) {
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if ( '' != $args['type'] && 'image' != $args['type'] ) {
				$content = buddyboss_theme_entry_header_thumbnail( $post, $args );
			}
		}

		$content = apply_filters( 'buddyboss_theme_entry_header', $content, $post, $args );

		if ( $args['echo'] ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $content;
		} else {
			return $content;
		}
	}
}

if ( ! function_exists( 'buddyboss_theme_entry_header_video' ) ) {
	/**
	 * BuddyBoss entry header content for video.
	 *
	 * @param \WP_Post $post Post object.
	 * @param array    $args Additional arguments.
	 *
	 * @return string
	 */
	function buddyboss_theme_entry_header_video( $post, $args ) {
		$retval = '';

		$content = do_shortcode( apply_filters( 'the_content', $post->post_content ) );
		$embeds  = get_media_embedded_in_content( $content );

		if ( ! empty( $embeds ) ) {
			// check what is the first embed containg video tag, youtube or vimeo.
			foreach ( $embeds as $embed ) {
				if ( strpos( $embed, 'video' ) || strpos( $embed, 'youtube' ) || strpos( $embed, 'vimeo' ) ) {
					// $retval = $embed;
					$retval = "<div class='ratio-wrap'><div class='video-container'>" . $embed . '</div></div>';
				}
			}
		}

		return apply_filters( 'buddyboss_theme_entry_header_video', $retval, $post, $args );
	}
}

if ( ! function_exists( 'buddyboss_theme_entry_header_audio' ) ) {
	/**
	 * BuddyBoss entry header content for audio.
	 *
	 * @param \WP_Post $post Post object.
	 * @param array    $args Additional arguments.
	 *
	 * @return string
	 */
	function buddyboss_theme_entry_header_audio( $post, $args ) {
		$retval = '';

		/**
		 * First look for an 'audio' shortcode in the content.
		 * If not then look for oembeds
		 */
		$audio_shortcode = buddyboss_theme_pull_shortcode_from_content( $post->post_content, 'audio' );

		if ( ! empty( $gallery_shortcode ) ) {
			$retval  = "<div class='audio'>";
			$retval .= do_shortcode( $audio_shortcode );
			$retval .= '</div>';
		} else {
			$content = do_shortcode( apply_filters( 'the_content', $post->post_content ) );
			$embeds  = get_media_embedded_in_content( $content );

			if ( ! empty( $embeds ) ) {
				$retval = $embeds[0];
			}
		}

		return apply_filters( 'buddyboss_theme_entry_header_audio', $retval, $post, $args );
	}
}

if ( ! function_exists( 'buddyboss_theme_entry_header_slider' ) ) {
	/**
	 * BuddyBoss entry header content for slider.
	 *
	 * @param \WP_Post $post Post object.
	 * @param array    $args Additional arguments.
	 *
	 * @return string
	 */
	function buddyboss_theme_entry_header_slider( $post, $args ) {
		$gallery_shortcode = buddyboss_theme_pull_shortcode_from_content( $post->post_content, 'gallery' );

		if ( ! empty( $gallery_shortcode ) ) {
			$retval  = "<div class='bb-gallery-slider'>";
			$retval .= do_shortcode( $gallery_shortcode );
			$retval .= '</div>';
		} else {
			$retval = '';
		}

		return apply_filters( 'buddyboss_theme_entry_header_slider', $retval, $post, $args );
	}
}

if ( ! function_exists( 'buddyboss_theme_entry_header_image' ) ) {
	/**
	 * BuddyBoss entry header content for image.
	 *
	 * @param \WP_Post $post Post object.
	 * @param array    $args Additional arguments.
	 *
	 * @return string
	 */
	function buddyboss_theme_entry_header_image( $post, $args ) {
		/**
		 * First check if thumbnail image present.
		 * If not, try to pull first image from content
		 */
		$content = '';
		if ( has_post_thumbnail( $post ) ) {
			ob_start();
			?>
			<div class="ratio-wrap">
				<a href="<?php the_permalink(); ?>" class="entry-media entry-img">
					<?php the_post_thumbnail( 'large', array( 'sizes' => '(max-width:768px) 768px, (max-width:1024px) 1024px, 1024px' ) ); ?>
				</a>
			</div>
			<?php
			$content = ob_get_clean();
		} else {
			preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches );
			$first_img = ( ! empty( $matches[1] ) ? $matches[1][0] : '' );
			if ( $first_img && filter_var( $first_img, FILTER_VALIDATE_URL ) ) {
				ob_start();
				?>
				<div class="ratio-wrap">
					<a href="<?php the_permalink(); ?>" class="entry-media entry-img">
						<img src="<?php echo esc_url( $first_img ); ?>">
					</a>
				</div>
				<?php
				$content = ob_get_clean();
			}
		}

		return apply_filters( 'buddyboss_theme_entry_header_image', $content, $post, $args );
	}
}

if ( ! function_exists( 'buddyboss_theme_entry_header_thumbnail' ) ) {
	/**
	 * BuddyBoss entry header content for thumbnail.
	 *
	 * @param \WP_Post $post Post object.
	 * @param array    $args Additional arguments.
	 *
	 * @return string
	 */
	function buddyboss_theme_entry_header_thumbnail( $post, $args ) {
		$content = '';

		if ( has_post_thumbnail( $post ) ) {
			ob_start();
			?>
			<div class="ratio-wrap">
				<a href="<?php the_permalink(); ?>" class="entry-media entry-img">
					<?php the_post_thumbnail( 'large', array( 'sizes' => '(max-width:768px) 768px, (max-width:1024px) 1024px, 1024px' ) ); ?>
				</a>
			</div>
			<?php
			$content = ob_get_clean();
		}

		return apply_filters( 'buddyboss_theme_entry_header_thumbnail', $content, $post, $args );
	}
}

if ( ! function_exists( 'the_exceprt_quote' ) ) {
	/**
	 * Except quote.
	 */
	function the_exceprt_quote() {
		echo get_exceprt_quote();
	}
}

if ( ! function_exists( 'get_exceprt_quote' ) ) {

	function get_exceprt_quote() {
		$retval = '';
		/**
		 * If the entire content is too small, return the whole content.
		 */
		$content = get_the_content();

		// @todo add a filter for this
		$permissible_max_length = 150;

		if ( strlen( $content ) <= $permissible_max_length ) {
			$retval = $content;
		} else {
			/**
			 * Try to get first blockquote element and display stripped cotent from the blcokquote.
			 */
			$blockquotes = buddyboss_theme_get_elements_from_html_string( $content, 'blockquote' );
			$first_quote = $blockquotes->item( 0 );
			if ( ! empty( $first_quote ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$quote_content = strip_tags( $first_quote->nodeValue );
				if ( strlen( $quote_content ) <= $permissible_max_length ) {
					$retval = "<blockquote>{$quote_content}</blockquote>";
				} else {
					$quote_content = substr( $quote_content, 0, $permissible_max_length );
					$retval        = "<blockquote>{$quote_content}...</blockquote>";
				}
			}
		}

		// fall back to get_the_excerpt.
		if ( ! $retval ) {
			$retval = get_the_excerpt();
		}

		return $retval;
	}
}

/**
 * Site Header
 */
if ( ! function_exists( 'buddyboss_theme_header' ) ) {

	function buddyboss_theme_header() {

		// Header check.
		if ( buddyboss_theme_remove_header() ) {
			return;
		}

		$header = (int) buddyboss_theme_get_option( 'buddyboss_header' );
		get_template_part( 'template-parts/header', apply_filters( 'buddyboss_header', $header ) );

	}

	add_action( THEME_HOOK_PREFIX . 'header', 'buddyboss_theme_header' );
}

/**
 * Mobile Header
 */
if ( ! function_exists( 'buddyboss_theme_mobile_header' ) ) {

	function buddyboss_theme_mobile_header() {
		// Mobile header check.
		if ( buddyboss_theme_mobile_remove_header() ) {
			return;
		}

		get_template_part( 'template-parts/header-mobile', apply_filters( 'buddyboss_header_mobile', '' ) );
	}

	add_action( THEME_HOOK_PREFIX . 'header', 'buddyboss_theme_mobile_header' );
}

/**
 * Site Footer
 */
if ( ! function_exists( 'buddyboss_theme_footer_area' ) ) {

	function buddyboss_theme_footer_area() {
		// Footer check.
		if ( buddyboss_theme_remove_footer() ) {
			return;
		}

		get_template_part( 'template-parts/footer', apply_filters( 'buddyboss_footer', '' ) );
	}

	add_action( THEME_HOOK_PREFIX . 'footer', 'buddyboss_theme_footer_area' );
}

/**
 * Site Header
 */
if ( ! function_exists( 'buddyboss_theme_buddypanel' ) ) {

	function buddyboss_theme_buddypanel() {
		$show_buddypanel = buddyboss_theme_get_option( 'buddypanel' );
		$header          = (int) buddyboss_theme_get_option( 'buddyboss_header' );

		if ( is_page_template( 'page-fullscreen.php' ) || ( function_exists( 'bp_is_register_page' ) && bp_is_register_page() ) ) {
			return;
		}

		if ( is_page_template( 'page-fullscreen.php' ) || ( function_exists( 'bp_is_activation_page' ) && bp_is_activation_page() ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( $show_buddypanel ) {

			$menu = is_user_logged_in() ? 'buddypanel-loggedin' : 'buddypanel-loggedout';

			if ( has_nav_menu( $menu ) ) {

				get_template_part( 'template-parts/buddypanel' );

			}
		}
	}

	add_action( THEME_HOOK_PREFIX . 'before_page', 'buddyboss_theme_buddypanel' );
}

/**
 * Single template part content
 */
if ( ! function_exists( 'buddyboss_theme_single_template_part_content' ) ) {

	function buddyboss_theme_single_template_part_content( $post_type ) {
		if ( wp_job_manager_is_post_type() ) :

			get_template_part( 'template-parts/content', 'resume' );

		elseif ( gamipress_is_post_type() ) :

			get_template_part( 'template-parts/content', 'gamipress' );

		elseif ( wp_learndash_course_is_post_type() ) :

			get_template_part( 'template-parts/content-sfwd', $post_type );

		else :

			get_template_part( 'template-parts/content', $post_type );

			/**
			 * If comments are open or we have at least one comment, load up the comment template.
			 */
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;

		endif;
	}

	add_action( THEME_HOOK_PREFIX . '_single_template_part_content', 'buddyboss_theme_single_template_part_content' );
}

/**
 * Check Learndash course post type
 *
 * @return boolean
 * @since 1.7.3
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'wp_learndash_course_is_post_type' ) ) {

	function wp_learndash_course_is_post_type() {

		if ( class_exists( 'SFWD_LMS' ) && is_singular( 'sfwd-courses' ) ) {
			return true;
		}

		return false;
	}
}

/**
 * Check BuddyPanel position.
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddypanel_position_right' ) ) {

	function buddypanel_position_right() {
		$show_buddypanel   = buddyboss_theme_get_option( 'buddypanel' );
		$header            = (int) buddyboss_theme_get_option( 'buddyboss_header' );
		$buddypanel_toggle = buddyboss_theme_get_option( 'buddypanel_toggle' );

		if ( 3 === $header ) {
			$buddypanel_side = buddyboss_theme_get_option( 'buddypanel_position_h3' );
		} else {
			$buddypanel_side = buddyboss_theme_get_option( 'buddypanel_position' );
		}

		if ( is_page_template( 'page-fullscreen.php' ) || ( function_exists( 'bp_is_register_page' ) && bp_is_register_page() ) ) {
			return;
		}

		if ( is_page_template( 'page-fullscreen.php' ) || ( function_exists( 'bp_is_activation_page' ) && bp_is_activation_page() ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( ( $show_buddypanel || 3 === $header ) && $buddypanel_side && 'right' === $buddypanel_side && $buddypanel_toggle ) {
			$toggle_panel = '<a href="#" class="bb-toggle-panel"><i class="bb-icon-l bb-icon-sidebar"></i></a>';
			return $toggle_panel;
		}
	}
}

/**
 * Filter the except length to 20 words.
 *
 * @param int $length Excerpt length.
 *
 * @return int (Maybe) modified excerpt length.
 */
function bb_custom_excerpt_length( $length ) {
	return 25;
}

add_filter( 'excerpt_length', 'bb_custom_excerpt_length', 15 );

/**
 * Filter the excerpt "read more" string.
 *
 * @param string $more "Read more" excerpt string.
 *
 * @return string (Maybe) modified "read more" excerpt string.
 */
function bb_excerpt_more( $more ) {
	return '&hellip;';
}

add_filter( 'excerpt_more', 'bb_excerpt_more' );


if ( ! function_exists( 'buddyboss_comment' ) ) {

	function buddyboss_comment( $comment, $args, $depth ) {
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( 'div' == $args['style'] ) {
			$tag       = 'div';
			$add_below = 'comment';
		} else {
			$tag       = 'li';
			$add_below = 'div-comment';
		}
		?>

		<<?php echo esc_attr( $tag ); ?> <?php comment_class( $args['has_children'] ? 'parent' : '', $comment ); ?> id="comment-<?php comment_ID(); ?>">

	<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">

			<?php
			if ( 0 != $args['avatar_size'] ) {
				$platform_author_link = buddyboss_theme_get_option( 'blog_platform_author_link' );
				if ( function_exists( 'bp_core_get_user_domain' ) && $platform_author_link ) {
					$user_link = bp_core_get_user_domain( $comment->user_id );
				} else {
					$user_link = get_comment_author_url( $comment );
				}
				?>
				<div class="comment-author vcard">
					<a href="<?php echo ! empty( $user_link ) ? esc_url( $user_link ) : ''; ?>">
						<?php echo get_avatar( $comment, $args['avatar_size'] ); ?>
					</a>
				</div>
			<?php } ?>

		<div class="comment-content-wrap">
			<div class="comment-meta comment-metadata">
				<?php
				printf(
					/* translators: %s: Author related metas. */
					__( '%s', 'buddyboss-theme' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.I18n.NoEmptyStrings
					sprintf(
						'<cite class="fn comment-author"><a href="%s" rel="external nofollow ugc" class="url">%s</a></cite>',
						empty( $user_link ) ? '' : esc_url( $user_link ),
						get_comment_author_link( $comment )
					)
				);
				?>
				<a class="comment-date" href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
					<?php
					printf(
						/* translators: %s: Author comment date. */
						__( '%1$s', 'buddyboss-theme' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.I18n.NoEmptyStrings
						get_comment_date( '', $comment ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.I18n.NoEmptyStrings
						get_comment_time() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.I18n.NoEmptyStrings
					);
					?>
				</a>
			</div>

			<?php if ( '0' == $comment->comment_approved ) { ?>
				<p>
					<em class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'buddyboss-theme' ); ?></em>
				</p>
			<?php } ?>

			<div class="comment-text">
				<?php
				comment_text(
					$comment,
					array_merge(
						$args,
						array(
							'add_below' => $add_below,
							'depth'     => $depth,
							'max_depth' => $args['max_depth'],
						)
					)
				);
				?>
			</div>

			<footer class="comment-footer">
				<?php
				comment_reply_link(
					array_merge(
						$args,
						array(
							'reply_text' => esc_html__( 'Reply', 'buddyboss-theme' ),
							'add_below' => $add_below,
							'depth'     => $depth,
							'max_depth' => $args['max_depth'],
							'before'    => '',
							'after'     => '',
						)
					)
				);
				?>

				<?php edit_comment_link( esc_html__( 'Edit', 'buddyboss-theme' ), '', '' ); ?>
			</footer>
		</div>		</article>
		<?php
	}
}

if ( ! function_exists( 'buddyboss_pagination' ) ) {

	/**
	 * Custom Pagination
	 */
	function buddyboss_pagination() {
		global $paged, $wp_query;

		$max_page = 0;

		if ( ! $max_page ) {
			$max_page = $wp_query->max_num_pages;
		}

		if ( ! $paged ) {
			$paged = 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$nextpage = intval( $paged ) + 1;

		if ( is_front_page() || is_home() ) {
			$template = 'home';
		} elseif ( is_category() ) {
			$template = 'category';
		} elseif ( is_search() ) {
			$template = 'search';
		} else {
			$template = 'archive';
		}

		$class = ( true ) ? ' post-infinite-scroll' : '';
		$label = __( 'Load More', 'buddyboss-theme' );

		if ( ! is_single() && ( $nextpage <= $max_page ) ) {
			$attr = 'data-page=' . $nextpage . ' data-template=' . $template;
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="bb-pagination pagination-below"><a class="button-load-more-posts' . esc_attr( $class ) . '" href="' . esc_url( next_posts( $max_page, false ) ) . "\" esc_attr( $attr )>" . esc_html( $label ) . '</a></div>';
		}
	}
}

if ( ! function_exists( 'bb_set_row_post_class' ) ) {

	function bb_set_row_post_class( $classes, $class, $post_id ) {

		// Condition for archive posts for elementor.
		if ( in_array( 'elementor-post elementor-grid-item', $classes ) ) {
			return $classes;
		}

		// Condition for archive posts for beaver themer.
		if ( in_array( 'fl-post-grid-post', $classes ) ) {
			return $classes;
		}

		global $wp_query;
		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

		$blog_type = 'masonry'; // standard, grid, masonry.

		$blog_type = apply_filters( 'bb_blog_type', $blog_type );

		if ( is_search() ) {
			$classes[] = 'hentry search-hentry';
			return $classes;
		}

		if ( get_post_type() !== 'post' ) {
			return $classes;
		}

		if ( 'masonry' === $blog_type ) {
			$classes[] = ( 0 === $wp_query->current_post && 1 == $paged ) ? 'bb-grid-2-3 first' : ''; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		} elseif ( ( 'grid' === $blog_type ) && ( ( is_archive() ) || ( is_search() ) || ( is_author() ) || ( is_category() ) || ( is_home() ) || ( is_tag() ) ) ) {
			$classes[] = ( 0 === $wp_query->current_post && 1 == $paged ) ? 'lg-grid-2-3 md-grid-1-1 sm-grid-1-1 bb-grid-cell first' : 'lg-grid-1-3 md-grid-1-2 bb-grid-cell sm-grid-1-1'; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		} elseif ( ( is_related_posts() ) ) {
			$classes[] = 'lg-grid-1-3 md-grid-1-2 bb-grid-cell sm-grid-1-1';
		}

		// Return the array.
		return $classes;
	}

	add_filter( 'post_class', 'bb_set_row_post_class', 10, 3 );
}

/**
 * Single Post Featured Image Dependant Class
 */
if ( ! function_exists( 'featuredimg_custom_post_class' ) ) {

	function featuredimg_custom_post_class( $classes ) {

		$featured_img      = 'default-fi';
		$featured_img_type = apply_filters( 'bb_featured_type', $featured_img );

		if ( is_single() ) {
			$classes[] = $featured_img_type;
		}

		// Return the array.
		return $classes;
	}

	add_filter( 'post_class', 'featuredimg_custom_post_class', 10, 3 );
}

if ( ! function_exists( 'is_related_posts' ) ) {
	function is_related_posts() {
		global $is_related_posts;
		return $is_related_posts;
	}
}

/**
 * Wrap video in container
 */
if ( ! function_exists( 'buddyboss_theme_embed_html' ) ) {

	function buddyboss_theme_embed_html( $html ) {
		return '<div class="video-container">' . $html . '</div>';
	}

	// This is removed due to issue with multipe embed option given in Gutenberg.
	// add_filter( 'embed_oembed_html', 'buddyboss_theme_embed_html', 10, 3 );
	// add_filter( 'video_embed_html', 'buddyboss_theme_embed_html' );
}

/**
 * Yoast Breadcrumb Support
 */
if ( ! function_exists( 'bb_yoast_breadcrumb' ) ) {

	function bb_yoast_breadcrumb() {
		if ( function_exists( 'yoast_breadcrumb' ) ) {
			yoast_breadcrumb( '<div id="breadcrumbs" class="bb-yoast-breadcrumbs">', '</div>' );
		}
	}

	add_action( THEME_HOOK_PREFIX . 'begin_content', 'bb_yoast_breadcrumb' );
}

/**
 * Header Search bar
 */
if ( ! function_exists( 'buddyboss_theme_header_search' ) ) {

	function buddyboss_theme_header_search() {
		$show_search = buddyboss_theme_get_option( 'desktop_component_opt_multi_checkbox', 'desktop_header_search' );

		if ( $show_search ) {
			get_template_part( 'template-parts/header-search' );
		}
	}

	add_action( THEME_HOOK_PREFIX . 'header', 'buddyboss_theme_header_search' );
}

/**
 * Function that checks if BuddyPress plugin is active
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddyboss_is_bp_active' ) ) {

	function buddyboss_is_bp_active() {
		if ( function_exists( 'bp_is_active' ) ) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Check if we are on some of WC pages
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddyboss_is_woocommerce' ) ) {

	function buddyboss_is_woocommerce() {

		if ( function_exists( 'is_woocommerce' ) ) {
			return ( is_woocommerce() || is_shop() || is_product_tag() || is_product_category() || is_product()
			// || is_cart()
			// || is_checkout()
			// || is_account_page()
			);
		}
	}
}

/**
 * Check if we are on some of learndash pages
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddyboss_is_learndash' ) ) {

	function buddyboss_is_learndash() {
		global $post;

		if ( class_exists( 'SFWD_LMS' ) ) {
			if ( is_object( $post ) ) {
				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				return ( ( 'sfwd-courses' == $post->post_type ) || ( 'sfwd-topic' == $post->post_type ) || ( 'sfwd-lessons' == $post->post_type ) || ( 'sfwd-quiz' == $post->post_type ) );
			}
		}
	}
}

/**
 * Check if we are on some of learndash pages
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddyboss_is_learndash_inner' ) ) {

	function buddyboss_is_learndash_inner() {
		global $post;

		// Do not run on search results page.
		if ( is_search() || is_archive() ) {
			return;
		}

		if ( class_exists( 'SFWD_LMS' ) ) {
			if ( is_object( $post ) ) {
				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				return ( ( 'sfwd-topic' == $post->post_type ) || ( 'sfwd-lessons' == $post->post_type ) || ( 'sfwd-quiz' == $post->post_type ) );
			}
		}
	}
}

/**
 * Check if LearnDash focus mode is enabled.
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'ld_30_focus_mode_enable' ) ) {
	function ld_30_focus_mode_enable() {

		if ( class_exists( 'SFWD_LMS' ) ) {
			$focus_mode = \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );

			$post_types = [
				'sfwd-lessons',
				'sfwd-topic',
				'sfwd-assignment',
				'sfwd-quiz',
			];

			if ( in_array( get_post_type(), $post_types ) ) {
				if ( 'yes' === $focus_mode ) {
					return true;
				}
			}
		}

	}
}

/**
 * Check if we are on inner pages of lifterLMS
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddyboss_is_lifterlms_inner' ) ) {

	function buddyboss_is_lifterlms_inner() {

		if ( class_exists( 'LifterLMS' ) ) {
			return ( is_singular( 'lesson' ) || is_singular( 'llms_quiz' ) || is_singular( 'llms_assignment' ) );
		}

	}
}

if ( ! function_exists( 'buddyboss_is_learndash_brand_logo' ) ) {

	function buddyboss_is_learndash_brand_logo() {
		global $post;

		if ( class_exists( 'SFWD_LMS' ) ) {
			$logo = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_logo' );

			if ( ! empty( $logo ) ) {

				return $logo;

			} else {

				return;

			}
		}

	}
}

/**
 * Check if learndash focus mode is enabled
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddyboss_theme_ld_focus_mode' ) ) {

	function buddyboss_theme_ld_focus_mode() {

		if ( class_exists( 'SFWD_LMS' ) ) {
			$focus_mode = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );

			if ( 'yes' === $focus_mode ) {
				return true;
			} else {
				return false;
			}
		}
	}
}

if ( ! function_exists( 'buddyboss_theme_ld_focus_style' ) ) {

	function buddyboss_theme_ld_focus_style() {

		if ( class_exists( 'SFWD_LMS' ) ) {
			$focus_mode               = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );
			$focus_mode_content_width = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_content_width' );
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if ( 'default' == $focus_mode_content_width ) {
				$focus_mode_content_width = '960px';
			}

			if ( 'yes' === $focus_mode ) {
				echo '<style id="learndash-focus-mode-style">';
				echo '.ld-in-focus-mode .learndash-wrapper .learndash_content_wrap{max-width: ' . $focus_mode_content_width . '}'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '.ld-in-focus-mode .learndash-wrapper .bb-lms-header .lms-header-title, .ld-in-focus-mode .learndash-wrapper .bb-lms-header .lms-header-instructor{max-width: ' . $focus_mode_content_width . '}'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				if ( 'inherit' == $focus_mode_content_width || '1600px' == $focus_mode_content_width ) {
					echo '.ld-in-focus-mode.single #learndash-course-header{max-width: ' . $focus_mode_content_width . '}'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo '</style>';
			} else {
				return;
			}
		}
	}

	add_action( 'wp_head', 'buddyboss_theme_ld_focus_style', 100 );
}

/**
 * Check if we are on some of lifterLMS pages
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddyboss_is_lifterlms' ) ) {

	function buddyboss_is_lifterlms() {

		if ( class_exists( 'LifterLMS' ) ) {
			return ( is_course() || is_courses() || is_lesson() || is_quiz() || is_singular( 'llms_assignment' ) || is_membership() || is_memberships() || is_membership_category() || is_membership_tag() || is_membership_taxonomy() || is_llms_account_page() || is_llms_checkout() );
		}

	}
}

if ( ! function_exists( 'buddyboss_is_llms_inner' ) ) {
	function buddyboss_is_llms_inner() {
		if ( class_exists( 'LifterLMS' ) ) {
			return ( is_lesson() || is_quiz() || is_singular( 'llms_assignment' ) );
		}
	}
}

if ( ! function_exists( 'buddyboss_is_llms_courses' ) ) {
	function buddyboss_is_llms_courses() {
		if ( class_exists( 'LifterLMS' ) ) {
			return ( is_courses() || is_memberships() || is_course_taxonomy() || is_membership_taxonomy() );
		}
	}
}

if ( ! function_exists( 'buddyboss_is_llms_page' ) ) {
	function buddyboss_is_llms_page() {
		if ( class_exists( 'LifterLMS' ) ) {
			return ( is_llms_account_page() || is_llms_checkout() );
		}
	}
}

if ( ! function_exists( 'buddyboss_is_llms_post' ) ) {
	function buddyboss_is_llms_post() {
		if ( class_exists( 'LifterLMS' ) ) {
			return ( is_membership() );
		}
	}
}

if ( ! function_exists( 'buddyboss_is_academy' ) ) {

	/**
	 * Function to check is single academy course page.
	 *
	 * @since 2.6.00
	 *
	 * @return bool|void
	 */
	function buddyboss_is_academy() {
		if ( class_exists( 'Academy' ) ) {
			return ( is_singular( 'academy_courses' ) );
		}
	}
}

/**
 * Check if we are on inner pages of Tutor LMS
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddyboss_is_tutorlms_inner' ) ) {

	function buddyboss_is_tutorlms_inner() {

		if ( function_exists( 'tutor' ) ) {
			return (
				is_singular( tutor()->lesson_post_type ) ||
				is_singular( tutor()->quiz_post_type ) ||
				is_singular( tutor()->assignment_post_type ) ||
				is_singular( 'tutor-google-meet' ) ||
				is_singular( 'tutor_zoom_meeting' )
			);
		}

	}
}

/**
 * Check if we are on some of TutorLMS pages
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddyboss_is_tutorlms' ) ) {

	function buddyboss_is_tutorlms() {

		if ( function_exists( 'tutor' ) ) {
			global $wp_query;
			$post_type = get_query_var( 'post_type' );
			if ( ! is_array( $post_type ) ) {
				$post_type = array( $post_type );
			}
			$course_category = get_query_var( 'course-category' );

			return (
				(
					in_array( tutor()->course_post_type, $post_type, true ) ||
					( ! empty( $course_category ) && $wp_query->is_archive )
				) ||
				is_single_course() ||
				is_singular( tutor()->lesson_post_type ) ||
				is_singular( tutor()->quiz_post_type ) ||
				is_singular( tutor()->assignment_post_type )
			);
		}
	}
}

/**
 * Is the current user online
 *
 * @param $user_id
 *
 * @return bool
 */
if ( ! function_exists( 'bb_is_user_online' ) ) {

	function bb_is_user_online( $user_id ) {

		if ( ! function_exists( 'bp_get_user_last_activity' ) ) {
			return;
		}

		$last_activity = strtotime( bp_get_user_last_activity( $user_id ) );

		if ( empty( $last_activity ) ) {
			return false;
		}

		// the activity timeframe is 5 minutes.
		$activity_timeframe = 5 * MINUTE_IN_SECONDS;
		return ( time() - $last_activity <= $activity_timeframe );
	}
}



/**
 * Cover Image Callback
 */
if ( ! function_exists( 'buddyboss_theme_cover_image_callback' ) ) {

	function buddyboss_theme_cover_image_callback( $params = array() ) {
		if ( empty( $params ) ) {
			return;
		}

		// Profile Cover Image.
		$profile_cover = buddyboss_theme_get_option( 'buddyboss_profile_cover_default', 'url' );
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( ! empty( $profile_cover ) && empty( $params['cover_image'] ) && 'xprofile' == $params['component'] ) {
			$params['cover_image'] = $profile_cover;
		}

		// Group Cover Image.
		$group_cover = buddyboss_theme_get_option( 'buddyboss_group_cover_default', 'url' );
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		if ( ! empty( $group_cover ) && empty( $params['cover_image'] ) && 'groups' == $params['component'] ) {
			$params['cover_image'] = $group_cover;
		}

		return '
			#buddypress #header-cover-image {
				height: 225px;
				background-image: url(' . $params['cover_image'] . ');
			}
		';
	}
}

/**
 * Set default profile cover image
 */
if ( ! function_exists( 'buddyboss_theme_cover_image_css' ) ) {

	function buddyboss_theme_cover_image_css( $settings = array() ) {
		$settings['callback'] = 'buddyboss_theme_cover_image_callback';

		return $settings;
	}

	add_filter( 'bp_before_xprofile_cover_image_settings_parse_args', 'buddyboss_theme_cover_image_css', 10, 1 );
	add_filter( 'bp_before_groups_cover_image_settings_parse_args', 'buddyboss_theme_cover_image_css', 10, 1 );
}

if ( ! function_exists( 'buddyboss_theme_bp_get_add_follow_button' ) ) {

	/**
	 * Follow button.
	 *
	 * @param array $button HTML markup for follow button.
	 *
	 * @return array Array of button element.
	 */
	function buddyboss_theme_bp_get_add_follow_button( $button ) {

		if ( 'follow-button following' === $button['wrapper_class'] ) {
			$button['link_class'] .= ' small';
		} else {
			$button['link_class'] .= ' small outline';
		}

		$button['parent_element'] = 'div';
		$button['button_element'] = 'button';

		return $button;
	}

	add_filter( 'bp_get_add_follow_button', 'buddyboss_theme_bp_get_add_follow_button' );
}

/**
 * Group Admins Count
 */
if ( ! function_exists( 'buddyboss_theme_bp_get_group_admins_count' ) ) {

	function buddyboss_theme_bp_get_group_admins_count() {
		global $groups_template;
		$group = $groups_template->group;

		if ( ! empty( $group->admins ) ) {
			return sizeof( $group->admins ); // phpcs:ignore
		}
	}
}

/**
 * LearnDash inner panel
 */
if ( ! function_exists( 'buddypanel_is_learndash_inner' ) ) {

	function buddypanel_is_learndash_inner() {
		global $post;

		if ( class_exists( 'SFWD_LMS' ) ) {
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			return ( ( isset( $post->post_type ) && 'sfwd-topic' == $post->post_type ) || ( isset( $post->post_type ) && 'sfwd-lessons' == $post->post_type ) || ( isset( $post->post_type ) && 'sfwd-quiz' == $post->post_type ) );
		}
	}
}

/**
 * LifterLMS inner panel
 */
if ( ! function_exists( 'buddypanel_is_lifterlms_inner' ) ) {

	function buddypanel_is_lifterlms_inner() {
		global $post;

		if ( class_exists( 'LifterLMS' ) ) {
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			return ( ( isset( $post->post_type ) && 'lesson' == $post->post_type ) || ( isset( $post->post_type ) && 'llms_quiz' == $post->post_type ) || ( isset( $post->post_type ) && 'llms_assignment' == $post->post_type ) );
		}
	}
}

/**
 * Add logout link and profile dropdown items when BP is disabled.
 */
if ( ! function_exists( 'buddyboss_theme_add_logout_link' ) ) {

	function buddyboss_theme_add_logout_link() {
		if ( ! function_exists( 'bp_is_active' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'header-my-account',
					'menu_id'        => 'header-my-account-menu',
					'container'      => false,
					'fallback_cb'    => '',
					'depth'          => 2,
					'walker'         => new BuddyBoss_SubMenuWrap(),
					'menu_class'     => 'bb-my-account-menu',
				)
			);
			echo '<li class="logout-link"><a href="' . esc_url( wp_logout_url() ) . '">' . esc_html__( 'Log Out', 'buddyboss-theme' ) . '</a></li>';
		}
	}

	add_action( THEME_HOOK_PREFIX . 'header_user_menu_items', 'buddyboss_theme_add_logout_link' );
}

/**
 * Add logout link when BP is disabled.
 */
if ( ! function_exists( 'buddyboss_theme_header_my_account_menu' ) ) {

	function buddyboss_theme_header_my_account_menu() {
		wp_nav_menu(
			array(
				'theme_location' => 'header-my-account',
				'menu_id'        => 'header-my-account-menu',
				'container'      => false,
				'fallback_cb'    => '',
				'depth'          => 2,
				'walker'         => new BuddyBoss_SubMenuWrap(),
				'menu_class'     => 'bb-my-account-menu',
			)
		);
	}

	add_action( THEME_HOOK_PREFIX . 'after_bb_profile_menu', 'buddyboss_theme_header_my_account_menu' );
}

/**
 * Remove theme header
 */
if ( ! function_exists( 'buddyboss_theme_remove_header' ) ) {

	function buddyboss_theme_remove_header() {

		if ( is_page_template( 'page-fullscreen.php' ) || ( function_exists( 'bp_is_register_page' ) && bp_is_register_page() ) || ( function_exists( 'bp_is_activation_page' ) && bp_is_activation_page() ) ) {
			return apply_filters( 'buddyboss_theme_remove_header', true );
		}
	}
}

/**
 * Remove theme mobile header
 */
if ( ! function_exists( 'buddyboss_theme_mobile_remove_header' ) ) {

	function buddyboss_theme_mobile_remove_header() {

		if ( is_page_template( 'page-fullscreen.php' ) || ( function_exists( 'bp_is_register_page' ) && bp_is_register_page() ) || ( function_exists( 'bp_is_activation_page' ) && bp_is_activation_page() ) ) {
			return apply_filters( 'buddyboss_theme_mobile_remove_header', true );
		}
	}
}

/**
 * Remove theme footer
 */
if ( ! function_exists( 'buddyboss_theme_remove_footer' ) ) {

	function buddyboss_theme_remove_footer() {
		if ( is_page_template( 'page-fullscreen.php' ) || ( function_exists( 'bp_is_register_page' ) && bp_is_register_page() ) || ( function_exists( 'bp_is_activation_page' ) && bp_is_activation_page() ) || buddypanel_is_learndash_inner() ) {
			return apply_filters( 'buddyboss_theme_remove_footer', true );
		}
	}
}

/*
 !
 * Function to trim excerpt
 */
if ( ! function_exists( 'bb_get_excerpt' ) ) {
	function bb_get_excerpt( $text, $lenght ) {
		$content = substr( $text, 0, $lenght );

		if ( strlen( $content ) < strlen( $text ) ) {
			$content = $content . '&hellip;';
		}

		return $content;
	}
}

/**
 * WP Job Manager post types
 */
if ( ! function_exists( 'wp_job_manager_is_post_type' ) ) {

	function wp_job_manager_is_post_type() {
		global $post;

		if ( class_exists( 'WP_Job_Manager' ) ) {

			if ( is_singular( 'resume' ) ) {
				return true;
			} else {
				return false;
			}
		}
	}
}

/**
 * GamiPress post types
 */
if ( ! function_exists( 'gamipress_is_post_type' ) ) {

	function gamipress_is_post_type() {
		global $post;

		if ( class_exists( 'GamiPress' ) && ! empty( $post->post_type ) ) {

			$post_type_achievement = gamipress_get_achievement_types_slugs();
			$post_type_rank        = gamipress_get_rank_types_slugs();

			if ( in_array( $post->post_type, $post_type_achievement ) || in_array( $post->post_type, $post_type_rank ) ) {
				return true;
			}
		}

		return false;
	}
}

/**
 * Callback for WordPress 'prepend_attachment' filter.
 *
 * Change the attachment page image size to 'large'
 *
 * @param string $attachment_content the attachment html
 *
 * @return string $attachment_content the attachment html
 * @see wp-includes/post-template.php
 *
 * @package WordPress
 * @category Attachment
 */
if ( ! function_exists( 'buddyboss_theme_custom_prepend_attachment' ) ) {

	function buddyboss_theme_custom_prepend_attachment( $attachment_content ) {
		// set the attachment image size to 'large'.
		$attachment_content = sprintf( '<p class="attachment">%s</p>', wp_get_attachment_link( 0, 'full', false ) );

		// return the attachment content.
		return $attachment_content;
	}

	add_filter( 'prepend_attachment', 'buddyboss_theme_custom_prepend_attachment' );
}

if ( ! function_exists( 'buddyboss_theme_get_header_unread_messages' ) ) {

	function buddyboss_theme_get_header_unread_messages() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_success(
				array(
					'message' => __( 'You need to be loggedin.', 'buddyboss-theme' ),
				)
			);
		}

		$response = array();

		ob_start();

		get_template_part( 'template-parts/unread-messages' );

		$response['contents'] = ob_get_clean();

		wp_send_json_success( $response );
	}

	add_action( 'wp_ajax_buddyboss_theme_get_header_unread_messages', 'buddyboss_theme_get_header_unread_messages' );
	add_action( 'wp_ajax_nopriv_buddyboss_theme_get_header_unread_messages', 'buddyboss_theme_get_header_unread_messages' );
}

/**
 * Check if current page template is Elementor Full Width template.
 */
if ( ! function_exists( 'bb_is_elementor_header_footer_template' ) ) {

	function bb_is_elementor_header_footer_template() {
		global $post, $wp_query;

		$id = 0;

		if ( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) {
			$id = $post->ID;
		} elseif ( isset( $wp_query ) && is_object( $wp_query ) && isset( $wp_query->post ) && ! empty( $wp_query->post ) ) {
			$id = $wp_query->post->ID;
		}

		if ( 'elementor_header_footer' === get_post_meta( $id, '_wp_page_template', true ) ) {
			return true;
		}
	}
}

/**
 * Update site content grid class
 */
if ( ! function_exists( 'bb_add_elementor_content_class' ) ) {

	function bb_add_elementor_content_class() {

		if ( bb_is_elementor_header_footer_template() ) {
			add_filter(
				'buddyboss_site_content_grid_class',
				function () {
					return 'bb-elementor-content';
				}
			);
		}
	}

	add_action( THEME_HOOK_PREFIX . 'before_header', 'bb_add_elementor_content_class' );
}

/**
 * Remove Header/Footer for BuddyBoss App.
 */
if ( ! function_exists( 'bb_theme_remove_header_footer_for_buddyboss_app' ) ) {

	function bb_theme_remove_header_footer_for_buddyboss_app() {

		if (
			(
				function_exists( 'bbapp_is_loaded_from_inapp_browser' ) &&
				bbapp_is_loaded_from_inapp_browser()
			) ||
			(
				function_exists( 'appboss_is_loaded_from_inapp_browser' ) &&
				appboss_is_loaded_from_inapp_browser()
			)
		) {
			/* Disable the default template which loads on mobile app */
			if ( function_exists( 'bbapp_disable_default_inapp_browser_template' ) ) {
				bbapp_disable_default_inapp_browser_template();
			} elseif ( function_exists( 'appboss_disable_default_inapp_browser_template' ) ) {
				appboss_disable_default_inapp_browser_template();
			}

			/* Remove WP Adminbar */
			add_filter( 'show_admin_bar', '__return_false', 99 );

			/* Remove Theme Header Footer */
			remove_action( THEME_HOOK_PREFIX . 'header', 'buddyboss_theme_header' );
			remove_action( THEME_HOOK_PREFIX . 'header', 'buddyboss_theme_mobile_header' );
			remove_action( THEME_HOOK_PREFIX . 'header', 'buddyboss_theme_header_search' );
			remove_action( THEME_HOOK_PREFIX . 'footer', 'buddyboss_theme_footer_area' );
			remove_action( THEME_HOOK_PREFIX . 'before_page', 'buddyboss_theme_buddypanel' );

			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				remove_action( THEME_HOOK_PREFIX . 'header', array( buddyboss_theme()->elementor_pro_helper(), 'do_header' ), 0 );
				remove_action( THEME_HOOK_PREFIX . 'footer', array( buddyboss_theme()->elementor_pro_helper(), 'do_footer' ), 0 );
				remove_action( THEME_HOOK_PREFIX . 'before_header', array( buddyboss_theme()->elementor_pro_helper(), 'remove_theme_header_class' ), 0 );
			}

			/* Remove Header Class */
			add_filter(
				'body_class',
				function ( array $classes ) {
					if ( in_array( 'sticky-header', $classes ) ) {
						unset( $classes[ array_search( 'sticky-header', $classes ) ] );
					}

					return $classes;
				}
			);
		}
	}

	add_action( 'init', 'bb_theme_remove_header_footer_for_buddyboss_app' );
}

if ( ! function_exists( 'buddyboss_theme_sudharo_tapas' ) ) {
	/**
	 * Theme sudho tapas.
	 *
	 * @since 1.6.0
	 */
	function buddyboss_theme_sudharo_tapas() {
		$saved_licenses = get_option( 'bboss_updater_saved_licenses' );
		if ( is_multisite() ) {
			$saved_site_licenses = get_site_option( 'bboss_updater_saved_licenses' );
			if ( ! empty( $saved_site_licenses ) ) {
				$saved_licenses = $saved_site_licenses;
			}
		}

		$license_is_there = false;
		$expired_license  = false;
		if ( ! empty( $saved_licenses ) ) {
			foreach ( $saved_licenses as $package_id => $license_details ) {
				if (
					! empty( $license_details['license_key'] ) &&
					! empty( $license_details['token'] ) &&
					! empty( $license_details['product_keys'] ) &&
					in_array( 'BB_THEME', $license_details['product_keys'], true )
				) {
					$token = $license_details['token'];

					list( $header, $payload, $signature ) = explode( '.', $token );

					$payload = json_decode( base64_decode( $payload ), true );
					$exp     = $payload['licence_exp'];
					if ( ! empty( $exp ) && strtotime( $exp ) > time() ) {
						$license_is_there = true;
						if ( isset( $license_details['is_active'] ) && false === $license_details['is_active'] ) {
							$expired_license = true;
						}
					}
				}
			}
		}
		if ( ! $license_is_there && ! $expired_license ) {
			if ( is_multisite() ) {
				update_site_option( 'be5f330bbd49d6160ff4658ac3d219ee', '1' );
			} else {
				update_option( 'be5f330bbd49d6160ff4658ac3d219ee', '1' );
			}
		} else {
			if ( is_multisite() ) {
				delete_site_option( 'be5f330bbd49d6160ff4658ac3d219ee' );
			} else {
				delete_option( 'be5f330bbd49d6160ff4658ac3d219ee' );
			}
		}
	}

	add_action( 'admin_init', 'buddyboss_theme_sudharo_tapas', 999999 );
	add_action( 'after_switch_theme', 'buddyboss_theme_sudharo_tapas' );
}

if ( ! function_exists( 'bb_theme_reply_link_attribute_change' ) ) {

	function bb_theme_reply_link_attribute_change( $retval, $r, $args ) {

		if ( ! function_exists( 'buddypress' ) && ! bp_is_active( 'forums' ) ) {
			return;
		}

		// Get the reply to use it's ID and post_parent.
		$reply = bbp_get_reply( bbp_get_reply_id( (int) $r['id'] ) );

		// Bail if no reply or user cannot reply.
		if ( empty( $reply ) || ! bbp_current_user_can_access_create_reply_form() ) {
			return;
		}

		// If single user replies page then no need to open a modal for reply to.
		if ( bbp_is_single_user_replies() ) {
			return $retval;
		}

		// Build the URI and return value.
		$uri = remove_query_arg( array( 'bbp_reply_to' ) );
		$uri = add_query_arg( array( 'bbp_reply_to' => $reply->ID ), bbp_get_topic_permalink( bbp_get_reply_topic_id( $reply->ID ) ) );
		$uri = wp_nonce_url( $uri, 'respond_id_' . $reply->ID );
		$uri = $uri . '#new-post';

		// Only add onclick if replies are threaded.
		if ( bbp_thread_replies() ) {

			// Array of classes to pass to moveForm.
			$move_form = array(
				$r['add_below'] . '-' . $reply->ID,
				$reply->ID,
				$r['respond_id'],
				$reply->post_parent,
			);

			// Build the onclick.
			$onclick = ' onclick="return addReply.moveForm(\'' . implode( "','", $move_form ) . '\');"';

			// No onclick if replies are not threaded.
		} else {
			$onclick = '';
		}

		$modal = 'data-modal-id-inline="new-reply-' . $reply->post_parent . '"';

		// Add $uri to the array, to be passed through the filter.
		$r['uri'] = $uri;
		$retval   = $r['link_before'] . '<a data-balloon=" ' . esc_html__( 'Reply', 'buddyboss-theme' ) . ' " data-balloon-pos="up" href="' . esc_url( $r['uri'] ) . '" class="bbp-reply-to-link ' . $reply->ID . ' "' . $modal . $onclick . '><i class="bb-icon-l bb-icon-reply"></i><span class="bb-forum-reply-text">' . esc_html( $r['reply_text'] ) . '</span></a>' . $r['link_after'];

		return $retval;
	}
}

if ( ! function_exists( 'bb_theme_topic_link_attribute_change' ) ) {

	function bb_theme_topic_link_attribute_change( $retval, $r, $args ) {

		if ( ! function_exists( 'buddypress' ) && ! bp_is_active( 'forums' ) ) {
			return;
		}
		$retval = $r['link_before'] . '<a data-balloon=" ' . esc_html__( 'Reply', 'buddyboss-theme' ) . ' " data-balloon-pos="up" href="' . esc_url( $r['uri'] ) . '" data-modal-id="bbp-reply-form" class="bbp-reply-to-link"><i class="bb-icon-l bb-icon-reply"></i><span class="bb-forum-reply-text">' . esc_html( $r['reply_text'] ) . '</span></a>' . $r['link_after'];
		return apply_filters( 'bb_theme_topic_link_attribute_change', $retval, $r, $args );
	}
}

if ( ! function_exists( 'bb_set_unread_notification' ) ) {

	/**
	 * Added new function to unread notification from header
	 *
	 * @since BuddyBoss Theme 1.5.8
	 */
	function bb_set_unread_notification() {

		if ( ! function_exists( 'buddypress' ) && ! bp_is_active( 'notifications' ) ) {
			return;
		}

		$notif_id = bb_theme_filter_input_string( INPUT_POST, 'notification_id' );
		if ( 'all' !== $notif_id ) {
			$notif_id = filter_input( INPUT_POST, 'notification_id', FILTER_SANITIZE_NUMBER_INT );
		}
		if ( ! empty( $notif_id ) && 'all' !== $notif_id ) {
			BP_Notifications_Notification::update(
				array( 'is_new' => 0 ),
				array( 'id' => $notif_id )
			);
		} elseif ( 'all' === $notif_id ) {
			$user_id          = bp_loggedin_user_id();
			$notification_ids = BP_Notifications_Notification::get(
				array(
					'user_id'           => $user_id,
					'order_by'          => 'date_notified',
					'sort_order'        => 'DESC',
					'page'              => 1,
					'per_page'          => 25,
					'update_meta_cache' => false,
				)
			);
			if ( $notification_ids ) {
				foreach ( $notification_ids as $notification_id ) {
					BP_Notifications_Notification::update(
						array( 'is_new' => 0 ),
						array( 'id' => $notification_id->id )
					);
				}
			}
		}
		$response = array();
		ob_start();
		get_template_part( 'template-parts/unread-notifications' );
		$response['contents']            = ob_get_clean();
		$response['total_notifications'] = bp_notifications_get_unread_notification_count( bp_displayed_user_id() );
		wp_send_json_success( $response );
	}

	add_action( 'wp_ajax_buddyboss_theme_unread_notification', 'bb_set_unread_notification' );
}

if ( ! function_exists( 'bb_theme_elementor_reply_link_attribute_change' ) ) {

	function bb_theme_elementor_reply_link_attribute_change( $retval, $r, $args ) {

		if ( ! function_exists( 'buddypress' ) && ! bp_is_active( 'forums' ) ) {
			return;
		}

		// Get the reply to use it's ID and post_parent.
		$reply = bbp_get_reply( bbp_get_reply_id( (int) $r['id'] ) );

		// Bail if no reply or user cannot reply.
		if ( empty( $reply ) || ! bbp_current_user_can_access_create_reply_form() ) {
			return;
		}

		// If single user replies page then no need to open a modal for reply to.
		if ( bbp_is_single_user_replies() ) {
			return $retval;
		}

		// Build the URI and return value.
		$uri = remove_query_arg( array( 'bbp_reply_to' ) );
		$uri = add_query_arg( array( 'bbp_reply_to' => $reply->ID ), bbp_get_topic_permalink( bbp_get_reply_topic_id( $reply->ID ) ) );
		$uri = wp_nonce_url( $uri, 'respond_id_' . $reply->ID );
		$uri = $uri . '#new-post';

		// Only add onclick if replies are threaded.
		if ( bbp_thread_replies() ) {

			// Array of classes to pass to moveForm.
			$move_form = array(
				$r['add_below'] . '-' . $reply->ID,
				$reply->ID,
				$r['respond_id'],
				$reply->post_parent,
			);

			// Build the onclick.
			$onclick = ' onclick="return addReply.moveForm(\'' . implode( "','", $move_form ) . '\');"';

			// No onclick if replies are not threaded.
		} else {
			$onclick = '';
		}

		$modal = 'data-modal-id-inline="new-reply-' . $reply->post_parent . '"';

		// Add $uri to the array, to be passed through the filter.
		$r['uri'] = $uri;
		$retval   = $r['link_before'] . '<a data-balloon=" ' . esc_html__( 'Reply', 'buddyboss-theme' ) . ' " data-balloon-pos="up" href="' . esc_url( $r['uri'] ) . '" class="bbp-reply-to-link ' . $reply->ID . ' "><i class="bb-icon-l bb-icon-reply"></i><span class="bb-forum-reply-text">' . esc_html( $r['reply_text'] ) . '</span></a>' . $r['link_after'];

		return $retval;
	}
}

if ( ! function_exists( 'bb_theme_elementor_topic_link_attribute_change' ) ) {

	function bb_theme_elementor_topic_link_attribute_change( $retval, $r, $args ) {

		if ( ! function_exists( 'buddypress' ) && ! bp_is_active( 'forums' ) ) {
			return;
		}

		$url    = bbp_get_topic_last_reply_url( $r['id'] ) . '?bbp_reply_to=0#new-post';
		$retval = $r['link_before'] . '<a data-balloon=" ' . esc_html__( 'Reply', 'buddyboss-theme' ) . ' " data-balloon-pos="up" href="' . esc_url( $url ) . '" class="bbp-reply-to-link"><i class="bb-icon-l bb-icon-reply"></i><span class="bb-forum-reply-text">' . esc_html( $r['reply_text'] ) . '</span></a>' . $r['link_after'];
		return apply_filters( 'bb_theme_topic_link_attribute_change', $retval, $r, $args );
	}
}

/**
 * Edit button alter href when elementor activity.
 *
 * @param array $buttons     Array of Buttons visible on activity entry.
 * @param int   $activity_id Activity ID.
 *
 * @return mixed
 * @since BuddyBoss 1.5.1
 */
function bb_theme_elementor_activity_edit_button( $buttons, $activity_id ) {
	global $bb_theme_elementor_activity;
	if ( isset( $buttons['activity_edit'] ) && true === $bb_theme_elementor_activity ) {
		$activity = new BP_Activity_Activity( $activity_id );

		if ( ! empty( $activity->id ) ) {
			$buttons['activity_edit']['button_attr']['href'] = bp_activity_get_permalink( $activity_id ) . 'edit';

			$classes  = explode( ' ', $buttons['activity_edit']['button_attr']['class'] );
			$edit_key = array_search( 'edit', $classes, true );
			if ( ! empty( $edit_key ) ) {
				unset( $classes[ $edit_key ] );
			}
			$buttons['activity_edit']['button_attr']['class'] = implode( ' ', $classes );
		}
	}

	return $buttons;
}
add_filter( 'bp_nouveau_get_activity_entry_buttons', 'bb_theme_elementor_activity_edit_button', 10, 2 );

/**
 * Output the privacy option inside an Elementor Activity Loop widget.
 *
 * @since BuddyBoss 1.2.3
 */
if ( ! function_exists( 'bb_theme_elementor_bp_nouveau_activity_privacy' ) ) {
	function bb_theme_elementor_bp_nouveau_activity_privacy() {
		if ( ! function_exists( 'buddypress' ) ) {
			return;
		}

		if ( bp_activity_user_can_edit() && ! bp_is_group() ) {

			if ( bp_is_active( 'groups' ) && buddypress()->groups->id === bp_get_activity_object_name() ) {
				return;
			}

			$privacy            = bp_get_activity_privacy();
			$activity_id        = bp_get_activity_id();
			$activity_url       = bp_activity_get_permalink( $activity_id );
			$activity_metas     = function_exists( 'bb_activity_get_metadata' ) ? bb_activity_get_metadata( $activity_id ) : bp_activity_get_meta( $activity_id );
			$media_activity     = ( 'media' === $privacy || ( isset( $_REQUEST['action'] ) && 'media_get_activity' === $_REQUEST['action'] ) );
			$document_activity  = ( 'document' === $privacy || ( isset( $_REQUEST['action'] ) && 'document_get_activity' === $_REQUEST['action'] ) );
			$parent_activity_id = false;
			$group_id           = false;
			$album_id           = false;
			$folder_id          = false;

			// Get media privacy to show.
			if ( bp_is_active( 'media' ) ) {
				if ( $media_activity ) {
					$media_id = BP_Media::get_activity_media_id( $activity_id );
					$media    = new BP_Media( $media_id );

					if ( ! empty( $media ) ) {
						$privacy  = $media->privacy;
						$group_id = $media->group_id;
						$album_id = $media->album_id;

						if ( ! empty( $album_id ) ) {
							$album   = new BP_Media_Album( $album_id );
							$privacy = $album->privacy;
						} else {
							$parent_activity_id = get_post_meta( $media->attachment_id, 'bp_media_parent_activity_id', true );
						}
					}
				}

				if ( $document_activity ) {
					$document_id = BP_Document::get_activity_document_id( $activity_id );
					$document    = new BP_Document( $document_id );
					if ( ! empty( $document ) ) {
						$privacy   = $document->privacy;
						$group_id  = $document->group_id;
						$folder_id = $document->folder_id;

						if ( ! empty( $folder_id ) ) {
							$folder_id = bp_document_get_root_parent_id( $folder_id );
							$folder    = new BP_Document_Folder( $folder_id );
							$privacy   = $folder->privacy;
						} else {
							$parent_activity_id = get_post_meta( $document->attachment_id, 'bp_document_parent_activity_id', true );
						}
					}
				}

				$activity_album_id = $activity_metas['bp_media_album_activity'][0] ?? '';
				if ( ! empty( $activity_album_id ) ) {
					$album_id       = $activity_album_id;
					$album          = new BP_Media_Album( $album_id );
					$privacy        = $album->privacy;
					$media_activity = true;
				} else {
					$media_ids = $activity_metas['bp_media_ids'][0] ?? '';
					if ( ! empty( $media_ids ) ) {
						$media_ids = explode( ',', $media_ids );
						$media_id  = ! empty( $media_ids ) ? $media_ids[0] : false;
						$media     = new BP_Media( $media_id );

						if ( ! empty( $media->album_id ) ) {
							$album_id       = $media->album_id;
							$album          = new BP_Media_Album( $album_id );
							$privacy        = $album->privacy;
							$media_activity = true;
							bp_activity_update_meta( $activity_id, 'bp_media_album_activity', $album_id );
						}
					}
				}

				$activity_folder_id = $activity_metas['bp_document_folder_activity'][0] ?? '';
				if ( ! empty( $activity_folder_id ) ) {
					$folder_id         = $activity_folder_id;
					$folder_id         = bp_document_get_root_parent_id( $folder_id );
					$folder            = new BP_Document_Folder( $folder_id );
					$privacy           = $folder->privacy;
					$document_activity = true;
				} else {
					$document_ids = $activity_metas['bp_document_ids'][0] ?? '';
					if ( ! empty( $document_ids ) ) {
						$document_ids = explode( ',', $document_ids );
						$document_id  = ! empty( $document_ids ) ? $document_ids[0] : false;
						$document     = new BP_Document( $document_id );

						if ( ! empty( $document->folder_id ) ) {
							$folder_id         = $document->folder_id;
							$folder_id         = bp_document_get_root_parent_id( $folder_id );
							$folder            = new BP_Document_Folder( $folder_id );
							$privacy           = $folder->privacy;
							$document_activity = true;
							bp_activity_update_meta( $activity_id, 'bp_document_folder_activity', $folder_id );
						}
					}
				}
			}

			if ( $media_activity && empty( $group_id ) && $parent_activity_id ) {
				$parent_activity = new BP_Activity_Activity( $parent_activity_id );

				if ( ! empty( $parent_activity->id ) ) {
					$group_id = $parent_activity->item_id;
				}
			}

			if ( $document_activity && empty( $group_id ) && $parent_activity_id ) {
				$parent_activity = new BP_Activity_Activity( $parent_activity_id );

				if ( ! empty( $parent_activity->id ) ) {
					$group_id = $parent_activity->item_id;
				}
			}

			if ( ! empty( $group_id ) ) {
				return;
			}

			$privacy_items = bp_activity_get_visibility_levels();

			?>
			<div class="bb-media-privacy-wrap bb-media-privacy-wrap--el-activity">
				<span class="bp-tooltip privacy-wrap" data-bp-tooltip-pos="left" data-bp-tooltip="<?php echo ! empty( $privacy_items[ $privacy ] ) ? esc_attr( $privacy_items[ $privacy ] ) : esc_attr( $privacy ); ?>"><span class="privacy selected <?php echo esc_attr( $privacy ); ?>"></span></span>
				<ul class="activity-privacy">

					<li class="bb-edit-privacy" data-value="<?php echo esc_url( $activity_url ); ?>" >
						<a href="<?php echo esc_url( $activity_url ); ?>" data-value="<?php echo esc_url( $activity_url ); ?>"><?php esc_html_e( 'Edit Post Privacy', 'buddyboss-theme' ); ?></a>
					</li>

				</ul>
			</div>
			<?php
		}
	}
}

/**
 * Add tooltips and icon for follow button in member directories.
 *
 * @param bool $enabled_message_action Is enabled or not message button.
 * @param int  $member_id Member ID.
 * @param int  $current_user_id Current member ID.
 *
 * @since BuddyBoss 1.8.7
 *
 * @return bool True if enabled message button otherwise false.
 */
function buddyboss_theme_bb_member_loop_show_message_button( $enabled_message_action, $member_id, $current_user_id ) {
	if ( function_exists( 'bb_messages_user_can_send_message' ) ) {
		return $enabled_message_action;
	}

	return (bool) ( $enabled_message_action && 'yes' === buddyboss_theme()->buddypress_helper()->buddyboss_theme_show_private_message_button( $member_id, $current_user_id ) );
}
add_filter( 'bb_member_loop_show_message_button', 'buddyboss_theme_bb_member_loop_show_message_button', 10, 3 );

/**
 * Function will remove buddypanel state cookie once buddypanel toggle off.
 *
 * @since 2.0.0
 */
function bb_theme_remove_cookie_buddypanel_toggle_off() {
	$buddypanel_toggle = buddyboss_theme_get_option( 'buddypanel_toggle' );
	if ( ! $buddypanel_toggle && isset( $_COOKIE['buddypanel'] ) ) {
		unset( $_COOKIE['buddypanel'] );
		setcookie( 'buddypanel', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
	}
}
add_action( 'init', 'bb_theme_remove_cookie_buddypanel_toggle_off' );

/**
 * Remove cancel comment reply link URL.
 *
 * @since BuddyBoss 2.0.2
 *
 * @param string $link Cancel comment reply link URL.
 *
 * @return null
 */
function buddyboss_theme_remove_blog_comment_reply_link( $link ) {
	return '';
}
add_filter( 'cancel_comment_reply_link', 'buddyboss_theme_remove_blog_comment_reply_link', 10 );

/**
 * Add cancel comment reply link URL before submit button.
 *
 * @since BuddyBoss 2.0.2
 *
 * @param string $submit_field HTML markup for the submit field.
 * @param array  $args         Arguments passed to comment_form().
 *
 * @return string Return HTML markup for cancel reply link and submit button.
 */
function buddyboss_theme_add_blog_comment_reply_link( $submit_button, $args ) {
	$cancel_reply_link = '';

	if ( get_option( 'thread_comments' ) ) {
		$cancel_reply_link .= $args['cancel_reply_before'];
		remove_filter( 'cancel_comment_reply_link', 'buddyboss_theme_remove_blog_comment_reply_link', 10 );
		$cancel_reply_link .= get_cancel_comment_reply_link( $args['cancel_reply_link'] );
		$cancel_reply_link .= $args['cancel_reply_after'];
	}

	return $cancel_reply_link . $submit_button;
}
add_action( 'comment_form_submit_button', 'buddyboss_theme_add_blog_comment_reply_link', 99, 2 );

/**
 * Check if tutor spotlight mode is enabled.
 *
 * @since BuddyBoss 2.4.90
 */
if ( ! function_exists( 'buddyboss_theme_is_tutorlms_spotlight_mode' ) ) {

	function buddyboss_theme_is_tutorlms_spotlight_mode() {

		if ( function_exists( 'tutor_utils' ) ) {
			return tutor_utils()->get_option( 'enable_spotlight_mode' );
		}

		return false;
	}
}

/**
 * Check if we are on some of tutorlms inner pages.
 *
 * @package BuddyBoss_Theme
 */
if ( ! function_exists( 'buddyboss_theme_is_tutorlms_inner' ) ) {

	function buddyboss_theme_is_tutorlms_inner() {
		global $post;

		// Do not run on search results page.
		if ( is_search() || is_archive() ) {
			return false;
		}

		if ( 
			function_exists( 'tutor' ) &&
			is_object( $post ) &&
			in_array( $post->post_type, array( 'lesson', 'tutor_assignments', 'tutor_quiz' ) )
		) {
			return true;
		}

		return false;
	}
}
