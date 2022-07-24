<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2022 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
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

use Prado\Web\UI\JuiControls\TJuiProgressbar;
use Prado\Web\UI\ActiveControls\TActiveDataGrid;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TActiveTextBox;
use Prado\Web\UI\ActiveControls\TCallback;
use Bacularis\API\Modules\BaculumAPIPage;

/**
 * API main page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Panel
 */
class APIHome extends BaculumAPIPage
{
	public function onInit($param)
	{
		parent::onInit($param);

		$config = $this->getModule('api_config')->getConfig();
		if (count($config) === 0) {
			// Config doesn't exist, go to wizard
			$this->goToPage('APIInstallWizard');
			return;
		} elseif (!$this->IsCallback) {
			$this->setAuthParams(null, null);
			$this->loadAuthParams(null, null);
		}
	}

	public function setAuthParams($sender, $param)
	{
		$config = $this->getModule('api_config')->getConfig();
		$base_params = ['auth_type' => $config['api']['auth_type']];
		$params = [];
		if ($config['api']['auth_type'] === 'oauth2') {
			$oauth2_cfg = $this->getModule('oauth2_config')->getConfig();
			$client_id = null;
			if (is_object($param)) {
				$client_id = $param->CallbackParameter;
			}
			if (is_string($client_id)) {
				$params = [
					'client_id' => $oauth2_cfg[$client_id]['client_id'],
					'client_secret' => $oauth2_cfg[$client_id]['client_secret'],
					'redirect_uri' => $oauth2_cfg[$client_id]['redirect_uri'],
					'scope' => explode(' ', $oauth2_cfg[$client_id]['scope'])
				];
			}
		} elseif ($config['api']['auth_type'] === 'basic') {
			if (is_object($param)) {
				$params['login'] = $param->CallbackParameter;
				$params['password'] = '';
			} else {
				// no auth params, possibly no authentication
				$params['login'] = $params['password'] = '';
			}
		}
		$params = array_merge($base_params, $params);
		$this->AuthParamsInput->Value = json_encode($params);
	}

	public function loadAuthParams($sender, $param)
	{
		$ids = $values = [];
		$config = $this->getModule('api_config')->getConfig();
		if ($config['api']['auth_type'] === 'oauth2') {
			$oauth2_cfg = $this->getModule('oauth2_config')->getConfig();
			$ids = array_keys($oauth2_cfg);
			$values = [];
			for ($i = 0; $i < count($ids); $i++) {
				$values[] = "{$oauth2_cfg[$ids[$i]]['client_id']} ({$oauth2_cfg[$ids[$i]]['name']})";
			}
		} elseif ($config['api']['auth_type'] === 'basic') {
			$api_user_cfg = $this->getModule('basic_apiuser')->getUsers();
			$values = $ids = array_keys($api_user_cfg);
		}
		$this->AuthParamsCombo->DataSource = array_combine($ids, $values);
		$this->AuthParamsCombo->dataBind();
	}
}
