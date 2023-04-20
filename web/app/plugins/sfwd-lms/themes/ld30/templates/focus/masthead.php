<?php
/**
 * LearnDash LD30 focus mode masthead.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;
$header         = array(
	'logo_alt' => '',
	'logo_url' => '',
	'text'     => '',
	'text_url' => '',
);
$header['logo'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_logo' );
if ( ! empty( $header['logo'] ) ) {
	$header['logo_alt'] = get_post_meta( $header['logo'], '_wp_attachment_image_alt', true );
	/**
	 * Filters Focus mode header logo alternative text.
	 * This filter will be called only if there is a logo set in LearnDash plugin settings.
	 *
	 * @since 3.1.0
	 *
	 * @param string $logo_alt  Header logo alternative text.
	 * @param int    $course_id Course ID.
	 * @param int    $user_id   User ID.
	 */
	$header['logo_alt'] = apply_filters( 'learndash_focus_header_logo_alt', $header['logo_alt'], $course_id, $user_id );
	/**
	 * Filters Focus mode header logo URL.
	 * This filter will be called only if there is a logo set in LearnDash plugin settings.
	 *
	 * @since 3.1.0
	 *
	 * @param string $logo_url  Header logo URL.
	 * @param int    $course_id Course ID.
	 * @param int    $user_id   User ID.
	 */
	$header['logo_url'] = apply_filters( 'learndash_focus_header_logo_url', get_home_url(), $course_id, $user_id );
} else {
	/**
	 * Filters Focus mode header text. This text is used to display in place of the logo.
	 * This filter will be called only if there is no logo set in LearnDash plugin settings.
	 *
	 * @since 3.1.0
	 *
	 * @param string $header_text Focus mode header text.
	 * @param int    $course_id   Course ID.
	 * @param int    $user_id     User ID.
	 */
	$header['text'] = apply_filters( 'learndash_focus_header_text', '', $course_id, $user_id );
	if ( ! empty( $header['text'] ) ) {
		/**
		 * Filters Focus mode header text URL.
		 * This filter will be called only if there is no logo set in LearnDash plugin settings.
		 *
		 * @since 3.1.0
		 *
		 * @param string $header_text_url Header Text URL
		 * @param int    $course_id       Course ID.
		 * @param int    $user_id         User ID.
		 */
		$header['text_url'] = apply_filters( 'learndash_focus_header_text_url', '', $course_id, $user_id );
	}
}
?>
<div class="ld-focus-header">

	<?php
	/**
	 * Fires before the header mobile nav in the focus template.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-focus-header-mobile-nav-before', $course_id, $user_id );
	?>

	<div class="ld-mobile-nav">
		<a href="#" class="ld-trigger-mobile-nav" aria-label="<?php esc_attr_e( 'Menu', 'learndash' ); ?>">
			<span class="bar-1"></span>
			<span class="bar-2"></span>
			<span class="bar-3"></span>
		</a>
	</div>

	<?php
	/**
	 * Fires before the header logo in the focus template.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-focus-header-logo-before', $course_id, $user_id );
	?>

	<div class="ld-brand-logo">
	<?php
		$header_element = '';
	if ( ! empty( $header['logo'] ) ) {
		if ( ! empty( $header['logo_url'] ) ) {
			$header_element .= '<a href="' . esc_url( $header['logo_url'] ) . '">';
		}
		$header_element .= '<img src="' . esc_url( wp_get_attachment_url( $header['logo'] ) ) . '" alt="' . esc_html( $header['logo_alt'] ) . '" />';
		if ( ! empty( $header['logo_url'] ) ) {
			$header_element .= '</a>';
		}
	} else {
		if ( ! empty( $header['text'] ) ) {
			if ( ! empty( $header['text_url'] ) ) {
				$header_element .= '<a href="' . esc_url( $header['text_url'] ) . '">';
			}
			$header_element .= esc_html( $header['text'] );
			if ( ! empty( $header['text_url'] ) ) {
				$header_element .= '</a>';
			}
		}
	}

		/**
		 * Filters Focus mode Header element markup.
		 *
		 * @since 3.1.0
		 *
		 * @param string $header_element Focus mode header element markup.
		 * @param array  $header         Array of header element details keyed logo_alt, logo_url, text, text_url.
		 * @param int    $course_id      Course ID.
		 * @param int    $user_id        User ID.
		 */
		$header_element = apply_filters( 'learndash_focus_header_element', $header_element, $header, $course_id, $user_id );
		echo $header_element; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs HTML for the header element
	?>
	</div>

	<?php
	/**
	 * Fires after the header logo in the focus template.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-focus-header-logo-after', $course_id, $user_id );

	if ( is_user_logged_in() ) {
		learndash_get_template_part(
			'modules/progress.php',
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
				'context'   => 'focus',
			),
			true
		);
	}

	/**
	 * Fires before the header nav in the focus template.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-focus-header-nav-before', $course_id, $user_id );

	$can_complete = false;
	if ( ( ! learndash_lesson_progression_enabled( $course_id ) ) || ( true === learndash_can_user_bypass( $user_id, 'learndash_course_progression' ) ) ) {
		if ( learndash_user_is_course_children_progress_complete( $user_id, $course_id, $post->ID ) ) {
			$can_complete = true;
		} else {
			$can_complete = false;
		}
	} else {
		$can_complete = learndash_can_complete_step( $user_id, $post->ID, $course_id, true );
	}

	learndash_get_template_part(
		'modules/course-steps.php',
		array(
			'course_id'        => $course_id,
			'course_step_post' => $post,
			'user_id'          => $user_id,
			'course_settings'  => isset( $course_settings ) ? $course_settings : array(),
			'can_complete'     => $can_complete,
			'context'          => 'focus',
		),
		true
	);

	/**
	 * Fires after the header nav in the focus template.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-focus-header-nav-after', $course_id, $user_id );
	?>

	<?php if ( is_user_logged_in() ) : ?>
		<div class="ld-user-menu">
			<?php
			/**
			 * Fires before the user menu in the focus template.
			 *
			 * @since 3.0.0
			 *
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-focus-header-user-menu-before', $course_id, $user_id );

			$user_data = get_userdata( $user_id );
			?>
			<span class="ld-text ld-user-welcome-text">
			<?php
			echo sprintf(
				// translators: Focus mode welcome placeholder.
				esc_html_x( 'Hello, %s!', 'Focus mode welcome placeholder', 'learndash' ),
				/**
				 * Filters Focus mode user welcome name.
				 *
				 * @since 3.0.3
				 *
				 * @param string           $user_nicename User nice name.
				 * @param \WP_User|boolean $user_data     User Object or false if no user found.
				 */
				wp_kses_post( apply_filters( 'ld_focus_mode_welcome_name', $user_data->user_nicename, $user_data ) )
			);
			?>
				</span>

			<span class="ld-profile-avatar">
				<?php
				/**
				 * Fires before the user avatar in the focus template.
				 *
				 * @since 3.0.0
				 *
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-focus-header-avatar-before', $course_id, $user_id );
				echo get_avatar( $user_id );
				/**
				 * Fires after the user avatar in the focus template.
				 *
				 * @since 3.0.0
				 *
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-focus-header-avatar-after', $course_id, $user_id );
				?>
			</span> <!--/.ld-profile-avatar-->

			<?php
			/**
			 * Fires before the header user dropdown in the focus template.
			 *
			 * @since 3.0.0
			 *
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-focus-header-user-dropdown-before', $course_id, $user_id );
			?>

			<span class="ld-user-menu-items">
				<?php
				$custom_menu_items = learndash_30_get_custom_focus_menu_items();

				$menu_items = array(
					'course-home' => array(
						'url'   => get_the_permalink( $course_id ),
						'label' => sprintf(
							// translators: Placeholder for course home link.
							esc_html_x( '%s Home', 'Placeholder for course home link', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'course' )
						),
					),
				);

				if ( $custom_menu_items ) :
					foreach ( $custom_menu_items as $menu_item ) :
						$menu_items[ $menu_item->post_name ] = array(
							'url'        => $menu_item->url,
							'label'      => $menu_item->title,
							'classes'    => esc_attr( 'ld-focus-menu-link ld-focus-menu-' . $menu_item->post_name ),
							'target'     => '',
							'attr_title' => '',
							'xfn'        => '',
						);

						if ( ( property_exists( $menu_item, 'classes' ) ) && ( is_array( $menu_item->classes ) ) ) {
							$classes = array_filter( $menu_item->classes, 'strlen' );
							if ( ! empty( $classes ) ) {
								$menu_items[ $menu_item->post_name ]['classes'] .= ' ' . implode( ' ', $classes );
							}
						}

						if ( ( property_exists( $menu_item, 'target' ) ) && ( ! empty( $menu_item->target ) ) ) {
							$menu_items[ $menu_item->post_name ]['target'] = esc_attr( $menu_item->target );
						}

						if ( ( property_exists( $menu_item, 'attr_title' ) ) && ( ! empty( $menu_item->attr_title ) ) ) {
							$menu_items[ $menu_item->post_name ]['attr_title'] = esc_attr( $menu_item->attr_title );
						}
						if ( ( property_exists( $menu_item, 'xfn' ) ) && ( ! empty( $menu_item->xfn ) ) ) {
							$menu_items[ $menu_item->post_name ]['xfn'] = esc_attr( $menu_item->xfn );
						}

					endforeach;
				endif;

				$menu_items['logout'] = array(
					'url'   => wp_logout_url( get_the_permalink( $course_id ) ),
					'label' => __( 'Logout', 'learndash' ),
				);

				/**
				 * Focus Mode Header menu items.
				 *
				 * @since 3.2.3
				 *
				 * @param array $menu_items Array of menu items.
				 * @param int   $course_id Course ID.
				 * @param int   $user_id   User ID.
				 */
				$menu_items = apply_filters( 'learndash_focus_header_user_dropdown_items', $menu_items, $course_id, $user_id );

				if ( $menu_items && ! empty( $menu_items ) ) :
					foreach ( $menu_items as $slug => $item ) :
						?>
						<a <?php if ( ! empty( $item['classes'] ) ) { ?>
							class="<?php echo esc_attr( $item['classes'] ); ?>"
						<?php } ?> <?php if ( ! empty( $item['target'] ) ) { ?>
							target="<?php echo esc_attr( $item['target'] ); ?>"
						<?php } ?> <?php if ( ! empty( $item['xfn'] ) ) { ?>
							rel="<?php echo esc_attr( $item['xfn'] ); ?>"
						<?php } ?> <?php if ( ! empty( $item['attr_title'] ) ) { ?>
							title="<?php echo esc_attr( $item['attr_title'] ); ?>"
						<?php } ?> href="<?php echo esc_url( $item['url'] ); ?>"><?php echo apply_filters( 'the_title', $item['label'], 0 ); ?></a>
						<?php
					endforeach;
				endif;
				?>
			</span> <!--/.ld-user-menu-items-->

			<?php
			/**
			 * Fires after the header user dropdown in the focus template.
			 *
			 * @since 3.0.0
			 *
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-focus-header-user-dropdown-after', $course_id, $user_id );
			?>

		</div>
		<?php
	endif;
	/**
	 * Fires after the header user dropdown in the focus template.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-focus-header-usermenu-after', $course_id, $user_id );
	?>
</div> <!--/.ld-focus-header-->
