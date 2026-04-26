<?php
/**
 * @package LearnDash
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Enqueue JS and CSS.
require plugin_dir_path( __FILE__ ) . 'lib/enqueue-scripts.php';
require plugin_dir_path( __FILE__ ) . 'lib/class-learndash-propanel-gutenberg-block.php';

// Dynamic Blocks.
require plugin_dir_path( __FILE__ ) . 'blocks/ld-propanel-filters/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-propanel-reporting/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-propanel-link/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-propanel-activity/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-propanel-progress-chart/index.php';
require plugin_dir_path( __FILE__ ) . 'blocks/ld-propanel-overview/index.php';
