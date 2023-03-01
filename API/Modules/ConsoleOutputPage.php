<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2023 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
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

namespace Bacularis\API\Modules;

/**
 * Get console output page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
abstract class ConsoleOutputPage extends BaculumAPIServer
{
	/**
	 * Available output formats.
	 */
	public const OUTPUT_FORMAT_RAW = 'raw';
	public const OUTPUT_FORMAT_JSON = 'json';

	/**
	 * Get raw output from console.
	 * @param mixed $params
	 */
	abstract protected function getRawOutput($params = []);

	/**
	 * Get parsed JSON output from console.
	 * @param mixed $params
	 */
	abstract protected function getJSONOutput($params = []);

	/**
	 * Validate output format.
	 *
	 * @param string $format output format
	 * @return bool true if output format is valid, otherwise false
	 */
	protected function isOutputFormatValid($format)
	{
		return ($format === self::OUTPUT_FORMAT_RAW || $format === self::OUTPUT_FORMAT_JSON);
	}
}
