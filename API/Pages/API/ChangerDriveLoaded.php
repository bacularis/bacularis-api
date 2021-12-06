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
 * Which slot is loaded in autochanger drive.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class ChangerDriveLoaded extends BaculumAPIServer {

	public function get() {
		$misc = $this->getModule('misc');
		$device_name = $this->Request->contains('device_name') && $misc->isValidName($this->Request['device_name']) ? $this->Request['device_name'] : null;
		$drive = $this->Request->contains('drive') && $misc->isValidName($this->Request['drive']) ? $this->Request['drive'] : null;

		if (is_null($drive)) {
			$this->output = ChangerCommandError::MSG_ERROR_CHANGER_COMMAND_AUTOCHANGER_DRIVE_DOES_NOT_EXIST;
			$this->error = ChangerCommandError::ERROR_CHANGER_COMMAND_AUTOCHANGER_DRIVE_DOES_NOT_EXIST;
			return;
		}

		$result = $this->getModule('changer_command')->execChangerCommand(
			$device_name,
			'loaded',
			$drive
		);

		if ($result->error === 0 && count($result->output)) {
			$this->output = ['slot' => intval($result->output[0])];
		} else {
			$this->output = $result->output;
		}

		$this->error = $result->error;
	}
}
?>
