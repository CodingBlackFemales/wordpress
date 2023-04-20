<?php
/**
 * Gutenberg loader
 *
 * @since 2.5.8
 * @package LearnDash
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Enqueue JS and CSS.
require plugin_dir_path( __FILE__ ) . 'lib/enqueue-scripts.php';
require plugin_dir_path( __FILE__ ) . 'lib/class-ld-rest-gutenberg-posts-controller.php';
require plugin_dir_path( __FILE__ ) . 'lib/class-learndash-gutenberg-block.php';

// Dynamic Blocks.
require plugin_dir_path( __FILE__ ) . 'blocks/ld-login/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-profile/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-course-list/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-lesson-list/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-topic-list/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-quiz-list/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-course-progress/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-visitor/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-student/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-course-complete/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-course-inprogress/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-course-notstarted/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-course-resume/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-course-info/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-user-course-points/index.php';

require plugin_dir_path( __FILE__ ) . 'blocks/ld-group-list/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-user-groups/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-group/index.php';

require plugin_dir_path( __FILE__ ) . 'blocks/ld-payment-buttons/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-course-content/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-course-expire-status/index.php';

require plugin_dir_path( __FILE__ ) . 'blocks/ld-certificate/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-quiz-complete/index.php';

require plugin_dir_path( __FILE__ ) . 'blocks/ld-courseinfo/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-quizinfo/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-groupinfo/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-usermeta/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-registration/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-infobar/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-materials/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-user-status/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-navigation/index.php';

require plugin_dir_path( __FILE__ ) . 'blocks/ld-exam/index.php';

require plugin_dir_path( __FILE__ ) . 'blocks/ld-reset-password/index.php';
