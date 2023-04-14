<?php

namespace BuddyBossTheme;

interface BBLMSHelper {
	/**
	 * @param int $limit
	 *
	 * @return mixed
	 */
	public function last_courses_actions( $limit = 5 );

	/**
	 * @param $course
	 *
	 * @return mixed
	 */
	public function active_lesson( $course );
}