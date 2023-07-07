<?php
/**
 * View: Focus Mode Masthead Menu Items.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var array       $menu_items Menu items.
 * @var Course_Step $model      Course step model.
 * @var WP_User     $user       User.
 *
 * @phpstan-var array<string, array{
 *     url: string,
 *     label: string,
 *     classes: string,
 *     target: string,
 *     attr_title: string,
 *     xfn: string
 * }> $menu_items
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

// TODO: move out.
// This filter is documented in themes/ld30/templates/focus/masthead.php.
$menu_items = apply_filters(
	'learndash_focus_header_user_dropdown_items',
	$menu_items,
	$model->get_course() ? $model->get_course()->get_id() : 0,
	$user->ID
);
?>
<span class="ld-user-menu-items">
	<?php foreach ( $menu_items as $slug => $item ) : ?>
		<a
			href="<?php echo esc_url( $item['url'] ); ?>"
			class="<?php echo esc_attr( $item['classes'] ?? '' ); ?>"
			target="<?php echo esc_attr( $item['target'] ?? '' ); ?>"
			rel="<?php echo esc_attr( $item['xfn'] ?? '' ); ?>"
			title="<?php echo esc_attr( $item['attr_title'] ?? '' ); ?>"
		>
			<?php echo esc_html( apply_filters( 'the_title', $item['label'], 0 ) ); ?>
		</a>
	<?php endforeach; ?>
</span>
