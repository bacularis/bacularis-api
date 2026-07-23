<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\API\Modules\BaculaConsole;
use Bacularis\API\Modules\Bconsole;
use Bacularis\Common\Modules\Errors\BconsoleError;
use Bacularis\Common\Modules\Errors\JobError;

/**
 * Start the Bacula console restore session.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class RestoreStart extends BaculumAPIServer
{
	public function create($params)
	{
		// Prepare parameters to start restore session.
		$crypto = $this->getModule('crypto');
		$api_config = $this->getModule('api_config');
		$config = $api_config->getConfig('bconsole');
		$session_id = $crypto->getRandomString(36);
		$bconsole_bin = Bconsole::getCmdPath();
		$bconsole_cfg = Bconsole::getCfgPath();
		$use_sudo = ($config['use_sudo'] == 1);
		$sudo_user = $config['sudo_user'] ?? null;
		$sudo_group = $config['sudo_group'] ?? null;

		$parameters_con = [
			'session-id' => $session_id,
			'director' => $this->director,
			'bconsole-bin' => $bconsole_bin,
			'bconsole-conf' => $bconsole_cfg,
			'use-sudo' => $use_sudo
		];

		if ($sudo_user) {
			$parameters_con['sudo-user'] = $sudo_user;
		}
		if ($sudo_group) {
			$parameters_con['sudo-group'] = $sudo_group;
		}

		// Add debug option
		$api_config = $this->getModule('api_config');
		$config = $api_config->getConfig('api');
		if (key_exists('debug', $config) && $config['debug'] == 1) {
			$parameters_con['debug'] = 1;
		}


		// Open a new Bacula console session first
		$result = BaculaConsole::start($parameters_con);
		if ($result['error'] == 0) {
			$misc = $this->getModule('misc');
			$jobid = property_exists($params, 'jobid') && $misc->isValidIdsList($params->jobid) ? $params->jobid : null;

			// Backup client
			$client = null;
			$client_mod = $this->getModule('client');
			if (property_exists($params, 'clientid')) {
				$clientid = (int) ($params->clientid);
				$client_row = $client_mod->getClientById($clientid);
				$client = is_object($client_row) ? $client_row->name : null;
			} elseif (property_exists($params, 'client') && $misc->isValidName($params->client)) {
				$client = $params->client;
			}
			// Backup fileset
			$fileset = null;
			if (property_exists($params, 'filesetid')) {
				$filesetid = (int) ($params->filesetid);
				$fileset_mod = $this->getModule('fileset');
				$fileset_row = $fileset_mod->getFileSetById($filesetid);
				$fileset = is_object($fileset_row) ? $fileset_row->fileset : null;
			} elseif (property_exists($params, 'fileset') && $misc->isValidName($params->fileset)) {
				$fileset = $params->fileset;
			}

			$restorejob = null;
			if (property_exists($params, 'restorejob') && $misc->isValidName($params->restorejob)) {
				$restorejob = $params->restorejob;
			}

			if (is_null($jobid)) {
				$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS;
				$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
				return;
			}
			if (is_null($client)) {
				$this->output = JobError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
				$this->error = JobError::ERROR_CLIENT_DOES_NOT_EXISTS;
				return;
			}
			if (is_null($fileset)) {
				$this->output = JobError::MSG_ERROR_FILESET_DOES_NOT_EXISTS;
				$this->error = JobError::ERROR_FILESET_DOES_NOT_EXISTS;
				return;
			}
			if (is_null($restorejob)) {
				$emsg = ' Required restore job has not been provided.';
				$this->output = JobError::MSG_ERROR_JOB_DOES_NOT_EXISTS . $emsg;
				$this->error = JobError::ERROR_JOB_DOES_NOT_EXISTS;
				return;
			}

			$command = 'restore/start';
			$parameters_res = [
				'session-id' => $session_id,
				'job-id' => $jobid,
				'client' => $client,
				'fileset' => $fileset,
				'restorejob' => $restorejob
			];

			// Add debug option
			$api_config = $this->getModule('api_config');
			$config = $api_config->getConfig('api');
			if (key_exists('debug', $config) && $config['debug'] == 1) {
				$parameters_res['debug'] = 1;
			}

			// Run Bacula restore session
			$result = BaculaConsole::execute($command, $parameters_res);
			if ($result['error'] == 0) {
				$output_str = implode('', $result['output']);
				$this->output = json_decode($output_str, true);
				$this->error = BconsoleError::ERROR_NO_ERRORS;
			} else {
				$emsg = ' Error=' . $result['error'] . ', Output=' . var_export($result['output'], true);
				$this->output = BconsoleError::MSG_ERROR_WRONG_EXITCODE . $emsg;
				$this->error = BconsoleError::ERROR_WRONG_EXITCODE;
			}
		} else {
			$this->output = $result['output'];
			$this->error = $result['error'];
		}
	}
}
