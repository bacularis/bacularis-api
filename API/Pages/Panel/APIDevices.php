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

Prado::using('Application.API.Class.DeviceConfig');
Prado::using('Application.API.Class.BAPIException');
Prado::using('Application.API.Class.BaculumAPIPage');

/**
 * API devices page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Panel
 * @package Baculum API
 */
class APIDevices extends BaculumAPIPage {

	const WINDOW_TYPE_ADD = 'add';
	const WINDOW_TYPE_EDIT = 'edit';

	private $config;

	public function onInit($param) {
		parent::onInit($param);
		$this->config = $this->getModule('device_config')->getConfig();
	}

	public function setAutochangerList($sender, $param) {
		$devices = [];
		foreach ($this->config as $name => $device) {
			if ($device['type'] !== DeviceConfig::DEV_TYPE_AUTOCHANGER) {
				continue;
			}
			$device['name'] = $name;
			$devices[] = $device;
		}

		$this->getCallbackClient()->callClientFunction(
			'oAPIAutochangers.load_autochanger_list_cb',
			[$devices]
		);

		if (is_object($sender)) {
			$this->setConfigAutochangers();
		}
	}

	public function addAutochanger($sender, $param) {
		$ach_drives = $this->getAutochangerDrives();
		$disabled_indices = [];
		for ($i = 0; $i < $this->ChangerDevices->getItemCount(); $i++) {
			$item = $this->ChangerDevices->Items[$i];
			if (key_exists($item->Value, $ach_drives)) {
				$disabled_indices[] = $i;
			}
		}

		$this->getCallbackClient()->callClientFunction(
			'oAPIAutochangers.set_disabled_drives',
			[$disabled_indices]
		);
	}

	public function loadAutochanger($sender, $param) {
		$ach_name = $param->getCallbackParameter();
		$ach = [];
		foreach ($this->config as $name => $device) {
			if ($device['type'] !== DeviceConfig::DEV_TYPE_AUTOCHANGER) {
				continue;
			}
			if ($name == $ach_name) {
				$ach = $device;
				break;
			}
		}
		if (count($ach) > 0) {
			$this->AutochangerName->Text = $name;
			$this->ChangerDevice->Text = $ach['device'];
			$this->ChangerCommand->Text = $ach['command'];
			$this->ChangerCommandUseSudo->Checked = ($ach['use_sudo'] == 1);
			$drives = explode(',', $ach['drives']);
			$ach_drives = $this->getAutochangerDrives();
			$disabled_indices = [];
			$selected_indices = [];
			for ($i = 0; $i < $this->ChangerDevices->getItemCount(); $i++) {
				$item = $this->ChangerDevices->Items[$i];
				if (key_exists($item->Value, $ach_drives) && $ach_drives[$item->Value] !== $name) {
					$disabled_indices[] = $i;
					continue;
				}
				if (in_array($item->Value, $drives)) {
					$selected_indices[] = $i;
				}
			}
			$this->ChangerDevices->setSelectedIndices($selected_indices);
			$this->getCallbackClient()->callClientFunction(
				'oAPIAutochangers.set_disabled_drives',
				[$disabled_indices]
			);
		}
	}

	public function saveAutochanger($sender, $param) {
		if ($this->AutochangerWindowType->Value == self::WINDOW_TYPE_ADD && key_exists($this->AutochangerName->Text, $this->config)) {
			$this->getCallbackClient()->show('autochanger_exists');
			return;
		}
		$drives = [];
		$selected_indices = $this->ChangerDevices->getSelectedIndices();
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->ChangerDevices->getItemCount(); $i++) {
				if ($i === $indice) {
					$drives[] = $this->ChangerDevices->Items[$i]->Value;
				}
			}
		}
		$autochanger = [
			'type' => DeviceConfig::DEV_TYPE_AUTOCHANGER,
			'device' => $this->ChangerDevice->Text,
			'command' => $this->ChangerCommand->Text,
			'use_sudo' => $this->ChangerCommandUseSudo->Checked ? '1' : '0',
			'drives'=> implode(',', $drives)
		];
		$this->config[$this->AutochangerName->Text] = $autochanger;
		$result = $this->getModule('device_config')->setConfig($this->config);
		if ($result) {
			$this->getCallbackClient()->callClientFunction('oAPIAutochangers.show_autochanger_window', [false]);
			$this->setAutochangerList(null, null);
			$this->setDeviceList(null, null);
		}
	}

	public function deleteAutochanger($sender, $param) {
		$ach = $param->getCallbackParameter();
		if (!key_exists($ach, $this->config)) {
			return;
		}
		unset($this->config[$ach]);
		$result = $this->getModule('device_config')->setConfig($this->config);
		if ($result) {
			$this->setAutochangerList(null, null);
			$this->setDeviceList(null, null);
		}
	}

	public function testChangerCommand($sender, $param) {
		$emsg = '';
		$use_sudo = $this->ChangerCommandUseSudo->Checked;
		$changer_command = $this->ChangerCommand->Text;
		$changer_device = $this->ChangerDevice->Text;
		$command = 'listall';
		// slot, archive device and index are not used in listall cmd
		$slot = 0;
		$archive_device = '/dev/null';
		$drive_index = 0;
		$is_validate = false;
		if (!empty($changer_command) && !empty($changer_device)) {
			$result = $this->getModule('changer_command')->testChangerCommand(
				$use_sudo,
				$changer_command,
				$changer_device,
				$command,
				$slot,
				$archive_device,
				$drive_index
			);
			$is_validate = ($result->error === 0);
			if (!$is_validate) {
				$this->ChangerCommandTestResultErr->Text = implode(PHP_EOL, $result->output);
			}
		}
		if ($is_validate === true) {
			$this->getCallbackClient()->show('changer_command_test_result_ok');
			$this->getCallbackClient()->hide('changer_command_test_result_err');
			$this->getCallbackClient()->hide($this->ChangerCommandTestResultErr);
		} else {
			$this->getCallbackClient()->hide('changer_command_test_result_ok');
			$this->getCallbackClient()->show('changer_command_test_result_err');
			$this->getCallbackClient()->show($this->ChangerCommandTestResultErr);
		}
	}

	public function setDeviceList($sender, $param) {
		$devices = [];
		$ach_devices = $this->getAutochangerDrives();
		$dev_names = [];
		foreach ($this->config as $name => $device) {
			if ($device['type'] !== DeviceConfig::DEV_TYPE_DEVICE) {
				continue;
			}
			$device['name'] = $name;
			$device['autochanger'] = (key_exists($name, $ach_devices)) ? $ach_devices[$name] : '';
			$dev_names[] = $name;
			$devices[] = $device;
		}

		// Set changer device select list
		$this->ChangerDevices->DataSource = array_combine($dev_names, $dev_names);
		$this->ChangerDevices->dataBind();

		$this->getCallbackClient()->callClientFunction(
			'oAPIDevices.load_device_list_cb',
			[$devices]
		);

		if (is_object($sender)) {
			$this->setConfigDevices();
		}
	}

	private function getAutochangerDrives() {
		$ach_devices = [];
		foreach ($this->config as $name => $device) {
			if ($device['type'] !== DeviceConfig::DEV_TYPE_AUTOCHANGER) {
				continue;
			}
			$drives = explode(',', $device['drives']);
			for ($i = 0; $i < count($drives); $i++) {
				$ach_devices[$drives[$i]] = $name;
			}
		}
		return $ach_devices;
	}

	public function loadDevice($sender, $param) {
		$dev_name = $param->getCallbackParameter();
		$dev = [];
		foreach ($this->config as $name => $device) {
			if ($device['type'] !== DeviceConfig::DEV_TYPE_DEVICE) {
				continue;
			}
			if ($name == $dev_name) {
				$dev = $device;
				break;
			}
		}
		if (count($dev) > 0) {
			$this->DeviceName->Text = $name;
			$this->DeviceDevice->Text = $dev['device'];
			$this->DeviceIndex->Text = $dev['index'];
		}
	}

	public function saveDevice($sender, $param) {
		if ($this->DeviceWindowType->Value == self::WINDOW_TYPE_ADD && key_exists($this->DeviceName->Text, $this->config)) {
			$this->getCallbackClient()->show('device_exists');
			return;
		}
		$device = [
			'type' => DeviceConfig::DEV_TYPE_DEVICE,
			'device' => $this->DeviceDevice->Text,
			'index' => intval($this->DeviceIndex->Text)
		];
		$this->config[$this->DeviceName->Text] = $device;
		$result = $this->getModule('device_config')->setConfig($this->config);
		if ($result) {
			$this->getCallbackClient()->callClientFunction(
				'oAPIDevices.show_device_window',
				[false]
			);
			$this->setDeviceList(null, null);
		}
	}

	public function deleteDevice($sender, $param) {
		$device = $param->getCallbackParameter();
		if (!key_exists($device, $this->config)) {
			return;
		}
		unset($this->config[$device]);
		$result = $this->getModule('device_config')->setConfig($this->config);
		if ($result) {
			$this->setDeviceList(null, null);
		}
	}

	private function setConfigAutochangers() {
		$achs = [];
		try {
			$achs = $this->getModule('bacula_setting')->getConfig('sd', 'Autochanger');
		} catch (BConfigException $e) {
			// do nothing
		}
		$this->getCallbackClient()->callClientFunction(
			'oAPIAutochangers.set_config_autochangers',
			[$achs]
		);
	}

	private function setConfigDevices() {
		$devs = [];
		try {
			$devs = $this->getModule('bacula_setting')->getConfig('sd', 'Device');
		} catch (BConfigException $e) {
			// do nothing
		}
		$this->getCallbackClient()->callClientFunction(
			'oAPIDevices.set_config_devices',
			[$devs]
		);
	}
}
?>
