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
 * List files from 'list files jobid=xx' bconsole command.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class JobListFiles extends BaculumAPIServer {

	public function get() {
		$misc = $this->getModule('misc');
		$jobid = $this->Request->contains('id') ? intval($this->Request['id']) : 0;
		$type = $this->Request->contains('type') && $misc->isValidListFilesType($this->Request['type']) ? $this->Request['type'] : null;
		$offset = $this->Request->contains('offset') ? intval($this->Request['offset']) : 0;
		$limit = $this->Request->contains('limit') ? intval($this->Request['limit']) : 0;
		$search = $this->Request->contains('search') && $misc->isValidPath($this->Request['search']) ? $this->Request['search'] : null;
		$details = $this->Request->contains('details') && $misc->isValidBooleanTrue($this->Request['details']) ? $this->Request['details'] : false;

		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);
		if ($result->exitcode === 0) {
			$job = $this->getModule('job')->getJobById($jobid);
			if (is_object($job) && in_array($job->name, $result->output)) {
				if ($details) {
					$result = $this->getDetailedOutput([
						'jobid' => $jobid,
						'type' => $type,
						'offset' => $offset,
						'limit' => $limit,
						'search' => $search
					]);
				} else {
					$result = $this->getSimpleOutput([
						'jobid' => $jobid,
						'type' => $type,
						'offset' => $offset,
						'limit' => $limit,
						'search' => $search
					]);
					if (APIServer::getVersion() === 1) {
						// TODO: Remove it when APIv1 will not be used
						$result = [
							'items' => $result,
							'totals' => count($result)
						];
					}
				}
				$this->output = $result;
				$this->error = GenericError::ERROR_NO_ERRORS;
			} else {
				$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
				$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
			}
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}

	/**
	 * Get simple output with file list and total number of items.
	 *
	 * @params array $params job parameters to get file list
	 * @return array file list
	 */
	protected function getSimpleOutput($params = []) {
		$result = $this->getModule('job')->getJobFiles(
			$params['jobid'],
			$params['type'],
			$params['offset'],
			$params['limit'],
			$params['search'],
			true
		);
		return $result;
	}

	/**
	 * Get detailed output with file list.
	 * It also includes LStat value.
	 *
	 * @params array $params job parameters to get file list
	 * @return array file list
	 */
	protected function getDetailedOutput($params = []) {
		$result = $this->getModule('job')->getJobFiles(
			$params['jobid'],
			$params['type'],
			$params['offset'],
			$params['limit'],
			$params['search']
		);
		return $result;
	}
}
?>
