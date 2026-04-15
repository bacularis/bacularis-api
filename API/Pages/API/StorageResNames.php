<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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
use Bacularis\Common\Modules\Errors\BconsoleError;

/**
 * Storage resource names endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class StorageResNames extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$offset = $this->Request->contains('offset') ? (int) ($this->Request['offset']) : 0;
		$limit = $this->Request->contains('limit') ? (int) ($this->Request['limit']) : 0;
		$search = $this->Request->contains('search') ? $this->Request['search'] : null;

		if (is_string($search) && !$misc->isValidName($this->Request['search'])) {
			$this->output = BconsoleError::MSG_ERROR_INVALID_COMMAND;
			$this->error = BconsoleError::ERROR_INVALID_COMMAND;
			return;
		}

		$storage_cmd = ['.storage'];
		$bconsole = $this->getModule('bconsole');
		$storages = $bconsole->bconsoleCommand(
			$this->director,
			$storage_cmd,
			null,
			true
		);
		$error = $storages->exitcode !== 0;

		if (!$error && $storages->output && isset($search)) {
			$misc::filterList($storages->output, "*{$search}*");
		}

		if ($offset > 0 || $limit > 0) {
			if ($limit == 0) {
				$limit = null;
			}
			$storages->output = array_slice($storages->output, $offset, $limit);
		}

		if (!$error) {
			$this->output = $storages->output;
			$this->error = BconsoleError::ERROR_NO_ERRORS;
		} else {
			$emsg = var_export($storages->output, true);
			$this->output = BconsoleError::MSG_ERROR_WRONG_EXITCODE . " ExitCode: {$storages->exitcode}, Output: {$emsg}.";
			$this->error = BconsoleError::ERROR_WRONG_EXITCODE;
		}
	}
}
