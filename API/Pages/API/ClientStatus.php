<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2023 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
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

use Bacularis\Common\Modules\Errors\ClientError;
use Bacularis\Common\Modules\Errors\GenericError;
use Bacularis\API\Modules\ConsoleOutputPage;

/**
 * Client status.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class ClientStatus extends ConsoleOutputPage
{
	public function get()
	{
		$clientid = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;
		$client = $this->getModule('client')->getClientById($clientid);
		$status = $this->getModule('status_fd');
		$type = $this->Request->contains('type') && $status->isValidOutputType($this->Request['type']) ? $this->Request['type'] : null;
		$out_format = $this->Request->contains('output') && $this->isOutputFormatValid($this->Request['output']) ? $this->Request['output'] : parent::OUTPUT_FORMAT_RAW;
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.client'],
			null,
			true
		);

		$client_exists = false;
		if ($result->exitcode === 0) {
			$client_exists = (is_object($client) && in_array($client->name, $result->output));
		}

		if ($client_exists == false) {
			// Client doesn't exist or is not available for user because of ACL restrictions
			$this->output = ClientError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = ClientError::ERROR_CLIENT_DOES_NOT_EXISTS;
			return;
		}
		$out = (object) ['output' => [], 'error' => 0];
		if ($out_format === parent::OUTPUT_FORMAT_RAW) {
			$out = $this->getRawOutput(['client' => $client->name]);
		} elseif ($out_format === parent::OUTPUT_FORMAT_JSON) {
			$out = $this->getJSONOutput([
				'client' => $client->name,
				'type' => $type
			]);
		}
		$this->output = $out['output'];
		$this->error = $out['error'];
	}

	protected function getRawOutput($params = [])
	{
		// traditional status client output
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			[
				'status',
				'client="' . $params['client'] . '"'
			]
		);
		$error = $result->exitcode == 0 ? $result->exitcode : GenericError::ERROR_WRONG_EXITCODE;
		$ret = [
			'output' => $result->output,
			'error' => $error
		];
		return $ret;
	}

	protected function getJSONOutput($params = [])
	{
		// status client JSON output by API 2 interface
		$status = $this->getModule('status_fd');
		return $status->getStatus(
			$this->director,
			$params['client'],
			$params['type']
		);
	}
}
