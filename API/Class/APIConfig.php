<?php
/*
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2020 Kern Sibbald
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

Prado::using('Application.Common.Class.ConfigFileModule');

/**
 * Manage API configuration.
 * Module is responsible for get/set API config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 * @package Baculum API
 */
class APIConfig extends ConfigFileModule {

	/**
	 * Default application language
	 */
	const DEF_LANG = 'en';

	/**
	 * API config file path
	 */
	const CONFIG_FILE_PATH = 'Application.API.Config.api';

	/**
	 * API config file format
	 */
	const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * JSON tool types
	 */
	const JSON_TOOL_DIR_TYPE = 'dir';
	const JSON_TOOL_SD_TYPE = 'sd';
	const JSON_TOOL_FD_TYPE = 'fd';
	const JSON_TOOL_BCONS_TYPE = 'bcons';

	/**
	 * Action types
	 */
	const ACTION_DIR_START = 'dir_start';
	const ACTION_DIR_STOP = 'dir_stop';
	const ACTION_DIR_RESTART = 'dir_restart';
	const ACTION_SD_START = 'sd_start';
	const ACTION_SD_STOP = 'sd_stop';
	const ACTION_SD_RESTART = 'sd_restart';
	const ACTION_FD_START = 'fd_start';
	const ACTION_FD_STOP = 'fd_stop';
	const ACTION_FD_RESTART = 'fd_restart';

	/**
	 * These options are obligatory for API config.
	 */
	private $required_options = array(
		'api' => array('auth_type', 'debug'),
		'db' => array('type', 'name', 'login', 'password', 'ip_addr', 'port', 'path'),
		'bconsole' => array('bin_path', 'cfg_path', 'use_sudo'),
		'jsontools' => array('enabled')
	);

	/**
	 * Get (read) API config.
	 *
	 * @access public
	 * @param string $section config section name
	 * @return array config
	 */
	public function getConfig($section = null) {
		$config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($this->validateConfig($config) === true) {
			if (!is_null($section)) {
				$config = array_key_exists($section, $this->required_options) ? $config[$section] : array();
			}
		} else {
			$config = array();
		}
		return $config;
	}

	/**
	 * Set (save) API config.
	 *
	 * @access public
	 * @param array $config config
	 * @return boolean true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config) {
		$result = false;
		if ($this->validateConfig($config) === true) {
			$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
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
	 * @return boolean true if config valid, otherwise false
	 */
	private function validateConfig(array $config = array()) {
		return $this->isConfigValid($this->required_options, $config, self::CONFIG_FILE_FORMAT, self::CONFIG_FILE_PATH);
	}

	private function getJSONToolTypes() {
		return array(
			self::JSON_TOOL_DIR_TYPE,
			self::JSON_TOOL_SD_TYPE,
			self::JSON_TOOL_FD_TYPE,
			self::JSON_TOOL_BCONS_TYPE
		);
	}

	/**
	 * Check if JSON tools are configured for application.
	 *
	 * @access public
	 * @return boolean true if JSON tools are configured, otherwise false
	 */
	public function isJSONToolsConfigured() {
		$config = $this->getConfig();
		$configured = array_key_exists('jsontools', $config);
		return $configured;
	}

	public function isJSONToolConfigured($tool_type) {
		$configured = false;
		$tool = $this->getJSONToolOptions($tool_type);
		$config = $this->getJSONToolsConfig();
		$is_bin = array_key_exists($tool['bin'], $config) && !empty($config[$tool['bin']]);
		$is_cfg = array_key_exists($tool['cfg'], $config) && !empty($config[$tool['cfg']]);
		if ($is_bin === true && $is_cfg === true) {
			$configured = true;
		}
		return $configured;
	}

	private function getJSONToolOptions($tool_type) {
		$options = array(
			'bin' => "b{$tool_type}json_path",
			'cfg' => "{$tool_type}_cfg_path"
		);
		return $options;
	}

	/**
	 * Check if JSON tools support is enabled.
	 *
	 * @access public
	 * @return boolean true if JSON tools support is enabled, otherwise false
	 */
	public function isJSONToolsEnabled() {
		$enabled = false;
		if ($this->isJSONToolsConfigured() === true) {
			$config = $this->getConfig();
			$enabled = ($config['jsontools']['enabled'] == 1);
		}
		return $enabled;
	}

	/**
	 * Get JSON tools config parameters.
	 *
	 * @return array JSON tools config parameters
	 */
	public function getJSONToolsConfig() {
		$cfg = array();
		if ($this->isJSONToolsConfigured() === true) {
			$config = $this->getConfig();
			$cfg = $config['jsontools'];
		}
		return $cfg;
	}

	public function getJSONToolConfig($tool_type) {
		$tool = array('bin' => '', 'cfg' => '', 'use_sudo' => false);
		$tools = $this->getSupportedJSONTools();
		$config = $this->getJSONToolsConfig();
		if (in_array($tool_type, $tools)) {
			$opt = $this->getJSONToolOptions($tool_type);
			$tool['bin'] = $config[$opt['bin']];
			$tool['cfg'] = $config[$opt['cfg']];
			$tool['use_sudo'] = ($config['use_sudo'] == 1);
		}
		return $tool;
	}

	/**
	 * Save JSON tools config parameters.
	 *
	 * JSON tools config params can be provided in following form:
	 * array(
	 *   'enabled' => true,
	 *   'bconfig_dir' => '/path/config/',
	 *   'use_sudo' => false,
	 *   'bdirjson_path' => '/path1/bdirjson',
	 *   'dir_cfg_path' => '/path2/bacula-dir.conf',
	 *   'bsdjson_path' => '/path1/bsdjson',
	 *   'sd_cfg_path' => '/path2/bacula-sd.conf',
	 *   'bfdjson_path' => '/path1/bfdjson',
	 *   'fd_cfg_path' => '/path2/bacula-fd.conf',
	 *   'bbconsjson_path' => '/path1/bbconsjson',
	 *   'bcons_cfg_path' => '/path2/bconsole.conf'
	 * )
	 *
	 * Please note that there is not required to provide all JSON tools params at once
	 * but they should be provided in pairs (tool and cfg paths).
	 *
	 * @param array $jsontools_config associative array with JSON tools parameters
	 * @return boolean true if JSON tools parameters saved successfully, otherwise false
	 */
	public function saveJSONToolsConfig(array $jsontools_config) {
		$saved = false;
		$added = false;
		$config = $this->getConfig();

		if ($this->isJSONToolsConfigured() === false) {
			$config['jsontools'] = array();
		}
		if (array_key_exists('enabled', $jsontools_config)) {
			$config['jsontools']['enabled'] = ($jsontools_config['enabled'] === true) ? 1 : 0;
			$added = true;
		}
		// @TOVERIFY: Check if bconfig_dir will be ever needed and used.
		if (array_key_exists('bconfig_dir', $jsontools_config)) {
			$bconfig_dir = rtrim($jsontools_config['bconfig_dir'], '/');
			$config['jsontools']['bconfig_dir'] = $bconfig_dir;
			$added = true;
		}
		if (array_key_exists('use_sudo', $jsontools_config)) {
			$config['jsontools']['use_sudo'] = ($jsontools_config['use_sudo'] === true) ? 1 : 0;
			$added = true;
		}

		$types = $this->getJSONToolTypes();
		for ($i = 0; $i < count($types); $i++) {
			$opt = $this->getJSONToolOptions($types[$i]);
			if (array_key_exists($opt['bin'], $jsontools_config) && array_key_exists($opt['cfg'], $jsontools_config)) {
				$config['jsontools'][$opt['bin']] = $jsontools_config[$opt['bin']];
				$config['jsontools'][$opt['cfg']] = $jsontools_config[$opt['cfg']];
				$added = true;
			}
		}

		if ($added === true) {
			$saved = $this->setConfig($config);
		}
		return $saved;
	}

	public function getSupportedJSONTools() {
		$tools = array();
		$types = $this->getJSONToolTypes();
		for ($i = 0; $i < count($types); $i++) {
			if ($this->isJSONToolConfigured($types[$i]) === true) {
				array_push($tools, $types[$i]);
			}
		}
		return $tools;
	}

	/**
	 * Get action types.
	 *
	 * @return array action types
	 */
	public function getActionTypes() {
		return array(
			self::ACTION_DIR_START,
			self::ACTION_DIR_STOP,
			self::ACTION_DIR_RESTART,
			self::ACTION_SD_START,
			self::ACTION_SD_STOP,
			self::ACTION_SD_RESTART,
			self::ACTION_FD_START,
			self::ACTION_FD_STOP,
			self::ACTION_FD_RESTART
		);
	}

	/**
	 * Check if Actions are configured for application.
	 *
	 * @return boolean true if Actions are configured, otherwise false
	 */
	public function isActionsConfigured() {
		$config = $this->getConfig();
		return key_exists('actions', $config);
	}

	/**
	 * Check if single action is configured for application.
	 *
	 * @return boolean true if single action is configured, otherwise false
	 */
	public function isActionConfigured($action_type) {
		$configured = false;
		$config = $this->getActionsConfig();
		return (key_exists($action_type, $config) && !empty($config[$action_type]));
	}

	/**
	 * Check if Actions support is enabled.
	 *
	 * @return boolean true if Actions support is enabled, otherwise false
	 */
	public function isActionsEnabled() {
		$enabled = false;
		if ($this->isActionsConfigured() === true) {
			$config = $this->getConfig();
			$enabled = ($config['actions']['enabled'] == 1);
		}
		return $enabled;
	}

	/**
	 * Get Actions config parameters.
	 *
	 * @return array Actions config parameters
	 */
	public function getActionsConfig() {
		$cfg = array();
		if ($this->isActionsConfigured() === true) {
			$config = $this->getConfig();
			$cfg = $config['actions'];
		}
		return $cfg;
	}

	/**
	 * Get single action command and sudo option.
	 *
	 * @param string $action_type action type (dir_start, dir_stop ...etc.)
	 * @return array command and sudo option state
	 */
	public function getActionConfig($action_type) {
		$action = array('cmd' => '', 'use_sudo' => false);
		$actions = $this->getSupportedActions();
		$config = $this->getActionsConfig();
		if (in_array($action_type, $actions) && $this->isActionConfigured($action_type) === true) {
			$action['cmd'] = $config[$action_type];
			$action['use_sudo'] = ($config['use_sudo'] == 1);
		}
		return $action;
	}

	/**
	 * Get supported actions defined in API config.
	 *
	 * @return array supported actions
	 */
	public function getSupportedActions() {
		$actions = array();
		$types = $this->getActionTypes();
		for ($i = 0; $i < count($types); $i++) {
			if ($this->isActionConfigured($types[$i]) === true) {
				array_push($actions, $types[$i]);
			}
		}
		return $actions;
	}
}
