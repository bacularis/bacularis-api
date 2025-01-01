<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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

use Bacularis\Common\Modules\Errors\PoolError;
use Bacularis\API\Modules\ConsoleOutputPage;
use Bacularis\API\Modules\ConsoleOutputShowPage;

/**
 * Show pool command endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class PoolShow extends ConsoleOutputShowPage
{
	public function get()
	{
		$poolid = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;
		$out_format = $this->Request->contains('output') && $this->isOutputFormatValid($this->Request['output']) ? $this->Request['output'] : ConsoleOutputPage::OUTPUT_FORMAT_RAW;
		$pool = $this->getModule('pool')->getPoolById($poolid);
		if (is_object($pool)) {
			$out = (object) [
				'output' => [],
				'exitcode' => 0
			];
			if ($out_format === ConsoleOutputPage::OUTPUT_FORMAT_RAW) {
				$out = $this->getRawOutput(['pool' => $pool->name]);
			} elseif ($out_format === ConsoleOutputPage::OUTPUT_FORMAT_JSON) {
				$out = $this->getJSONOutput(['pool' => $pool->name]);
			}
			$this->output = $out->output;
			$this->error = $out->exitcode;
		} else {
			$this->output = PoolError::MSG_ERROR_POOL_DOES_NOT_EXISTS;
			$this->error = PoolError::ERROR_POOL_DOES_NOT_EXISTS;
		}
	}

	/**
	 * Get show pool output from console in raw format.
	 *
	 * @param array $params command parameters
	 * @return StdClass object with output and exitcode
	 */
	protected function getRawOutput($params = [])
	{
		return $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['show', 'pool="' . $params['pool'] . '"']
		);
	}

	/**
	 * Get show pool output in JSON format.
	 *
	 * @param array $params command  parameters
	 * @return StdClass object with output and exitcode
	 */
	protected function getJSONOutput($params = [])
	{
		$result = (object) [
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
