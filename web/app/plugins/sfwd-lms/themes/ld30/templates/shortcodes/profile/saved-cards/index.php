<?php
/**
 * View: Profile Saved Cards.
 *
 * @since 4.25.0
 * @version 4.25.3
 *
 * @var Card[]   $cards The saved cards.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Commerce\Card;
use LearnDash\Core\Template\Template;

?>
<div
	class="ld-profile__saved-cards"
	data-js="learndash-view"
	data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
	data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
>
	<h2 class="ld-profile__saved-cards-title">
		<?php esc_html_e( 'Saved Cards', 'learndash' ); ?>
	</h2>

	<?php $this->template( 'shortcodes/profile/saved-cards/list' ); ?>

	<?php $this->template( 'shortcodes/profile/saved-cards/actions' ); ?>
</div>
<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
