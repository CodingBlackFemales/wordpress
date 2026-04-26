<?php
/**
 * View: Lesson Navigation Progress area - Mark Incomplete.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Lesson $lesson The lesson model.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Lesson;

echo learndash_show_mark_incomplete( $lesson->get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's the button HTML.
