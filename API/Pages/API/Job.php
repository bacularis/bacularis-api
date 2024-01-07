<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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
use Bacularis\Common\Modules\Errors\JobError;

/**
 * Job endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class Job extends BaculumAPIServer
{
	public function get()
	{
		$jobid = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);
		if ($result->exitcode === 0) {
			$params = [
				'Job.Name' => [
					'operator' => 'IN',
					'vals' => $result->output
				]
			];
			$job = $this->getModule('job')->getJobById($jobid, $params);
			if (is_object($job) && in_array($job->name, $result->output)) {
				$this->output = $job;
				$this->error = JobError::ERROR_NO_ERRORS;
			} else {
				$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
				$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
			}
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}

	public function remove($id)
	{
		$jobid = (int) $id;
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);
		if ($result->exitcode === 0) {
			$job = $this->getModule('job')->getJobById($jobid);
			if (is_object($job) && in_array($job->name, $result->output)) {
				$result = $this->getModule('bconsole')->bconsoleCommand(
					$this->director,
					['delete', 'jobid="' . $job->jobid . '"']
				);
				$this->output = $result->output;
				$this->error = $result->exitcode;
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
