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

Prado::using('Application.Common.Class.ConfigFileModule');

/**
 * Manage Bacula configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 * @package Baculum API
 */
class BaculaConfig extends ConfigFileModule {

	/**
	 * Bacula config file format
	 */
	const CONFIG_FILE_FORMAT = 'bacula';

	/**
	 * Get (read) Bacula config.
	 *
	 * @access public
	 * @param string $component_type Bacula component type
	 * @param array $params requested config parameters
	 * @return array config
	 */
	public function getConfig($component_type, $params = array()) {
		$config = array();
		$result = $this->getModule('json_tools')->execCommand($component_type, $params);
		if ($result['exitcode'] === 0 && is_array($result['output'])) {
			$config = $result['output'];
		}
		return $config;
	}

	/**
	 * Set (save) Bacula config.
	 *
	 * @access public
	 * @param string $component_type Bacula component type
	 * @param array $config config
	 * @param string $file config file path
	 * @return array validation result, validation output and write to config result
	 */
	public function setConfig($component_type, array $config, $file = null) {
		$result = array('is_valid' => false, 'save_result' => false, 'output' => null);
		$config_content = $this->prepareConfig($config, self::CONFIG_FILE_FORMAT);
		$validation = $this->validateConfig($component_type, $config_content);
		$result['is_valid'] = $validation['is_valid'];
		$result['result'] = $validation['result'];
		if ($result['is_valid'] === true) {
			if (is_null($file)) {
				$tool_config = $this->getModule('api_config')->getJSONToolConfig($component_type);
				$file = $tool_config['cfg'];
			}
			$result['save_result'] = $this->writeConfig($config, $file, self::CONFIG_FILE_FORMAT);
		}
		return $result;
	}

	/**
	 * Validate Bacula config.
	 * Config validation should be used as early as config data is available.
	 * Validation is done in write config method. In read config method it isn't
	 * required because both reading config and validating config are done by
	 * the same tool (Bacula JSON program)
	 *
	 * @access private
	 * @param string $component_type Bacula component type
	 * @param string $config config
	 * @return array validation output and exitcode
	 */
	private function validateConfig($component_type, $config) {
		$ret = array('is_valid' => false, 'result' => null);
		$params = array('test_config' => true);
		$result = $this->getModule('json_tools')->execCommand($component_type, $params, $config);
		if ($result['exitcode'] === 0) {
			$ret['is_valid'] = true;
		}
		$ret['result'] = $result;

		if ($ret['is_valid'] === false) {
			$error = is_array($result['output']) ? implode('', $result['output']) : $result['output'];
			$emsg = "ERROR [$component_type] $error";
			$this->getModule('logging')->log(
				__FUNCTION__,
				$emsg,
				Logging::CATEGORY_APPLICATION,
				__FILE__,
				__LINE__
			);
		}
		return $ret;
	}
}
?>
