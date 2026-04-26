<?php
/**
 * Course outline AI module.
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI;

use Exception;
use InvalidArgumentException;
use LDLMS_Course_Steps;
use LDLMS_Factory_Post;
use LDLMS_Post_Types;
use LearnDash\Core\Services\ChatGPT;
use SFWD_LMS;
use StellarWP\Learndash\lucatume\DI52\App;

/**
 * Course outline generator AI class.
 *
 * @since 4.6.0
 */
class Course_Outline {
	/**
	 * Page slug.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	public static $slug = 'learndash-ai-course-outline';

	/**
	 * AJAX actions.
	 *
	 * @since 4.6.0
	 *
	 * @var array<string, string>
	 */
	public static $ajax_actions = [
		'get_course' => 'ld_get_course',
	];

	/**
	 * Maximum number of allowed lessons.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	private static $max_lessons = 30;

	/**
	 * ChatGPT client.
	 *
	 * @since 4.6.0
	 *
	 * @var ChatGPT
	 */
	private $chatgpt;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param ChatGPT $chatgpt ChatGPT client.
	 */
	public function __construct( ChatGPT $chatgpt ) {
		$this->chatgpt = $chatgpt;
	}

	/**
	 * Add the course wizard button to the course list table.
	 *
	 * @since 4.23.1
	 *
	 * @param array<string,mixed> $buttons Array of header buttons.
	 *
	 * @return array<int|string,mixed> Modified array of header buttons.
	 */
	public function add_header_buttons( $buttons = [] ) {
		$screen = get_current_screen();

		if ( is_object( $screen ) && 'edit-' . learndash_get_post_type_slug( LDLMS_Post_Types::COURSE ) === $screen->id ) {
			$buttons[] = [
				'text' => wp_sprintf(
					// translators: Course label.
					__( 'Create %s Outline from AI', 'learndash' ),
					learndash_get_custom_label( 'course' )
				),
				'href' => esc_url( admin_url( 'admin.php?page=' . static::$slug ) ),
			];
		}

		return $buttons;
	}

	/**
	 * Add the course wizard button to the course list table.
	 *
	 * @since 4.6.0
	 * @deprecated 4.23.1
	 *
	 * @return void
	 */
	public function add_button() {
		_deprecated_function( __METHOD__, '4.23.1', 'Course_Outline::add_header_buttons' );
	}

	/**
	 * Register admin pages.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			'learndash-lms',
			wp_sprintf(
				// translators: Course label.
				__( 'Create %s Outline from AI', 'learndash' ),
				learndash_get_custom_label( 'course' )
			),
			'',
			LEARNDASH_ADMIN_CAPABILITY_CHECK,
			static::$slug,
			App::callback( $this, 'render' )
		);
	}

	/**
	 * Add admin styles.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function add_scripts(): void {
		?>
		<style>
			#adminmenu .toplevel_page_learndash-lms .wp-submenu a[href="admin.php?page=<?php echo esc_attr( static::$slug ); ?>"] {
				display: none;
			}
		</style>
		<?php
		$screen = get_current_screen();

		if ( is_object( $screen ) && 'learndash-lms_page_' . static::$slug === $screen->id ) {
			?>
				<style>
					.select2-container {
						border: 1px solid #8c8f94;
						border-radius: 4px;
					}

					.select2-container.select2-container--default {
						height: 30px;
					}

					.select2-container .select2-selection--single {
						height: 30px;
						line-height: 30px;
					}
				</style>
				<script>
					( function() {
						jQuery( function( $ ) {
							$( '.submenu-ldlms-courses' ).addClass( 'current' );

							$( 'select[name="course_id"]' ).select2({
								width: '100%',
								allowClear: true,
								placeholder: '',
								multiple: false,
								maximumSelectionSize: 1,
								minimumInputLength: 3,
								style: '30px',
								ajax: {
									url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
									method: 'GET',
									dataType: 'json',
									delay: 300,
									data: function ( params ) {
										return {
											action: '<?php echo esc_attr( static::$ajax_actions['get_course'] ); ?>',
											nonce: '<?php echo esc_attr( wp_create_nonce( static::$ajax_actions['get_course'] ) ); ?>',
											keyword: params.term
										};
									},
									processResults: function ( response, params ) {
										params.page = params.page || 1;

										return {
											results: response.data.items,
										};
									},
									cache: false
								}
							});
						} );
					} )();
				</script>
			<?php
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts(): void {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'learndash-lms_page_' . static::$slug === $screen->id ) {
			wp_enqueue_style( 'ld-tailwindcss' );
		}
	}

	/**
	 * Render page.
	 *
	 * @since 4.6.0
	 *
	 * @todo Change with the new admin view render after Breezy template update.
	 *
	 * @return void
	 */
	public function render(): void {
		SFWD_LMS::get_view(
			'AI/course-outline',
			[
				'api_key' => $this->chatgpt->get_api_key(),
			],
			true
		);
	}

	/**
	 * Execute ChatGPT command.
	 *
	 * @since 4.6.0
	 *
	 * @throws InvalidArgumentException Invalid argument value.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( static::$slug ) ) {
			wp_die( 'Unauthorized access.' );
		}

		if ( isset( $_POST['course_id'] ) && isset( $_POST['lesson_count'] ) && isset( $_POST['course_idea'] ) ) {
			try {
				$course_id    = sanitize_text_field( wp_unslash( $_POST['course_id'] ) );
				$lesson_count = absint( $_POST['lesson_count'] );
				$course_idea  = sanitize_textarea_field( wp_unslash( $_POST['course_idea'] ) );

				if ( $lesson_count > self::$max_lessons ) {
					throw new InvalidArgumentException(
						wp_sprintf(
							// translators: 1$: Maximum amount of lessons, 2$: Lessons label.
							__( 'Error: %1$s is maximum number of %2$s you can enter.', 'learndash' ),
							self::$max_lessons,
							learndash_get_custom_label_lower( 'lessons' )
						)
					);
				}

				$course       = json_decode( $course_id, true );
				$course_title = is_array( $course ) && ! empty( $course['title'] ) ? $course['title'] : get_the_title( absint( $course ) );
				$course_id    = ! is_array( $course ) || empty( $course['title'] ) ? absint( $course ) : 0;

				$command = "Create a numbered bullet list with {$lesson_count} lesson titles for a '{$course_title}' course on the topic of '{$course_idea}'.";

				$response = $this->chatgpt->send_command( $command );
			} catch ( Exception $e ) {
				learndash_safe_redirect(
					admin_url(
						add_query_arg(
							[
								'page'  => static::$slug,
								'error' => rawurlencode( $e->getMessage() ),
							],
							'admin.php'
						)
					)
				);

				exit;
			}

			$parsed_response = $this->parse(
				$response,
				array(
					'id'    => $course_id,
					'title' => $course_title,
				)
			);
			$process         = $this->process( $parsed_response );

			learndash_safe_redirect(
				add_query_arg(
					[
						'page'    => static::$slug,
						'success' => rawurlencode( $process ),
					],
					admin_url( 'admin.php' )
				)
			);

			exit;
		}
	}

	/**
	 * Parse ChatGPT response.
	 *
	 * @since 4.6.0
	 *
	 * @param string               $response_text Response text given by ChatGPT.
	 * @param array<string, mixed> $course_args   Course args.
	 * @return array<string, mixed>
	 */
	private function parse( string $response_text, array $course_args ): array {
		$lines = explode( PHP_EOL, trim( $response_text ) );

		$lesson_titles = [];
		foreach ( $lines as $line ) {
			if ( preg_match( '/^\d+\./', $line ) ) {
				$lesson_title = trim( substr( $line, strpos( $line, '.' ) + 1 ) );
				if ( ! empty( $lesson_title ) ) {
					$lesson_titles[] = $lesson_title;
				}
			}
		}

		return [
			'course_title'  => $course_args['title'],
			'course_id'     => $course_args['id'],
			'lesson_count'  => count( $lesson_titles ),
			'lesson_titles' => $lesson_titles,
		];
	}

	/**
	 * Process parsed response.
	 *
	 * @since 4.6.0
	 *
	 * @param array<string, mixed> $parsed_response Response text after being parsed.
	 * @return string
	 */
	private function process( array $parsed_response ): string {
		$course_id = $parsed_response['course_id'];
		/**
		 * Course title.
		 *
		 * @var string
		 */
		$course_title  = $parsed_response['course_title'];
		$lesson_titles = is_array( $parsed_response['lesson_titles'] ) ? $parsed_response['lesson_titles'] : [];

		if ( empty( $course_id ) ) {
			$course_id = wp_insert_post(
				[
					'post_title'  => $course_title,
					'post_type'   => learndash_get_post_type_slug( LDLMS_Post_Types::COURSE ),
					'post_status' => 'publish',
				]
			);
		}

		$course_id    = absint( $course_id );
		$lesson_count = count( $lesson_titles );
		for ( $i = 0; $i < $lesson_count; $i++ ) {
			$lesson_title = $lesson_titles[ $i ];
			$lesson_id    = wp_insert_post(
				[
					'post_title'  => $lesson_title,
					'post_type'   => 'sfwd-lessons',
					'post_status' => 'publish',
				]
			);

			learndash_update_setting( $lesson_id, 'course', $course_id );

			learndash_course_add_child_to_parent( $course_id, $lesson_id, $course_id );
		}

		$message = wp_sprintf(
			// translators: 1$: lessons count, 2$: lesson label, 3$: course label, 4$: clickable course title.
			__( '%1$d %2$s have been created successfully for the %3$s: %4$s.', 'learndash' ),
			$lesson_count,
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			_n(
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle
				learndash_get_custom_label( 'lesson' ),
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural
				learndash_get_custom_label( 'lessons' ),
				$lesson_count
			),
			learndash_get_custom_label_lower( 'course' ),
			'<a href="' . get_edit_post_link( $course_id ) . '">' . get_the_title( $course_id ) . '</a>'
		);

		return $message;
	}

	/**
	 * AJAX request handler.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function handle_ajax_request(): void {
		if ( ! current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 401 );
		}

		// phpcs:ignore
		if ( ! isset( $_GET['nonce'] ) || ! isset( $_GET['action'] ) || ! wp_verify_nonce( $_GET['nonce'], $_GET['action'] ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 401 );
		}

		switch ( $_GET['action'] ) {
			case static::$ajax_actions['get_course']:
				$keyword = isset( $_GET['keyword'] ) ? sanitize_text_field( wp_unslash( $_GET['keyword'] ) ) : '';

				$posts = get_posts(
					[
						'post_type'   => learndash_get_post_type_slug( LDLMS_Post_Types::COURSE ),
						'post_status' => 'any',
						's'           => $keyword,
					]
				);

				$posts = array_map(
					function( $post ) {
						return [
							'id'   => $post->ID,
							'text' => $post->post_title . ' (ID: ' . $post->ID . ')',
						];
					},
					$posts
				);

				$items = array_merge(
					[
						[
							'id'   => wp_json_encode(
								array(
									'new'   => md5( microtime() ),
									'title' => $keyword,
								)
							),
							'text' => wp_sprintf(
								// translators: 1$: keyword, 2$: course label.
								esc_html__( '%1$s (New %2$s)', 'learndash' ),
								$keyword,
								learndash_get_custom_label( 'course' )
							),
						],
					],
					$posts
				);

				$response = [
					'items' => $items,
				];
				break;

			default:
				$response = [];
				break;
		}

		wp_send_json_success( $response );
	}
}
