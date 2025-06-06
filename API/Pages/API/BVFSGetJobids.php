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
use Bacularis\Common\Modules\Errors\BVFSError;

/**
 * BVFS get jobids to do restore.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class BVFSGetJobids extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$jobid = $this->Request->contains('jobid') ? (int) ($this->Request['jobid']) : 0;
		$inc_copy_job = $this->Request->contains('inc_copy_job') && $misc->isValidBooleanTrue($this->Request['inc_copy_job']);
		if ($jobid > 0) {
			$result = [];
			$error = -1;
			$job = $this->getModule('job');
			$jobobj = $job->getJobById($jobid);
			if ($inc_copy_job && is_object($jobobj) && $jobobj->type == 'C') {
				/**
				 * .bvfs_get_jobids does not support copy jobs and returns wrong results.
				 * Because of that to select compositional jobids, we use own algorithm for copy jobs.
				 */
				$jobids = $job->getJobidsToRestore($jobobj->jobid);
				$jobids_str = implode(',', $jobids); // implode to be compatible with Bvfs output
				if (!empty($jobids_str)) {
					$result = [$jobids_str];
					$error = BVFSError::ERROR_NO_ERRORS;
				} else {
					$error = BVFSError::ERROR_INVALID_JOBID;
				}
			} else {
				$cmd = ['.bvfs_get_jobids', 'jobid="' . $jobid . '"'];
				$jobids = $this->getModule('bconsole')->bconsoleCommand(
					$this->director,
					$cmd
				);
				$result = $jobids->exitcode !== 0 ? BVFSError::MSG_ERROR_WRONG_EXITCODE . 'ExitCode=' . $jobids->exitcode : $jobids->output;
				$error = $jobids->exitcode !== 0 ? BVFSError::ERROR_WRONG_EXITCODE : BVFSError::ERROR_NO_ERRORS;
			}
			$this->output = $result;
			$this->error = $error;
		} else {
			$this->output = BVFSError::MSG_ERROR_INVALID_JOBID;
			$this->error = BVFSError::ERROR_INVALID_JOBID;
		}
	}
}
