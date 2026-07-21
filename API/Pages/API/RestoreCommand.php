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
use Bacularis\API\Modules\BaculaConsole;
use Bacularis\Common\Modules\Errors\BconsoleError;

/**
 * Run Bacula console restore command.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class RestoreCommand extends BaculumAPIServer
{
	public function create($params)
	{
		$misc = $this->getModule('misc');

		// Session identifier
		$session_id = property_exists($params, 'session-id') && $misc->isValidState($params->{'session-id'}) ? $params->{'session-id'} : null;

		// Command name
		$bcommand = property_exists($params, 'command') && $misc->isValidState($params->command) ? $params->command : null;

		// Optional parameter - path marked to restore
		$path = property_exists($params, 'path') && $misc->isValidPath($params->path) ? $params->path : null;
		$async = property_exists($params, 'async') && $misc->isValidBooleanTrue($params->async) ? 1 : 0;

		if (is_null($session_id)) {
			$this->output = BconsoleError::MSG_ERROR_INVALID_SESSION_ID;
			$this->error = BconsoleError::ERROR_INVALID_SESSION_ID;
			return;
		}
		if (is_null($bcommand)) {
			$this->output = BconsoleError::MSG_ERROR_INVALID_COMMAND;
			$this->error = BconsoleError::ERROR_INVALID_COMMAND;
			return;
		}

		$command = 'restore/command';
		$params = [
			'session-id' => $session_id,
			'command' => $bcommand,
			'path' => $path,
			'async' => $async
		];
		$result = BaculaConsole::execute($command, $params);
		if ($result['error'] == 0) {
			$this->output = $result['output'];
		} else {
			if (is_array($result['output']) && count($result['output']) == 1) {
				// Standard error message
				$this->output = $result['output'][0];
			} else {
				// Long output with error
				$this->output = $result['output'];
			}
		}
		$this->error = $result['error'];
	}
}
