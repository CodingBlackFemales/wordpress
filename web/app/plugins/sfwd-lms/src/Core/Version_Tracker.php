<?php
/**
 * Version_Tracker class
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core;

/**
 * Version_Tracker class that assists tracking the plugin versions and notifies listeners on changes.
 *
 * @since 4.21.0
 */
class Version_Tracker {
	/**
	 * Contains the key for the WordPress option for our version data.
	 *
	 * @since 4.21.0
	 *
	 * @return string The option key for version data.
	 */
	public static function option_key() {
		return 'learndash_version_tracking';
	}

	/**
	 * Will sync the version in our database based on the passed version,
	 * firing appropriate update hooks to notify the application of the version applied.
	 *
	 * Note: this integrates with the existing data inside Learndash_Admin_Data_Upgrades::init_data_settings.
	 *
	 * @since 4.21.0
	 *
	 * @param string $version The version we are updating to. Defaults to LEARNDASH_VERSION.
	 *
	 * @return void
	 */
	public static function sync_version( string $version = LEARNDASH_VERSION ): void {
		/**
		 * Initialize version tracking data.
		 *
		 * @var array{ current_version: ?string, version_history: ?array<int,string> } $version_fields The tracked version fields.
		 */
		$version_fields  = get_option( self::option_key(), [] );
		$current_version = $version_fields['current_version'] ?? null;
		$version_history = $version_fields['version_history'] ?? [];
		$is_new          = empty( $current_version );

		// If nothing changed, we can bail.
		if (
			! $is_new
			&& version_compare( $version, $current_version, 'eq' )
		) {
			return;
		}

		// Fire upgrade/downgrade hooks.
		// This will run if the version if bumped or if there is no "current version" such as during a new install.
		if (
			$is_new
			|| version_compare( $version, $current_version, '>' )
		) {
			/**
			 * Action that runs whenever the plugin version moves forward.
			 *
			 * @since 4.21.0
			 *
			 * @param null|string $current_version The version the plugin is currently set at.
			 * @param string      $version         The version the plugin is moving to.
			 */
			do_action( 'learndash_version_upgraded', $current_version, $version );

			/**
			 * Action that runs whenever the plugin version moves forward, for a specific version.
			 *
			 * @since 4.21.0
			 *
			 * @param null|string $current_version The version the plugin is currently set at.
			 * @param string      $version         The version the plugin is moving to.
			 */
			do_action( "learndash_version_upgraded_to_$version", $current_version, $version );
		} elseif ( version_compare( $version, $current_version, '<' ) ) {
			/**
			 * Action that runs whenever the plugin version moves backward.
			 *
			 * @since 4.21.0
			 *
			 * @param string $current_version The version the plugin is currently set at.
			 * @param string $version         The version the plugin is moving to.
			 */
			do_action( 'learndash_version_downgraded', $current_version, $version );

			/**
			 * Action that runs whenever the plugin version moves backward, for a specific version.
			 *
			 * @since 4.21.0
			 *
			 * @param string $current_version The version the plugin is currently set at.
			 * @param string $version         The version the plugin is moving to.
			 */
			do_action( "learndash_version_downgraded_to_$version", $current_version, $version );
		}

		// Append history with the change.
		$version_history[ time() ] = $version;

		// Merge, so we can focus on the current values we support here.
		$version_fields = wp_parse_args(
			[
				'current_version' => $version,
				'prior_version'   => $current_version,
				'version_history' => $version_history,
			],
			$version_fields
		);

		update_option( self::option_key(), $version_fields );
	}

	/**
	 * Returns the list of versions we have loaded previously.
	 *
	 * @since 4.21.0
	 *
	 * @return array<int, string> The version history list.
	 */
	public static function get_version_history(): array {
		/**
		 * The version data we have stored.
		 *
		 * @var array{version_history: ?array<int, string> } $version_data The version data we have stored.
		 */
		$version_data = get_option( self::option_key(), [] );

		return $version_data['version_history'] ?? [];
	}

	/**
	 * Sets the version history in the database.
	 *
	 * @since 4.21.0
	 *
	 * @param array<int, string> $history The version history for this plugin.
	 *
	 * @return void
	 */
	public static function set_version_history( array $history ): void {
		// Merge our data together.
		$data                    = (array) get_option( self::option_key(), [] );
		$data['version_history'] = $history;

		// Now update database.
		update_option( self::option_key(), $data );
	}

	/**
	 * Checks if the supplied version has been operated on in the past.
	 * This is useful to ensure a particular version has run previously to avoid re-running seed or migration operations.
	 *
	 * @since 4.21.0
	 *
	 * @param string $version The version we are comparing.
	 *
	 * @return bool
	 */
	public static function has_upgraded( string $version ): bool {
		$versions = self::get_version_history();
		foreach ( $versions as $prior_version ) {
			if ( version_compare( $prior_version, $version, '>=' ) ) {
				return true;
			}
		}

		return false;
	}
}
