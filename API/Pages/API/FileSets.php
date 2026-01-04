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
use Bacularis\Common\Modules\Errors\FileSetError;
use Bacularis\Common\Modules\Errors\JobError;

/**
 * FileSets endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class FileSets extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$limit = $this->Request->contains('limit') ? (int) ($this->Request['limit']) : 0;
		$job = $this->Request->contains('job') && $misc->isValidName($this->Request['job']) ? $this->Request['job'] : null;
		$fss = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.fileset'],
			null,
			true
		);
		if ($fss->exitcode === 0) {
			$filesets = [];
			if (is_string($job)) {
				// Get fileset by job name
				$jobs = $this->getModule('bconsole')->bconsoleCommand(
					$this->director,
					['.jobs'],
					null,
					true
				);
				if ($jobs->exitcode === 0) {
					if (in_array($job, $jobs->output)) {
						$filesets = $this->getModule('fileset')->getFileSetsByJob($job);
					} else {
						$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
						$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
						return;
						// END
					}
				}
			} else {
				// Get all filesets
				$filesets = $this->getModule('fileset')->getFileSets($limit);
			}
			if (is_array($filesets)) {
				$fs = [];
				for ($i = 0; $i < count($filesets); $i++) {
					if (in_array($filesets[$i]->fileset, $fss->output)) {
						$fs[] = $filesets[$i];
					}
				}
				$this->output = $fs;
				$this->error = FileSetError::ERROR_NO_ERRORS;
			} else {
				$this->output = FileSetError::MSG_ERROR_FILESET_DOES_NOT_EXISTS;
				$this->error = FileSetError::ERROR_FILESET_DOES_NOT_EXISTS;
			}
		} else {
			$this->output = FileSetError::MSG_ERROR_WRONG_EXITCODE . 'ExitCode => ' . $fss->exitcode . ', Output => ' . var_export($fss->output, true);
			$this->error = FileSetError::ERROR_WRONG_EXITCODE;
		}
	}
}
