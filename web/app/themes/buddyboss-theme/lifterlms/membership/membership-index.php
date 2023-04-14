<?php

$view              = get_option( 'bb_theme_lifter_membership_grid_list', 'grid' );
$class_grid_active = ( 'grid' === $view ) ? 'active' : '';
$class_list_active = ( 'list' === $view ) ? 'active' : '';
$class_grid_show   = ( 'grid' === $view ) ? 'grid-view bb-grid' : '';
$class_list_show   = ( 'list' === $view ) ? 'list-view bb-list' : '';

?>
<div id="lifterlms-content" class="lifterlms-course-list lifterlms-course-list--memberships">
    <form id="bb-membership-directory-form" class="bb-courses-directory" method="get" action="">

		<?php $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1; ?>
        <input type="hidden" name="current_page" value="<?php echo esc_attr( $paged ); ?>">

        <div class="flex align-items-center bb-courses-header">
            <h1 class="page-title"><?php lifterlms_page_title(); ?></h1>
            <div id="courses-dir-search" class="bs-dir-search" role="search">
                <div id="search-members-form" class="bs-search-form">
                    <label for="bs_members_search" class="bp-screen-reader-text"><?php _e( 'Search', 'buddyboss-theme' ); ?></label>
                    <input type="text" name="search" id="bs_members_search" value="<?php echo ! empty( $_GET['search'] ) ? $_GET['search'] : ''; ?>" placeholder="<?php _e( 'Search Memberships...', 'buddyboss-theme' ); ?>">
                </div>
            </div>
        </div>

        <div class="ld-secondary-header ld-secondary-header--llms">

            <div class="bb-secondary-list-tabs flex align-items-center" id="subnav" aria-label="Members directory secondary navigation" role="navigation">

                <div class="grid-filters" data-view="llms-membership">
                    <a href="#" class="layout-view layout-view-course layout-grid-view bp-tooltip <?php echo esc_attr( $class_grid_active ); ?>" data-view="grid" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Grid View','buddyboss-theme' ); ?>">
                        <i class="dashicons dashicons-screenoptions" aria-hidden="true"></i>
                    </a>

                    <a href="#" class="layout-view layout-view-course layout-list-view bp-tooltip <?php echo esc_attr( $class_list_active ); ?>" data-view="list" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('List View','buddyboss-theme' ); ?>">
                        <i class="dashicons dashicons-menu" aria-hidden="true"></i>
                    </a>
                </div>

            </div>

        </div>


        <div class="grid-view bb-grid">

            <div id="course-dir-list" class="course-dir-list bs-dir-list">
				<?php if ( have_posts() ) { ?>
                    <ul class="bb-course-items <?php echo esc_attr( $class_grid_show . $class_list_show ); ?>" aria-live="assertive" aria-relevant="all">
						<?php

						/* Start the Loop */
						while ( have_posts() ) :
							the_post();

							/*
							 * Include the Post-Format-specific template for the content.
							 * If you want to override this in a child theme, then include a file
							 * called content-___.php (where ___ is the Post Format name) and that will be used instead.
							 */

							llms_get_template( 'membership/membership-index-loop.php' );

						endwhile;
						?>
                    </ul>

                    <div class="bb-lms-pagination"><?php
					global $wp_query;
					$big        = 999999999; // need an unlikely integer
					$translated = __( 'Page', 'buddyboss-theme' ); // Supply translatable string

					echo paginate_links( array(
						'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
						'format'             => '?paged=%#%',
						'current'            => max( 1, get_query_var( 'paged' ) ),
						'total'              => $wp_query->max_num_pages,
						'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
					) ); ?></div><?php
				} else { ?>
                    <aside class="bp-feedback bp-template-notice ld-feedback info">
                    <span class="bp-icon" aria-hidden="true"></span>
                    <p><?php _e( 'Sorry, no courses were found.', 'buddyboss-theme' ); ?></p>
                    </aside><?php
				} ?>
            </div>
        </div>


    </form>

</div>
