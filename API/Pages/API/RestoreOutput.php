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
use Bacularis\Common\Modules\Errors\GenericError;

/**
 * Run Bacula console restore command.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class RestoreOutput extends BaculumAPIServer
{
	private const FORMATTER_DIR_FILE_LIST = 'ls';
	private const FORMATTER_RESTORE_START = 'restore';

	public function get()
	{
		$misc = $this->getModule('misc');

		// Session identifier
		$session_id = $this->Request->contains('session-id') && $misc->isValidState($this->Request['session-id']) ? $this->Request['session-id'] : null;

		// Command identifier
		$command_id = $this->Request->contains('command-id') && $misc->isValidState($this->Request['command-id']) ? $this->Request['command-id'] : null;

		// Limits
		$offset = $this->Request->contains('offset') ? (int) $this->Request['offset'] : 0;
		$limit = $this->Request->contains('limit') ? (int) $this->Request['limit'] : 0;

		// Waiting time out (in miliseconds)
		$timeout = $this->Request->contains('timeout') && $misc->isValidInteger($this->Request['timeout']) ? (int) $this->Request['timeout'] : null;

		if (is_null($session_id)) {
			$this->output = BconsoleError::MSG_ERROR_INVALID_SESSION_ID;
			$this->error = BconsoleError::ERROR_INVALID_SESSION_ID;
			return;
		}
		if (is_null($command_id)) {
			$this->output = BconsoleError::MSG_ERROR_INVALID_COMMAND;
			$this->error = BconsoleError::ERROR_INVALID_COMMAND;
			return;
		}
		$command = 'restore/output';
		$params = [
			'session-id' => $session_id,
			'command-id' => $command_id,
		];
		if ($timeout) {
			$params['timeout'] = $timeout;
		}
		$result = BaculaConsole::execute($command, $params);
		if ($result['error'] == 0 && count($result['output']) > 0) {
			$parts = explode(' ', $result['output'][0]);
			$command = array_shift($parts);
			if ($command == self::FORMATTER_DIR_FILE_LIST) {
				$ret = self::parseLsOutput($result['output'], $offset, $limit);
				if (is_null($ret)) {
					// No dirs/files in the catalog - full restore needed
					$this->output = [];
					$this->error = GenericError::ERROR_INVALID_PATH;
				} else {
					// Dirs/files exist, list them
					$this->output = $ret;
					$this->error = $result['error'];
				}
			} elseif (strpos($command, self::FORMATTER_RESTORE_START) !== false) {
				// For restore start, nothing to show
				$this->output = [];
				$this->error = $result['error'];
			}
		} else {
			$this->output = $result['output'];
			$this->error = $result['error'];
		}

	}

	/**
	 * Parse directory/file list.
	 * Used if command to list dirs/files is used.
	 *
	 * @param array $output command output
	 * @param int $offset dir/file list offset
	 * @param int $limit dir/file list limit
	 * @return null|array dir/file list or null if no dir/file record in database and full restore is required.
	 */
	private static function parseLsOutput(array $output, int $offset, int $limit): ?array
	{
		$dirs = [];
		$files = [];
		$jobs = [];
		$olen = count($output);
		$listing = false;
		for ($i = 0; $i < $olen; $i++) {
			if (!$listing && preg_match('/^ls$/', $output[$i]) == 1) {
				// Begin listing, nothing to od
				$listing = true;
				continue;
			} elseif ($i == ($olen - 1) && preg_match('/^\$$/', $output[$i], $match) == 1) {
				// End listing, nothing to do
				$listing = false;
				continue;
			} elseif (preg_match('/^Do you want to restore all the files\? \(yes\|no\):/', $output[$i], $match) == 1) {
				// Dirs/files do not exists in the database. Full restore needed.
				return null;
			} elseif ($listing && preg_match('/^(?P<directory>.+)\/$/', $output[$i], $match) == 1) {
				// directory
				$dirs[] = [
					'name' => $match['directory'] . '/',
					'type' => 'dir'
				];
			} elseif ($listing && preg_match('/^(?P<file>.+)$/', $output[$i], $match) == 1) {
				// file
				$files[] = [
					'name' => $match['file'],
					'type' => 'file'
				];
			}
		}

		// Apply limits
		if ($offset > 0 || $limit > 0) {
			$dirs = array_slice(
				$dirs,
				$offset,
				$limit
			);
			$files = array_slice(
				$files,
				$offset,
				$limit
			);
		}
		return [
			'jobs' => $jobs,
			'dirs' => $dirs,
			'files' => $files
		];
	}
}
