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

/**
 * Get console output page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
abstract class ConsoleOutputPage extends BaculumAPIServer {

	/**
	 * Available output formats.
	 */
	const OUTPUT_FORMAT_RAW = 'raw';
	const OUTPUT_FORMAT_JSON = 'json';

	/**
	 * Get raw output from console.
	 */
	abstract protected function getRawOutput($params = []);

	/**
	 * Get parsed JSON output from console.
	 */
	abstract protected function getJSONOutput($params = []);

	/**
	 * Validate output format.
	 *
	 * @param string $format output format
	 * @return boolean true if output format is valid, otherwise false
	 */
	protected function isOutputFormatValid($format) {
		return ($format === self::OUTPUT_FORMAT_RAW || $format === self::OUTPUT_FORMAT_JSON);
	}
}
?>
