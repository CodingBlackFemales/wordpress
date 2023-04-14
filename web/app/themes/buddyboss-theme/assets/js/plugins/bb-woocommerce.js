( function ( $ ) {

    "use strict";

    window.BuddyBossThemeWc = {
        init: function () {
            this.wcShop();
            this.wcProductSlider();
        },
        
        wcShop: function() {

            $( '.wc-widget-area .widget_product_categories ul.product-categories .cat-item.cat-parent' ).each( function () {
                var self = $( this );

                self.prepend( '<span class="expand-parent"><i class="bb-icon-angle-right"></i></span>' );
                if ( self.is( '.current-cat, .current-cat-parent' ) ) {
                    self.find( '.expand-parent' ).first().addClass( 'active' );
                    self.addClass( 'cat-expanded' );
                }
            } );

            $( document ).on( 'click', 'ul.product-categories li span.expand-parent', function ( e ) {
                var self = $( this ),
                    catParent = self.closest( 'li.cat-parent' ),
                    catChildrent = catParent.find( 'ul.children' ).first();

                self.toggleClass( 'active' );
                catChildrent.slideToggle( '200' );
            } );

            $( '.wc-widget-area .widget_product_categories ul.product-categories li .count' ).text( function ( _, text ) {
                return text.replace( /\(|\)/g, '' );
            } );

            $( '#tab-title-reviews a' ).html( function ( i, h ) {
                return h.replace( /\(/g, '<span>' ).replace( /\)/, '</span>' );
            } );

            $( document ).on( 'click', '.bs-quantity .quantity-button:not(".limit")', function () {
                var input_qty = $( this ).parents( '.bs-quantity' ).find( 'input' ),
                    oldValue = input_qty.val(),
                    newVal = oldValue,
                    min = input_qty.attr( 'min' ),
                    max = input_qty.attr( 'max' );

                if ( oldValue == 0 ) {
                    $( this ).parents( '.bs-quantity' ).find( '.quantity-down' ).addClass( 'limit' );
                }

                if ( $( this ).hasClass( 'quantity-up' ) ) {
                    $( this ).parents( '.bs-quantity' ).find( '.quantity-down' ).removeClass( 'limit' );
                    if ( parseInt( oldValue ) == parseInt( max ) - 1 ) {
                        $( this ).parents( '.bs-quantity' ).find( '.quantity-up' ).addClass( 'limit' );
                    }
                    if ( max.length === 0 ) {
                        newVal = parseInt( oldValue ) + 1;
                    } else {
                        if ( parseInt( oldValue ) >= parseInt( max ) ) {
                            newVal = parseInt( oldValue );
                        } else {
                            newVal = parseInt( oldValue ) + 1;
                        }
                    }
                } else {
                    if ( parseInt( oldValue ) == parseInt( min ) + 1 ) {
                        $( this ).parents( '.bs-quantity' ).find( '.quantity-down' ).addClass( 'limit' );
                    }
                    if ( parseInt( oldValue ) <= parseInt( min ) ) {
                        newVal = parseInt( oldValue );
                    } else {
                        newVal = parseInt( oldValue ) - 1;
                        $( this ).parents( '.bs-quantity' ).find( '.quantity-up' ).removeClass( 'limit' );
                    }
                }
                $( this ).parents( '.bs-quantity' ).find( 'input' ).val( parseInt( newVal ) ).trigger( 'change' );
            } );

            $( document ).on( "change", "form[name='checkout'] input[name='payment_method']", function () {
                if ( $( this ).is( ':checked' ) ) {
                    $( this ).addClass( "selected_payment_method" );
                } else {
                    removeClass( "selected_payment_method" );
                }
            } );

            $( document ).on( 'click', 'a.push-my-account-nav', function ( event ) {
                event.preventDefault();

                var self = $( this );
                var navContainer = $( this ).closest( '.woocommerce-MyAccount-navigation' );
                navContainer.find( 'ul.woocommerce-MyAccount-menu' ).slideToggle();
            } );

            $( document ).on( 'click', 'span.wc-widget-area-expand', function ( event ) {
                var self = $( this );
                var widgetsContainer = $( this ).closest( '#secondary' );
                widgetsContainer.find( '.wc-widget-area-expandable' ).slideToggle();
                widgetsContainer.find( '.widget.widgets_expand' ).toggleClass( 'active' );
            } );

            if ( $( '.widget_layered_nav' ).length > 0 ) {
                $( '.widget_layered_nav' ).on( "click", "li input[type='checkbox']", function () {
                    window.location.href = $( this ).data( 'href' );
                } );
            }

            if ( $( '.widget_price_filter' ).length > 0 ) {
                $( '.price_slider' ).on( "slidestop", function ( event, ui ) {
                    $( '.price_slider' ).parent().parent().submit();
                } );
            }

            function filterCheckboxes() {
                // Checkbox Styling
                $( '.woocommerce-widget-layered-attribute input[type=checkbox].bb-input-switch' ).each( function () {
                    var $this = $( this );
                    $this.addClass( 'checkbox' );
                    if ( $this.is( ':checked' ) ) {
                        $this.next( 'span.checkbox' ).addClass( 'on' );
                        $this.closest( 'li.woocommerce-widget-layered-nav-list__item' ).addClass( 'on' );
                    }
                    ;
                    $this.fadeTo( 0, 0 );
                    $this.change( function () {
                        $this.next( 'span.checkbox' ).toggleClass( 'on' );
                    } );
                } );
            }
            filterCheckboxes();

            var $couponCode = $( 'form.woocommerce-cart-form .coupon #coupon_code' );
            var $couponCodeBtn = $( 'form.woocommerce-cart-form .coupon .button' );
            $couponCode.keyup( function () {

                var empty = false;
                $couponCode.each( function () {
                    if ( $( this ).val() == '' ) {
                        empty = true;
                    }
                } );

                if ( empty ) {
                    $couponCodeBtn.removeClass( 'bp-coupon-btn-active' );
                } else {
                    $couponCodeBtn.addClass( 'bp-coupon-btn-active' );
                }
            } );

            $( document ).on( 'click', function ( e ) {
                if ( $( e.target ).closest( '.woocommerce-shipping-calculator .shipping-calculator-form' ).length === 0 ) {
                    $( '.woocommerce-shipping-calculator .shipping-calculator-form' ).hide();
                }
            } );

        },
        
        wcProductSlider: function() {
            function wcProductGallery() {
                var wcGallery = {
                    infinite: false,
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    adaptiveHeight: true,
                    arrows: true,
                    prevArrow: '<span class="bb-slide-prev"><i class="bb-icon-angle-right"></i></span>',
                    nextArrow: '<span class="bb-slide-next"><i class="bb-icon-angle-right"></i></span>',
                }

                setTimeout( function() { //Defer until DOM is ready
                    $( '.woocommerce-product-gallery .flex-control-thumbs' ).not( '.slick-initialized' ).slick( wcGallery );
                },0);
            }

            wcProductGallery();
        },

    };
    
    $( document ).on( 'ready', function () {
        BuddyBossThemeWc.init();
    } );

} )( jQuery );
