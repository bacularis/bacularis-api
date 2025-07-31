<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

namespace Bacularis\API\Modules;

/**
 * Uname format tools.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class Uname extends APIModule
{

	/**
	 * General uname pattern.
	 */
	private const UNAME_PATTERN = '/^(?P<major>\d+)\.(?P<minor>\d+)\.(?P<release>\d+)\s+\((?P<date>[A-Za-z\d\s]+)\)\s+(?P<os>.+)$/i';

	/**
	 * Parse uname string.
	 *
	 * @param string $val uname string
	 * @return array parsed uname value
	 */
	public static function parse(string $uname): array
	{
		/**
		 * Example uname:
		 * 15.0.3 (25 March 2025) x86_64-pc-linux-gnu redhat
		 * or
		 * 13.0.4 (12Feb24) Microsoft Windows 8 Professional (build 9200), 64-bit,Cross-compile,Win64
		 */

		$result = [
			'major' => -1,
			'minor' => -1,
			'release' => -1,
			'date' => '',
			'os' => ''
		];
		if (preg_match(self::UNAME_PATTERN, $uname, $match) == 1) {
			// version
			$result['major'] = (int) $match['major'];
			$result['minor'] = (int) $match['minor'];
			$result['release'] = (int) $match['release'];

			// release date
			$result['date'] = $match['date'];

			// os info
			$result['os'] = $match['os'];
		}
		return $result;
	}
}
