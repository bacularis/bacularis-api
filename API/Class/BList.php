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
 * Tools used to show list files command output.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum API
 */
class BList extends APIModule {

	/**
	 * Pattern to get only files from list files output.
	 */
	const LIST_FILES_OUTPUT_PATTERN = '/^\| (?!(filename|jobid|\d))(?P<path>.+) \|$/i';

	/**
	 * Parse list files output.
	 *
	 * @param array $output raw list files output lines
	 * @return array parsed list files paths
	 */
	public function parseListFilesOutput(array $output) {
		$result = array();
		for ($i = 0; $i < count($output); $i++) {
			if (preg_match(self::LIST_FILES_OUTPUT_PATTERN, $output[$i], $match) === 1) {
				$result[] = trim($match['path']);
			}
		}
		return $result;
	}

	/**
	 * Find file list items by given keyword.
	 *
	 * @param array $file_list file list
	 * @param string $keyword keyword to find
	 * @return array search result (items)
	 */
	public function findFileListItems($file_list, $keyword) {
		$result = array();
		for ($i = 0; $i < count($file_list); $i++) {
			if (preg_match('!' . preg_quote($keyword, '!') . '!i', $file_list[$i], $match) === 1) {
				$result[] = $file_list[$i];
			}
		}
		return $result;
	}

}
?>
