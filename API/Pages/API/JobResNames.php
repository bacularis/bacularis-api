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

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\Common\Modules\Errors\BconsoleError;

/**
 * Job resource names endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class JobResNames extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$offset = $this->Request->contains('offset') ? (int) ($this->Request['offset']) : 0;
		$limit = $this->Request->contains('limit') ? (int) ($this->Request['limit']) : 0;
		$search = $this->Request->contains('search') ? $this->Request['search'] : null;

		if (is_string($search) && !$misc->isValidName($this->Request['search'])) {
			$this->output = BconsoleError::MSG_ERROR_INVALID_COMMAND;
			$this->error = BconsoleError::ERROR_INVALID_COMMAND;
			return;
		}

		$jobs_cmd = ['.jobs'];
		$types = $this->getModule('misc')->job_types;
		if ($this->Request->contains('type') && key_exists($this->Request['type'], $types)) {
			array_push($jobs_cmd, 'type="' . $this->Request['type'] . '"');
		}

		$bconsole = $this->getModule('bconsole');
		$directors = $bconsole->getDirectors();
		if ($directors->exitcode != 0) {
			$this->output = $directors->output;
			$this->error = $directors->exitcode;
			return;
		}
		$jobs = [];
		$error = false;
		$error_obj = null;
		for ($i = 0; $i < count($directors->output); $i++) {
			$job_list = $bconsole->bconsoleCommand(
				$directors->output[$i],
				$jobs_cmd,
				null,
				true
			);
			if ($job_list->exitcode != 0) {
				$error_obj = $job_list;
				$error = true;
				break;
			}
			$jobs[$directors->output[$i]] = $job_list->output;
		}

		if ($jobs && isset($search)) {
			foreach ($jobs as &$items) {
				$misc::filterList($items, "*{$search}*");
			}
		}

		foreach ($jobs as &$job_list) {
			if ($offset > 0 || $limit > 0) {
				if ($limit == 0) {
					$limit = null;
				}
				$job_list = array_slice($job_list, $offset, $limit);
			}
		}

		if ($error === true) {
			$this->output = $error_obj->output;
			$this->error = $error_obj->exitcode;
		} else {
			$this->output = $jobs;
			$this->error = BconsoleError::ERROR_NO_ERRORS;
		}
	}
}
