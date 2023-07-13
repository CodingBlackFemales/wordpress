<?php
/**
 * View: Pagination List Item.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var array{class: string, content: string} $pagination_item Pagination item.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

?>
<li class="<?php echo esc_attr( $pagination_item['class'] ); ?>">
	<?php echo $pagination_item['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- It's expected, we control HTML. ?>
</li>
