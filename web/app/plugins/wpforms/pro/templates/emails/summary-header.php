<?php
/**
 * Summary header template.
 *
 * This template can be overridden by copying it to yourtheme/wpforms/emails/summary-header.php.
 *
 * Note: This template overrides the Lite version and is only loaded if the Pro version is active.
 *
 * @since 1.8.8
 *
 * @var string $title          Email title.
 * @var array  $header_image   Header image arguments.
 * @var array  $license_banner License banner arguments.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>">
	<meta content="width=device-width, initial-scale=1.0" name="viewport">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="color-scheme" content="light dark">
	<title><?php echo esc_html( $title ); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" bgcolor="#f8f8f8">
<table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%" class="body" role="presentation" bgcolor="#f8f8f8">
	<tr>
		<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
		<td align="center" valign="top" class="body-inner" width="700">
			<div class="wrapper" width="100%" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="container" role="presentation">
					<?php if ( ! empty( $header_image['url_dark'] ) ) : ?>
						<tr class="header-wrapper dark-mode">
							<td align="center" valign="middle" class="header">
								<div class="header-image">
									<img width="260" src="<?php echo esc_url( $header_image['url_dark'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
								</div>
							</td>
						</tr>
					<?php endif; ?>
					<?php if ( ! empty( $header_image['url_light'] ) ) : ?>
						<tr class="header-wrapper light-mode">
							<td align="center" valign="middle" class="header">
								<div class="header-image">
									<img width="260" src="<?php echo esc_url( $header_image['url_light'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
								</div>
							</td>
						</tr>
					<?php endif; ?>

					<?php if ( ! empty( $license_banner ) && ! empty( $license_banner['status'] ) ) : ?>
						<tr>
							<td class="license-banner">
								<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
									<tbody>
										<tr>
											<td class="license-banner-content license-<?php echo esc_attr( $license_banner['status'] ); ?>">
												<?php if ( ! empty( $license_banner['title'] ) ) : ?>
													<h5><?php echo esc_html( $license_banner['title'] ); ?></h5>
												<?php endif; ?>

												<?php if ( ! empty( $license_banner['content'] ) ) : ?>
													<p><?php echo wp_kses_post( implode( '</p><p>', $license_banner['content'] ) ); ?></p>
												<?php endif; ?>

												<?php if ( ! empty( $license_banner['cta']['url'] ) && ! empty( $license_banner['cta']['text'] ) && ! empty( $license_banner['cta']['class'] ) ) : ?>
													<table class="button-wrapper" role="presentation">
														<tr>
															<td class="<?php echo esc_attr( $license_banner['cta']['class'] ); ?>" align="center" border="1" valign="middle">
																<a href="<?php echo esc_url( $license_banner['cta']['url'] ); ?>" class="button-link" target="_blank" rel="noopener noreferrer" bgcolor="#d63638">
																	<?php echo esc_html( $license_banner['cta']['text'] ); ?>
																</a>
															</td>
														</tr>
													</table>
												<?php endif; ?>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					<?php endif; ?>

					<tr>
						<td class="wrapper-inner">
							<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
								<tr>
									<td valign="top" class="content">
