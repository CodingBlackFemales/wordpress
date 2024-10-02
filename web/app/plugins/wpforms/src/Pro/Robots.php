<?php

namespace WPForms\Pro;

/**
 * Class Robots handles robots.txt related code.
 *
 * @since 1.7.0
 */
class Robots {

	/**
	 * Path to WPForms upload directory.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
	private $upload_root = '';

	/**
	 * Hooks.
	 *
	 * @since 1.7.0
	 */
	public function hooks() {

		add_filter( 'robots_txt', [ $this, 'disallow_upload_dir_indexing' ], -42 );
	}

	/**
	 * Disallow WPForms upload directory indexing in robots.txt.
	 *
	 * @since 1.7.0
	 * @since 1.9.0 Added a separate User-agent line for the WPForms group.
	 *
	 * @param string $output Robots.txt output.
	 *
	 * @return string
	 */
	public function disallow_upload_dir_indexing( $output ) {

		if ( ! $this->get_upload_root() ) {
			return $output;
		}

		// Proceed with adding the Disallow rule only if there are incompatibility issues.
		if ( ! $this->is_own_rule_block_allowed() ) {
			return $output . 'Disallow: ' . $this->get_upload_root();
		}

		// Prepare WPForms rule block with all needed rules.
		$append = $this->get_rule_block();

		return empty( $append ) ? $output : $output . $append;
	}

	/**
	 * We tend to always add WPForms own rule block.
	 * But there was a compatibility issue with the AIOSEO plugin that was resolved in v4.6.7.
	 * For context: it was related to Robots.txt editor functionality.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	private function is_own_rule_block_allowed(): bool {

		if ( ! defined( 'AIOSEO_VERSION' ) ) {
			return true;
		}

		return version_compare( AIOSEO_VERSION, '4.6.7', '>=' );
	}

	/**
	 * Retrieve a relative path to WPForms upload directory.
	 *
	 * @since 1.9.0
	 *
	 * @return false|string
	 */
	public function get_upload_root() {

		if ( ! empty( $this->upload_root ) ) {
			return $this->upload_root;
		}

		$upload_dir = wpforms_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return false;
		}

		$site_url = site_url();

		$upload_root = str_replace( $site_url, '', $upload_dir['url'] );
		$upload_root = trailingslashit( $upload_root );

		$site_url_parts = wp_parse_url( $site_url );

		if ( ! empty( $site_url_parts['path'] ) ) {
			$upload_root = $site_url_parts['path'] . $upload_root;
		}

		$this->upload_root = $upload_root;

		return $upload_root;
	}

	/**
	 * Retrieve WPForms rule block.
	 *
	 * @since 1.9.0
	 *
	 * @param bool $wrap True if the rule block should be wrapped by comments.
	 *
	 * @return string
	 */
	public function get_rule_block( bool $wrap = true ): string {

		$rule_block  = 'User-agent: *' . PHP_EOL;
		$rule_block .= 'Disallow: ' . $this->get_upload_root();

		if ( $wrap ) {
			$rule_block  = PHP_EOL . '# START WPFORMS BLOCK' . PHP_EOL . '# ---------------------------' . PHP_EOL . $rule_block;
			$rule_block .= PHP_EOL . '# ---------------------------' . PHP_EOL . '# END WPFORMS BLOCK' . PHP_EOL;
		}

		/**
		 * Filters WPForms rule block for the robots.txt output.
		 *
		 * @since 1.9.0
		 *
		 * @param string $rule_block WPForms rule block that will be added to the robots.txt.
		 */
		return (string) apply_filters( 'wpforms_pro_robots_get_rule_block', $rule_block );
	}
}
