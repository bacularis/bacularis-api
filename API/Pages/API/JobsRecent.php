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
use Bacularis\Common\Modules\Errors\FileSetError;
use Bacularis\Common\Modules\Errors\JobError;

/**
 * Recent jobs endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class JobsRecent extends BaculumAPIServer
{
	public function get()
	{
		$jobname = $this->Request->contains('name') ? $this->Request['name'] : '';
		$inc_copy_job = $this->Request->contains('inc_copy_job') ? (int) ($this->Request['inc_copy_job']) : 0;
		$clientid = null;
		if ($this->Request->contains('clientid')) {
			$clientid = (int) ($this->Request['clientid']);
		} elseif ($this->Request->contains('client') && $this->getModule('misc')->isValidName($this->Request['client'])) {
			$client = $this->Request['client'];
			$client_row = $this->getModule('client')->getClientByName($client);
			if (is_object($client_row)) {
				$clientid = (int) ($client_row->clientid);
			}
		}
		$filesetid = null;
		if ($this->Request->contains('filesetid')) {
			$filesetid = (int) ($this->Request['filesetid']);
		} elseif ($this->Request->contains('fileset') && $this->getModule('misc')->isValidName($this->Request['fileset'])) {
			$fileset = $this->Request['fileset'];
			$fileset_row = $this->getModule('fileset')->getFileSetByName($fileset);
			if (is_object($fileset_row)) {
				$filesetid = (int) ($fileset_row->filesetid);
			}
		}

		if (is_null($clientid)) {
			$this->output = ClientError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = ClientError::ERROR_CLIENT_DOES_NOT_EXISTS;
		} elseif (is_null($filesetid)) {
			$this->output = FileSetError::MSG_ERROR_FILESET_DOES_NOT_EXISTS;
			$this->error = FileSetError::ERROR_FILESET_DOES_NOT_EXISTS;
		} else {
			$result = $this->getModule('bconsole')->bconsoleCommand(
				$this->director,
				['.jobs'],
				null,
				true
			);
			if ($result->exitcode === 0) {
				if (in_array($jobname, $result->output)) {
					$jobs = $this->getModule('job')->getRecentJobids($jobname, $clientid, $filesetid, $inc_copy_job);
					if (is_array($jobs)) {
						$this->output = $jobs;
						$this->error = JobError::ERROR_NO_ERRORS;
					} else {
						$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
						$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
					}
				} else {
					$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
					$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
				}
			} else {
				$this->output = $result->output;
				$this->error = $result->exitcode;
			}
		}
	}
}
