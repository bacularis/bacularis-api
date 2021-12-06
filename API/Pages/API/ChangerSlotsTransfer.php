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

Prado::using('Application.API.Class.ChangerCommand');

/**
 * Transfer autochanger tape from slot to slot.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class ChangerSlotsTransfer extends BaculumAPIServer {

	public function get() {
		$output = [];
		$misc = $this->getModule('misc');
		if ($this->Request->contains('out_id') && $misc->isValidAlphaNumeric($this->Request->itemAt('out_id'))) {
			$out_id = $this->Request->itemAt('out_id');
			$output = ChangerCommand::readOutputFile($out_id);
		}
		$this->output = $output;
		$this->error = DeviceError::ERROR_NO_ERRORS;
	}

	public function set($id, $params) {
		$misc = $this->getModule('misc');
		$device_name = $this->Request->contains('device_name') && $misc->isValidName($this->Request['device_name']) ? $this->Request['device_name'] : null;
		$slotsrc = $this->Request->contains('slotsrc') && $misc->isValidInteger($this->Request['slotsrc']) ? intval($this->Request['slotsrc']) : null;
		$slotdest = $this->Request->contains('slotdest') && $misc->isValidInteger($this->Request['slotdest']) ? intval($this->Request['slotdest']) : null;

		if (is_null($slotsrc) || is_null($slotdest)) {
			$this->output = DeviceError::MSG_ERROR_DEVICE_WRONG_SLOT_NUMBER;
			$this->error = DeviceError::ERROR_DEVICE_WRONG_SLOT_NUMBER;
			return;
		}

		$result = $this->getModule('changer_command')->execChangerCommand(
			$device_name,
			'transfer',
			null,
			$slotsrc,
			$slotdest,
			ChangerCommand::PTYPE_BG_CMD
		);
		$this->output = $result->output;
		$this->error = $result->error;
	}
}
?>
