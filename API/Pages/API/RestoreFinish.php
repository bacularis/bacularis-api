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
use Bacularis\Common\Modules\Errors\JobError;

/**
 * Finalize the Bacula console restore session.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class RestoreFinish extends BaculumAPIServer
{
	public function create($params)
	{
		$misc = $this->getModule('misc');

		// Restore session identifier
		$session_id = property_exists($params, 'session-id') && $misc->isValidState($params->{'session-id'}) ? $params->{'session-id'} : '';

		// Restore client
		$restoreclient = null;
		$client_mod = $this->getModule('client');
		if (property_exists($params, 'restoreclientid')) {
			$restoreclientid = (int) ($params->restoreclientid);
			$restoreclient_row = $client_mod->getClientById($restoreclientid);
			$restoreclient = is_object($restoreclient_row) ? $restoreclient_row->name : null;
		} elseif (property_exists($params, 'restoreclient') && $misc->isValidName($params->restoreclient)) {
			$restoreclient = $params->restoreclient;
		}

		// Where to restore
		$where = property_exists($params, 'where') ? $params->where : null;

		// Replace mode - ifolder/ifnewer/never/always
		$replace = property_exists($params, 'replace') ? $params->replace : null;

		// Directory list to restore
		$directory = property_exists($params, 'directory') ? $params->directory : [];
		$dirs = [];
		if (is_array($directory)) {
			for ($i = 0; $i < count($directory); $i++) {
				if (!$misc->isValidPath($directory[$i])) {
					continue;
				}
				$dirs[] = $directory[$i];
			}
		}

		// File list to restore
		$file = property_exists($params, 'file') ? $params->file : [];
		$files = [];
		if (is_array($file)) {
			for ($i = 0; $i < count($file); $i++) {
				if (!$misc->isValidPath($file[$i])) {
					continue;
				}
				$files[] = $file[$i];
			}
		}

		$strip_prefix = null;
		if (property_exists($params, 'strip_prefix') && $misc->isValidPath($params->strip_prefix)) {
			$strip_prefix = $params->strip_prefix;
		}
		$add_prefix = null;
		if (property_exists($params, 'add_prefix') && $misc->isValidPath($params->add_prefix)) {
			$add_prefix = $params->add_prefix;
		}
		$add_suffix = null;
		if (property_exists($params, 'add_suffix') && $misc->isValidPath($params->add_suffix)) {
			$add_suffix = $params->add_suffix;
		}
		$regex_where = null;
		if (property_exists($params, 'regex_where') && $misc->isValidPath($params->regex_where)) {
			$regex_where = $params->regex_where;
		}

		if (!is_null($where) && !$misc->isValidPath($where)) {
			$this->output = JobError::MSG_ERROR_INVALID_WHERE_OPTION;
			$this->error = JobError::ERROR_INVALID_WHERE_OPTION;
			return;
		}

		if (!is_null($replace) && !$misc->isValidReplace($replace)) {
			$this->output = JobError::MSG_ERROR_INVALID_REPLACE_OPTION;
			$this->error = JobError::ERROR_INVALID_REPLACE_OPTION;
			return;
		}

		// Select files and directories to restore
		$result = $this->selectDirsFiles($session_id, $dirs, $files);
		if (!$result) {
			$emsg = ' Error while selecting restore dirs/files';
			$this->output = JobError::MSG_ERROR_INVALID_PATH . $emsg;
			$this->error = JobError::ERROR_INVALID_PATH;
			return;
		}

		// Finalize dir/file selection
		$result = $this->finishDirFileSelection($session_id);
		if (!$result) {
			$emsg = ' Error while finishing dir/file selection.';
			$this->output = JobError::MSG_ERROR_INVALID_COMMAND . $emsg;
			$this->error = JobError::ERROR_INVALID_COMMAND;
			return;
		}

		// Select destination path (where)
		if (is_string($where)) {
			$result = $this->selectWhere($session_id, $where);
			if (!$result) {
				$emsg = ' Error while modifying where parameter.';
				$this->output = JobError::MSG_ERROR_INVALID_WHERE_OPTION . $emsg;
				$this->error = JobError::ERROR_INVALID_WHERE_OPTION;
				return;
			}
		}

		// Select replace files option
		$result = $this->selectReplace($session_id, $replace);
		if (!$result) {
			$emsg = ' Error while modifying replace parameter.';
			$this->output = JobError::MSG_ERROR_INVALID_REPLACE_OPTION . $emsg;
			$this->error = JobError::ERROR_INVALID_REPLACE_OPTION;
			return;
		}

		// Select restore client
		$result = $this->selectRestoreClient($session_id, $restoreclient);
		if (!$result) {
			$emsg = ' Error while modifying restore client parameter.';
			$this->output = JobError::MSG_ERROR_INVALID_COMMAND . $emsg;
			$this->error = JobError::ERROR_INVALID_COMMAND;
			return;
		}

		// File relocation options
		$file_relocation = [];
		if ($strip_prefix) {
			$file_relocation['strip_prefix'] = $strip_prefix;
		}

		if ($add_prefix) {
			$file_relocation['add_prefix'] = $add_prefix;
		}

		if ($add_suffix) {
			$file_relocation['add_suffix'] = $add_suffix;
		}

		if ($regex_where) {
			$file_relocation['regex_where'] = $regex_where;
		}

		if ($file_relocation) {
			$result = $this->selectFileRelocationOption($session_id, 'modify', 'file_relocation_start');
			if (!$result) {
				$emsg = ' Error while starting file relocation menu.';
				$this->output = JobError::MSG_ERROR_INVALID_COMMAND . $emsg;
				$this->error = JobError::ERROR_INVALID_COMMAND;
				return;
			}
			foreach ($file_relocation as $name => $value) {
				$result = $this->selectFileRelocationOption($session_id, 'select', $name, $value);
				if (!$result) {
					$emsg = sprintf(' Error while modifying file relocation %s = %s parameter.', $name, $value);
					$this->output = JobError::MSG_ERROR_INVALID_COMMAND . $emsg;
					$this->error = JobError::ERROR_INVALID_COMMAND;
					return;
				}
			}
			$result = $this->selectFileRelocationOption($session_id, 'select', 'file_relocation_end');
			if (!$result) {
				$emsg = ' Error while ending file relocation menu.';
				$this->output = JobError::MSG_ERROR_INVALID_COMMAND . $emsg;
				$this->error = JobError::ERROR_INVALID_COMMAND;
				return;
			}
		}

		// Everything is fine - run restore
		$result = $this->runRestore($session_id);

		if ($result) {
			$this->output = $result;
			$this->error = JobError::ERROR_NO_ERRORS;
		} else {
			$emsg = ' Error while starting restore job. Please check logs.';
			$this->output = JobError::MSG_ERROR_INVALID_COMMAND . $emsg;
			$this->error = JobError::ERROR_INVALID_COMMAND;
		}
	}

	/**
	 * Exit the console restore file selection mode.
	 *
	 * @return bool true on success, false otherwise
	 */
	private function finishDirFileSelection(string $session_id): bool
	{
		$ret = $this->runCommand($session_id, 'done');
		return ($ret || false);
	}

	/**
	 * Select directories and files to restore.
	 *
	 * @param string $session_id session identifier
	 * @param array $dirs directory list to restore
	 * @param array $files file list to restore
	 * @return bool true on success, false otherwise
	 */
	private function selectDirsFiles(string $session_id, array $dirs, array $files): bool
	{
		$result_dirs = $this->selectDirs($session_id, $dirs);
		$result_files = $this->selectFiles($session_id, $files);
		return ($result_dirs && $result_files);
	}

	/**
	 * Select directories to restore.
	 *
	 * @param string $session_id session identifier
	 * @param array $dirs directory list to restore
	 * @return bool true on success, false otherwise
	 */
	private function selectDirs(string $session_id, array $dirs): bool
	{
		$success = true;
		$command = 'restore/command';
		for ($i = 0; $i < count($dirs); $i++) {
			$params = [
				'session-id' => $session_id,
				'command' => 'cd',
				'path' => $dirs[$i]
			];
			$result = BaculaConsole::execute($command, $params);
			if ($result['error'] != 0) {
				$result = false;
				break;
			}
			$params = [
				'session-id' => $session_id,
				'command' => 'markall'
			];
			$result = BaculaConsole::execute($command, $params);
			if ($result['error'] != 0) {
				$success = false;
				break;
			}
		}
		return $success;
	}

	/**
	 * Select files to restore.
	 *
	 * @param string $session_id session identifier
	 * @param array $files file list to restore
	 * @return bool true on success, false otherwise
	 */
	private function selectFiles(string $session_id, array $files): bool
	{
		$success = true;
		$command = 'restore/command';
		for ($i = 0; $i < count($files); $i++) {
			$ffile = basename($files[$i]);
			$fdir = preg_replace('/' . $ffile . '$/', '', $files[$i]);
			$params = [
				'session-id' => $session_id,
				'command' => 'cd',
				'path' => $fdir
			];
			$result = BaculaConsole::execute($command, $params);
			if ($result['error'] != 0) {
				$result = false;
				break;
			}
			$params = [
				'session-id' => $session_id,
				'command' => 'mark',
				'path' => $ffile
			];
			$result = BaculaConsole::execute($command, $params);
			if ($result['error'] != 0) {
				$success = false;
				break;
			}
		}
		return $success;
	}

	/**
	 * Type where to restore data.
	 *
	 * @param string $session_id session identifier
	 * @param string $where where destination path
	 * @param bool true on success, false otherwise
	 */
	private function selectWhere(string $session_id, string $where): bool
	{
		$ret = $this->runCommand($session_id, 'modify', 'where', $where);
		return ($ret || false);
	}

	/**
	 * Select data replace option.
	 * There is possible to set one of four values:
	 *  - never
	 *  - ifolder
	 *  - ifnewer
	 *  - always
	 *
	 * @param string $session_id session identifier
	 * @param string $replace replace option value
	 * @param bool true on success, false otherwise
	 */
	private function selectReplace(string $session_id, string $replace): bool
	{
		$ret = $this->runCommand($session_id, 'modify', 'replace', $replace);
		return ($ret || false);
	}

	/**
	 * Select restore file relocation option.
	 *
	 * @param string $session_id session identifier
	 * @param string $type option type
	 * @param string $option option name
	 * @param string $value option value
	 * @param bool true on success, false otherwise
	 */
	private function selectFileRelocationOption(string $session_id, string $type, string $option, string $value = ''): bool
	{
		$ret = $this->runCommand($session_id, $type, $option, $value);
		return ($ret || false);
	}

	/**
	 * Select destination restore client.
	 *
	 * @param string $session_id session identifier
	 * @param string $restore_client destination client to restore data
	 * @param bool true on success, false otherwise
	 */
	private function selectRestoreClient(string $session_id, string $restore_client): bool
	{
		$ret = $this->runCommand($session_id, 'modify', 'restore_client', $restore_client);
		return ($ret || false);
	}

	/**
	 * Run restore command.
	 *
	 * @param string $session_id session identifier
	 * @return array run restore command output
	 */
	private function runRestore(string $session_id): array
	{
		return $this->runCommand($session_id, 'yes');
	}

	/**
	 * Run single command in restore Bacula console environment.
	 *
	 * @param string $session_id session identifier
	 * @param string $cmd command name
	 * @param string $parameter command parameter name
	 * @param string $value command parameter value
	 * @param bool $async determines if command should be executed asynchronously
	 * @return array command output
	 */
	private function runCommand(string $session_id, string $cmd, string $parameter = '', string $value = '', bool $async = false): array
	{
		$command = 'restore/command';
		$params = [
			'session-id' => $session_id,
			'command' => $cmd,
			'parameter' => $parameter,
			'value' => $value,
			'async' => ($async ? 1 : 0)
		];
		$ret = [];
		$result = BaculaConsole::execute($command, $params);
		if ($result['error'] == 0) {
			$ret = $result['output'] ?: [1];
		}
		return $ret;
	}
}
