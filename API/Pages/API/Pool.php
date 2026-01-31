<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\API\Modules\Bconsole;
use Bacularis\Common\Modules\Errors\BaculaConfigError;
use Bacularis\Common\Modules\Errors\PoolError;

/**
 * Pool endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class Pool extends BaculumAPIServer
{
	public function get()
	{
		$poolid = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.pool'],
			null,
			true
		);
		if ($result->exitcode === 0) {
			$pool = $this->getModule('pool')->getPoolById($poolid);
			if (!is_null($pool) && in_array($pool->name, $result->output)) {
				$this->output = $pool;
				$this->error = PoolError::ERROR_NO_ERRORS;
			} else {
				$this->output = PoolError::MSG_ERROR_POOL_DOES_NOT_EXISTS;
				$this->error = PoolError::ERROR_POOL_DOES_NOT_EXISTS;
			}
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}

	public function remove($id)
	{
		$poolid = (int) $id;

		// Get pool from catalog
		$pool = $this->getModule('pool');
		$pool_obj = $pool->getPoolById($poolid);
		if (is_null($pool_obj)) {
			$this->output = PoolError::MSG_ERROR_POOL_DOES_NOT_EXISTS;
			$this->error = PoolError::ERROR_POOL_DOES_NOT_EXISTS;
			return;
		}

		// Get pool config
		$bsettings = $this->getModule('bacula_setting');
		$config = $bsettings->getConfig(
			'dir',
			'Pool',
			$pool_obj->name
		);
		if ($config['exitcode'] != 0) {
			$this->output = PoolError::MSG_ERROR_WRONG_EXITCODE . ' ' . var_export($config['output'], true);
			$this->error = PoolError::ERROR_WRONG_EXITCODE;
			return;
		}

		// Get pools
		$bconsole = $this->getModule('bconsole');
		$pools = $bconsole->bconsoleCommand(
			$this->director,
			['.pool'],
			null,
			true
		);
		if ($pools->exitcode != 0) {
			$this->output = PoolError::MSG_ERROR_WRONG_EXITCODE . ' ' . var_export($pools->output, true);
			$this->error = PoolError::ERROR_WRONG_EXITCODE;
			return;
		}

		// Check if pool config does not exist.
		if (count($config['output']) > 0) {
			// Pool config exists - end
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_ALREADY_EXISTS;
			$this->error = BaculaConfigError::ERROR_CONFIG_ALREADY_EXISTS;
			return;
		}

		// Check if pool exists on config pool list
		if (in_array($pool_obj->name, $pools->output)) {
			/**
			 * Pool does not exists in configuration but exists in Director memory.
			 * It looks that it is removed from config but the Director configuration has not been reloaded yet.
			 */
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_DOES_NOT_EXIST;
			$this->error = BaculaConfigError::ERROR_CONFIG_DOES_NOT_EXIST;
			return;
		}
		$volume = $this->getModule('volume');
		$volumes = $volume->getVolumesByPoolId($poolid);
		if (count($volumes) > 0) {
			// volumes in pool - error
			$this->output = PoolError::MSG_ERROR_POOL_NOT_EMPTY;
			$this->error = PoolError::ERROR_POOL_NOT_EMPTY;
			return;
		}

		// No volume in pool - delete it
		$result = $bconsole->bconsoleCommand(
			$this->director,
			['delete', 'pool="' . $pool_obj->name . '"'],
			Bconsole::PTYPE_CONFIRM_YES_CMD
		);
		$this->output = $result->output;
		$this->error = $result->exitcode;
	}
}
