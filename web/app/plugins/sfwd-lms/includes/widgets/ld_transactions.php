<?php
/**
 * LearnDash `Transactions` Widget Class.
 *
 * @since 2.1.0
 * @package LearnDash\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Transactions_Widget' ) ) && ( class_exists( 'WP_Widget' ) ) ) {

	/**
	 * Class for LearnDash `Transactions` Widget.
	 *
	 * @since 2.1.0
	 * @uses WP_Widget
	 */
	class Transactions_Widget extends WP_Widget /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Post type
		 *
		 * @var string $post_type.
		 */
		protected $post_type = 'sfwd-transactions';

		/**
		 * Post name
		 *
		 * @var string $post_name.
		 */
		protected $post_name = 'Transactions';

		/**
		 * Post arguments
		 *
		 * @var object $post_args.
		 */
		protected $post_args;

		/**
		 * Public constructor for Widget Class.
		 *
		 * @since 2.1.0
		 */
		public function __construct() {
			$args = array();

			if ( empty( $args['description'] ) ) {
				// translators: placeholder: Transactions.
				$args['description'] = sprintf( esc_html_x( 'Displays a list of %s', 'placeholder: Transactions', 'learndash' ), $this->post_name );
			}

			if ( empty( $this->post_args ) ) {
				$this->post_args = array(
					'post_type'   => $this->post_type,
					'numberposts' => -1,
					'order'       => 'DESC',
					'orderby'     => 'date',
				);
			}

			parent::__construct( "{$this->post_type}-widget", $this->post_name, $args );
		}

		/**
		 * Displays widget
		 *
		 * @since 2.1.0
		 *
		 * @param array $args     widget arguments.
		 * @param array $instance widget instance.
		 */
		public function widget( $args, $instance ) {
			global $learndash_shortcode_used;

			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

			/* Before Widget content */
			$buf = $before_widget;

			/** This filter is documented in https://developer.wordpress.org/reference/hooks/widget_title/ */
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

			if ( ! empty( $title ) ) {
				$buf .= $before_title . $title . $after_title;
			}

			$buf .= '<ul>';

			/* Display Widget Data */
			$args = $this->post_args;

			$args['posts_per_page'] = $args['numberposts'];
			$args['wrapper']        = 'li';
			global $shortcode_tags, $post;

			if ( ! empty( $shortcode_tags[ $this->post_type ] ) ) {
				$buf .= call_user_func( $shortcode_tags[ $this->post_type ], $args, null, $this->post_type );
			}

			/* After Widget content */
			$buf .= '</ul>' . $after_widget;

			echo $buf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.

			$learndash_shortcode_used = true;
		}

		/**
		 * Handles widget updates in admin
		 *
		 * @since 2.1.0
		 *
		 * @param array $new_instance New instance.
		 * @param array $old_instance Old instance.
		 *
		 * @return array $instance
		 */
		public function update( $new_instance, $old_instance ) {
			/* Updates widget title value */
			$instance          = $old_instance;
			$instance['title'] = wp_strip_all_tags( $new_instance['title'] );
			return $instance;
		}

		/**
		 * Display widget form in admin
		 *
		 * @since 2.1.0
		 *
		 * @param array $instance widget instance.
		 *
		 * @return string Default return is 'noform'.
		 */
		public function form( $instance ) {
			if ( $instance ) {
				$title = esc_attr( $instance['title'] );
			} else {
				$title = $this->post_name;
			}

			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'learndash' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>
			<?php
			return '';
		}
	}

	add_action(
		'widgets_init',
		function() {
			return register_widget( 'Transactions_Widget' );
		}
	);
}
