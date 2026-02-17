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
use Bacularis\Common\Modules\Errors\ClientError;

/**
 * Disable client endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class ClientDisable extends BaculumAPIServer
{
	public function set($id, $params)
	{
		$cli = null;
		if ($id > 0) {
			$client = $this->getModule('client');
			$cli = $client->getClientById($id);
		}

		$client_name = null;
		$bconsole = $this->getModule('bconsole');
		$result = $bconsole->bconsoleCommand(
			$this->director,
			['.client'],
			null,
			true
		);
		if ($result->exitcode === 0) {
			if (is_object($cli) && in_array($cli->name, $result->output)) {
				$client_name = $cli->name;
			}
		}
		if (is_null($client_name)) {
			$this->output = ClientError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = ClientError::ERROR_CLIENT_DOES_NOT_EXISTS;
			return;
		}

		$cmd = ['disable', 'client="' . $client_name . '"'];
		$result = $bconsole->bconsoleCommand(
			$this->director,
			$cmd
		);
		if ($result->exitcode === 0) {
			$this->output = $result->output;
			$this->error = ClientError::ERROR_NO_ERRORS;
		} else {
			$emsg = sprintf(
				' Error: %s, ExitCode: %d.',
				var_export($result->output, true),
				$result->exitcode
			);
			$this->output = ClientError::MSG_ERROR_WRONG_EXITCODE . $emsg;
			$this->error = ClientError::ERROR_WRONG_EXITCODE;
		}
	}
}
