<?php
/**
 * Template page for LearnDash in-app help page.
 *
 * @since 4.4.0.1
 * @version 4.20.2
 *
 * @var array<string, array{id: string, url: string, label: string, description: string, icon: string}> $categories Categories.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Modules\Support\TrustedLogin\TrustedLogin;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>

<div class="wrap learndash-support">
	<div class="logo">
		<img
			src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . 'assets/images/support/learndash-logo.svg' ); ?>"
			alt="LearnDash"
		>
	</div>

	<div class="hero">
		<h1><?php esc_html_e( 'Support', 'learndash' ); ?></h1>
		<h2 class="tagline">
			<?php esc_html_e( 'We\'re here to help you succeed.', 'learndash' ); ?>
		</h2>
	</div>

	<div class="search box">
		<h2><?php esc_html_e( 'Got a question?', 'learndash' ); ?></h2>
		<div class="fields">
			<div class="form-wrapper">
				<form
					method="POST"
					name="search"
					id="search-form"
				>
					<input
						type="text"
						name="keyword"
						placeholder="<?php esc_html_e( 'Ask the DocsBot', 'learndash' ); ?>"
					>
					<button
						type="submit"
						class="submit-button"
					>
						<span class="dashicons dashicons-search submit"></span>
					</button>
				</form>
			</div>
		</div>
	</div>

	<section class="learndash-web-development-upsell box">
		<div class="headline-wrapper">
			<div class="headline">
				<h2>
					<?php esc_html_e( 'Get Professional Help Launching Your Site', 'learndash' ); ?>
				</h2>

				<p class="description">
					<?php
					printf(
						// Translators: %1$s is the opening anchor tag, %2$s is the closing anchor tag.
						esc_html__(
							'Launch your website in as little as two weeks with the help of our dedicated Professional Services Team. %1$sLearn More About our Professional Services Team%2$s',
							'learndash'
						),
						'<a href="https://go.learndash.com/sitedev" target="_blank" rel="noreferrer noopener">',
						'</a>'
					);
					?>
				</p>
			</div>
		</div>
	</section>

	<div class="answers box">
		<div class="headline-wrapper">
			<div class="headline">
				<h2><?php esc_html_e( 'Find The Answers', 'learndash' ); ?></h2>
				<p class="description">
					<?php
					esc_html_e(
						'We\'ve categorized our documentation to help your most pressing questions.',
						'learndash'
					)
					?>
				</p>
			</div>
			<div class="buttons">
				<p><?php esc_html_e( 'Can\'t find what you\'re looking for?', 'learndash' ); ?></p>
				<div class="buttons-list">
					<a
						class="button <?php echo esc_attr( TrustedLogin::$page_slug ); ?>"
						href="
							<?php
							echo esc_url(
								menu_page_url( TrustedLogin::$page_slug, false )
							);
							?>
						"
					>
						<?php esc_html_e( 'Provide Support Access', 'learndash' ); ?>
					</a>
					<a
						class="button create-ticket"
						href="https://account.learndash.com/?tab=support"
						target="_blank"
						rel="noreferrer noopener"
					>
						<?php esc_html_e( 'Create A Support Ticket', 'learndash' ); ?>
					</a>
				</div>
			</div>
		</div>

		<div class="grid">
			<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound?>
			<?php foreach ( $categories as $category_id => $category ) : ?>
			<a
				class="item"
				id="item-<?php echo esc_attr( $category['id'] ); ?>"
				href="<?php echo esc_url( $category['url'] ); ?>"
				target="_blank"
			>
				<div class="label-wrapper">
					<span class="icon">
                        <?php // phpcs:ignore Generic.Files.LineLength.TooLong?>
						<img src="<?php echo esc_url( LEARNDASH_LMS_PLUGIN_URL . '/assets/images/support/' . $category['icon'] . '.png' ); ?>" alt="" >
					</span>
					<h3><?php echo esc_html( $category['label'] ); ?></h3>
					<span class="icon icon-external dashicons dashicons-external"></span>
				</div>
				<?php if ( ! empty( $category['description'] ) ) : ?>
					<p class="description"><?php echo esc_html( $category['description'] ); ?></p>
				<?php endif; ?>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
</div>
