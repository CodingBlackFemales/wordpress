<?php
/**
 * Single Achievement Template
 *
 * @package LifterLMS/Templates
 *
 * @since 1.0.0
 * @version 3.14.6
 */

defined( 'ABSPATH' ) || exit;

?>

<a class="llms-achievement" data-id="<?php echo $achievement->get( 'id' ); ?>" href="#<?php printf( 'achievement-%d', $achievement->get( 'id' ) ); ?>" id="<?php printf( 'llms-achievement-%d', $achievement->get( 'id' ) ); ?>">

	<?php do_action( 'lifterlms_before_achievement', $achievement ); ?>

	<div class="llms-achievement-image llms-achievement-image--icon"><?php echo $achievement->get_image_html(); ?></div>

	<div class="llms-achievement__body">

		<h4 class="llms-achievement-title"><?php echo $achievement->get( 'achievement_title' ); ?></h4>

		<div class="llms-achievement-info">
			<div class="llms-achievement-content">
			<?php echo wp_trim_words( $achievement->get( 'content' ), 20 ); ?>
			</div>
			<div class="llms-achievement-date"><?php printf( _x( '<span>Awarded on</span> %s', 'achievement earned date', 'buddyboss-theme' ), $achievement->get_earned_date() ); ?></div>
		</div>

	</div>

	<?php do_action( 'lifterlms_after_achievement', $achievement ); ?>

</a>

