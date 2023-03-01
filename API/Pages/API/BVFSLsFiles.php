<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2023 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2021 Kern Sibbald
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

use Bacularis\Common\Modules\Errors\BVFSError;
use Bacularis\API\Modules\ConsoleOutputPage;

/**
 * BVFS list files.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class BVFSLsFiles extends ConsoleOutputPage
{
	private $jobids;
	private $path;

	public function get()
	{
		$misc = $this->getModule('misc');
		$limit = $this->Request->contains('limit') ? (int) ($this->Request['limit']) : 0;
		$offset = $this->Request->contains('offset') ? (int) ($this->Request['offset']) : 0;
		$jobids = $this->Request->contains('jobids') && $misc->isValidIdsList($this->Request['jobids']) ? $this->Request['jobids'] : null;
		$path = $this->Request->contains('path') && $misc->isValidPath($this->Request['path']) ? $this->Request['path'] : '';
		$pathid = $this->Request->contains('pathid') ? (int) ($this->Request['pathid']) : null;
		$out_format = $this->Request->contains('output') && $this->isOutputFormatValid($this->Request['output']) ? $this->Request['output'] : parent::OUTPUT_FORMAT_RAW;

		if (is_null($jobids)) {
			$this->output = BVFSError::MSG_ERROR_INVALID_JOBID_LIST;
			$this->error = BVFSError::ERROR_INVALID_JOBID_LIST;
			return;
		}

		if (is_null($path)) {
			$this->output = BVFSError::ERROR_INVALID_RESTORE_PATH;
			$this->error = BVFSError::MSG_ERROR_INVALID_RESTORE_PATH;
			return;
		}

		$params = [
			'jobids' => $jobids,
			'path' => $path,
			'pathid' => $pathid,
			'offset' => $offset,
			'limit' => $limit
		];
		$out = (object) ['output' => [], 'exitcode' => 0];
		if ($out_format === parent::OUTPUT_FORMAT_RAW) {
			$out = $this->getRawOutput($params);
		} elseif ($out_format === parent::OUTPUT_FORMAT_JSON) {
			$out = $this->getJSONOutput($params);
		}

		$this->output = $out->output;
		$this->error = $out->exitcode;
	}

	/**
	 * Get BVFS list files output from console in raw format.
	 *
	 * @param array $params command  parameters
	 * @return StdClass object with output and exitcode
	 */
	protected function getRawOutput($params = [])
	{
		$cmd = [
			'.bvfs_lsfiles',
			'jobid="' . $params['jobids'] . '"'
		];

		if ($params['pathid']) {
			array_push($cmd, 'pathid="' . $params['pathid'] . '"');
		} else {
			array_push($cmd, 'path="' . $params['path'] . '"');
		}

		if ($params['offset'] > 0) {
			array_push($cmd, 'offset="' . $params['offset'] . '"');
		}
		if ($params['limit'] > 0) {
			array_push($cmd, 'limit="' . $params['limit'] . '"');
		}
		return $this->getModule('bconsole')->bconsoleCommand($this->director, $cmd);
	}

	/**
	 * Get BVFS list files output in JSON format.
	 *
	 * @param array $params command  parameters
	 * @return StdClass object with output and exitcode
	 */
	protected function getJSONOutput($params = [])
	{
		$result = (object) ['output' => [], 'exitcode' => 0];
		$raw = $this->getRawOutput($params);
		if ($raw->exitcode === 0) {
			$result->output = $this->getModule('bvfs')->parseFileDirList($raw->output);
		} else {
			$result = $raw;
		}
		return $result;
	}
}
