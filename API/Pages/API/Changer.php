<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\API\Modules\DeviceConfig;
use Bacularis\Common\Modules\Errors\DeviceError;

/**
 * Configure autochanger.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class Changer extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$device_name = $this->Request->contains('device_name') && $misc->isValidName($this->Request['device_name']) ? $this->Request['device_name'] : null;
		$config = $this->getModule('device_config')->getConfig($device_name);
		if (count($config) == 0) {
			$this->output = DeviceError::ERROR_DEVICE_DEVICE_CONFIG_DOES_NOT_EXIST;
			$this->error = DeviceError::MSG_ERROR_DEVICE_DEVICE_CONFIG_DOES_NOT_EXIST;
			return;
		}
		$this->output = $config;
		$this->error = DeviceError::ERROR_NO_ERRORS;
	}

	public function create($params)
	{
		$misc = $this->getModule('misc');
		$device_config = $this->getModule('device_config');
		$device_name = $this->Request->contains('device_name') && $misc->isValidName($this->Request['device_name']) ? $this->Request['device_name'] : null;
		$type = (property_exists($params, 'type') && $device_config->isValidDevType($params->type)) ? $params->type : null;
		$device = (property_exists($params, 'device') && $misc->isValidPath($params->device)) ? $params->device : null;
		$sudo_user = (property_exists($params, 'sudo_user') && $misc->isValidName($params->sudo_user)) ? $params->sudo_user : null;
		$sudo_group = (property_exists($params, 'sudo_group') && $misc->isValidName($params->sudo_group)) ? $params->sudo_group : null;

		if ($device_config->deviceExists($device_name)) {
			$this->output = DeviceError::MSG_ERROR_DEVICE_INVALID_VALUE . ' Device already exists';
			$this->error = DeviceError::ERROR_DEVICE_INVALID_VALUE;
			return;
		}
		if (is_null($type)) {
			$this->output = DeviceError::MSG_ERROR_DEVICE_INVALID_VALUE . ' Wrong type value';
			$this->error = DeviceError::ERROR_DEVICE_INVALID_VALUE;
			return;
		}
		if (is_null($device)) {
			$this->output = DeviceError::MSG_ERROR_DEVICE_INVALID_VALUE . ' Wrong device value';
			$this->error = DeviceError::ERROR_DEVICE_INVALID_VALUE;
			return;
		}
		$config = [];
		if ($type === DeviceConfig::DEV_TYPE_AUTOCHANGER) {
			$command = property_exists($params, 'command') && $misc->isValidPath($params->command) ? $params->command : '';
			$use_sudo = property_exists($params, 'use_sudo') && $misc->isValidBoolean($params->use_sudo) ? $params->use_sudo : 0;
			$drives = property_exists($params, 'drives') ? explode(',', $params->drives) : [];
			$drives = $misc->filterValidNameList($drives);
			$config = [
				'type' => $type,
				'device' => $device,
				'command' => $command,
				'use_sudo' => $use_sudo,
				'drives' => implode(',', $drives)
			];
			if ($sudo_user) {
				$config['sudo_user'] = $sudo_user;
			}
			if ($sudo_group) {
				$config['sudo_group'] = $sudo_group;
			}
		} elseif ($type === DeviceConfig::DEV_TYPE_DEVICE) {
			$drive_index = (property_exists($params, 'index') && $misc->isValidInteger($params->index)) ? (int) $params->index : 0;
			$config = [
				'type' => $type,
				'device' => $device,
				'index' => $drive_index
			];
			if ($sudo_user) {
				$config['sudo_user'] = $sudo_user;
			}
			if ($sudo_group) {
				$config['sudo_group'] = $sudo_group;
			}
		}
		$output = '';
		$error = -1;
		if (count($config) > 0) {
			$result = $device_config->setDevice($device_name, $config);
			if ($result) {
				$output = DeviceError::MSG_ERROR_NO_ERRORS;
				$error = DeviceError::ERROR_NO_ERRORS;
			} else {
				$output = DeviceError::MSG_ERROR_WRONG_EXITCODE;
				$error = DeviceError::ERROR_WRONG_EXITCODE;
			}
		} else {
			$output = DeviceError::MSG_ERROR_DEVICE_INVALID_VALUE;
			$error = DeviceError::ERROR_DEVICE_INVALID_VALUE . ' Wrong type property.';
		}
		$this->output = $output;
		$this->error = $error;
	}

	public function set($id, $params)
	{
		$misc = $this->getModule('misc');
		$device_config = $this->getModule('device_config');
		$device_name = $this->Request->contains('device_name') && $misc->isValidName($this->Request['device_name']) ? $this->Request['device_name'] : null;
		$config = $device_config->getDevice($device_name);
		if (count($config) > 0) {
			if (property_exists($params, 'type') && $device_config->isValidDevType($params->type)) {
				$config['type'] = $params->type;
			}
			if (property_exists($params, 'device') && $misc->isValidPath($params->device)) {
				$config['device'] = $params->device;
			}
			if (property_exists($params, 'command') && $misc->isValidPath($params->command)) {
				$config['command'] = $params->command;
			}
			if (property_exists($params, 'use_sudo') && $misc->isValidBoolean($params->use_sudo)) {
				$config['use_sudo'] = $params->use_sudo;
			}
			if (property_exists($params, 'sudo_user') && $misc->isValidName($params->sudo_user)) {
				$config['sudo_user'] = $params->sudo_user;
			}
			if (property_exists($params, 'sudo_group') && $misc->isValidName($params->sudo_group)) {
				$config['sudo_group'] = $params->sudo_group;
			}
			if (property_exists($params, 'drives')) {
				$drives = explode(',', $params->drives);
				$drives = $misc->filterValidNameList($drives);
				$config['drives'] = implode(',', $drives);
			}
			if (property_exists($params, 'index') && $misc->isValidInteger($params->index)) {
				$config['index'] = $params->index;
			}
			$output = '';
			$error = -1;
			$result = $device_config->setDevice($device_name, $config);
			if ($result) {
				$output = DeviceError::MSG_ERROR_NO_ERRORS;
				$error = DeviceError::ERROR_NO_ERRORS;
			} else {
				$output = DeviceError::MSG_ERROR_WRONG_EXITCODE;
				$error = DeviceError::ERROR_WRONG_EXITCODE;
			}
			$this->output = $output;
			$this->error = $error;
		} else {
			$this->output = DeviceError::MSG_ERROR_DEVICE_DEVICE_CONFIG_DOES_NOT_EXIST;
			$this->error = DeviceError::ERROR_DEVICE_DEVICE_CONFIG_DOES_NOT_EXIST;
		}
	}

	public function remove($id)
	{
		$misc = $this->getModule('misc');
		$device_config = $this->getModule('device_config');
		$device_name = $this->Request->contains('device_name') && $misc->isValidName($this->Request['device_name']) ? $this->Request['device_name'] : null;
		$output = '';
		$error = -1;
		if ($device_name) {
			if (!$device_config->deviceExists($device_name)) {
				$output = DeviceError::MSG_ERROR_DEVICE_INVALID_VALUE . ' Device does not exist.';
				$error = DeviceError::ERROR_DEVICE_INVALID_VALUE;
			} else {
				$result = $device_config->deleteDevice($device_name);
				if ($result) {
					$output = DeviceError::MSG_ERROR_NO_ERRORS;
					$error = DeviceError::ERROR_NO_ERRORS;
				} else {
					$output = DeviceError::MSG_ERROR_WRONG_EXITCODE;
					$error = DeviceError::ERROR_WRONG_EXITCODE;
				}
			}
		} else {
			// This case should not happen
			$output = DeviceError::MSG_ERROR_INTERNAL_ERROR;
			$error = DeviceError::ERROR_INTERNAL_ERROR;
		}
		$this->output = $output;
		$this->error = $error;
	}
}
