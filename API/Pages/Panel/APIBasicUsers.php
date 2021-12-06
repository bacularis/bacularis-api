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

Prado::using('System.Web.UI.ActiveControls.TCallback');
Prado::using('Application.API.Class.BaculumAPIPage');

/**
 * API Basic users page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Panel
 * @package Baculum API
 */
class APIBasicUsers extends BaculumAPIPage {

	public function onInit($param) {
		parent::onInit($param);

		$config = $this->getModule('api_config')->getConfig();
		if(count($config) === 0) {
			// Config doesn't exist, go to wizard
			$this->goToPage('APIInstallWizard');
			return;
		} elseif (!$this->IsCallback) {
			$this->loadBasicUsers(null, null);
		}
	}

	public function loadBasicUsers($sender, $param) {
		$users = $this->getModule('basic_config')->getUsers();
		$this->getCallbackClient()->callClientFunction(
			'oAPIBasicUsers.load_basic_users_cb',
			[$users]
		);
		$this->hideBasicUserWindow($sender);
	}

	public function cancelBasicUserWindow($sender, $param) {
		$this->hideBasicUserWindow($sender);
	}

	private function hideBasicUserWindow($sender) {
		if (is_object($sender)) {
			if ($sender->ID === 'NewBasicClient') {
				$this->getCallbackClient()->callClientFunction(
					'oAPIBasicUsers.show_new_user_window',
					[false]
				);
			} elseif ($sender->ID === 'EditBasicClient') {
				$this->getCallbackClient()->callClientFunction(
					'oAPIBasicUsers.show_edit_user_window',
					[false]
				);
			}
		}
	}

	public function deleteBasicUser($sender, $param) {
		$username = $param->getCallbackParameter();
		$this->getModule('basic_config')->removeUser($username);
		$this->loadBasicUsers(null, null);
	}
}
?>
