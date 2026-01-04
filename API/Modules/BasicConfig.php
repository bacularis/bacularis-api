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
 * Copyright (C) 2013-2021 Kern Sibbald
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

namespace Bacularis\API\Modules;

use Bacularis\Common\Modules\ConfigFileModule;
use Bacularis\Common\Modules\Logging;

/**
 * Manage Basic user configuration.
 * Module is responsible for get/set Basic user config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Authorization
 */
class BasicConfig extends ConfigFileModule
{
	/**
	 * Basic user config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.API.Config.basic';

	/**
	 * Basic user config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * These options are obligatory for Basic config.
	 */
	private $required_options = [];

	/**
	 * Stores basic user config content.
	 */
	private $config;

	/**
	 * Get (read) Basic user config.
	 *
	 * @access public
	 * @param string $section config section name
	 * @return array config
	 */
	public function getConfig($section = null)
	{
		$config = [];
		if (is_null($this->config)) {
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		}
		$is_valid = true;
		if (!is_null($section)) {
			$config = key_exists($section, $this->config) ? $this->config[$section] : [];
			$is_valid = $this->validateConfig($config);
		} else {
			foreach ($this->config as $username => $value) {
				if ($this->validateConfig($value) === false) {
					$is_valid = false;
					break;
				}
				$config[$username] = $value;
			}
		}
		if ($is_valid === false) {
			// no validity, no config
			$config = [];
		}
		return $config;
	}

	/**
	 * Set (save) Basic user config.
	 *
	 * @access public
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
	 * Validate API config.
	 * Config validation should be used as early as config data is available.
	 * Validation is done in read config method.
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
				$emsg = 'Invalid Basic user config. Missing ' . $this->required_options[$i] . ' option.';
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
	 * Get users.
	 * NOTE: User list bases on basic password file.
	 * @see BasicAPIUserConfig
	 *
	 * @param string $username username to get user config
	 * @return array basic user list
	 */
	public function getUsers($username = null)
	{
		$basic_users = [];
		$basic_apiuser = $this->getModule('basic_apiuser')->getUsers();
		$basic_config = $this->getConfig();
		foreach ($basic_apiuser as $user => $pwd) {
			$bconsole_cfg_path = '';
			$dir_res_perm = [];
			$sd_res_perm = [];
			$fd_res_perm = [];
			$bcons_res_perm = [];
			if (key_exists($user, $basic_config)) {
				if (key_exists('bconsole_cfg_path', $basic_config[$user])) {
					$bconsole_cfg_path = $basic_config[$user]['bconsole_cfg_path'];
				}
				if (key_exists('dir_res_perm', $basic_config[$user])) {
					$dir_res_perm = $basic_config[$user]['dir_res_perm'];
				}
				if (key_exists('sd_res_perm', $basic_config[$user])) {
					$sd_res_perm = $basic_config[$user]['sd_res_perm'];
				}
				if (key_exists('fd_res_perm', $basic_config[$user])) {
					$fd_res_perm = $basic_config[$user]['fd_res_perm'];
				}
				if (key_exists('bcons_res_perm', $basic_config[$user])) {
					$bcons_res_perm = $basic_config[$user]['bcons_res_perm'];
				}
			}
			$basic_users[$user] = [
				'username' => $user,
				'bconsole_cfg_path' => $bconsole_cfg_path,
				'dir_res_perm' => $dir_res_perm,
				'sd_res_perm' => $sd_res_perm,
				'fd_res_perm' => $fd_res_perm,
				'bcons_res_perm' => $bcons_res_perm
			];
		}
		$ret = array_values($basic_users);
		if (is_string($username) && key_exists($username, $basic_users)) {
			$ret = $basic_users[$username];
		}
		return $ret;
	}

	/**
	 * Add single basic user to config.
	 * NOTE: Basic password hashes are stored in separate file.
	 * @see BasicAPIUserConfig
	 *
	 * @param string $username user name
	 * @param string $password password
	 * @param array $params user properties
	 * @param array $props
	 * @return bool true on success, otherwise false
	 */
	public function addUser($username, $password, array $props)
	{
		$success = false;
		$config = $this->getConfig();
		if (!key_exists($username, $config)) {
			$config[$username] = $props;
			$success = $this->setConfig($config);
		}
		if ($success) {
			// Set password in the password file
			$success = $this->getModule('basic_apiuser')->setUsersConfig(
				$username,
				$password
			);
		}
		// TODO: Add rollback and locking
		return $success;
	}

	/**
	 * Edit single basic user.
	 *
	 * @param string $username user name
	 * @param string $password password
	 * @param array $params user properties
	 * @param array $props
	 * @return bool true on success, otherwise false
	 */
	public function editUser($username, $password, array $props = [])
	{
		$success = false;
		$config = $this->getConfig();
		if (key_exists($username, $config)) {
			// User exists, so edit him
			$config[$username] = array_merge($config[$username], $props);
			$success = $this->setConfig($config);
		} else {
			// User does not exists, so add him.
			// NOTE: Not all users with password defined are in config file.
			$config[$username] = $props;
			$success = $this->setConfig($config);
		}
		if ($success && !empty($password)) {
			// Update password in the password file
			$success = $this->getModule('basic_apiuser')->setUsersConfig(
				$username,
				$password
			);
		}
		// TODO: Add rollback and locking
		return $success;
	}

	/**
	 * Remove single basic user.
	 *
	 * @param string $username user name
	 * @return bool true on success, otherwise false
	 */
	public function removeUser($username)
	{
		$config = $this->getConfig();
		if (key_exists($username, $config)) {
			unset($config[$username]);
			$this->setConfig($config);
		}
		/**
		 * There is returned only state of removing user from password file because
		 * because user can be defined in password file but it does not have
		 * to be defined in basic.conf file. It is for backward compatibility
		 * with config files.
		 */
		$success = $this->getModule('basic_apiuser')->removeUser($username);
		return $success;
	}
}
