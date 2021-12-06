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
 * Clients endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class Clients extends BaculumAPIServer {

	public function get() {
		$limit = $this->Request->contains('limit') ? intval($this->Request['limit']) : 0;
		$result = $this->getModule('bconsole')->bconsoleCommand($this->director, array('.client'));
		if ($result->exitcode === 0) {
			$clients = $this->getModule('client')->getClients($limit);
			array_shift($result->output);
			$clients_output = array();
			foreach($clients as $client) {
				if(in_array($client->name, $result->output)) {
					$clients_output[] = $client;
				}
			}
			$this->output = $clients_output;
			$this->error = ClientError::ERROR_NO_ERRORS;
		} else {

			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}
}

?>
