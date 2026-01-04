<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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
 * Software management info command support.
 * It shows if package is installed or not.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class SoftwareManagementInfo extends BaculumAPIServer
{
	public function get()
	{
		$output = [];
		$exitcode = 0;

		$component = $this->Request->contains('component') ? $this->Request['component'] : '';
		$software_mgmt = $this->getModule('software_mgmt');
		$cmd = '';
		switch ($component) {
			case 'catalog':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_CAT_INFO;
				break;
			case 'director':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_DIR_INFO;
				break;
			case 'storage':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_SD_INFO;
				break;
			case 'client':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_FD_INFO;
				break;
			case 'console':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_BCONS_INFO;
				break;
		}

		// Info command
		$ret = $software_mgmt->execSoftwareManagementCommand($cmd);
		$this->output = $ret->output;
		$this->error = $ret->error;
	}
}
