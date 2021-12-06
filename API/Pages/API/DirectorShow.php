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
Prado::using('Application.API.Class.ConsoleOutputShowPage');

/**
 * Director show command endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class DirectorShow extends ConsoleOutputShowPage {

	public function get() {
		$director = $this->Request->contains('name') && $this->getModule('misc')->isValidName($this->Request['name']) ? $this->Request['name'] : null;
		$out_format = $this->Request->contains('output') && $this->isOutputFormatValid($this->Request['output']) ? $this->Request['output'] : ConsoleOutputPage::OUTPUT_FORMAT_RAW;
		$dirs = [];
		$result = $this->getModule('bconsole')->getDirectors();
		if ($result->exitcode === 0) {
			$dirs = $result->output;
		}

		if (is_string($director) && in_array($director, $dirs)) {
			$out = (object)[
				'output' => [],
				'exitcode' => 0
			];
			if ($out_format === ConsoleOutputPage::OUTPUT_FORMAT_RAW) {
				$out = $this->getRawOutput(['director' => $director]);
			} elseif($out_format === ConsoleOutputPage::OUTPUT_FORMAT_JSON) {
				$out = $this->getJSONOutput(['director' => $director]);
			}
			$this->output = $out->output;
			$this->error = $out->exitcode;
		} else {
			$this->output = BconsoleError::MSG_ERROR_INVALID_DIRECTOR;
			$this->error = BconsoleError::ERROR_INVALID_DIRECTOR;
		}
	}

	/**
	 * Get show director output from console in raw format.
	 *
	 * @param array $params command parameters
	 * @return StdClass object with output and exitcode
	 */
	protected function getRawOutput($params = []) {
		return $this->getModule('bconsole')->bconsoleCommand(
			$params['director'],
			['show', 'director']
		);
	}

	/**
	 * Get show director output in JSON format.
	 *
	 * @param array $params command parameters
	 * @return StdClass object with output and exitcode
	 */
	protected function getJSONOutput($params = []) {
		$result = (object)[
			'output' => [],
			'exitcode' => 0
		];
		$output = $this->getRawOutput($params);
		if ($output->exitcode === 0) {
			array_shift($output->output);
			$result->output = $this->parseOutput($output->output);
		}
		$result->exitcode = $output->exitcode;
		return $result;
	}
}
?>
