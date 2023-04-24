<?php
/**
 * Template for choosing a package during the Job Listing submission.
 *
 * This template can be overridden by copying it to yourtheme/wc-paid-listings/package-selection.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager-resumes
 * @category    Template
 * @since       1.0.0
 * @version     2.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( $packages || $user_packages ) :
	$checked = 1;
	?>
	<ul class="job_packages">
		<?php if ( $user_packages ) : ?>
			<li class="package-section"><?php _e( 'Your Packages:', 'wp-job-manager-wc-paid-listings' ); ?></li>
			<?php foreach ( $user_packages as $key => $package ) :
				$package = wc_paid_listings_get_package( $package );
				?>
				<li class="user-job-package <?php echo $package->is_featured() ? 'user-job-package-featured' : '' ?>">
					<input type="radio" <?php checked( $checked, 1 ); ?> name="job_package" value="user-<?php echo $key; ?>" id="user-package-<?php echo $package->get_id(); ?>" />
					<label for="user-package-<?php echo $package->get_id(); ?>"><?php echo $package->get_title(); ?></label><br/>
					<?php
					$featured_marking = $package->is_featured() ? __( 'featured', 'wp-job-manager-wc-paid-listings' ) : '';
					if ( $package->get_limit() ) {
						// translators: 1: Posted count. 2: Featured marking. 3: Limit.
						$package_description = _n( '%1$s %2$s job posted out of %3$d', '%1$s %2$s jobs posted out of %3$d', $package->get_count(), 'wp-job-manager-wc-paid-listings' );
						printf( $package_description, $package->get_count(), $featured_marking, $package->get_limit() );
					} else {
						// translators: 1: Posted count. 2: Featured marking.
						$package_description = _n( '%1$s %2$s job posted', '%1$s %2$s jobs posted', $package->get_count(), 'wp-job-manager-wc-paid-listings' );
						printf( $package_description, $package->get_count(), $featured_marking );
					}

					if ( $package->get_duration() ) {
						printf( ', ' . _n( 'listed for %s day', 'listed for %s days', $package->get_duration(), 'wp-job-manager-wc-paid-listings' ), $package->get_duration() );
					}

						$checked = 0;
					?>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php if ( $packages ) : ?>
			<li class="package-section"><?php _e( 'Purchase Package:', 'wp-job-manager-wc-paid-listings' ); ?></li>
			<?php foreach ( $packages as $key => $package ) :
				$product = wc_get_product( $package );
				if ( ! $product->is_type( array( 'job_package', 'job_package_subscription' ) ) || ! $product->is_purchasable() ) {
					continue;
				}
				/* @var $product WC_Product_Job_Package|WC_Product_Job_Package_Subscription */
				if ( $product->is_type( 'variation' ) ) {
					$post = get_post( $product->get_parent_id() );
				} else {
					$post = get_post( $product->get_id() );
				}
				?>
				<li class="job-package <?php echo $product->is_job_listing_featured() ? 'job-package-featured' : '' ?>">
					<input type="radio" <?php checked( $checked, 1 );
					$checked = 0; ?> name="job_package" value="<?php echo $product->get_id(); ?>" id="package-<?php echo $product->get_id(); ?>" />
					<label for="package-<?php echo $product->get_id(); ?>"><?php echo $product->get_title(); ?></label><br/>
					<?php if ( ! empty( $post->post_excerpt ) ) : ?>
						<?php echo apply_filters( 'woocommerce_short_description', $post->post_excerpt ) ?>
					<?php else :
						$limit_marking = $product->get_limit() ? $product->get_limit() : __( 'unlimited', 'wp-job-manager-wc-paid-listings' );
						$featured_marking = $product->is_job_listing_featured() ? __( 'featured', 'wp-job-manager-wc-paid-listings' ) : '';
						// translators: 1: Price. 2: Limit marking (days number or unlimited). 3: Featured marking.
						$product_description = _n( '%1$s for %2$s %3$s job', '%1$s for %2$s %3$s jobs', $product->get_limit(), 'wp-job-manager-wc-paid-listings' );
						printf( $product_description . ' ', $product->get_price_html(), $limit_marking, $featured_marking );

						echo $product->get_duration() ? sprintf( _n( 'listed for %s day', 'listed for %s days', $product->get_duration(), 'wp-job-manager-wc-paid-listings' ), $product->get_duration() ) : '';
					endif; ?>
				</li>

			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
<?php else : ?>

	<p><?php _e( 'No packages found', 'wp-job-manager-wc-paid-listings' ); ?></p>

<?php endif; ?>
