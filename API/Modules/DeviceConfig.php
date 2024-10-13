<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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

/**
 * Manage devices configuration.
 * Module is responsible for device config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Device
 */
class DeviceConfig extends ConfigFileModule
{
	/**
	 * Supported device types
	 */
	public const DEV_TYPE_DEVICE = 'device';
	public const DEV_TYPE_AUTOCHANGER = 'autochanger';

	/**
	 * Device file path patter.
	 */
	public const DEVICE_PATH_PATTERN = '[a-zA-Z0-9:.\-_ ]+';

	/**
	 * Device config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.API.Config.devices';

	/**
	 * Device config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Stores device config.
	 */
	private $config;

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
	public function getConfig($section = null)
	{
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
	 * Check if device config exists.
	 *
	 * @param string $name device name
	 * @return bool true if device config exists, otherwise false
	 */
	public function deviceExists(string $name): bool
	{
		$devices = $this->getConfig();
		return key_exists($name, $devices);
	}

	/**
	 * Get device config.
	 *
	 * @param string $name device name
	 * @return array device config or empty array if device does not exist
	 */
	public function getDevice(string $name): array
	{
		$device = [];
		if ($this->deviceExists($name)) {
			$devices = $this->getConfig();
			$device = $devices[$name];
		}
		return $device;
	}

	/**
	 * Set device config.
	 *
	 * @param string $name device name
	 * @param array $config $device config
	 * @return bool true if device saved successfully, otherwise false
	 */
	public function setDevice(string $name, array $config): bool
	{
		$devices = $this->getConfig();
		$devices[$name] = $config;
		return $this->setConfig($devices);
	}

	/**
	 * Delete device config.
	 *
	 * @param string $name device name
	 * @return bool true if device deleted successfully, otherwise false
	 */
	public function deleteDevice(string $name): bool
	{
		$result = false;
		if ($this->deviceExists($name)) {
			$devices = $this->getConfig();
			unset($devices[$name]);
			$result = $this->setConfig($devices);
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
	 * @return bool true if config valid, otherwise false
	 */
	private function validateConfig($section, array $config = [])
	{
		$required_options = [$section => $this->required_options];
		return $this->isConfigValid(
			$required_options,
			[$section => $config],
			self::CONFIG_FILE_FORMAT,
			self::CONFIG_FILE_PATH
		);
	}

	public function getChangerDrives($changer)
	{
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

	/**
	 * Check if given device type is valid.
	 *
	 * @param string $type device type
	 * @return bool true if device type is valid, otherwise false
	 */
	public function isValidDevType(string $type): bool
	{
		$types = [
			self::DEV_TYPE_DEVICE,
			self::DEV_TYPE_AUTOCHANGER
		];
		return in_array($type, $types);
	}
}
