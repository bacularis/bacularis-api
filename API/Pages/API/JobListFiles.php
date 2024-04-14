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

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\Common\Modules\Errors\GenericError;
use Bacularis\Common\Modules\Errors\JobError;
use Bacularis\Common\Modules\Miscellaneous;
use Bacularis\API\Modules\APIServer;
use Bacularis\API\Modules\JobManager;

/**
 * List files from 'list files jobid=xx' bconsole command.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class JobListFiles extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$jobid = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;
		$type = $this->Request->contains('type') && $misc->isValidListFilesType($this->Request['type']) ? $this->Request['type'] : null;
		$offset = $this->Request->contains('offset') && $misc->isValidInteger($this->Request['offset']) ? (int) ($this->Request['offset']) : 0;
		$limit = $this->Request->contains('limit') && $misc->isValidInteger($this->Request['limit']) ? (int) ($this->Request['limit']) : 0;
		$order_by = $this->Request->contains('order_by') && $misc->isValidName($this->Request['order_by']) ? $this->Request['order_by'] : '';
		$order_type = $this->Request->contains('order_type') && $misc->isValidOrderType($this->Request['order_type']) ? $this->Request['order_type'] : Miscellaneous::ORDER_ASC;
		$search = $this->Request->contains('search') && $misc->isValidPath($this->Request['search']) ? $this->Request['search'] : null;
		$details = $this->Request->contains('details') && $misc->isValidBooleanTrue($this->Request['details']) ? $this->Request['details'] : false;

		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);
		$order = [];
		if ($result->exitcode === 0) {
			if (!empty($order_by)) {
				$order_by_lc = strtolower($order_by);
				if (!in_array($order_by_lc, JobManager::ORDER_BY_FILE_LIST_PROPS)) {
					$this->error = JobError::ERROR_INVALID_COMMAND;
					$this->output = JobError::MSG_ERROR_INVALID_COMMAND . ' Column: ' . $order_by;
					return;
				}
				$order = [$order_by, $order_type];
			}
			$params = [
				'Job.Name' => [
					'operator' => 'IN',
					'vals' => $result->output
				]
			];
			$job = $this->getModule('job')->getJobById($jobid, $params);
			if (is_object($job) && in_array($job->name, $result->output)) {
				if ($details) {
					$result = $this->getDetailedOutput([
						'jobid' => $jobid,
						'type' => $type,
						'offset' => $offset,
						'limit' => $limit,
						'order' => $order,
						'search' => $search
					]);
				} else {
					$result = $this->getSimpleOutput([
						'jobid' => $jobid,
						'type' => $type,
						'offset' => $offset,
						'limit' => $limit,
						'order' => $order,
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
	 * @param mixed $params
	 * @return array file list
	 */
	protected function getSimpleOutput($params = [])
	{
		$result = $this->getModule('job')->getJobFiles(
			$params['jobid'],
			$params['type'],
			$params['offset'],
			$params['limit'],
			$params['order'],
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
	 * @param mixed $params
	 * @return array file list
	 */
	protected function getDetailedOutput($params = [])
	{
		$result = $this->getModule('job')->getJobFiles(
			$params['jobid'],
			$params['type'],
			$params['offset'],
			$params['limit'],
			$params['order'],
			$params['search']
		);
		return $result;
	}
}
