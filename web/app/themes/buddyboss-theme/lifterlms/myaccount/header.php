<?php
/**
 * Student Dashboard Header
 *
 * @package LifterLMS/Templates
 *
 * @since    3.14.0
 * @version  3.14.0
 */

defined( 'ABSPATH' ) || exit;

?>
<header class="llms-sd-header">

	<?php
	/**
	 * @hooked lifterlms_template_my_account_navigation - 10
	 * @hooked lifterlms_template_student_dashboard_title - 20
	 */
	do_action( 'lifterlms_student_dashboard_header' );
	?>

</header>

<?php
/**
 * .llms-student-dashboard__frame close tag hooked bb_lifterlms_template_student_dashboard_wrapper_close - 12
 */
?>
<div class="llms-student-dashboard__frame">
