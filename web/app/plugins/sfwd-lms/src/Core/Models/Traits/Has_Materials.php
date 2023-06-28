<?php
/**
 * Trait for models that have materials.
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

namespace LearnDash\Core\Models\Traits;

use LDLMS_Post_Types;

/**
 * Trait for models that have materials.
 *
 * @since 4.6.0
 */
trait Has_Materials {
	/**
	 * Returns the materials content.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_materials(): string {
		$setting_prefix    = LDLMS_Post_Types::get_post_type_key( $this->get_post()->post_type );
		$materials_enabled = $this->get_setting( $setting_prefix . '_materials_enabled' );
		$materials         = $this->get_setting( $setting_prefix . '_materials' );

		if ( empty( $materials ) ) {
			return '';
		}

		if ( 'on' !== $materials_enabled ) {
			return '';
		}

		$materials = wp_specialchars_decode( strval( $materials ), ENT_QUOTES );
		$materials = do_shortcode( $materials );
		$materials = wpautop( $materials );

		return $materials;
	}
}
