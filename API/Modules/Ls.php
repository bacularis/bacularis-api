<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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
 * Ls command module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class Ls extends APIModule
{
	public const LS_OUTPUT_PATTERN = '/^(?P<perm>[a-z\-\.]+)\s+(?P<nb_hardlink>\d+)\s+(?P<owner>[\w.\-]+)\s+(?P<group>[\w.\-]+)\s+(?P<size>\d+)\s+(?P<mtime>[\d\-]+\s+[\d:]+)\s+(?P<item>(?U:[\S\s]+))(?P<dest>(?(?=\s+\-\>\s+)[\S\s]*))$/i';

	public function parseOutput(array $output)
	{
		$result = [];
		for ($i = 0; $i < count($output); $i++) {
			if (preg_match(self::LS_OUTPUT_PATTERN, $output[$i], $match) === 1) {
				$type = substr($match['perm'], 0, 1);
				$result[] = [
					'perm' => $match['perm'],
					'nb_hardlink' => (int) ($match['nb_hardlink']),
					'owner' => $match['owner'],
					'group' => $match['group'],
					'size' => (int) ($match['size']),
					'mtime' => $match['mtime'],
					'item' => $match['item'],
					'type' => $type,
					'dest' => key_exists('dest', $match) ? $match['dest'] : null
				];
			}
		}
		return $result;
	}
}
