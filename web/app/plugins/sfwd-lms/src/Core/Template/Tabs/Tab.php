<?php
/**
 * LearnDash Tab class.
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

namespace LearnDash\Core\Template\Tabs;

use InvalidArgumentException;

/**
 * The Tab object.
 *
 * @since 4.6.0
 */
class Tab {
	/**
	 * Tab content.
	 *
	 * @since 4.6.0
	 *
	 * @var ?string
	 */
	protected $content;

	/**
	 * Tab icon.
	 *
	 * @since 4.6.0
	 *
	 * @var ?string
	 */
	protected $icon;

	/**
	 * Tab ID.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Is the tab the first one?
	 *
	 * @since 4.6.0
	 *
	 * @var bool
	 */
	protected $is_first = false;

	/**
	 * Tab label.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Tab order.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	protected $order = 100;

	/**
	 * Tab template.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	protected $template = '';

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param string $id    Tab ID.
	 * @param string $label Tab label.
	 */
	public function __construct( string $id, string $label ) {
		$this->id    = $id;
		$this->label = $label;
	}

	/**
	 * Gets the tab content.
	 *
	 * @since 4.6.0
	 *
	 * @return string|null
	 */
	public function get_content(): ?string {
		/**
		 * Filters the tab content.
		 *
		 * @since 4.6.0
		 *
		 * @param string|null $content Tab content.
		 * @param Tab         $tab     Tab object.
		 *
		 * @ignore
		 */
		return apply_filters( 'learndash_template_tab_content', $this->content, $this );
	}

	/**
	 * Gets the tab icon.
	 *
	 * @since 4.6.0
	 *
	 * @return string|null
	 */
	public function get_icon(): ?string {
		/**
		 * Filters the tab icon.
		 *
		 * @since 4.6.0
		 *
		 * @param string|null $icon Tab icon.
		 * @param Tab         $tab  Tab object.
		 *
		 * @ignore
		 */
		return apply_filters( 'learndash_template_tab_icon', $this->icon, $this );
	}

	/**
	 * Gets the tab ID.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_id(): string {
		/**
		 * Filters the tab ID.
		 *
		 * @since 4.6.0
		 *
		 * @param string $id  Tab ID.
		 * @param Tab    $tab Tab object.
		 *
		 * @ignore
		 */
		return (string) apply_filters( 'learndash_template_tab_id', $this->id, $this );
	}

	/**
	 * Gets the tab label.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		/**
		 * Filters the tab label.
		 *
		 * @since 4.6.0
		 *
		 * @param string $label Tab label.
		 * @param Tab    $tab   Tab object.
		 *
		 * @ignore
		 */
		return (string) apply_filters( 'learndash_template_tab_label', $this->label, $this );
	}

	/**
	 * Gets the tab order.
	 *
	 * @since 4.6.0
	 *
	 * @return int Defaults to 100.
	 */
	public function get_order(): int {
		/**
		 * Filters the tab order.
		 *
		 * @since 4.6.0
		 *
		 * @param int $order Tab order.
		 * @param Tab $tab   Tab object.
		 *
		 * @ignore
		 */
		return (int) apply_filters( 'learndash_template_tab_order', $this->order, $this );
	}

	/**
	 * Gets the tab template.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_template(): string {
		/**
		 * Filters the tab template.
		 *
		 * @since 4.6.0
		 *
		 * @param string $template Tab template.
		 * @param Tab    $tab      Tab object.
		 *
		 * @ignore
		 */
		return (string) apply_filters( 'learndash_template_tab_template', $this->template, $this );
	}

	/**
	 * Returns whether the tab is first.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function is_first(): bool {
		/**
		 * Filters the tab is_first state.
		 *
		 * @since 4.6.0
		 *
		 * @param bool $is_first Tab is_first state.
		 * @param Tab  $tab      Tab object.
		 *
		 * @ignore
		 */
		return (bool) apply_filters( 'learndash_template_tab_is_first', $this->is_first, $this );
	}

	/**
	 * Parses a tab into a Tab object.
	 *
	 * @since 4.6.0
	 *
	 * @param Tab|array<int|string, mixed> $tab Tab to parse.
	 *
	 * @throws InvalidArgumentException If the tab is not an array or a Tab object.
	 *
	 * @return Tab
	 */
	public static function parse( $tab ): Tab {
		if ( $tab instanceof self ) {
			return $tab;
		}

		if ( ! is_array( $tab ) ) {
			throw new InvalidArgumentException(
				// translators: The dynamic variable in this string is an instance of a class.
				sprintf( __( 'Tabs either be a %1$s instance or an array.', 'learndash' ), __CLASS__ )
			);
		}

		if ( ! isset( $tab['id'] ) || ! isset( $tab['label'] ) ) {
			throw new InvalidArgumentException( __( 'Tabs must have an "id" and "label".', 'learndash' ) );
		}

		$tab_object = new self(
			strval( $tab['id'] ),
			strval( $tab['label'] )
		);

		foreach ( $tab as $key => $value ) {
			if ( 'id' === $key || 'label' === $key ) {
				continue;
			}

			$method = 'set_' . $key;
			if ( ! method_exists( $tab_object, $method ) ) {
				continue;
			}

			$tab_object->{$method}( $value );
		}

		return $tab_object;
	}

	/**
	 * Sets the tab content.
	 *
	 * @since 4.6.0
	 *
	 * @param string|null $content Content.
	 *
	 * @return self
	 */
	public function set_content( ?string $content ): self {
		$this->content = $content;

		return $this;
	}

	/**
	 * Sets the tab icon.
	 *
	 * @since 4.6.0
	 *
	 * @param string|null $icon Tab icon.
	 *
	 * @return self
	 */
	public function set_icon( ?string $icon ): self {
		$this->icon = $icon;

		return $this;
	}

	/**
	 * Sets the tab ID.
	 *
	 * @since 4.6.0
	 *
	 * @param string $id Tab ID.
	 *
	 * @return self
	 */
	public function set_id( string $id ): self {
		$this->id = $id;

		return $this;
	}

	/**
	 * Sets the tab as first.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $is_first Whether the tab is first. Default true.
	 *
	 * @return self
	 */
	public function set_is_first( bool $is_first = true ): self {
		$this->is_first = $is_first;

		return $this;
	}

	/**
	 * Sets the tab label.
	 *
	 * @since 4.6.0
	 *
	 * @param string $label Tab label.
	 *
	 * @return self
	 */
	public function set_label( string $label ): self {
		$this->label = $label;

		return $this;
	}

	/**
	 * Sets the tab order.
	 *
	 * @since 4.6.0
	 *
	 * @param int $order Tab order.
	 *
	 * @return self
	 */
	public function set_order( int $order ): self {
		$this->order = $order;

		return $this;
	}

	/**
	 * Sets the tab template.
	 *
	 * @since 4.6.0
	 *
	 * @param string $template Tab template.
	 *
	 * @return self
	 */
	public function set_template( string $template ): self {
		$this->template = $template;

		return $this;
	}
}
