<?php
/**
 * View: Topic Navigation Progress area - Mark Incomplete.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Topic $topic The topic model.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;

echo learndash_show_mark_incomplete( $topic->get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's the button HTML.
