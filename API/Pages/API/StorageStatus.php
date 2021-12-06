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

Prado::using('Application.API.Class.ConsoleOutputPage');
 
/**
 * Storage status command endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class StorageStatus extends ConsoleOutputPage {

	public function get() {
		$storageid = $this->Request->contains('id') ? intval($this->Request['id']) : 0;
		$storage = $this->getModule('storage')->getStorageById($storageid);
		$status = $this->getModule('status_sd');
		$type = $this->Request->contains('type') && $status->isValidOutputType($this->Request['type']) ? $this->Request['type'] : null;
		$out_format = $this->Request->contains('output') && $this->isOutputFormatValid($this->Request['output']) ? $this->Request['output'] : parent::OUTPUT_FORMAT_RAW;
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			array('.storage')
		);

		$storage_exists = false;
		if ($result->exitcode === 0) {
			$storage_exists = (is_object($storage) && in_array($storage->name, $result->output));
		}

		if ($storage_exists == false) {
			// Storage doesn't exist or is not available for user because of ACL restrictions
			$this->output = StorageError::MSG_ERROR_STORAGE_DOES_NOT_EXISTS;
			$this->error = StorageError::ERROR_STORAGE_DOES_NOT_EXISTS;
			return;
		}

		$out = (object)['output' => [], 'error' => 0];
		if ($out_format === parent::OUTPUT_FORMAT_RAW) {
			$out = $this->getRawOutput(['storage' => $storage->name]);
		} elseif ($out_format === parent::OUTPUT_FORMAT_JSON) {
			$out = $this->getJSONOutput([
				'storage' => $storage->name,
				'type' => $type
			]);
		}
		$this->output = $out['output'];
		$this->error = $out['error'];
	}

	protected function getRawOutput($params = []) {
		// traditional status storage output
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			[
				'status',
				'storage="' . $params['storage'] . '"'
			]
		);
		$error = $result->exitcode == 0 ? $result->exitcode : GenericError::ERROR_WRONG_EXITCODE;
		$ret = [
			'output' => $result->output,
			'error' => $error
		];
		return $ret;
	}

	protected function getJSONOutput($params = []) {
		// status storage JSON output by API 2 interface
		$status = $this->getModule('status_sd');
		return $status->getStatus(
			$this->director,
			$params['storage'],
			$params['type']
		);
	}
}

?>
