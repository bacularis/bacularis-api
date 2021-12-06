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
 * Autochanger list all slots.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class ChangerListAll extends BaculumAPIServer {

	const LIST_ALL_DRIVE_PATTERN = '/^D:(?P<index>\d+):(?P<state>[EF]):?(?P<slot>\d+)?:?(?P<volume>\S+)?$/';
	const LIST_ALL_SLOT_PATTERN = '/^S:(?P<slot>\d+):(?P<state>[EF]):?(?P<volume>\S+)?$/';
	const LIST_ALL_IO_SLOT_PATTERN = '/^I:(?P<slot>\d+):(?P<state>[EF]):?(?P<volume>\S+)?$/';

	public function get() {
		$misc = $this->getModule('misc');
		$device_name = $this->Request->contains('device_name') && $misc->isValidName($this->Request['device_name']) ? $this->Request['device_name'] : null;

		if (is_null($device_name)) {
			$output = DeviceError::MSG_ERROR_DEVICE_AUTOCHANGER_DOES_NOT_EXIST;
			$error = DeviceError::ERROR_DEVICE_AUTOCHANGER_DOES_NOT_EXIST;
			return;
		}

		$result = $this->getModule('changer_command')->execChangerCommand(
			$device_name,
			'listall'
		);

		if ($result->error === 0) {
			$this->output = $this->parseListAll($device_name, $result->output);
		} else {
			$this->output = $result->output;
		}
		$this->error = $result->error;
	}

	private function parseListAll($device_name, $output) {
		$list = ['drives' => [], 'slots' => [], 'ie_slots' => []];
		$drives = $this->getModule('device_config')->getChangerDrives($device_name);
		$volumes = [];
		$db_params = $this->getModule('api_config')->getConfig('db');
		if (key_exists('enabled', $db_params) && $db_params['enabled'] == 1) {
			/**
			 * Volume information is provided only if on API host with autochanger
			 * enabled is access to the Catalog database for the API instance.
			 */
			$volumes = $this->getModule('volume')->getVolumesKeys();
		}
		$get_volume_info  = function($volname) use ($volumes) {
			$volume = [
				'mediaid' => 0,
				'volume' => '',
				'mediatype' => '',
				'pool' => '',
				'lastwritten' => '',
				'whenexpire' => '',
				'volbytes' => '',
				'volstatus' => '',
				'slot' => ''
			];
			if (key_exists($volname, $volumes)) {
				$volume['mediaid'] = intval($volumes[$volname]->mediaid);
				$volume['mediatype'] = $volumes[$volname]->mediatype;
				$volume['pool'] = $volumes[$volname]->pool;
				$volume['lastwritten'] = $volumes[$volname]->lastwritten;
				$volume['whenexpire'] = $volumes[$volname]->whenexpire;
				$volume['volbytes'] = $volumes[$volname]->volbytes;
				$volume['volstatus'] = $volumes[$volname]->volstatus;
				$volume['slot'] = $volumes[$volname]->slot;
			}
			return $volume;
		};
		for ($i = 0; $i < count($output); $i++) {
			if (preg_match(self::LIST_ALL_DRIVE_PATTERN, $output[$i], $match) == 1) {
				$index = intval($match['index']);
				if (!key_exists($index, $drives)) {
					continue;
				}
				$drive = $drives[$index]['name'];
				$device = $drives[$index]['device'];
				$volume = '';
				if (key_exists('volume', $match)) {
					$volume = $match['volume'];
				}
				$volinfo = $get_volume_info($volume);
				$list['drives'][] = [
					'type' => 'drive',
					'index' => $index,
					'drive' => $drive,
					'device' => $device,
					'state' => $match['state'],
					'slot_ach' => key_exists('slot', $match) ? intval($match['slot']) : '',
					'mediaid' => $volinfo['mediaid'],
					'volume' => $volume,
					'mediatype' => $volinfo['mediatype'],
					'pool' => $volinfo['pool'],
					'lastwritten' => $volinfo['lastwritten'],
					'whenexpire' => $volinfo['whenexpire'],
					'volbytes' => $volinfo['volbytes'],
					'volstatus' => $volinfo['volstatus'],
					'slot_cat' => $volinfo['slot']
				];
			} elseif (preg_match(self::LIST_ALL_SLOT_PATTERN, $output[$i], $match) == 1) {
				$volume = '';
				if (key_exists('volume', $match)) {
					$volume = $match['volume'];
				}
				$volinfo = $get_volume_info($volume);
				$list['slots'][] = [
					'type' => 'slot',
					'slot_ach' => intval($match['slot']),
					'state' => $match['state'],
					'mediaid' => $volinfo['mediaid'],
					'volume' => $volume,
					'mediatype' => $volinfo['mediatype'],
					'pool' => $volinfo['pool'],
					'lastwritten' => $volinfo['lastwritten'],
					'whenexpire' => $volinfo['whenexpire'],
					'volbytes' => $volinfo['volbytes'],
					'volstatus' => $volinfo['volstatus'],
					'slot_cat' => $volinfo['slot']
				];
			} elseif (preg_match(self::LIST_ALL_IO_SLOT_PATTERN, $output[$i], $match) == 1) {
				$volume = '';
				if (key_exists('volume', $match)) {
					$volume = $match['volume'];
				}
				$volinfo = $get_volume_info($volume);
				$list['ie_slots'][] = [
					'type' => 'ie_slot',
					'slot_ach' => intval($match['slot']),
					'state' => $match['state'],
					'mediaid' => $volinfo['mediaid'],
					'volume' => $volume,
					'mediatype' => $volinfo['mediatype'],
					'pool' => $volinfo['pool'],
					'lastwritten' => $volinfo['lastwritten'],
					'whenexpire' => $volinfo['whenexpire'],
					'volbytes' => $volinfo['volbytes'],
					'volstatus' => $volinfo['volstatus'],
					'slot_cat' => $volinfo['slot']
				];
			}
		}
		return $list;
	}
}
?>
