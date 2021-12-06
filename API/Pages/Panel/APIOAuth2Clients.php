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

Prado::using('Application.API.Class.BaculumAPIPage');

/**
 * API OAuth2 clients page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Panel
 * @package Baculum API
 */
class APIOAuth2Clients extends BaculumAPIPage {

	public function onInit($param) {
		parent::onInit($param);

		$config = $this->getModule('api_config')->getConfig();
		if(count($config) === 0) {
			// Config doesn't exist, go to wizard
			$this->goToPage('APIInstallWizard');
			return;
		} elseif (!$this->IsCallback) {
			$this->loadOAuth2Clients(null, null);
		}
	}

	public function loadOAuth2Clients($sender, $param) {
		$oauth2_cfg = $this->getModule('oauth2_config')->getConfig();
		$clients = array_values($oauth2_cfg);
		$this->getCallbackClient()->callClientFunction(
			'oAPIOAuth2Clients.load_oauth2_clients_cb',
			[$clients]
		);
		$this->hideOAuth2ClientWindow($sender);
	}

	public function cancelOAuth2ClientWindow($sender, $param) {
		$this->hideOAuth2ClientWindow($sender);
	}

	private function hideOAuth2ClientWindow($sender) {
		if (is_object($sender)) {
			if ($sender->ID === 'NewOAuth2Client') {
				$this->getCallbackClient()->callClientFunction(
					'oAPIOAuth2Clients.show_new_client_window',
					[false]
				);
			} elseif ($sender->ID === 'EditOAuth2Client') {
				$this->getCallbackClient()->callClientFunction(
					'oAPIOAuth2Clients.show_edit_client_window',
					[false]
				);
			}
		}
	}

	public function deleteOAuth2Client($sender, $param) {
		$config = $this->getModule('oauth2_config');
		$clients = $config->getConfig();
		$client_id = $param->getCallbackParameter();
		if (key_exists($client_id, $clients)) {
			unset($clients[$client_id]);
		}
		$config->setConfig($clients);
		$this->loadOAuth2Clients(null, null);
	}
}
?>
