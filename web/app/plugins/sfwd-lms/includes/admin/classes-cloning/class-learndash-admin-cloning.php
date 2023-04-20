<?php
/**
 * LearnDash Admin Cloning.
 *
 * @since 4.2.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Cloning' ) ) {
	/**
	 * Class LearnDash Admin Cloning.
	 *
	 * @since 4.2.0
	 */
	abstract class Learndash_Admin_Cloning {
		/**
		 * Array of cloning classes instances.
		 *
		 * @since 4.2.0
		 *
		 * @var array
		 */
		private static $cloning_classes = array();

		/**
		 * The cloning scheduler instance.
		 *
		 * @since 4.2.0
		 *
		 * @var Learndash_Admin_Action_Scheduler
		 */
		private $cloning_scheduler;

		/**
		 * Clone the LearnDash Object.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $ld_object The LearnDash WP_Post.
		 * @param array   $args      The copy arguments.
		 *
		 * @return int|WP_Error The new object ID.
		 */
		abstract protected function clone( WP_Post $ld_object, array $args = array() );

		/**
		 * Init the class.
		 *
		 * @since 4.2.0
		 *
		 * @param Learndash_Admin_Action_Scheduler $cloning_scheduler The cloning scheduler instance.
		 *
		 * @return void
		 */
		public function init( Learndash_Admin_Action_Scheduler $cloning_scheduler ): void {
			$this->cloning_scheduler = $cloning_scheduler;

			// cloning option.
			add_filter( 'post_row_actions', array( $this, 'add_cloning_option' ), 10, 2 );
			add_action( 'admin_post_' . $this->get_cloning_action_name(), array( $this, 'action_cloning' ) );

			$this->cloning_scheduler->register_callback( $this->get_cloning_task_name(), array( $this, 'cloning_task' ), 10, 2 );
		}

		/**
		 * Get the LearnDash Object name for cloning.
		 *
		 * @since 4.2.0
		 *
		 * @return string The LearnDash Object name for cloning.
		 */
		abstract protected function get_cloning_object(): string;

		/**
		 * Get the cloning action name.
		 *
		 * @since 4.2.0
		 *
		 * @return string The cloning action name.
		 */
		private function get_cloning_action_name(): string {
			return 'learndash_cloning_action_' . $this->get_cloning_object();
		}

		/**
		 * Get the cloning task action name.
		 *
		 * @since 4.2.0
		 *
		 * @return string The cloning task action name.
		 */
		private function get_cloning_task_name(): string {
			return 'learndash_cloning_task_' . $this->get_cloning_object();
		}

		/**
		 * Get the cloning action row label.
		 *
		 * @since 4.2.0
		 *
		 * @return string The cloning action row label.
		 */
		protected function get_cloning_row_label(): string {
			return __( 'Clone', 'learndash' );
		}

		/**
		 * Decide whether the cloning should be run immediately (without action scheduler) or not.
		 *
		 * This function can be overridden by child classes to force the cloning to run immediately.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $ld_object The LearnDash WP_Post.
		 *
		 * @return bool True if the cloning should be run immediately.
		 */
		protected function run_clone_immediately( WP_Post $ld_object ): bool {
			return false;
		}

		/**
		 * Add the cloning option to the row actions.
		 *
		 * @since 4.2.0
		 *
		 * @param array   $actions listing actions.
		 * @param WP_Post $post    The post object.
		 *
		 * @return array
		 */
		public function add_cloning_option( array $actions, WP_Post $post ): array {
			if ( learndash_get_post_type_slug( $this->get_cloning_object() ) === $post->post_type ) {
				$action_url = add_query_arg(
					array(
						'action'    => $this->get_cloning_action_name(),
						'object_id' => $post->ID,
						'nonce'     => wp_create_nonce( $this->get_cloning_action_name() . $post->ID ),
					),
					admin_url( 'admin-post.php' )
				);

				$actions[ $this->get_cloning_action_name() ] = '<a href="' . $action_url . '">' . $this->get_cloning_row_label() . '</a>';
			}
			return $actions;
		}

		/**
		 * Processing the cloning action.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		public function action_cloning(): void {
			$redirect_url = admin_url( 'edit.php?post_type=' . learndash_get_post_type_slug( $this->get_cloning_object() ) );

			// request validation.
			if ( ! isset( $_GET['object_id'] ) || ! isset( $_GET['nonce'] ) ) {
				Learndash_Admin_Action_Scheduler::add_admin_notice( __( 'Invalid request.', 'learndash' ), 'error', 0, $redirect_url );
			}

			// nonce validation.
			$object_id = absint( sanitize_text_field( wp_unslash( $_GET['object_id'] ) ) );
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), $this->get_cloning_action_name() . $object_id ) ) {
				Learndash_Admin_Action_Scheduler::add_admin_notice( __( 'Request expired. Please try it again.', 'learndash' ), 'error', $object_id, $redirect_url );
			}

			// object validation.
			$object = ! empty( $object_id ) ? get_post( $object_id ) : false;
			if ( empty( $object ) || learndash_get_post_type_slug( $this->get_cloning_object() ) !== $object->post_type ) {
				// translators: placeholder: object name.
				Learndash_Admin_Action_Scheduler::add_admin_notice( sprintf( __( 'Invalid LearnDash %s.', 'learndash' ), $this->get_cloning_object() ), 'error', $object_id, $redirect_url );
			}

			// check if we should run the cloning now.
			if ( $this->run_clone_immediately( $object ) ) {
				$this->cloning_task( $object->ID, get_current_user_id() );
			} else {
				// enqueue the cloning task.
				Learndash_Admin_Action_Scheduler::add_admin_notice(
					sprintf(
					// translators: placeholder: current object link, new object name.
						__( 'The cloning of %1$s into %2$s is scheduled. Please refresh this page to see the progress.', 'learndash' ),
						'<a href="' . get_edit_post_link( $object ) . '">' . esc_html( $object->post_title ) . '</a>',
						'<b>' . esc_html( $this->get_default_copy_name( $object ) ) . '</b>'
					),
					'info',
					$object_id
				);
				$this->cloning_scheduler->enqueue_task(
					$this->get_cloning_task_name(),
					array(
						'object_id' => $object->ID,
						'user_id'   => get_current_user_id(),
					),
					$object->ID,
					sprintf(
					// translators: placeholder: current object link, new object name.
						__( 'The cloning of %1$s into %2$s is in the processing queue. Please refresh this page to see the progress.', 'learndash' ),
						'<a href="' . get_edit_post_link( $object ) . '">' . esc_html( $object->post_title ) . '</a>',
						'<b>' . esc_html( $this->get_default_copy_name( $object ) ) . '</b>'
					),
					sprintf(
					// translators: placeholder: current object link, new object name.
						__( 'The cloning of %1$s into %2$s is running. Please refresh this page to see the progress.', 'learndash' ),
						'<a href="' . get_edit_post_link( $object ) . '">' . esc_html( $object->post_title ) . '</a>',
						'<b>' . esc_html( $this->get_default_copy_name( $object ) ) . '</b>'
					)
				);
			}

			// redirect to listing page.
			learndash_safe_redirect( $redirect_url );
		}

		/**
		 * Cloning processing.
		 *
		 * @since 4.2.0
		 *
		 * @param int $object_id The LearnDash object ID.
		 * @param int $user_id   The user ID.
		 *
		 * @return void
		 */
		public function cloning_task( int $object_id, int $user_id = 0 ): void {
			$ld_post = ! empty( $object_id ) ? get_post( $object_id ) : null;

			// validate post.
			if ( empty( $ld_post ) || learndash_get_post_type_slug( $this->get_cloning_object() ) !== $ld_post->post_type ) {
				Learndash_Admin_Action_Scheduler::add_admin_notice(
					sprintf(
						// translators: placeholder: object name.
						__( 'The %1$s can not be cloned because it is not a valid LearnDash %2$s.', 'learndash' ),
						$this->get_cloning_object(),
						$this->get_cloning_object()
					),
					'error',
					$object_id
				);
				return;
			}

			// cloning processing.
			$new_object_id = $this->clone( $ld_post, array( 'user_id' => $user_id ) );

			// error handling.
			if ( is_wp_error( $new_object_id ) ) {
				Learndash_Admin_Action_Scheduler::add_admin_notice(
					$new_object_id->get_error_message(),
					'error',
					$object_id
				);
				return;
			}

			// add success notice.
			Learndash_Admin_Action_Scheduler::add_admin_notice(
				sprintf(
					// translators: placeholder: LearnDash object type, cloned object name, new object edit link.
					__( 'Your %1$s %2$s has been cloned successfully into %3$s.', 'learndash' ),
					$this->get_cloning_object(),
					'<b>' . $ld_post->post_title . '</b>',
					'<a href="' . get_edit_post_link( $new_object_id ) . '">' . esc_html( get_the_title( $new_object_id ) ) . '</a>'
				),
				'success',
				$object_id
			);
		}

		/**
		 * Get the default copy name for the new post.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $post The post object.
		 *
		 * @return string The copy name.
		 */
		protected function get_default_copy_name( WP_Post $post ): string {
			/**
			 * Filters the default copy name for the new cloned post.
			 *
			 * @since 4.2.0
			 *
			 * @param string  $copy_name The current default copy name.
			 * @param WP_Post $post      The post object.
			 *
			 * @return string The default name for the LearnDash cloned post.
			 */
			return apply_filters(
				'learndash_cloning_get_default_copy_name',
				sprintf(
				// translators: placeholder: LearnDash object name.
					__( 'Copy of %s', 'learndash' ),
					$post->post_title
				),
				$post
			);
		}

		/**
		 * Clone a post fully. This function will clone the post itself and all the post meta and taxonomies.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $post       The post to be cloned.
		 * @param array   $clone_args The cloning arguments.
		 *
		 * @return int The new post ID.
		 */
		protected function clone_post_fully( WP_Post $post, array $clone_args ): int {
			$defaults_clone_args = array(
				'copy_name' => $this->get_default_copy_name( $post ),
				'user_id'   => get_current_user_id(),
			);
			$clone_args          = wp_parse_args( $clone_args, $defaults_clone_args );

			$new_post_data = array(
				'post_title'     => $clone_args['copy_name'],
				'post_author'    => $clone_args['user_id'],
				'post_type'      => $post->post_type,
				'post_status'    => $post->post_status,
				'post_content'   => $post->post_content,
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order,
			);
			// future posts.
			if ( 'future' === $post->post_status ) {
				$new_post_data['post_date']     = $post->post_date;
				$new_post_data['post_date_gmt'] = $post->post_date_gmt;
			}

			/**
			 * Filters the default copy name for the new cloned post.
			 *
			 * @since 4.2.0
			 *
			 * @param array   $new_post_data The current new post data.
			 * @param WP_Post $post          The post object.
			 * @param array   $clone_args    The cloning arguments.
			 *
			 * @return array The post data for the new post.
			 */
			$new_post_data = apply_filters( 'learndash_cloning_get_cloned_post_data', $new_post_data, $post, $clone_args );

			$new_post_id = wp_insert_post( $new_post_data );

			// cloning featured image.
			$this->clone_featured_image( $post, $new_post_id );

			// cloning taxonomies.
			$this->clone_post_taxonomies( $post, $new_post_id );

			// cloning post metadata.
			$this->clone_post_meta( $post, $new_post_id );

			return $new_post_id;
		}

		/**
		 * Clone all post metadata.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $post        The post to be cloned.
		 * @param int     $new_post_id The new post ID.
		 *
		 * @return void
		 */
		protected function clone_post_meta( WP_Post $post, int $new_post_id ): void {
			$post_meta     = get_post_meta( $post->ID );
			$excluded_keys = $this->get_cloning_excluded_meta_keys( $post );

			if ( is_array( $post_meta ) ) {
				foreach ( $post_meta as $meta_key => $meta_value ) {
					if ( in_array( $meta_key, $excluded_keys, true ) ) {
						continue;
					}
					update_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value[0] ) );
				}
			}
		}

		/**
		 * Return a list of meta keys that should be excluded from cloning.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $post The post to be cloned.
		 *
		 * @return array The list of meta keys that should be excluded from cloning.
		 */
		protected function get_cloning_excluded_meta_keys( WP_Post $post ): array {
			$excluded_keys = array(
				'_wp_old_slug',
				'_wp_old_date',
				'_edit_lock',
				'_edit_last',
			);

			/**
			 * Filters the list of meta keys that should be excluded from cloning.
			 *
			 * @since 4.2.0
			 *
			 * @param array   $excluded_keys The current meta keys blacklist
			 * @param WP_Post $post          The post to be cloned.
			 *
			 * @return array The meta keys blacklist array.
			 */
			return apply_filters( 'learndash_cloning_excluded_meta_keys', $excluded_keys, $post );
		}

		/**
		 * Clone all post taxonomies.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $post        The post to be cloned.
		 * @param int     $new_post_id The new post ID.
		 *
		 * @return void
		 */
		protected function clone_post_taxonomies( WP_Post $post, int $new_post_id ): void {
			$taxonomies = get_object_taxonomies( $post->post_type );
			foreach ( $taxonomies as $taxonomy ) {
				$post_terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'slugs' ) );
				wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
			}
		}

		/**
		 * Clone the featured image.
		 *
		 * @param WP_Post $post The post to be cloned.
		 * @param integer $new_post_id The new post ID.
		 * @return void
		 */
		protected function clone_featured_image( WP_Post $post, int $new_post_id ): void {
			$featured_image_id = get_post_thumbnail_id( $post->ID );
			if ( ! empty( $featured_image_id ) ) {
				set_post_thumbnail( $new_post_id, $featured_image_id );
			}
		}

		/**
		 * Loads and inits cloning classes.
		 *
		 * @since 4.2.0
		 *
		 * @param Learndash_Admin_Action_Scheduler $cloning_scheduler The cloning scheduler.
		 *
		 * @return void
		 */
		public static function init_classes( Learndash_Admin_Action_Scheduler $cloning_scheduler ): void {
			$cloning_classes = array(
				array(
					'file_path'  => 'class-learndash-admin-course-cloning.php',
					'class_name' => Learndash_Admin_Course_Cloning::class,
				),
				array(
					'file_path'  => 'class-learndash-admin-lesson-cloning.php',
					'class_name' => Learndash_Admin_Lesson_Cloning::class,
				),
				array(
					'file_path'  => 'class-learndash-admin-topic-cloning.php',
					'class_name' => Learndash_Admin_Topic_Cloning::class,
				),
				array(
					'file_path'  => 'class-learndash-admin-quiz-cloning.php',
					'class_name' => Learndash_Admin_Quiz_Cloning::class,
				),
			);

			$folder_path = LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-cloning/';
			foreach ( $cloning_classes as $class ) {
				require_once $folder_path . $class['file_path'];
				self::$cloning_classes[ $class['class_name'] ] = new $class['class_name']();
			}

			/**
			 * Filters cloning classes.
			 *
			 * @since 4.2.0
			 *
			 * @param array $cloning_classes Cloning Classes.
			 */
			self::$cloning_classes = apply_filters( 'learndash_cloning_classes', self::$cloning_classes );

			self::$cloning_classes = array_filter(
				self::$cloning_classes,
				function( $cloning_class ) {
					return $cloning_class instanceof Learndash_Admin_Cloning;
				}
			);

			// init all cloning classes.
			foreach ( self::$cloning_classes as $class ) {
				$class->init( $cloning_scheduler );
			}
		}
	}
}
