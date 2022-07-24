<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2022 Marcin Haba
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

use Prado\Prado;

/**
 * API Server layer.
 * Introduces main method inherited by particular API server versions.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class APIServer extends APIModule
{
	/**
	 * Default API version if there was not possible to determine version.
	 */
	public const DEFAULT_VERSION = 2;

	public const VERSION_PATTERN = '!/api/v(?P<version>\d+)/!';

	/**
	 * Stores API server instance.
	 */
	protected $server;

	/**
	 * Set API server instance.
	 *
	 * @param BaculumAPIServer $obj server object
	 * @return none
	 */
	public function setServerObj($obj)
	{
		$this->server = $obj;
	}

	/**
	 * Get API server instance.
	 * @return null|BaculumAPIServer API server object
	 */
	public function getServerObj()
	{
		return $this->server;
	}

	/**
	 * Get requested API version from URL path.
	 *
	 * @return int requested API version number
	 */
	public static function getVersion()
	{
		$version = self::DEFAULT_VERSION;
		$path = Prado::getApplication()->Request->getPathInfo();
		if (preg_match(self::VERSION_PATTERN, $path, $match) === 1) {
			$version = (int) ($match['version']);
		}
		return $version;
	}
}
