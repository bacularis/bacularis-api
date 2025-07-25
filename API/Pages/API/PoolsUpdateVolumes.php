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
use Bacularis\Common\Modules\Errors\PoolError;

/**
 * Update volumes in all pools command endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class PoolsUpdateVolumes extends BaculumAPIServer
{
	public function set($id, $params)
	{
		$bconsole = $this->getModule('bconsole');
		$result = $bconsole->bconsoleCommand(
			$this->director,
			['update', 'volume', 'fromallpools']
		);
		if ($result->exitcode != 0) {
			$out = var_export($result->output, true);
			$this->output = PoolError::MSG_ERROR_WRONG_EXITCODE . ", Output: '{$out}' ExitCode: {$result->exitcode}";
			$this->error = PoolError::ERROR_WRONG_EXITCODE;
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}
}
