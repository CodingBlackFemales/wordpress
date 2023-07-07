<?php
/**
 * LearnDash Breadcrumb class.
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

namespace LearnDash\Core\Template\Breadcrumbs;

use InvalidArgumentException;

/**
 * The Breadcrumb object.
 *
 * @since 4.6.0
 */
class Breadcrumb {
	/**
	 * Breadcrumb ID.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Breadcrumb label.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	protected $label;

	/**
	 * Breadcrumb url.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Is the breadcrumb the last one?
	 *
	 * @since 4.6.0
	 *
	 * @var bool
	 */
	protected $is_last = false;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param string $id    Breadcrumb ID.
	 * @param string $label Breadcrumb label.
	 * @param string $url   Breadcrumb url.
	 */
	public function __construct( string $id, string $label, string $url = '' ) {
		$this->id    = $id;
		$this->label = $label;
		$this->url   = $url;
	}

	/**
	 * Gets the Breadcrumb ID.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_id(): string {
		/**
		 * Filters the breadcrumb ID.
		 *
		 * @since 4.6.0
		 *
		 * @param string     $id         Breadcrumb ID.
		 * @param Breadcrumb $Breadcrumb Breadcrumb object.
		 *
		 * @ignore
		 */
		return (string) apply_filters( 'learndash_template_breadcrumb_id', $this->id, $this );
	}

	/**
	 * Gets the breadcrumb label.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		/**
		 * Filters the Breadcrumb label.
		 *
		 * @since 4.6.0
		 *
		 * @param string     $label      Breadcrumb label.
		 * @param Breadcrumb $Breadcrumb Breadcrumb object.
		 *
		 * @ignore
		 */
		return (string) apply_filters( 'learndash_template_breadcrumb_label', $this->label, $this );
	}

	/**
	 * Gets the breadcrumb url.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_url(): string {
		/**
		 * Filters the breadcrumb url.
		 *
		 * @since 4.6.0
		 *
		 * @param string     $url        Breadcrumb url.
		 * @param Breadcrumb $Breadcrumb Breadcrumb object.
		 *
		 * @ignore
		 */
		return apply_filters( 'learndash_template_breadcrumb_url', $this->url, $this );
	}

	/**
	 * Returns whether the breadcrumb is last.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function is_last(): bool {
		/**
		 * Filters the breadcrumb is_last state.
		 *
		 * @since 4.6.0
		 *
		 * @param bool       $is_last    Breadcrumb is_last state.
		 * @param Breadcrumb $Breadcrumb Breadcrumb object.
		 *
		 * @ignore
		 */
		return (bool) apply_filters( 'learndash_template_breadcrumb_is_last', $this->is_last, $this );
	}

	/**
	 * Parses a breadcrumb into a Breadcrumb object.
	 *
	 * @since 4.6.0
	 *
	 * @param array<string, mixed>|Breadcrumb $breadcrumb Breadcrumb to parse.
	 *
	 * @throws InvalidArgumentException If the Breadcrumb is not an array or a Breadcrumb object.
	 *
	 * @return Breadcrumb
	 */
	public static function parse( $breadcrumb ): Breadcrumb {
		if ( $breadcrumb instanceof self ) {
			return $breadcrumb;
		}

		if ( ! is_array( $breadcrumb ) ) {
			throw new InvalidArgumentException(
				// translators: The dynamic variable in this string is an instance of a class.
				sprintf( __( 'Breadcrumbs either be a %1$s instance or an array.', 'learndash' ), __CLASS__ )
			);
		}

		if ( ! isset( $breadcrumb['id'] ) || ! isset( $breadcrumb['label'] ) ) {
			throw new InvalidArgumentException( __( 'Breadcrumbs must have an "id" and "label".', 'learndash' ) );
		}

		$breadcrumb_object = new self(
			strval( $breadcrumb['id'] ),
			strval( $breadcrumb['label'] )
		);

		foreach ( $breadcrumb as $key => $value ) {
			if ( 'id' === $key || 'label' === $key ) {
				continue;
			}

			$method = 'set_' . $key;
			if ( ! method_exists( $breadcrumb_object, $method ) ) {
				continue;
			}

			$breadcrumb_object->{$method}( $value );
		}

		return $breadcrumb_object;
	}

	/**
	 * Sets the breadcrumb ID.
	 *
	 * @since 4.6.0
	 *
	 * @param string $id Breadcrumb ID.
	 *
	 * @return self
	 */
	public function set_id( string $id ): self {
		$this->id = $id;

		return $this;
	}

	/**
	 * Sets the breadcrumb label.
	 *
	 * @since 4.6.0
	 *
	 * @param string $label Breadcrumb label.
	 *
	 * @return self
	 */
	public function set_label( string $label ): self {
		$this->label = $label;

		return $this;
	}

	/**
	 * Sets the breadcrumb url.
	 *
	 * @since 4.6.0
	 *
	 * @param string $url Breadcrumb url.
	 *
	 * @return self
	 */
	public function set_url( string $url ): self {
		$this->url = $url;

		return $this;
	}

	/**
	 * Sets the breadcrumb as last.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $is_last Whether the breadcrumb is last. Default true.
	 *
	 * @return self
	 */
	public function set_is_last( bool $is_last = true ): self {
		$this->is_last = $is_last;

		return $this;
	}
}
