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
 * Job log endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class JobLog extends BaculumAPIServer {
	public function get() {
		$jobid = $this->Request->contains('id') ? intval($this->Request['id']) : 0;
		$show_time = false;
		if ($this->Request->contains('show_time') && $this->getModule('misc')->isValidBoolean($this->Request['show_time'])) {
			$show_time = (bool)$this->Request['show_time'];
		}
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);
		if ($result->exitcode === 0) {
			$job = $this->getModule('job')->getJobById($jobid);
			if (is_object($job) && in_array($job->name, $result->output)) {
				$log = $this->getModule('joblog')->getLogByJobId($job->jobid, $show_time);
				$log = array_map('trim', $log);
				// Output may contain national characters.
				$this->output = array_map('utf8_encode', $log);
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
}
?>
