<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

namespace Bacularis\API\Modules\Cloud\Amazon;

use Bacularis\Common\Modules\ConfigFileModule;
use Prado\Prado;

/**
 * Manage AWS CLI configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class AWSCLIConfig extends ConfigFileModule
{
	/**
	 * AWS CLI config file path.
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.API.Config.aws_cli';

	/**
	 * AWS CLI config file format.
	 */
	public const CONFIG_FILE_FORMAT = 'nini';

	/**
	 * AWS CLI supported output formats.
	 */
	public const OUTPUT_FORMAT_JSON = 'json';

	/**
	 * Supported AWS CLI configuration options.
	 */
	private const CONFIG_OPTIONS = [
		'aws_access_key_id',
		'aws_secret_access_key',
		'aws_session_token',
		'role_arn',
		'source_profile',
		'credential_source',
		'region',
		'output'
	];

	/**
	 * Stores device config.
	 */
	private $config;

	/**
	 * Get all accounts config.
	 *
	 * @return array all account config
	 */
	public function getConfig(): array
	{
		$account_config = [];
		if (is_null($this->config)) {
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		}
		foreach ($this->config as $host => $host_config) {
			$account_config[$host] = $host_config;
		}
		return $account_config;
	}

	/**
	 * Set all account config.
	 *
	 * @param array $config config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config)
	{
		$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}

	/**
	 * Get account config.
	 *
	 * @param $name account name
	 * @return array account config
	 */
	public function getAccountConfig(string $name): array
	{
		$account_config = [];
		$profile = self::getProfileName($name);
		$config = $this->getConfig();
		if (key_exists($profile, $config)) {
			$account_config = $config[$profile];
		}
		return $account_config;
	}

	/**
	 * Set account config.
	 *
	 * @param string $name account name
	 * @param array $account_config account configuration
	 * @return bool true if account config saved successfully, otherwise false
	 */
	public function setAccountConfig(string $name, array $account_config): bool
	{
		$config = $this->getConfig();
		self::filterAccountOptions($account_config);
		$profile = self::getProfileName($name);
		$config[$profile] = $account_config;
		$result = $this->setConfig($config);
		return $result;
	}

	/**
	 * Update account config.
	 *
	 * @param string $name account name
	 * @param array $opts selected config options to update
	 * @return bool true if account config saved successfully, otherwise false
	 */
	public function updateAccountConfig(string $name, array $opts): bool
	{
		$result = false;
		$profile = self::getProfileName($name);
		$config = $this->getConfig();
		if (key_exists($profile, $config)) {
			self::filterAccountOptions($opts);
			$config[$profile] = array_merge($config[$profile], $opts);
			$result = $this->setConfig($config);
		}
		return $result;
	}

	/**
	 * Delete account config.
	 *
	 * @param $name account name
	 * @return bool true on success, false otherwise
	 */
	public function deleteAccountConfig(string $name): bool
	{
		$result = false;
		$profile = self::getProfileName($name);
		$config = $this->getConfig();
		if (key_exists($profile, $config)) {
			unset($config[$profile]);
			$result = $this->setConfig($config);
		}
		return $result;
	}

	/**
	 * Check if account config exists.
	 *
	 * @param $name account name
	 * @return bool true on success, false otherwise
	 */
	public function accountConfigExists(string $name): bool
	{
		$profile = self::getProfileName($name);
		$config = $this->getConfig();
		return key_exists($profile, $config);
	}

	/**
	 * Filter account configuration options.
	 * Returned are the supported options only.
	 *
	 * @param $config account configuration option reference
	 */
	private static function filterAccountOptions(array &$config): void
	{
		$config = array_filter(
			$config,
			fn ($opt) => in_array($opt, self::CONFIG_OPTIONS),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Get AWS CLI profile name by account name.
	 *
	 * @param string $name account name
	 * @return string profile name
	 */
	private static function getProfileName(string $name): string
	{
		return "profile $name";
	}

	/**
	 * Get AWS CLI configuration file path.
	 */
	public static function getConfigFilePath(): string
	{
		return Prado::getPathOfNamespace(
			self::CONFIG_FILE_PATH,
			static::CONFIG_FILE_EXT
		);
	}
}
