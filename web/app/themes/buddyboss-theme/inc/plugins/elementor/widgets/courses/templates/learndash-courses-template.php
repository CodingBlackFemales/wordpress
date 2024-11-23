<?php
/**
 * @var       $query
 * @var       $nameLower
 * @var       $current_page_url
 * @var array $settings
 * @var       $posts_per_page
 * @var       $current_page
 * @var       $helper
 * @var       $view
 * @var       $course_box_border
 * @var       $class_grid_active
 * @var       $settings_skin
 * @var       $course_cols
 * @var       $class_list_active
 * @var       $tax_query
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
if ( ! defined( 'BB_LMS_WIDGET' ) ) {
	exit;
} // Exit if accessed outside widget

$courses_label = LearnDash_Custom_Label::get_label( 'courses' );
?>
<div id="<?php echo $nameLower ?>-content" <?php echo $this->get_render_attribute_string( 'ld-switch' ); ?>>
	<form data-current_page_url="<?php echo esc_url( $current_page_url ); ?>" id="bb-courses-directory-form" class="bb-elementor-widget bb-courses-directory" method="get" action="">


		<?php $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1; ?>
			<input type="hidden" name="current_page" value="<?php echo esc_attr( $paged ); ?>">
			<div class="flex align-items-center bb-courses-header">
			<?php if ( ! empty( $settings['switch_heading'] ) && 'yes' === $settings['switch_heading'] ) : ?>
				<h4 class="bb-title"><?php echo LearnDash_Custom_Label::get_label( 'courses' ); ?></h4>
			<?php endif; ?>
			<?php if ( ! empty( $settings['switch_search'] ) && 'yes' === $settings['switch_search'] ) : ?>
				<div id="courses-dir-search" class="bs-dir-search" role="search">
					<div id="search-members-form" class="bs-search-form">
						<label for="bs_members_search" class="bp-screen-reader-text"><?php _e( 'Search', 'buddyboss-theme' ); ?></label>
						<input type="text" name="search" id="bs_members_search" value="<?php echo ! empty( $_GET['search'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) : ''; ?>" placeholder="<?php echo sprintf( esc_html__( 'Search %s...', 'buddyboss-theme' ), $courses_label ); ?>">
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $settings['switch_courses_nav'] ) && 'yes' === $settings['switch_courses_nav'] ) : ?>
			<nav class="courses-type-navs main-navs bp-navs dir-navs bp-subnavs">
				<ul class="component-navigation courses-nav">
					<?php
					$navs = array(
						'all' => '<div class="bb-component-nav-item-point">' . sprintf( esc_html__( 'All %s', 'buddyboss-theme' ), $courses_label ) . '</div>' . '<span class="count">' . $query->found_posts . '</span>',
					);

					if ( is_user_logged_in() ) {
						$navs['my-courses'] = '<div class="bb-component-nav-item-point">' . sprintf( esc_html__( 'My %s', 'buddyboss-theme' ), $courses_label ) . '</div>' . '<span class="count">' . buddyboss_theme()->learndash_helper()->get_my_courses_count( null, $tax_query ) . '</span>';
					}

					$navs = apply_filters( 'BuddyBossTheme/Learndash/Archive/Navs', $navs );

					if ( ! empty( $navs ) ) {
						$current_nav = isset( $_GET['type'] ) && isset( $navs[ $_GET['type'] ] ) ? $_GET['type'] : 'all';
						$base_url    = get_post_type_archive_link( 'sfwd-courses' );
						foreach ( $navs as $nav => $text ) {
							$selected_class = $nav == $current_nav ? 'selected' : '';
							$url            = 'all' != $nav ? add_query_arg( array( 'type' => $nav ), $base_url ) : $base_url;
							printf( "<li id='courses-{$nav}' class='{$selected_class}'><a href='%s'>%s</a></li>", $url, $text );
						}
					} else {
						$current_nav = 'all';
					}
					?>
				</ul>
			</nav>
			<input type="hidden" name="type" value="<?php echo esc_attr( $current_nav ); ?>">
		<?php endif; ?>

		<div class="ld-secondary-header">
			<div class="bb-secondary-list-tabs flex align-items-center" id="subnav" aria-label="Members directory secondary navigation" role="navigation">

				<input type="hidden" id="course-order" name="order" value="<?php echo ! empty( $_GET['order'] ) ? $_GET['order'] : 'desc'; ?>"/>
				<input type="hidden" id="post-per-page" name="posts_per_page" value="<?php echo $posts_per_page; ?>"/>
				<input type="hidden" id="current-page" name="current_page" value="<?php echo $current_page; ?>"/>

				<div class="sfwd-courses-filters flex push-right">
					<div class="select-wrap <?php echo ! empty( $settings['orderby_filter'] ) && 'on' === $settings['orderby_filter'] ? 'active' : 'hide'; ?>">
						<select id="sfwd_prs-order-by" name="orderby">
							<?php echo $helper->print_sorting_options(); ?>
						</select>
					</div>

					<?php
					$archive_category_taxonomy = buddyboss_theme_get_option( 'learndash_course_index_categories_filter_taxonomy' );
					if ( empty( $archive_category_taxonomy ) ) {
						$archive_category_taxonomy = 'ld_course_category';
					}

					$tags_array = ! empty( $tags ) ? $tags : array();
					if (
						'ld_course_tag' !== $archive_category_taxonomy &&
						! empty( $tags_array )
					) {
						$tags_str = is_array( $tags_array ) ? implode( ',', $tags_array ) : $tags_array;
						?>
						<input type="hidden" name="filter-block-tags" value="<?php echo $tags_str; ?>"/>
						<?php
					} elseif( 'ld_course_tag' === $archive_category_taxonomy ) {
						if ( 1 === count( $tags_array ) ) {
							$tags_str = is_array( $tags_array ) ? implode( ',', $tags_array ) : $tags_array;
							?>
							<input type="hidden" name="filter-block-tags" value="<?php echo $tags_str; ?>"/>
							<?php
						} else {
							$category_dropdown = buddyboss_theme()->learndash_helper()->print_categories_options( array( 'include' => $tags_array ) );
							?>
							<div class="select-wrap <?php echo ! empty( $settings['category_filter'] ) && 'on' === $settings['category_filter'] ? 'active' : 'hide'; ?>">
								<?php if ( '' !== trim( $category_dropdown ) ) { ?>
									<select id="sfwd_cats-order-by" name="filter-categories">
										<?php echo $category_dropdown; ?>
									</select>
								<?php } ?>
							</div>
							<?php
						}
					}

					$category_array = ! empty( $category ) ? $category : array();
					if (
						'ld_course_category' !== $archive_category_taxonomy &&
						! empty( $category_array )
					) {
						$category_str = is_array( $category_array ) ? implode( ',', $category_array ) : $category_array;
						?>
						<input type="hidden" name="filter-block-categories" value="<?php echo $category_str; ?>"/>
						<?php
					} elseif ( 'ld_course_category' === $archive_category_taxonomy ) {
						if ( 1 === count( $category_array ) ) {
							$category_str = is_array( $category_array ) ? implode( ',', $category_array ) : $category_array;
							?>
							<input type="hidden" name="filter-block-categories" value="<?php echo $category_str; ?>"/>
							<?php
						} else {
							$category_dropdown = buddyboss_theme()->learndash_helper()->print_categories_options( array( 'include' => $category_array ) );
							?>
							<div class="select-wrap <?php echo ! empty( $settings['category_filter'] ) && 'on' === $settings['category_filter'] ? 'active' : 'hide'; ?>">
								<?php if ( '' !== trim( $category_dropdown ) ) { ?>
									<select id="sfwd_cats-order-by" name="filter-categories">
										<?php echo $category_dropdown; ?>
									</select>
								<?php } ?>
							</div>
							<?php
						}
					}
					?>

					<div class="select-wrap <?php echo ! empty( $settings['instructors_filter'] ) && 'on' === $settings['instructors_filter'] ? 'active' : 'hide'; ?>">
						<select id="sfwd_instructors-order-by" name="filter-instructors">
							<?php echo $helper->print_instructors_options(); ?>
						</select>
					</div>
				</div>

				<div class="grid-filters <?php echo ! empty( $settings['grid_filter'] ) && 'on' === $settings['grid_filter'] ? 'active' : 'hide'; ?>" data-view="ld-course">
					<a href="#" class="layout-view layout-view-course layout-grid-view bp-tooltip <?php echo ( 'grid' === $view ) ? esc_attr( 'active' ) : ''; ?>" data-view="grid" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Grid View', 'buddyboss-theme' ); ?>">
						<i class="dashicons dashicons-screenoptions" aria-hidden="true"></i>
					</a>

					<a href="#" class="layout-view layout-view-course layout-list-view bp-tooltip <?php echo ( 'list' === $view ) ? esc_attr( 'active' ) : ''; ?>" data-view="list" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'List View', 'buddyboss-theme' ); ?>">
						<i class="dashicons dashicons-menu" aria-hidden="true"></i>
					</a>
				</div>
			</div>
		</div>

		<div class="grid-view bb-grid grid-box-<?php echo $course_box_border; ?>">

			<div id="course-dir-list" class="course-dir-list bs-dir-list columns-<?php echo $course_cols; ?> <?php echo ! empty( $settings['switch_media'] ) ? 'course-dir-list--media' : 'course-dir-list--hidemedia'; ?> <?php echo ! empty( $settings['switch_status'] ) ? 'course-dir-list--status' : 'course-dir-list--hidestatus'; ?>">
				<?php
				if ( $query->have_posts() ) {
					?>
					<ul class="bb-card-list bb-course-items list-view bb-list <?php echo ( 'list' === $view ) ? '' : esc_attr( 'hide' ); ?> <?php echo ( $settings_skin == 'cover' ) ? esc_attr( 'is-cover' ) : ''; ?>" aria-live="assertive" aria-relevant="all">
						<?php
						/* Start the Loop */
						while ( $query->have_posts() ) :
							$query->the_post();

							/*
							* Include the Post-Format-specific template for the content.
							* If you want to override this in a child theme, then include a file
							* called content-___.php (where ___ is the Post Format name) and that will be used instead.
							*/
							get_template_part( 'learndash/ld30/template-course-item' );

						endwhile;
						?>
					</ul>

					<ul class="bb-card-list bb-course-items grid-view bb-grid <?php echo ( 'grid' === $view || $settings_skin == 'cover' ) ? '' : esc_attr( 'hide' ); ?> <?php echo ( $settings_skin == 'cover' ) ? esc_attr( 'is-cover' ) : ''; ?>" aria-live="assertive" aria-relevant="all">
						<?php
						/* Start the Loop */
						while ( $query->have_posts() ) :
							$query->the_post();

							/*
							* Include the Post-Format-specific template for the content.
							* If you want to override this in a child theme, then include a file
							* called content-___.php (where ___ is the Post Format name) and that will be used instead.
							*/
							get_template_part( 'learndash/ld30/template-course-item' );

						endwhile;

						wp_reset_postdata();
						?>
					</ul>
					<?php if ( ! empty( $settings['switch_pagination'] ) && 'yes' === $settings['switch_pagination'] ) : ?>
						<div <?php echo $this->get_render_attribute_string( 'ld-pagination-switch' ); ?>>
							<?php
							$big        = 999999999; // need an unlikely integer
							$translated = __( 'Page', 'buddyboss-theme' ); // Supply translatable string

							echo paginate_links(
								array(
									'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
									'format'             => '?paged=%#%',
									'current'            => max( 1, get_query_var( 'paged' ) ),
									'total'              => $query->max_num_pages,
									'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
								)
							);
							?>
						</div>
					<?php endif; ?>
					<?php
				} else {
					?>
					<aside class="bp-feedback bp-template-notice ld-feedback info">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php _e( 'Sorry, no courses were found.', 'buddyboss-theme' ); ?></p>
					</aside>
					<?php
				}
				?>
			</div>
		</div>
	</form>
</div>
