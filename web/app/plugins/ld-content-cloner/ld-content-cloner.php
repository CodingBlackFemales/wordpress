<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wisdmlabs.com
 * @since             1.0.0
 * @package           Ld_Content_Cloner
 *
 * @wordpress-plugin
 * Plugin Name:       LearnDash Content Cloner
 * Plugin URI:        https://wisdmlabs.com
 * Description:       This plugin clones LearnDash course content - the course along with the associated lessons and topics - for easy content creation.
 * Version:           1.3.1
 * Author:            WisdmLabs
 * Author URI:        https://wisdmlabs.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ld-content-cloner
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'EDD_LDCC_ITEM_NAME' ) ) {
	define( 'EDD_LDCC_ITEM_NAME', 'LearnDash Content Cloner' );
}

if ( ! defined( 'LDCC_VERSION' ) ) {
	define( 'LDCC_VERSION', '1.3.1' );
}

if ( ! defined( 'EDD_LDCC_STORE_URL' ) ) {
	define( 'EDD_LDCC_STORE_URL', 'https://wisdmlabs.com/license-check/' );
}
global $ldcc_plugin_data;

add_action( 'plugins_loaded', 'ldcc_load_license', 11 );

add_action( 'plugins_loaded', 'ldcc_initialize' );

/**
 * This function is used to load the licensing module for the plugin.
 */
function ldcc_load_license() {
	global $ldcc_plugin_data;
	$ldcc_plugin_data = include_once 'license.config.php';
	require_once 'licensing/class-wdm-license.php';
	new \Licensing\WdmLicense( $ldcc_plugin_data );
}

/**
 * This function is used to check LearnDash dependency and initialize plugin if dependency is present.
 */
function ldcc_initialize() {
	// check if learndash is active.
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	$is_ld_active = is_plugin_active( 'sfwd-lms/sfwd_lms.php' );

	// check dependency activation.
	if ( ! $is_ld_active ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		unset( $_GET['activate'] );//phpcs:ignore.
		add_action( 'admin_notices', 'ldcc_activation_deps_check_notices' );
	} else {
		run_ld_content_cloner();
	}
}

/**
 * This function shows the error notice if LearnDash LMS plugin is not active.
 */
function ldcc_activation_deps_check_notices() {
	echo "<div class='error'>
			<p>LearnDash LMS plugin is not active. In order to make <strong>LearnDash Content Cloner</strong> plugin work, you need to install and activate LearnDash LMS first.</p>
		</div>";
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ld-content-cloner-activator.php
 */
function activate_ld_content_cloner() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ldcc-activator.php';
	\LDCC_Activator\LDCC_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ld-content-cloner-deactivator.php
 */
function deactivate_ld_content_cloner() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ldcc-deactivator.php';
	LDCC_Deactivator\LDCC_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ld_content_cloner' );
register_deactivation_hook( __FILE__, 'deactivate_ld_content_cloner' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ld-content-cloner.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ld_content_cloner() {
	$plugin = new LdContentCloner\LdContentCloner();
	$plugin->run();
}

/**
 * This function is used to delete Quizzes from WP Pro Quiz DB tables.
 *
 * @param  integer $quiz_id Quiz ID.
 * @param  boolean $is_post Check if Post.
 * @internal
 */
function ldcc_delete_quiz_from_pro_tables( $quiz_id, $is_post = true ) {
	$ld_quiz_data_old = get_post_meta( $quiz_id, '_sfwd-quiz', true );
	$pro_quiz_id      = $ld_quiz_data_old['sfwd-quiz_quiz_pro'];
	if ( empty( $pro_quiz_id ) ) {
		$pro_quiz_id = get_post_meta( $quiz_id, 'quiz_pro_id', true );
	}
	$quiz_mapper     = new \WpProQuiz_Model_QuizMapper();
	$quiz            = $quiz_mapper->fetch( $pro_quiz_id );
	$question_mapper = new \WpProQuiz_Model_QuestionMapper();
	if ( strpos( $quiz->getName(), ' Copy' ) !== false ) {
		$question_mapper->deleteByQuizId( $pro_quiz_id );
		$quiz_mapper->delete( $pro_quiz_id );
	} else {
		$questions      = $question_mapper->fetchAll( $pro_quiz_id );
		$question_array = array();
		foreach ( $questions as $qu ) {
			$question_array[] = $qu->getId();
		}

		if ( function_exists( 'learndash_get_quiz_questions' ) ) {
			$question_post_ids = learndash_get_quiz_questions( $quiz_id );
			$question_pro_ids  = array_map(
				function( $question_id ) {
					$pro_question_id = get_post_meta( $question_id, 'question_pro_id', true );
					if ( empty( $pro_question_id ) ) {
						$ld_question_data = get_post_meta( $question_id, '_sfwd-question', true );
						$pro_question_id  = $ld_question_data['sfwd-question_quiz'];
					}
					return $pro_question_id;
				},
				$question_post_ids
			);
			$question_array    = array_unique(
				array_merge(
					$question_array,
					$question_pro_ids
				)
			);
		}
		foreach ( $question_array as $question ) {
			if ( strpos( $question->getTitle(), ' Copy' ) !== false ) {
				$question_mapper->delete( $question->getId() );
			}
		}
	}
}

/**
 * This function is used to delete Questions from Pro Quiz DB Tables.
 *
 * @param  integer $question_id Question ID.
 * @param  boolean $is_post     Check if Post.
 * @internal
 */
function delete_question_from_pro_tables( $question_id, $is_post = true ) {
	$pro_question_id = get_post_meta( $question_id, 'question_pro_id', true );
	if ( empty( $pro_question_id ) ) {
		$ld_question_data = get_post_meta( $question_id, '_sfwd-question', true );
		$pro_question_id  = $ld_question_data['sfwd-question_quiz'];
	}
	$question_mapper = new \WpProQuiz_Model_QuestionMapper();
	$question        = $question_mapper->fetch( $pro_question_id );
	if ( strpos( $question->getTitle(), ' Copy' ) !== false ) {
		$question_mapper->delete( $pro_question_id );
	}
}

/**
 * This method is used to delete cloned content from the website.
 * @internal
 */
function delete_copy_posts() {
	if ( ( ! isset( $_GET['delete_posts'] ) || 'wisdmdelete' !== $_GET['delete_posts'] ) || ! isset( $_GET['post_type'] ) ) {// phpcs:ignore
		return;
	}
	if ( ! post_type_exists( $_GET['post_type'] ) ) {// phpcs:ignore
		return;
	}
	@ini_set( 'max_execution_time', '300' );// phpcs:ignore
	$args  = array(
		'post_type'      => $_GET['post_type'],// phpcs:ignore
		'posts_per_page' => -1,
		'post_status'    => 'any',
	);
	$posts = get_posts( $args );
	if ( ! empty( $posts ) ) {
		foreach ( $posts as $post ) {
			if ( strpos( $post->post_title, ' Copy' ) !== false ) {
				if ( 'sfwd-quiz' === $post->post_type ) {
					ldcc_delete_quiz_from_pro_tables( $post->ID );
				}
				if ( 'sfwd-question' === $post->post_type ) {
					delete_question_from_pro_tables( $post->ID );
				}
				wp_delete_post( $post->ID, true );
			}
		}
	}
}

/**
 * This function is used to delete Pro Quiz entries from the database if the posts are missing.
 * @internal
 */
function delete_only_pro_entries() {
	if ( ! isset( $_GET['delete_pro'] ) || 'wisdmdelete' !== $_GET['delete_pro'] ) {// phpcs:ignore
		return;
	}
	@ini_set( 'max_execution_time', '300' );// phpcs:ignore
	$quiz_mapper     = new \WpProQuiz_Model_QuizMapper();
	$question_mapper = new \WpProQuiz_Model_QuestionMapper();
	$quizzes         = $quiz_mapper->fetchAll();
	if ( ! empty( $quizzes ) ) {
		foreach ( $quizzes as $quiz ) {
			if ( strpos( $quiz->getName(), ' Copy' ) !== false ) {
				$question_mapper->deleteByQuizId( $quiz->getId() );
				$quiz_mapper->delete( $quiz->getId() );
			}
		}
	}
	global $wpdb;
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {\LDLMS_DB::get_table_name( 'quiz_question' )} WHERE online = %d",
			1
		),
		ARRAY_A
	);
	if ( ! empty( $results ) ) {
		foreach ( $results as $row ) {
			$model = new WpProQuiz_Model_Question( $row );
			if ( strpos( $model->getTitle(), ' Copy' ) !== false ) {
				$question_mapper->delete( $model->getId() );
			}
		}
	}
}

add_action( 'wp_loaded', 'delete_copy_posts' );
add_action( 'wp_loaded', 'delete_only_pro_entries' );
