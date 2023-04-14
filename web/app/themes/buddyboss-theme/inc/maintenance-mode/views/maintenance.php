<?php
/**
 * Maintenance mode template that's shown to logged out users.
 */
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
		<link rel="profile" href="http://gmpg.org/xfn/11">

		<?php
		wp_site_icon();

		$rtl_css        = is_rtl() ? '-rtl' : '';
		$minified_css   = buddyboss_theme_get_option( 'boss_minified_css' );
		$mincss         = $minified_css ? '.min' : '';
		$version        = buddyboss_theme()->version();
		$theme_template = (int) buddyboss_theme_get_option( 'theme_template' );
		?>
		<link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/css' . $rtl_css . '/theme' . $mincss . '.css?ver=' . $version ); ?>" />
		<link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/css' . $rtl_css . '/maintenance' . $mincss . '.css?ver=' . $version ); ?>" />
		<link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/css/icons-map' . $mincss . '.css?ver=' . $version ); ?>" />
		<link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/css/bb-icons' . $mincss . '.css?ver=' . $version ); ?>" />

		<?php
		if ( 2 === $theme_template ) {
			?>
		    <link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/css' . $rtl_css . '/template-v2' . $mincss . '.css?ver=' . $version ); ?>" />
			<?php
		}
		?>

		<title><?php esc_html_e( 'Down for Maintenance', 'buddyboss-theme' ); ?> | <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>

		<?php wp_head(); ?>
		<?php do_action( 'bb_maintenance_head' ); ?>
	</head>

	<body class="<?php echo esc_attr( 'bb-template-v' . $theme_template ); ?>">
		<?php get_template_part( 'template-parts/content', 'maintenance' ); ?>
		<?php do_action( 'bb_maintenance_footer' ); ?>
		<?php wp_footer(); ?>
	</body>
</html>
