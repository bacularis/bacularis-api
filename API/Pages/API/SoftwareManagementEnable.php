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
 * Software management enable command support.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class SoftwareManagementEnable extends BaculumAPIServer
{
	public function get() {
		$component = $this->Request->contains('component') ? $this->Request['component'] : '';
		$software_mgmt = $this->getModule('software_mgmt');
		$cmd = '';
		switch ($component) {
			case 'director':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_DIR_ENABLE;
				break;
			case 'storage':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_SD_ENABLE;
				break;
			case 'client':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_FD_ENABLE;
				break;
		}

		// Install command
		$ret = $software_mgmt->execSoftwareManagementCommand($cmd);

		// Installation completed successfully
		$this->output = $ret->output;
		$this->error = $ret->error;
	}
}
