<?php
/**
 * LearnDash LD30 Displays an informational bar in group
 *
 * Is contextulaized by passing in a $context variable that indicates post type
 *
 * @var string $context      Context used for display. 'group'.
 * @var int    $group_id     Group ID.
 * @var int    $user_id      User ID.
 * @var bool   $has_access   User has access to group or is enrolled.
 * @var bool   $group_status User's Group Status. Completed, No Started, or In Complete.
 * @var object $post         Group Post Object.
 *
 * @since 3.1.7
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** This filter is documented in themes/ld30/templates/modules/infobar.php */
do_action( 'learndash-infobar-before', get_post_type( $group_id ), $group_id, $user_id );

/** This filter is documented in themes/ld30/templates/modules/infobar.php */
do_action( 'learndash-' . $context . '-infobar-before', $group_id, $user_id );

/** This filter is documented in themes/ld30/templates/modules/infobar.php */
do_action( 'learndash-infobar-inside-before', get_post_type( $group_id ), $group_id, $user_id );

/** This filter is documented in themes/ld30/templates/modules/infobar.php */
do_action( 'learndash-' . $context . '-infobar-inside-before', $group_id, $user_id );

learndash_get_template_part(
	'modules/infobar/group.php',
	array(
		'has_access'   => $has_access,
		'user_id'      => $user_id,
		'group_id'     => $group_id,
		'group_status' => $group_status,
		'post'         => $post,
	),
	true
);

/** This filter is documented in themes/ld30/templates/modules/infobar.php */
do_action( 'learndash-infobar-inside-after', get_post_type( $group_id ), $group_id, $user_id );

/** This filter is documented in themes/ld30/templates/modules/infobar.php */
do_action( 'learndash-' . $context . '-infobar-inside-after', $group_id, $user_id );

/** This filter is documented in themes/ld30/templates/modules/infobar.php */
do_action( 'learndash-infobar-after', get_post_type( $group_id ), $group_id, $user_id );

/** This filter is documented in themes/ld30/templates/modules/infobar.php */
do_action( 'learndash-' . $context . '-infobar-after', $group_id, $user_id );
