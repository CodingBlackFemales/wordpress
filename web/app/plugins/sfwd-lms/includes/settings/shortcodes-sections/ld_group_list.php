<?php
/**
 * LearnDash Shortcode Section for Group List [ld_group_list].
 *
 * @since 3.2.0
 * @package LearnDash\Settings\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_group_list' ) ) ) {
	/**
	 * Class LearnDash Shortcode Section for Group List [ld_group_list].
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Shortcodes_Section_ld_group_list extends LearnDash_Shortcodes_Section /* phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid */ {

		/**
		 * Public constructor for class.
		 *
		 * @since 3.2.0
		 *
		 * @param array $fields_args Field Args.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;
			$groups_public     = ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'public' ) === '' ) ? learndash_groups_get_not_public_message() : '';

			$this->shortcodes_section_key = 'ld_group_list';
			// translators: placeholder: Group.
			$this->shortcodes_section_title = sprintf( esc_html_x( '%s List', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) );
			$this->shortcodes_section_type  = 1;
			// translators: placeholders: groups, groups (URL slug).
			$this->shortcodes_section_description = sprintf( wp_kses_post( _x( 'This shortcode shows list of %1$s. You can use this shortcode on any page if you do not want to use the default <code>/%2$s/</code> page. %3$s', 'placeholders: groups, groups (URL slug)', 'learndash' ) ), learndash_get_custom_label_lower( 'groups' ), LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'groups' ), $groups_public );

			parent::__construct();
		}

		/**
		 * Initialize the shortcode fields.
		 *
		 * @since 3.2.0
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'orderby'        => array(
					'id'        => $this->shortcodes_section_key . '_orderby',
					'name'      => 'orderby',
					'type'      => 'select',
					'label'     => esc_html__( 'Order by', 'learndash' ),
					'help_text' => wp_kses_post( __( 'See <a target="_blank" href="https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters">the full list of available orderby options here.</a>', 'learndash' ) ),
					'value'     => 'ID',
					'options'   => array(
						'ID'         => esc_html__( 'ID - Order by post id. (default)', 'learndash' ),
						'title'      => esc_html__( 'Title - Order by post title', 'learndash' ),
						'date'       => esc_html__( 'Date - Order by post date', 'learndash' ),
						'menu_order' => esc_html__( 'Menu - Order by Page Order Value', 'learndash' ),
					),
				),
				'order'          => array(
					'id'        => $this->shortcodes_section_key . '_order',
					'name'      => 'order',
					'type'      => 'select',
					'label'     => esc_html__( 'Order', 'learndash' ),
					'help_text' => esc_html__( 'Order', 'learndash' ),
					'value'     => 'ID',
					'options'   => array(
						''    => esc_html__( 'DESC - highest to lowest values (default)', 'learndash' ),
						'ASC' => esc_html__( 'ASC - lowest to highest values', 'learndash' ),
					),
				),
				'num'            => array(
					'id'        => $this->shortcodes_section_key . '_num',
					'name'      => 'num',
					'type'      => 'number',
					// translators: placeholder: Groups.
					'label'     => sprintf( esc_html_x( '%s Per Page', 'placeholder: Groups', 'learndash' ), LearnDash_Custom_Label::get_label( 'groups' ) ),
					// translators: placeholders: groups, default per page.
					'help_text' => sprintf( esc_html_x( '%1$s per page. Default is %2$d. Set to zero for all.', 'placeholders: groups, default per page', 'learndash' ), LearnDash_Custom_Label::get_label( 'groups' ), LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ) ),
					'value'     => '',
					'class'     => 'small-text',
					'attrs'     => array(
						'min'  => 0,
						'step' => 1,
					),
				),
				'price_type'     => array(
					'id'        => $this->shortcodes_section_key . '_price_type',
					'name'      => 'price_type',
					'type'      => 'multiselect',
					// translators: placeholder: Group Access Modes.
					'label'     => sprintf( esc_html_x( '%s Access Mode(s)', 'placeholder: Group Access Modes', 'learndash' ), learndash_get_custom_label( 'groups' ) ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'Filter %s by access mode(s), Ctrl+click to deselect selected items.', 'placeholder: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => '',
					'options'   => array(
						'free'      => esc_html__( 'Free', 'learndash' ),
						'paynow'    => esc_html__( 'Buy Now', 'learndash' ),
						'subscribe' => esc_html__( 'Recurring', 'learndash' ),
						'closed'    => esc_html__( 'Closed', 'learndash' ),
					),
				),
				'mygroups'       => array(
					'id'        => $this->shortcodes_section_key . '_mygroups',
					'name'      => 'mygroups',
					'type'      => 'select',
					// translators: placeholder: Groups.
					'label'     => sprintf( esc_html_x( 'My %s', 'placeholder: Groups', 'learndash' ), LearnDash_Custom_Label::get_label( 'groups' ) ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'show current user\'s %s.', 'placeholders: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => '',
					'options'   => array(
						// translators: placeholders: groups.
						''             => sprintf( esc_html_x( 'Show All %s (default)', 'placeholders: groups', 'learndash' ), learndash_get_custom_label_lower( 'Groups' ) ),
						// translators: placeholders: groups.
						'enrolled'     => sprintf( esc_html_x( 'Show Enrolled %s only', 'placeholders: groups', 'learndash' ), learndash_get_custom_label_lower( 'Groups' ) ),
						// translators: placeholders: groups.
						'not-enrolled' => sprintf( esc_html_x( 'Show not-Enrolled %s only', 'placeholders: groups', 'learndash' ), learndash_get_custom_label_lower( 'Groups' ) ),
					),
				),
				'status'         => array(
					'id'        => $this->shortcodes_section_key . '_status',
					'name'      => 'status',
					'type'      => 'multiselect',
					// translators: placeholder: Group.
					'label'     => sprintf( esc_html_x( 'All %s Status', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'filter %s by status.', 'placeholders: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => array( 'not_started', 'in_progress', 'completed' ),
					'options'   => array(
						'not_started' => esc_html__( 'Not Started', 'learndash' ),
						'in_progress' => esc_html__( 'In Progress', 'learndash' ),
						'completed'   => esc_html__( 'Completed', 'learndash' ),
					),
				),
				'show_content'   => array(
					'id'        => $this->shortcodes_section_key . 'show_content',
					'name'      => 'show_content',
					'type'      => 'select',
					// translators: placeholder: Group.
					'label'     => sprintf( esc_html_x( 'Show %s Content', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
					// translators: placeholder: group.
					'help_text' => sprintf( esc_html_x( 'shows %s content.', 'placeholder: group', 'learndash' ), learndash_get_custom_label_lower( 'group' ) ),
					'value'     => 'true',
					'options'   => array(
						''      => esc_html__( 'Yes (default)', 'learndash' ),
						'false' => esc_html__( 'No', 'learndash' ),
					),
				),
				'show_thumbnail' => array(
					'id'        => $this->shortcodes_section_key . '_show_thumbnail',
					'name'      => 'show_thumbnail',
					'type'      => 'select',
					// translators: placeholder: Group.
					'label'     => sprintf( esc_html_x( 'Show %s Thumbnail', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
					// translators: placeholder: group.
					'help_text' => sprintf( esc_html_x( 'shows a %s thumbnail.', 'placeholder: group', 'learndash' ), learndash_get_custom_label_lower( 'group' ) ),
					'value'     => 'true',
					'options'   => array(
						''      => esc_html__( 'Yes (default)', 'learndash' ),
						'false' => esc_html__( 'No', 'learndash' ),
					),
				),
			);

			if ( defined( 'LEARNDASH_COURSE_GRID_FILE' ) ) {
				$this->shortcodes_option_fields['col'] = array(
					'id'        => $this->shortcodes_section_key . '_col',
					'name'      => 'col',
					'type'      => 'number',
					'label'     => esc_html__( 'Columns', 'learndash' ),
					// translators: placeholder: group.
					'help_text' => sprintf( esc_html_x( 'number of columns to show when using %s grid addon', 'placeholder: group', 'learndash' ), learndash_get_custom_label_lower( 'group' ) ),
					'value'     => '',
					'class'     => 'small-text',
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Taxonomies', 'ld_group_category' ) == 'yes' ) {

				$this->shortcodes_option_fields['group_category_name'] = array(
					'id'        => $this->shortcodes_section_key . 'group_category_name',
					'name'      => 'group_category_name',
					'type'      => 'text',
					// translators: placeholder: Group.
					'label'     => sprintf( esc_html_x( '%s Category Slug', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'shows %s with mentioned category slug.', 'placeholder: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => '',
				);

				$this->shortcodes_option_fields['group_cat'] = array(
					'id'        => $this->shortcodes_section_key . 'group_cat',
					'name'      => 'group_cat',
					'type'      => 'number',
					// translators: placeholder: Group.
					'label'     => sprintf( esc_html_x( '%s Category ID', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'shows %s with mentioned category id.', 'placeholder: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => '',
					'class'     => 'small-text',
				);

				$this->shortcodes_option_fields['group_categoryselector'] = array(
					'id'        => $this->shortcodes_section_key . 'group_categoryselector',
					'name'      => 'group_categoryselector',
					'type'      => 'checkbox',
					// translators: placeholder: Group.
					'label'     => sprintf( esc_html_x( '%s Category Selector', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
					// translators: placeholder: group.
					'help_text' => sprintf( esc_html_x( 'shows a %s category dropdown.', 'placeholder: group', 'learndash' ), learndash_get_custom_label_lower( 'group' ) ),
					'value'     => '',
					'options'   => array(
						'true' => esc_html__( 'Yes', 'learndash' ),
					),
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Taxonomies', 'ld_group_tag' ) == 'yes' ) {
				$this->shortcodes_option_fields['group_tag'] = array(
					'id'        => $this->shortcodes_section_key . 'group_tag',
					'name'      => 'group_tag',
					'type'      => 'text',
					// translators: placeholder: Group.
					'label'     => sprintf( esc_html_x( '%s Tag Slug', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'shows %s with mentioned tag slug.', 'placeholder: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => '',
				);

				$this->shortcodes_option_fields['group_tag_id'] = array(
					'id'        => $this->shortcodes_section_key . 'group_tag_id',
					'name'      => 'group_tag_id',
					'type'      => 'number',
					// translators: placeholder: Group.
					'label'     => sprintf( esc_html_x( '%s Tag ID', 'placeholder: Group', 'learndash' ), LearnDash_Custom_Label::get_label( 'group' ) ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'shows %s with mentioned tag id.', 'placeholder: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => '',
					'class'     => 'small-text',
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Taxonomies', 'wp_post_category' ) == 'yes' ) {

				$this->shortcodes_option_fields['category_name'] = array(
					'id'        => $this->shortcodes_section_key . 'category_name',
					'name'      => 'category_name',
					'type'      => 'text',
					'label'     => esc_html__( 'WP Category Slug', 'learndash' ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'shows %s with mentioned WP category slug.', 'placeholder: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => '',
				);

				$this->shortcodes_option_fields['cat'] = array(
					'id'        => $this->shortcodes_section_key . 'cat',
					'name'      => 'cat',
					'type'      => 'number',
					'label'     => esc_html__( 'WP Category ID', 'learndash' ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'shows %s with mentioned WP category id.', 'placeholder: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => '',
					'class'     => 'small-text',
				);

				$this->shortcodes_option_fields['categoryselector'] = array(
					'id'        => $this->shortcodes_section_key . 'categoryselector',
					'name'      => 'categoryselector',
					'type'      => 'checkbox',
					'label'     => esc_html__( 'WP Category Selector', 'learndash' ),
					'help_text' => esc_html__( 'shows a WP category dropdown.', 'learndash' ),
					'value'     => '',
					'options'   => array(
						'true' => esc_html__( 'Yes', 'learndash' ),
					),
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Taxonomies', 'wp_post_tag' ) == 'yes' ) {
				$this->shortcodes_option_fields['tag'] = array(
					'id'        => $this->shortcodes_section_key . 'tag',
					'name'      => 'tag',
					'type'      => 'text',
					'label'     => esc_html__( 'WP Tag Slug', 'learndash' ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'shows %s with mentioned WP tag slug.', 'placeholder: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => '',
				);

				$this->shortcodes_option_fields['tag_id'] = array(
					'id'        => $this->shortcodes_section_key . 'tag_id',
					'name'      => 'tag_id',
					'type'      => 'number',
					'label'     => esc_html__( 'WP Tag ID', 'learndash' ),
					// translators: placeholder: groups.
					'help_text' => sprintf( esc_html_x( 'shows %s with mentioned WP tag id.', 'placeholder: groups', 'learndash' ), learndash_get_custom_label_lower( 'groups' ) ),
					'value'     => '',
					'class'     => 'small-text',
				);
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-group-access-settings.php */
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}

		/**
		 * Show Shortcode section footer extra
		 *
		 * @since 3.2.0
		 */
		public function show_shortcodes_section_footer_extra() {
			?>
			<script>
				jQuery( function() {
					if ( jQuery( 'form#learndash_shortcodes_form_ld_group_list select#ld_group_list_mygroups' ).length) {
						jQuery( 'form#learndash_shortcodes_form_ld_group_list select#ld_group_list_mygroups').on( 'change', function() {
							var selected = jQuery(this).val();
							if ( selected == 'enrolled' ) {
								jQuery( 'form#learndash_shortcodes_form_ld_group_list #ld_group_list_status_field select option').attr('selected', true);
								jQuery( 'form#learndash_shortcodes_form_ld_group_list #ld_group_list_status_field').slideDown();
							} else {
								jQuery( 'form#learndash_shortcodes_form_ld_group_list #ld_group_list_status_field').hide();
								jQuery( 'form#learndash_shortcodes_form_ld_group_list #ld_group_list_status_field select').val('');
							}
						});
						jQuery( 'form#learndash_shortcodes_form_ld_group_list select#ld_group_list_mygroups').change();
					}
				});
			</script>
			<?php
		}
	}
}
