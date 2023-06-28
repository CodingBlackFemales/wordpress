<?php
/**
 * LearnDash Step class.
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

namespace LearnDash\Core\Template\Steps;

use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\Models;
use LearnDash\Core\Models\Interfaces;
use LearnDash_Custom_Label;

// TODO: Write tests for it.

/**
 * The Step object.
 *
 * @since 4.6.0
 */
class Step {
	/**
	 * Step ID. Public for a walker.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Step parent ID. Default 0. Public for a walker.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	public $parent_id = 0;

	/**
	 * Step title.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Step url.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Step icon. Defaults to empty string.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Step contents. Defaults to empty array.
	 *
	 * @since 4.6.0
	 *
	 * @var array{
	 *     label: string,
	 *     icon: string,
	 * }[]
	 */
	protected $contents = [];

	/**
	 * Amount of sub steps. Defaults to 0.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	protected $steps_number = 0;

	/**
	 * Sub steps page size. Defaults to 0.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	protected $sub_steps_page_size = 0;

	/**
	 * Type label. Defaults to empty string.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	private $type_label = '';

	/**
	 * Step progress percentage. Defaults to 0.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	protected $progress = 0;

	/**
	 * Step is section. Defaults to false.
	 *
	 * @since 4.6.0
	 *
	 * @var bool
	 */
	protected $is_section = false;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param int    $id        Step ID.
	 * @param string $title     Step title.
	 * @param string $url       Optional. Step url.
	 * @param int    $parent_id Optional. Step parent ID. Default 0.
	 */
	public function __construct( int $id, string $title, string $url = '', int $parent_id = 0 ) {
		$this->id        = $id;
		$this->title     = $title;
		$this->url       = $url;
		$this->parent_id = $parent_id;
	}

	/**
	 * Gets the step ID.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_id(): int {
		/**
		 * Filters the step ID.
		 *
		 * @since 4.6.0
		 *
		 * @param int  $id   Step ID.
		 * @param Step $step Step object.
		 *
		 * @ignore
		 */
		return (int) apply_filters( 'learndash_template_step_id', $this->id, $this );
	}

	/**
	 * Gets the step title.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_title(): string {
		/**
		 * Filters the step title.
		 *
		 * @since 4.6.0
		 *
		 * @param string $title Step title.
		 * @param Step   $step  Step object.
		 *
		 * @ignore
		 */
		return (string) apply_filters( 'learndash_template_step_title', $this->title, $this );
	}

	/**
	 * Gets the step url.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_url(): string {
		/**
		 * Filters the step url.
		 *
		 * @since 4.6.0
		 *
		 * @param string $url  Step url.
		 * @param Step   $step Step object.
		 *
		 * @ignore
		 */
		return (string) apply_filters( 'learndash_template_step_url', $this->url, $this );
	}

	/**
	 * Gets the step's parent ID.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_parent_id(): int {
		/**
		 * Filters the step ID.
		 *
		 * @since 4.6.0
		 *
		 * @param int  $id   Step parent ID.
		 * @param Step $step Step object.
		 *
		 * @ignore
		 */
		return (int) apply_filters( 'learndash_template_step_parent_id', $this->parent_id, $this );
	}

	/**
	 * Gets the step icon.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_icon(): string {
		/**
		 * Filters the step icon.
		 *
		 * @since 4.6.0
		 *
		 * @param string $icon Step icon.
		 * @param Step   $step Step object.
		 *
		 * @ignore
		 */
		return (string) apply_filters( 'learndash_template_step_icon', $this->icon, $this );
	}

	/**
	 * Gets the step contents.
	 *
	 * @since 4.6.0
	 *
	 * @return array{
	 *     label: string,
	 *     icon: string,
	 * }[]
	 */
	public function get_contents(): array {
		/**
		 * Filters the step progress.
		 *
		 * @since 4.6.0
		 *
		 * @param array{ label: string, icon: string }[] $contents Step contents.
		 * @param Step                                   $step     Step object.
		 *
		 * @ignore
		 */
		return (array) apply_filters( 'learndash_template_step_contents', $this->contents, $this );
	}

	/**
	 * Gets the step progress.
	 *
	 * @since 4.6.0
	 *
	 * @return int Defaults to 0.
	 */
	public function get_progress(): int {
		/**
		 * Filters the step progress.
		 *
		 * @since 4.6.0
		 *
		 * @param int  $progress Step progress.
		 * @param Step $step     Step object.
		 *
		 * @ignore
		 */
		return (int) apply_filters( 'learndash_template_step_progress', $this->progress, $this );
	}

	/**
	 * Gets the steps number.
	 *
	 * @since 4.6.0
	 *
	 * @return int Defaults to 0.
	 */
	public function get_steps_number(): int {
		/**
		 * Filters the steps numbers.
		 *
		 * @since 4.6.0
		 *
		 * @param int  $steps_number Step steps number.
		 * @param Step $step         Step object.
		 *
		 * @ignore
		 */
		return (int) apply_filters( 'learndash_template_step_steps_number', $this->steps_number, $this );
	}

	/**
	 * Gets the sub steps page size.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_sub_steps_page_size(): int {
		/**
		 * Filters the step sub steps page size.
		 *
		 * @since 4.6.0
		 *
		 * @param int  $sub_steps_page_size Step sub steps page size.
		 * @param Step $step                Step object.
		 *
		 * @ignore
		 */
		return (int) apply_filters( 'learndash_template_step_sub_steps_page_size', $this->sub_steps_page_size, $this );
	}

	/**
	 * Gets the type label.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_type_label(): string {
		/**
		 * Filters the step type label.
		 *
		 * @since 4.6.0
		 *
		 * @param string $type_label Step type label.
		 * @param Step   $step       Step object.
		 *
		 * @ignore
		 */
		return (string) apply_filters( 'learndash_template_step_type_label', $this->type_label, $this );
	}

	/**
	 * Returns whether the step is a section.
	 *
	 * @since 4.6.0
	 *
	 * @return bool Defaults to false.
	 */
	public function is_section(): bool {
		/**
		 * Filters the step is_section flag.
		 *
		 * @since 4.6.0
		 *
		 * @param bool $is_section Step is_section flag.
		 * @param Step $step       Step object.
		 *
		 * @ignore
		 */
		return (bool) apply_filters( 'learndash_template_step_is_section', $this->is_section, $this );
	}

	/**
	 * Sets the step ID.
	 *
	 * @since 4.6.0
	 *
	 * @param int $id Step ID.
	 *
	 * @return self
	 */
	public function set_id( int $id ): self {
		$this->id = $id;

		return $this;
	}

	/**
	 * Sets the step title.
	 *
	 * @since 4.6.0
	 *
	 * @param string $title Step title.
	 *
	 * @return self
	 */
	public function set_title( string $title ): self {
		$this->title = $title;

		return $this;
	}

	/**
	 * Sets the step url.
	 *
	 * @since 4.6.0
	 *
	 * @param string $url Step url.
	 *
	 * @return self
	 */
	public function set_url( string $url ): self {
		$this->url = $url;

		return $this;
	}

	/**
	 * Sets the step's parent ID.
	 *
	 * @since 4.6.0
	 *
	 * @param int $id Step ID.
	 *
	 * @return self
	 */
	public function set_parent_id( int $id ): self {
		$this->parent_id = $id;

		return $this;
	}

	/**
	 * Sets the step icon.
	 *
	 * @since 4.6.0
	 *
	 * @param string $icon Step icon.
	 *
	 * @return self
	 */
	public function set_icon( string $icon ): self {
		$this->icon = $icon;

		return $this;
	}

	/**
	 * Sets the contents.
	 *
	 * @since 4.6.0
	 *
	 * @param array{ label: string, icon: string }[] $contents Step contents.
	 *
	 * @return self
	 */
	public function set_contents( array $contents ): self {
		$this->contents = $contents;

		return $this;
	}

	/**
	 * Sets the sub steps counter.
	 *
	 * @since 4.6.0
	 *
	 * @param int $steps_number Step sub steps counter.
	 *
	 * @return self
	 */
	public function set_steps_number( int $steps_number ): self {
		$this->steps_number = $steps_number;

		return $this;
	}

	/**
	 * Sets the sub steps page size.
	 *
	 * @since 4.6.0
	 *
	 * @param int $sub_steps_page_size Step sub steps page size.
	 *
	 * @return self
	 */
	public function set_sub_steps_page_size( int $sub_steps_page_size ): self {
		$this->sub_steps_page_size = $sub_steps_page_size;

		return $this;
	}

	/**
	 * Sets the type label
	 *
	 * @since 4.6.0
	 *
	 * @param string $type_label Step type label.
	 *
	 * @return self
	 */
	public function set_type_label( string $type_label ): self {
		$this->type_label = $type_label;

		return $this;
	}

	/**
	 * Sets the contents.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $is_section Step is_section flag.
	 *
	 * @return self
	 */
	public function set_is_section( bool $is_section ): self {
		$this->is_section = $is_section;

		return $this;
	}

	/**
	 * Adds the contents.
	 *
	 * @since 4.6.0
	 *
	 * @param string $label Step contents label.
	 * @param string $icon  Step contents icon.
	 *
	 * @return self
	 */
	public function add_contents( string $label, string $icon ): self {
		$this->contents[] = [
			'label' => $label,
			'icon'  => $icon,
		];

		return $this;
	}

	/**
	 * Sets the step progress.
	 *
	 * @since 4.6.0
	 *
	 * @param int $progress Step progress.
	 *
	 * @return self
	 */
	public function set_progress( int $progress ): self {
		$this->progress = $progress;

		return $this;
	}

	/**
	 * Parses a step into a Step object.
	 *
	 * @since 4.6.0
	 *
	 * @param Step|array<string,mixed> $step Step to parse.
	 *
	 * @throws InvalidArgumentException If the step is not an array or a Step object or a Interfaces\Step object.
	 *
	 * @return Step
	 */
	public static function parse( $step ): Step {
		if ( $step instanceof self ) {
			return $step;
		}

		if ( ! is_array( $step ) ) {
			throw new InvalidArgumentException(
				// translators: The dynamic variable in this string is an instance of a class.
				sprintf( __( 'Steps either be a %1$s instance or an array.', 'learndash' ), __CLASS__ )
			);
		}

		if ( ! isset( $step['id'] ) || ! isset( $step['title'] ) ) {
			throw new InvalidArgumentException( __( 'Steps must have and "id" and "title".', 'learndash' ) );
		}

		$step_object = new self(
			intval( $step['id'] ),
			strval( $step['title'] )
		);

		foreach ( $step as $key => $value ) {
			if ( 'id' === $key || 'title' === $key ) {
				continue;
			}

			$method = 'set_' . $key;
			if ( ! method_exists( $step_object, $method ) ) {
				continue;
			}

			$step_object->{$method}( $value );
		}

		return $step_object;
	}
}
