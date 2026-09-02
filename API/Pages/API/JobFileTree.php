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
use Bacularis\Common\Modules\Errors\JobError;

/**
 * Get full directory tree from elementary backup jobids.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class JobFileTree extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$jobids = $this->Request->contains('jobids') && $misc->isValidIdsList($this->Request['jobids']) ? ($this->Request['jobids']) : null;

		// Get job records for jobids
		$jobs = $jids = [];
		$job_mod = $this->getModule('job');
		if (is_string($jobids)) {
			$jids = explode(',', $jobids);
			$params = [
				'Job.JobId' => [[
					'operator' => 'IN',
					'vals' => $jids
				]]
			];
			$jobs = $job_mod->getJobs($params);
		}

		// Check if all jobids belong to the same job
		$error = false;
		$job_name = null;
		for ($i = 0; $i < count($jobs); $i++) {
			if ($i == 0) {
				$job_name = $jobs[$i]->name;
				continue;
			}
			if ($jobs[$i]->name != $job_name) {
				$error = true;
				break;
			}
		}
		if ($error) {
			$this->error = JobError::ERROR_INVALID_COMMAND;
			$this->output = JobError::MSG_ERROR_INVALID_COMMAND;
			return;
		}

		// Get allowed jobs
		$bconsole = $this->getModule('bconsole');
		$result = $bconsole->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);

		if ($result->exitcode == 0) {
			if (in_array($job_name, $result->output)) {
				$result = $job_mod->getJobFileTree($jids);
				$this->output = $result;
				$this->error = JobError::ERROR_NO_ERRORS;
			} else {
				$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
				$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
			}
		} else {
			$emsg = sprintf('Error: %d, Output: %s.', $result->exitcode, $result->output);
			$this->output = JobError::MSG_ERROR_WRONG_EXITCODE . $emsg;
			$this->error = JobError::ERROR_WRONG_EXITCODE;
		}
	}
}
