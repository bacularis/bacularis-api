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

Prado::using('Application.API.Class.Bconsole');

/**
 * Component status module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class ComponentStatus extends BaculumAPIServer {

	public function get() {
		$component = $this->Request->contains('component') ? $this->Request['component'] : '';
		$component_name = $this->Request->contains('name') && $this->getModule('misc')->isValidName($this->Request['name']) ? $this->Request['name'] : null;
		$type = null;
		$status = null;
		switch($component) {
			case 'director': {
				$status = $this->getModule('status_dir');
				$type = $this->Request->contains('type') && $status->isValidOutputType($this->Request['type']) ? $this->Request['type'] : '';
				break;
			}
			case 'storage': {
				$status = $this->getModule('status_sd');
				$type = $this->Request->contains('type') && $status->isValidOutputType($this->Request['type']) ? $this->Request['type'] : 'header';
				break;
			}
			case 'client':  {
				$status = $this->getModule('status_fd');
				$type = $this->Request->contains('type') && $status->isValidOutputType($this->Request['type']) ? $this->Request['type'] : 'header';
			}
		}
		if (is_object($status)) {
			$ret = $status->getStatus(
				$this->director,
				$component_name,
				$type
			);
			$this->output = $ret['output'];
			$this->error = $ret['error'];
		} else {
			$this->output = GenericError::MSG_ERROR_INTERNAL_ERROR;
			$this->error =  GenericError::ERROR_INTERNAL_ERROR;
		}
	}
}
?>
