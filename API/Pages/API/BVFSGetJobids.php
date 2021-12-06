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
 * BVFS get jobids to do restore.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class BVFSGetJobids extends BaculumAPIServer {

	public function get() {
		$jobid = $this->Request->contains('jobid') ? intval($this->Request['jobid']) : 0;
		$inc_copy_job = $this->Request->contains('inc_copy_job') ? intval($this->Request['inc_copy_job']) : 0;
		if ($jobid > 0) {
			$result = array();
			$error = BVFSError::ERROR_NO_ERRORS;
			if ($inc_copy_job == 1) {
				/**
				 * To use copy jobs to restore here is used Baculum own method to get
				 * all compositional jobs. It is because of a bug in .bvfs_get_jobids command
				 * reported here:
				 * http://bugs.bacula.org/view.php?id=2500
				 */
				$jobids = $this->getModule('job')->getJobidsToRestore($jobid);
				$jobids_str = implode(',', $jobids); // implode to be compatible with Bvfs output
				if (!empty($jobids_str)) {
					$result = array($jobids_str);
				}
			} else {
				$cmd = array('.bvfs_get_jobids', 'jobid="' . $jobid . '"');
				$jobids = $this->getModule('bconsole')->bconsoleCommand($this->director, $cmd);
				$result = $jobids->output;
				$error = $jobids->exitcode;
			}
			$this->output = $result;
			$this->error = $error;
		} else {
			$this->output = BVFSError::MSG_ERROR_INVALID_JOBID;
			$this->error = BVFSError::ERROR_INVALID_JOBID;
		}
	}
}
?>
