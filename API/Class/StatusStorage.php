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

Prado::using('Application.API.Class.ComponentStatusModule');

/**
 * Module used to get and parse storage status output.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Status
 * @package Baculum API
 */
class StatusStorage extends ComponentStatusModule {

	/**
	 * Output types (output sections).
	 */
	const OUTPUT_TYPE_HEADER = 'header';
	const OUTPUT_TYPE_RUNNING = 'running';
	const OUTPUT_TYPE_TERMINATED = 'terminated';
	const OUTPUT_TYPE_DEVICES = 'devices';

	/**
	 * Get parsed storage status.
	 *
	 * @param string $director director name
	 * @param string $component_name component name
	 * @param string $type output type (e.g. header, running, terminated ...etc.)
	 * @return array ready array parsed component status output
	 */
	public function getStatus($director, $component_name = null, $type = null) {
		$ret = array('output' => array(), 'error' => 0);
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$director,
			array('.status', 'storage="' . $component_name . '"', $type),
			Bconsole::PTYPE_API_CMD
		);
		if ($result->exitcode === 0) {
			$ret['output'] = $this->parseStatus($result->output, $type);
		} else {
			$ret['output'] = $result->output;
		}
		$ret['error'] = $result->exitcode;
		return $ret;
	}

	/**
	 * Parse .api 2 storage status output from bconsole.
	 *
	 * @param array $output bconsole status storage output
	 * @param string $type output type (e.g. header, running, terminated ...etc.)
	 * @return array array with parsed storage status values
	 */
	public function parseStatus(array $output, $type) {
		$result = [];
		$line = null;
		$part = [];
		$autochanger = null;
		$autochangers = [];
		$ach_dev = [];
		$empty_lines = 0;
		for($i = 0; $i < count($output); $i++) {
			if (empty($output[$i])) {
				$empty_lines++;
				if  (count($part) > 10) {
					$result[] = $part;
					$part = [];
				}
				if (count($ach_dev) == 2) {
					$autochangers[$autochanger]['devices'][]  = $ach_dev;
					$ach_dev = [];
				}
				if ($empty_lines == 4 && $autochanger) {
					$autochanger = null;
				}
			} else {
				$empty_lines = 0;
				$line = $this->parseLine($output[$i]);
				if (!is_array($line)) {
					continue;
				}

				if ($line['key'] == 'autochanger') {
					$autochanger  = $line['value'];
					$autochangers[$autochanger] = ['devices' => []];
				} elseif ($autochanger) {
					$ach_dev[$line['key']] = $line['value'];
				} else {
					$part[$line['key']] = $line['value'];
				}
			}
		}
		if ($type === self::OUTPUT_TYPE_HEADER) {
			$result = array_pop($result);
		} elseif ($type === self::OUTPUT_TYPE_DEVICES) {
			for ($i = 0; $i < count($result); $i++) {
				$found = false;
				foreach ($autochangers as $ach => $devs) {
					for ($j = 0; $j < count($devs['devices']); $j++) {
						if ($result[$i]['name'] === $devs['devices'][$j]['name']) {
							$result[$i]['autochanger'] = $ach;
							$found = true;
							break 2;
						}
					}
				}
				if (!$found) {
					$result[$i]['autochanger'] = null;
				}
			}
		}
		return $result;
	}

	/**
	 * Validate status output type.
	 *
	 * @param string $type output type (e.g. header, running, terminated ...etc.)
	 * @return boolean true if output type is valid for component, otherwise false
	 */
	public function isValidOutputType($type) {
		return in_array(
			$type,
			array(
				self::OUTPUT_TYPE_HEADER,
				self::OUTPUT_TYPE_RUNNING,
				self::OUTPUT_TYPE_TERMINATED,
				self::OUTPUT_TYPE_DEVICES
			)
		);
	}
}
?>
