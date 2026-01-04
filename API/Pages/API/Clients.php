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
use Bacularis\API\Modules\ClientRecord;
use Bacularis\Common\Modules\Errors\ClientError;

/**
 * Clients endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class Clients extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$limit = $this->Request->contains('limit') && $misc->isValidInteger($this->Request['limit']) ? (int) ($this->Request['limit']) : 0;
		$order_by = $this->Request->contains('order_by') && $misc->isValidName($this->Request['order_by']) ? $this->Request['order_by'] : '';
		$order_type = $this->Request->contains('order_type') && $misc->isValidOrderType($this->Request['order_type']) ? $this->Request['order_type'] : 'asc';
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			['.client'],
			null,
			true
		);
		if ($result->exitcode === 0) {
			if (!empty($order_by)) {
				$cr = new ReflectionClass('Bacularis\API\Modules\ClientRecord');
				$order_by_lc = strtolower($order_by);
				if (!$cr->hasProperty($order_by_lc)) {
					$this->error = ClientError::ERROR_INVALID_COMMAND;
					$this->output = ClientError::MSG_ERROR_INVALID_COMMAND . ' Column: ' . $order_by;
					return;
				}
			}
			$clients = $this->getModule('client')->getClients(
				0,
				$order_by,
				$order_type
			);
			$clients_output = [];
			foreach ($clients as $client) {
				if (in_array($client->name, $result->output)) {
					$clients_output[] = $client;
				}
			}
			if ($limit > 0) {
				/**
				 * Limit in PHP instead database because in the database can exists
				 * clients that are not available in the configuration. It would cause
				 * results inconsistency.
				 */
				array_splice($clients_output, $limit);
			}
			$this->output = $clients_output;
			$this->error = ClientError::ERROR_NO_ERRORS;
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}
}
