<?php

$view              = bb_theme_get_directory_layout_preference( 'llms-course' );
$class_grid_active = ( 'grid' === $view ) ? 'active' : '';
$class_list_active = ( 'list' === $view ) ? 'active' : '';
$class_grid_show   = ( 'grid' === $view ) ? 'grid-view bb-grid' : '';
$class_list_show   = ( 'list' === $view ) ? 'list-view bb-list' : '';
$search            = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

?>
<div id="lifterlms-content" class="lifterlms-course-list">
	<form id="bb-courses-directory-form" class="bb-courses-directory" method="get" action="">
		<?php $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1; ?>
		<input type="hidden" name="current_page" value="<?php echo esc_attr( $paged ); ?>">
		<div class="flex align-items-center bb-courses-header">
			<h1 class="page-title"><?php lifterlms_page_title(); ?></h1>
			<div id="courses-dir-search" class="bs-dir-search" role="search">
				<div id="search-members-form" class="bs-search-form">
					<label for="bs_members_search" class="bp-screen-reader-text">
						<?php _e( 'Search', 'buddyboss-theme' ); ?>
					</label>
					<input type="text" name="search" id="bs_members_search" value="<?php echo ! empty( $_GET['search'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) : ''; ?>" placeholder="<?php _e( 'Search Courses...', 'buddyboss-theme' ); ?>">
				</div>
			</div>
		</div>

		<nav class="courses-type-navs main-navs bp-navs dir-navs bp-subnavs">
		<ul class="component-navigation courses-nav">
				<?php
				$navs = array(
					'all' => '<div class="bb-component-nav-item-point">' . esc_html__( 'All Courses', 'buddyboss-theme' ) . '</div>' . ' ' . '<span class="count">' . buddyboss_theme()->lifterlms_helper()->get_all_courses_count() . '</span>',
				);

				if ( is_user_logged_in() ) {
					$user_id = get_current_user_id();

					if ( ! $course_ids = wp_cache_get( $user_id, 'llms_mycourse_ids' ) ) {
						$student_data    = new LLMS_Student();
						$student_courses = $student_data->get_courses();
						$course_ids      = ( ! empty( $student_courses['results'] ) ) ? $student_courses['results'] : array( - 1 );
						wp_cache_set( $user_id, $course_ids, 'llms_mycourse_ids' );
					}

					$terms = wp_list_pluck(
						get_terms(
							array(
								'taxonomy'   => 'llms_product_visibility',
								'hide_empty' => false,
							)
						),
						'term_taxonomy_id',
						'name'
					);

					foreach ( $course_ids as $course ) {
						$cats       = wp_get_object_terms( $course, 'llms_product_visibility' );
						$term_slugs = wp_list_pluck( $cats, 'slug' );
						if ( ! empty( $_GET['search'] ) && ( ! in_array( $terms['hidden'], $term_slugs, true ) || ! in_array( $terms['search'], $term_slugs, true ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
							$in[] = $course;
						} else {
							if ( in_array( 'catalog_search', $term_slugs, true ) || in_array( 'catalog', $term_slugs, true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
								$in[] = $course;
							}
						}
					}

					$category = get_queried_object();
					if ( $category && isset( $category->term_id ) && $category->term_id > 0 ) {
						$in = array();
						foreach ( $course_ids as $course ) {
							$cats     = wp_get_object_terms( $course, $category->taxonomy );
							$term_ids = wp_list_pluck( $cats, 'term_id' );
							if ( in_array( (int) $category->term_id, $term_ids, true ) ) {
								$in[] = $course;
							}
						}
						$all_query = new WP_Query(
							array(
								'post_type'      => 'course',
								'post_status'    => 'publish',
								'posts_per_page' => - 1,
								'post__in'       => $in,
								'tax_query'      => array(
									array(
										'taxonomy' => "$category->taxonomy",
										'field'    => 'id',
										'terms'    => array( $category->term_id ),
									),
								),
							)
						);
					} else {
						$all_query = new WP_Query(
							array(
								'post_type'      => 'course',
								'post_status'    => 'publish',
								'post__in'       => $course_ids,
								'posts_per_page' => - 1,
							)
						);
					}

					$count = (int) $all_query->found_posts;

					$navs['my-courses'] = '<div class="bb-component-nav-item-point">' . esc_html__( 'My Courses', 'buddyboss-theme' ) . '</div>' . ' ' . '<span class="count">' . $count . '</span>';
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

		<div class="ld-secondary-header ld-secondary-header--llms">
			<div class="bb-secondary-list-tabs flex align-items-center" id="subnav" aria-label="Members directory secondary navigation" role="navigation">

				<input type="hidden" id="course-order" name="order" value="<?php echo ! empty( $_GET['order'] ) ? $_GET['order'] : 'desc'; ?>"/>

				<div class="sfwd-courses-filters flex push-right">
					<div class="select-wrap">
						<select id="sfwd_prs-order-by" name="orderby">
							<?php echo buddyboss_theme()->lifterlms_helper()->print_sorting_options(); ?>
						</select>
					</div>

					<?php if ( buddyboss_theme_get_option( 'lifterlms_course_index_show_categories_filter' ) ) : ?>
						<div class="select-wrap">
							<?php
							$category = get_queried_object();
							if ( '' !== trim( buddyboss_theme()->lifterlms_helper()->print_categories_options() ) && is_post_type_archive( 'course' ) ) { ?>
								<select id="sfwd_cats-order-by" name="filter-categories">
									<?php echo buddyboss_theme()->lifterlms_helper()->print_categories_options(); ?>
								</select>
								<?php
							} ?>
						</div>
					<?php endif; ?>

					<?php if ( buddyboss_theme_get_option( 'lifterlms_course_index_show_instructors_filter' ) ) : ?>
						<div class="select-wrap">
							<select id="sfwd_instructors-order-by" name="filter-instructors">
								<?php echo buddyboss_theme()->lifterlms_helper()->print_instructors_options(); ?>
							</select>
						</div>
					<?php endif; ?>
				</div>

				<div class="grid-filters" data-view="llms-course">
					<a href="#" class="layout-view layout-view-course layout-grid-view bp-tooltip <?php echo esc_attr( $class_grid_active ); ?>" data-view="grid" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Grid View', 'buddyboss-theme' ); ?>"><i class="dashicons dashicons-screenoptions" aria-hidden="true"></i>
					</a>

					<a href="#" class="layout-view layout-view-course layout-list-view bp-tooltip <?php echo esc_attr( $class_list_active ); ?>" data-view="list" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'List View', 'buddyboss-theme' ); ?>"><i class="dashicons dashicons-menu" aria-hidden="true"></i>
					</a>
				</div>

			</div>
		</div>

		<div class="grid-view bb-grid">
			<div id="course-dir-list" class="course-dir-list bs-dir-list">
				<?php
				if ( isset( $_GET['type'] ) && 'my-courses' === $_GET['type'] ) {

					$user_id  = get_current_user_id();
					$paged    = isset( $_GET['current_page'] ) ? (int) $_GET['current_page'] : 1;
					$per_page = (int) apply_filters( 'llms_dashboard_courses_per_page', get_option( 'lifterlms_shop_courses_per_page', 12 ) );

					if ( ! $course_ids = wp_cache_get( $user_id, 'llms_mycourse_ids' ) ) {
						$student_data    = new LLMS_Student();
						$student_courses = $student_data->get_courses();
						$course_ids      = ( ! empty( $student_courses['results'] ) ) ? $student_courses['results'] : array( - 1 );
						wp_cache_set( $user_id, $course_ids, 'llms_mycourse_ids' );
					}

					$terms = wp_list_pluck(
						get_terms(
							array(
								'taxonomy'   => 'llms_product_visibility',
								'hide_empty' => false,
							)
						),
						'term_taxonomy_id',
						'name'
					);

					foreach ( $course_ids as $course ) {
						$cats       = wp_get_object_terms( $course, 'llms_product_visibility' );
						$term_slugs = wp_list_pluck( $cats, 'slug' );
						if ( ! empty( $_GET['search'] ) && ( ! in_array( $terms['hidden'], $term_slugs, true ) || ! in_array( $terms['search'], $term_slugs, true ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
							$in[] = $course;
						} else {
							if ( in_array( 'catalog_search', $term_slugs, true ) || in_array( 'catalog', $term_slugs, true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
								$in[] = $course;
							}
						}
					}

					$category = get_queried_object();
					if ( $category && isset( $category->term_id ) && $category->term_id > 0 ) {
						$in = array();
						foreach ( $course_ids as $course ) {
							$cats     = wp_get_object_terms( $course, $category->taxonomy );
							$term_ids = wp_list_pluck( $cats, 'term_id' );
							if ( in_array( (int) $category->term_id, $term_ids, true ) ) {
								$in[] = $course;
							}
						}

						$all_query = new WP_Query(
							array(
								's'              => $search,
								'post_type'      => 'course',
								'post_status'    => 'publish',
								'posts_per_page' => $per_page,
								'paged'          => $paged,
								'post__in'       => $in,
								'tax_query'      => array(
									array(
										'taxonomy' => "$category->taxonomy",
										'field'    => 'id',
										'terms'    => array( $category->term_id ),
									),
								),
							)
						);
					} else {
						$all_query = new WP_Query(
							array(
								's'              => $search,
								'post_type'      => 'course',
								'post_status'    => 'publish',
								'paged'          => $paged,
								'post__in'       => $course_ids,
								'posts_per_page' => $per_page,
							)
						);
					}

					if ( $all_query->have_posts() ) {

						?>
						<ul class="bb-course-items <?php echo esc_attr( $class_grid_show . $class_list_show ); ?>" aria-live="assertive" aria-relevant="all">
							<?php
							/* Start the Loop */
							while ( $all_query->have_posts() ) : $all_query->the_post();

								/*
								 * Include the Post-Format-specific template for the content.
								 * If you want to override this in a child theme, then include a file
								 * called content-___.php (where ___ is the Post Format name) and that will be used instead.
								 */

								llms_get_template( 'course/course-index-loop.php' );
							endwhile;
							?>
						</ul>

						<div class="bb-lms-pagination mine">
							<?php
							global $wp_query;
							$big        = 999999999; // need an unlikely integer
							$translated = __( 'Page', 'buddyboss-theme' ); // Supply translatable string

							echo paginate_links(
								array(
									'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
									'format'             => '?paged=%#%',
									'current'            => max( 1, get_query_var( 'paged' ) ),
									'total'              => $all_query->max_num_pages,
									'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
								)
							);
							?>
						</div>
						<?php

					} else {
						?>
						<aside class="bp-feedback bp-template-notice ld-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php _e( 'Sorry, no courses were found.', 'buddyboss-theme' ); ?></p>
						</aside>
						<?php
					}

				} else {
					if ( have_posts() ) {
						?>
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

								llms_get_template( 'course/course-index-loop.php' );
							endwhile;
							?>
						</ul>

						<div class="bb-lms-pagination all">
							<?php
							global $wp_query;
							$big        = 999999999; // need an unlikely integer
							$translated = __( 'Page', 'buddyboss-theme' ); // Supply translatable string

							echo paginate_links(
								array(
									'base'               => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
									'format'             => '?paged=%#%',
									'current'            => max( 1, get_query_var( 'paged' ) ),
									'total'              => $wp_query->max_num_pages,
									'before_page_number' => '<span class="screen-reader-text">' . $translated . ' </span>',
								)
							);
							?>
						</div>
						<?php
					} else {
						?>
						<aside class="bp-feedback bp-template-notice ld-feedback info">
							<span class="bp-icon" aria-hidden="true"></span>
							<p><?php _e( 'Sorry, no courses were found.', 'buddyboss-theme' ); ?></p>
						</aside>
						<?php
					}
				}
				?>
			</div>
		</div>
	</form>
</div>
