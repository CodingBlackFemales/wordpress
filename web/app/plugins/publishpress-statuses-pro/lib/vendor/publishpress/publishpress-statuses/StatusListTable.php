<?php
namespace PublishPress_Statuses;

/**
 * Custom Statuses uses WordPress' List Table API for generating the custom status management table
 *
 * @since 0.7
 */
class StatusListTable extends \WP_List_Table
{
    public $callback_args;

    public $default_status;

    private $module;

    private $status_children = [];
    private $current_ancestors = [];

    private $status_roles = [];

    /**
     * Construct the extended class
     */
    public function __construct()
    {
        parent::__construct(
            [
                'plural' => 'statuses',
                'singular' => 'status',
                'ajax' => true,
            ]
        );

        add_action('admin_footer', [$this, 'adminFooterScripts']);

        // Possible troubleshooting use
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        if (!empty($_REQUEST['pp_refresh_role_counts'])) {
            delete_option('publishpress_statuses_num_roles');
        }
        */

        if (!$this->status_roles = get_option('publishpress_statuses_num_roles')) {
            $this->status_roles = [];
        }
    }

    function adminFooterScripts() {
        // Prevent disorienting vertical shift of the statuses table caused by standard notice div after drag-ordering a status.
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function ($) {
            $("div.notice-success").css('float', 'right').insertBefore("header > :first-child");
        });
        /* ]]> */
        </script>
        <?php
    }

    /**
     * Pull in the data we'll be displaying on the table
     *
     * @since 0.7
     */
    public function prepare_items()
    {
        global $publishpress;

        $columns = $this->get_columns();
        
        $hidden = apply_filters('publishpress_statuses_hidden_columns', [
            'position',
            'status_name'
        ]);

        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $this->items = \PublishPress_Statuses::getPostStati(
            [], 
            ['output' => 'object'],
            ['context' => 'load']
        );

        foreach (['draft-revision', 'pending-revision', 'future-revision'] as $locked_revision_status) {
            if (!empty($this->items[$locked_revision_status]->alternate) || !empty($this->items[$locked_revision_status]->revision_alternate) || !empty($this->items[$locked_revision_status]->disabled)) {
                $this->moveItem($this->items, $locked_revision_status, 'up', '_revision-alternate');
                $this->items[$locked_revision_status]->alternate = false;
                $this->items[$locked_revision_status]->revision_alternate = false;
                $this->items[$locked_revision_status]->disabled = false;
            }
        }

        $total_items = count($this->items);

        foreach($this->items as $status) {
            if (!empty($status->status_parent)) {
                if (!isset($this->status_children[$status->status_parent])) {
                    $this->status_children[$status->status_parent] = [];
                }

                $this->status_children[$status->status_parent][] = $status->name;
            }
        }

        if (!empty($this->items['_revision-workflow'])) {
            $main_workflow = $this->items['_revision-workflow'];

            unset($this->items['_revision-workflow']);
            $this->items = ['_revision-workflow' => $main_workflow] + $this->items;
        }

        $this->default_status = \PublishPress_Statuses::DEFAULT_STATUS;

        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page' => $total_items,
            ]
        );
    }

    // https://stackoverflow.com/questions/11282592/move-an-element-by-key-before-another-element-by-key-in-the-context-of-an-associ
    private function moveItem(&$ref_arr, $key1, $move, $key2 = null)
    {
        $arr = $ref_arr;

        if($key2 == null) $key2 = $key1;

        if(!isset($arr[$key1]) || !isset($arr[$key2])) return false;

        $i = 0; foreach($arr as &$val) $val = array('sort' => (++$i * 10), 'val' => $val);

        switch($move)
        {
            case 'up':     $arr[$key1]['sort'] = $arr[$key2]['sort'] - ($key1 == $key2 ? 15 : 5); break;
            case 'down':   $arr[$key1]['sort'] = $arr[$key2]['sort'] + ($key1 == $key2 ? 15 : 5); break;
            default: return false;
        }

        uasort($arr, function($a, $b) { return intval($a['sort'] > $b['sort']); });

        foreach($arr as &$val) $val = $val['val'];

        $ref_arr = $arr;

        return true;
    }

    /**
     * Table shows (hidden) position, status name, status description, and the post count for each activated
     * post type
     *
     * @return array $columns Columns to be registered with the List Table
     * @since 0.7
     *
     */
    public function get_columns()
    {
        global $publishpress;

        $columns = [
            'position' => __('Position', 'publishpress-statuses'),
            'status_name' => __('Status Name', 'publishpress-statuses'),
            'name' => __('Name', 'publishpress-statuses'),
            'icon' => __('Icon', 'publishpress-statuses'),
            'roles' => esc_html__('Roles', 'publishpress-statuses'),
            'post_types' => esc_html__('Post Types', 'publishpress-statuses'),
            'description' => __('Description', 'publishpress-statuses'),
        ];

        return apply_filters('publishpress_statuses_admin_columns', $columns);
    }

    public function display() {
        $singular = $this->_args['singular'];

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		//$this->display_tablenav( 'top' );

        $this->screen->render_screen_reader_content( 'heading_list' );
		?>

<div class="wrap pp-nested-list">

<div class="is-dismissible notice notice-success pp-float-notice" style="clear:both;display:none;"><p></p></div>

<?php if (!defined('PUBLISHPRESS_STATUSES_PRO_VERSION') && ('revision' == \PP_Statuses_Functions::REQUEST_key('status_type'))):?>
    <div id="pp-statuses-promo">
    
    <!-- CTA Section -->
	<div class="pp-cta-section">
		<h4>
			<?php esc_html_e('Upgrade to create an approval workflow for published content', 'publishpress-statuses'); ?>
		</h4>
		<p>
			<?php esc_html_e('Create approval workflows using PublishPress Statuses Pro together with the PublishPress Revisions Pro plugin', 'publishpress-statuses'); ?>
		</p>

		<div class="pp-revisions-pro-features">
			<ul>
				<li>
					&nbsp;<?php _e('The PublishPress Revisions plugin allows users to submit changes to published posts', 'publishpress-statuses');?>
				</li>
				<li>
					&nbsp;<?php _e('PublishPress Statuses plugin enables you to add statuses for post changes created with Revisions', 'publishpress-statuses');?>
				</li>
				<li>
					&nbsp;<?php _e('Each post type can have different Revisions statuses', 'publishpress-statuses');?>
				</li>
				<li>
					&nbsp;<?php _e('You choose which users can manage content in each Revisions status', 'publishpress-statuses');?>
				</li>
			</ul>
		</div>

		<div class="pp-cta-buttons">
			<a href="https://publishpress.com/statuses/" 
				class="button-primary button-large pp-upgrade-btn" 
				target="_blank">
				<?php esc_html_e('Upgrade to Pro', 'publishpress-statuses'); ?>
			</a>
			<a href="https://publishpress.com/knowledge-base/revisions-statuses/" 
				target="_blank"
				class="pp-learn-more-link">
				<?php esc_html_e('Learn More', 'publishpress-statuses'); ?>
			</a>
		</div>
	</div>

	<?php if (!empty($_REQUEST['rvy_promo_img'])):  // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
	<div class="pp-integration-card">
	<div>
	<img src="<?php echo esc_url(trailingslashit(PUBLISHPRESS_STATUSES_URL) . 'revision-statuses.png');?>" style="width: 797px;" />
	</div>

	<div class="pp-upgrade-overlay">
		<h4><?php esc_html_e('Premium Feature', 'publishpress-statuses'); ?></h4>
		<p><?php esc_html_e('Install Statuses Pro to unlock custom revision statuses.', 'publishpress-statuses');?></p>
		<p><?php esc_html_e('Configure for any post type and role to match your editing workflow.', 'publishpress-statuses');?></p>
		<div class="pp-upgrade-buttons">
			<a href="<?php echo esc_url('https://publishpress.com/knowledge-base/revisions-statuses/'); ?>" target="_blank" class="pp-upgrade-btn-secondary">
				<?php esc_html_e('Learn More', 'publishpress-statuses'); ?>
			</a>

			<a href="https://publishpress.com/statuses/" target="_blank" class="pp-upgrade-btn-primary">
			<?php esc_html_e('Upgrade to Pro', 'publishpress-statuses');?>
			</a>
		</div>
	</div>
	</div>
    <?php endif;?>

    </div>

<?php else:?>
<div class="pp-nested-list">
	<div id="status_list_header" class="status-list-header">

        <div class="row tpl-default">
            <div class="check-column">
                <input type="checkbox" data-np-check-all="pp-nested_bulk[]" data-np-bulk-checkbox="">
            </div>

            <div class="child-toggle" style="padding-left: 0"><div class="child-toggle-spacer"></div></div>

            <div class="row-inner">
                <table class="status-row" style="float:right; width:100%"><tbody>
                <tr>

                <?php
                list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

                foreach ($columns as $column_name => $column_display_name) {
                    $is_hidden = in_array($column_name, $hidden, true);?>
                    <th class="<?php echo esc_attr($column_name);?>" <?php if ($is_hidden) echo 'style="width:0"';?>>
                        <div class="<?php echo esc_attr($column_name);?> column-<?php echo esc_attr($column_name);?>" <?php if ($is_hidden) echo 'hidden';?>>
                            <?php echo esc_html($column_display_name);?>
                        </div>
                    </th>
                    <?php
                }
                ?>

                </tr>
                </tbody></table>
            </div>
        </div>

    </div>
</div>

<ol class="sortable visible ui-sortable pp-nested-list <?php echo esc_attr(implode( ' ', $this->get_table_classes() )); ?>" id="the_status_list">
	<?php $this->display_rows_or_placeholder(); ?>
</ol>

<?php endif;?>

</div>

<?php
    if (defined('PUBLISHPRESS_STATUSES_PRO_VERSION') && defined('PUBLISHPRESS_REVISIONS_VERSION') && version_compare(PUBLISHPRESS_REVISIONS_VERSION, '3.6.0-rc', '<')) {
        echo '<div class="pp-custom-status-hints">';
        printf(
            esc_html__('To define and control Revision statuses, update the %1$sPublishPress Revisions%2$s plugin to version %3$s or higher.', 'publishpress-statuses'),
            '<a href="https://publishpress.com/revisions/" target="_blank">',
            '</a>',
            '3.6.0'
        );
        echo '</div>';
    }
}

    /**
	 * Generates the tbody element for the list table.
	 *
	 * @since 3.1.0
	 */
	public function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows();
		} else {
			echo '<li class="no-items colspanchange">';
			$this->no_items();
			echo '</li>';
		}
	}

	/**
	 * Generates the table rows.
	 *
	 * @since 3.1.0
	 */
	public function display_rows() {
        $this->display_section_row('_pre-publish', 
            [
                'label' => __('Main Workflow:', 'publishpress-statuses'),
                'class' => 'moderation-status'
            ]);

		foreach ( $this->items as $item ) {
			$this->single_row( $item );
		}
    }
    
    private function display_section_row($key, $args) {
        $class = (!empty($args['class'])) ? $args['class'] : '';
        $label = (!empty($args['label'])) ? $args['label'] : $key;

        $options = \PublishPress_Statuses::instance()->options;

        $hide_status_dropdown = !empty($options->hide_manual_status_selectors);

        if (($key == '_pre-publish-alternate') && (\PP_Statuses_Functions::empty_REQUEST('status_type') || 'moderation' == \PP_Statuses_Functions::REQUEST_key('status_type'))) :?>
            <li class="ui-sortable-placeholder moderation-status ui-temp-placeholder" style="height: 50px;">
            <div class="row tpl-default">
                <div class="child-toggle" style="padding-left: 0">
                    <div class="child-toggle-spacer"></div>
                </div>

                <div class="row-inner">
                    <table class="status-row" style="width:100%"><tbody><tr>
                    <td colspan="7" style="text-align: center"><?php _e('Drop any status here to include it in main workflow for new posts.', 'publishpress-statuses');?></td>
                    </tr></tbody></table>
                </div>
            </div>
            </li>
        <?php endif;

		do_action('publishpress_statuses_table_list', $key, $args);
        if (!$status_type = \PP_Statuses_Functions::REQUEST_key('status_type')) {
            $status_type = 'moderation';
        }

        $hidden = (in_array($key, ['_pre-publish', '_pre-publish-alternate']) && ('moderation' != $status_type))
        || (in_array($key, ['_standard-publication', '_visibility-statuses']) && ('visibility' != $status_type))
        || (('_disabled' == $key) && $hide_status_dropdown);
        ?>
<li id="status_row_<?php echo esc_attr($key);?>" class="page-row section-row <?php echo esc_attr($class);?>"<?php if ($hidden) echo ' style="display: none;"';?>>
<div class="row tpl-default">
<div class="row-inner has-row-actions">

<table class="status-row" style="float:right; width:100%"><tbody><tr>

<?php if (\PublishPress_Statuses::getCustomStatus($key)) :?>
<td class="status_name" style="width:0"><div class="status_name <?php echo esc_attr($key);?> column-<?php echo esc_attr($key);?> hidden"><?php echo esc_html($key);?></div></td>
<?php endif; ?>

<td class="name"><div class="name column-name has-row-actions column-primary" data-colname="Name">
<strong>
<?php echo esc_html($label);?>
</strong>

<?php 
if (in_array($key, ['_pre-publish', '_pre-publish-alternate'])):?>
<?php 
if ('_pre-publish' == $key) {
    $this->generateTooltip(
        esc_html__('Statuses in the main workflow are presented for convenient default selection when updating an unpublished post.', 'publishpress-statuses'),
        '',
        'bottom'
    );

} elseif ('_pre-publish-alternate' == $key) {
    if (!$hide_status_dropdown) {
        $this->generateTooltip(
            esc_html__('Statuses outside the main workflow are manually selectable when editing an unpublished post.', 'publishpress-statuses'),
            '',
            'bottom'
        );
    }
}
?>
<?php endif;?>

<?php 
do_action('publishpress_statuses_table_row', $key, []);
?>
</div>
</td>

</tr></tbody></table>
</div></div></li>
        <?php
        switch ($key) {
            case '_pre-publish-alternate':
                if ((\PP_Statuses_Functions::empty_REQUEST('status_type') || 'moderation' == \PP_Statuses_Functions::REQUEST_key('status_type'))) :?>
                <li class="ui-sortable-placeholder <?php if (!$hide_status_dropdown) echo 'alternate-moderation-status'; else echo 'disabled-status';?> ui-temp-placeholder" style="height: 50px;">
                <div class="row tpl-default">
                    <div class="child-toggle" style="padding-left: 0">
                        <div class="child-toggle-spacer"></div>
                    </div>
    
                    <div class="row-inner">
                        <table class="status-row" style="width:100%"><tbody><tr>
                        <td colspan="7" style="text-align: center">
                        <?php 
                        if (!$hide_status_dropdown) {
                            _e('Drop any status here to make it manually selectable outside the main workflow.', 'publishpress-statuses');
                        } else {
                            _e('Drop any status here to disable.', 'publishpress-statuses');
                        }
                        ?>
                        </td>
                        </tr></tbody></table>
                    </div>
                </div>
                </li>

                <?php endif;
                break;

            case '_disabled':
                if (!$hide_status_dropdown) :?>
                <li class="ui-sortable-placeholder disabled-status ui-temp-placeholder" style="height: 50px;">
                <div class="row tpl-default">
                    <div class="child-toggle" style="padding-left: 0">
                        <div class="child-toggle-spacer"></div>
                    </div>
    
                    <div class="row-inner">
                        <table class="status-row" style="width:100%"><tbody><tr>
                        <td colspan="7" style="text-align: center"><?php _e('Drop any status here to disable.', 'publishpress-statuses');?></td>
                        </tr></tbody></table>
                    </div>
                </div>
                </li>

                <?php endif;

                break;
                
			default:
    			do_action('publishpress_statuses_table_alternate_list', $key, $args);
        		
        }
    }

	/**
	 * Generates content for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object|array $item The current item
	 */
	public function single_row( $item ) {
        $last_status_parent = end($this->current_ancestors);

        $options = \PublishPress_Statuses::instance()->options;

        if (empty($this->status_children[$item->name]) || ($item->status_parent != $last_status_parent) ) :
            $item_status_parent = (!empty($item->status_parent)) ? $item->status_parent : '';

            if ($last_status_parent && ($last_status_parent != $item_status_parent)) :
                // Close the nested list and parent status
                array_pop($this->current_ancestors);
                ?>
                </ol>
                </li>
            <?php
            endif;
            ?>
        <?php 
        endif;?>
        <?php

        if (!$status_type = \PP_Statuses_Functions::REQUEST_key('status_type')) {
            $status_type = 'moderation';
        }

        if ('future' == $item->name) {
            $this->display_section_row('_standard-publication', 
            [
                'label' => __('Standard Publication:', 'publishpress-statuses'),
                'class' => ''
            ]);
        
        } elseif ('private' == $item->name) {
            $this->display_section_row('_visibility-statuses', 
            [
                'label' => __('Visibility Statuses for Private Publication:', 'publishpress-statuses'),
                'class' => 'private-status'
            ]);

        } elseif ('_pre-publish-alternate' == $item->name) {
            $this->display_section_row('_pre-publish-alternate', 
            [
                'label' => (empty($options->hide_manual_status_selectors)) ? __('Alternate Workflows:', 'publishpress-statuses') : __('Disabled Statuses (drag to re-enable):', 'publishpress-statuses'),
                'class' => (empty($options->hide_manual_status_selectors)) ? 'alternate-moderation-status' : 'disabled-status'
            ]);

            return;

        } elseif ('_disabled' == $item->name) {
            $this->display_section_row('_disabled', 
            [
                'label' => 
                    // translators: %s is the opening and closing <span> tags
                    (empty($options->hide_manual_status_selectors)) ? __('Disabled Statuses (drag to re-enable):', 'publishpress-statuses') : ' ',
                'class' => 'disabled-status'
            ]);

            return;

        } elseif (isset($item->taxonomy) && (\PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS == $item->taxonomy)) {
            if (!empty($item->status_type) && ($item->status_type == $status_type)) {
                $this->display_section_row($item->name, 
                [
                    'label' => $item->label,
                    'class' => !empty($item->class) ? $item->class : '',
                ]);
            }

            return;
        }

        $hidden = false;
        $class = '';

        if (!empty($item->alternate) && ('future' != $item->name)) {
            $class = (empty($options->hide_manual_status_selectors)) ? ' alternate-moderation-status' : ' disabled-status';

            if ('moderation' != $status_type) {
                $hidden = true;
            }

        } elseif ((!empty($item->moderation) || ('draft' == $item->name)) && ('future' != $item->name)) {
            $class = ' main-section moderation-status';

            if ('moderation' != $status_type) {
                $hidden = true;
            }

        } elseif (!empty($item->private)) {
            $class = ' main-section private-status';

            if ('visibility' != $status_type) {
                $hidden = true;
            }
        } else {
            $class = '';

            if ('visibility' != $status_type) {
                $hidden = true;
            }
        }

        if (!empty($item->disabled)) {
            $class .= ' disabled-status';
        }

        if (in_array($item->name, ['_pre-publish-alternate', '_disabled']) 
        || (isset($item->taxonomy)  && (\PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS == $item->taxonomy))
        ) {
            $class .= ' section-row';
        }

        // Deferred, Needs Work and Rejected statuses require an Alternate Workflow selection UI
        if (!empty($options->hide_manual_status_selectors) && in_array($item->name, ['deferred', 'needs-work', 'rejected'])) {
            $hidden = true;
        }
    
    	$hidden = apply_filters('publishpress_statuses_table_alternate_row_hidden', $hidden, $item, $status_type);
    	
    	$class = apply_filters('publishpress_statuses_table_alternate_row_class', $class, $item, $status_type);

        ?>
        <li id="status_row_<?php echo esc_attr($item->name);?>" class="page-row<?php echo esc_attr($class);?>"<?php if ($hidden) echo ' style="display: none;"';?>>

        <div class="row tpl-default">
            <div class="check-column">
                <input id="cb-select-<?php echo esc_attr($item->name);?>" type="checkbox" name="status[]" value="<?php echo esc_attr($item->name);?>" />
            </div>

            <div class="child-toggle" style="padding-left: 0">
                <div class="child-toggle-spacer"></div>
            </div>

            <div class="row-inner has-row-actions">
                <?php
                $this->single_row_columns( $item );
                ?>
            </div>
        </div>

        <?php 
        if (!empty($this->status_children[$item->name])) :
            // Open a nested list instead of closing this item
            $this->current_ancestors []= $item->name;
            ?>
            <ol class="pp-nested-list" style="display: block">
            <?php
        else:
            // Normal closure of this item
            ?>
            </li>
        <?php 
        endif;?>
        <?php

        if (('private' == $item->name) && ('visibility' == $status_type) && !defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) :?>
            <li class="pp-cta-section pp-visibility-statuses-promo">
                <h4>
                    <?php esc_html_e('Upgrade to choose who can view your non-public posts', 'publishpress-statuses'); ?>
                </h4>
                <p>
                    <?php esc_html_e('Upgrade to PublishPress Statuses Pro to define your own custom visibility statuses.', 'publishpress-statuses'); ?>
                </p>

                <div class="pp-revisions-pro-features">
                    <ul>
                        <li>
                            &nbsp;<?php _e('Create multiple visibility statuses to show content to different users', 'publishpress-statuses');?>
                        </li>
                        <li>
                            &nbsp;<?php _e('Choose which user roles can see posts in each status', 'publishpress-statuses');?>
                        </li>
                        <li>
                            &nbsp;<?php _e('Select which user roles can move posts to each status', 'publishpress-statuses');?>
                        </li>
                        <li>
                            &nbsp;<?php _e('Decide which user roles can edit posts in each status', 'publishpress-statuses');?>
                        </li>
                    </ul>
                </div>

                <div class="pp-cta-buttons">
                    <a href="https://publishpress.com/statuses/" 
                        class="button-primary button-large pp-upgrade-btn" 
                        target="_blank">
                        <?php esc_html_e('Get PublishPress Statuses Pro', 'publishpress-statuses'); ?>
                    </a>
                    <a href="https://publishpress.com/knowledge-base/custom-post-visibility/" 
                        target="_blank"
                        class="pp-learn-more-link">
                        <?php esc_html_e('Learn More', 'publishpress-statuses'); ?>
                    </a>
                </div>
            </li>
        <?php endif;
	}

	/**
	 * @param object|array $item
	 * @param string $column_name
	 */
	protected function column_default( $item, $column_name ) {}

	/**
	 * @param object|array $item
	 */
	protected function column_cb( $item ) {}

	/**
	 * Generates the columns for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object|array $item The current item.
	 */
	protected function single_row_columns( $item ) {
        ?>
        <table class="status-row" style="float:right; width:100%"><tr>

        <?php
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
            if (!in_array($column_name, ['name']) && !empty($item->taxonomy) && (\PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS == $item->taxonomy)) {
                continue;
            }

			$classes = "$column_name column-$column_name";
			if ( 'name' === $column_name ) {
                $classes .= ' has-row-actions column-primary';
            }

			if ( in_array( $column_name, $hidden, true ) ) {
				$classes .= ' hidden';
            }
            
			// Comments column uses HTML in the display name with screen reader text.
			// Strip tags to get closer to a user-friendly string.

            echo '<td class="' . esc_attr($column_name) . '" ';
            if ( in_array( $column_name, $hidden, true ) ) echo 'style="width:0"';
            echo '>';

			if ( 'cb' === $column_name ) {
				echo '<div scope="row" class="check-column">';

                // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
				/* echo $this->column_cb( $item ); */
                echo '</div>';
                
            } elseif ( 'status_name' === $column_name ) {
                echo '<div class="' . esc_attr($classes) . '"' . 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '">';
                echo esc_html($item->name);
                echo '</div>';

            } elseif ('post_types' == $column_name) {
                echo '<div class="' . esc_attr($classes) . '"' . 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '">';
                
                if (!in_array($item->name, ['draft', 'pending', 'future', 'publish', 'private'])) {
                    $status_obj = $item;

                    if (!empty($status_obj) && !empty($status_obj->post_type)) {
                        $arr_captions = [];
                        foreach ($status_obj->post_type as $_post_type) {
                            if ($type_obj = get_post_type_object($_post_type)) {
                                $arr_captions [] = $type_obj->labels->singular_name;
                            }
                        }

                        $types_caption = implode(', ', array_slice($arr_captions, 0, 7));

                        if (count($arr_captions) > 7) {
                            // translators: %s is the list of post types
                            $types_caption = sprintf(__('%s, more...', 'publishpress-statuses'), $types_caption);
                        }
                    } else {
                        $types_caption = __('All');
                    }

                    $url = admin_url("admin.php?action=edit-status&name={$item->name}&page=publishpress-statuses&pp_tab=post_types");

                    echo '<a href="' . esc_url($url) . '">' . esc_html($types_caption) . '</a>';
                } else {
                    esc_html(__('All'));
                }

                echo '</div>';

            } elseif ( 'roles' === $column_name ) {
                if (!in_array($item->name, ['draft', 'future', 'publish', 'private'])) {
                    echo '<div class="' . esc_attr($classes) . '"' . 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '">';

                    if (!isset($this->status_roles[$item->name])) {
                        $this->status_roles[$item->name] = \PublishPress_Statuses::updateStatusNumRoles($item->name);
                    }

                    $num_roles = isset($this->status_roles[$item->name]) ? $this->status_roles[$item->name] : 0; // @todo

                    $url = admin_url("admin.php?action=edit-status&name={$item->name}&page=publishpress-statuses&pp_tab=roles");

                    echo '<a href="' . esc_url($url) . '">' . esc_html($num_roles) . '</a>';
                    echo '</div>';
                }

			} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
                echo "<div class='" . esc_attr($classes) . "' " . 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '">';

                call_user_func( array( $this, 'column_' . $column_name ), $item );

                echo '</div>';
			} else {
				echo '<div class="' . esc_attr($classes) . '"' . 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '">';
                do_action('publishpress_statuses_custom_column', $column_name, $item, compact('column_display_name'));
                echo '</div>';
            }

            echo '</td>';
        }
        ?>

        </tr></table>

        <div style="float:left"></div>
		
        <div style="float:left">

        </div>
        
        <?php
    }
    

    public function get_table_classes()
    {
        $classes_list = parent::get_table_classes();
        $class_to_remove = 'fixed';

        $class_to_remove_index = array_search($class_to_remove, $classes_list);
        if ($class_to_remove_index === false) {
            return $classes_list;
        }

        unset($classes_list[$class_to_remove_index]);

        return $classes_list;
    }

    /**
     * Message to be displayed when there are no custom statuses. Should never be displayed, but we'll customize it
     * just in case.
     *
     * @since 0.7
     */
    public function no_items()
    {
        _e('No custom statuses found.', 'publishpress-statuses');
    }

    /**
     * Hidden column for storing the status position
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 0.7
     *
     */
    public function column_position($item)
    {
        return esc_html($item->position);
    }

    /**
     * Displayed column showing the name of the status
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 0.7
     *
     */
    public function column_name($item)
    {
        global $publishpress;

        if (!empty($item->taxonomy) && (\PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS == $item->taxonomy)) {
            echo '<strong><em>';
            echo esc_html($item->label);
            echo '</em></strong>';
        }

        $item_edit_link = \PublishPress_Statuses::getLink(
            [
                'action' => 'edit-status',
                'name' => $item->name,
            ]
        );
        
        $status_obj = $item;

        $handle_class = (!empty($status_obj) && empty($status_obj->public) && !in_array($status_obj->name, ['draft', 'future', 'private']))
        ? 'handle '
        : 'handle-disabled ';
        
        echo "<img src='" . esc_url(PUBLISHPRESS_STATUSES_URL . "common/assets/handle.svg") . "' alt='Sorting Handle' class='" . esc_attr($handle_class) . "}np-icon-menu'>";

        echo '<span class="pp-statuses-color" style="background:' . esc_attr($item->color) . ';"></span>';

        echo '<strong>';
        if (empty($item->_builtin)) {
            echo '<em>';
        }

        if ($item_edit_link) {
            echo '<a href="' . esc_url($item_edit_link) . '">';
        }

        echo esc_html($item->label);
        
        if ($item_edit_link) {
            echo '</a>';
        }

        $suffixes = [];

        if (!empty($status_obj->_builtin)) {
            $suffixes []= esc_html__('Core', 'publishpress-statuses');
         }

        if ($item->name == $this->default_status) {
            $suffixes []= esc_html__('Default', 'publishpress-statuses');
        }

        if ($suffixes) {
            echo ' - ' . esc_html(implode(', ', $suffixes));
        }

        if (empty($item->_builtin)) {
            echo '</em>';
        }

        echo '</strong>';

        $actions = [];
 
        $status_obj = $item;

        $url = admin_url("admin.php?action=edit-status&name={$status_obj->name}&page=publishpress-statuses");

        $options = \PublishPress_Statuses::instance()->options;

        if (
            !in_array($status_obj->name, ['draft', 'pending', 'publish', 'future']) 
            && (empty($status_obj->pp_builtin) || (empty($options->label_storage) && ('user' != $options->label_storage)))
        ) {
            $url = admin_url("admin.php?action=edit-status-labels&name={$status_obj->name}&page=publishpress-statuses");
            $actions['labels'] =  ['url' => esc_url($url), 'label' => esc_html__('Labels', 'publishpress-statuses')];
        }

        if (empty($status_obj) || empty($status_obj->_builtin)) {
            $actions['disable'] = ['url' => '#', 'label' => __('Disable', 'publishpress-statuses')];
        }

        if (empty($status_obj) || empty($status_obj->_builtin)) {
            $actions['enable'] = ['url' => '#', 'label' => __('Enable', 'publishpress-statuses')];
        }

        if (empty($status_obj) || (empty($status_obj->_builtin) && empty($status_obj->pp_builtin))) {
            $actions['delete'] = ['url' => '#', 'label' => __('Delete', 'publishpress-statuses')];
        }

        $actions = apply_filters('publishpress_statuses_row-actions', $actions, $item);
        
        $this->row_actions($actions, false, $item);
    }

    protected function row_actions( $actions, $always_visible = false, $item = false ) {
		$action_count = count( $actions );

		if ( ! $action_count ) {
			return;
		}

		$mode = get_user_setting( 'posts_list_mode', 'list' );

		if ( 'excerpt' === $mode ) {
			$always_visible = true;
		}

        $classes = $always_visible ? 'row-actions visible' : 'row-actions';

        ?>
		<div class="row-actions<?php if ($always_visible) echo ' visible';?>">

        <?php
		$i = 0;

		foreach ( $actions as $action => $arr ) {
			++$i;
			echo "<span class='" . esc_attr($action) . "'";
             
            if ((('disable' == $action) && is_object($item) && !empty($item->disabled)) 
            || (('enable' == $action) && is_object($item) && empty($item->disabled))
            ) {
                echo " style='display:none;'";
            }

            if (('enable' == $action) && is_object($item) && !empty($item->disabled)) {
                $action_count++;
            }

            $sep = ( $i < $action_count - 1 ) ? ' | ' : ' ';

            echo ">";
            echo '<a href="' . esc_url($arr['url']) . '">' . esc_html($arr['label']) . '</a>';
            echo esc_html($sep) . "</span>";
		}

        echo '</div>';
	}

    /**
     * Displayed column showing the description of the status
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 0.7
     *
     */
    public function column_description($item)
    {
        $descript = (!empty($item->description && ('-' != $item->description)) ? $item->description : '&nbsp;');

        $url = admin_url("admin.php?action=edit-status&name={$item->name}&page=publishpress-statuses");

        echo "<a href='" . esc_url($url) . "'>" . esc_html($descript) . "</a>";
    }

    /**
     * Displayed column showing the icon of the status
     *
     * @param object $item Custom status as an object
     *
     * @return string $output What will be rendered
     * @since 1.7.0
     *
     */
    public function column_icon($item)
    {
        $url = admin_url("admin.php?action=edit-status&name={$item->name}&page=publishpress-statuses");

        echo '<a href="' . esc_url($url) . '"><span class="dashicons ' . esc_html($item->icon) . '"></span></a>';
    }

    private function generateTooltip($tooltip, $text = '', $position = 'top', $useIcon = true)
    {
        ?>
        <span data-toggle="tooltip" data-placement="<?php esc_attr_e($position); ?>">
        <?php esc_html_e($text);?>
        <span class="tooltip-text"><span><?php esc_html_e($tooltip);?></span><i></i></span>
        <?php 
        if ($useIcon) : ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 50 50" style="margin-left: 4px; vertical-align: text-bottom;">
                <path d="M 25 2 C 12.264481 2 2 12.264481 2 25 C 2 37.735519 12.264481 48 25 48 C 37.735519 48 48 37.735519 48 25 C 48 12.264481 37.735519 2 25 2 z M 25 4 C 36.664481 4 46 13.335519 46 25 C 46 36.664481 36.664481 46 25 46 C 13.335519 46 4 36.664481 4 25 C 4 13.335519 13.335519 4 25 4 z M 25 11 A 3 3 0 0 0 25 17 A 3 3 0 0 0 25 11 z M 21 21 L 21 23 L 23 23 L 23 36 L 21 36 L 21 38 L 29 38 L 29 36 L 27 36 L 27 21 L 21 21 z"></path>
            </svg>
        <?php
        endif; ?>
        </span>
        <?php
    }
}
