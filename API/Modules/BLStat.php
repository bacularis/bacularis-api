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

namespace Bacularis\API\Modules;

use Bacularis\Common\Modules\Miscellaneous;

/**
 * Bacula LStat value support.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BLStat extends APIModule
{
	/**
	 * Decode Bacula base64 encoded LStat value.
	 *
	 * @param string $lstat encoded LStat string
	 * @return array decoded values from LStat string
	 */
	public static function decode($lstat)
	{
		$base64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
		$lstat = trim($lstat);
		$lstat_fields = explode(' ', $lstat);
		$lstat_len = count($lstat_fields);
		if ($lstat_len < 16) {
			// not known or empty lstat value
			return;
		} elseif ($lstat_len > 16) {
			// cut off unknown fields
			array_splice($lstat_fields, 16);
		}

		[
			$dev,
			$inode,
			$mode,
			$nlink,
			$uid,
			$gid,
			$rdev,
			$size,
			$blocksize,
			$blocks,
			$atime,
			$mtime,
			$ctime,
			$linkfi,
			$flags,
			$data
		] = $lstat_fields;
		$encoded_values = [
			'dev' => $dev,
			'inode' => $inode,
			'mode' => $mode,
			'nlink' => $nlink,
			'uid' => $uid,
			'gid' => $gid,
			'rdev' => $rdev,
			'size' => $size,
			'blocksize' => $blocksize,
			'blocks' => $blocks,
			'atime' => $atime,
			'mtime' => $mtime,
			'ctime' => $ctime,
			'linkfi' => $linkfi,
			'flags' => $flags,
			'data' => $data
		];

		$ret = [];
		foreach ($encoded_values as $key => $val) {
			$result = 0;
			$is_minus = false;
			$start = 0;

			if (substr($val, 0, 1) === '-') {
				$is_minus = true;
				$start++;
			}

			for ($i = $start; $i < strlen($val); $i++) {
				$result = bcmul($result, bcpow(2, 6));
				$result += strpos($base64, substr($val, $i, 1));
			}
			$ret[$key] = ($is_minus === true) ? -$result : $result;
		}
		return $ret;
	}

	/**
	 * Get decoded and human readable LStat values.
	 *
	 * @param string $lstat LStat value to decode
	 * @return array decoded LStat values
	 */
	public static function lstat_human($lstat)
	{
		$value = self::decode($lstat);
		$value['mode'] = Miscellaneous::get_human_mode($value['mode']);
		return $value;
	}
}
