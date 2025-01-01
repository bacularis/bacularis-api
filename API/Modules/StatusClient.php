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

namespace Bacularis\API\Modules;

use Bacularis\Common\Modules\Errors\GenericError;

/**
 * Module used to get and parse client status output.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Status
 */
class StatusClient extends ComponentStatusModule
{
	/**
	 * Output types (output sections).
	 */
	public const OUTPUT_TYPE_HEADER = 'header';
	public const OUTPUT_TYPE_RUNNING = 'running';
	public const OUTPUT_TYPE_TERMINATED = 'terminated';

	/**
	 * Get parsed client status.
	 *
	 * @param string $director director name
	 * @param string $component_name component name
	 * @param string $type output type (e.g. header, running, terminated ...etc.)
	 * @return array ready array parsed component status output
	 */
	public function getStatus($director, $component_name = null, $type = null)
	{
		$ret = ['output' => [], 'error' => 0];
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$director,
			['.status', 'client="' . $component_name . '"', $type],
			Bconsole::PTYPE_API_CMD
		);
		if ($result->exitcode === 0) {
			$ret['output'] = $this->parseStatus($result->output, $type);
			$ret['error'] = $result->exitcode;
		} else {
			$ret['output'] = $result->output;
			$ret['error'] = GenericError::ERROR_WRONG_EXITCODE;
		}
		return $ret;
	}

	/**
	 * Parse .api 2 client status output from bconsole.
	 *
	 * @param array $output bconsole status client output
	 * @param string $type output type (e.g. header, running, terminated ...etc.)
	 * @return array array with parsed client status values
	 */
	public function parseStatus(array $output, $type)
	{
		$result = [];
		$line = null;
		$opts = [];
		for ($i = 0; $i < count($output); $i++) {
			if (empty($output[$i])) {
				if (count($opts) > 10) {
					$result[] = $opts;
				}
				if (count($opts) > 0) {
					$opts = [];
				}
			} else {
				if (preg_match('/^(error|errmsg)=/', $output[$i]) === 1) {
					// skip key/value items that are not client status
					continue;
				}
				$line = $this->parseLine($output[$i]);
				if (is_array($line)) {
					$opts[$line['key']] = $line['value'];
				}
			}
		}
		if ($type === self::OUTPUT_TYPE_HEADER) {
			$result = array_pop($result);
		}
		return $result;
	}

	/**
	 * Validate status output type.
	 *
	 * @param string $type output type (e.g. header, running, terminated ...etc.)
	 * @return bool true if output type is valid for component, otherwise false
	 */
	public function isValidOutputType($type)
	{
		return in_array(
			$type,
			[
				self::OUTPUT_TYPE_HEADER,
				self::OUTPUT_TYPE_RUNNING,
				self::OUTPUT_TYPE_TERMINATED
			]
		);
	}
}
