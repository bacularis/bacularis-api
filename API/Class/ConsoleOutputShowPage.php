<?php
/*
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

Prado::using('Application.API.Class.ConsoleOutputPage');

/**
 * Get console output for 'show' type commands.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
abstract class ConsoleOutputShowPage extends ConsoleOutputPage {

	/**
	 * Parse 'show' type command output for specific resource.
	 *
	 * @param array $output 'show' command output
	 * @return array parsed output
	 */
	protected function parseOutput(array $output) {
		$ret = [];
		for ($i = 0; $i < count($output); $i++) {
			$mcount = preg_match_all('/(?<=\s)\w+=.+?(?=\s+\w+=.+|$)/i', $output[$i], $matches);
			if ($mcount === 0) {
				continue;
			}
			for ($j = 0; $j < count($matches[0]); $j++) {
				list($key, $value) = explode('=', $matches[0][$j], 2);
				$key = strtolower($key);
				if (key_exists($key, $ret)) {
					/*
					 * The most important options are in first lines.
					 * If keys are double skip the second ones
					 */
					continue;
				}
				$ret[$key] = $value;
			}
		}
		return $ret;
	}

	/**
	 * Parse 'show' type command output for all resources given type.
	 *
	 * @param array $output 'show' command output
	 * @return array parsed output
	 */
	protected function parseOutputAll(array $output) {
		$ret = $part = [];
		$section = '';
		for ($i = 0; $i < count($output); $i++) {
			$scount = preg_match('/^[A-Za-z]+: name=.+/i', $output[$i], $match);
			$mcount = preg_match_all('/(?<=\s)\w+=.*?(?=\s+\w+=.*?|$)/i', $output[$i], $matches);
			if ($mcount == 0) {
				continue;
			}
			for ($j = 0; $j < count($matches[0]); $j++) {
				list($key, $value) = explode('=', $matches[0][$j], 2);
				$key = strtolower($key);
				if ($i > 0 && $scount == 1 && count($part) > 0) {
					$ret[] = $part;
					$part = [];
					$scount = 0;
				}
				if (key_exists($key, $part)) {
					/*
					 * The most important options are in first lines.
					 * If keys are double skip the second ones
					 */
					continue;
				}
				$part[$key] = $value;
			}
		}
		if (count($part) > 0) {
			$ret[] = $part;
			$part = [];
		}
		return $ret;
	}
}
?>
