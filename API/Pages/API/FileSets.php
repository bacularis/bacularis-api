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
 * FileSets endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class FileSets extends BaculumAPIServer {

	public function get() {
		$limit = $this->Request->contains('limit') ? intval($this->Request['limit']) : 0;
		$filesets = $this->getModule('fileset')->getFileSets($limit);
		$result = $this->getModule('bconsole')->bconsoleCommand(
			$this->director,
			array('.fileset')
		);
		if ($result->exitcode === 0) {
			array_shift($result->output);
			if (is_array($filesets)) {
				$fs = array();
				for ($i = 0; $i < count($filesets); $i++) {
					if(in_array($filesets[$i]->fileset, $result->output)) {
						$fs[] = $filesets[$i];
					}
				}
				$this->output = $fs;
				$this->error = FileSetError::ERROR_NO_ERRORS;
			} else {
				$this->output = FileSetError::MSG_ERROR_FILESET_DOES_NOT_EXISTS;
				$this->error = FileSetError::ERROR_FILESET_DOES_NOT_EXISTS;
			}
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}
}

?>
