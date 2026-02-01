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

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\API\Modules\Bconsole;
use Bacularis\Common\Modules\Errors\BaculaConfigError;
use Bacularis\Common\Modules\Errors\ClientError;

/**
 * Client endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class Client extends BaculumAPIServer
{
	public function get()
	{
		$clientid = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;
		$client = null;
		if ($clientid > 0) {
			$client = $this->getModule('client')->getClientById($clientid);
		}
		$result = $this->getModule('bconsole')->bconsoleCommand($this->director, ['.client']);
		if ($result->exitcode === 0) {
			array_shift($result->output);
			if (is_object($client) && in_array($client->name, $result->output)) {
				$this->output = $client;
				$this->error = ClientError::ERROR_NO_ERRORS;
			} else {
				$this->output = ClientError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
				$this->error = ClientError::ERROR_CLIENT_DOES_NOT_EXISTS;
			}
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}

	public function remove($id)
	{
		$clientid = (int) $id;
		$client = $this->getModule('client');
		$client_obj = $client->getClientById($clientid);
		if (is_null($client_obj)) {
			// Client does not exist in catalog - error
			$this->output = ClientError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = ClientError::ERROR_CLIENT_DOES_NOT_EXISTS;
			return;
		}

		// Get client config
		$bsettings = $this->getModule('bacula_setting');
		$config = $bsettings->getConfig(
			'dir',
			'Client',
			$client_obj->name
		);
		if ($config['exitcode'] != 0) {
			$this->output = ClientError::MSG_ERROR_WRONG_EXITCODE . ' ' . var_export($config['output'], true);
			$this->error = ClientError::ERROR_WRONG_EXITCODE;
			return;
		}

		// Get clients
		$bconsole = $this->getModule('bconsole');
		$clients = $bconsole->bconsoleCommand(
			$this->director,
			['.client'],
			null,
			true
		);
		if ($clients->exitcode != 0) {
			$this->output = ClientError::MSG_ERROR_WRONG_EXITCODE . ' ' . var_export($clients->output, true);
			$this->error = ClientError::ERROR_WRONG_EXITCODE;
			return;
		}

		// Check if client config does not exist.
		if (count($config['output']) > 0) {
			// Client config exists - end
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_ALREADY_EXISTS;
			$this->error = BaculaConfigError::ERROR_CONFIG_ALREADY_EXISTS;
			return;
		}

		// Check if client exists on config client list
		if (in_array($client_obj->name, $clients->output)) {
			/**
			 * Client does not exists in configuration but exists in Director memory.
			 * It looks that it is removed from config but the Director configuration has not been reloaded yet.
			 */
			$this->output = BaculaConfigError::MSG_ERROR_CONFIG_DOES_NOT_EXIST;
			$this->error = BaculaConfigError::ERROR_CONFIG_DOES_NOT_EXIST;
			return;
		}

		// Client does not exist in config, delete it from the catalog
		$result = $bconsole->bconsoleCommand(
			$this->director,
			['delete', 'client="' . $client_obj->name . '"'],
			Bconsole::PTYPE_CONFIRM_YES_CMD
		);
		$this->output = $result->output;
		$this->error = $result->exitcode;
	}
}
