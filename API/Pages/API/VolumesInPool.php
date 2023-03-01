<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2023 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
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

use Bacularis\Common\Modules\Errors\PoolError;
use Bacularis\Common\Modules\Errors\VolumeError;

/**
 * Volumes in pool endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class VolumesInPool extends BaculumAPIServer
{
	public function get()
	{
		$poolid = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.pool']
		);
		if ($result->exitcode === 0) {
			array_shift($result->output);
			$pool = $this->getModule('pool')->getPoolById($poolid);
			if (is_object($pool) && in_array($pool->name, $result->output)) {
				$result = $this->getModule('volume')->getVolumesByPoolId($poolid);
				$this->output = $result;
				$this->error = VolumeError::ERROR_NO_ERRORS;
			} else {
				$this->output = PoolError::MSG_ERROR_POOL_DOES_NOT_EXISTS;
				$this->error = PoolError::ERROR_POOL_DOES_NOT_EXISTS;
			}
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}
}
