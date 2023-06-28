<?php
/**
 * View: Alert.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var string   $type Alert type.
 * @var Template $this Current Instance of template engine rendering this template.
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
?>
<div class="ld-alert ld-alert--<?php echo esc_attr( $type ); ?>">
	<?php $this->template( 'components/alert/icon' ); ?>

	<?php $this->template( 'components/alert/message' ); ?>

	<?php $this->template( 'components/alert/action' ); ?>
</div>
