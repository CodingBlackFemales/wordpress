<?php
/**
 * 2025 Promotional Banner Template
 *
 * @package LearnDash\Core
 *
 * @since 4.25.4
 * @version 4.25.4
 *
 * @var string $promo_title     Promotional banner title.
 * @var string $promo_text      Promotional banner description.
 * @var string $shop_now_text   Call-to-action button text.
 * @var string $logo_alt_text   Alt text for LearnDash logo.
 * @var string $promo_alt_text  Alt text for promotional image.
 * @var string $logo_url        URL to LearnDash logo.
 * @var string $promo_image_url URL to promotional image.
 * @var string $shop_url        Shop URL for the button.
 */

?>

<div class="ld-component-promotional-banner">
	<div class="ld-component-promotional-banner__content">
		<div class="ld-component-promotional-banner__text">
			<h2 class="ld-component-promotional-banner__title"><?php echo esc_html( $promo_title ); ?></h2>
			<p class="ld-component-promotional-banner__subtitle"><?php echo wp_kses_post( $promo_text ); ?></p>
			<a href="<?php echo esc_url( $shop_url ); ?>" class="ld-component-promotional-banner__button" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $shop_now_text ); ?>
			</a>
		</div>
		<div class="ld-component-promotional-banner__image-container">
			<div class="ld-component-promotional-banner__images">
				<div class="ld-component-promotional-banner__logo">
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt_text ); ?>" class="ld-component-promotional-banner__logo-image" />
				</div>
				<img src="<?php echo esc_url( $promo_image_url ); ?>" alt="<?php echo esc_attr( $promo_alt_text ); ?>" class="ld-component-promotional-banner__promo-image" />
			</div>
		</div>
	</div>
</div>
