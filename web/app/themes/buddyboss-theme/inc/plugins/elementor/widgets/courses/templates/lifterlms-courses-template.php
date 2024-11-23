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

$view              = bb_theme_get_directory_layout_preference( 'llms-course' );
$class_grid_active = ( 'grid' === $view ) ? 'active' : '';
$class_list_active = ( 'list' === $view ) ? 'active' : '';
$class_grid_show   = ( 'grid' === $view || $settings_skin == 'cover' ) ? 'grid-view bb-grid' : '';
$class_list_show   = ( 'list' === $view && $settings_skin != 'cover' ) ? 'list-view bb-list' : '';
$search            = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

?>
<div id="<?php echo $nameLower; ?>-content" <?php echo $this->get_render_attribute_string( 'ld-switch' ); ?>>
	<form data-current_page_url="<?php echo esc_url( $current_page_url ); ?>" id="bb-courses-directory-form" class="bb-elementor-widget bb-courses-directory bb-courses-directory-form" method="get" action="">

		<?php $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1; ?>
		<input type="hidden" name="current_page" value="<?php echo esc_attr( $paged ); ?>">
		<input type="hidden" name="posts_per_page" value="<?php echo esc_attr( $posts_per_page ); ?>">

		<div class="flex align-items-center bb-courses-header">
			<?php if ( ! empty( $settings['switch_heading'] ) && 'yes' === $settings['switch_heading'] ) : ?>
				<h1 class="page-title bb-title"><?php _e( 'Courses', 'buddyboss-theme' ); ?></h1>
			<?php endif; ?>
			<?php if ( ! empty( $settings['switch_search'] ) && 'yes' === $settings['switch_search'] ) : ?>
				<div id="courses-dir-search" class="bs-dir-search" role="search">
					<div id="search-members-form" class="bs-search-form">
						<label for="bs_members_search" class="bp-screen-reader-text"><?php _e( 'Search', 'buddyboss-theme' ); ?></label>
						<input type="text" name="search" id="bs_members_search" value="<?php echo ! empty( $_GET['search'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) : ''; ?>" placeholder="<?php _e( 'Search Courses...', 'buddyboss-theme' ); ?>">
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $settings['switch_courses_nav'] ) && 'yes' === $settings['switch_courses_nav'] ) : ?>
			<nav class="courses-type-navs main-navs bp-navs dir-navs bp-subnavs">
				<ul class="component-navigation courses-nav">
					<?php
					$navs = array(
						'all' => '<div class="bb-component-nav-item-point">' . esc_html__( 'All Courses', 'buddyboss-theme' ) . '</div>' . ' ' . '<span class="count">' . $query->found_posts . '</span>',
					);

					if ( is_user_logged_in() ) {
						$navs['my-courses'] = '<div class="bb-component-nav-item-point">' . esc_html__( 'My Courses', 'buddyboss-theme' ) . '</div>' . '<span class="count">' . buddyboss_theme()->lifterlms_helper()->get_my_courses_count( null, $tax_query ) . '</span>';
					}

					$navs = apply_filters( 'BuddyBossTheme/lifterlms/Archive/Navs', $navs );

					if ( ! empty( $navs ) ) {
						$current_nav = isset( $_GET['type'] ) && isset( $navs[ $_GET['type'] ] ) ? $_GET['type'] : 'all';
						$base_url    = get_post_type_archive_link( 'course' );
						foreach ( $navs as $nav => $text ) {
							$selected_class = $nav == $current_nav ? 'selected' : '';
							$url            = 'all' != $nav ? add_query_arg(
								array( 'type' => $nav ),
								$base_url
							) : $base_url;
							printf(
								"<li id='courses-{$nav}' class='{$selected_class}'><a href='%s'>%s</a></li>",
								$url,
								$text
							);
						}
					} else {
						$current_nav = 'all';
					}
					?>

				</ul>
			</nav>
			<input type="hidden" name="type" value="<?php echo esc_attr( $current_nav ); ?>">
		<?php endif; ?>

		<div class="ld-secondary-header ld-secondary-header--llms">
			<div class="bb-secondary-list-tabs flex align-items-center" id="subnav" aria-label="Members directory secondary navigation" role="navigation">

				<input type="hidden" id="course-order" name="order" value="<?php echo ! empty( $_GET['order'] ) ? $_GET['order'] : 'desc'; ?>"/>

				<div class="sfwd-courses-filters flex push-right">
					<div class="select-wrap <?php echo ! empty( $settings['orderby_filter'] ) && 'on' === $settings['orderby_filter'] ? 'active' : 'hide'; ?>">
						<select id="sfwd_prs-order-by" name="orderby">
							<?php echo buddyboss_theme()->lifterlms_helper()->print_sorting_options(); ?>
						</select>
					</div>

					<?php
					$category_dropdown         = '';
					$archive_category_taxonomy = buddyboss_theme()->lifterlms_helper()->get_theme_category();

					$tags_array = ! empty( $tags ) ? $tags : array();
					if (
						'course_tag' !== $archive_category_taxonomy &&
						! empty( $tags_array )
					) {
						$tags_str = is_array( $tags_array ) ? implode( ',', $tags_array ) : $tags_array;
						?>
						<input type="hidden" name="filter-block-tags" value="<?php echo $tags_str; ?>"/>
						<?php
					} elseif ( 'course_tag' === $archive_category_taxonomy ) {
						if ( 1 === count( $tags_array ) ) {
							$tags_str = is_array( $tags_array ) ? implode( ',', $tags_array ) : $tags_array;
							?>
							<input type="hidden" name="filter-block-tags" value="<?php echo $tags_str; ?>"/>
							<?php
						} else {
							$category_dropdown = buddyboss_theme()->lifterlms_helper()->print_categories_options( array( 'include' => $tags_array ) );
						}
					}

					$category_array = ! empty( $category ) ? $category : array();
					if (
						'course_cat' !== $archive_category_taxonomy &&
						! empty( $category_array )
					) {
						$category_str = is_array( $category_array ) ? implode( ',', $category_array ) : $category_array;
						?>
						<input type="hidden" name="filter-block-categories" value="<?php echo $category_str; ?>"/>
						<?php
					} elseif ( 'course_cat' === $archive_category_taxonomy ) {
						if ( 1 === count( $category_array ) ) {
							$category_str = is_array( $category_array ) ? implode( ',', $category_array ) : $category_array;
							?>
							<input type="hidden" name="filter-block-categories" value="<?php echo $category_str; ?>"/>
							<?php
						} else {
							$category_dropdown = buddyboss_theme()->lifterlms_helper()->print_categories_options( array( 'include' => $category_array ) );
						}
					}

					if (
						'course_difficulty' === $archive_category_taxonomy ||
						'course_track' === $archive_category_taxonomy
					) {
						$category_dropdown = buddyboss_theme()->lifterlms_helper()->print_categories_options();
					}

					if ( ! empty( $category_dropdown ) ) {
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
					?>

					<?php if ( buddyboss_theme_get_option( 'lifterlms_course_index_show_instructors_filter' ) ) : ?>
						<div class="select-wrap <?php echo ! empty( $settings['instructors_filter'] ) && 'on' === $settings['instructors_filter'] ? 'active' : 'hide'; ?>">
							<select id="sfwd_instructors-order-by" name="filter-instructors">
								<?php echo buddyboss_theme()->lifterlms_helper()->print_instructors_options(); ?>
							</select>
						</div>
					<?php endif; ?>
				</div>

				<div class="grid-filters <?php echo ! empty( $settings['grid_filter'] ) && 'on' === $settings['grid_filter'] ? 'active' : 'hide'; ?>" data-view="llms-course">
					<a href="#" class="layout-view layout-view-course layout-grid-view bp-tooltip <?php echo ! empty( $class_grid_active ) ? esc_attr( $class_grid_active ) : ''; ?>" data-view="grid" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Grid View', 'buddyboss-theme' ); ?>">
						<i class="dashicons dashicons-screenoptions" aria-hidden="true"></i>
					</a>

					<a href="#" class="layout-view layout-view-course layout-list-view bp-tooltip <?php echo ! empty( $class_list_active ) ? esc_attr( $class_list_active ) : ''; ?>" data-view="list" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'List View', 'buddyboss-theme' ); ?>">
						<i class="dashicons dashicons-menu" aria-hidden="true"></i>
					</a>
				</div>

			</div>
		</div>

		<div class="grid-view bb-grid">

			<div id="course-dir-list" <?php echo $this->get_render_attribute_string( 'course-dir-list' ); ?>>
				<?php
				if ( $query->have_posts() ) {
					?>
					<ul class="bb-course-items <?php echo esc_attr( $class_grid_show . $class_list_show ); ?> <?php echo ( $settings_skin == 'cover' ) ? esc_attr( 'is-cover' ) : ''; ?>" aria-live="assertive" aria-relevant="all">
						<?php
						/* Start the Loop */
						while ( $query->have_posts() ) :
							$query->the_post();

							/*
							* Include the Post-Format-specific template for the content.
							* If you want to override this in a child theme, then include a file
							* called content-___.php (where ___ is the Post Format name) and that will be used instead.
							*/

							llms_get_template( 'course/course-index-loop.php' );
						endwhile;

						wp_reset_postdata();
						?>
					</ul>

					<?php if ( ! empty( $settings['switch_pagination'] ) && 'yes' === $settings['switch_pagination'] ) : ?>
						<div <?php echo $this->get_render_attribute_string( 'llms-pagination-switch' ); ?>>
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
