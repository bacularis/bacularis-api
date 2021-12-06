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


Prado::using('System.Web.UI.ActiveControls.TActiveDropDownList');
Prado::using('System.Web.UI.ActiveControls.TActiveTextBox');
Prado::using('System.Web.UI.ActiveControls.TActivePanel');
Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActiveButton');
Prado::using('System.Web.UI.ActiveControls.TActiveRadioButton');
Prado::using('System.Web.UI.ActiveControls.TActiveCustomValidator');
Prado::using('Application.Common.Class.OAuth2');
Prado::using('Application.API.Class.APIConfig');
Prado::using('Application.API.Class.BAPIException');
Prado::using('Application.API.Class.BaculumAPIPage');
Prado::using('Application.API.Class.Database');
Prado::using('Application.API.Class.BasicAPIUserConfig');

/**
 * API install wizard.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Panel
 * @package Baculum API
 */
class APIInstallWizard extends BaculumAPIPage {

	public $first_run;
	public $add_auth_params = false;
	public $config;

	const DEFAULT_DB_NAME = 'bacula';
	const DEFAULT_DB_LOGIN = 'bacula';
	const DEFAULT_BCONSOLE_BIN = '/usr/sbin/bconsole';
	const DEFAULT_BCONSOLE_CONF = '/etc/bacula/bconsole.conf';
	const DEFAULT_BDIRJSON_BIN = '/usr/sbin/bdirjson';
	const DEFAULT_DIR_CONF = '/etc/bacula/bacula-dir.conf';
	const DEFAULT_BSDJSON_BIN = '/usr/sbin/bsdjson';
	const DEFAULT_SD_CONF = '/etc/bacula/bacula-sd.conf';
	const DEFAULT_BFDJSON_BIN = '/usr/sbin/bfdjson';
	const DEFAULT_FD_CONF = '/etc/bacula/bacula-fd.conf';
	const DEFAULT_BBCONJSON_BIN = '/usr/sbin/bbconsjson';

	const DEFAULT_ACTION_DIR_START = '/usr/bin/systemctl start bacula-dir';
	const DEFAULT_ACTION_DIR_STOP = '/usr/bin/systemctl stop bacula-dir';
	const DEFAULT_ACTION_DIR_RESTART = '/usr/bin/systemctl restart bacula-dir';
	const DEFAULT_ACTION_SD_START = '/usr/bin/systemctl start bacula-sd';
	const DEFAULT_ACTION_SD_STOP = '/usr/bin/systemctl stop bacula-sd';
	const DEFAULT_ACTION_SD_RESTART = '/usr/bin/systemctl restart bacula-sd';
	const DEFAULT_ACTION_FD_START = '/usr/bin/systemctl start bacula-fd';
	const DEFAULT_ACTION_FD_STOP = '/usr/bin/systemctl stop bacula-fd';
	const DEFAULT_ACTION_FD_RESTART = '/usr/bin/systemctl restart bacula-fd';

	public function onPreInit($param) {
		parent::onPreInit($param);
		if (isset($_SESSION['language'])) {
			$this->Application->getGlobalization()->Culture = $_SESSION['language'];
		}
	}

	public function onInit($param) {
		parent::onInit($param);
		$config = $this->getModule('api_config');
		$this->config = $config->getConfig();
		$this->first_run = (count($this->config) === 0);
		$oauth2_cfg = $this->getModule('oauth2_config')->getConfig();
		$this->add_auth_params = (count($oauth2_cfg) === 0);
		if (isset($_SESSION['language'])) {
			$this->Lang->SelectedValue = $_SESSION['language'];
		} elseif (!$this->first_run && isset($this->config['api']['lang'])) {
			$this->Lang->SelectedValue = $this->config['api']['lang'];
		}
	}

	public function onLoad($param) {
		parent::onLoad($param);
		$this->Port->setViewState('port', $this->Port->Text);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}

		if ($this->first_run === true) {
			$this->DBName->Text = self::DEFAULT_DB_NAME;
			$this->Login->Text = self::DEFAULT_DB_LOGIN;
			$this->BconsolePath->Text = self::DEFAULT_BCONSOLE_BIN;
			$this->BconsoleConfigPath->Text = self::DEFAULT_BCONSOLE_CONF;
			$this->BDirJSONPath->Text = self::DEFAULT_BDIRJSON_BIN;
			$this->DirCfgPath->Text = self::DEFAULT_DIR_CONF;
			$this->BSdJSONPath->Text = self::DEFAULT_BSDJSON_BIN;
			$this->SdCfgPath->Text = self::DEFAULT_SD_CONF;
			$this->BFdJSONPath->Text = self::DEFAULT_BFDJSON_BIN;
			$this->FdCfgPath->Text = self::DEFAULT_FD_CONF;
			$this->BBconsJSONPath->Text = self::DEFAULT_BBCONJSON_BIN;
			$this->BconsCfgPath->Text = self::DEFAULT_BCONSOLE_CONF;

			$this->DatabaseNo->Checked = true;
			$this->ConsoleNo->Checked = true;
			$this->ConfigNo->Checked = true;
		} else {
			// Database param settings
			if ($this->config['db']['enabled'] == 1) {
				$this->DatabaseYes->Checked = true;
				$this->DatabaseNo->Checked = false;
			} else {
				$this->DatabaseYes->Checked = false;
				$this->DatabaseNo->Checked = true;
			}
			$this->DBType->SelectedValue = $this->config['db']['type'];
			$this->DBName->Text = $this->config['db']['name'];
			$this->Login->Text = $this->config['db']['login'];
			$this->Password->Text = $this->config['db']['password'];
			$this->IP->Text = $this->config['db']['ip_addr'];
			$this->Port->Text = $this->config['db']['port'];
			$this->Port->setViewState('port', $this->config['db']['port']);
			$this->DBPath->Text = $this->config['db']['path'];

			// Bconsole param settings
			if ($this->config['bconsole']['enabled'] == 1) {
				$this->ConsoleYes->Checked = true;
				$this->ConsoleNo->Checked = false;
			} else {
				$this->ConsoleYes->Checked = false;
				$this->ConsoleNo->Checked = true;
			}
			$this->BconsolePath->Text = $this->config['bconsole']['bin_path'];
			$this->BconsoleConfigPath->Text = $this->config['bconsole']['cfg_path'];
			$this->UseSudo->Checked = $this->getPage()->config['bconsole']['use_sudo'] == 1;

			$api_config = $this->getModule('api_config');

			// JSONTools param settings
			if ($api_config->isJSONToolsEnabled() === true) {
				$this->ConfigYes->Checked = true;
				$this->ConfigNo->Checked = false;
			} else {
				$this->ConfigYes->Checked = false;
				$this->ConfigNo->Checked = true;
			}
			$this->BConfigDir->Text = $this->config['jsontools']['bconfig_dir'];
			$this->BJSONUseSudo->Checked = ($this->config['jsontools']['use_sudo'] == 1);
			$this->BDirJSONPath->Text = $this->config['jsontools']['bdirjson_path'];
			$this->DirCfgPath->Text = $this->config['jsontools']['dir_cfg_path'];
			$this->BSdJSONPath->Text = $this->config['jsontools']['bsdjson_path'];
			$this->SdCfgPath->Text = $this->config['jsontools']['sd_cfg_path'];
			$this->BFdJSONPath->Text = $this->config['jsontools']['bfdjson_path'];
			$this->FdCfgPath->Text = $this->config['jsontools']['fd_cfg_path'];
			$this->BBconsJSONPath->Text = $this->config['jsontools']['bbconsjson_path'];
			$this->BconsCfgPath->Text = $this->config['jsontools']['bcons_cfg_path'];

			if ($this->config['api']['auth_type'] === 'basic') {
				// API basic auth data
				$this->AuthBasic->Checked = true;
				$this->AuthOAuth2->Checked = false;
			} elseif ($this->config['api']['auth_type'] === 'oauth2') {
				// API oauth2 auth data
				$this->AuthBasic->Checked = false;
				$this->AuthOAuth2->Checked = true;
			}
		}
	}

	public function NextStep($sender, $param) {
	}

	public function PreviousStep($sender, $param) {
	}

	public function wizardStop($sender, $param) {
		$this->goToDefaultPage();
	}

	public function wizardCompleted($sender, $param) {
		$cfg_data = array(
			'api' => array(),
			'db' => array(),
			'bconsole' => array(),
			'jsontools' => array()
		);
		if ($this->AuthBasic->Checked) {
			$cfg_data['api']['auth_type'] =  'basic';
		} elseif($this->AuthOAuth2->Checked) {
			$cfg_data['api']['auth_type'] =  'oauth2';
		}
		$cfg_data['api']['debug'] = isset($this->config['api']['debug']) ? $this->config['api']['debug'] : "0";
		$cfg_data['api']['lang'] = isset($_SESSION['language']) ? $_SESSION['language'] : APIConfig::DEF_LANG;
		$cfg_data['db']['enabled'] = (integer)($this->DatabaseYes->Checked === true);
		$cfg_data['db']['type'] = $this->DBType->SelectedValue;
		$cfg_data['db']['name'] = $this->DBName->Text;
		$cfg_data['db']['login'] = $this->Login->Text;
		$cfg_data['db']['password'] = $this->Password->Text;
		$cfg_data['db']['ip_addr'] = $this->IP->Text;
		$cfg_data['db']['port'] = $this->Port->Text;
		$cfg_data['db']['path'] = $this->isSQLiteType($cfg_data['db']['type']) ? $this->DBPath->Text : '';
		$cfg_data['bconsole']['enabled'] = (integer)($this->ConsoleYes->Checked === true);
		$cfg_data['bconsole']['bin_path'] = $this->BconsolePath->Text;
		$cfg_data['bconsole']['cfg_path'] = $this->BconsoleConfigPath->Text;
		$cfg_data['bconsole']['use_sudo'] = (integer)($this->UseSudo->Checked === true);
		$cfg_data['jsontools']['enabled'] = (integer)($this->ConfigYes->Checked === true);
		$cfg_data['jsontools']['use_sudo'] = (integer)($this->BJSONUseSudo->Checked === true);
		$cfg_data['jsontools']['bconfig_dir'] = $this->BConfigDir->Text;
		$cfg_data['jsontools']['bdirjson_path'] = $this->BDirJSONPath->Text;
		$cfg_data['jsontools']['dir_cfg_path'] = $this->DirCfgPath->Text;
		$cfg_data['jsontools']['bsdjson_path'] = $this->BSdJSONPath->Text;
		$cfg_data['jsontools']['sd_cfg_path'] = $this->SdCfgPath->Text;
		$cfg_data['jsontools']['bfdjson_path'] = $this->BFdJSONPath->Text;
		$cfg_data['jsontools']['fd_cfg_path'] = $this->FdCfgPath->Text;
		$cfg_data['jsontools']['bbconsjson_path'] = $this->BBconsJSONPath->Text;
		$cfg_data['jsontools']['bcons_cfg_path'] = $this->BconsCfgPath->Text;

		$ret = $this->getModule('api_config')->setConfig($cfg_data);
		if ($ret) {
			if ($this->first_run && $this->AuthBasic->Checked && $this->getModule('basic_apiuser')->isUsersConfig()) {
				// save basic auth user only on first run
				$this->getModule('basic_apiuser')->setUsersConfig(
					$this->APILogin->Text,
					$this->APIPassword->Text,
					true,
					$_SERVER['PHP_AUTH_USER']
				);
			}
			if (($this->first_run || $this->add_auth_params) && $this->AuthOAuth2->Checked) {
				// save OAuth2 auth user on first run or when no OAuth2 client defined
				$oauth2_cfg = $this->getModule('oauth2_config')->getConfig();
				$oauth2_cfg[$this->APIOAuth2ClientId->Text] = array();
				$oauth2_cfg[$this->APIOAuth2ClientId->Text]['client_id'] = $this->APIOAuth2ClientId->Text;
				$oauth2_cfg[$this->APIOAuth2ClientId->Text]['client_secret'] = $this->APIOAuth2ClientSecret->Text;
				$oauth2_cfg[$this->APIOAuth2ClientId->Text]['redirect_uri'] = $this->APIOAuth2RedirectURI->Text;
				$oauth2_cfg[$this->APIOAuth2ClientId->Text]['scope'] = $this->APIOAuth2Scope->Text;
				$oauth2_cfg[$this->APIOAuth2ClientId->Text]['bconsole_cfg_path'] = $this->APIOAuth2BconsoleCfgPath->Text;
				$oauth2_cfg[$this->APIOAuth2ClientId->Text]['name'] = $this->APIOAuth2Name->Text;
				$this->getModule('oauth2_config')->setConfig($oauth2_cfg);
			}
			$this->goToDefaultPage();
		}

	}

	// @TODO: Remove it. It is templates work, not page work
	public function getDbNameByType($type) {
		$db_name = null;
		switch ($type) {
			case Database::PGSQL_TYPE: $db_name = 'PostgreSQL'; break;
			case Database::MYSQL_TYPE: $db_name = 'MySQL'; break;
			case Database::SQLITE_TYPE: $db_name = 'SQLite'; break;
		}
		return $db_name;
	}

	// @TODO: Remove it and check SQLite prettier and not here
	public function isSQLiteType($type) {
		return ($type === Database::SQLITE_TYPE);
	}

	public function setDBType($sender, $param) {
		$db = $this->DBType->SelectedValue;
		$this->setLogin($db);
		$this->setPassword($db);
		$this->setIP($db);
		$this->setDefaultPort($db);
		$this->setDBPath($db);
	}

	public function setLogin($db) {
		$this->Login->Enabled = ($this->isSQLiteType($db) === false);
	}

	public function setPassword($db) {
		$this->Password->Enabled = ($this->isSQLiteType($db) === false);
	}

	public function setIP($db) {
		$this->IP->Enabled = ($this->isSQLiteType($db) === false);
	}

	public function setDefaultPort($db) {
		$port = null;
		if(Database::PGSQL_TYPE === $db) {
			$port = 5432;
		} elseif(Database::MYSQL_TYPE === $db) {
			$port = 3306;
		} elseif(Database::SQLITE_TYPE === $db) {
			$port = null;
		}

		$prevPort = $this->Port->getViewState('port');

		if(is_null($port)) {
			$this->Port->Text = '';
			$this->Port->Enabled = false;
		} else {
			$this->Port->Enabled = true;
			$this->Port->Text = (empty($prevPort)) ? $port : $prevPort;
		}
		$this->Port->setViewState('port', '');
	}

	public function setDBPath($db) {
		if($this->isSQLiteType($db) === true) {
			$this->DBPath->Enabled = true;
			$this->DBPathField->Display = 'Fixed';
		} else {
			$this->DBPath->Enabled = false;
			$this->DBPathField->Display = 'Hidden';
		}
	}

	public function setLang($sender, $param) {
		$_SESSION['language'] = $sender->SelectedValue;
	}

	public function renderPanel($sender, $param) {
		$this->LoginValidator->Display = ($this->Login->Enabled === true) ? 'Dynamic' : 'None';
		$this->PortValidator->Display = ($this->Port->Enabled === true) ? 'Dynamic' : 'None';
		$this->IPValidator->Display = ($this->IP->Enabled === true) ? 'Dynamic' : 'None';
		$this->DBPathValidator->Display = ($this->DBPath->Enabled === true) ? 'Dynamic' : 'None';
		$this->DbTestResultErr->Display = 'None';
		$this->Step2Content->render($param->NewWriter);
	}

	public function connectionDBTest($sender, $param) {
		$validation = false;
		$db_params = array();
		$db_params['type'] = $this->DBType->SelectedValue;
		if($db_params['type'] === Database::MYSQL_TYPE || $db_params['type'] === Database::PGSQL_TYPE) {
			$db_params['name'] = $this->DBName->Text;
			$db_params['login'] = $this->Login->Text;
			$db_params['password'] = $this->Password->Text;
			$db_params['ip_addr'] = $this->IP->Text;
			$db_params['port'] = $this->Port->Text;
			$validation = true;
		} elseif($db_params['type'] === Database::SQLITE_TYPE && !empty($this->DBPath->Text)) {
			$db_params['path'] = $this->DBPath->Text;
			$validation = true;
		}

		$is_validate = false;
		$emsg = '';
		if ($validation === true) {
			try {
				$is_validate = $this->getModule('db')->testDbConnection($db_params);
			} catch (BAPIException $e) {
				$emsg = $e->getErrorMessage();
			}
		}
		if (!empty($emsg)) {
			$this->DbTestResultErr->Text = $emsg;
		}
		if ($is_validate === true) {
			$this->getCallbackClient()->show('db_test_result_ok');
			$this->getCallbackClient()->hide('db_test_result_err');
			$this->getCallbackClient()->hide($this->DbTestResultErr);
		} else {
			$this->getCallbackClient()->hide('db_test_result_ok');
			$this->getCallbackClient()->show('db_test_result_err');
			$this->getCallbackClient()->show($this->DbTestResultErr);
		}
	}

	public function connectionBconsoleTest($sender, $param) {
		$emsg = '';
		$result = $this->getModule('bconsole')->testBconsoleCommand(
			array('version'),
			$this->BconsolePath->Text,
			$this->BconsoleConfigPath->Text,
			$this->UseSudo->Checked
		);
		$is_validate = ($result->exitcode === 0);
		if (!$is_validate) {
			$this->BconsoleTestResultErr->Text = $result->output;
		}
		if ($is_validate === true) {
			$this->getCallbackClient()->show('bconsole_test_result_ok');
			$this->getCallbackClient()->hide('bconsole_test_result_err');
			$this->getCallbackClient()->hide($this->BconsoleTestResultErr);
		} else {
			$this->getCallbackClient()->hide('bconsole_test_result_ok');
			$this->getCallbackClient()->show('bconsole_test_result_err');
			$this->getCallbackClient()->show($this->BconsoleTestResultErr);
		}
	}

	public function testJSONToolsCfg($sender, $param) {
		$jsontools = array(
			'dir' => array(
				'path' => $this->BDirJSONPath->Text,
				'cfg' => $this->DirCfgPath->Text,
				'ok_el' => $this->BDirJSONPathTestOk,
				'error_el' => $this->BDirJSONPathTestErr
			),
			'sd' => array(
				'path' => $this->BSdJSONPath->Text,
				'cfg' => $this->SdCfgPath->Text,
				'ok_el' => $this->BSdJSONPathTestOk,
				'error_el' => $this->BSdJSONPathTestErr
			),
			'fd' => array(
				'path' => $this->BFdJSONPath->Text,
				'cfg' => $this->FdCfgPath->Text,
				'ok_el' => $this->BFdJSONPathTestOk,
				'error_el' => $this->BFdJSONPathTestErr
			),
			'bcons' => array(
				'path' => $this->BBconsJSONPath->Text,
				'cfg' => $this->BconsCfgPath->Text,
				'ok_el' => $this->BBconsJSONPathTestOk,
				'error_el' => $this->BBconsJSONPathTestErr
			)
		);
		$use_sudo = $this->BJSONUseSudo->Checked;

		foreach ($jsontools as $type => $config) {
			$config['ok_el']->Display = 'None';
			$config['error_el']->Display = 'None';
			if (!empty($config['path']) && !empty($config['cfg'])) {

				$result = (object)$this->getModule('json_tools')->testJSONTool($config['path'], $config['cfg'], $use_sudo);
				if ($result->exitcode === 0) {
					// test passed
					$config['ok_el']->Display = 'Dynamic';
				} else {
					// test failed
					$config['error_el']->Text = implode("\n", $result->output);
					$config['error_el']->Display = 'Dynamic';
				}
			}
		}
	}

	public function testConfigDir($sender, $param) {
		$valid = is_writeable($this->BConfigDir->Text);
		$this->BConfigDirTestOk->Display = 'None';
		$this->BConfigDirTestErr->Display = 'None';
		$this->BConfigDirWritableTest->Display = 'None';
		if ($valid === true) {
			$this->BConfigDirTestOk->Display = 'Dynamic';
		} else {
			$this->BConfigDirWritableTest->Display = 'Dynamic';
			$this->BConfigDirTestErr->Display = 'Dynamic';
		}
		$param->setIsValid($valid);
	}
}
?>
