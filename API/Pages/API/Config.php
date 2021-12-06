<?php
/*
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

Prado::using('Application.Common.Class.Errors');

/**
 * Config endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class Config extends BaculumAPIServer {
	public function get() {
		$misc = $this->getModule('misc');
		$component_type = $this->Request->contains('component_type') ? $this->Request['component_type'] : null;
		$resource_type = $this->Request->contains('resource_type') ? $this->Request['resource_type'] : null;
		$resource_name = $this->Request->contains('resource_name') ? $this->Request['resource_name'] : null;
		$apply_jobdefs = $this->Request->contains('apply_jobdefs') && $misc->isValidBoolean($this->Request['apply_jobdefs']) ? (bool)$this->Request['apply_jobdefs'] : null;
		$opts = [];
		if ($apply_jobdefs) {
			$opts['apply_jobdefs'] = $apply_jobdefs;
		}

		$config = $this->getModule('bacula_setting')->getConfig($component_type, $resource_type, $resource_name, $opts);
		$this->output = $config['output'];
		$this->error = $config['exitcode'];
	}

	public function set($id, $params) {
		$config = (array)$params;
		if (array_key_exists('config', $config)) {
			if ($this->getClientVersion() <= 0.2) {
				// old way sending config as serialized array
				$config = unserialize($config['config']);
			} else {
				$config = json_decode($config['config'], true);
			}
		} else {
			$config = array();
		}
		if (is_null($config)) {
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_VALIDATION_ERROR;
			$this->error = BaculaConfigError::ERROR_CONFIG_VALIDATION_ERROR;
			return;
		}
		$component_type = $this->Request->contains('component_type') ? $this->Request['component_type'] : null;
		$resource_type = $this->Request->contains('resource_type') ? $this->Request['resource_type'] : null;
		$resource_name = $this->Request->contains('resource_name') ? $this->Request['resource_name'] : null;

		$result = $this->getModule('bacula_setting')->setConfig($config, $component_type, $resource_type, $resource_name);
		if ($result['save_result'] === true) {
			$this->output = BaculaConfigError::MSG_ERROR_NO_ERRORS;
			$this->error = BaculaConfigError::ERROR_NO_ERRORS;
		} else if ($result['is_valid'] === false) {
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_VALIDATION_ERROR . print_r($result['result'], true);
			$this->error = BaculaConfigError::ERROR_CONFIG_VALIDATION_ERROR;
		} else {
			$this->output = BaculaConfigError::MSG_ERROR_WRITE_TO_CONFIG_ERROR . print_r($result['result'], true);
			$this->error = BaculaConfigError::ERROR_WRITE_TO_CONFIG_ERROR;
		}
	}
}
