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
 
/**
 * Welcome endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class Welcome extends BaculumAPIServer {
	public function get() {
		$panel_url = sprintf('%s://%s:%d',
			isset($_SERVER['HTTPS']) ? 'https' : 'http',
			$_SERVER['SERVER_NAME'],
			$_SERVER['SERVER_PORT']
		);
		$panel_url .= $this->getService()->constructUrl('Panel.APIHome');
		$this->output = "Welcome in the Baculum API. Panel is available on $panel_url";
		$this->error = GenericError::ERROR_NO_ERRORS;
	}
}
?>
