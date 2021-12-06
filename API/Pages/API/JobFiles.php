<?php
/*
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2020 Kern Sibbald
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

/**
 * Job files endpoint.
 * It finds job by file criteria.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class JobFiles extends BaculumAPIServer {

	public function get() {
		$misc = $this->getModule('misc');
		$filename = $this->Request->contains('filename') && $misc->isValidFilename($this->Request['filename']) ? $this->Request['filename'] : null;
		$strict_mode = ($this->Request->contains('strict') && $misc->isValidBooleanTrue($this->Request['strict']));
		$path = $this->Request->contains('path') && $misc->isValidPath($this->Request['path']) ? $this->Request['path'] : '';

		$clientid = null;
		if ($this->Request->contains('clientid')) {
			$clientid = intval($this->Request['clientid']);
		} elseif ($this->Request->contains('client') && $this->getModule('misc')->isValidName($this->Request['client'])) {
			$client_row = $this->getModule('client')->getClientByName($this->Request['client']);
			$clientid = is_object($client_row) ? intval($client_row->clientid) : null;
		}

		if (is_null($clientid)) {
			$this->output = JobError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_CLIENT_DOES_NOT_EXISTS;
			return;
		}

		if (is_null($filename)) {
			$this->output = JobError::MSG_ERROR_INVALID_FILENAME;
			$this->error = JobError::ERROR_INVALID_FILENAME;
			return;
		}

		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);

		if ($result->exitcode === 0) {
			if (count($result->output) == 0) {
				// no allowed jobs means that user has no job resource assigned.
				$this->output = [];
				$this->error = JobError::ERROR_NO_ERRORS;
			} else {
				$job = $this->getModule('job')->getJobsByFilename($clientid, $filename, $strict_mode, $path, $result->output);
				$this->output = $job;
				$this->error = JobError::ERROR_NO_ERRORS;
			}
		} else {
			$result = is_array($result->output) ? implode('', $result->output) : $result->output;
			$this->output = JobError::MSG_ERROR_WRONG_EXITCODE . $result;
			$this->error = JobError::ERROR_WRONG_EXITCODE;
		}
	}
}
?>
