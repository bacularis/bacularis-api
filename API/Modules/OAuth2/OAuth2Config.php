<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2019 Kern Sibbald
 *
 * The main author of Baculum is Marcin Haba.
 * The original author of Bacula is Kern Sibbald, with contributions
 * from many others, a complete list can be found in the file AUTHORS.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 *
 * This notice must be preserved when any source code is
 * conveyed and/or propagated.
 *
 * Bacula(R) is a registered trademark of Kern Sibbald.
 */

namespace Bacularis\API\Modules\OAuth2;

use Bacularis\Common\Modules\ConfigFileModule;
use Bacularis\Common\Modules\Logging;

/**
 * Manage OAuth2 client configuration.
 * Module is responsible for get/set OAuth2 client config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Authorization
 */
class OAuth2Config extends ConfigFileModule
{
	/**
	 * OAuth2 client config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.API.Config.oauth2';

	/**
	 * OAuth2 client config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * These options are obligatory for OAuth2 config.
	 */
	private $required_options = ['client_id', 'client_secret', 'redirect_uri', 'scope'];

	/**
	 * New options with default values that has been added later (after creating config).
	 * They are added to config output.
	 *
	 * @see OAuth2Config::setAddedOptions()
	 */
	private $added_options = ['name' => ''];

	/**
	 * Get (read) OAuth2 client config.
	 *
	 * @access public
	 * @param string $section config section name
	 * @return array config
	 */
	public function getConfig($section = null)
	{
		$config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		$is_valid = true;
		if (!is_null($section)) {
			$config = key_exists($section, $config) ? $config[$section] : [];
			$is_valid = $this->validateConfig($config);
		} else {
			foreach ($config as $value) {
				if ($this->validateConfig($value) === false) {
					$is_valid = false;
					break;
				}
			}
		}
		if ($is_valid === false) {
			// no validity, no config
			$config = [];
		} else {
			$this->setAddedOptions($config, $section);
		}
		return $config;
	}

	/**
	 * Get current valid OAuth2 client configuration.
	 *
	 * @param mixed $client_id client identifier
	 * @return null|array current client configuration or null if invalid
	 */
	public function getValidClientConfig($client_id): ?array
	{
		$oauth2 = $this->getModule('oauth2');
		if (!is_string($client_id) || !$oauth2->validateClientId($client_id)) {
			return null;
		}

		$client = $this->getConfig($client_id);
		$required_string_options = [
			'client_id',
			'client_secret',
			'redirect_uri',
			'scope',
			'bconsole_cfg_path'
		];
		for ($i = 0; $i < count($required_string_options); $i++) {
			$option = $required_string_options[$i];
			if (!key_exists($option, $client) || !is_string($client[$option])) {
				return null;
			}
		}

		if ($client['client_id'] !== $client_id || !$oauth2->validateClientSecret($client['client_secret'])) {
			return null;
		}
		$redirect_uri = BaculumOAuth2::normalizeRedirectURI($client['redirect_uri']);
		if (is_null($redirect_uri) || !$oauth2->validateScopes($client['scope'])) {
			return null;
		}

		$misc = $this->getModule('misc');
		if (!$misc->isValidPath($client['bconsole_cfg_path'])) {
			return null;
		}
		$resource_permissions = [
			'dir' => 'dir_res_perm',
			'sd' => 'sd_res_perm',
			'fd' => 'fd_res_perm',
			'bcons' => 'bcons_res_perm'
		];
		foreach ($resource_permissions as $component => $option) {
			if (!key_exists($option, $client)) {
				continue;
			}
			if (!is_array($client[$option]) || !$misc->isValidResourcePermissions($component, $client[$option])) {
				return null;
			}
			foreach ($client[$option] as $permission) {
				if (!is_string($permission) || !in_array($permission, ['ro', 'rw', 'no'], true)) {
					return null;
				}
			}
		}
		return $client;
	}

	/**
	 * Set (save) OAuth2 client config.
	 *
	 * @access public
	 * @param array $config config
	 * @param string $client_id identifier of client whose tokens should be revoked
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config, string $client_id): bool
	{
		$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($result) {
			$token_manager = $this->getModule('oauth2_token');
			$result = $token_manager->revokeClientTokens($client_id);
		}
		return $result;
	}


	/**
	 * Validate API config.
	 * Config validation should be used as early as config data is available.
	 * Validation is done in read/write config methods.
	 *
	 * @access private
	 * @param array $config config
	 * @return bool true if config valid, otherwise false
	 */
	private function validateConfig(array $config = [])
	{
		$is_valid = true;
		/**
		 * Don't use validation from parent class because it logs to file in
		 * case errors and it could cause save to log a private auth params.
		 */
		for ($i = 0; $i < count($this->required_options); $i++) {
			if (!key_exists($this->required_options[$i], $config)) {
				$is_valid = false;
				$emsg = 'Invalid OAuth2 config. Missing ' . $this->required_options[$i] . ' option.';
				Logging::log(
					Logging::CATEGORY_APPLICATION,
					$emsg
				);
				break;
			}
		}
		return $is_valid;
	}

	/**
	 * Add "on the fly" new options to config.
	 * Note, this method should be used after config validation.
	 *
	 * @param array reference $config config to set added options.
	 * @param mixed $section determines if passed all config or only section
	 */
	private function setAddedOptions(&$config, $section = null)
	{
		foreach ($this->added_options as $added_opt => $defval) {
			if (is_null($section)) {
				foreach ($config as $key => $value) {
					if (!key_exists($added_opt, $value)) {
						$config[$key][$added_opt] = $defval;
					}
				}
			} elseif (!key_exists($added_opt, $config)) {
				$config[$added_opt] = $defval;
			}
		}
	}
}
