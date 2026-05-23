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

use Bacularis\Common\Modules\Cloud\Amazon\Amazon;
use Bacularis\Common\Modules\Cloud\Amazon\Account as AmazonAccount;
use Bacularis\Common\Modules\Cloud\Amazon\EC2\EC2 as AmazonEC2;
use Bacularis\Common\Modules\ConfigFileModule;

/**
 * Manage Amazon account configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class AccountConfig extends ConfigFileModule
{
	/**
	 * Amazon account config file path.
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.API.Config.amazon_accounts';

	/**
	 * Amazon account config file format.
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Supported AWS CLI configuration options.
	 */
	private const CONFIG_OPTIONS = [
		'description',
		'access_method',
		'access_key',
		'secret_key',
		'session_token',
		'role_access_type',
		'role_access_key',
		'role_secret_key',
		'role_service',
		'role_arn',
		'region',
		'output',
		'enabled'
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
			$host_config['name'] = $host;
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
	public function setConfig(array $config): bool
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
	 * @param string $name account name
	 * @return array account config
	 */
	public function getAccountConfig(string $name): array
	{
		$account_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$account_config = $config[$name];
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
		$this->filterAccountOptions($account_config);
		$config[$name] = $account_config;
		$result = $this->setConfig($config);
		if ($result) {
			if ($config[$name]['enabled'] == 1) {
				// Update AWS CLI configuration
				$result = $this->updateAWSCLIConfig($name, $config[$name]);
			} else {
				// Delete AWS CLI configuration
				$this->deleteAWSCLIConfig($name);
			}
		}
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
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$this->filterAccountOptions($opts);
			$config[$name] = array_merge($config[$name], $opts);
			$result = $this->setConfig($config);
			if ($result) {
				if ($config[$name]['enabled'] == 1) {
					// Update AWS CLI configuration
					$result = $this->updateAWSCLIConfig($name, $config[$name]);
				} else {
					// Delete AWS CLI configuration
					$this->deleteAWSCLIConfig($name);
				}
			}
		}
		return $result;
	}

	/**
	 * Delete account config.
	 *
	 * @param string $name account name
	 * @return bool true on success, false otherwise
	 */
	public function deleteAccountConfig(string $name): bool
	{
		$result = false;
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			unset($config[$name]);
			$result = $this->setConfig($config);
			if ($result) {
				// Delete account from AWS CLI configuration
				$result = $this->deleteAWSCLIConfig($name);
			}
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
		$config = $this->getConfig();
		return key_exists($name, $config);
	}

	/**
	 * Filter account configuration options.
	 * Returned are the supported options only.
	 *
	 * @param array $config account configuration option reference
	 */
	private function filterAccountOptions(array &$config): void
	{
		$config = array_filter(
			$config,
			fn ($opt) => in_array($opt, self::CONFIG_OPTIONS),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Update and synchronize AWS CLI configuration.
	 *
	 * @param string $account account name
	 * @param array $config account configuration option reference
	 * @return bool true on success, false otherwise
	 */
	private function updateAWSCLIConfig(string $account, array $config): bool
	{
		$options = [];
		if (key_exists('access_method', $config)) {
			if ($config['access_method'] === AmazonAccount::ACCOUNT_ACCESS_METHOD_STATIC_CREDENTIALS) {
				// Static keys access method
				if ($config['role_access_type'] === AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_ROLE) {
					// IAM user role provides all required access
					$options['aws_access_key_id'] = $config['access_key'];
					$options['aws_secret_access_key'] = $config['secret_key'];
				} elseif ($config['role_access_type'] === AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_SERVICE) {
					// Service IAM role provides all required access
					// nothing to write
				}
			} elseif ($config['access_method'] === AmazonAccount::ACCOUNT_ACCESS_METHOD_ASSUME_ROLE) {
				// STS Assume Role access method
				$options['role_arn'] = $config['role_arn'] ?? '';
				if ($config['role_access_type'] === AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_ROLE) {
					// Access keys
					$options['aws_access_key_id'] = $config['role_access_key'];
					$options['aws_secret_access_key'] = $config['role_secret_key'];
					$options['source_profile'] = $account;
				} elseif ($config['role_access_type'] === AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_SERVICE) {
					// Instance role
					if ($config['role_service'] === AmazonEC2::SERVICE_NAME) {
						$options['credential_source'] = AmazonAccount::CREDENTIAL_SOURCE_EC2_METADATA;
					}
				}
			}
		}

		if (key_exists('region', $config)) {
			$options['region'] = $config['region'];
		}

		// JSON is standard Bacularis format
		$options['output'] = AWSCLIConfig::OUTPUT_FORMAT_JSON;

		// Save single account configuration
		$caacc = $this->getModule('cloud_amazon_aws_cli_config');
		$result = $caacc->setAccountConfig($account, $options);
		return $result;
	}

	/**
	 * Delete AWS CLI account.
	 *
	 * @param string $account AWS CLI configuration account
	 * @return bool true on success, false otherwise
	 */
	private function deleteAWSCLIConfig(string $account): bool
	{
		$caacc = $this->getModule('cloud_amazon_aws_cli_config');
		$result = $caacc->deleteAccountConfig($account);
		return $result;
	}
}
