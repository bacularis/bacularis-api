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
 
Prado::using('Application.API.Class.ConsoleOutputPage');

/**
 * BVFS versions.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class BVFSVersions extends ConsoleOutputPage {

	public function get() {
		$jobid = $this->Request->contains('jobid') ? intval($this->Request['jobid']) : 0;
		$pathid = $this->Request->contains('pathid') ? intval($this->Request['pathid']) : 0;
		$filenameid = $this->Request->contains('filenameid') ? intval($this->Request['filenameid']) : 0;
		$copies = $this->Request->contains('copies') ? intval($this->Request['copies']) : 0;
		$out_format = $this->Request->contains('output') && $this->isOutputFormatValid($this->Request['output']) ? $this->Request['output'] : parent::OUTPUT_FORMAT_RAW;
		$client = null;
		if ($this->Request->contains('client') && $this->getModule('misc')->isValidName($this->Request['client'])) {
			$client = $this->Request['client'];
		} elseif ($this->Request->contains('clientid')) {
			$clientid = intval($this->Request['clientid']);
			$client_row = $this->getModule('client')->getClientById($clientid);
			if (is_object($client_row)) {
				$client = $client_row->name;
			}
		}

		if (is_null($client)) {
			$this->output = BVFSError::MSG_ERROR_INVALID_CLIENT;
			$this->error = BVFSError::ERROR_INVALID_CLIENT;
			return;
		}
		$params = [
			'client' => $client,
			'jobid' => $jobid,
			'pathid' => $pathid,
			'filenameid' => $filenameid,
			'copies' => $copies
		];
		$out = (object)['output' => [], 'exitcode' => 0];
		if ($out_format === parent::OUTPUT_FORMAT_RAW) {
			$out = $this->getRawOutput($params);
		} elseif($out_format === parent::OUTPUT_FORMAT_JSON) {
			$out = $this->getJSONOutput($params);
		}

		$this->output = $out->output;
		$this->error = $out->exitcode;
	}

	/**
	 * Get BVFS versions output from console in raw format.
	 *
	 * @param array $params command  parameters
	 * @return StdClass object with output and exitcode
	 */
	protected function getRawOutput($params = []) {
		$cmd = array(
			'.bvfs_versions',
			'client="' . $params['client'] . '"',
			'jobid="' . $params['jobid'] . '"',
			'pathid="' . $params['pathid'] . '"',
			'fnid="' . $params['filenameid'] . '"'
		);
		if ($params['copies'] == 1) {
			$cmd[] = 'copies';
		}
		return $this->getModule('bconsole')->bconsoleCommand($this->director, $cmd);
	}

	/**
	 * Get BVFS versions output in JSON format.
	 *
	 * @param array $params command  parameters
	 * @return StdClass object with output and exitcode
	 */
	protected function getJSONOutput($params = []) {
		$result = (object)['output' => [], 'exitcode' => 0];
		$raw = $this->getRawOutput($params);
		if ($raw->exitcode === 0) {
			$result->output = $this->getModule('bvfs')->parseFileVersions($raw->output);
		} else {
			$result = $raw;
		}
		return $result;
	}
}
?>
