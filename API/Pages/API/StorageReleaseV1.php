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

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\Common\Modules\Errors\StorageError;

/**
 * Release storage command endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class StorageReleaseV1 extends BaculumAPIServer
{
	public function get()
	{
		$storageid = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;
		$drive = $this->Request->contains('drive') ? (int) ($this->Request['drive']) : 0;
		$device = $this->Request->contains('device') ? $this->Request['device'] : null;

		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.storage']
		);

		if ($result->exitcode === 0) {
			array_shift($result->output);
			$storage = $this->getModule('storage')->getStorageById($storageid);
			if (is_object($storage) && in_array($storage->name, $result->output)) {
				$result = $this->getModule('bconsole')->bconsoleCommand(
					$this->director,
					[
						'release',
						'storage="' . $storage->name . '"',
						(is_string($device) ? 'device="' . $device . '" drive=0 slot=0' : 'drive=' . $drive . ' slot=0')
					]
				);
				$this->output = $result->output;
				$this->error = $result->exitcode;
			} else {
				$this->output = StorageError::MSG_ERROR_STORAGE_DOES_NOT_EXISTS;
				$this->error = StorageError::ERROR_STORAGE_DOES_NOT_EXISTS;
			}
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}
}
