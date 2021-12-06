<?php
/*
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

/**
 * Job bandwidth limit endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class JobBandwidthLimit extends BaculumAPIServer {

	public function set($id, $params) {
		$job = null;
		if ($id > 0) {
			$job = $this->getModule('job')->getJobById($id);
		}

		$jobid = null;
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);
		if ($result->exitcode === 0) {
			if(is_object($job) && in_array($job->name, $result->output)) {
				$jobid = $job->jobid;
			}
		}
		if (is_null($jobid)) {
			$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
			return;
		}

		$limit = property_exists($params, 'limit') && $this->getModule('misc')->isValidInteger($params->limit) ? $params->limit : 0;

		$cmd = array('setbandwidth', 'jobid="' . $jobid . '"', 'limit="' . $limit . '"');
		$result = $this->getModule('bconsole')->bconsoleCommand($this->director, $cmd);
		$this->output = $result->output;
		if ($result->exitcode === 0) {
			$this->error = GenericError::ERROR_NO_ERRORS;
		} else {
			$this->error = $result->exitcode;
		}
	}
}
?>
