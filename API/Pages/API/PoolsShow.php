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

Prado::using('Application.API.Class.ConsoleOutputPage');
Prado::using('Application.API.Class.ConsoleOutputShowPage');

/**
 * Show pools command endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class PoolsShow extends ConsoleOutputShowPage {

	public function get() {
		$out_format = $this->Request->contains('output') && $this->isOutputFormatValid($this->Request['output']) ? $this->Request['output'] : ConsoleOutputPage::OUTPUT_FORMAT_RAW;
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.pool'],
			null,
			true
		);

		$pool = null;
		if ($result->exitcode === 0) {
			if ($this->Request->contains('name')) {
				if (in_array($this->Request['name'], $result->output)) {
					$pool = $this->Request['name'];
				} else {
					$this->output = PoolError::MSG_ERROR_POOL_DOES_NOT_EXISTS;
					$this->error = PoolError::ERROR_POOL_DOES_NOT_EXISTS;
					return;
				}
			}
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
			return;
		}
		$params = [];
		if (is_string($pool)) {
			$params = ['pool' => $pool];
		}
		$out = (object)[
			'output' => [],
			'exitcode' => 0
		];
		if ($out_format === ConsoleOutputPage::OUTPUT_FORMAT_RAW) {
			$out = $this->getRawOutput($params);
		} elseif($out_format === ConsoleOutputPage::OUTPUT_FORMAT_JSON) {
			$out = $this->getJSONOutput($params);
		}
		$this->output = $out->output;
		$this->error = $out->exitcode;
	}

	/**
	 * Get show pools output from console in raw format.
	 *
	 * @param array $params command  parameters
	 * @return StdClass object with output and exitcode
	 */
	protected function getRawOutput($params = []) {
		$cmd = ['show'];
		if (key_exists('pool', $params)) {
			$cmd[] = 'pool="' . $params['pool'] . '"';
		} else {
			$cmd[] = 'pools';
		}
		return $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			$cmd
		);
	}

	/**
	 * Get show pools output in JSON format.
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
			if (key_exists('pool', $params)) {
				$result->output = $this->parseOutput($output->output);
			} else {
				$result->output = $this->parseOutputAll($output->output);
			}
		}
		$result->exitcode = $output->exitcode;
		return $result;
	}
}
?>
