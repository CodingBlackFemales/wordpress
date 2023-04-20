<?php
/**
 * LearnDash Admin filter factory.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Filters
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Filter_Factory' ) ) {
	/**
	 * Learndash admin filter factory class.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Filter_Factory {
		/**
		 * Available filter types.
		 *
		 * @since 4.2.0
		 *
		 * @var array
		 */
		protected static $filter_types = array(
			Learndash_Admin_Filters::TYPE_POST_ID          => Learndash_Admin_Filter_Post_ID::class,
			Learndash_Admin_Filters::TYPE_POST_TITLE       => Learndash_Admin_Filter_Post_Title::class,
			Learndash_Admin_Filters::TYPE_POST_STATUS      => Learndash_Admin_Filter_Post_Status::class,
			Learndash_Admin_Filters::TYPE_META_SWITCH      => Learndash_Admin_Filter_Meta_Switch::class,
			Learndash_Admin_Filters::TYPE_META_SELECT      => Learndash_Admin_Filter_Meta_Select::class,
			Learndash_Admin_Filters::TYPE_META_SELECT_AJAX => Learndash_Admin_Filter_Meta_Select_Ajax::class,
			Learndash_Admin_Filters::TYPE_SHARED_STEPS     => Learndash_Admin_Filter_Shared_Steps::class,
		);

		/**
		 * Returns Learndash_Admin_Filter instance.
		 *
		 * @since 4.2.0
		 *
		 * @param string $type Filter type.
		 * @param mixed  ...$args Filter Parameters.
		 *
		 * @return Learndash_Admin_Filter
		 */
		public static function create_filter( string $type, ...$args ): Learndash_Admin_Filter {
			/**
			 * Filters admin filter types.
			 *
			 * @since 4.2.0
			 *
			 * @param array $filter_types Admin filters.
			 */
			$filter_types = apply_filters( 'learndash_filter_types', self::$filter_types );

			if ( ! isset( $filter_types[ $type ] ) ) {
				// translators: placeholder: field type.
				wp_die( sprintf( esc_html__( 'Learndash admin filter with the "%s" type not found.', 'learndash' ), esc_attr( $type ) ) );
			}

			return new $filter_types[ $type ]( ...$args );
		}
	}
}
