<div class="notification-wrap header-cart-link-wrap cart-wrap menu-item-has-children">
	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="header-cart-link notification-link">
        <span data-balloon-pos="down" data-balloon="<?php _e( 'Cart', 'buddyboss-theme' ); ?>">
			<i class="bb-icon-l bb-icon-shopping-cart"></i>
            <?php
            if ( is_object( WC()->cart ) ) {
             $wc_cart_count = wc()->cart->get_cart_contents_count();
             if( $wc_cart_count != 0 ) { ?>
                 <span class="count"><?php echo wc()->cart->get_cart_contents_count(); ?></span>
             <?php }
            }
            ?>
        </span>
	</a>
    <section class="notification-dropdown">
        <header class="notification-header">
            <h2 class="title"><?php esc_html_e( 'Shopping Cart', 'buddyboss-theme' ); ?></h2>
            <?php
            if ( is_object( WC()->cart ) ) {
	            $wc_cart_count = wc()->cart->get_cart_contents_count();
	            if ( 0 !== $wc_cart_count ) {
		            ?>
		            <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="header-view-cart-link">
			            <?php esc_html_e( 'View Cart', 'buddyboss-theme' ); ?>
		            </a>
		            <?php
	            }
            }
            ?>
        </header>
        <div class="header-mini-cart">
            <?php
            if ( is_object( WC()->cart ) ) {
	            woocommerce_mini_cart();
            }
            ?>
        </div>
    </section>
</div>