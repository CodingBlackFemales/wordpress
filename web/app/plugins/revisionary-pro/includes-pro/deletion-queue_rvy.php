<?php
/**
 * @global string       $post_type
 * @global WP_Post_Type $post_type_object
 */
global $post_type, $post_type_object, $wpdb;

if ( ! $post_types = rvy_get_manageable_types() ) {
	wp_die( esc_html__( 'You are not allowed to manage deletion requests.', 'revisionary' ) );
}

set_current_screen( 'revisionary-deletion' );

require_once( dirname(__FILE__).'/class-deletion-list-table_rvy.php');
$wp_list_table = new Revisionary_Deletion_List_Table(['screen' => 'revisionary-deletion', 'post_types' => $post_types]);					//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$pagenum = $wp_list_table->get_pagenum();

$parent_file = 'admin.php?page=revisionary-q';																								//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$submenu_file = 'admin.php?page=revisionary-deletion';																						//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

$wp_list_table->prepare_items();

$bulk_counts = array(
	'requested_deletion_count' => isset( $_REQUEST['requested_deletion_count'] ) ? absint( $_REQUEST['requested_deletion_count'] ) : 0,		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'request_cleared_count' => isset( $_REQUEST['request_cleared_count'] ) ? absint( $_REQUEST['request_cleared_count'] ) : 0,				//phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'deleted' => isset( $_REQUEST['deleted'] ) ? absint( $_REQUEST['deleted'] ) : 0,														//phpcs:ignore WordPress.Security.NonceVerification.Recommended
);

$bulk_messages = [];
$bulk_messages['post'] = array(
	'requested_deletion_count'   => sprintf(esc_html(_n( '%s revision submitted.', '%s revisions submitted.', $bulk_counts['requested_deletion_count'], 'revisionary' )), $bulk_counts['requested_deletion_count']),
	'request_cleared_count'   => sprintf(esc_html(_n( '%s deletion request cleared.', '%s deletion requests cleared.', $bulk_counts['request_cleared_count'], 'revisionary' )), $bulk_counts['request_cleared_count']),
	'deleted'   => sprintf(esc_html(_n( '%s post deleted.', '%s posts deleted.', $bulk_counts['deleted'], 'revisionary' )), $bulk_counts['deleted']),
);

$bulk_messages['page'] = $bulk_messages['post'];

/**
 * Filters the bulk action updated messages.
 *
 * By default, custom post types use the messages for the 'post' post type.
 *
 * @since 3.7.0
 *
 * @param array $bulk_messages Arrays of messages, each keyed by the corresponding post type. Messages are
 *                             keyed with 'updated', 'locked', 'deleted', 'trashed', and 'untrashed'.
 * @param array $bulk_counts   Array of item counts for each message, used to build internationalized strings.
 */
$bulk_messages = apply_filters( 'bulk_post_updated_messages', $bulk_messages, $bulk_counts );
$bulk_counts = array_filter( $bulk_counts );

require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>
<div class="wrap pressshack-admin-wrapper revision-q">
<header>
<h1 class="wp-heading-inline"><?php

echo '<span class="dashicons dashicons-backup"></span>&nbsp;';

if ( ! empty( $_REQUEST['type'] ) ) {										//phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$type_obj = get_post_type_object(sanitize_key($_REQUEST['type']));		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

$filters = [];

if (!empty($_REQUEST['author'])) {											//phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ($_user = new WP_User((int) $_REQUEST['author'])) {					//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filters['author'] = $_user->display_name;
	}
}

if (!empty($_REQUEST['type']) && empty($published_title)) {					//phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$filters['post_type'] = $type_obj->labels->name;
}

if (!empty($_REQUEST['post_author']) && empty($published_title)) {			//phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ($_user = new WP_User((int) $_REQUEST['post_author'])) {				//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filters['post_author'] = $filters 
		? sprintf(esc_html__('%sPost Author: %s', 'revisionary'), ' - ', $_user->display_name) 
		: sprintf(esc_html__('%sPost Author: %s', 'revisionary'), '', $_user->display_name);
	}
}

$filter_csv = ($filters) ? ' (' . implode(" ", $filters) . ')' : '';

printf( esc_html__('Deletion Queue %s', 'revisionary' ), esc_html($filter_csv));
?></h1>

<?php
if ( isset( $_REQUEST['s'] ) && strlen( sanitize_text_field(wp_unslash($_REQUEST['s'])) ) ) {	//phpcs:ignore WordPress.Security.NonceVerification.Recommended
	/* translators: %s: search keywords */
	printf( 
		' <span class="subtitle">' . esc_html__( 'Search results for "%s"' ) . '</span>', 
		esc_html(wp_strip_all_tags(sanitize_text_field(wp_unslash($_REQUEST['s'])))) );			//phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
?>

</header>
<!--<hr class="wp-header-end">-->

<?php
// If we have a bulk message to issue:
$messages = array();

foreach ( $bulk_counts as $message => $count ) {
	if (!empty($bulk_messages['post'][$message])) {
		$any_messages = true;
		break;
	}
}

if (!empty($any_messages)) {
	echo '<div id="message" class="updated notice is-dismissible"><p>';
}

foreach ( $bulk_counts as $message => $count ) {
	if (!empty($bulk_messages['post'][$message])) {
		echo esc_html($bulk_messages['post'][$message]) . ' ';
	}
}

if (!empty($any_messages)) {
	echo '</p></div>';
}

unset( $messages );

if (!empty($_SERVER['REQUEST_URI'])) {
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'requested_deletion_count', 'request_cleared_count', 'deleted' ), esc_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))) );
}
?>

<?php $wp_list_table->views(); ?>

<form name="bulk-revisions" id="bulk-revisions" method="post" action="">

<?php $wp_list_table->search_box( 'Search', 'post' ); ?>

<input type="hidden" name="page" class="post_status_page" value="revisionary-q" />
<input type="hidden" name="post_status" class="post_status_page" 
value="<?php echo !empty($_REQUEST['post_status']) ? esc_attr(sanitize_key($_REQUEST['post_status'])) : 'all';  //phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" />

<?php if ( ! empty( $_REQUEST['show_sticky'] ) ) {	//phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<input type="hidden" name="show_sticky" value="1" />
<?php } ?>

<?php $wp_list_table->display(); ?>

</form>

<div id="ajax-response"></div>
<br class="clear" />

<?php
do_action('revisionary_admin_footer');
?>

</div>

<?php
