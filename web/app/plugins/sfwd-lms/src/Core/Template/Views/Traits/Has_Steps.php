<?php
/**
 * Trait for views that have steps.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Template\Views\Traits;

use LearnDash\Core\App;
use LearnDash\Core\Template\Steps;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Str;
use StellarWP\Learndash\lucatume\DI52\ContainerException;

// TODO: Add hooks later when everything is ready.

/**
 * Trait for views that have steps.
 *
 * @since 4.6.0
 */
trait Has_Steps {
	use Supports_Steps_Context {
		Supports_Steps_Context::build_steps_context as build_steps_context_from_trait;
	}

	/**
	 * The current steps page.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	private $steps_current_page = 0;

	/**
	 * Gets the steps content.
	 *
	 * @since 4.6.0
	 *
	 * @param Steps\Steps $steps Steps.
	 *
	 * @return string HTML.
	 */
	protected function get_steps_content( Steps\Steps $steps ): string {
		try {
			/**
			 * Steps walker.
			 *
			 * @var Steps\Walker $steps_walker Steps walker.
			 */
			$steps_walker = App::container()->get( Steps\Walker::class );
		} catch ( ContainerException $e ) {
			return '';
		}

		return Template::get_template(
			'components/steps',
			[
				'content'          => $steps_walker->walk( $steps->all(), $this->steps_walker_max_depth, $this->build_steps_context() ),
				'pagination_items' => $this->get_steps_pagination_items(),
			]
		);
	}

	/**
	 * Gets the pagination items.
	 *
	 * @since 4.6.0
	 *
	 * @return array{class: string, content: string}[] Array of items.
	 */
	protected function get_steps_pagination_items(): array {
		$links = (array) paginate_links(
			[
				'type'      => 'array',
				'total'     => $this->get_steps_page_size() > 0
					? (int) ceil( $this->get_total_steps() / $this->get_steps_page_size() )
					: 1,
				'format'    => '?steps_page=%#%',
				'current'   => $this->get_current_steps_page(),
				'prev_text' => Template::get_template( 'components/icons/caret-left', [ 'classes' => [ 'ld-icon--lg' ] ] ),
				'next_text' => Template::get_template( 'components/icons/caret-right', [ 'classes' => [ 'ld-icon--lg' ] ] ),
			]
		);

		$items = [];

		foreach ( $links as &$link ) {
			$is_current = Str::contains( $link, 'aria-current' );
			$is_dots    = Str::contains( $link, '&hellip;' );

			$item_class = 'ld-pagination__item';
			if ( $is_current ) {
				$item_class .= ' ld-pagination__item--current';
			}
			if ( $is_dots ) {
				$item_class .= ' ld-pagination__item--dots';
			}

			if ( Str::starts_with( $link, '<a' ) ) {
				$link = Str::replace_first( 'page-numbers', 'ld-pagination__link', $link );
				$link = Str::replace_first( 'prev', 'ld-pagination__link--prev', $link );
				$link = Str::replace_first( 'next', 'ld-pagination__link--next', $link );
			}

			$items[] = [
				'class'   => $item_class,
				'content' => $link,
			];
		}

		return $items;
	}

	/**
	 * Builds the context for the steps.
	 *
	 * @since 4.6.0
	 *
	 * @return array<string, mixed>
	 */
	protected function build_steps_context(): array {
		return self::build_steps_context_from_trait( $this->model );
	}

	/**
	 * Gets the current steps page number.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	protected function get_current_steps_page(): int {
		if ( 0 === $this->steps_current_page ) {
			$this->steps_current_page = min(
				max( 1, intval( $_GET['steps_page'] ?? 1 ) ),
				$this->get_total_steps_pages()
			);
		}

		return $this->steps_current_page;
	}

	/**
	 * Gets the steps total pages.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	protected function get_total_steps_pages(): int {
		if ( $this->get_steps_page_size() <= 0 ) {
			return 1;
		}

		$result = ceil( $this->get_total_steps() / $this->get_steps_page_size() );

		return (int) $result;
	}
}
