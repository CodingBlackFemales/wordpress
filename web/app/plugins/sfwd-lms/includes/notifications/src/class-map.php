<?php
/**
 * File for autoloading classes.
 *
 * @package LearnDash\Notifications
 */

$base_dir = dirname( __DIR__ );

$classes = [
	'LearnDash_Notification\\Notification'                 => $base_dir . '/src/notification.php',
	'LearnDash_Notification\\Trigger'                      => $base_dir . '/src/trigger.php',
	'LearnDash_Notification\\Trigger\\After_Course_Expire' => $base_dir . '/src/trigger/after-course-expire.php',
	'LearnDash_Notification\\Trigger\\Assignment_Approved' => $base_dir . '/src/trigger/assignment-approved.php',
	'LearnDash_Notification\\Trigger\\Assignment_Uploaded' => $base_dir . '/src/trigger/assignment_uploaded.php',
	'LearnDash_Notification\\Trigger\\Before_Course_Expire' => $base_dir . '/src/trigger/before-course-expire.php',
	'LearnDash_Notification\\Trigger\\Complete_Course'     => $base_dir . '/src/trigger/complete-course.php',
	'LearnDash_Notification\\Trigger\\Complete_Lesson'     => $base_dir . '/src/trigger/complete-lesson.php',
	'LearnDash_Notification\\Trigger\\Complete_Topic'      => $base_dir . '/src/trigger/complete-topic.php',
	'LearnDash_Notification\\Trigger\\Drip_Lesson_Available' => $base_dir . '/src/trigger/drip-lesson-available.php',
	'LearnDash_Notification\\Trigger\\Enroll_Course'       => $base_dir . '/src/trigger/enroll-course.php',
	'LearnDash_Notification\\Trigger\\Enroll_Group'        => $base_dir . '/src/trigger/enroll-group.php',
	'LearnDash_Notification\\Trigger\\Essay_Graded'        => $base_dir . '/src/trigger/essay_graded.php',
	'LearnDash_Notification\\Trigger\\Essay_Submitted'     => $base_dir . '/src/trigger/essay-submitted.php',
	'LearnDash_Notification\\Trigger\\Quiz_Passed'         => $base_dir . '/src/trigger/quiz-passed.php',
	'LearnDash_Notification\\Trigger\\Quiz_Completed'      => $base_dir . '/src/trigger/quiz-completed.php',
	'LearnDash_Notification\\Trigger\\Quiz_Failed'         => $base_dir . '/src/trigger/quiz-failed.php',
	'LearnDash_Notification\\Trigger\\Quiz_Submitted'      => $base_dir . '/src/trigger/quiz-submitted.php',
	'LearnDash_Notification\\Trigger\\User_Login_Track'    => $base_dir . '/src/trigger/user-login-track.php',
];

spl_autoload_register(
	function ( $class ) use ( $classes ) {
		if ( isset( $classes[ $class ] ) ) {
			require_once $classes[ $class ];
		}
	}
);
