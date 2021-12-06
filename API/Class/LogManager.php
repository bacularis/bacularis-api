<?php
/*
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

Prado::using('Application.API.Class.APIModule');
Prado::using('Application.API.Class.LogRecord');

/**
 * Log manager module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum API
 */
class LogManager extends APIModule {

	/**
	 * Get job log by job identifier.
	 *
	 * @param integer $jobid job identifier
	 * @param boolean $show_time show time in job log
	 * @return array job log lines
	 */
	public function getLogByJobId($jobid, $show_time = false) {
		$logs = LogRecord::finder()->findAllByjobid($jobid);
		$joblog = [];
		if(is_array($logs)) {
			foreach($logs as $log) {
				if ($show_time) {
					$joblog[] = $log->time . ' ' . $log->logtext;
				} else {
					$joblog[] = $log->logtext;
				}
			}
		}
		return $joblog;
	}
}
?>
