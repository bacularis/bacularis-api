<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2022 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

use Bacularis\API\Modules\APIConfig;
use Bacularis\Common\Modules\Errors\SoftwareManagementError;

/**
 * Software management install command support.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class SoftwareManagementInstall extends BaculumAPIServer
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
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_DIR_INSTALL;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_DIR_INSTALL;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_DIR_INSTALL;
				break;
			case 'storage':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_SD_INSTALL;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_SD_INSTALL;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_SD_INSTALL;
				break;
			case 'client':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_FD_INSTALL;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_FD_INSTALL;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_FD_INSTALL;
				break;
			case 'console':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_BCONS_INSTALL;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_BCONS_INSTALL;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_BCONS_INSTALL;
				break;
		}

		// Pre-install command
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

		// Post-install command
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
