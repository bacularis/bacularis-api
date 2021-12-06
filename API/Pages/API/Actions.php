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

Prado::using('Application.API.Class.APIConfig');
 
/**
 * API actions support.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class Actions extends BaculumAPIServer {

	public function get() {
		$component = $this->Request->contains('component') ? $this->Request['component'] : '';
		$action = $this->Request->contains('action') ? $this->Request['action'] : '';
		$action_type = null;

		switch ($component) {
			case 'director':
				if ($action === 'start') {
					$action_type = APIConfig::ACTION_DIR_START;
				} elseif ($action === 'stop') {
					$action_type = APIConfig::ACTION_DIR_STOP;
				} elseif ($action === 'restart') {
					$action_type = APIConfig::ACTION_DIR_RESTART;
				}
				break;
			case 'storage':
				if ($action === 'start') {
					$action_type = APIConfig::ACTION_SD_START;
				} elseif ($action === 'stop') {
					$action_type = APIConfig::ACTION_SD_STOP;
				} elseif ($action === 'restart') {
					$action_type = APIConfig::ACTION_SD_RESTART;
				}
				break;
			case 'client':
				if ($action === 'start') {
					$action_type = APIConfig::ACTION_FD_START;
				} elseif ($action === 'stop') {
					$action_type = APIConfig::ACTION_FD_STOP;
				} elseif ($action === 'restart') {
					$action_type = APIConfig::ACTION_FD_RESTART;
				}
				break;
		}

		if (is_string($action_type)) {
			$result = $this->getModule('comp_actions')->execAction($action_type);
			if ($result->error === 0) {
				$this->output = ActionsError::MSG_ERROR_NO_ERRORS;
				$this->error = ActionsError::ERROR_NO_ERRORS;
			} else {
				$this->output = $result->output;
				$this->error = $result->error;
			}
		} else {
			$this->output = ActionsError::MSG_ERROR_ACTIONS_ACTION_DOES_NOT_EXIST;
			$this->error = ActionsError::ERROR_ACTIONS_ACTION_DOES_NOT_EXIST;
		}
	}
}
?>
