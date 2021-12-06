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

/**
 * How many slots has autochanger.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class ChangerSlots extends BaculumAPIServer {

	public function get() {
		$misc = $this->getModule('misc');
		$device_name = $this->Request->contains('device_name') && $misc->isValidName($this->Request['device_name']) ? $this->Request['device_name'] : null;

		$result = $this->getModule('changer_command')->execChangerCommand(
			$device_name,
			'slots'
		);

		if ($result->error === 0 && count($result->output)) {
			$this->output = ['slots' => intval($result->output[0])];
		} else {
			$this->output = $result->output;
		}
		$this->error = $result->error;
	}
}
?>
