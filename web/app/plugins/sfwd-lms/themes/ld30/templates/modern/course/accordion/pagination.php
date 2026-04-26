<?php
/**
 * View: Course Accordion - Pagination.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var string      $label             Pagination label.
 * @var int         $paged             Current page number.
 * @var int         $pages_total       Total number of pages.
 * @var string      $pagination_source Post type key for the paginated step type.
 * @var Course|Step $parent            Parent model object.
 * @var Template    $this              Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Course;
use LearnDash\Core\Models\Step;
use LearnDash\Core\Template\Template;

if ( $pages_total < 2 ) {
	return;
}

if ( empty( $label ) ) {
	$label = __( 'Pagination', 'learndash' );
}
?>
<nav
	aria-label="<?php echo esc_attr( $label ); ?>"
	class="ld-accordion__pagination"
	data-nonce="<?php echo esc_attr( wp_create_nonce( 'ld30-modern-course-accordion-pagination' ) ); ?>"
	data-paged="<?php echo esc_attr( (string) $paged ); ?>"
	data-ld-pagination="true"
	data-pagination-source="<?php echo esc_attr( $pagination_source ); ?>"
	data-parent-id="<?php echo esc_attr( (string) $parent->get_id() ); ?>"
>
	<ul class="ld-accordion__pagination-list">
		<li class="ld-accordion__pagination-list-item">
			<button
				class="ld-accordion__pagination-button ld-accordion__pagination-button--previous"
				data-previous="true"
				<?php if ( $paged === 1 ) : ?>
					disabled
				<?php endif; ?>
			>
				<?php
					$this->template(
						'components/icons/caret-left',
						[
							'label' => __( 'Previous page', 'learndash' ),
						]
					);
					?>
			</button>
		</li>

		<li class="ld-accordion__pagination-list-item">
			<span class="ld-accordion__pagination-text">
				<?php
				printf(
					// translators: %1$d is the current page number, %2$d is the total number of pages.
					esc_html__( '%1$d of %2$d', 'learndash' ),
					esc_html( (string) $paged ),
					esc_html( (string) $pages_total )
				);
				?>
			</span>
		</li>

		<li class="ld-accordion__pagination-list-item">
			<button
				class="ld-accordion__pagination-button ld-accordion__pagination-button--next"
				data-next="true"
				<?php if ( $paged === $pages_total ) : ?>
					disabled
				<?php endif; ?>
			>
				<?php
					$this->template(
						'components/icons/caret-right',
						[
							'label' => __( 'Next page', 'learndash' ),
						]
					);
					?>
			</button>
		</li>
	</ul>
</nav>

