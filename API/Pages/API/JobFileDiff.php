<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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
use Bacularis\API\Modules\JobManager;
use Bacularis\Common\Modules\Errors\JobError;

/**
 * Job file differences endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class JobFileDiff extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$method = $this->Request->contains('method') && $misc->isValidDiffMethod($this->Request['method']) ? $this->Request['method'] : JobManager::FILE_DIFF_METHOD_A_AND_B;
		$jobname = $this->Request->contains('name') && $misc->isValidName($this->Request['name']) ? $this->Request['name'] : '';
		$start_jobid = $this->Request->contains('start_id') ? (int) ($this->Request['start_id']) : 0;
		$end_jobid = $this->Request->contains('end_id') ? (int) ($this->Request['end_id']) : 0;

		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);

		if ($result->exitcode === 0) {
			if (empty($jobname) || !in_array($jobname, $result->output)) {
				$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
				$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
				return;
			}
		}
		$params = [
			'Job.JobId' => [
				'operator' => 'OR',
				'vals' => [$start_jobid, $end_jobid]
			],
			'Job.Name' => [
				'operator' => 'AND',
				'vals' => $jobname
			]
		];

		$job = $this->getModule('job');
		$jobs = $job->getJobs($params);
		if (count($jobs) != 2) {
			$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
			return;
		}

		$this->output = $job->getJobFileDiff($method, $jobname, $start_jobid, $end_jobid);
		$this->error = JobError::ERROR_NO_ERRORS;
	}
}
