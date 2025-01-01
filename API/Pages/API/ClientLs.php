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
use Bacularis\Common\Modules\Errors\ClientError;
use Bacularis\Common\Modules\Errors\GenericError;

/**
 * Client list directories.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class ClientLs extends BaculumAPIServer
{
	public function get()
	{
		$clientid = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;
		$client = null;
		$cli = null;
		if ($clientid > 0) {
			$cli = $this->getModule('client')->getClientById($clientid);
		}

		$result = $this->getModule('bconsole')->bconsoleCommand($this->director, ['.client']);
		if ($result->exitcode === 0) {
			array_shift($result->output);
			if (is_object($cli) && in_array($cli->name, $result->output)) {
				$client = $cli->name;
			}
		}
		if (is_null($client)) {
			$this->output = ClientError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = ClientError::ERROR_CLIENT_DOES_NOT_EXISTS;
			return;
		}

		$path = $this->Request->contains('path') && $this->getModule('misc')->isValidPath($this->Request['path']) ? $this->Request['path'] : null;

		if (is_null($path)) {
			$this->output = GenericError::MSG_ERROR_INVALID_PATH;
			$this->error = GenericError::ERROR_INVALID_PATH;
			return;
		}

		$cmd = ['.ls', 'client="' . $client . '"', 'path="' . $path . '"'];
		$result = $this->getModule('bconsole')->bconsoleCommand($this->director, $cmd);
		if ($result->exitcode === 0) {
			$ls = $this->getModule('ls')->parseOutput($result->output);
			$this->output = $ls;
			$this->error = GenericError::ERROR_NO_ERRORS;
		}
	}
}
