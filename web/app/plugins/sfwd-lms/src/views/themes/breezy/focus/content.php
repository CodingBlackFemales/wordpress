<?php
/**
 * View: Focus Mode Content.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Course_Step $model Course step model.
 * @var WP_User     $user  User.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Interfaces\Course_Step;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content can contain HTML.
echo apply_filters(
	'the_content',
	get_the_content(
		null,
		false,
		$model->get_post() // @phpstan-ignore-line -- It will be refactored.
	)
);
