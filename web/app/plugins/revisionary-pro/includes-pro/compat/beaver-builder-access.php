<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Manages Beaver Builder's Builder Access entry for the Revisor role.
 */
class RevisionaryBeaverBuilderAccess
{
	const OPTION = 'beaver_builder_revisor_access';
	const SNAPSHOT_OPTION = 'rvy_beaver_builder_revisor_access_original';

	public static function init()
	{
		add_action('after_setup_theme', [__CLASS__, 'sync_from_saved_setting'], 99);
		add_action('updated_option', [__CLASS__, 'actOptionUpdated'], 10, 3);
		add_action('added_option', [__CLASS__, 'actOptionAdded'], 10, 2);
		add_action('deleted_option', [__CLASS__, 'actOptionDeleted'], 10, 1);
		add_action('updated_site_option', [__CLASS__, 'actSiteOptionUpdated'], 10, 4);
		add_action('added_site_option', [__CLASS__, 'actSiteOptionAdded'], 10, 3);
		add_action('deleted_site_option', [__CLASS__, 'actSiteOptionDeleted'], 10, 2);
	}

	public static function actOptionUpdated($option, $old_value, $value)
	{
		if ('rvy_' . self::OPTION === $option) {
			self::sync((bool) $value);
		}
	}

	public static function actOptionAdded($option, $value)
	{
		self::actOptionUpdated($option, null, $value);
	}

	public static function actOptionDeleted($option)
	{
		if ('rvy_' . self::OPTION === $option) {
			self::refreshRevisionOptions();
			self::sync(self::settingEnabled());
		}
	}

	public static function actSiteOptionUpdated($option, $value, $old_value, $network_id)
	{
		if ('rvy_' . self::OPTION === $option) {
			self::sync((bool) $value);
		}
	}

	public static function actSiteOptionAdded($option, $value, $network_id)
	{
		self::actSiteOptionUpdated($option, $value, null, $network_id);
	}

	public static function actSiteOptionDeleted($option, $network_id)
	{
		if ('rvy_' . self::OPTION === $option) {
			self::refreshRevisionOptions();
			self::sync(self::settingEnabled());
		}
	}

	public static function sync_from_saved_setting()
	{
		self::refreshRevisionOptions();
		self::sync(self::settingEnabled());
	}

	public static function on_activation()
	{
		self::refreshRevisionOptions();
		self::sync(self::settingEnabled());
	}

	public static function on_deactivation()
	{
		self::restore_original_access();
	}

	private static function refreshRevisionOptions()
	{
		if (function_exists('rvy_refresh_options')) {
			rvy_refresh_options();
		} elseif (function_exists('rvy_refresh_default_options')) {
			rvy_refresh_default_options();
		}
	}

	private static function settingEnabled()
	{
		if (function_exists('rvy_get_option')) {
			return (bool) rvy_get_option(self::OPTION);
		}

		return (bool) get_option('rvy_' . self::OPTION, true);
	}

	private static function sync($enabled)
	{
		if ($enabled) {
			self::grant_builder_access();
		} else {
			self::restore_original_access();
		}
	}

	private static function isAvailable()
	{
		return class_exists('FLBuilderUserAccess')
			&& method_exists('FLBuilderUserAccess', 'get_raw_settings')
			&& method_exists('FLBuilderUserAccess', 'save_settings');
	}

	private static function grant_builder_access()
	{
		if (!self::isAvailable()) {
			return;
		}

		$settings = self::getBuilderSettings();
		if (!is_array($settings)) {
			$settings = [];
		}

		$builder_access = isset($settings['builder_access']) && is_array($settings['builder_access'])
			? $settings['builder_access']
			: [];

		if (!is_array(get_option(self::SNAPSHOT_OPTION, false))) {
			update_option(self::SNAPSHOT_OPTION, [
				'revisor' => !empty($builder_access['revisor']),
			]);
		}

		if (!empty($builder_access['revisor'])) {
			return;
		}

		$data = self::prepareSettingsForSave($settings);
		if (!in_array('revisor', $data['builder_access'], true)) {
			$data['builder_access'][] = 'revisor';
		}

		self::saveBuilderSettings($data);
	}

	private static function restore_original_access()
	{
		$snapshot = get_option(self::SNAPSHOT_OPTION, false);
		if (!is_array($snapshot) || !array_key_exists('revisor', $snapshot) || !self::isAvailable()) {
			return;
		}

		$settings = self::getBuilderSettings();
		if (!is_array($settings)) {
			$settings = [];
		}

		$current_builder_access = isset($settings['builder_access']) && is_array($settings['builder_access'])
			? !empty($settings['builder_access']['revisor'])
			: false;
		if ($current_builder_access === (bool) $snapshot['revisor']) {
			delete_option(self::SNAPSHOT_OPTION);
			return;
		}

		$data = self::prepareSettingsForSave($settings);
		$data['builder_access'] = array_values(array_diff($data['builder_access'], ['revisor']));

		if ($snapshot['revisor']) {
			$data['builder_access'][] = 'revisor';
		}

		self::saveBuilderSettings($data);
		delete_option(self::SNAPSHOT_OPTION);
	}

	private static function prepareSettingsForSave($settings)
	{
		$data = [];
		foreach ($settings as $key => $role_settings) {
			if (!is_array($role_settings)) {
				continue;
			}

			$data[$key] = [];
			foreach ($role_settings as $role => $allowed) {
				if ($allowed) {
					$data[$key][] = $role;
				}
			}
		}

		if (!isset($data['builder_access'])) {
			$data['builder_access'] = [];
		}

		return $data;
	}

	private static function getBuilderSettings()
	{
		if (method_exists('FLBuilderUserAccess', 'get_saved_settings')) {
			return FLBuilderUserAccess::get_saved_settings();
		}

		return FLBuilderUserAccess::get_raw_settings();
	}

	private static function saveBuilderSettings($data)
	{
		$had_override = array_key_exists('fl_ua_override_ms', $_POST);					// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$old_override = $had_override ? (bool) $_POST['fl_ua_override_ms'] : null;		// phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Beaver Builder skips writes for multisite settings unless their keys are
		// explicitly marked as local overrides in the current request.
		if (is_multisite() && class_exists('FLBuilderAdminSettings')
		&& method_exists('FLBuilderAdminSettings', 'multisite_support')
		&& FLBuilderAdminSettings::multisite_support() && !is_network_admin()
		) {
			$_POST['fl_ua_override_ms'] = array_keys($data);
		}

		FLBuilderUserAccess::save_settings($data);

		if ($had_override) {
			$_POST['fl_ua_override_ms'] = $old_override;
		} else {
			unset($_POST['fl_ua_override_ms']);
		}
	}
}

RevisionaryBeaverBuilderAccess::init();
