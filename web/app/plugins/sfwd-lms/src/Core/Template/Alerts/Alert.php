<?php
/**
 * LearnDash Alert class.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Alerts;

use InvalidArgumentException;
use LearnDash\Core\Utilities\Cast;

/**
 * The Alert object.
 *
 * @since 4.24.0
 */
class Alert {
	/**
	 * Alert ID.
	 *
	 * @since 4.24.0
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * Alert type (e.g., error, info, warning). Default "info".
	 *
	 * @since 4.24.0
	 *
	 * @var string
	 */
	protected string $type = 'info';

	/**
	 * Alert message. Default empty string.
	 *
	 * @since 4.24.0
	 *
	 * @var string
	 */
	protected string $message = '';

	/**
	 * Alert action type (e.g., link, button, etc.). A matching template must exist for each valid action type. Default empty string.
	 *
	 * @since 4.24.0
	 *
	 * @var ?string
	 */
	protected ?string $action_type;

	/**
	 * Alert icon. Default null.
	 *
	 * @since 4.24.0
	 *
	 * @var ?string
	 */
	protected ?string $icon;

	/**
	 * Alert link text. Default null.
	 *
	 * @since 4.24.0
	 *
	 * @var ?string
	 */
	protected ?string $link_text;

	/**
	 * Alert link URL. Default null.
	 *
	 * @since 4.24.0
	 *
	 * @var ?string
	 */
	protected ?string $link_url;

	/**
	 * Alert link target. Default "_self".
	 *
	 * @since 4.24.0
	 *
	 * @var string
	 */
	protected string $link_target = '_self';

	/**
	 * Alert button icon. Default null.
	 *
	 * @since 4.24.0
	 *
	 * @var ?string
	 */
	protected ?string $button_icon;

	/**
	 * Constructor.
	 *
	 * @since 4.24.0
	 *
	 * @param string  $id          Alert ID.
	 * @param string  $type        Alert type. Default "info".
	 * @param string  $message     Alert message. Default empty string.
	 * @param ?string $action_type Alert action type. Default null.
	 * @param ?string $icon        Alert icon. Default null.
	 * @param ?string $link_text   Alert link text. Default null.
	 * @param ?string $link_url    Alert link URL. Default null.
	 * @param string  $link_target Alert link target. Default "_self".
	 * @param ?string $button_icon Alert button icon. Default null.
	 */
	public function __construct(
		string $id,
		string $type = 'info',
		string $message = '',
		?string $action_type = null,
		?string $icon = null,
		?string $link_text = null,
		?string $link_url = null,
		string $link_target = '_self',
		?string $button_icon = null
	) {
		$this->id          = $id;
		$this->type        = $type;
		$this->message     = $message;
		$this->action_type = $action_type;
		$this->icon        = $icon;
		$this->link_text   = $link_text;
		$this->link_url    = $link_url;
		$this->link_target = $link_target;
		$this->button_icon = $button_icon;
	}

	/**
	 * Gets the alert ID.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_id(): string {
		/**
		 * Filters the alert ID.
		 *
		 * @since 4.24.0
		 *
		 * @param string $id    Alert ID.
		 * @param Alert  $alert Alert object.
		 *
		 * @return string
		 */
		return apply_filters( 'learndash_template_alert_id', $this->id, $this );
	}

	/**
	 * Sets the alert ID.
	 *
	 * @since 4.24.0
	 *
	 * @param string $id Alert ID.
	 *
	 * @return self
	 */
	public function set_id( string $id ): self {
		$this->id = strtolower( $id );

		return $this;
	}

	/**
	 * Gets the alert type.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_type(): string {
		/**
		 * Filters the alert type.
		 *
		 * @since 4.24.0
		 *
		 * @param string $type  Alert type.
		 * @param Alert  $alert Alert object.
		 *
		 * @return string
		 */
		return (string) apply_filters( 'learndash_template_alert_type', $this->type, $this );
	}

	/**
	 * Sets the alert type.
	 *
	 * @since 4.24.0
	 *
	 * @param string $type Alert type.
	 *
	 * @return self
	 */
	public function set_type( string $type ): self {
		$this->type = strtolower( $type );

		return $this;
	}

	/**
	 * Gets the alert message.
	 *
	 * @since 4.24.0
	 *
	 * @return ?string
	 */
	public function get_message(): ?string {
		/**
		 * Filters the alert message.
		 *
		 * @since 4.24.0
		 *
		 * @param ?string $message Alert message.
		 * @param Alert   $alert   Alert object.
		 *
		 * @return ?string
		 */
		return apply_filters( 'learndash_template_alert_message', $this->message, $this );
	}

	/**
	 * Sets the alert message.
	 *
	 * @since 4.24.0
	 *
	 * @param string $message Alert message.
	 *
	 * @return self
	 */
	public function set_message( string $message ): self {
		$this->message = $message;

		return $this;
	}

	/**
	 * Gets the alert action type.
	 *
	 * @since 4.24.0
	 *
	 * @return ?string
	 */
	public function get_action_type(): ?string {
		/**
		 * Filters the alert action type.
		 *
		 * @since 4.24.0
		 *
		 * @param ?string $action_type Alert action type.
		 * @param Alert   $alert       Alert object.
		 *
		 * @return ?string
		 */
		return apply_filters( 'learndash_template_alert_action_type', $this->action_type, $this );
	}

	/**
	 * Sets the alert action type.
	 *
	 * @since 4.24.0
	 *
	 * @param string $action_type Alert action type.
	 *
	 * @return self
	 */
	public function set_action_type( string $action_type ): self {
		$this->action_type = strtolower( $action_type );

		return $this;
	}

	/**
	 * Gets the alert icon.
	 *
	 * @since 4.24.0
	 *
	 * @return ?string
	 */
	public function get_icon(): ?string {
		if (
			empty( $this->icon )
			&& ! empty( $this->get_type() )
		) {
			$default_icon_map = [
				'error'   => 'error',
				'info'    => 'success',
				'warning' => 'warning-2',
			];

			$this->set_icon( $default_icon_map[ $this->get_type() ] ?? 'warning-2' );
		}

		/**
		 * Filters the alert icon.
		 *
		 * @since 4.24.0
		 *
		 * @param ?string $icon  Alert icon.
		 * @param Alert   $alert Alert object.
		 *
		 * @return ?string
		 */
		return apply_filters( 'learndash_template_alert_icon', $this->icon, $this );
	}

	/**
	 * Sets the alert icon.
	 *
	 * @since 4.24.0
	 *
	 * @param string $icon Alert icon.
	 *
	 * @return self
	 */
	public function set_icon( string $icon ): self {
		$this->icon = $icon;

		return $this;
	}

	/**
	 * Gets the alert link text.
	 *
	 * @since 4.24.0
	 *
	 * @return ?string
	 */
	public function get_link_text(): ?string {
		/**
		 * Filters the alert link text.
		 *
		 * @since 4.24.0
		 *
		 * @param ?string $link_text Alert link text.
		 * @param Alert   $alert     Alert object.
		 *
		 * @return ?string
		 */
		return apply_filters( 'learndash_template_alert_link_text', $this->link_text, $this );
	}

	/**
	 * Sets the alert link text.
	 *
	 * @since 4.24.0
	 *
	 * @param string $link_text Alert link text.
	 *
	 * @return self
	 */
	public function set_link_text( string $link_text ): self {
		$this->link_text = $link_text;

		return $this;
	}

	/**
	 * Gets the alert link URL.
	 *
	 * @since 4.24.0
	 *
	 * @return ?string
	 */
	public function get_link_url(): ?string {
		/**
		 * Filters the alert link URL.
		 *
		 * @since 4.24.0
		 *
		 * @param ?string $link_url Alert link URL.
		 * @param Alert   $alert    Alert object.
		 *
		 * @return ?string
		 */
		return apply_filters( 'learndash_template_alert_link_url', $this->link_url, $this );
	}

	/**
	 * Sets the alert link URL.
	 *
	 * @since 4.24.0
	 *
	 * @param string $link_url Alert link URL.
	 *
	 * @return self
	 */
	public function set_link_url( string $link_url ): self {
		$this->link_url = $link_url;

		return $this;
	}

	/**
	 * Gets the alert link target.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_link_target(): string {
		/**
		 * Filters the alert link target.
		 *
		 * @since 4.24.0
		 *
		 * @param string $link_target Alert link target.
		 * @param Alert  $alert       Alert object.
		 *
		 * @return string
		 */
		return apply_filters( 'learndash_template_alert_link_target', $this->link_target, $this );
	}

	/**
	 * Sets the alert link target.
	 *
	 * @since 4.24.0
	 *
	 * @param string $link_target Alert link target.
	 *
	 * @return self
	 */
	public function set_link_target( string $link_target ): self {
		$this->link_target = $link_target;

		return $this;
	}

	/**
	 * Gets the alert button icon.
	 *
	 * @since 4.24.0
	 *
	 * @return ?string
	 */
	public function get_button_icon(): ?string {
		/**
		 * Filters the alert button icon.
		 *
		 * @since 4.24.0
		 *
		 * @param ?string $button_icon Alert button icon.
		 * @param Alert   $alert       Alert object.
		 *
		 * @return ?string
		 */
		return apply_filters( 'learndash_template_alert_button_icon', $this->button_icon, $this );
	}

	/**
	 * Sets the alert button icon.
	 *
	 * @since 4.24.0
	 *
	 * @param string $button_icon Alert button icon.
	 *
	 * @return self
	 */
	public function set_button_icon( string $button_icon ): self {
		$this->button_icon = strtolower( $button_icon );

		return $this;
	}

	/**
	 * Parses an alert into an Alert object.
	 *
	 * @since 4.24.0
	 *
	 * @param array<string, mixed>|Alert $alert Alert to parse.
	 *
	 * @throws InvalidArgumentException If the Alert is not an array or an Alert object.
	 *
	 * @return Alert
	 */
	public static function parse( $alert ): Alert {
		if ( $alert instanceof self ) {
			return $alert;
		}

		if ( ! is_array( $alert ) ) {
			throw new InvalidArgumentException(
				// translators: The dynamic variable in this string is an instance of a class.
				sprintf( esc_html__( 'Alerts must be a %1$s instance or an array.', 'learndash' ), __CLASS__ )
			);
		}

		if ( ! isset( $alert['id'] ) ) {
			throw new InvalidArgumentException(
				esc_html__( 'Alerts must have an "id".', 'learndash' )
			);
		}

		// Cast and prepare nullable string values.
		$action_type_str = strtolower( Cast::to_string( $alert['action_type'] ?? '' ) );
		$icon_str        = strtolower( Cast::to_string( $alert['icon'] ?? '' ) );
		$link_text_str   = Cast::to_string( $alert['link_text'] ?? '' );
		$link_url_str    = Cast::to_string( $alert['link_url'] ?? '' );
		$button_icon_str = strtolower( Cast::to_string( $alert['button_icon'] ?? '' ) );

		$alert_object = new self(
			strtolower( Cast::to_string( $alert['id'] ) ),
			strtolower( Cast::to_string( $alert['type'] ?? 'info' ) ),
			Cast::to_string( $alert['message'] ?? '' ),
			! empty( $action_type_str ) ? $action_type_str : null,
			! empty( $icon_str ) ? $icon_str : null,
			! empty( $link_text_str ) ? $link_text_str : null,
			! empty( $link_url_str ) ? $link_url_str : null,
			Cast::to_string( $alert['link_target'] ?? '_self' ),
			! empty( $button_icon_str ) ? $button_icon_str : null,
		);

		foreach ( $alert as $key => $value ) {
			// If set via the constructor, skip.
			if ( in_array( $key, [ 'id', 'type', 'message', 'action_type', 'icon', 'link_text', 'link_url', 'link_target', 'button_icon' ], true ) ) {
				continue;
			}

			// If it cannot be set via a setter method, skip.
			$method = 'set_' . $key;
			if ( ! method_exists( $alert_object, $method ) ) {
				continue;
			}

			$alert_object->{$method}( $value );
		}

		return $alert_object;
	}
}
