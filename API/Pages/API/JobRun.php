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
 * Job run endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class JobRun extends BaculumAPIServer {

	public function create($params) {
		$job = null;
		if (property_exists($params, 'id')) {
			$jobid = intval($params->id);
			$job_row = $this->getModule('job')->getJobById($jobid);
			$job = is_object($job_row) ? $job_row->name : null;
		} elseif (property_exists($params, 'name') && $this->getModule('misc')->isValidName($params->name)) {
			$job = $params->name;
		}
		$level = null;
		if (property_exists($params, 'level')) {
			$level = $params->level;
		}
		$fileset = null;
		if (property_exists($params, 'filesetid')) {
			$filesetid = intval($params->filesetid);
			$fileset_row = $this->getModule('fileset')->getFileSetById($filesetid);
			$fileset = is_object($fileset_row) ? $fileset_row->fileset : null;
		} elseif (property_exists($params, 'fileset') && $this->getModule('misc')->isValidName($params->fileset)) {
			$fileset = $params->fileset;
		}
		$client = null;
		if (property_exists($params, 'clientid')) {
			$clientid = intval($params->clientid);
			$client_row = $this->getModule('client')->getClientById($clientid);
			$client = is_object($client_row) ? $client_row->name : null;
		} elseif (property_exists($params, 'client') && $this->getModule('misc')->isValidName($params->client)) {
			$client = $params->client;
		}

		$accurate = 'no';
		if (property_exists($params, 'accurate')) {
			$accurate_job = intval($params->accurate);
			$accurate = $accurate_job === 1 ? 'yes' : 'no';
		}

		$storage = null;
		if (property_exists($params, 'storageid')) {
			$storageid = intval($params->storageid);
			$storage_row = $this->getModule('storage')->getStorageById($storageid);
			$storage = is_object($storage_row) ? $storage_row->name : null;
		} elseif (property_exists($params, 'storage') && $this->getModule('misc')->isValidName($params->storage)) {
			$storage = $params->storage;
		}
		$pool = null;
		if (property_exists($params, 'poolid')) {
			$poolid = intval($params->poolid);
			$pool_row = $this->getModule('pool')->getPoolById($poolid);
			$pool = is_object($pool_row) ? $pool_row->name : null;
		} elseif (property_exists($params, 'pool') && $this->getModule('misc')->isValidName($params->pool)) {
			$pool = $params->pool;
		}
		$priority = property_exists($params, 'priority') ? intval($params->priority) : 10; // default priority is set to 10

		$jobid = property_exists($params, 'jobid') ? 'jobid="' . intval($params->jobid) . '"' : '';
		$verifyjob = null;
		if (property_exists($params, 'verifyjob') && $this->getModule('misc')->isValidName($params->verifyjob)) {
			$verifyjob = 'verifyjob="' . $params->verifyjob . '"';
		}
		
		if(is_null($job)) {
			$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
			return;
		} else {
			$result = $this->getModule('bconsole')->bconsoleCommand(
				$this->director,
				['.jobs'],
				null,
				true
			);
			if ($result->exitcode === 0) {
				if (!in_array($job, $result->output)) {
					$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
					$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
					return;
				}
			} else {
				$this->output = $result->output;
				$this->error = $result->exitcode;
				return;
			}
		}

		$is_valid_level = $this->getModule('misc')->isValidJobLevel($level);
		if(!$is_valid_level) {
			$this->output = JobError::MSG_ERROR_INVALID_JOBLEVEL;
			$this->error = JobError::ERROR_INVALID_JOBLEVEL;
			return;
		}

		if(is_null($fileset)) {
			$this->output = JobError::MSG_ERROR_FILESET_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_FILESET_DOES_NOT_EXISTS;
			return;
		}

		if(is_null($client)) {
			$this->output = JobError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_CLIENT_DOES_NOT_EXISTS;
			return;
		}

		if(is_null($storage)) {
			$this->output = JobError::MSG_ERROR_STORAGE_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_STORAGE_DOES_NOT_EXISTS;
			return;
		}

		if(is_null($pool)) {
			$this->output = JobError::MSG_ERROR_POOL_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_POOL_DOES_NOT_EXISTS;
			return;
		}

		$joblevels  = $this->getModule('misc')->getJobLevels();
		$command = array(
			'run',
			'job="' . $job . '"',
			'level="' . $joblevels[$level] . '"',
			'fileset="' . $fileset . '"',
			'client="' . $client . '"',
			'storage="' . $storage . '"',
			'pool="' . $pool . '"' ,
			'priority="' . $priority . '"',
			'accurate="' . $accurate . '"',
			$jobid,
			$verifyjob,
			'yes'
		);
		$run = $this->getModule('bconsole')->bconsoleCommand($this->director, $command);
		$this->output = $run->output;
		$this->error = $run->exitcode;
	}
}

?>
