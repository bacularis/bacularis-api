<?php
/*
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

Prado::using('Application.API.Class.ConsoleOutputPage');

/**
 * Director status command endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class DirectorStatus extends ConsoleOutputPage {

	public function get() {
		$status = $this->getModule('status_dir');
		$director = $this->Request->contains('name') && $this->getModule('misc')->isValidName($this->Request['name']) ? $this->Request['name'] : null;
		$type = $this->Request->contains('type') && $status->isValidOutputType($this->Request['type']) ? $this->Request['type'] : null;
		$out_format = $this->Request->contains('output') && $this->isOutputFormatValid($this->Request['output']) ? $this->Request['output'] : parent::OUTPUT_FORMAT_RAW;

		$dirs = [];
		$result = $this->getModule('bconsole')->getDirectors();
		if ($result->exitcode === 0) {
			$dirs = $result->output;
		}

		if (is_null($director) || !in_array($director, $dirs)) {
			// Invalid director
			$this->output = BconsoleError::MSG_ERROR_INVALID_DIRECTOR;
			$this->error = BconsoleError::ERROR_INVALID_DIRECTOR;
			return;
		}

		$out = (object)['output' => [], 'error' => 0];
		if ($out_format === parent::OUTPUT_FORMAT_RAW) {
			$out = $this->getRawOutput(['director' => $director]);
		} elseif ($out_format === parent::OUTPUT_FORMAT_JSON) {
			$out = $this->getJSONOutput([
				'director' => $director,
				'type' => $type
			]);
		}
		$this->output = $out['output'];
		$this->error = $out['error'];
	}

	protected function getRawOutput($params = []) {
		// traditional status director output
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$params['director'],
			[
				'status',
				'director'
			]
		);
		$error = $result->exitcode == 0 ? $result->exitcode : GenericError::ERROR_WRONG_EXITCODE;
		$ret = [
			'output' => $result->output,
			'error' => $error
		];
		return $ret;
	}

	protected function getJSONOutput($params = []) {
		// status director JSON output by API 2 interface
		$status = $this->getModule('status_dir');
		return $status->getStatus(
			$params['director'],
			null,
			$params['type']
		);
	}
}

?>
