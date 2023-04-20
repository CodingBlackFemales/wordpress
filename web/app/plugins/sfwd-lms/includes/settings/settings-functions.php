<?php
/**
 * LearnDash Settings functions
 *
 * @since 3.5.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Gets the LearnDash setting for a post.
 *
 * @since 2.1.0
 *
 * @param int|WP_Post $post    The `WP_Post` object or Post ID.
 * @param string|null $setting Optional. The slug of the setting to get. Default null.
 *
 * @return mixed The value for requested setting.
 */
function learndash_get_setting( $post, $setting = null ) {

	if ( is_numeric( $post ) ) {
		$post = get_post( $post );
	} else {
		if ( empty( $post ) || ! is_object( $post ) || empty( $post->ID ) ) {
			if ( is_null( $setting ) ) {
				return array();
			}
			return null;
		}
	}

	if ( is_a( $post, 'WP_Post' ) ) {

		if ( 'lesson' === $setting ) {
			return learndash_get_lesson_id( $post->ID );
		}

		if ( 'course' === $setting ) {
			return learndash_get_course_id( $post->ID );
		}

		$post_type_prefix = learndash_get_post_type_key( $post->post_type );
		if ( in_array( $setting, array( $post_type_prefix . '_price_billing_p3', $post_type_prefix . '_price_billing_t3', $post_type_prefix . '_trial_duration_t1', $post_type_prefix . '_trial_duration_p1' ), true ) ) {
			$price_billing_p3  = 0;
			$price_billing_t3  = get_post_meta( $post->ID, $post_type_prefix . '_price_billing_t3', true );
			$trial_duration_p1 = 0;
			$trial_duration_t1 = get_post_meta( $post->ID, $post_type_prefix . '_trial_duration_t1', true );
			if ( ! empty( $price_billing_t3 ) ) {
				$price_billing_t3_new = learndash_billing_cycle_field_frequency_validate( $price_billing_t3 );
				if ( $price_billing_t3 !== $price_billing_t3_new ) {
					$price_billing_t3 = $price_billing_t3_new;
					if ( ! empty( $price_billing_t3 ) ) {
						update_post_meta( $post->ID, $post_type_prefix . '_price_billing_t3', $price_billing_t3 );
					} else {
						delete_post_meta( $post->ID, $post_type_prefix . '_price_billing_t3' );
					}
				}

				$price_billing_p3 = absint( get_post_meta( $post->ID, $post_type_prefix . '_price_billing_p3', true ) );
				if ( ! empty( $price_billing_p3 ) ) {
					$price_billing_p3_new = learndash_billing_cycle_field_interval_validate( $price_billing_p3, $price_billing_t3 );
					if ( $price_billing_p3 !== $price_billing_p3_new ) {
						$price_billing_p3 = $price_billing_p3_new;
						if ( ! empty( $price_billing_p3 ) ) {
							update_post_meta( $post->ID, $post_type_prefix . '_price_billing_p3', $price_billing_p3 );
						} else {
							delete_post_meta( $post->ID, $post_type_prefix . '_price_billing_p3' );
						}
					}
				}
			}

			if ( ! empty( $trial_duration_t1 ) ) {
				$trial_duration_t1_new = learndash_billing_cycle_field_frequency_validate( $trial_duration_t1 );
				if ( $trial_duration_t1 !== $trial_duration_t1_new ) {
					$trial_duration_t1 = $trial_duration_t1_new;
					if ( ! empty( $trial_duration_t1 ) ) {
						update_post_meta( $post->ID, $post_type_prefix . '_trial_duration_t1', $trial_duration_t1 );
					} else {
						delete_post_meta( $post->ID, $post_type_prefix . '_trial_duration_t1' );
					}
				}

				$trial_duration_p1 = absint( get_post_meta( $post->ID, $post_type_prefix . '_trial_duration_p1', true ) );
				if ( ! empty( $trial_duration_p1 ) ) {
					$trial_duration_p1_new = learndash_billing_cycle_field_interval_validate( $trial_duration_p1, $trial_duration_t1 );
					if ( $trial_duration_p1 !== $trial_duration_p1_new ) {
						$trial_duration_p1 = $trial_duration_p1_new;
						if ( ! empty( $trial_duration_p1 ) ) {
							update_post_meta( $post->ID, $post_type_prefix . '_trial_duration_p1', $trial_duration_p1 );
						} else {
							delete_post_meta( $post->ID, $post_type_prefix . '_trial_duration_p1' );
						}
					}
				}
			}

			if ( $setting === $post_type_prefix . '_price_billing_p3' ) {
				return $price_billing_p3;
			} elseif ( $setting === $post_type_prefix . '_price_billing_t3' ) {
				return $price_billing_t3;
			} elseif ( $setting === $post_type_prefix . '_trial_duration_p1' ) {
				return $trial_duration_p1;
			} elseif ( $setting === $post_type_prefix . '_trial_duration_t1' ) {
				return $trial_duration_t1;
			}
		}

		$meta = get_post_meta( $post->ID, '_' . $post->post_type, true );
		if ( ( ! empty( $meta ) ) && ( is_array( $meta ) ) ) {
			if ( empty( $setting ) ) {
				$settings = array();
				foreach ( $meta as $k => $v ) {
					$settings[ str_replace( $post->post_type . '_', '', $k ) ] = $v;
				}
				return $settings;
			} else {
				if ( 'statisticsOn' === $setting ) {
					if ( isset( $meta[ $post->post_type . '_' . $setting ] ) ) {
						return $meta[ $post->post_type . '_' . $setting ];
					} else {
						if ( ( isset( $meta[ $post->post_type . '_quiz_pro' ] ) ) && ( ! empty( $meta[ $post->post_type . '_quiz_pro' ] ) ) ) {
							$quizMapper = new WpProQuiz_Model_QuizMapper(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
							$quiz       = $quizMapper->fetch( $meta[ $post->post_type . '_quiz_pro' ] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
							if ( ( $quiz ) && ( is_a( $quiz, 'WpProQuiz_Model_Quiz' ) ) ) {
								return $quiz->isStatisticsOn();
							}
						}
					}
				}

				if ( isset( $meta[ $post->post_type . '_' . $setting ] ) ) {
					return $meta[ $post->post_type . '_' . $setting ];
				} else {
					return '';
				}
			}
		} else {
			if ( is_null( $setting ) ) {
				return array();
			}
			return '';
		}
	}
	if ( is_null( $setting ) ) {
		return array();
	}
}

/**
 * Updates the LearnDash setting for a post.
 *
 * @since 2.1.0
 *
 * @param int|WP_Post $post    The `WP_Post` object or Post ID.
 * @param string      $setting The slug of the setting to update.
 * @param mixed       $value   The new value of setting to be updated.
 *
 * @return boolean Returns true if the update was successful, otherwise false.
 */
function learndash_update_setting( $post, $setting, $value ) {
	if ( empty( $setting ) ) {
		return false;
	}

	$return = false;

	// Were we sent a post ID?
	if ( is_numeric( $post ) ) {
		$post = get_post( $post );
	}

	// Ensure we have a post object or type WP_Post!
	if ( is_a( $post, 'WP_Post' ) ) {
		$meta = get_post_meta( $post->ID, '_' . $post->post_type, true );
		if ( ! is_array( $meta ) ) {
			$meta = array( $meta );
		}

		if ( 'course' === $setting ) {
			$value = absint( $value );
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'course_id', $value );
			} else {
				delete_post_meta( $post->ID, 'course_id' );
			}
		} elseif ( 'course_access_list' === $setting ) {
			$value = learndash_convert_course_access_list( $value );
			update_post_meta( $post->ID, 'course_access_list', $value );

		} elseif ( 'course_points' === $setting ) {
			$course_points = learndash_format_course_points( $value );
			if ( ! empty( $course_points ) ) {
				update_post_meta( $post->ID, 'course_points', $course_points );
			} else {
				delete_post_meta( $post->ID, 'course_points' );
			}
		} elseif ( 'course_price_type' === $setting ) {
			update_post_meta( $post->ID, '_ld_price_type', $value );
		} elseif ( 'course_price_billing_t3' === $setting ) {
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'course_price_billing_t3', $value );
			} else {
				delete_post_meta( $post->ID, 'course_price_billing_t3' );
			}
		} elseif ( 'course_price_billing_p3' === $setting ) {
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'course_price_billing_p3', $value );
			} else {
				delete_post_meta( $post->ID, 'course_price_billing_p3' );
			}
		} elseif ( 'group_price_type' === $setting ) {
			update_post_meta( $post->ID, '_ld_price_type', $value );
		} elseif ( 'group_price_billing_t3' === $setting ) {
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'group_price_billing_t3', $value );
			} else {
				delete_post_meta( $post->ID, 'group_price_billing_t3' );
			}
		} elseif ( 'group_price_billing_p3' === $setting ) {
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'group_price_billing_p3', $value );
			} else {
				delete_post_meta( $post->ID, 'group_price_billing_p3' );
			}
		} elseif ( 'course_trial_duration_t1' === $setting ) {
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'course_trial_duration_t1', $value );
			} else {
				delete_post_meta( $post->ID, 'course_trial_duration_t1' );
			}
		} elseif ( 'course_trial_duration_p1' === $setting ) {
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'course_trial_duration_p1', $value );
			} else {
				delete_post_meta( $post->ID, 'course_trial_duration_p1' );
			}
		} elseif ( 'group_trial_duration_t1' === $setting ) {
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'group_trial_duration_t1', $value );
			} else {
				delete_post_meta( $post->ID, 'group_trial_duration_t1' );
			}
		} elseif ( 'group_trial_duration_p1' === $setting ) {
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'group_trial_duration_p1', $value );
			} else {
				delete_post_meta( $post->ID, 'group_trial_duration_p1' );
			}
		} elseif ( 'certificate' === $setting ) {
			update_post_meta( $post->ID, '_ld_certificate', $value );
		} elseif ( 'exam_challenge' === $setting ) {
			$value = intval( $value );
			if ( ! empty( $value ) ) {
				learndash_update_course_exam_challenge( $post->ID, $value, false );
			} else {
				learndash_update_course_exam_challenge( $post->ID, $value, true );
			}
		} elseif ( 'exam_challenge_course_show' === $setting ) {
			$value = intval( $value );
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, $setting, $value );
			} else {
				delete_post_meta( $post->ID, $setting );
			}
		} elseif ( 'exam_challenge_course_passed' === $setting ) {
			$value = intval( $value );
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, $setting, $value );
			} else {
				delete_post_meta( $post->ID, $setting );
			}
		} elseif ( 'threshold' === $setting ) {
			update_post_meta( $post->ID, '_ld_certificate_threshold', $value );
		} elseif ( 'lesson' === $setting ) {
			$value = intval( $value );
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'lesson_id', $value );
			} else {
				delete_post_meta( $post->ID, 'lesson_id' );
			}
		} elseif ( 'quiz' === $setting ) {
			update_post_meta( $post->ID, 'quiz_id', absint( $value ) );
		} elseif ( 'quiz_pro' === $setting ) {
			$value = absint( $value );

			// Moved from includes/class-ld-semper-fi-module.php line1052.
			$quiz_pro_id_new = $value;
			$quiz_pro_id_org = absint( get_post_meta( $post->ID, 'quiz_pro_id', true ) );

			if ( ( ! empty( $quiz_pro_id_new ) ) && ( $quiz_pro_id_org !== $quiz_pro_id_new ) ) {
				/**
				 * If this quiz was the primary for all shared settings. We need to
				 * delete the primary marker then move the primary marker to another
				 * quiz using the same shared settings.
				 */
				$quiz_id_primary_org = absint( learndash_get_quiz_primary_shared( $quiz_pro_id_org, false ) );
				if ( $quiz_id_primary_org === $post->ID ) {
					delete_post_meta( $post->ID, 'quiz_pro_primary_' . $quiz_pro_id_org );
					$quiz_post_ids = learndash_get_quiz_post_ids( $quiz_pro_id_org );
					if ( ! empty( $quiz_post_ids ) ) {
						foreach ( $quiz_post_ids as $quiz_post_id ) {
							if ( $quiz_post_id !== $post->ID ) {
								update_post_meta( $quiz_post_id, 'quiz_pro_primary_' . $quiz_pro_id_org, $quiz_pro_id_org );

								/**
								 * After we move the primary marker we also need to move the questions.
								 */
								$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( intval( $post->ID ) );
								if ( $ld_quiz_questions_object ) {
									$questions = $ld_quiz_questions_object->get_questions( 'post_ids' );

									$questions = get_post_meta( $post->ID, 'ld_quiz_questions', true );
									update_post_meta( $quiz_post_id, 'ld_quiz_questions', $questions );
								}
								break;
							}
						}
					}
				}

				$quiz_id_primary_new = absint( learndash_get_quiz_primary_shared( $quiz_pro_id_new, false ) );
				if ( empty( $quiz_id_primary_new ) ) {
					update_post_meta( $post->ID, 'quiz_pro_primary_' . $quiz_pro_id_new, $quiz_pro_id_new );
					// trigger to cause reloading of the questions.
					delete_post_meta( $post->ID, 'ld_quiz_questions' );
				}

				global $wpdb;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$quiz_query_results = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
						absint( $post->ID ),
						'quiz_pro_id_%'
					)
				);

				update_post_meta( $post->ID, 'quiz_pro_id', $quiz_pro_id_new );
				update_post_meta( $post->ID, 'quiz_pro_id_' . $quiz_pro_id_new, $quiz_pro_id_new );
			}
		} elseif ( 'viewProfileStatistics' === $setting ) {
			update_post_meta( $post->ID, '_viewProfileStatistics', $value );
		} elseif ( 'timeLimitCookie' === $setting ) {
			update_post_meta( $post->ID, '_timeLimitCookie', absint( $value ) );
		} elseif (
			// Coupon simple fields.
			in_array(
				$setting,
				array(
					LEARNDASH_COUPON_META_KEY_CODE,
					LEARNDASH_COUPON_META_KEY_TYPE,
					LEARNDASH_COUPON_META_KEY_REDEMPTIONS,
					LEARNDASH_COUPON_META_KEY_START_DATE,
					LEARNDASH_COUPON_META_KEY_END_DATE,
					LEARNDASH_COUPON_META_KEY_PREFIX_APPLY_TO_ALL . 'courses',
					LEARNDASH_COUPON_META_KEY_PREFIX_APPLY_TO_ALL . 'groups',
				),
				true
			)
		) {
			update_post_meta( $post->ID, $setting, $value );
		}

		$meta[ $post->post_type . '_' . $setting ] = $value;

		$return = update_post_meta( $post->ID, '_' . $post->post_type, $meta );
	}

	return $return;
}

