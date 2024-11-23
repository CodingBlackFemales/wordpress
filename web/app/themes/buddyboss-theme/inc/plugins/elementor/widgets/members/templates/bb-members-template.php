<?php
/**
 * @var array $settings
 * @var       $type
 * @var       $avatar
 * @var       $members_template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
if ( ! defined( 'BB_MEMBERS_WIDGET' ) ) {
	exit;
} // Exit if accessed outside widget

?>

<div class="bb-members">

	<?php
	if ( ( '' != $settings['member_link_text'] ) || ( '' != $settings['heading_text'] ) ) {
		?>
		<div class="bb-block-header flex align-items-center">
			<div class="bb-block-header__title"><h3><?php echo esc_html( $settings['heading_text'] ); ?></h3></div>
			<?php
			if ( $settings['switch_more'] ) :
				?>
				<div class="bb-block-header__extra push-right">
					<?php
					if ( '' !== $settings['member_link_text'] ) {
						?>
						<a href="<?php bp_members_directory_permalink(); ?>" class="count-more"><?php echo esc_html( $settings['member_link_text'] ); ?><i class="bb-icon-l bb-icon-angle-right"></i></a>
						<?php
					}
					?>
				</div>
				<?php
			endif;
			?>
		</div>
		<?php
	}

	$members_type = array(
		'active'  => __( 'active', 'buddyboss-theme' ),
		'popular' => __( 'popular', 'buddyboss-theme' ),
		'newest'  => __( 'newest', 'buddyboss-theme' ),
	);

	if ( $settings['switch_filter'] ) :
		?>
		<div class="item-options border-<?php echo $settings['filter_border_style']; ?>">
			<?php
			foreach ( $members_type as $k => $mtype ) {
				?>
				<a href="#" id="bb-<?php echo esc_attr( $k ); ?>-members" class="bb-members__tab <?php echo $k === $type ? esc_attr( 'selected' ) : ''; ?>" data-type="<?php echo esc_attr( $k ); ?>">
					<?php echo $mtype; ?>
				</a>
				<?php
			}
			?>
		</div>
		<?php
	endif;
	?>

	<div class="bbel-list-flow">
		<?php
		foreach ( $members_type as $k => $mtype ) {

			// Query members args.
			$members_args = array(
				'user_id'         => 0,
				'type'            => esc_attr( $k ),
				'per_page'        => esc_attr( $settings['members_count']['size'] ),
				'max'             => esc_attr( $settings['members_count']['size'] ),
				'member_type'     => ! empty( $settings['profile_types'] ) ? $settings['profile_types'] : 0,
				'populate_extras' => true,
				'search_terms'    => false,
			);

			// Query members.
			if ( bp_has_members( $members_args ) ) :
				?>

				<div class="bb-members-list bb-members-list--<?php echo esc_attr( $k ); ?> bb-members-list--align-<?php echo esc_attr( $settings['alignment'] ); ?> <?php echo $k === $type ? esc_attr( 'active' ) : ''; ?>">

					<?php $this->add_render_attribute( 'bb-member', 'class', 'bb-members-list__item' ); ?>

					<?php
					while ( bp_members() ) :
						bp_the_member();
						?>

						<div <?php echo $this->get_render_attribute_string( 'bb-member' ); ?>>
							<?php if ( $settings['switch_avatar'] ) : ?>
								<div class="bb-members-list__avatar">
									<a href="<?php bp_member_permalink(); ?>"
										<?php
										if ( 'yes' === $settings['switch_tooltips'] ) {
											?>
											 data-balloon-pos="<?php echo ( 'left' === $settings['alignment'] ) ? esc_attr( 'right' ) : esc_attr( 'left' ); ?>"
											 data-balloon="<?php echo bp_get_member_last_active(); ?>"
										<?php } ?>
									>
										<?php bp_member_avatar( $avatar ); ?>
									</a>
								</div>
							<?php endif; ?>

							<?php if ( $settings['switch_name'] ) : ?>
								<div class="bb-members-list__name fn"><a
											href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a></div>
							<?php endif; ?>
							<?php
							if ( function_exists( 'bb_user_presence_html' ) ) {
								bb_user_presence_html( bp_get_member_user_id() );
							} else {
								$current_time = current_time( 'mysql', 1 );
								$diff         = strtotime( $current_time ) - strtotime( $members_template->member->last_activity );
								if ( $diff < 300 && $settings['switch_status'] ) { // 5 minutes  =  5 * 60
									echo wp_kses_post( apply_filters( 'bb_user_online_html', '<span class="member-status online"></span>', bp_get_member_user_id() ) );
								}
							}
							?>
						</div>

					<?php endwhile; ?>
				</div>
				<?php
			else :
				?>

				<div class="bb-members-list bb-members-list--<?php echo $mtype; ?> bb-no-data bb-no-data--members <?php echo $mtype == $type ? 'active' : ''; ?>">
					<img class="bb-no-data__image" src="<?php echo get_template_directory_uri(); ?>/assets/images/svg/dfy-no-data-icon03.svg" alt="Members"/>
					<div><?php echo __( 'Sorry, no members were found.', 'buddyboss-theme' ); ?></div>
				</div>

				<?php
			endif;
		}
		?>
	</div>

</div>
