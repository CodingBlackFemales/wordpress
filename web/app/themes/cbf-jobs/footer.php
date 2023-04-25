<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package OnePress
 */

$hide_footer = false;
$page_id = get_the_ID();

if ( is_page() ) {
	$hide_footer = get_post_meta( $page_id, '_hide_footer', true );
}

if ( onepress_is_wc_active() ) {
	if ( is_shop() ) {
		$page_id = wc_get_page_id( 'shop' );
		$hide_footer = get_post_meta( $page_id, '_hide_footer', true );
	}
}

if ( ! $hide_footer ) {
	?>
	<!-- Footer -->
		<footer class="pt-4 my-md-5 pt-md-5 border-top">
			<div class="container">
				<div class="row">
					<div class="col-12 col-md">
						<img id="footer-img" class="mb-2" src="https://cbf-test-a847f.firebaseapp.com/img/logo-grey-yellow.png" alt="" height="50">
						<small class="d-block mb-3 text-muted">Copyright &copy; Coding Black Females Ltd 2020</small>

						<ul class="list-inline button-section-area">
							<li class="list-inline-item">
								<a href="https://www.instagram.com/codingblackfemales/" target="_blank">
									<i class="fab fa-instagram"></i>
								</a>
							</li>
							<li class="list-inline-item">
								<a href="https://twitter.com/codingblackfems" target="_blank">
									<i class="fab fa-twitter"></i>
								</a>
							</li>
							<li class="list-inline-item">
								<a href="https://facebook.com/codingblackfemales" target="_blank">
									<i class="fab fa-facebook"></i>
								</a>
							</li>
							<li class="list-inline-item">
								<a href="https://linkedin.com/company/codingblackfemales" target="_blank">
									<i class="fab fa-linkedin"></i>
								</a>
							</li>
						</ul>

					</div>
					 <div class="col-sm-3 col-md">
									<h6>Companies</h6>
									<ul class="list-unstyled text-small">
										<li><a class="text-muted" href="https://jobs.codingblackfemales.com/for-companies/">Post A Job</a></li>
										<li><a class="text-muted" href="https://jobs.codingblackfemales.com/my-account/">Account</a></li>
										<li><a class="text-muted" href="https://jobs.codingblackfemales.com/basket/">Basket</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/supportus.html">Support Us</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/companyprofile.html">Company Profiles</a></li>

									</ul>
								</div>
								<div class="col-sm-3 col-md">
									<h6>Members</h6>
									<ul class="list-unstyled text-small">
										<li><a class="text-muted" href="https://codingblackfemales.com/memberzone.html">Member Zone</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/events.html">Events</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/pinboard.html">Open Projects</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/community.html">Community</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/directory.html">Business Directory</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/shop.html">Shop</a></li>
									</ul>
								</div>
								<div class="col-sm-3 col-md">
									<h6>Content</h6>
									<ul class="list-unstyled text-small">
										<li><a class="text-muted" href="https://codingblackfemales.com/blog.html">Blog</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/youtube.html">YouTube Channel</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/podcast.html">Podcast</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/visibleintech.html">#VisibleInTech</a></li>
									</ul>
								</div>
								<div class="col-sm-3 col-md">
									<h6>About</h6>
									<ul class="list-unstyled text-small">
										<li><a class="text-muted" href="https://mailchi.mp/59b1f57bb4e2/landing-page">Join Mailing List</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/coc.html">Code Of Conduct</a></li>
										<li><a class="text-muted" href="https://codingblackfemales.com/privacy.html">Privacy Policy</a></li>
											<li><a class="text-muted" href="https://codingblackfemales.com/press.html">Press</a></li>
											<li><a class="text-muted" href="https://codingblackfemales.com/team.html">Team</a></li>
									</ul>
								</div>
				</div>
			</div>
		</footer><!-- #colophon -->
	<?php
}
/**
 * Hooked: onepress_site_footer
 *
 * @see onepress_site_footer
 */
do_action( 'onepress_site_end' );
?>
</div><!-- #page -->

<?php do_action( 'onepress_after_site_end' ); ?>

<?php wp_footer(); ?>

</body>
</html>
