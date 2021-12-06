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
 * Client bandwidth limit endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class ClientBandwidthLimit extends BaculumAPIServer {

	public function set($id, $params) {
		$cli = null;
		if ($id > 0) {
			$cli = $this->getModule('client')->getClientById($id);
		}

		$client = null;
		$result = $this->getModule('bconsole')->bconsoleCommand($this->director, array('.client'));
		if ($result->exitcode === 0) {
			array_shift($result->output);
			if(is_object($cli) && in_array($cli->name, $result->output)) {
				$client = $cli->name;
			}
		}
		if (is_null($client)) {
			$this->output = ClientError::MSG_ERROR_CLIENT_DOES_NOT_EXISTS;
			$this->error = ClientError::ERROR_CLIENT_DOES_NOT_EXISTS;
			return;
		}

		$limit = property_exists($params, 'limit') && $this->getModule('misc')->isValidInteger($params->limit) ? $params->limit : 0;

		$cmd = array('setbandwidth', 'client="' . $client . '"', 'limit="' . $limit . '"');
		$result = $this->getModule('bconsole')->bconsoleCommand($this->director, $cmd);
		$this->output = $result->output;
		if ($result->exitcode === 0) {
			$this->error = GenericError::ERROR_NO_ERRORS;
		} else {
			$this->error = $result->exitcode;
		}
	}
}
?>
