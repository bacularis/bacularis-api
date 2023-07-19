<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2023 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\API\Modules\APIConfig;
use Bacularis\Common\Modules\Errors\SoftwareManagementError;

/**
 * Software management remove command support.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class SoftwareManagementRemove extends BaculumAPIServer
{
	public function get()
	{
		$output = [];
		$exitcode = 0;

		$component = $this->Request->contains('component') ? $this->Request['component'] : '';
		$software_mgmt = $this->getModule('software_mgmt');
		$pre_cmd = $cmd = $post_cmd = '';
		switch ($component) {
			case 'director':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_DIR_REMOVE;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_DIR_REMOVE;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_DIR_REMOVE;
				break;
			case 'storage':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_SD_REMOVE;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_SD_REMOVE;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_SD_REMOVE;
				break;
			case 'client':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_FD_REMOVE;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_FD_REMOVE;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_FD_REMOVE;
				break;
			case 'console':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_BCONS_REMOVE;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_BCONS_REMOVE;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_BCONS_REMOVE;
				break;
		}

		// Pre-remove command
		$ret = $software_mgmt->execSoftwareManagementCommand($pre_cmd);
		if ($ret->error === 0) {
			$output = array_merge($output, $ret->output);
		} elseif ($ret->error !== SoftwareManagementError::ERROR_SOFTWARE_MANAGEMENT_COMMAND_NOT_CONFIGURED) {
			$this->output = $ret->output;
			$this->error = $ret->error;
			return;
		}

		// Install command
		$ret = $software_mgmt->execSoftwareManagementCommand($cmd);
		if ($ret->error === 0) {
			$output = array_merge($output, $ret->output);
		} elseif ($ret->error !== 0) {
			$this->output = $ret->output;
			$this->error = $exitcode = $ret->error;
			return;
		}

		// Post-remove command
		$ret = $software_mgmt->execSoftwareManagementCommand($post_cmd);
		if ($ret->error === 0) {
			$output = array_merge($output, $ret->output);
		} elseif ($ret->error !== 0 && $ret->error !== SoftwareManagementError::ERROR_SOFTWARE_MANAGEMENT_COMMAND_NOT_CONFIGURED) {
			$this->output = $ret->output;
			$this->error = $ret->error;
			return;
		}

		// Installation completed successfully
		$this->output = $output;
		$this->error = $ret->error !== SoftwareManagementError::ERROR_SOFTWARE_MANAGEMENT_COMMAND_NOT_CONFIGURED ? $ret->error : $exitcode;
	}
}
