<?php
/*
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

/**
 * Basic user endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class BasicUser extends BaculumAPIServer {

	public function get() {
		$user = $this->Request->contains('id') ? $this->Request['id'] : 0;
		$basic_apiuser = $this->getModule('basic_apiuser');
		$username = $basic_apiuser->validateUsername($user) ? $user : null;
		if (is_string($username)) {
			$basic_config = $this->getModule('basic_config')->getConfig($username);
			$basic_cfg = $basic_apiuser->getUserCfg($username);
			if (count($basic_cfg) > 0) {
				$this->output = array_merge([
					'username' => $username,
					'bconsole_cfg_path' => ''
				], $basic_config);
				$this->error = BasicUserError::ERROR_NO_ERRORS;
			} else {
				$this->output = BasicUserError::MSG_ERROR_BASIC_USER_DOES_NOT_EXIST;
				$this->error = BasicUserError::ERROR_BASIC_USER_DOES_NOT_EXIST;
			}
		} else {
			$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_USERNAME;
			$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_USERNAME;
		}
	}

	public function create($params) {
		$basic_apiuser = $this->getModule('basic_apiuser');
		$basic_config = $this->getModule('basic_config');
		$misc = $this->getModule('misc');
		$basic_cfg = $basic_apiuser->getUsers();
		$username = '';
		$password = '';
		$props = [];

		if (property_exists($params, 'username') && key_exists($params->username, $basic_cfg)) {
			$this->output = BasicUserError::MSG_ERROR_BASIC_USER_ALREADY_EXISTS;
			$this->error = BasicUserError::ERROR_BASIC_USER_ALREADY_EXISTS;
			return;
		}

		if (property_exists($params, 'username') && $basic_apiuser->validateUsername($params->username)) {
			$username = $params->username;
		} else {
			$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_USERNAME;
			$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_USERNAME;
			return;
		}

		if (property_exists($params, 'password')) {
			$password = $params->password;
		} else {
			$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_PASSWORD;
			$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_PASSWORD;
			return;
		}

		if (property_exists($params, 'bconsole_cfg_path')) {
			if ($misc->isValidPath($params->bconsole_cfg_path)) {
				$props['bconsole_cfg_path'] = $params->bconsole_cfg_path;
			} else {
				$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_BCONSOLE_CFG_PATH;
				$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_BCONSOLE_CFG_PATH;
				return;
			}
		} else {
			$props['bconsole_cfg_path'] = '';
		}

		if (property_exists($params, 'console') && property_exists($params, 'director')) {
			if (!$misc->isValidName($params->console)) {
				$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_CONSOLE;
				$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_CONSOLE;
				return;
			}
			if (!$misc->isValidName($params->director)) {
				$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_DIRECTOR;
				$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_DIRECTOR;
				return;
			}
			$bs = $this->getModule('bacula_setting');

			$dir_cfg = $bs->getConfig('bcons', 'Director', $params->director);
			if ($dir_cfg['exitcode'] != 0) {
				$this->output = $dir_cfg['output'];
				$this->error = BasicUserError::ERROR_INTERNAL_ERROR;
				return;
			}

			$console_cfg = $bs->getConfig('dir', 'Console', $params->console);
			if ($console_cfg['exitcode'] != 0) {
				$this->output = $console_cfg['output'];
				$this->error = BasicUserError::ERROR_INTERNAL_ERROR;
				return;
			}

			$cfg = [
				[
					'Director' => [
						'Name' => '"' . $dir_cfg['output']['Name'] . '"',
						'DirPort' => $dir_cfg['output']['DirPort'],
						'Address' => $dir_cfg['output']['Address'],
						'Password' => 'XXXX'
					],
					'Console' => [
						'Name' => '"' . $console_cfg['output']['Name'] . '"',
						'Password' => '"' . $console_cfg['output']['Password'] . '"'
					]
				]
			];
			$json_tools = $this->getModule('api_config')->getConfig('jsontools');
			$dir = $json_tools['bconfig_dir'];
			$file = sprintf('%s/bconsole-%s.cfg', $dir, $console_cfg['output']['Name']);
			$this->getModule('bacula_config')->setConfig('bcons', $cfg, $file);
			$props['bconsole_cfg_path'] = $file;
		}

		// save config
		$result = $basic_config->addUser($username, $password, $props);

		if ($result) {
			$this->output = $props;
			$this->error = BasicUserError::ERROR_NO_ERRORS;
		} else {
			$this->output = BasicUserError::MSG_ERROR_INTERNAL_ERROR;
			$this->error = BasicUserError::ERROR_INTERNAL_ERROR;
		}
	}

	public function set($id, $params) {
		$basic_apiuser = $this->getModule('basic_apiuser');
		$basic_config = $this->getModule('basic_config');
		$misc = $this->getModule('misc');
		$basic_cfg = $basic_apiuser->getUsers();
		$username = '';
		$password = '';
		$props = [];

		if (property_exists($params, 'username') && !key_exists($params->username, $basic_cfg)) {
			$this->output = BasicUserError::MSG_ERROR_BASIC_USER_DOES_NOT_EXIST;
			$this->error = BasicUserError::ERROR_BASIC_USER_DOES_NOT_EXIST;
			return;
		}

		if (property_exists($params, 'username') && $basic_apiuser->validateUsername($params->username)) {
			$username = $params->username;
		} else {
			$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_USERNAME;
			$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_USERNAME;
			return;
		}

		if (property_exists($params, 'password')) {
			$password = $params->password;
		} else {
			$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_PASSWORD;
			$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_PASSWORD;
			return;
		}

		if (property_exists($params, 'bconsole_cfg_path')) {
			if ($misc->isValidPath($params->bconsole_cfg_path)) {
				$props['bconsole_cfg_path'] = $params->bconsole_cfg_path;
			} else {
				$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_BCONSOLE_CFG_PATH;
				$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_BCONSOLE_CFG_PATH;
				return;
			}
		} else {
			$props['bconsole_cfg_path'] = '';
		}

		if (property_exists($params, 'console') && property_exists($params, 'director')) {
			if (!$misc->isValidName($params->console)) {
				$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_CONSOLE;
				$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_CONSOLE;
				return;
			}
			if (!$misc->isValidName($params->director)) {
				$this->output = BasicUserError::MSG_ERROR_BASIC_USER_INVALID_DIRECTOR;
				$this->error = BasicUserError::ERROR_BASIC_USER_INVALID_DIRECTOR;
				return;
			}
			$bs = $this->getModule('bacula_setting');

			$dir_cfg = $bs->getConfig('bcons', 'Director', $params->director);
			if ($dir_cfg['exitcode'] != 0) {
				$this->output = $dir_cfg['output'];
				$this->error = BasicUserError::ERROR_INTERNAL_ERROR;
				return;
			}

			$console_cfg = $bs->getConfig('dir', 'Console', $params->console);
			if ($console_cfg['exitcode'] != 0) {
				$this->output = $console_cfg['output'];
				$this->error = BasicUserError::ERROR_INTERNAL_ERROR;
				return;
			}

			$cfg = [
				[
					'Director' => [
						'Name' => '"' . $dir_cfg['output']['Name'] . '"',
						'DirPort' => $dir_cfg['output']['DirPort'],
						'Address' => $dir_cfg['output']['Address'],
						'Password' => 'XXXX'
					],
					'Console' => [
						'Name' => '"' . $console_cfg['output']['Name'] . '"',
						'Password' => '"' . $console_cfg['output']['Password'] . '"'
					]
				]
			];
			$json_tools = $this->getModule('api_config')->getConfig('jsontools');
			$dir = $json_tools['bconfig_dir'];
			$file = sprintf('%s/bconsole-%s.cfg', $dir, $console_cfg['output']['Name']);
			$this->getModule('bacula_config')->setConfig('bcons', $cfg, $file);
			$props['bconsole_cfg_path'] = $file;
		}

		// save config
		$result = $basic_config->editUser($username, $password, $props);

		if ($result) {
			$this->output = $props;
			$this->error = BasicUserError::ERROR_NO_ERRORS;
		} else {
			$this->output = BasicUserError::MSG_ERROR_INTERNAL_ERROR;
			$this->error = BasicUserError::ERROR_INTERNAL_ERROR;
		}
	}

	public function remove($id) {
		$user_cfg = $this->getModule('basic_apiuser')->getUserCfg($id);
		if (count($user_cfg) > 0) {
			$result = $this->getModule('basic_config')->removeUser($id);
			if ($result) {
				$this->output = [];
				$this->error = BasicUserError::ERROR_NO_ERRORS;
			} else {
				$this->output = BasicUserError::MSG_ERROR_INTERNAL_ERROR;
				$this->error = BasicUserError::ERROR_INTERNAL_ERROR;
			}
		} else {
			$this->output = BasicUserError::MSG_ERROR_BASIC_USER_DOES_NOT_EXIST;
			$this->error = BasicUserError::ERROR_BASIC_USER_DOES_NOT_EXIST;
		}
	}
}
?>
