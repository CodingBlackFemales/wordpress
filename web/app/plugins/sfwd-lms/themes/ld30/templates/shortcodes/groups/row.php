<?php
/**
 * LearnDash LD30 Displays a group row.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_content = ( empty( $group->post_content ) ? false : true ); ?>

<div class="ld-item-list-item ld-expandable ld-item-group-item" id="<?php echo esc_attr( 'ld-expand-' . $group->ID ); ?>">
	<div class="ld-item-list-item-preview ld-group-row">
		<?php if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'public' ) === 'yes' ) { ?>
			<a href="<?php echo esc_url( get_the_permalink( $group->ID ) ); ?>" class="ld-item-name">
			<span class="ld-item-name"><?php echo esc_html( get_the_title( $group->ID ) ); ?></span></a>
			<?php
		} else {
			echo esc_html( get_the_title( $group->ID ) );
		}
		?>
		<?php if ( $has_content ) : ?>
			<div class="ld-item-details">
				<div class="ld-expand-button ld-button-alternate" id="<?php echo esc_attr( 'ld-expand-button-' . $group->ID ); ?>" data-ld-expands="<?php echo esc_attr( 'ld-group-list-item-' . $group->ID ); ?>">
					<span class="ld-icon-arrow-down ld-icon ld-primary-background"></span>
					<span class="ld-text ld-primary-color"><?php esc_html_e( 'Expand', 'learndash' ); ?></span>
				</div> <!--/.ld-expand-button-->
			</div> <!--/.ld-item-details-->
		<?php endif; ?>
	</div> <!--/.ld-item-list-item-preview-->
	<?php if ( $has_content ) : ?>
		<div class="ld-item-list-item-expanded" data-ld-expand-id="<?php echo esc_attr( 'ld-group-list-item-' . $group->ID ); ?>">
			<div class="ld-item-list-content">
				<?php
				SFWD_LMS::content_filter_control( false );

				/** This filter is documented in https://developer.wordpress.org/reference/hooks/the_content/ */
				$group_content = apply_filters( 'the_content', $group->post_content );
				$group_content = str_replace( ']]>', ']]&gt;', $group_content );
				echo $group_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputting HTML content

				SFWD_LMS::content_filter_control( true );
				?>
			</div>
		</div>
	<?php endif; ?>
</div> <!--/.ld-table-list-item-->
