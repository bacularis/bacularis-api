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
 * Client endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class Client extends BaculumAPIServer {

	public function get() {
		$clientid = $this->Request->contains('id') ? intval($this->Request['id']) : 0;
		$client = null;
		if ($clientid > 0) {
			$client = $this->getModule('client')->getClientById($clientid);
		}
		$result = $this->getModule('bconsole')->bconsoleCommand($this->director, array('.client'));
		if ($result->exitcode === 0) {
			array_shift($result->output);
			if(is_object($client) && in_array($client->name, $result->output)) {
				$this->output = $client;
				$this->error = ClientError::ERROR_NO_ERRORS;
			} else {
				$this->output = ClientError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
				$this->error =ClientError::ERROR_CLIENT_DOES_NOT_EXISTS;
			}
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}
}

?>
