<?php
/**
 * View: Alert Action Link.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var array|null $action Alert action.
 * @var Template   $this   Current Instance of template engine rendering this template.
 *
 * @phpstan-var null|array{
 *     label: string,
 * } $action
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Template;

if ( ! $action || empty( $action['label'] ) ) {
	return;
}
?>
<span class="ld-alert__label">
	<?php echo esc_html( $action['label'] ); ?>
</span>



