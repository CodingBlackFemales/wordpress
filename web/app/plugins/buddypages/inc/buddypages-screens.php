<?php
/**
 * BuddyPages Screens
 *
 * @package BuddyPagesScreens
 * @subpackage BuddyPages
 * @author WebDevStudios
 * @since 1.0.0
 */

/**
 * Screen callback for BuddyPages user.
 *
 * @since 1.0.0
 */
function buddypages_user_nav_item_screen() {
	add_action( 'bp_template_title', 'buddypages_screen_title' );
	add_action( 'bp_template_content', 'buddypages_screen_content' );
	bp_core_load_template( array( 'members/single/plugins' ) );
}

/**
 * Screen callback for BuddyPages group.
 *
 * @since 1.0.0
 */
function buddypages_group_nav_item_screen() {
	add_action( 'bp_template_content', 'buddypages_group_screen_content' );
	bp_core_load_template( array( 'groups/single/plugins' ) );
}

/**
 * BuddyPage title.
 *
 * @since 1.0.0
 */
function buddypages_screen_title() {

	if ( $buddypage = get_page_by_path( bp_current_component(), OBJECT, 'buddypages' ) ) {

		$draft_title = esc_attr__( 'post status is draft', 'buddypages' );
		$draft       = ( 'draft' === $buddypage->post_status ) ? '<div class="dashicons dashicons-edit" title="' . $draft_title . '"></div>' : '';

		if ( 'edit' !== bp_current_action() ) {
			/**
			 * Filters BuddyPage screen title.
			 *
			 * @since 1.0.0
			 *
			 * @param string $post->post_title
			 */
			echo esc_attr( apply_filters( 'buddypages_screen_title', $buddypage->post_title ) ) . $draft;
		}
	}
}

/**
 * BuddyPage content.
 *
 * @since 1.0.0
 */
function buddypages_screen_content() {

	if ( $buddypage = get_page_by_path( bp_current_component(), OBJECT, 'buddypages' ) ) {

		$meta = get_post_meta( $buddypage->ID );

		if ( 'edit' === bp_current_action() ) {
		?>
			<div id="buddypages">
			<form name="edit-buddypage" class="standard-form base" method="post">

				<label id="post-title" for="post_title"><?php esc_html_e( 'Title', 'buddypages' ); ?></label>
				<div class="editfield field_type_textbox">
					<label><input type="text" name="post_title" value="<?php echo esc_textarea( $buddypage->post_title ); ?>"></label>
				</div>

				<label id="post-content" for="post_content"><?php esc_html_e( 'Content', 'buddypages' ); ?></label>
				<div class="editfield field_type_textbox">
					<?php buddypages_text_editor( $buddypage->post_content ); ?>

				</div>

				<label id="post-in" for="post_in"><?php esc_html_e( 'Post In', 'buddypages' ); ?></label>
	            <div class="editfield field_type_select">
					<label><select name="post_in">
						<?php buddypages_post_in_options( $buddypage, $meta ); ?>
			        </select></label>
	            </div>

				<label id="post-status" for="post_status"><?php esc_html_e( 'Post Status', 'buddypages' ); ?></label>
				<div class="editfield field_type_select">
					<?php buddypages_post_status_options( $buddypage->post_status ); ?>
				</div>

				<?php

				/**
				 * Fires after the display of the BuddPages fields on screen content.
				 *
				 * @since 1.0.0
				 *
				 * @param object $buddypage BuddyPage object.
				 * @param mixed  $meta      Screen meta data.
				 */
				do_action( 'buddypages_after_fields', $buddypage, $meta ); ?>

				<div class="submit">
	                <button name="buddypage-edit-submit" type="submit" value="edit_page"><?php esc_html_e( 'Save Page', 'buddypages' ); ?></button>
					<input type="hidden" name="ID" value="<?php echo esc_attr( $buddypage->ID ); ?>">
				</div>
				<div class="delete-page">
					<a href="<?php echo bp_displayed_user_domain() . buddypages_post_slug( $buddypage ) . '/' . bp_current_action(); ?>?delete=<?php echo esc_attr( $buddypage->ID ); ?>" style="float:right;"><?php esc_html_e( 'delete', 'buddypages' ); ?></a>
				</div>
	            <?php wp_nonce_field( 'edit_buddypage', 'buddypage-edit-security' ); ?>
			</form>
			</div>
		<?php
		} else {

			$query = new WP_Query( array( 'p' => $buddypage->ID, 'post_type' => 'buddypages' ) );

			if ( $query->have_posts() ) :
				echo '<div id="buddypages-page" class="buddypages buddypages-myprofile buddypages-' . $buddypage->ID . '">';
				while ( $query->have_posts() ) : $query->the_post();
					the_content();
				endwhile;
				echo '</div>';
			endif;
		}
	}

	if ( 'settings' === bp_current_component() && 'pages' === bp_current_action() ) {
		buddypages_list_pages_content();
	}
}

/**
 * List of BuddyPages in user settings screen.
 *
 * @since 1.0.0
 */
function buddypages_list_pages_content() {

	$variables = bp_action_variables();

	if ( 'pages' === bp_current_action() && is_array( $variables ) && 'new' === $variables[0] ) {
		?>
		<div id="buddypages">
		<form name="edit-buddypage" class="standard-form base" method="post">

			<label id="post-title" for="post_title"><?php esc_html_e( 'Title', 'buddypages' ); ?></label>
			<div class="editfield field_type_textbox">
				<label><input type="text" name="post_title" value=""></label>
			</div>

			<label id="post-content" for="post_content"><?php esc_html_e( 'Content', 'buddypages' ); ?></label>
			<div class="editfield field_type_textbox">
				<?php buddypages_text_editor( '' ); ?>
			</div>

			<label id="post-in" for="post_in"><?php esc_html_e( 'Post In', 'buddypages' ); ?></label>
			<div class="editfield field_type_select">
				<label><select name="post_in">
					<?php buddypages_post_in_options( '', '' ); ?>
				</select></label>
			</div>

			<label id="post-status" for="post_status"><?php esc_html_e( 'Post Status', 'buddypages' ); ?></label>
			<div class="editfield field_type_select">
				<?php buddypages_post_status_options( '' ); ?>
			</div>

			<div class="submit">
				<button name="buddypage-create-submit" type="submit" value="create_page"><?php esc_html_e( 'Add Page', 'buddypages' ); ?></button>
			</div>
			<?php wp_nonce_field( 'edit_buddypage', 'buddypage-edit-security' ); ?>
		</form>
		</div>
	<?php
	} else {

		echo '<div class="pages-sub-nav"><a class="button" href="' . esc_url( bp_displayed_user_domain() . bp_current_component() . '/' . bp_current_action() ) . '/new/">' . esc_html__( 'Add New', 'buddypages' ) . '</a></div>';
		echo '<ul id="activity-stream" class="activity-list item-list">';

		$is_profile = bp_displayed_user_id() === bp_loggedin_user_id() ? true : false;
		$query      = buddypages()->user_pages->get_user_pages( bp_displayed_user_id(), false, $is_profile );
		?>

		<?php if ( ( $query instanceof WP_Query ) && $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post();

				global $post;

				$draft_title = esc_attr__( 'post status is draft', 'buddypages' );
				$draft       = ( 'draft' === $post->post_status ) ? '   <div class="dashicons dashicons-edit" title="' . $draft_title . '"></div>' : '';
				$post_name   = ( $post->post_name ) ? $post->post_name : sanitize_title( $post->post_title );
		 	?>
			<li>
				<div class="activity-content">
					<div class="pages-title"><?php the_title(); ?><?php echo $draft; ?></div>
					<div class="pages-posted-in"><?php esc_html_e( 'Posted in: ', 'buddypages' ); ?><?php buddypages_permalink( $post_name, get_the_ID() ); ?></div>
					<a class="pages-edit" href="<?php echo esc_url( bp_displayed_user_domain() .  $post_name  . '/edit/' ); ?>" rel="bookmark" title="<?php printf( esc_attr__( 'Permanent Link to edit %s', 'buddypages' ), get_the_title() ); ?>"><?php esc_html_e( 'Edit', 'buddypages' ); ?></a>
				 </div>
			 </li>

		<?php endwhile;
	 		else : ?>
			<div id="message" class="info">
			    <p><?php esc_html_e( 'Sorry, no pages created.', 'buddypages' ); ?></p>
			</div>
	 	<?php endif;
			echo '<ul>';
	}
}

/**
 * BuddyPage group content.
 *
 * @since 1.0.0
 */
function buddypages_group_screen_content() {

	if ( $buddypage = get_page_by_path( bp_current_action(), OBJECT, 'buddypages' ) ) {

		$user_link = bp_core_get_user_domain( $buddypage->post_author );
		$edit_link = trailingslashit( $user_link . buddypages_post_slug( $buddypage ) . '/edit' );

		if ( bp_core_can_edit_settings() ) {
		?>

		<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
			<ul>
				<li>
					<a href="<?php echo esc_url( $edit_link ); ?>"><?php esc_html_e( 'Edit', 'buddypages' ); ?></a>
				</li>
			</ul>
		</div><!-- .item-list-tabs -->

		<?php
		}

		$query = new WP_Query( array( 'p' => $buddypage->ID, 'post_type' => 'buddypages' ) );

		if ( $query->have_posts() ) :
			echo '<div id="buddypages-page" class="buddypages buddypages-group buddypages-' . $buddypage->ID . '">';
			while ( $query->have_posts() ) : $query->the_post();
				echo '<h3>' . get_the_title() . '</h3>';
				the_content();
			endwhile;
			echo '</div>';
		endif;
	}

}
