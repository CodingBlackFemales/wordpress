<?php
/**
 * Virtual instructor module Admin class file.
 *
 * TODO: change CSS/JS selectors in this file to use approved CSS classes.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Virtual_Instructor;
use LearnDash\Core\Modules\AI\Virtual_Instructor\AJAX\Process_Setup_Wizard;
use LearnDash\Core\Modules\AJAX\Search_Posts;
use Learndash_Admin_Menus_Tabs;
use LearnDash_Custom_Label;
use LearnDash_Settings_Section_AI_Integrations;
use WP_Post;
use WP_Role;

/**
 * Virtual instructor module Admin class.
 *
 * This class manages the admin side of the virtual instructor module.
 *
 * @since 4.13.0
 */
class Admin {
	/**
	 * Post type slug.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Constructor.
	 *
	 * @since 4.13.0
	 */
	public function __construct() {
		$this->post_type = learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR );
	}

	/**
	 * Registers post type for virtual instructor.
	 *
	 * @since 4.13.0
	 *
	 * @param array<string, array<string, mixed>> $post_args Existing LearnDash post types args to be registered.
	 *
	 * @return array<string, array<string, mixed>> Returned LearnDash custom post types args to be registered.
	 */
	public function register_post_type( array $post_args ): array {
		$label_singular = LearnDash_Custom_Label::get_label( 'virtual_instructor' );
		$label_plural   = LearnDash_Custom_Label::get_label( 'virtual_instructors' );

		$post_args[ $this->post_type ] = [
			'plugin_name'        => $label_singular,
			'slug_name'          => $this->post_type,
			'post_type'          => $this->post_type,
			'template_redirect'  => false,
			'taxonomies'         => [],
			'cpt_options'        => [
				'has_archive'         => false,
				'hierarchical'        => false,
				'supports'            => [
					'title',
				],
				'labels'              => [
					'name'                     => $label_plural,
					'singular_name'            => $label_singular,
					'add_new'                  => esc_html_x( 'Add New', 'Add New Virtual Instructor Label', 'learndash' ),
					// translators: placeholder: Virtual Instructor.
					'add_new_item'             => sprintf( esc_html_x( 'Add New %s', 'placeholder: Virtual Instructor', 'learndash' ), $label_singular ),
					// translators: placeholder: Virtual Instructor.
					'edit_item'                => sprintf( esc_html_x( 'Edit %s', 'placeholder: Virtual Instructor', 'learndash' ), $label_singular ),
					// translators: placeholder: Virtual Instructor.
					'new_item'                 => sprintf( esc_html_x( 'New %s', 'placeholder: Virtual Instructor', 'learndash' ), $label_singular ),
					'all_items'                => $label_plural,
					// translators: placeholder: Virtual Instructor.
					'view_item'                => sprintf( esc_html_x( 'View %s', 'placeholder: Virtual Instructor', 'learndash' ), $label_singular ),
					// translators: placeholder: Virtual Instructors.
					'view_items'               => sprintf( esc_html_x( 'View %s', 'placeholder: Virtual Instructors', 'learndash' ), $label_plural ),
					// translators: placeholder: Virtual Instructors.
					'search_items'             => sprintf( esc_html_x( 'Search %s', 'placeholder: Virtual Instructors', 'learndash' ), $label_plural ),
					// translators: placeholder: Virtual Instructors.
					'not_found'                => sprintf( esc_html_x( 'No %s found', 'placeholder: Virtual Instructors', 'learndash' ), $label_plural ),
					// translators: placeholder: Virtual Instructor.
					'not_found_in_trash'       => sprintf( esc_html_x( 'No %s found in Trash', 'placeholder: Virtual Instructor', 'learndash' ), $label_plural ),
					'parent_item_colon'        => '',
					'menu_name'                => $label_plural,
					// translators: placeholder: Virtual Instructor.
					'item_published'           => sprintf( esc_html_x( '%s Published', 'placeholder: Virtual Instructor', 'learndash' ), $label_singular ),
					// translators: placeholder: Virtual Instructor.
					'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'placeholder: Virtual Instructor', 'learndash' ), $label_singular ),
					// translators: placeholder: Virtual Instructor.
					'item_reverted_to_draft'   => sprintf( esc_html_x( '%s Reverted to Draft', 'placeholder: Virtual Instructor', 'learndash' ), $label_singular ),
					// translators: placeholder: Virtual Instructor.
					'item_scheduled'           => sprintf( esc_html_x( '%s Scheduled', 'placeholder: Virtual Instructor', 'learndash' ), $label_singular ),
					// translators: placeholder: Virtual Instructor.
					'item_updated'             => sprintf( esc_html_x( '%s Updated', 'placeholder: Virtual Instructor', 'learndash' ), $label_singular ),
					// translators: placeholder: Virtual Instructor.
					'item_trashed'             => sprintf( esc_html_x( '%s Trashed', 'placeholder: Virtual Instructor', 'learndash' ), $label_singular ),
				],
				'capability_type'     => 'virtual_instructor',
				'capabilities'        => $this->get_user_capabilities_map(),
				'public'              => false,
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
				'show_in_rest'        => false,
			],
			'options_page_title' => sprintf(
				// translators: placeholder: Virtual Instructor.
				esc_html_x( '%s Settings', 'placeholder: virtual instructor', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'virtual_instructor' )
			),
			'fields'             => [],
			'default_options'    => [],
		];

		return $post_args;
	}

	/**
	 * Filters bulk post updated messages for virtual instructor.
	 *
	 * @since 4.13.0
	 *
	 * @param array<string, array<string, string>> $bulk_messages Existing messages.
	 * @param array<string, int>                   $bulk_counts Post counts for different update statuses.
	 *
	 * @return array<string, array<string, string>> Returned messages.
	 */
	public function filter_bulk_post_updated_messages( $bulk_messages, array $bulk_counts ): array {
		$screen = get_current_screen();

		if (
			! $screen
			|| $screen->post_type !== $this->post_type
		) {
			return $bulk_messages;
		}

		$singular_label = LearnDash_Custom_Label::get_label( 'virtual_instructor' );
		$plural_label   = LearnDash_Custom_Label::get_label( 'virtual_instructors' );

		$bulk_messages['post'] = [
			'updated' => sprintf(
				// translators: placeholders: %1$s: Number of virtual instructors, %2$s: Custom label for virtual instructor(s).
				_n(
					'%1$s %2$s updated.',
					'%1$s %2$s updated.',
					$bulk_counts['updated'],
					'learndash'
				),
				$bulk_counts['updated'],
				_n(
					$singular_label, // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle -- Translation is handled by LearnDash_Custom_Label::get_label().
					$plural_label, // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural -- Translation is handled by LearnDash_Custom_Label::get_label().
					$bulk_counts['updated'],
					'learndash'
				)
			),
			'locked' => ( 1 === $bulk_counts['locked'] )
				? sprintf(
					// translators: placeholder: %s: Custom label for virtual instructor.
					__( '1 %s not updated, somebody is editing it.', 'learndash' ),
					$singular_label
				)
				: sprintf(
					// translators: placeholders: %1$s: Number of virtual instructors, %2$s: Custom label for virtual instructor(s).
					_n(
						'%1$s %2$s not updated, somebody is editing it.',
						'%1$s %2$s not updated, somebody is editing them.',
						$bulk_counts['locked'],
						'learndash'
					),
					$bulk_counts['locked'],
					_n(
						$singular_label, // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle -- Translation is handled by LearnDash_Custom_Label::get_label().
						$plural_label, // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural -- Translation is handled by LearnDash_Custom_Label::get_label().
						$bulk_counts['locked'],
						'learndash'
					)
				),
			'deleted' => sprintf(
				// translators: placeholders: %1$s: Number of virtual instructors, %2$s: Custom label for virtual instructor(s).
				_n(
					'%1$s %2$s permanently deleted.',
					'%1$s %2$s permanently deleted.',
					$bulk_counts['deleted'],
					'learndash'
				),
				$bulk_counts['deleted'],
				_n(
					$singular_label, // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle -- Translation is handled by LearnDash_Custom_Label::get_label().
					$plural_label, // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural -- Translation is handled by LearnDash_Custom_Label::get_label().
					$bulk_counts['deleted'],
					'learndash'
				)
			),
			'trashed' => sprintf(
				// translators: placeholders: %1$s: Number of virtual instructors, %2$s: Custom label for virtual instructor(s).
				_n(
					'%1$s %2$s moved to the Trash.',
					'%1$s %2$s moved to the Trash.',
					$bulk_counts['trashed'],
					'learndash'
				),
				$bulk_counts['trashed'],
				_n(
					$singular_label, // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle -- Translation is handled by LearnDash_Custom_Label::get_label().
					$plural_label, // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural -- Translation is handled by LearnDash_Custom_Label::get_label().
					$bulk_counts['trashed'],
					'learndash'
				)
			),
			'untrashed' => sprintf(
				// translators: placeholders: %1$s: Number of virtual instructors, %2$s: Custom label for virtual instructor(s).
				_n(
					'%1$s %2$s restored from the Trash.',
					'%1$s %2$s restored from the Trash.',
					$bulk_counts['untrashed'],
					'learndash'
				),
				$bulk_counts['untrashed'],
				_n(
					$singular_label, // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle -- Translation is handled by LearnDash_Custom_Label::get_label().
					$plural_label, // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural -- Translation is handled by LearnDash_Custom_Label::get_label().
					$bulk_counts['untrashed'],
					'learndash'
				)
			),
		];

		return $bulk_messages;
	}

	/**
	 * Registers submenu;
	 *
	 * @since 4.13.0
	 *
	 * @param array<string, array<string, string>> $ld_submenu Existing LearnDash submenu.
	 *
	 * @return array<string, array<string, string>> Returned LearnDash submenu.
	 */
	public function register_submenu( $ld_submenu ): array {
		global $submenu;

		if (
			! current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK )
			|| ! isset( $submenu[ 'edit.php?post_type=' . $this->post_type ] )
		) {
			return $ld_submenu;
		}

		$new_submenu = [
			$this->post_type => [
				'name'  => LearnDash_Custom_Label::get_label( 'virtual_instructors' ),
				'cap'   => LEARNDASH_ADMIN_CAPABILITY_CHECK,
				'link'  => 'edit.php?post_type=' . $this->post_type,
				'class' => 'submenu-virtual-instructor',
			],
		];

		// Adds submenu after 'ld-exam' submenu.

		$exam_post_slug = learndash_get_post_type_slug( LDLMS_Post_Types::EXAM );
		$index          = array_search( $exam_post_slug, array_keys( $ld_submenu ), true );

		array_splice( $ld_submenu, $index + 1, 0, $new_submenu );

		return $ld_submenu;
	}

	/**
	 * Registers submenu items.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function register_submenu_items(): void {
		$ld_admin_tabs = Learndash_Admin_Menus_Tabs::get_instance();

		if ( ! $ld_admin_tabs instanceof Learndash_Admin_Menus_Tabs ) {
			return;
		}

		$ld_admin_tabs->add_admin_tab_item(
			'edit.php?post_type=' . $this->post_type,
			[
				'link' => 'edit.php?post_type=' . $this->post_type,
				'name' => LearnDash_Custom_Label::get_label( 'virtual_instructors' ),
				'id'   => 'edit-' . $this->post_type,
				'cap'  => LEARNDASH_ADMIN_CAPABILITY_CHECK,
			],
			5
		);
	}

	/**
	 * Manages meta boxes.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function manage_meta_boxes(): void {
		remove_meta_box( 'slugdiv', $this->post_type, 'normal' );
	}

	/**
	 * Sets virtual instructor list columns.
	 *
	 * @since 4.13.0
	 *
	 * @param array<string, string> $columns Existing columns.
	 *
	 * @return array<string, string> Returned columns.
	 */
	public function manage_posts_columns( $columns ): array {
		unset( $columns['date'] );

		$columns['title']  = _x( 'Name', 'Virtual Instructor Name', 'learndash' );
		$columns['course'] = learndash_get_custom_label( 'courses' );
		$columns['group']  = learndash_get_custom_label( 'groups' );

		return $columns;
	}

	/**
	 * Outputs custom columns values.
	 *
	 * @since 4.13.0
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 *
	 * @return void
	 */
	public function manage_posts_custom_column( string $column_name, int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$virtual_instructor = Virtual_Instructor::create_from_post( $post );

		switch ( $column_name ) {
			case 'course':
			case 'group':
				$this->output_associated_fields_column( $virtual_instructor, $column_name );
				break;
		}
	}

	/**
	 * Changes default title placeholder on post edit screen.
	 *
	 * @since 4.13.0
	 *
	 * @param string  $title Original title text.
	 * @param WP_Post $post  WP post object.
	 *
	 * @return string Returned title placeholder.
	 */
	public function change_title_placeholder( $title, $post ): string {
		if ( $post->post_type !== $this->post_type ) {
			return $title;
		}

		return sprintf(
			// translators: placeholder: virtual instructor.
			esc_html_x( 'Add %s name', 'placeholder: virtual instructor', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'virtual_instructor' )
		);
	}

	/**
	 * Filters sample permalink HTML.
	 *
	 * @since 4.13.0
	 *
	 * @param string  $html      Existing HTML.
	 * @param int     $post_id   Post ID.
	 * @param string  $new_title New title.
	 * @param string  $slug      Post slug.
	 * @param WP_Post $post      WP post object.
	 *
	 * @return string Returned sample permalink HTML.
	 */
	public function filter_get_sample_permalink_html( $html, $post_id, $new_title, $slug, $post ) {
		if ( $post->post_type !== $this->post_type ) {
			return $html;
		}

		// Returns empty string because virtual instructor post type doesn't have permalink.
		return '';
	}

	/**
	 * Gets user capabilities map for virtual instructor post type.
	 *
	 * @since 4.13.0
	 *
	 * @return array<string, string> User capabilities map.
	 */
	private function get_user_capabilities_map(): array {
		return [
			'read_post'              => 'read_virtual_instructor',
			'publish_posts'          => 'publish_virtual_instructors',
			'edit_posts'             => 'edit_virtual_instructors',
			'edit_post'              => 'edit_virtual_instructor',
			'edit_others_posts'      => 'edit_others_virtual_instructors',
			'delete_posts'           => 'delete_virtual_instructors',
			'delete_others_posts'    => 'delete_others_virtual_instructors',
			'read_private_posts'     => 'read_private_virtual_instructors',
			'delete_post'            => 'delete_virtual_instructor',
			'edit_published_posts'   => 'edit_published_virtual_instructors',
			'delete_published_posts' => 'delete_published_virtual_instructors',
		];
	}

	/**
	 * Registers user capabilities.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function register_user_capabilities(): void {
		$admin_role = get_role( 'administrator' );

		if ( ! $admin_role instanceof WP_Role ) {
			return;
		}

		foreach ( $this->get_user_capabilities_map() as $capability ) {
			if ( ! $admin_role->has_cap( $capability ) ) {
				$admin_role->add_cap( $capability );
			}
		}
	}

	/**
	 * Outputs associated fields column value.
	 *
	 * @since 4.13.0
	 *
	 * @param Virtual_Instructor $virtual_instructor Virtual instructor object.
	 * @param string             $column              Column name.
	 *
	 * @return void
	 */
	public function output_associated_fields_column( Virtual_Instructor $virtual_instructor, string $column = 'course' ): void {
		if ( ! in_array( $column, [ 'course', 'group' ], true ) ) {
			return;
		}

		if ( $column === 'course' ) {
			$object_ids       = $virtual_instructor->get_course_ids();
			$applied_callable = 'is_applied_to_all_courses';
			$type_label       = learndash_get_custom_label( 'courses' );
		} else {
			$object_ids       = $virtual_instructor->get_group_ids();
			$applied_callable = 'is_applied_to_all_groups';
			$type_label       = learndash_get_custom_label( 'groups' );
		}

		if (
			empty( $object_ids )
			&& method_exists( $virtual_instructor, $applied_callable )
			&& ! call_user_func( [ $virtual_instructor, $applied_callable ] )
		) {
			echo '&mdash;';
			return;
		}

		$displayed_object_ids = array_slice( $object_ids, 0, 3 );

		echo '<ul class="object-list">';

		if (
			method_exists( $virtual_instructor, $applied_callable )
			&& call_user_func( [ $virtual_instructor, $applied_callable ] )
		) {
			printf(
				'<li class="object-list__item">%s</li>',
				sprintf(
					// translators: %s: Object type label.
					esc_html__( 'All %s', 'learndash' ),
					esc_html( $type_label )
				)
			);
		} else {
			foreach ( $displayed_object_ids as $object_id ) {
				printf(
					'<li class="object-list__item">%s</li>',
					esc_html( get_the_title( $object_id ) )
				);
			}

			if ( count( $object_ids ) > count( $displayed_object_ids ) ) {
				printf(
					'<li class="object-list__item object-list__others">%s</li>',
					sprintf(
						// translators: %d: Number of objects.
						esc_html__( 'and %d more', 'learndash' ),
						count( $object_ids ) - count( $displayed_object_ids )
					)
				);
			}
		}

		echo '</ul>';
	}

	/**
	 * Enqueues scripts.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$screen = get_current_screen();

		if (
			! $screen
			|| $screen->post_type !== $this->post_type
		) {
			return;
		}

		wp_enqueue_script(
			'learndash-virtual-instructor-setup-wizard',
			LEARNDASH_LMS_PLUGIN_URL . 'src/assets/dist/js/admin/modules/ai/virtual-instructor/setup-wizard.js',
			[ 'react', 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'wp-url' ],
			LEARNDASH_VERSION,
			true
		);

		wp_localize_script(
			'learndash-virtual-instructor-setup-wizard',
			'learndashVirtualInstructorSetupWizard',
			[
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => [
					'search_posts' => wp_create_nonce( Search_Posts::$action ),
					'setup'        => wp_create_nonce( Process_Setup_Wizard::$action ),
				],
				'actions'      => [
					'search_posts' => Search_Posts::$action,
					'setup'        => Process_Setup_Wizard::$action,
				],
				'post_types'   => [
					LDLMS_Post_Types::COURSE => learndash_get_post_type_slug( LDLMS_Post_Types::COURSE ),
					LDLMS_Post_Types::GROUP  => learndash_get_post_type_slug( LDLMS_Post_Types::GROUP ),
				],
				'field_values' => [
					'openai_api_key' => LearnDash_Settings_Section_AI_Integrations::get_setting( 'openai_api_key' ),
					'banned_words'   => Settings\Page_Section::get_setting( 'banned_words' ),
					'error_message'  => Settings\Page_Section::get_setting( 'error_message' ),
				],
			]
		);

		wp_enqueue_style( 'wp-components' );
	}

	/**
	 * Updates setting virtual instructor settings in its own metadata as well so that they will be available in virtual instructor model.
	 *
	 * @since 4.13.0
	 *
	 * @param WP_Post $post    WP post object.
	 * @param string  $setting Setting name.
	 * @param mixed   $value   Setting value.
	 *
	 * @return void
	 */
	public function update_setting( $post, $setting, $value ): void {
		if ( $post->post_type !== $this->post_type ) {
			return;
		}

		update_post_meta( $post->ID, $setting, $value );
	}
}
