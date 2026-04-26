<?php
/**
 * View: Profile Saved Cards - Single Card.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-profile__saved-card">
	<?php $this->template( 'shortcodes/profile/saved-cards/card/description' ); ?>

	<?php $this->template( 'shortcodes/profile/saved-cards/card/expiry' ); ?>

	<?php $this->template( 'shortcodes/profile/saved-cards/card/holder-name' ); ?>

	<?php $this->template( 'shortcodes/profile/saved-cards/card/actions' ); ?>
</div>
