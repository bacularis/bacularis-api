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
 * Jobs endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class Jobs extends BaculumAPIServer {
	public function get() {
		$misc = $this->getModule('misc');
		$limit = $this->Request->contains('limit') ? intval($this->Request['limit']) : 0;
		$jobstatus = $this->Request->contains('jobstatus') ? $this->Request['jobstatus'] : '';
		$level = $this->Request->contains('level') && $misc->isValidJobLevel($this->Request['level']) ? $this->Request['level'] : '';
		$type = $this->Request->contains('type') && $misc->isValidJobType($this->Request['type']) ? $this->Request['type'] : '';
		$jobname = $this->Request->contains('name') && $misc->isValidName($this->Request['name']) ? $this->Request['name'] : '';
		$clientid = $this->Request->contains('clientid') ? $this->Request['clientid'] : '';

		if (!empty($clientid) && !$misc->isValidId($clientid)) {
			$this->output = JobError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_CLIENT_DOES_NOT_EXISTS;
			return;
		}

		$client = $this->Request->contains('client') ? $this->Request['client'] : '';
		if (!empty($client) && !$misc->isValidName($client)) {
			$this->output = JobError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_CLIENT_DOES_NOT_EXISTS;
			return;
		}

		$params = [];
		$jobstatuses = array_keys($misc->getJobState());
		$sts = str_split($jobstatus);
		for ($i = 0; $i < count($sts); $i++) {
			if (in_array($sts[$i], $jobstatuses)) {
				if (!key_exists('Job.JobStatus', $params)) {
					$params['Job.JobStatus'] = array('operator' => 'OR', 'vals' => array());
				}
				$params['Job.JobStatus']['vals'][] = $sts[$i];
			}
		}
		if (!empty($level)) {
			$params['Job.Level']['operator'] = '';
			$params['Job.Level']['vals'] = $level;
		}
		if (!empty($type)) {
			$params['Job.Type']['operator'] = '';
			$params['Job.Type']['vals'] = $type;
		}
		$allowed = [];
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);
		if ($result->exitcode === 0) {
			$vals = [];
			if (!empty($jobname) && in_array($jobname, $result->output)) {
				$vals = [$jobname];
			} else {
				$vals = $result->output;
			}
			if (count($vals) == 0) {
				// no $vals criteria means that user has no job resources assigned.
				$this->output = [];
				$this->error = JobError::ERROR_NO_ERRORS;
				return;
			}

			$params['Job.Name']['operator'] = 'OR';
			$params['Job.Name']['vals'] = $vals;

			$error = false;
			// Client name and clientid filter
			if (!empty($client) || !empty($clientid)) {
				$result = $this->getModule('bconsole')->bconsoleCommand($this->director, array('.client'));
				if ($result->exitcode === 0) {
					array_shift($result->output);
					$cli = null;
					if (!empty($client)) {
						$cli = $this->getModule('client')->getClientByName($client);
					} elseif (!empty($clientid)) {
						$cli = $this->getModule('client')->getClientById($clientid);
					}
					if (is_object($cli) && in_array($cli->name, $result->output)) {
						$params['Job.ClientId']['operator'] = 'AND';
						$params['Job.ClientId']['vals'] = [$cli->clientid];
					} else {
						$error = true;
						$this->output = JobError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
						$this->error = JobError::ERROR_CLIENT_DOES_NOT_EXISTS;
					}
				} else {
					$error = true;
					$this->output = $result->output;
					$this->error = $result->exitcode;
				}
			}

			if ($error === false) {
				$jobs = $this->getModule('job')->getJobs($params, $limit);
				$this->output = $jobs;
				$this->error = JobError::ERROR_NO_ERRORS;
			}
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}
}
?>
