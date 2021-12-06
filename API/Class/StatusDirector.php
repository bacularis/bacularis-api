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

Prado::using('Application.API.Class.Bconsole');
Prado::using('Application.API.Class.ComponentStatusModule');

/**
 * Module used to parse and prepare director status output.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Status
 * @package Baculum API
 */
class StatusDirector extends ComponentStatusModule {

	/**
	 * Output types (output sections).
	 */
	const OUTPUT_TYPE_HEADER = 'header';
	const OUTPUT_TYPE_SCHEDULED = 'scheduled';
	const OUTPUT_TYPE_RUNNING = 'running';
	const OUTPUT_TYPE_TERMINATED = 'terminated';


	/**
	 * Get parsed director status.
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
			array('status', 'director'),
			Bconsole::PTYPE_API_CMD
		);
		if ($result->exitcode === 0) {
			$ret['output'] = $this->parseStatus($result->output, $type);
			if (is_string($type) && key_exists($type, $ret['output'])) {
				if ($type === self::OUTPUT_TYPE_HEADER) {
					$ret['output'] = array_pop($ret['output'][$type]);
				} else {
					$ret['output'] = $ret['output'][$type];
				}
			}
		} else {
			$ret['output'] = $result->output;
		}
		$ret['error'] = $result->exitcode;
		return $ret;
	}

	/**
	 * Parse .api 2 director status output from bconsole.
	 *
	 * @param array $output bconsole status director output
	 * @param string $type output type (e.g. header, running, terminated ...etc.)
	 * @return array array with parsed director status values
	 */
	public function parseStatus(array $output, $type) {
		$result = [
			self::OUTPUT_TYPE_HEADER => [],
			self::OUTPUT_TYPE_SCHEDULED => [],
			self::OUTPUT_TYPE_RUNNING => [],
			self::OUTPUT_TYPE_TERMINATED => []
		];
		$type = null;
		$line = null;
		$types = array(
			self::OUTPUT_TYPE_HEADER . ':',
			self::OUTPUT_TYPE_RUNNING . ':',
			self::OUTPUT_TYPE_TERMINATED . ':'
		);
		$opts = array();
		for($i = 0; $i < count($output); $i++) {
			if (in_array($output[$i], $types)) { // check if type
				$type = rtrim($output[$i], ':');
			} elseif ($type === self::OUTPUT_TYPE_HEADER && count($opts) == 0 && $output[$i] === '') {
				/**
				 * special treating 'scheduled' type because this type
				 * is missing in the api status dir output.
				 */
				$type = self::OUTPUT_TYPE_SCHEDULED;
			} elseif (!empty($type)) {
				$line = $this->parseLine($output[$i]);
				if (is_array($line)) { // check if line
					if (!key_exists($type, $result)) {
						$result[$type] = array();
					}
					$opts[$line['key']] = $line['value'];
				} elseif (count($opts) > 0) { // dump all parameters
					$result[$type][] = $opts;
					$opts = array();
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
	 * @return boolean true if output type is valid for component, otherwise false
	 */
	public function isValidOutputType($type) {
		return in_array(
			$type,
			array(
				self::OUTPUT_TYPE_HEADER,
				self::OUTPUT_TYPE_SCHEDULED,
				self::OUTPUT_TYPE_RUNNING,
				self::OUTPUT_TYPE_TERMINATED
			)
		);
	}
}
?>
