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

use Bacularis\API\Modules\ConsoleOutputPage;
use Bacularis\Common\Modules\Errors\BVFSError;

/**
 * BVFS get elementary jobids (full/incremental/differential).
 * This is useful to do prepare consistent backup job restore.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class BVFSGetJobids extends ConsoleOutputPage
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$jobid = $this->Request->contains('jobid') ? (int) ($this->Request['jobid']) : 0;
		$out_format = $this->Request->contains('output') && $this->isOutputFormatValid($this->Request['output']) ? $this->Request['output'] : parent::OUTPUT_FORMAT_RAW;
		$inc_copy_job = $this->Request->contains('inc_copy_job') && $misc->isValidBooleanTrue($this->Request['inc_copy_job']);
		if ($jobid <= 0) {
			$this->output = BVFSError::MSG_ERROR_INVALID_JOBID;
			$this->error = BVFSError::ERROR_INVALID_JOBID;
			return;
		}

		// Get job identifier
		$job = $this->getModule('job');
		$jobobj = $job->getJobById($jobid);
		if (!is_object($jobobj)) {
			$this->output = BVFSError::MSG_ERROR_INVALID_JOBID;
			$this->error = BVFSError::ERROR_INVALID_JOBID;
			return;
		}

		// Get elementary job identifiers
		$result = [];
		if ($inc_copy_job && $jobobj->type == 'C') {
			$result = $this->getJobIdsCopyJob($jobobj->jobid);
		} else {
			$result = $this->getJobIdsBackupJob($jobobj->jobid);
		}

		// Prepare output
		$res = [];
		if ($out_format === parent::OUTPUT_FORMAT_JSON) {
			$res = $this->getJSONOutput($result);
		} elseif ($out_format === parent::OUTPUT_FORMAT_RAW) {
			$res = $this->getRawOutput($result);
		}
		$this->output = $res['output'];
		$this->error = $res['error'];
	}

	/**
	 * Get elementary job identifiers for backup job identifier.
	 *
	 * @param int $jobid base job identifier
	 * @return array elementary job identifiers
	 */
	private function getJobIdsBackupJob(int $jobid): array
	{
		$cmd = ['.bvfs_get_jobids', 'jobid="' . $jobid . '"'];
		$bconsole = $this->getModule('bconsole');
		$jobids = $bconsole->bconsoleCommand(
			$this->director,
			$cmd
		);
		$ret = ['output' => [], 'error' => -1];
		if ($jobids->exitcode == 0) {
			$ret['output'] = $jobids->output;
			$ret['error'] = BVFSError::ERROR_NO_ERRORS;
		} else {
			$ret['output'] = BVFSError::MSG_ERROR_WRONG_EXITCODE . 'ExitCode=' . $jobids->exitcode;
			$ret['error'] = BVFSError::ERROR_WRONG_EXITCODE;
		}
		return $ret;
	}

	/**
	 * get elementary job identifiers for copy job identifier.
	 *
	 * @param int $jobid base job identifier
	 * @return array elementary job identifiers
	 */
	private function getJobIdsCopyJob(int $jobid): array
	{
		/**
		 * .bvfs_get_jobids does not support copy jobs and returns wrong results.
		 * Because of that to select compositional jobids, we use own algorithm for copy jobs.
		 */
		$job = $this->getModule('job');
		$jobids = $job->getJobidsToRestore($jobid);

		$ret = ['output' => [], 'error' => -1];
		if ($jobids) {
			$ret['output'] = [implode(',', $jobids)];
			$ret['error'] = BVFSError::ERROR_NO_ERRORS;
		} else {
			$ret['error'] = BVFSError::ERROR_INVALID_JOBID;
		}
		return $ret;
	}

	/**
	 * Get raw output from console.
	 * @param mixed $params
	 */
	protected function getRawOutput($params = [])
	{
		// Nothing to do
		return $params;
	}

	/**
	 * Get parsed JSON output from console.
	 *
	 * @param mixed $params
	 * @return array parsed command result with output
	 */
	protected function getJSONOutput($params = [])
	{
		$result = [];
		if ($params['error'] == 0 && is_array($params['output'])) {
			$jobids = [];
			foreach ($params['output'] as $jobid) {
				if (preg_match('/^([\d\,]+)$/', $jobid, $match) == 1) {
					$jobids = explode(',', $match[1]);
					$jobids = array_map('intval', $jobids);
					break;
				}
			}
			$result['output'] = $jobids;
		} else {
			$result['output'] = $params['output'];
		}
		$result['error'] = $params['error'];
		return $result;
	}
}
