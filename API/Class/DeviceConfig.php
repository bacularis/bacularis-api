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

Prado::using('Application.Common.Class.ConfigFileModule');

/**
 * Manage devices configuration.
 * Module is responsible for device config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Device
 * @package Baculum API
 */
class DeviceConfig extends ConfigFileModule {

	/**
	 * Supported device types
	 */
	const DEV_TYPE_DEVICE = 'device';
	const DEV_TYPE_AUTOCHANGER = 'autochanger';

	/**
	 * Device file path patter.
	 */
	const DEVICE_PATH_PATTERN = '[a-zA-Z0-9:.\-_ ]+';

	/**
	 * Device config file path
	 */
	const CONFIG_FILE_PATH = 'Application.API.Config.devices';

	/**
	 * Device config file format
	 */
	const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Stores device config.
	 */
	private $config = null;

	/**
	 * These options are obligatory for device config.
	 */
	private $required_options = ['type', 'device'];

	/**
	 * Get (read) device config.
	 *
	 * @param string $section config section name
	 * @return array config
	 */
	public function getConfig($section = null) {
		$config = [];
		if (is_null($this->config)) {
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		}
		$is_valid = true;
		if (!is_null($section)) {
			$config = key_exists($section, $this->config) ? $this->config[$section] : [];
			$is_valid = $this->validateConfig($section, $config);
		} else {
			foreach ($this->config as $section => $value) {
				if ($this->validateConfig($section, $value) === false) {
					$is_valid = false;
					break;
				}
				$config[$section] = $value;
			}
		}
		if ($is_valid === false) {
			// no validity, no config
			$config = [];
		}
		return $config;
	}

	/**
	 * Set (save) device client config.
	 *
	 * @param array $config config
	 * @return boolean true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config) {
		$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}


	/**
	 * Validate device config.
	 * Config validation should be used as early as config data is available.
	 * Validation is done in read/write config methods.
	 *
	 * @param string $section section name
	 * @param array $config config
	 * @return boolean true if config valid, otherwise false
	 */
	private function validateConfig($section, array $config = []) {
		$required_options = [$section => $this->required_options];
		return $this->isConfigValid(
			$required_options,
			[$section => $config],
			self::CONFIG_FILE_FORMAT,
			self::CONFIG_FILE_PATH
		);
	}

	public function getChangerDrives($changer) {
		$drives = [];
		$config = $this->getConfig($changer);
		if (count($config) > 0) {
			$ach_drives = explode(',', $config['drives']);
			for ($i = 0; $i < count($ach_drives); $i++) {
				$drive = $this->getConfig($ach_drives[$i]);
				if (count($drive) > 0 && $drive['type'] === self::DEV_TYPE_DEVICE) {
					$drive['name'] = $ach_drives[$i];
					$drives[$drive['index']] = $drive;
				}
			}
		}
		return $drives;
	}
}
?>
