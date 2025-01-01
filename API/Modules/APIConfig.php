<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
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

namespace Bacularis\API\Modules;

use Bacularis\Common\Modules\ConfigFileModule;

/**
 * Manage API configuration.
 * Module is responsible for get/set API config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class APIConfig extends ConfigFileModule
{
	/**
	 * Default application language
	 */
	public const DEF_LANG = 'en';

	/**
	 * API config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.API.Config.api';

	/**
	 * API config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * JSON tool types
	 */
	public const JSON_TOOL_DIR_TYPE = 'dir';
	public const JSON_TOOL_SD_TYPE = 'sd';
	public const JSON_TOOL_FD_TYPE = 'fd';
	public const JSON_TOOL_BCONS_TYPE = 'bcons';

	/**
	 * Action types
	 */
	public const ACTION_CAT_START = 'cat_start';
	public const ACTION_CAT_STOP = 'cat_stop';
	public const ACTION_CAT_RESTART = 'cat_restart';
	public const ACTION_DIR_START = 'dir_start';
	public const ACTION_DIR_STOP = 'dir_stop';
	public const ACTION_DIR_RESTART = 'dir_restart';
	public const ACTION_SD_START = 'sd_start';
	public const ACTION_SD_STOP = 'sd_stop';
	public const ACTION_SD_RESTART = 'sd_restart';
	public const ACTION_FD_START = 'fd_start';
	public const ACTION_FD_STOP = 'fd_stop';
	public const ACTION_FD_RESTART = 'fd_restart';

	/**
	 * Software management commands.
	 */
	public const SOFTWARE_MANAGEMENT_BACULARIS_INSTALL = 'bacularis_install';
	public const SOFTWARE_MANAGEMENT_BACULARIS_UPGRADE = 'bacularis_upgrade';
	public const SOFTWARE_MANAGEMENT_BACULARIS_REMOVE = 'bacularis_remove';
	public const SOFTWARE_MANAGEMENT_BACULARIS_INFO = 'bacularis_info';
	public const SOFTWARE_MANAGEMENT_BACULARIS_ENABLE = 'bacularis_enable';
	public const SOFTWARE_MANAGEMENT_PRE_BACULARIS_INSTALL = 'bacularis_pre_install_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_BACULARIS_UPGRADE = 'bacularis_pre_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_BACULARIS_REMOVE = 'bacularis_pre_remove_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_BACULARIS_INFO = 'bacularis_pre_info_cmd';
	public const SOFTWARE_MANAGEMENT_POST_BACULARIS_INSTALL = 'bacularis_post_install_cmd';
	public const SOFTWARE_MANAGEMENT_POST_BACULARIS_UPGRADE = 'bacularis_post_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_POST_BACULARIS_REMOVE = 'bacularis_post_remove_cmd';
	public const SOFTWARE_MANAGEMENT_POST_BACULARIS_INFO = 'bacularis_post_info_cmd';
	public const SOFTWARE_MANAGEMENT_CAT_INSTALL = 'cat_install';
	public const SOFTWARE_MANAGEMENT_CAT_UPGRADE = 'cat_upgrade';
	public const SOFTWARE_MANAGEMENT_CAT_REMOVE = 'cat_remove';
	public const SOFTWARE_MANAGEMENT_CAT_INFO = 'cat_info';
	public const SOFTWARE_MANAGEMENT_CAT_ENABLE = 'cat_enable';
	public const SOFTWARE_MANAGEMENT_PRE_CAT_INSTALL = 'cat_pre_install_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_CAT_UPGRADE = 'cat_pre_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_CAT_REMOVE = 'cat_pre_remove_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_CAT_INFO = 'cat_pre_info_cmd';
	public const SOFTWARE_MANAGEMENT_POST_CAT_INSTALL = 'cat_post_install_cmd';
	public const SOFTWARE_MANAGEMENT_POST_CAT_UPGRADE = 'cat_post_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_POST_CAT_REMOVE = 'cat_post_remove_cmd';
	public const SOFTWARE_MANAGEMENT_POST_CAT_INFO = 'cat_post_info_cmd';
	public const SOFTWARE_MANAGEMENT_DIR_INSTALL = 'dir_install';
	public const SOFTWARE_MANAGEMENT_DIR_UPGRADE = 'dir_upgrade';
	public const SOFTWARE_MANAGEMENT_DIR_REMOVE = 'dir_remove';
	public const SOFTWARE_MANAGEMENT_DIR_INFO = 'dir_info';
	public const SOFTWARE_MANAGEMENT_DIR_ENABLE = 'dir_enable';
	public const SOFTWARE_MANAGEMENT_PRE_DIR_INSTALL = 'dir_pre_install_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_DIR_UPGRADE = 'dir_pre_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_DIR_REMOVE = 'dir_pre_remove_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_DIR_INFO = 'dir_pre_info_cmd';
	public const SOFTWARE_MANAGEMENT_POST_DIR_INSTALL = 'dir_post_install_cmd';
	public const SOFTWARE_MANAGEMENT_POST_DIR_UPGRADE = 'dir_post_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_POST_DIR_REMOVE = 'dir_post_remove_cmd';
	public const SOFTWARE_MANAGEMENT_POST_DIR_INFO = 'dir_post_info_cmd';
	public const SOFTWARE_MANAGEMENT_SD_INSTALL = 'sd_install';
	public const SOFTWARE_MANAGEMENT_SD_UPGRADE = 'sd_upgrade';
	public const SOFTWARE_MANAGEMENT_SD_REMOVE = 'sd_remove';
	public const SOFTWARE_MANAGEMENT_SD_INFO = 'sd_info';
	public const SOFTWARE_MANAGEMENT_SD_ENABLE = 'sd_enable';
	public const SOFTWARE_MANAGEMENT_PRE_SD_INSTALL = 'sd_pre_install_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_SD_UPGRADE = 'sd_pre_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_SD_REMOVE = 'sd_pre_remove_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_SD_INFO = 'sd_pre_info_cmd';
	public const SOFTWARE_MANAGEMENT_POST_SD_INSTALL = 'sd_post_install_cmd';
	public const SOFTWARE_MANAGEMENT_POST_SD_UPGRADE = 'sd_post_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_POST_SD_REMOVE = 'sd_post_remove_cmd';
	public const SOFTWARE_MANAGEMENT_POST_SD_INFO = 'sd_post_info_cmd';
	public const SOFTWARE_MANAGEMENT_FD_INSTALL = 'fd_install';
	public const SOFTWARE_MANAGEMENT_FD_UPGRADE = 'fd_upgrade';
	public const SOFTWARE_MANAGEMENT_FD_REMOVE = 'fd_remove';
	public const SOFTWARE_MANAGEMENT_FD_INFO = 'fd_info';
	public const SOFTWARE_MANAGEMENT_FD_ENABLE = 'fd_enable';
	public const SOFTWARE_MANAGEMENT_PRE_FD_INSTALL = 'fd_pre_install_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_FD_UPGRADE = 'fd_pre_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_FD_REMOVE = 'fd_pre_remove_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_FD_INFO = 'fd_pre_info_cmd';
	public const SOFTWARE_MANAGEMENT_POST_FD_INSTALL = 'fd_post_install_cmd';
	public const SOFTWARE_MANAGEMENT_POST_FD_UPGRADE = 'fd_post_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_POST_FD_REMOVE = 'fd_post_remove_cmd';
	public const SOFTWARE_MANAGEMENT_POST_FD_INFO = 'fd_post_info_cmd';
	public const SOFTWARE_MANAGEMENT_BCONS_INSTALL = 'bcons_install';
	public const SOFTWARE_MANAGEMENT_BCONS_UPGRADE = 'bcons_upgrade';
	public const SOFTWARE_MANAGEMENT_BCONS_REMOVE = 'bcons_remove';
	public const SOFTWARE_MANAGEMENT_BCONS_INFO = 'bcons_info';
	public const SOFTWARE_MANAGEMENT_PRE_BCONS_INSTALL = 'bcons_pre_install_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_BCONS_UPGRADE = 'bcons_pre_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_BCONS_REMOVE = 'bcons_pre_remove_cmd';
	public const SOFTWARE_MANAGEMENT_PRE_BCONS_INFO = 'bcons_pre_info_cmd';
	public const SOFTWARE_MANAGEMENT_POST_BCONS_INSTALL = 'bcons_post_install_cmd';
	public const SOFTWARE_MANAGEMENT_POST_BCONS_UPGRADE = 'bcons_post_upgrade_cmd';
	public const SOFTWARE_MANAGEMENT_POST_BCONS_REMOVE = 'bcons_post_remove_cmd';
	public const SOFTWARE_MANAGEMENT_POST_BCONS_INFO = 'bcons_post_info_cmd';

	/**
	 * These options are obligatory for API config.
	 */
	private $required_options = [
		'api' => ['auth_type', 'debug'],
		'db' => ['type', 'name', 'login', 'password', 'ip_addr', 'port', 'path'],
		'bconsole' => ['bin_path', 'cfg_path', 'use_sudo'],
		'jsontools' => ['enabled']
	];

	/**
	 * Get (read) API config.
	 *
	 * @access public
	 * @param string $section config section name
	 * @return array config
	 */
	public function getConfig($section = null)
	{
		$config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($this->validateConfig($config) === true) {
			if (!is_null($section)) {
				$config = array_key_exists($section, $this->required_options) ? $config[$section] : [];
			}
		} else {
			$config = [];
		}
		return $config;
	}

	/**
	 * Set (save) API config.
	 *
	 * @access public
	 * @param array $config config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config)
	{
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
	 * @return bool true if config valid, otherwise false
	 */
	private function validateConfig(array $config = [])
	{
		return $this->isConfigValid($this->required_options, $config, self::CONFIG_FILE_FORMAT, self::CONFIG_FILE_PATH);
	}

	private function getJSONToolTypes()
	{
		return [
			self::JSON_TOOL_DIR_TYPE,
			self::JSON_TOOL_SD_TYPE,
			self::JSON_TOOL_FD_TYPE,
			self::JSON_TOOL_BCONS_TYPE
		];
	}

	/**
	 * Check if JSON tools are configured for application.
	 *
	 * @access public
	 * @return bool true if JSON tools are configured, otherwise false
	 */
	public function isJSONToolsConfigured()
	{
		$config = $this->getConfig();
		$configured = array_key_exists('jsontools', $config);
		return $configured;
	}

	public function isJSONToolConfigured($tool_type)
	{
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

	private function getJSONToolOptions($tool_type)
	{
		$options = [
			'bin' => "b{$tool_type}json_path",
			'cfg' => "{$tool_type}_cfg_path"
		];
		return $options;
	}

	/**
	 * Check if JSON tools support is enabled.
	 *
	 * @access public
	 * @return bool true if JSON tools support is enabled, otherwise false
	 */
	public function isJSONToolsEnabled()
	{
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
	public function getJSONToolsConfig()
	{
		$cfg = [];
		if ($this->isJSONToolsConfigured() === true) {
			$config = $this->getConfig();
			$cfg = $config['jsontools'];
		}
		return $cfg;
	}

	public function getJSONToolConfig($tool_type)
	{
		$tool = [
			'bin' => '',
			'cfg' => '',
			'sudo' => [
				'use_sudo' => false,
				'user' => '',
				'group' => ''
			]
		];
		$tools = $this->getSupportedJSONTools();
		$config = $this->getJSONToolsConfig();
		if (in_array($tool_type, $tools)) {
			$opt = $this->getJSONToolOptions($tool_type);
			$tool['bin'] = $config[$opt['bin']];
			$tool['cfg'] = $config[$opt['cfg']];
			$tool['sudo'] = [
				'use_sudo' => ((int) $config['use_sudo'] === 1),
				'user' => $config['sudo_user'] ?? '',
				'group' => $config['sudo_group'] ?? ''
			];
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
	 * @return bool true if JSON tools parameters saved successfully, otherwise false
	 */
	public function saveJSONToolsConfig(array $jsontools_config)
	{
		$saved = false;
		$added = false;
		$config = $this->getConfig();

		if ($this->isJSONToolsConfigured() === false) {
			$config['jsontools'] = [];
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

	public function getSupportedJSONTools()
	{
		$tools = [];
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
	public function getActionTypes()
	{
		return [
			self::ACTION_CAT_START,
			self::ACTION_CAT_STOP,
			self::ACTION_CAT_RESTART,
			self::ACTION_DIR_START,
			self::ACTION_DIR_STOP,
			self::ACTION_DIR_RESTART,
			self::ACTION_SD_START,
			self::ACTION_SD_STOP,
			self::ACTION_SD_RESTART,
			self::ACTION_FD_START,
			self::ACTION_FD_STOP,
			self::ACTION_FD_RESTART
		];
	}

	/**
	 * Check if Actions are configured for application.
	 *
	 * @return bool true if Actions are configured, otherwise false
	 */
	public function isActionsConfigured()
	{
		$config = $this->getConfig();
		return key_exists('actions', $config);
	}

	/**
	 * Check if single action is configured for application.
	 *
	 * @param mixed $action_type
	 * @return bool true if single action is configured, otherwise false
	 */
	public function isActionConfigured($action_type)
	{
		$config = $this->getActionsConfig();
		return (key_exists($action_type, $config) && !empty($config[$action_type]));
	}

	/**
	 * Check if Actions support is enabled.
	 *
	 * @return bool true if Actions support is enabled, otherwise false
	 */
	public function isActionsEnabled()
	{
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
	public function getActionsConfig()
	{
		$cfg = [];
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
	public function getActionConfig($action_type)
	{
		$action = [
			'cmd' => '',
			'sudo' => [
				'use_sudo' => false,
				'user' => '',
				'group' => ''
			]
		];
		$actions = $this->getSupportedActions();
		$config = $this->getActionsConfig();
		if (in_array($action_type, $actions) && $this->isActionConfigured($action_type) === true) {
			$action['cmd'] = $config[$action_type];
			$action['sudo'] = [
				'use_sudo' => ((int) $config['use_sudo'] === 1),
				'user' => ($config['sudo_user'] ?? ''),
				'group' => ($config['sudo_group'] ?? '')
			];
		}
		return $action;
	}

	/**
	 * Get supported actions defined in API config.
	 *
	 * @return array supported actions
	 */
	public function getSupportedActions()
	{
		$actions = [];
		$types = $this->getActionTypes();
		for ($i = 0; $i < count($types); $i++) {
			if ($this->isActionConfigured($types[$i]) === true) {
				array_push($actions, $types[$i]);
			}
		}
		return $actions;
	}

	/**
	 * Check if software management is configured for application.
	 *
	 * @return bool true if software management is configured, otherwise false
	 */
	public function isSoftwareManagementConfigured()
	{
		$config = $this->getConfig();
		return key_exists('software_management', $config);
	}

	/**
	 * Check if single software management command is configured for application.
	 *
	 * @param mixed $command
	 * @return bool true if single action is configured, otherwise false
	 */
	public function isSoftwareManagementCommandConfigured($command)
	{
		$config = $this->getSoftwareManagementConfig();
		return (key_exists($command, $config) && !empty($config[$command]));
	}

	/**
	 * Check if software management support is enabled.
	 *
	 * @return bool true if software management support is enabled, otherwise false
	 */
	public function isSoftwareManagementEnabled()
	{
		$enabled = false;
		if ($this->isSoftwareManagementConfigured() === true) {
			$config = $this->getConfig();
			$enabled = ($config['software_management']['enabled'] == 1);
		}
		return $enabled;
	}

	/**
	 * Get software management config parameters.
	 *
	 * @return array software management config parameters
	 */
	public function getSoftwareManagementConfig()
	{
		$cfg = [];
		if ($this->isSoftwareManagementConfigured() === true) {
			$config = $this->getConfig();
			$cfg = $config['software_management'];
		}
		return $cfg;
	}

	/**
	 * Get single software management command and sudo option.
	 *
	 * @param string $command command (dir_install, dir_upgrade ...etc.)
	 * @return array command and sudo option state
	 */
	public function getSoftwareManagementCommandConfig($command)
	{
		$cmd = [
			'cmd' => '',
			'sudo' => [
				'use_sudo' => false,
				'user' => '',
				'group' => ''
			]
		];
		$sm = $this->getSupportedSoftwareManagementCommands();
		$config = $this->getSoftwareManagementConfig();
		if (in_array($command, $sm) && $this->isSoftwareManagementCommandConfigured($command) === true) {
			$cmd['cmd'] = $config[$command];
			$cmd['sudo'] = [
				'use_sudo' => ((int) $config['use_sudo'] === 1),
				'user' => ($config['sudo_user'] ?? ''),
				'group' => ($config['sudo_group'] ?? '')
			];
		}
		return $cmd;
	}

	/**
	 * Get supported software management commands defined in API config.
	 *
	 * @return array supported software management commands
	 */
	public function getSupportedSoftwareManagementCommands()
	{
		$commands = [];
		$types = $this->getSoftwareManagementCommandTypes();
		for ($i = 0; $i < count($types); $i++) {
			if ($this->isSoftwareManagementCommandConfigured($types[$i]) === true) {
				array_push($commands, $types[$i]);
			}
		}
		return $commands;
	}

	/**
	 * Get software management command types.
	 *
	 * @return array command types
	 */
	public function getSoftwareManagementCommandTypes()
	{
		return [
			self::SOFTWARE_MANAGEMENT_BACULARIS_INSTALL,
			self::SOFTWARE_MANAGEMENT_BACULARIS_UPGRADE,
			self::SOFTWARE_MANAGEMENT_BACULARIS_REMOVE,
			self::SOFTWARE_MANAGEMENT_BACULARIS_INFO,
			self::SOFTWARE_MANAGEMENT_BACULARIS_ENABLE,
			self::SOFTWARE_MANAGEMENT_PRE_BACULARIS_INSTALL,
			self::SOFTWARE_MANAGEMENT_PRE_BACULARIS_UPGRADE,
			self::SOFTWARE_MANAGEMENT_PRE_BACULARIS_REMOVE,
			self::SOFTWARE_MANAGEMENT_PRE_BACULARIS_INFO,
			self::SOFTWARE_MANAGEMENT_POST_BACULARIS_INSTALL,
			self::SOFTWARE_MANAGEMENT_POST_BACULARIS_UPGRADE,
			self::SOFTWARE_MANAGEMENT_POST_BACULARIS_REMOVE,
			self::SOFTWARE_MANAGEMENT_POST_BACULARIS_INFO,
			self::SOFTWARE_MANAGEMENT_CAT_INSTALL,
			self::SOFTWARE_MANAGEMENT_CAT_UPGRADE,
			self::SOFTWARE_MANAGEMENT_CAT_REMOVE,
			self::SOFTWARE_MANAGEMENT_CAT_INFO,
			self::SOFTWARE_MANAGEMENT_CAT_ENABLE,
			self::SOFTWARE_MANAGEMENT_PRE_CAT_INSTALL,
			self::SOFTWARE_MANAGEMENT_PRE_CAT_UPGRADE,
			self::SOFTWARE_MANAGEMENT_PRE_CAT_REMOVE,
			self::SOFTWARE_MANAGEMENT_PRE_CAT_INFO,
			self::SOFTWARE_MANAGEMENT_POST_CAT_INSTALL,
			self::SOFTWARE_MANAGEMENT_POST_CAT_UPGRADE,
			self::SOFTWARE_MANAGEMENT_POST_CAT_REMOVE,
			self::SOFTWARE_MANAGEMENT_POST_CAT_INFO,
			self::SOFTWARE_MANAGEMENT_DIR_INSTALL,
			self::SOFTWARE_MANAGEMENT_DIR_UPGRADE,
			self::SOFTWARE_MANAGEMENT_DIR_REMOVE,
			self::SOFTWARE_MANAGEMENT_DIR_INFO,
			self::SOFTWARE_MANAGEMENT_DIR_ENABLE,
			self::SOFTWARE_MANAGEMENT_PRE_DIR_INSTALL,
			self::SOFTWARE_MANAGEMENT_PRE_DIR_UPGRADE,
			self::SOFTWARE_MANAGEMENT_PRE_DIR_REMOVE,
			self::SOFTWARE_MANAGEMENT_PRE_DIR_INFO,
			self::SOFTWARE_MANAGEMENT_POST_DIR_INSTALL,
			self::SOFTWARE_MANAGEMENT_POST_DIR_UPGRADE,
			self::SOFTWARE_MANAGEMENT_POST_DIR_REMOVE,
			self::SOFTWARE_MANAGEMENT_POST_DIR_INFO,
			self::SOFTWARE_MANAGEMENT_SD_INSTALL,
			self::SOFTWARE_MANAGEMENT_SD_UPGRADE,
			self::SOFTWARE_MANAGEMENT_SD_REMOVE,
			self::SOFTWARE_MANAGEMENT_SD_INFO,
			self::SOFTWARE_MANAGEMENT_SD_ENABLE,
			self::SOFTWARE_MANAGEMENT_PRE_SD_INSTALL,
			self::SOFTWARE_MANAGEMENT_PRE_SD_UPGRADE,
			self::SOFTWARE_MANAGEMENT_PRE_SD_REMOVE,
			self::SOFTWARE_MANAGEMENT_PRE_SD_INFO,
			self::SOFTWARE_MANAGEMENT_POST_SD_INSTALL,
			self::SOFTWARE_MANAGEMENT_POST_SD_UPGRADE,
			self::SOFTWARE_MANAGEMENT_POST_SD_REMOVE,
			self::SOFTWARE_MANAGEMENT_POST_SD_INFO,
			self::SOFTWARE_MANAGEMENT_FD_INSTALL,
			self::SOFTWARE_MANAGEMENT_FD_UPGRADE,
			self::SOFTWARE_MANAGEMENT_FD_REMOVE,
			self::SOFTWARE_MANAGEMENT_FD_INFO,
			self::SOFTWARE_MANAGEMENT_FD_ENABLE,
			self::SOFTWARE_MANAGEMENT_PRE_FD_INSTALL,
			self::SOFTWARE_MANAGEMENT_PRE_FD_UPGRADE,
			self::SOFTWARE_MANAGEMENT_PRE_FD_REMOVE,
			self::SOFTWARE_MANAGEMENT_PRE_FD_INFO,
			self::SOFTWARE_MANAGEMENT_POST_FD_INSTALL,
			self::SOFTWARE_MANAGEMENT_POST_FD_UPGRADE,
			self::SOFTWARE_MANAGEMENT_POST_FD_REMOVE,
			self::SOFTWARE_MANAGEMENT_POST_FD_INFO,
			self::SOFTWARE_MANAGEMENT_BCONS_INSTALL,
			self::SOFTWARE_MANAGEMENT_BCONS_UPGRADE,
			self::SOFTWARE_MANAGEMENT_BCONS_REMOVE,
			self::SOFTWARE_MANAGEMENT_BCONS_INFO,
			self::SOFTWARE_MANAGEMENT_PRE_BCONS_INSTALL,
			self::SOFTWARE_MANAGEMENT_PRE_BCONS_UPGRADE,
			self::SOFTWARE_MANAGEMENT_PRE_BCONS_REMOVE,
			self::SOFTWARE_MANAGEMENT_PRE_BCONS_INFO,
			self::SOFTWARE_MANAGEMENT_POST_BCONS_INSTALL,
			self::SOFTWARE_MANAGEMENT_POST_BCONS_UPGRADE,
			self::SOFTWARE_MANAGEMENT_POST_BCONS_REMOVE,
			self::SOFTWARE_MANAGEMENT_POST_BCONS_INFO
		];
	}
}
