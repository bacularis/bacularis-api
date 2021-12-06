<?php
/*
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

Prado::using('Application.API.Class.APIServer');
Prado::using('Application.API.Class.APIInterfaces');

/**
 * API Server version 1.
 * This version receives parameters as GET and POST parameters.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum API
 */
class APIServerV1 extends APIServer implements IAPIServer {

	/**
	 * Support for API GET method request.
	 *
	 * @return none;
	 */
	public function get() {
		$this->getServerObj()->get();
	}

	/**
	 * Support for API PUT method request.
	 *
	 * @return none
	 */
	public function put() {
		$id = $this->Request->contains('id') ? intval($this->Request['id']) : 0;
		$params = new StdClass;

		/**
		 * Check if it is possible to read PUT method data.
		 * Note that some clients sends data in PUT request as PHP input stream which
		 * is not possible to read by $_REQUEST data. From this reason, when is
		 * not possible to ready by superglobal $_REQUEST variable, then is try to
		 * read PUT data by PHP input stream.
		 */
		if ($this->Request->contains('update') && is_array($this->Request['update']) && count($this->Request['update']) > 0) {
			// $_REQUEST available to read
			$params = (object)$this->Request['update'];
		} else {
			// no possibility to read data from $_REQUEST. Try to load from input stream.
			$inputstr = file_get_contents("php://input");

			/**
			 * Read using chunks for case large updates (over 1000 values).
			 * Otherwise max_input_vars limitation in php.ini can be reached (usually
			 * set to 1000 variables)
			 * @see http://php.net/manual/en/info.configuration.php#ini.max-input-vars
			 */
			$chunks = explode('&', $inputstr);

			$response_data = array();
			for($i = 0; $i<count($chunks); $i++) {
				// if chunks would not be used, then here occurs reach max_input_vars limit
				parse_str($chunks[$i], $response_el);
				if (is_array($response_el) && array_key_exists('update', $response_el) && is_array($response_el['update'])) {
					$key = key($response_el['update']);
					$response_data['update'][$key] = $response_el['update'][$key];
				}
			}
			if (is_array($response_data) && array_key_exists('update', $response_data)) {
				$params = (object)$response_data['update'];
			}
		}
		$this->getServerObj()->set($id, $params);
	}

	/**
	 * Support for API POST method request.
	 *
	 * @return none
	 */
	public function post() {
		$params = new StdClass;
		if ($this->Request->contains('create') && is_array($this->Request['create']) && count($this->Request['create']) > 0) {
			$params = (object)$this->Request['create'];
		}
		$this->getServerObj()->create($params);
	}

	/**
	 * Support for API DELETE method request.
	 *
	 * @return none
	 */
	public function delete() {
		$id = null;
		if ($this->Request->contains('id')) {
			$id = $this->Request['id'];
		}
		$this->getServerObj()->remove($id);
	}
}
?>
