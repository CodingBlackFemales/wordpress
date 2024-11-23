<?php
/**
 * @var array $settings
 * @var       $type
 * @var       $user_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
if ( ! defined( 'BB_GROUPS_WIDGET' ) ) {
	exit;
} // Exit if accessed outside widget
?>

<div class="bb-groups">

	<?php if ( ( '' !== $settings['groups_link_text'] ) || ( '' !== $settings['heading_text'] ) ) { ?>
		<div class="bb-block-header flex align-items-center">
			<div class="bb-block-header__title"><h3><?php echo esc_html( $settings['heading_text'] ); ?></h3></div>
			<?php if ( $settings['switch_more'] ) : ?>
				<div class="bb-block-header__extra push-right">
					<?php if ( '' !== $settings['groups_link_text'] ) { ?>
						<a href="<?php bp_groups_directory_permalink(); ?>" class="count-more">
							<?php echo esc_html( $settings['groups_link_text'] ); ?>
							<i class="bb-icon-l bb-icon-angle-right"></i>
						</a>
					<?php } ?>
				</div>
			<?php endif; ?>
		</div>
	<?php } ?>

	<?php
	$groups_filter = array(
		'active'  => esc_html__( 'active', 'buddyboss-theme' ),
		'popular' => esc_html__( 'popular', 'buddyboss-theme' ),
		'newest'  => esc_html__( 'newest', 'buddyboss-theme' ),
	);
	?>

	<?php if ( $settings['switch_filter'] ) : ?>
		<div class="item-options">
			<?php foreach ( $groups_filter as $k => $gtype ) { ?>
				<a href="#" id="bb-<?php echo esc_attr( $k ); ?>-groups" class="bb-groups__tab <?php echo $k === $type ? esc_attr( 'selected' ) : ''; ?>" data-type="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $gtype ); ?></a>
			<?php } ?>
		</div>
	<?php endif; ?>

	<div class="bbel-list-flow">

		<?php
		foreach ( $groups_filter as $k => $gtype ) {
			$group_args = array(
				'user_id'    => $user_id,
				'type'       => esc_attr( $k ),
				'per_page'   => esc_attr( $settings['groups_count']['size'] ),
				'max'        => esc_attr( $settings['groups_count']['size'] ),
				'group_type' => ! empty( $settings['group_types'] ) ? $settings['group_types'] : 0,
			);
			?>

			<?php if ( bp_has_groups( $group_args ) ) : ?>

				<div class="bb-groups-list bb-groups-list--<?php echo esc_attr( $k ); ?> <?php echo $k === $type ? esc_attr( 'active' ) : ''; ?>">

					<ul id="groups-list" class="item-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
						<?php
						while ( bp_groups() ) :
							bp_the_group();
							?>
							<li <?php bp_group_class(); ?>>
								<?php if ( $settings['switch_avatar'] ) : ?>
									<div class="item-avatar">
										<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar_thumb(); ?></a>
									</div>
								<?php endif; ?>

								<div class="item">
									<div class="item-title"><?php bp_group_link(); ?></div>
									<?php if ( $settings['switch_meta'] ) : ?>
										<div class="item-meta">
											<span class="activity">
											<?php
											if ( 'newest' === $k ) {
												printf( esc_html__( 'created %s', 'buddyboss-theme' ), bp_get_group_date_created() );
											} elseif ( 'popular' === $k ) {
												bp_group_member_count();
											} else {
												printf( esc_html__( 'active %s', 'buddyboss-theme' ), bp_get_group_last_active() );
											}
											?>
											</span>
										</div>
									<?php endif; ?>
								</div>
							</li>

						<?php endwhile; ?>
					</ul>

				</div>

			<?php else : ?>

				<div class="bb-groups-list bb-groups-list--<?php echo esc_attr( $k ); ?> bb-no-data bb-no-data--groups <?php echo $k === $type ? esc_attr( 'active' ) : ''; ?>">
					<img class="bb-no-data__image" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/svg/dfy-no-data-icon04.svg" alt="Groups"/>
					<div><?php echo esc_html__( 'No groups matched the current filter.', 'buddyboss-theme' ); ?></div>
					<?php if ( is_user_logged_in() && bp_user_can_create_groups() ) { ?>
						<a href="<?php echo esc_url( trailingslashit( bp_get_groups_directory_permalink() . 'create' ) ); ?>" class="bb-no-data__link"><?php echo esc_html__( 'Create a group', 'buddyboss-theme' ); ?></a>
					<?php } ?>
				</div>

			<?php endif; ?>

		<?php } ?>

	</div>

</div>
