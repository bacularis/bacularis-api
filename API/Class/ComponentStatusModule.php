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

Prado::using('Application.API.Class.APIModule');

/**
 * Base abstract class to inherit commonly used method
 * in work with component status.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum API
 */
abstract class ComponentStatusModule extends APIModule {

	/**
	 * Get parsed status.
	 *
	 * @param string $director director name
	 * @param string $component_name component name
	 * @param string $type output type (e.g. header, running, terminated ...etc.)
	 * @return array ready array parsed component status output
	 */
	abstract public function getStatus($director, $component_name = null, $type = null);


	/**
	 * Parse component status.
	 *
	 * @param array $output component status output from bconsole
	 * @param string $type output type (e.g. header, running, terminated ...etc.)
	 * @return array parsed component status output
	 */
	abstract public function parseStatus(array $output, $type);

	/**
	 * Parse single component status line to find key=value pairs.
	 *
	 * @param string $line single line from component status
	 * @return mixed array with key and value on success, otherwise null
	 */
	protected function parseLine($line) {
		$ret = null;
		if (preg_match('/^(?P<key>\w+)=(?P<value>[\S\s]*)$/', $line, $match) === 1) {
			$ret = $match;
		}
		return $ret;
	}

	/**
	 * Validate status output type.
	 *
	 * @param string $type output type (e.g. header, running, terminated ...etc.)
	 * @return boolean true if output type is valid for component, otherwise false
	 */
	abstract public function isValidOutputType($type);
}
?>
