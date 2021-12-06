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

Prado::using('Application.API.Class.Bconsole');

/**
 * Label volume endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class VolumeLabel extends BaculumAPIServer {

	public function get() {
		$output = array();
		$misc = $this->getModule('misc');
		if ($this->Request->contains('out_id') && $misc->isValidAlphaNumeric($this->Request->itemAt('out_id'))) {
			$out_id = $this->Request->itemAt('out_id');
			$output = Bconsole::readOutputFile($out_id);
		}
		$this->output = $output;
		$this->error = VolumeError::ERROR_NO_ERRORS;
	}

	public function create($params) {
		$volume = property_exists($params, 'volume') ? $params->volume : 0;
		$slot = property_exists($params, 'slot') ? $params->slot : 0;
		$drive = property_exists($params, 'drive') ? intval($params->drive) : 0;
		$misc = $this->getModule('misc');

		if (!$misc->isValidName($volume)) {
			$this->output = VolumeError::MSG_ERROR_INVALID_VOLUME;
			$this->error = VolumeError::ERROR_INVALID_VOLUME;
			return;
		}

		if (!$misc->isValidInteger($slot)) {
			$this->output = VolumeError::MSG_ERROR_INVALID_SLOT;
			$this->error = VolumeError::ERROR_INVALID_SLOT;
			return;
		}

		$storage = null;
		if (property_exists($params, 'storageid')) {
			$storageid = intval($params->storageid);
			$result = $this->getModule('storage')->getStorageById($storageid);
			if (is_object($result)) {
				$storage = $result->name;
			}
		} elseif (property_exists($params, 'storage') && $misc->isValidName($params->storage)) {
			$storage = $params->storage;
		}

		$pool = null;
		if (property_exists($params, 'poolid')) {
			$poolid = intval($params->poolid);
			$result = $this->getModule('pool')->getPoolById($poolid);
			if (is_object($result)) {
				$pool = $result->name;
			}
		} elseif (property_exists($params, 'pool') && $misc->isValidName($params->pool)) {
			$pool = $params->pool;
		}

		if (!is_null($storage)) {
			$result = $this->getModule('bconsole')->bconsoleCommand(
				$this->director,
				array('.storage')
			);
			if ($result->exitcode === 0) {
				array_shift($result->output);
				if (!in_array($storage, $result->output)) {
					$this->output = StorageError::MSG_ERROR_STORAGE_DOES_NOT_EXISTS;
					$this->error = StorageError::ERROR_STORAGE_DOES_NOT_EXISTS;
					return;
				}
			} else {
				$this->output = $result->output;
				$this->error = $result->exitcode;
				return;
			}
		} else {
			$this->output = StorageError::MSG_ERROR_STORAGE_DOES_NOT_EXISTS;
			$this->error = StorageError::ERROR_STORAGE_DOES_NOT_EXISTS;
			return;
		}

		if (!is_null($pool)) {
			$result = $this->getModule('bconsole')->bconsoleCommand(
				$this->director,
				array('.pool')
			);
			if ($result->exitcode === 0) {
				array_shift($result->output);
				if (!in_array($pool, $result->output)) {
					$this->output = PoolError::MSG_ERROR_POOL_DOES_NOT_EXISTS;
					$this->error = PoolError::ERROR_POOL_DOES_NOT_EXISTS;
					return;
				}
			} else {
				$this->output = $result->output;
				$this->error = $result->exitcode;
				return;
			}
		} else {
			$this->output = PoolError::MSG_ERROR_POOL_DOES_NOT_EXISTS;
			$this->error = PoolError::ERROR_POOL_DOES_NOT_EXISTS;
			return;
		}

		$result = $this->getModule('volume')->getVolumeByName($volume);
		if (is_object($result)) {
			$this->output = VolumeError::MSG_ERROR_VOLUME_ALREADY_EXISTS;
			$this->error = VolumeError::ERROR_VOLUME_ALREADY_EXISTS;
			return;
		}

		$cmd = array(
			'label',
			'volume="' . $volume . '"',
			'storage="' . $storage . '"',
			'drive="' . $drive . '"',
			'slot="' . $slot . '"',
			'pool="' . $pool . '"'
		);
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			$cmd,
			Bconsole::PTYPE_BG_CMD
		);
		array_shift($result->output);
		if ($result->exitcode === 0) {
			$this->output = $result->output;
			$this->error = VolumeError::ERROR_NO_ERRORS;
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}
}

?>
