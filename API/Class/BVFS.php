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

/**
 * BVFS module class.
 * It provides tools to work with BVFS outputs.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum API
 */
class BVFS extends APIModule {

	const DIR_PATTERN = '/^(?P<pathid>\d+)\t(?P<filenameid>\d+)\t(?P<fileid>\d+)\t(?P<jobid>\d+)\t(?P<lstat>[a-zA-z0-9\+\-\/\ ]+)\t(?P<name>(.*\/|\.{2}))$/';
	const FILE_PATTERN = '/^(?P<pathid>\d+)\t(?P<filenameid>\d+)\t(?P<fileid>\d+)\t(?P<jobid>\d+)\t(?P<lstat>[a-zA-z0-9\+\-\/\ ]+)\t(?P<name>[^\/]+)$/';
	const VERSION_PATTERN = '/^(?P<pathid>\d+)\t(?P<filenameid>\d+)\t(?P<fileid>\d+)\t(?P<jobid>\d+)\t(?P<lstat>[a-zA-Z0-9\+\-\/\ ]+)\t(?P<md5>.+)\t(?P<volname>.+)\t(?P<inchanger>\d+)$/';

	public function parseFileDirList($list) {
		$elements = array();
		$blstat = $this->getModule('blstat');
		for($i = 0; $i < count($list); $i++) {
			if(preg_match(self::DIR_PATTERN, $list[$i], $match) == 1) {
				if($match['name'] == '.') {
					continue;
				}
				$elements[] = array(
					'pathid' => $match['pathid'],
					'filenameid' => $match['filenameid'],
					'fileid' => $match['fileid'],
					'jobid' => $match['jobid'],
					'lstat' => $blstat->decode($match['lstat']),
					'name' => $match['name'],
					'type' => 'dir'
				);
			} elseif(preg_match(self::FILE_PATTERN, $list[$i], $match) == 1) {
				if($match['name'] == '.') {
					continue;
				}
				$elements[] = array(
					'pathid' => $match['pathid'],
					'filenameid' => $match['filenameid'],
					'fileid' => $match['fileid'],
					'jobid' => $match['jobid'],
					'lstat' => $blstat->decode($match['lstat']),
					'name' => $match['name'],
					'type' => 'file'
				);
			}
		}
		usort($elements, 'sortFilesListByName');
		return $elements;
	}

	public function parseFileVersions($list) {
		$elements = array();
		for($i = 0; $i < count($list); $i++) {
			if(preg_match(self::VERSION_PATTERN, $list[$i], $match) == 1) {
				$elements[$match['fileid']] = array(
					'pathid' => $match['pathid'],
					'filenameid' => $match['filenameid'],
					'fileid' => $match['fileid'],
					'jobid' => $match['jobid'],
					'lstat' => $this->getModule('blstat')->decode($match['lstat']),
					'md5' => $match['md5'],
					'volname' => $match['volname'],
					'inchanger' => $match['inchanger'],
					'type' => 'file'
				);
			}
		}
		return $elements;
	}

}

/*
 * Small sorting callback function to sort files and directories by name.
 * Function keeps '.' and '..' names always in the beginning of array.
 * Used to sort files and directories from Bvfs.
 */
function sortFilesListByName($a, $b) {
	$firstLeft = substr($a['name'], 0, 1);
	$firstRight = substr($b['name'], 0, 1);
	if ($firstLeft == '.' && $firstRight != '.') {
		return -1;
	} else if ($firstRight == '.' && $firstLeft != '.') {
		return 1;
	}
	return strcmp($a['name'], $b['name']);
}
?>
