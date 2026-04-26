<?php
/**
 * View: Profile Saved Cards - List.
 *
 * @since 4.25.0
 * @version 4.25.0
 *
 * @var Card[]   $cards The saved cards.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Card;
use LearnDash\Core\Template\Template;
?>
<div class="ld-profile__saved-cards-list">
	<?php foreach ( $cards as $card ) : ?>
		<?php $this->template( 'shortcodes/profile/saved-cards/card', [ 'card' => $card ] ); ?>
	<?php endforeach; ?>
</div>
