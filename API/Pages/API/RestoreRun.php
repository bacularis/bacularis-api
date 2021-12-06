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

Prado::using('Application.API.Class.APIDbModule');
 
/**
 * Run restore command endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class RestoreRun extends BaculumAPIServer {

	public function create($params) {
		$misc = $this->getModule('misc');
		$jobid = property_exists($params, 'id') && $misc->isValidInteger($params->id) ? intval($params->id) : null;
		$client = null;
		if (property_exists($params, 'clientid')) {
			$clientid = intval($params->clientid);
			$client_row = $this->getModule('client')->getClientById($clientid);
			$client = is_object($client_row) ? $client_row->name : null;
		} elseif (property_exists($params, 'client') && $misc->isValidName($params->client)) {
			$client = $params->client;
		}

		$fileset = null;
		if (property_exists($params, 'filesetid')) {
			$filesetid = intval($params->filesetid);
			$fileset_row = $this->getModule('fileset')->getFileSetById($filesetid);
			$fileset = is_object($fileset_row) ? $fileset_row->fileset : null;
		} elseif (property_exists($params, 'fileset') && $misc->isValidName($params->fileset)) {
			$fileset = $params->fileset;
		}

		$rfile = property_exists($params, 'rpath') ? $params->rpath : null;
		$full = property_exists($params, 'full') && $misc->isValidInteger($params->full) ? (bool)$params->full : null;
		$where = property_exists($params, 'where') ? $params->where : null;
		$replace = property_exists($params, 'replace') ? $params->replace : null;

		$restorejob = null;
		if (property_exists($params, 'restorejob') && $misc->isValidName($params->restorejob)) {
			$restorejob = $params->restorejob;
		}
		$strip_prefix = null;
		if (property_exists($params, 'strip_prefix') && $misc->isValidPath($params->strip_prefix)) {
			$strip_prefix = $params->strip_prefix;
		}
		$add_prefix = null;
		if (property_exists($params, 'add_prefix') && $misc->isValidPath($params->add_prefix)) {
			$add_prefix = $params->add_prefix;
		}
		$add_suffix = null;
		if (property_exists($params, 'add_suffix') && $misc->isValidPath($params->add_suffix)) {
			$add_suffix = $params->add_suffix;
		}
		$regex_where = null;
		if (property_exists($params, 'regex_where') && $misc->isValidPath($params->regex_where)) {
			$regex_where = $params->regex_where;
		}

		if(is_null($client)) {
			$this->output = JobError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = JobError::ERROR_CLIENT_DOES_NOT_EXISTS;
			return;
		}

		if(!is_null($rfile) && preg_match($misc::RPATH_PATTERN, $rfile) !== 1) {
			$this->output = JobError::MSG_ERROR_INVALID_RPATH;
			$this->error = JobError::ERROR_INVALID_RPATH;
			return;
		}
		if(!is_null($where) && !$misc->isValidPath($where)) {
			$this->output = JobError::MSG_ERROR_INVALID_WHERE_OPTION;
			$this->error = JobError::ERROR_INVALID_WHERE_OPTION;
			return;
		}

		if(!is_null($replace) && !$misc->isValidReplace($replace)) {
			$this->output = JobError::MSG_ERROR_INVALID_REPLACE_OPTION;
			$this->error = JobError::ERROR_INVALID_REPLACE_OPTION;
			return;
		}

		$command = array('restore',
			'client="' . $client . '"'
		);
		if (is_string($rfile)) {
			// Restore using Bvfs
			$command[] = 'file="?' . $rfile . '"';
		} elseif ($full === true && is_int($jobid) && $jobid > 0 && is_string($fileset)) {
			// Full restore all files
			$command[] = 'jobid="' . $jobid . '"';
			$command[] = 'fileset="' . $fileset . '"';
			$command[] = 'select';
			$command[] = 'all';
			$command[] = 'done';
		}

		if (is_string($replace)) {
			$command[] = 'replace="' . $replace . '"';
		}
		if (is_string($restorejob)) {
			$command[] = 'restorejob="' . $restorejob . '"';
		}
		if (is_string($strip_prefix)) {
			$command[] = 'strip_prefix="' . $strip_prefix . '"';
		}
		if (is_string($add_prefix)) {
			$command[] = 'add_prefix="' . $add_prefix . '"';
		} elseif (is_string($where)) {
			$command[] = 'where="' . $where . '"';
		}
		if (is_string($add_suffix)) {
			$command[] = 'add_suffix="' . $add_suffix . '"';
		}
		if (is_string($regex_where)) {
			$command[] = 'regexwhere="' . $regex_where . '"';
		}
		$command[] = 'yes';

		$restore = $this->getModule('bconsole')->bconsoleCommand($this->director, $command);
		$this->output = $restore->output;
		$this->error = $restore->exitcode;
	}
}
?>
