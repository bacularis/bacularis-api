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

/**
 * Job log endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class JobLog extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');

		// Job identifier
		$jobid = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;

		// Decide if display time in job log
		$show_time = false;
		if ($this->Request->contains('show_time') && $misc->isValidBoolean($this->Request['show_time'])) {
			$show_time = (bool) $this->Request['show_time'];
		}

		// Set log order
		$order_by = ($this->Request->contains('order_by') && $misc->isValidName($this->Request['order_by'])) ? $this->Request['order_by'] : 'LogId';
		$order_type = ($this->Request->contains('order_type') && $misc->isValidOrderType($this->Request['order_type'])) ? $this->Request['order_type'] : 'asc';

		// Set log offset and limit
		$limit = ($this->Request->contains('limit') && $misc->isValidInteger($this->Request['limit'])) ? (int) $this->Request['limit'] : 0;
		$offset = ($this->Request->contains('offset') && $misc->isValidInteger($this->Request['offset'])) ? (int) $this->Request['offset'] : 0;

		$bconsole = $this->getModule('bconsole');
		$result = $bconsole->bconsoleCommand(
			$this->director,
			['.jobs'],
			null,
			true
		);
		if ($result->exitcode === 0) {
			if ($offset > 0 && $limit <= 0) {
				$emsg = ' Offset requires providing limit parameter as well.';
				$this->error = GenericError::ERROR_INVALID_COMMAND;
				$this->output = GenericError::MSG_ERROR_INVALID_COMMAND . $emsg;
				return;
			}

			$params = [
				'Job.Name' => [[
					'operator' => 'IN',
					'vals' => $result->output
				]]
			];
			$job_mod = $this->getModule('job');
			$job = $job_mod->getJobById($jobid, $params);
			if (is_object($job) && in_array($job->name, $result->output)) {
				if (!empty($order_by)) {
					$lr = new ReflectionClass('Bacularis\API\Modules\LogRecord');
					$order_by_lc = strtolower($order_by);
					if (!$lr->hasProperty($order_by_lc)) {
						$this->error = GenericError::ERROR_INVALID_COMMAND;
						$this->output = GenericError::MSG_ERROR_INVALID_COMMAND . ' Column: ' . $order_by;
						return;
					}
				}
				$log_params = [
					'show_time' => $show_time,
					'order_by' => $order_by,
					'order_type' => $order_type,
					'offset' => $offset,
					'limit' => $limit
				];
				$criteria = [
					'Log.JobId' => [[
						'vals' => $job->jobid
					]]
				];
				$joblog_mod = $this->getModule('joblog');
				$log = $joblog_mod->getLogs($criteria, $log_params);
				$cb = function ($log) {
					return trim(utf8_encode($log));
				};
				// Output may contain national characters.
				$this->output = array_map($cb, $log);
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
