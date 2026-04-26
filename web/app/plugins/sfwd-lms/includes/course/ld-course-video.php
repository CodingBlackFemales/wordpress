<?php
/**
 * Handles Video Progression logic and setup.
 *
 * @since   2.4.0

 * @package LearnDash\Video_Progression
 */

use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Utilities\Str;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Course_Video' ) ) {
	/**
	 * Class for handling the LearnDash Video Progression.
	 *
	 * @since 2.4.0
	 */
	class Learndash_Course_Video {
		/**
		 * Static instance of class.
		 *
		 * @var self $instance
		 */
		private static $instance;

		/**
		 * Array of video progress data options and default values.
		 *
		 * @var array<string, bool|int|string> $video_data
		 */
		private $video_data = [
			'videos_found_provider'              => false,
			'videos_found_type'                  => false,
			'videos_auto_start'                  => false,
			'videos_show_controls'               => false,
			'videos_auto_complete'               => true,
			'videos_auto_complete_delay'         => 0,
			'videos_auto_complete_delay_message' => '',
			'videos_hide_complete_button'        => false,
			'videos_shown'                       => false,
			'video_debug'                        => false,
			'video_admin_bypass'                 => false,
			'video_cookie_key'                   => false,
			'video_focus_pause'                  => false,
			'video_track_time'                   => false,
			'video_track_expires'                => 30,
			// Cookie Expire Days the cookie expires. Can be partial 0.5, 1.25, etc.
			'video_track_domain'                 => '',
			// Cookie Domain. Default set to WP COOKIE_DOMAIN.
			'video_track_path'                   => '',
			// Cookie Path. Default set to COOKIEPATH or if Multisite SITECOOKIEPATH.
			'course_id'                          => 0,
			'step_id'                            => 0,
		];

		/**
		 * User ID.
		 *
		 * @var int $user_id
		 */
		private $user_id;

		/**
		 * Course ID.
		 *
		 * @var int $course_id
		 */
		private $course_id;

		/**
		 * Course Step ID.
		 *
		 * @var int $step_id
		 */
		private $step_id;

		/**
		 * Course Step Settings array.
		 *
		 * @var array<mixed> $step_settings
		 */
		private $step_settings = [];

		/**
		 * Variable to contain the final rendered video HTML element.
		 *
		 * @var string $video_content
		 */
		private $video_content = '';

		/**
		 * LearnDash Vide Progress constructor.
		 *
		 * @since 2.4.0
		 */
		public function __construct() {
			add_action( 'wp_footer', [ $this, 'action_wp_footer' ], 1 );
		}

		/**
		 * Get instance.
		 *
		 * @since 2.4.0
		 *
		 * @return self
		 */
		final public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Hook into the LearnDash template logic to insert the Video Progression output
		 *
		 * @since 2.4.3
		 *
		 * @param string $content  HTML content to be output to browser.
		 * @param Object $post     WP_Post instance for Lesson or Topic.
		 * @param array  $settings Current setting values for Post.
		 *
		 * @return string Content with Video Progression HTML.
		 */
		public function add_video_to_content( $content, $post, $settings = [] ) {
			$this->user_id       = get_current_user_id();
			$this->step_id       = (int) $post->ID;
			$this->course_id     = (int) learndash_get_course_id( $post->ID );
			$this->step_settings = $settings;

			$this->video_data['step_id']   = $this->step_id;
			$this->video_data['course_id'] = $this->course_id;

			$this->video_data['video_cookie_key'] = $this->build_video_cookie_key();

			// Do we show the video. In some cases we do. But in others like when the setting is to show AFTER completing other steps then we set to false.
			$show_video = false;

			// In the initial flow we do apply the video restriction logic. But then in other if the user is an admin or the student has completed the lesson
			// we don't apply the video logic.
			$logic_video = false;

			if ( ( isset( $this->step_settings['lesson_video_enabled'] ) ) && ( 'on' === $this->step_settings['lesson_video_enabled'] ) ) {
				if ( ( isset( $this->step_settings['lesson_video_url'] ) ) && ( ! empty( $this->step_settings['lesson_video_url'] ) ) ) {
					// Because some copy/paste can result in leading whitespace. LEARNDASH-3819.
					$this->step_settings['lesson_video_url'] = trim( $this->step_settings['lesson_video_url'] );
					$this->step_settings['lesson_video_url'] = html_entity_decode( $this->step_settings['lesson_video_url'] );

					// Just to ensure the proper settings are available.
					if ( ( ! isset( $this->step_settings['lesson_video_shown'] ) ) || ( empty( $this->step_settings['lesson_video_shown'] ) ) ) {
						$this->step_settings['lesson_video_shown'] = 'BEFORE';
					}

					if ( ( isset( $this->step_settings['lesson_video_focus_pause'] ) ) && ( 'on' === $this->step_settings['lesson_video_focus_pause'] ) ) {
						$this->video_data['video_focus_pause'] = true;
					}
					if ( ( isset( $this->step_settings['lesson_video_track_time'] ) ) && ( 'on' === $this->step_settings['lesson_video_track_time'] ) ) {
						$this->video_data['video_track_time'] = true;
					}

					$bypass_course_limits_admin_users = learndash_can_user_bypass( $this->user_id, 'learndash_video_progression', $post->ID, $post );

					// For logged in users to allow an override filter.
					/** This filter is documented in includes/course/ld-course-progress.php */
					$bypass_course_limits_admin_users = apply_filters(
						'learndash_prerequities_bypass', // cspell:disable-line -- prerequities are prerequisites...
						$bypass_course_limits_admin_users,
						$this->user_id,
						$post->ID,
						$post
					);

					$this->video_data['video_admin_bypass'] = $bypass_course_limits_admin_users;

					if ( ! $bypass_course_limits_admin_users ) {

						if ( 'sfwd-lessons' === $post->post_type ) {
							$progress = learndash_get_course_progress( $this->user_id, $post->ID );

							if ( ( ! empty( $progress['this'] ) ) && ( $progress['this'] instanceof WP_Post ) && ( true === (bool) $progress['this']->completed ) ) {
								// The student has completes this step so we show the video but don't apply the logic.
								$show_video  = true;
								$logic_video = false;
							} else {
								if ( 'BEFORE' === $this->step_settings['lesson_video_shown'] ) {
									$show_video           = true;
									$logic_video          = true;
									$complete_child_steps = learndash_user_progression_get_complete_child_steps( $this->user_id, $this->course_id, $post->ID );
									if ( ! empty( $complete_child_steps ) ) {
										$logic_video = false;
									}
								} elseif ( 'AFTER' === $this->step_settings['lesson_video_shown'] ) {
									// If we have any incomplete child steps. Abort.
									$incomplete_child_steps = learndash_user_progression_get_incomplete_child_steps( $this->user_id, $this->course_id, $post->ID );
									if ( empty( $incomplete_child_steps ) ) {
										$show_video  = true;
										$logic_video = true;
									} else {
										$show_video  = false;
										$logic_video = false;
									}
								}
							}
						} elseif ( 'sfwd-topic' === $post->post_type ) {
							$progress = learndash_get_course_progress( $this->user_id, $post->ID );

							if ( ( ! empty( $progress['this'] ) ) && ( $progress['this'] instanceof WP_Post ) && ( true === (bool) $progress['this']->completed ) ) {
								// The student has completes this step so we show the video but don't apply the logic.
								$show_video  = true;
								$logic_video = false;
							} else {
								if ( 'BEFORE' === $this->step_settings['lesson_video_shown'] ) {
									$show_video  = true;
									$logic_video = true;

									$complete_child_steps = learndash_user_progression_get_complete_child_steps( $this->user_id, $this->course_id, $post->ID );
									if ( ! empty( $complete_child_steps ) ) {
										$logic_video = false;
									}
								} elseif ( 'AFTER' === $this->step_settings['lesson_video_shown'] ) {
									// If we have any incomplete child steps. Abort.
									$incomplete_child_steps = learndash_user_progression_get_incomplete_child_steps( $this->user_id, $this->course_id, $post->ID );
									if ( empty( $incomplete_child_steps ) ) {
										$show_video  = true;
										$logic_video = true;
									} else {
										$show_video  = false;
										$logic_video = false;
									}
								} else {
									$show_video  = false;
									$logic_video = false;
								}
							}
						}
					} else {
						$show_video  = true;
						$logic_video = false;
					}

					if ( ( true === $logic_video ) && ( $this->is_video_cookie_complete( $this->video_data['video_cookie_key'] ) ) ) {
						$logic_video = false;
					}

					if ( true === $show_video ) {
						$this->video_data['videos_shown'] = $this->step_settings['lesson_video_shown'];

						if ( ( strpos( $this->step_settings['lesson_video_url'], 'youtu.be' ) !== false ) || ( strpos( $this->step_settings['lesson_video_url'], 'youtube.com' ) !== false ) ) {
							$this->video_data['videos_found_provider'] = 'youtube';
						} elseif ( strpos( $this->step_settings['lesson_video_url'], 'vimeo.com' ) !== false ) {
							$this->video_data['videos_found_provider'] = 'vimeo';
						} elseif ( ( strpos( $this->step_settings['lesson_video_url'], 'wistia.com' ) !== false ) || ( strpos( $this->step_settings['lesson_video_url'], 'wistia.net' ) !== false ) ) {
							$this->video_data['videos_found_provider'] = 'wistia';
						} elseif ( strpos( $this->step_settings['lesson_video_url'], 'amazonaws.com' ) !== false ) {
							$this->video_data['videos_found_provider'] = 'local';
						} elseif ( ( strpos( $this->step_settings['lesson_video_url'], 'vooplayer' ) !== false ) || ( strpos( $this->step_settings['lesson_video_url'], 'spotlightr.com' ) !== false ) ) {
							$this->video_data['videos_found_provider'] = 'vooplayer';
						} elseif ( strpos( $this->step_settings['lesson_video_url'], trailingslashit( get_home_url() ) ) !== false ) {
							$this->video_data['videos_found_provider'] = 'local';
						} elseif ( strpos( $this->step_settings['lesson_video_url'], 'b-cdn.net' ) !== false ) {
							$this->video_data['videos_found_provider'] = 'bunny';
						}

						if ( empty( $this->video_data['videos_found_provider'] ) ) {
							$home_url_domain  = wp_parse_url( get_home_url(), PHP_URL_HOST );
							$video_url_domain = wp_parse_url( $this->step_settings['lesson_video_url'], PHP_URL_HOST );

							if ( strtolower( $home_url_domain ) === strtolower( $video_url_domain ) ) {
								$this->video_data['videos_found_provider'] = 'local';
							}
						}

						/**
						 * Filter to override unknown video provider.
						 *
						 * @since 2.4.0
						 *
						 * @param string $video_provider Video provider to use. May be empty.
						 * @param array  $settings       Array of Video Progression Settings.
						 */
						$this->video_data['videos_found_provider'] = apply_filters( 'ld_video_provider', $this->video_data['videos_found_provider'], $this->step_settings );
						if ( empty( $this->video_data['videos_found_provider'] ) ) {
							return $content;
						}

						if ( ( substr( $this->step_settings['lesson_video_url'], 0, strlen( 'http://' ) ) == 'http://' ) || ( substr( $this->step_settings['lesson_video_url'], 0, strlen( 'https://' ) ) == 'https://' ) ) {
							if ( 'local' === $this->video_data['videos_found_provider'] ) {
								$this->video_data['videos_found_type']   = 'video_shortcode';
								$this->step_settings['lesson_video_url'] = '[video src="' . $this->step_settings['lesson_video_url'] . '"][/video]';
							} elseif (
								'youtube' === $this->video_data['videos_found_provider']
								|| 'vimeo' === $this->video_data['videos_found_provider']
								|| 'wistia' === $this->video_data['videos_found_provider']
								|| 'bunny' === $this->video_data['videos_found_provider']
							) {
								$this->video_data['videos_found_type']   = 'embed_shortcode';
								$this->step_settings['lesson_video_url'] = '[embed]' . $this->step_settings['lesson_video_url'] . '[/embed]';
							}
						} elseif ( substr( $this->step_settings['lesson_video_url'], 0, strlen( '[embed' ) ) == '[embed' ) {
							$this->video_data['videos_found_type'] = 'embed_shortcode';
						} elseif ( substr( $this->step_settings['lesson_video_url'], 0, strlen( '[video' ) ) == '[video' ) {
							$this->video_data['videos_found_type'] = 'video_shortcode';
						} elseif ( strpos( $this->step_settings['lesson_video_url'], '<iframe' ) !== false ) {
							$this->video_data['videos_found_type'] = 'iframe';
						} else {
							if ( 'vooplayer' === $this->video_data['videos_found_provider'] ) {
								if ( substr( $this->step_settings['lesson_video_url'], 0, strlen( '[vooplayer' ) ) == '[vooplayer' ) {
									$this->video_data['videos_found_type'] = 'vooplayer_shortcode';
								} else {
									$this->video_data['videos_found_type'] = 'iframe';
								}
							}
						}

						if ( ( false !== $this->video_data['videos_found_provider'] ) && ( false !== $this->video_data['videos_found_type'] ) ) {
							if ( 'local' === $this->video_data['videos_found_provider'] ) {
								if ( 'embed_shortcode' === $this->video_data['videos_found_type'] ) {
									global $wp_embed;
									$video_content       = $wp_embed->run_shortcode( $this->step_settings['lesson_video_url'] );
									$this->video_content = do_shortcode( $video_content );

								} elseif ( 'video_shortcode' === $this->video_data['videos_found_type'] ) {
									$this->video_content = do_shortcode( $this->step_settings['lesson_video_url'] );
								} elseif ( 'iframe' === $this->video_data['videos_found_type'] ) {
									$this->video_content = $this->step_settings['lesson_video_url'];
								}
							} elseif (
								'youtube' === $this->video_data['videos_found_provider']
								|| 'vimeo' === $this->video_data['videos_found_provider']
								|| 'wistia' === $this->video_data['videos_found_provider']
							) {
								if ( 'embed_shortcode' === $this->video_data['videos_found_type'] ) {
									global $wp_embed;
									$this->video_content = $wp_embed->run_shortcode( $this->step_settings['lesson_video_url'] );
								} elseif ( 'video_shortcode' === $this->video_data['videos_found_type'] ) {
									$this->video_content = do_shortcode( $this->step_settings['lesson_video_url'] );
								} elseif ( 'iframe' === $this->video_data['videos_found_type'] ) {
									$this->video_content = $this->step_settings['lesson_video_url'];
								}
							} elseif ( 'vooplayer' === $this->video_data['videos_found_provider'] ) {
								if ( 'vooplayer_shortcode' === $this->video_data['videos_found_type'] ) {
									$this->video_content = do_shortcode( $this->step_settings['lesson_video_url'] );
								} elseif ( 'iframe' === $this->video_data['videos_found_type'] ) {
									$this->video_content = $this->step_settings['lesson_video_url'];
								}
							} elseif ( 'bunny' === $this->video_data['videos_found_provider'] ) {
								/** This filter is documented in includes/course/ld-course-video.php */
								$bunny_video_params = apply_filters(
									'ld_video_params',
									[
										'mp4_quality' => 720,
									],
									$this->video_data['videos_found_provider'],
									$this->video_content,
									$post,
									$this->step_settings
								);

								if ( 'embed_shortcode' === $this->video_data['videos_found_type'] ) {
									// Get the Bunny embed URL.

									preg_match(
										'/' . get_shortcode_regex( [ 'embed' ] ) . '/s',
										$this->step_settings['lesson_video_url'],
										$bunny_video_shortcode_parts
									);
									$bunny_video_playlist_url = $bunny_video_shortcode_parts[5] ?? '';

									$this->replace_bunny_playlist_url_with_mp4_url(
										Cast::to_string( $bunny_video_playlist_url ),
										Cast::to_int( $bunny_video_params['mp4_quality'] ?? 720 )
									);

									global $wp_embed;
									$this->video_content = do_shortcode(
										$wp_embed->run_shortcode( $this->step_settings['lesson_video_url'] )
									);
								} elseif ( 'video_shortcode' === $this->video_data['videos_found_type'] ) {
									// Get the Bunny video URL from the shortcode.

									preg_match(
										'/' . get_shortcode_regex( [ 'video' ] ) . '/s',
										$this->step_settings['lesson_video_url'],
										$bunny_video_shortcode_parts
									);
									$bunny_video_shortcode_parsed = shortcode_parse_atts( $bunny_video_shortcode_parts[3] ?? '' );
									$bunny_video_playlist_url     = is_array( $bunny_video_shortcode_parsed )
										? $bunny_video_shortcode_parsed['src'] ?? ''
										: '';

									$this->replace_bunny_playlist_url_with_mp4_url(
										Cast::to_string( $bunny_video_playlist_url ),
										Cast::to_int( $bunny_video_params['mp4_quality'] ?? 720 )
									);

									$this->video_content = do_shortcode(
										Cast::to_string( $this->step_settings['lesson_video_url'] )
									);
								}
							}

							if ( ! empty( $this->video_content ) ) {
								if ( $logic_video ) {

									if ( ( isset( $this->step_settings['lesson_video_show_controls'] ) ) && ( 'on' === $this->step_settings['lesson_video_show_controls'] ) ) {
										$this->video_data['videos_show_controls'] = 1;
									} else {
										$this->video_data['videos_show_controls'] = 0;
									}

									if ( ( isset( $this->step_settings['lesson_video_auto_start'] ) ) && ( 'on' === $this->step_settings['lesson_video_auto_start'] ) ) {
										$this->video_data['videos_auto_start'] = 1;
									} else {
										$this->video_data['videos_auto_start'] = 0;
									}

									$video_preg_pattern = '';

									if ( strstr( $this->video_content, '<iframe' ) ) {
										$video_token = 'iframe';
									} elseif ( strstr( $this->video_content, '<video' ) ) {
										$video_token = 'video';
									}
									if ( strstr( $this->video_content, ' src="' ) ) {
										$video_preg_pattern = '/<' . $video_token . '.*src=\"(.*)\".*><\/' . $video_token . '>/isU';
									} elseif ( strstr( $this->video_content, " src='" ) ) {
										$video_preg_pattern = '/<' . $video_token . ".*src=\'(.*)\'.*><\/" . $video_token . '>/isU';
									}

									if ( ! empty( $video_preg_pattern ) ) {
										preg_match( $video_preg_pattern, $this->video_content, $matches );
										if ( ( is_array( $matches ) ) && ( isset( $matches[1] ) ) && ( ! empty( $matches[1] ) ) ) {
											// Next we need to check if the video is YouTube, Vimeo, etc. so we check the matches[1].
											if ( 'youtube' === $this->video_data['videos_found_provider'] ) {
												/**
												 * Filters post content video parameters.
												 *
												 * @param array   $video_params   An array of video parameters.
												 * @param string  $video_provider Name of the video provider.
												 * @param string  $video_content  Video content HTML output.
												 * @param WP_POST $post           Post object.
												 * @param array   $settings       An array of LearnDash settings for a post.
												 */
												$ld_video_params = apply_filters(
													'ld_video_params',
													[
														'controls'       => $this->video_data['videos_show_controls'],
														'autoplay'       => $this->video_data['videos_auto_start'],
														'modestbranding' => 1,
														'showinfo'       => 0,
														'rel'            => 0,
													],
													$this->video_data['videos_found_provider'],
													$this->video_content,
													$post,
													$this->step_settings
												);

												// Regardless of the filter we set this param because we need it!
												$ld_video_params['enablejsapi'] = '1'; // cspell:disable-line.

												$matches_1_new       = add_query_arg( $ld_video_params, $matches[1] );
												$this->video_content = str_replace( $matches[1], $matches_1_new, $this->video_content );

											} elseif ( 'vimeo' === $this->video_data['videos_found_provider'] ) {

												/**
												 * Ensure for Vimeo, the video controls and auto-start cannot both be disabled.
												 */
												if ( ( ! $this->video_data['videos_show_controls'] ) && ( ! $this->video_data['videos_auto_start'] ) ) {
													$this->video_data['videos_show_controls'] = true;
												}

												/** This filter is documented in includes/course/ld-course-video.php */
												$ld_video_params = apply_filters(
													'ld_video_params',
													[
														'controls' => $this->video_data['videos_show_controls'],
														'autoplay' => $this->video_data['videos_auto_start'],
													],
													$this->video_data['videos_found_provider'],
													$this->video_content,
													$post,
													$this->step_settings
												);

												/**
												 * For auto-play we also need to mute the video.
												 * See: https://vimeo.zendesk.com/hc/en-us/articles/115004485728-Autoplaying-and-looping-embedded-videos
												 */
												if ( ( isset( $ld_video_params['autoplay'] ) ) && ( absint( '1' ) === absint( $ld_video_params['autoplay'] ) ) ) {
													$ld_video_params['muted'] = 1;
												}

												// Regardless of the filter we set this param because we need it!
												$ld_video_params['api'] = '1';

												$matches_1_new       = add_query_arg( $ld_video_params, $matches[1] );
												$this->video_content = str_replace( $matches[1], $matches_1_new, $this->video_content );

											} elseif ( 'wistia' === $this->video_data['videos_found_provider'] ) {
												/** This filter is documented in includes/course/ld-course-video.php */
												$ld_video_params = apply_filters(
													'ld_video_params',
													[],
													$this->video_data['videos_found_provider'],
													$this->video_content,
													$post,
													$this->step_settings
												);
												if ( ! empty( $ld_video_params ) ) {
													$matches_1_new       = add_query_arg( $ld_video_params, $matches[1] );
													$this->video_content = str_replace( $matches[1], $matches_1_new, $this->video_content );
												} else {
													$matches_1_new = $matches[1];
												}

												$url_path            = wp_parse_url( $matches_1_new, PHP_URL_PATH );
												$url_path_parts      = explode( '/', $url_path );
												$video_id            = $url_path_parts[ count( $url_path_parts ) - 1 ];
												$this->video_content = str_replace( '<iframe ', '<iframe class="wistia_embed" data-learndash-video-wistia-id="' . $video_id . '" ', $this->video_content );
											} elseif ( 'bunny' === $this->video_data['videos_found_provider'] ) {
												/** This filter is documented in includes/course/ld-course-video.php */
												$bunny_video_params = apply_filters(
													'ld_video_params',
													[
														'controls' => Cast::to_bool( $this->video_data['videos_show_controls'] ),
													],
													$this->video_data['videos_found_provider'],
													$this->video_content,
													$post,
													$this->step_settings
												);

												// If controls are disabled then we need to hide them. This "hack" is used for the local video player, so I use it here too.
												if ( ! $bunny_video_params['controls'] ) {
													$this->add_controls_hiding_styles();
												}
											} elseif ( 'vooplayer' === $this->video_data['videos_found_provider'] ) {
												/** This filter is documented in includes/course/ld-course-video.php */
												$ld_video_params = apply_filters(
													'ld_video_params',
													[],
													$this->video_data['videos_found_provider'],
													$this->video_content,
													$post,
													$this->step_settings
												);

												if ( ! empty( $ld_video_params ) ) {
													$matches_1_new       = add_query_arg( $ld_video_params, $matches[1] );
													$this->video_content = str_replace( $matches[1], $matches_1_new, $this->video_content );
												}
											} elseif ( 'local' === $this->video_data['videos_found_provider'] ) {
												/** This filter is documented in includes/course/ld-course-video.php */
												$ld_video_params = apply_filters(
													'ld_video_params',
													[
														'controls' => $this->video_data['videos_show_controls'],
													],
													$this->video_data['videos_found_provider'],
													$this->video_content,
													$post,
													$this->step_settings
												);
												if ( (int) true !== (int) $ld_video_params['controls'] ) {
													$this->add_controls_hiding_styles();
												}
											}
										}
									}

									$this->video_data['videos_auto_complete'] = false;
									if ( 'AFTER' === $this->step_settings['lesson_video_shown'] ) {
										if ( ( isset( $this->step_settings['lesson_video_auto_complete'] ) ) && ( 'on' === $this->step_settings['lesson_video_auto_complete'] ) ) {
											$this->video_data['videos_auto_complete'] = true;

											if ( ( isset( $this->step_settings['lesson_video_hide_complete_button'] ) ) && ( 'on' === $this->step_settings['lesson_video_hide_complete_button'] ) ) {
												$this->video_data['videos_hide_complete_button'] = true;
											}

											if ( isset( $this->step_settings['lesson_video_auto_complete_delay'] ) ) {
												$this->video_data['videos_auto_complete_delay'] = intval( $this->step_settings['lesson_video_auto_complete_delay'] );

												$post_type_obj  = get_post_type_object( $post->post_type );
												$post_type_name = $post_type_obj->labels->name;
												$this->video_data['videos_auto_complete_delay_message'] =
													'<p class="ld-video-delay-message">' . sprintf(
													// translators: placeholders: 1. Lesson or Topic label, 2. span for counter.
														esc_html_x( '%1$s will auto complete in %2$s seconds', 'placeholders: 1. Lesson or Topic label, 2. span for counter', 'learndash' ),
														$post_type_obj->labels->singular_name,
														'<span class="time-countdown">' . $this->video_data['videos_auto_complete_delay'] . '</span>'
													) . '</p>';
											}
										}
									}
								}
							}
						}
					}
				}

				if ( ! empty( $this->video_content ) ) {
					if ( false !== $this->video_data['videos_found_provider'] ) {
						if ( isset( $_GET['ld_debug'] ) ) {
							$this->video_data['video_debug'] = true;
						}

						$video_post_url       = learndash_get_step_permalink( $post );
						$video_post_url_parts = wp_parse_url( $video_post_url );

						if ( defined( 'COOKIE_DOMAIN' ) ) {
							$this->video_data['video_track_domain'] = COOKIE_DOMAIN;
						} else {
							if ( isset( $video_post_url_parts['host'] ) ) {
								$this->video_data['video_track_domain'] = $video_post_url_parts['host'];
							}
						}

						if ( ( is_multisite() ) && ( defined( 'SITECOOKIEPATH' ) ) ) {
							$this->video_data['video_track_path'] = SITECOOKIEPATH;
						} elseif ( defined( 'COOKIEPATH' ) ) {
							$this->video_data['video_track_path'] = COOKIEPATH;
						} else {
							if ( isset( $video_post_url_parts['path'] ) ) {
								$this->video_data['video_track_path'] = $video_post_url_parts['path'];
							}
						}

						/**
						 * Filters content video data.
						 *
						 * @param array $video_data An array of video data.
						 * @param array $settings   An array of LearnDash settings for a post.
						 */
						$this->video_data = apply_filters( 'learndash_lesson_video_data', $this->video_data, $this->step_settings );

						if ( true === $logic_video ) {
							$logic_video_str = 'true';
						} else {
							$logic_video_str = 'false';
						}

						$this->video_content = '<div class="ld-video" data-video-progression="' . $logic_video_str . '" data-video-cookie-key="' . $this->video_data['video_cookie_key'] . '" data-video-provider="' . $this->video_data['videos_found_provider'] . '">' . $this->video_content . '</div>';

						$content = SFWD_LMS::get_template(
							'learndash_lesson_video',
							[
								'content'        => $content,
								'video_content'  => $this->video_content,
								'video_settings' => $this->step_settings,
								'video_data'     => $this->video_data,
							]
						);

					} else {
						$this->video_data['videos_found_provider'] = false;

						$this->video_content = '<div class="ld-video" data-video-progression="false">' . $this->video_content . '</div>';
					}
				} else {
					$content = SFWD_LMS::get_template(
						'learndash_lesson_video',
						[
							'content'        => $content,
							'video_content'  => '',
							'video_settings' => $this->step_settings,
							'video_data'     => $this->video_data,
						]
					);
				}
			}

			return $content;
		}

		/**
		 * Add JS logic to the page footer.
		 *
		 * @since 2.4.3
		 *
		 * @return void
		 */
		public function action_wp_footer() {
			if ( false === $this->video_data['videos_found_provider'] ) {
				return;
			}

			global $learndash_assets_loaded;

			wp_enqueue_script(
				'learndash_cookie_script_js',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/vendor-libs/js-cookie/js.cookie' . learndash_min_asset() . '.js',
				[],
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
			$learndash_assets_loaded['scripts']['learndash_cookie_script_js'] = __FUNCTION__;

			wp_enqueue_script(
				'learndash_video_script_js',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash_video_script' . learndash_min_asset() . '.js',
				[ 'jquery' ],
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
			$learndash_assets_loaded['scripts']['learndash_video_script_js'] = __FUNCTION__;

			wp_localize_script( 'learndash_video_script_js', 'learndash_video_data', $this->video_data );

			if ( 'youtube' === $this->video_data['videos_found_provider'] ) {
				wp_enqueue_script( 'youtube_iframe_api', 'https://www.youtube.com/iframe_api', [ 'learndash_video_script_js' ], LEARNDASH_SCRIPT_VERSION_TOKEN, true );
			} elseif ( 'vimeo' === $this->video_data['videos_found_provider'] ) {
				wp_enqueue_script( 'vimeo_iframe_api', 'https://player.vimeo.com/api/player.js', [ 'learndash_video_script_js' ], LEARNDASH_SCRIPT_VERSION_TOKEN, true );
			} elseif ( 'wistia' === $this->video_data['videos_found_provider'] ) {
				wp_enqueue_script( 'wistia_iframe_api', 'https://fast.wistia.com/assets/external/E-v1.js', [ 'learndash_video_script_js' ], LEARNDASH_SCRIPT_VERSION_TOKEN, array( 'strategy' => 'async' ) );
			}
		}

		/**
		 * Handle Mark Complete on Lesson or Topic with Video Progress enabled.
		 *
		 * @since 2.4.6
		 *
		 * @deprecated 4.11.0 This method is not needed anymore.
		 *                 All logic was moved to `learndash_mark_complete_process`, `learndash_quiz_redirect` functions.
		 *
		 * @param bool    $mark_complete Whether to mark a process complete.
		 * @param WP_Post $post          WP_Post object being marked complete.
		 * @param WP_User $current_user  The User performing the action.
		 *
		 * @return bool
		 */
		public function process_mark_complete( $mark_complete, $post, $current_user ) {
			_deprecated_function(
				__METHOD__,
				'4.11.0',
				'It was a filter callback that is not needed anymore.'
			);

			return $mark_complete;
		}

		/**
		 * Redirect after Mark Complete is performed.
		 *
		 * @since 2.4.6
		 *
		 * @deprecated 4.11.0 This method is not needed anymore.
		 *                 All logic was moved to `learndash_mark_complete_process`, `learndash_quiz_redirect` functions.
		 *
		 * @param string $link Link to redirect to after Mark Complete.
		 *
		 * @return string
		 */
		public function learndash_completion_redirect( $link ) {
			_deprecated_function(
				__METHOD__,
				'4.11.0',
				'It was a filter callback that is not needed anymore.'
			);

			return $link;
		}

		/**
		 * Build unique video progress cookie key. This is used to track the video state
		 * in the user's browser.
		 *
		 * @since 3.2.0
		 *
		 * @return non-empty-string Cookie key.
		 */
		public function build_video_cookie_key() {
			$cookie_key = $this->get_nonce_slug();

			if ( ! empty( $this->step_settings['lesson_video_url'] ) ) {
				$lesson_video_url = html_entity_decode(
					trim( Cast::to_string( $this->step_settings['lesson_video_url'] ) )
				);

				$cookie_key .= '_' . $lesson_video_url;
			}

			return 'learndash-video-progress-' . md5( $cookie_key );
		}

		/**
		 * Utility function to get the nonce slug.
		 *
		 * @since 3.2.3
		 *
		 * @return string
		 */
		protected function get_nonce_slug() {
			return 'learndash_video_' . $this->user_id . '_' . $this->course_id . '_' . $this->step_id;
		}

		/**
		 * Checks if the video cookie 'video_state' is complete.
		 *
		 * @since 3.2.3
		 *
		 * @param string $cookie_key Cookie key. See build_video_cookie_key().
		 *
		 * @return bool
		 */
		public function is_video_cookie_complete( $cookie_key = '' ) {
			if (
				empty( $cookie_key )
				|| ! isset( $_COOKIE[ $cookie_key ] )
			) {
				return false;
			}

			$cookie_data = json_decode( stripslashes( $_COOKIE[ $cookie_key ] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Better not to touch this without testing.

			return is_array( $cookie_data )
				   && isset( $cookie_data['video_state'] )
				   && 'complete' === $cookie_data['video_state'];
		}

		/**
		 * Utility class method to allow add-hoc checks on video complete.
		 *
		 * @since 3.2.3
		 *
		 * @param int $step_id   Course Step ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 *
		 * @return bool
		 */
		public function check_video_complete( $step_id = 0, $course_id = 0, $user_id = 0 ) {
			$this->step_id   = absint( $step_id );
			$this->user_id   = absint( $user_id );
			$this->course_id = absint( $course_id );

			if ( empty( $this->step_id ) ) {
				return false;
			}

			if ( empty( $this->user_id ) ) {
				if ( is_user_logged_in() ) {
					$this->user_id = get_current_user_id();
				} else {
					return false;
				}
			}

			if ( empty( $this->course_id ) ) {
				$this->course_id = (int) learndash_get_course_id( $this->step_id );

				if ( empty( $this->course_id ) ) {
					return false;
				}
			}

			$this->step_settings = (array) learndash_get_setting( $this->step_id );

			// Check if any sub-step is complete.
			if (
				isset( $this->step_settings['lesson_video_shown'] )
				&& 'BEFORE' === $this->step_settings['lesson_video_shown']
			) {
				$complete_child_steps = learndash_user_progression_get_complete_child_steps( $this->user_id, $this->course_id, $this->step_id );

				/**
				 * If the video progression is set to "BEFORE" and there are ANY completed steps then
				 * let the user pass. This will handle scenarios where the user completed steps before
				 * the parent video progress was setup.
				 */
				if ( ! empty( $complete_child_steps ) ) {
					return true;
				}
			}

			$cookie_key = $this->build_video_cookie_key();

			if (
				! empty( $cookie_key )
				&& $this->is_video_cookie_complete( $cookie_key )
			) {
				return true;
			}

			return false;
		}

		/**
		 * Maps Bunny CDN playlist URL into MP4 URL.
		 *
		 * @since 4.8.0
		 *
		 * @param string $playlist_url Playlist URL.
		 * @param int    $mp4_quality  MP4 quality. Default 720.
		 *
		 * @return string
		 */
		protected function map_bunny_playlist_url_into_mp4( string $playlist_url, int $mp4_quality ): string {
			if (
				empty( $playlist_url )
				|| ! Str::contains( $playlist_url, 'playlist.m3u8' )
			) {
				return $playlist_url;
			}

			$bunny_video_mp4_url  = Str::before( $playlist_url, 'playlist.m3u8' );
			$bunny_video_mp4_url .= wp_sprintf( 'play_%1$dp.mp4', $mp4_quality );

			return $bunny_video_mp4_url;
		}

		/**
		 * Adds CSS styles to hide video controls for a <video> tag. It's a hacky way, but it was here before, so I decided not to change it for now.
		 *
		 * @since 4.8.0
		 *
		 * @return void
		 */
		protected function add_controls_hiding_styles(): void {
			$this->video_content .= '<style>.ld-video .mejs-controls { display: none !important; visibility: hidden !important;}</style>';
		}

		/**
		 * Replaces Bunny CDN playlist URL with MP4 URL in the lesson_video_url setting.
		 *
		 * @since 4.8.0
		 *
		 * @param string $playlist_url Playlist URL.
		 * @param int    $mp4_quality  MP4 quality.
		 *
		 * @return void
		 */
		protected function replace_bunny_playlist_url_with_mp4_url( string $playlist_url, int $mp4_quality ): void {
			if ( empty( $playlist_url ) ) {
				return;
			}

			$this->step_settings['lesson_video_url'] = str_replace(
				$playlist_url,
				$this->map_bunny_playlist_url_into_mp4( $playlist_url, $mp4_quality ),
				Cast::to_string( $this->step_settings['lesson_video_url'] )
			);
		}
	}
}

/**
 * Returns true if the video is complete for the step, otherwise false.
 *
 * @since 3.2.3
 *
 * @param int $step_id   Course Step ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 *
 * @return bool
 */
function learndash_video_complete_for_step( $step_id = 0, $course_id = 0, $user_id = 0 ) {
	return Learndash_Course_Video::get_instance()->check_video_complete( $step_id, $course_id, $user_id );
}

/**
 * Delete Video Cookie for Step
 *
 * @since 3.2.3
 *
 * @param int $step_id   Course Step ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 *
 * @return void
 */
function learndash_video_delete_cookie_for_step( $step_id = 0, $course_id = 0, $user_id = 0 ) {
	$ld_video_instance = Learndash_Course_Video::get_instance();

	if ( ! $ld_video_instance->check_video_complete( $step_id, $course_id, $user_id ) ) {
		return;
	}

	$video_cookie_key = $ld_video_instance->build_video_cookie_key();

	if ( isset( $_COOKIE[ $video_cookie_key ] ) ) {
		unset( $_COOKIE[ $video_cookie_key ] );
	}

	$video_track_domain = '';
	if ( defined( 'COOKIE_DOMAIN' ) ) {
		$video_track_domain = COOKIE_DOMAIN;
	}

	$video_track_path = '';
	if ( is_multisite() && defined( 'SITECOOKIEPATH' ) ) {
		$video_track_path = SITECOOKIEPATH;
	} elseif ( defined( 'COOKIEPATH' ) ) {
		$video_track_path = COOKIEPATH;
	}

	// empty value and expiration one hour before.
	setcookie(
		$video_cookie_key,
		'',
		time() - 3600,
		$video_track_path,
		$video_track_domain
	);
}

add_action(
	'learndash_init',
	function () {
		Learndash_Course_Video::get_instance();
	}
);
