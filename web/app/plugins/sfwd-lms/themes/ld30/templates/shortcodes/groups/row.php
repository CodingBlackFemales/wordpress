<?php
/**
 * LearnDash LD30 Displays a group row.
 *
 * @since 3.0.0
 * @version 4.21.4
 *
 * @var WP_Post $group   The group post object.
 * @var string  $context The context of the group row. Available contexts are: 'admin-group', 'user-group'. Available since v4.21.2.
 *
 * @package LearnDash\Templates\LD30
 */

$context_class_suffix = $context ? '-' . $context : '';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_content = ( empty( $group->post_content ) ? false : true ); ?>

<div class="ld-item-list-item ld-expandable ld-item-group-item" id="<?php echo esc_attr( 'ld-expand-' . $group->ID . $context_class_suffix ); ?>">
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
				<button
					aria-controls="<?php echo esc_attr( 'ld-group-list-item-' . $group->ID . $context_class_suffix . '-container' ); ?>"
					aria-expanded="false"
					class="ld-expand-button ld-button-alternate"
					data-ld-expands="<?php echo esc_attr( 'ld-group-list-item-' . $group->ID . $context_class_suffix . '-container' ); ?>"
					id="<?php echo esc_attr( 'ld-expand-button-' . $group->ID . $context_class_suffix ); ?>"
				>
					<span class="ld-icon-arrow-down ld-icon ld-primary-background"></span>
					<span class="ld-text ld-primary-color"><?php esc_html_e( 'Expand', 'learndash' ); ?></span>

					<span class="screen-reader-text">
						<?php echo esc_html( get_the_title( $group->ID ) ); ?>
					</span>
				</button> <!--/.ld-expand-button-->
			</div> <!--/.ld-item-details-->
		<?php endif; ?>
	</div> <!--/.ld-item-list-item-preview-->
	<?php if ( $has_content ) : ?>
		<div
			class="ld-item-list-item-expanded"
			data-ld-expand-id="<?php echo esc_attr( 'ld-group-list-item-' . $group->ID . $context_class_suffix . '-container' ); ?>"
			id="<?php echo esc_attr( 'ld-group-list-item-' . $group->ID . $context_class_suffix . '-container' ); ?>"
		>
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
