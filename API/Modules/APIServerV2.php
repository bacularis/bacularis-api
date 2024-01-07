<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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

namespace Bacularis\API\Modules;

/**
 * API Server version 2.
 * This version receives parameters as GET and POST parameters.
 * Main difference comparing to version 1 is that POST params are sent as
 * a JSON string in POST requests body.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class APIServerV2 extends APIServer implements IAPIServer
{
	/**
	 * Support for API GET method request.
	 *
	 */
	public function get()
	{
		$this->getServerObj()->get();
	}

	/**
	 * Support for API PUT method request.
	 *
	 */
	public function put()
	{
		$id = $this->Request->contains('id') ? (int) ($this->Request['id']) : 0;
		$inputstr = file_get_contents("php://input");
		$params = json_decode($inputstr);
		if (is_null($params)) {
			$params = new \StdClass();
		}
		$this->getServerObj()->set($id, $params);
	}

	/**
	 * Support for API POST method request.
	 *
	 */
	public function post()
	{
		$inputstr = file_get_contents("php://input");
		$params = json_decode($inputstr);
		if (is_null($params)) {
			$params = new \StdClass();
		}
		$this->getServerObj()->create($params);
	}

	/**
	 * Support for API DELETE method request.
	 *
	 */
	public function delete()
	{
		$id = null;
		if ($this->Request->contains('id')) {
			$id = $this->Request['id'];
		}
		$this->getServerObj()->remove($id);
	}
}
