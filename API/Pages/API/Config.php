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

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\API\Modules\BaculaSetting;
use Bacularis\API\Modules\BaculaConfig;
use Bacularis\Common\Modules\PluginConfigBase;
use Bacularis\Common\Modules\Errors\BaculaConfigError;

/**
 * Config endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class Config extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$component_type = $this->Request->contains('component_type') ? $this->Request['component_type'] : null;
		$resource_type = $this->Request->contains('resource_type') ? $this->Request['resource_type'] : null;
		$resource_name = $this->Request->contains('resource_name') ? $this->Request['resource_name'] : null;
		$apply_jobdefs = $this->Request->contains('apply_jobdefs') && $misc->isValidBoolean($this->Request['apply_jobdefs']) ? (bool) $this->Request['apply_jobdefs'] : null;
		$opts = [];
		if ($apply_jobdefs) {
			$opts['apply_jobdefs'] = $apply_jobdefs;
		}

		// Check if user is allowed to read resource type
		$rtype = strtolower($resource_type);
		$perm_key = sprintf('%s_res_perm', $component_type);
		if (key_exists($perm_key, $this->auth)) {
			$auth = array_change_key_case($this->auth[$perm_key]);
			if (isset($auth[$rtype])) {
				if (!in_array($auth[$rtype], ['ro', 'rw'])) {
					$this->output = BaculaConfigError::MSG_ERROR_USER_NOT_ALLOWED_TO_READ_RESOURCE_CONFIG;
					$this->error = BaculaConfigError::ERROR_USER_NOT_ALLOWED_TO_READ_RESOURCE_CONFIG;
					return;
				}
			}
		}

		// Run plugin pre-read actions
		$this->getModule('plugin_manager')->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_BACULA_CONFIGURATION,
			'preConfigRead',
			$component_type,
			$resource_type,
			$resource_name
		);

		// Read Bacula configuration
		$config = $this->getModule('bacula_setting')->getConfig(
			$component_type,
			$resource_type,
			$resource_name,
			$opts
		);

		// Run plugin post-read actions
		if ($config['exitcode'] == 0) {
			$this->getModule('plugin_manager')->callPluginActionByType(
				PluginConfigBase::PLUGIN_TYPE_BACULA_CONFIGURATION,
				'postConfigRead',
				$component_type,
				$resource_type,
				$resource_name,
				$config['output']
			);
		}

		$this->output = $config['output'];
		$this->error = $config['exitcode'];
	}

	public function create($params)
	{
		$config = (array) $params;
		if (key_exists('config', $config)) {
			$config = json_decode($config['config'], true);
		} else {
			$config = [];
		}
		if (is_null($config)) {
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_VALIDATION_ERROR;
			$this->error = BaculaConfigError::ERROR_CONFIG_VALIDATION_ERROR;
			return;
		}
		$misc = $this->getModule('misc');
		$component_type = $this->Request->contains('component_type') ? $this->Request['component_type'] : null;
		$resource_type = $this->Request->contains('resource_type') ? $this->Request['resource_type'] : null;
		$resource_name = $this->Request->contains('resource_name') ? $this->Request['resource_name'] : null;
		$mode = $this->Request->contains('mode') && $misc->isValidAlphaNumeric($this->Request['mode']) ? $this->Request['mode'] : BaculaSetting::MODE_SAVE;

		// Check if user is allowed to write resource type
		$rtype = strtolower($resource_type);
		$perm_key = sprintf('%s_res_perm', $component_type);
		if (key_exists($perm_key, $this->auth)) {
			$auth = array_change_key_case($this->auth[$perm_key]);
			if (isset($auth[$rtype])) {
				if (!in_array($auth[$rtype], ['rw'])) {
					$this->output = BaculaConfigError::MSG_ERROR_USER_NOT_ALLOWED_TO_WRITE_RESOURCE_CONFIG;
					$this->error = BaculaConfigError::ERROR_USER_NOT_ALLOWED_TO_WRITE_RESOURCE_CONFIG;
					return;
				}
			}
		}

		// Run plugin pre-create actions
		$this->getModule('plugin_manager')->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_BACULA_CONFIGURATION,
			'preConfigCreate',
			$component_type,
			$resource_type,
			$resource_name,
			$config
		);

		$rconfig = [];
		if (is_string($component_type) && is_string($resource_type) && is_string($resource_name)) {
			// Get existing resource config if exists
			$res = $this->getModule('bacula_setting')->getConfig(
				$component_type,
				$resource_type,
				$resource_name
			);
			if ($res['exitcode'] === 0) {
				$rconfig = $res['output'];
			}
		}
		if (is_null($resource_name) || count($rconfig) == 0) {
			$result = $this->getModule('bacula_setting')->setConfig(
				$config,
				$component_type,
				$resource_type,
				$resource_name,
				$mode
			);
			if ($result['save_result'] === true) {
				if ($mode === BaculaSetting::MODE_SIMULATE) {
					$this->output = $this->getSimulatedResult($result['config'], $component_type, $resource_type, $config['Name']);
				} else {
					$this->output = BaculaConfigError::MSG_ERROR_NO_ERRORS;

					// Run plugin post-create actions
					$this->getModule('plugin_manager')->callPluginActionByType(
						PluginConfigBase::PLUGIN_TYPE_BACULA_CONFIGURATION,
						'postConfigCreate',
						$component_type,
						$resource_type,
						$resource_name,
						$config
					);
				}
				$this->error = BaculaConfigError::ERROR_NO_ERRORS;
			} elseif ($result['is_valid'] === false) {
				$this->output = BaculaConfigError::MSG_ERROR_CONFIG_VALIDATION_ERROR . print_r($result['result'], true);
				$this->error = BaculaConfigError::ERROR_CONFIG_VALIDATION_ERROR;
			} else {
				$this->output = BaculaConfigError::MSG_ERROR_WRITE_TO_CONFIG_ERROR . print_r($result['result'], true);
				$this->error = BaculaConfigError::ERROR_WRITE_TO_CONFIG_ERROR;
			}
		} else {
			// Config already exists
			$emsg = sprintf(
				' Component Type: %s, Resource Type: %s, Resource Name: %s',
				$component_type ?? '',
				$resource_type ?? '',
				$resource_name ?? ''
			);
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_ALREADY_EXISTS . $emsg;
			$this->error = BaculaConfigError::ERROR_CONFIG_ALREADY_EXISTS;
		}
	}

	public function set($id, $params)
	{
		$config = (array) $params;
		if (array_key_exists('config', $config)) {
			if ($this->getClientVersion() <= 0.2) {
				// old way sending config as serialized array
				$config = unserialize($config['config']);
			} else {
				$config = json_decode($config['config'], true);
			}
		} else {
			$config = [];
		}
		if (is_null($config)) {
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_VALIDATION_ERROR;
			$this->error = BaculaConfigError::ERROR_CONFIG_VALIDATION_ERROR;
			return;
		}
		$misc = $this->getModule('misc');
		$component_type = $this->Request->contains('component_type') ? $this->Request['component_type'] : null;
		$resource_type = $this->Request->contains('resource_type') ? $this->Request['resource_type'] : null;
		$resource_name = $this->Request->contains('resource_name') ? $this->Request['resource_name'] : null;
		$mode = $this->Request->contains('mode') && $misc->isValidAlphaNumeric($this->Request['mode']) ? $this->Request['mode'] : BaculaSetting::MODE_SAVE;

		// Check if user is allowed to write resource type
		$rtype = strtolower($resource_type);
		$perm_key = sprintf('%s_res_perm', $component_type);
		if (key_exists($perm_key, $this->auth)) {
			$auth = array_change_key_case($this->auth[$perm_key]);
			if (isset($auth[$rtype])) {
				if (!in_array($auth[$rtype], ['rw'])) {
					$this->output = BaculaConfigError::MSG_ERROR_USER_NOT_ALLOWED_TO_WRITE_RESOURCE_CONFIG;
					$this->error = BaculaConfigError::ERROR_USER_NOT_ALLOWED_TO_WRITE_RESOURCE_CONFIG;
					return;
				}
			}
		}

		// Run plugin pre-update actions
		$this->getModule('plugin_manager')->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_BACULA_CONFIGURATION,
			'preConfigUpdate',
			$component_type,
			$resource_type,
			$resource_name,
			$config
		);

		$rconfig = [];
		if (is_string($component_type) && is_string($resource_type) && is_string($resource_name)) {
			// Get existing resource config if exists
			$res = $this->getModule('bacula_setting')->getConfig(
				$component_type,
				$resource_type,
				$resource_name
			);
			if ($res['exitcode'] === 0) {
				$rconfig = $res['output'];
			}
			if ($config['Name'] !== $resource_name) {
				// rename request
				// check if dest renamed name does not exist in config
				$res = $this->getModule('bacula_setting')->getConfig(
					$component_type,
					$resource_type,
					$config['Name']
				);
				if ($res['exitcode'] === 0 && count($res['output']) > 0) {
					// Dest name already exists. Do not overwrite it. RETURN
					$this->output = BaculaConfigError::MSG_ERROR_CONFIG_ALREADY_EXISTS;
					$this->error = BaculaConfigError::ERROR_CONFIG_ALREADY_EXISTS;
					return;
				}
			}
		}

		if (is_null($resource_name) || count($rconfig) > 0) {
			// Config exists. It can be updated.
			$result = $this->getModule('bacula_setting')->setConfig(
				$config,
				$component_type,
				$resource_type,
				$resource_name,
				$mode
			);
			if ($result['save_result'] === true) {
				$this->error = BaculaConfigError::ERROR_NO_ERRORS;
				if ($mode === BaculaSetting::MODE_SIMULATE) {
					// Simulation, nothing saved
					$this->output = $this->getSimulatedResult(
						$result['config'],
						$component_type,
						$resource_type,
						($config['Name'] ?? null)
					);
				} else {
					// Config savesd successfully
					$this->output = BaculaConfigError::MSG_ERROR_NO_ERRORS;

					// Run plugin post-update actions
					$this->getModule('plugin_manager')->callPluginActionByType(
						PluginConfigBase::PLUGIN_TYPE_BACULA_CONFIGURATION,
						'postConfigUpdate',
						$component_type,
						$resource_type,
						$resource_name,
						$config
					);
				}
			} elseif ($result['is_valid'] === false) {
				$this->output = BaculaConfigError::MSG_ERROR_CONFIG_VALIDATION_ERROR . print_r($result['result'], true);
				$this->error = BaculaConfigError::ERROR_CONFIG_VALIDATION_ERROR;
			} else {
				$this->output = BaculaConfigError::MSG_ERROR_WRITE_TO_CONFIG_ERROR . print_r($result['result'], true);
				$this->error = BaculaConfigError::ERROR_WRITE_TO_CONFIG_ERROR;
			}
		} else {
			// Config does not exist
			$emsg = sprintf(
				' Component Type: %s, Resource Type: %s, Resource Name: %s',
				$component_type ?? '',
				$resource_type ?? '',
				$resource_name ?? ''
			);
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_DOES_NOT_EXIST . $emsg;
			$this->error = BaculaConfigError::ERROR_CONFIG_DOES_NOT_EXIST;
		}
	}

	public function remove($id)
	{
		$component_type = $this->Request->contains('component_type') ? $this->Request['component_type'] : null;
		$resource_type = $this->Request->contains('resource_type') ? $this->Request['resource_type'] : null;
		$resource_name = $this->Request->contains('resource_name') ? $this->Request['resource_name'] : null;

		// Check if user is allowed to write resource type
		$rtype = strtolower($resource_type);
		$perm_key = sprintf('%s_res_perm', $component_type);
		if (key_exists($perm_key, $this->auth)) {
			$auth = array_change_key_case($this->auth[$perm_key]);
			if (isset($auth[$rtype])) {
				if (!in_array($auth[$rtype], ['rw'])) {
					$this->output = BaculaConfigError::MSG_ERROR_USER_NOT_ALLOWED_TO_WRITE_RESOURCE_CONFIG;
					$this->error = BaculaConfigError::ERROR_USER_NOT_ALLOWED_TO_WRITE_RESOURCE_CONFIG;
					return;
				}
			}
		}

		// Run plugin pre-delete actions
		$this->getModule('plugin_manager')->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_BACULA_CONFIGURATION,
			'preConfigDelete',
			$component_type,
			$resource_type,
			$resource_name
		);

		$config = [];
		if (is_string($component_type) && is_string($resource_type) && is_string($resource_name)) {
			$res = $this->getModule('bacula_setting')->getConfig(
				$component_type
			);
			if ($res['exitcode'] === 0) {
				$config = $res['output'];
			}
		}
		$config_len = count($config);
		if ($config_len > 0) {
			$index_del = -1;
			for ($i = 0; $i < $config_len; $i++) {
				if (!key_exists($resource_type, $config[$i])) {
					// skip other resource types
					continue;
				}
				if ($config[$i][$resource_type]['Name'] === $resource_name) {
					$index_del = $i;
					break;
				}
			}
			if ($index_del > -1) {
				// Check if resource has dependencies
				$deps = $this->getModule('data_deps')->checkDependencies(
					$component_type,
					$resource_type,
					$resource_name,
					$config
				);
				if (count($deps) == 0) {
					array_splice($config, $index_del, 1);
					$result = $this->getModule('bacula_setting')->setConfig(
						$config,
						$component_type
					);
					if ($result['save_result'] === true) {
						$this->output = BaculaConfigError::MSG_ERROR_NO_ERRORS;
						$this->error = BaculaConfigError::ERROR_NO_ERRORS;

						// Run plugin post-delete actions
						$this->getModule('plugin_manager')->callPluginActionByType(
							PluginConfigBase::PLUGIN_TYPE_BACULA_CONFIGURATION,
							'postConfigDelete',
							$component_type,
							$resource_type,
							$resource_name
						);
					} elseif ($result['is_valid'] === false) {
						$this->output = BaculaConfigError::MSG_ERROR_CONFIG_VALIDATION_ERROR . print_r($result['result'], true);
						$this->error = BaculaConfigError::ERROR_CONFIG_VALIDATION_ERROR;
					} else {
						$this->output = BaculaConfigError::MSG_ERROR_WRITE_TO_CONFIG_ERROR . print_r($result['result'], true);
						$this->error = BaculaConfigError::ERROR_WRITE_TO_CONFIG_ERROR;
					}
				} else {
					$this->output = json_encode($deps);
					$this->error = BaculaConfigError::ERROR_CONFIG_DEPENDENCY_ERROR;
				}
			} else {
				$this->output = BaculaConfigError::MSG_ERROR_CONFIG_DOES_NOT_EXIST;
				$this->error = BaculaConfigError::ERROR_CONFIG_DOES_NOT_EXIST;
			}
		} else {
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_DOES_NOT_EXIST;
			$this->error = BaculaConfigError::ERROR_CONFIG_DOES_NOT_EXIST;
		}
	}

	private function getSimulatedResult($config, $component_type, $resource_type, $resource_name)
	{
		$conf = [];
		if (!is_null($component_type)) {
			if (!is_null($resource_type) && !is_null($resource_name)) {
				for ($i = 0; $i < count($config); $i++) {
					if (isset($config[$i][$resource_type]['Name']) && $config[$i][$resource_type]['Name'] === "\"$resource_name\"") {
						$conf = $config[$i];
						break;
					}
				}
				$conf = $this->getModule('config_bacula')->prepareResource($conf);
			} else {
				$conf = $this->getModule('config_bacula')->prepareConfig($config);
			}
		}
		return $conf;
	}
}
