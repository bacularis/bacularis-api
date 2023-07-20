<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2023 Marcin Haba
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

use Bacularis\Common\Modules\AuthBasic;
use Bacularis\Common\Modules\AuthOAuth2;
use Bacularis\API\Modules\BaculumAPIPage;
use Bacularis\API\Modules\BAPIException;
use Bacularis\API\Modules\Database;

/**
 * API settings page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Panel
 */
class APISettings extends BaculumAPIPage
{
	public $config;

	public const DEFAULT_ACTION_DIR_START = '/usr/bin/systemctl start bacula-dir';
	public const DEFAULT_ACTION_DIR_STOP = '/usr/bin/systemctl stop bacula-dir';
	public const DEFAULT_ACTION_DIR_RESTART = '/usr/bin/systemctl restart bacula-dir';
	public const DEFAULT_ACTION_SD_START = '/usr/bin/systemctl start bacula-sd';
	public const DEFAULT_ACTION_SD_STOP = '/usr/bin/systemctl stop bacula-sd';
	public const DEFAULT_ACTION_SD_RESTART = '/usr/bin/systemctl restart bacula-sd';
	public const DEFAULT_ACTION_FD_START = '/usr/bin/systemctl start bacula-fd';
	public const DEFAULT_ACTION_FD_STOP = '/usr/bin/systemctl stop bacula-fd';
	public const DEFAULT_ACTION_FD_RESTART = '/usr/bin/systemctl restart bacula-fd';

	public const DEFAULT_SM_RPM_DIR_INSTALL = '/usr/bin/dnf -y install bacula-director';
	public const DEFAULT_SM_RPM_DIR_UPGRADE = '/usr/bin/dnf -y update bacula-director';
	public const DEFAULT_SM_RPM_DIR_REMOVE = '/usr/bin/dnf -y remove bacula-director';
	public const DEFAULT_SM_RPM_DIR_INFO = '/usr/bin/dnf list installed bacula-director';
	public const DEFAULT_SM_RPM_DIR_ENABLE = '/usr/bin/systemctl enable bacula-dir';
	public const DEFAULT_SM_RPM_SD_INSTALL = '/usr/bin/dnf -y install bacula-storage';
	public const DEFAULT_SM_RPM_SD_UPGRADE = '/usr/bin/dnf -y update bacula-storage';
	public const DEFAULT_SM_RPM_SD_REMOVE = '/usr/bin/dnf -y remove bacula-storage';
	public const DEFAULT_SM_RPM_SD_INFO = '/usr/bin/dnf list installed bacula-storage';
	public const DEFAULT_SM_RPM_SD_ENABLE = '/usr/bin/systemctl enable bacula-sd';
	public const DEFAULT_SM_RPM_FD_INSTALL = '/usr/bin/dnf -y install bacula-client';
	public const DEFAULT_SM_RPM_FD_UPGRADE = '/usr/bin/dnf -y update bacula-client';
	public const DEFAULT_SM_RPM_FD_REMOVE = '/usr/bin/dnf -y remove bacula-client';
	public const DEFAULT_SM_RPM_FD_INFO = '/usr/bin/dnf list installed bacula-client';
	public const DEFAULT_SM_RPM_FD_ENABLE = '/usr/bin/systemctl enable bacula-sd';
	public const DEFAULT_SM_RPM_BCONS_INSTALL = '/usr/bin/dnf -y install bacula-console';
	public const DEFAULT_SM_RPM_BCONS_UPGRADE = '/usr/bin/dnf -y update bacula-console';
	public const DEFAULT_SM_RPM_BCONS_REMOVE = '/usr/bin/dnf -y remove bacula-console';
	public const DEFAULT_SM_RPM_BCONS_INFO = '/usr/bin/dnf -y remove bacula-console';

	public const DEFAULT_SM_DEB_DIR_INSTALL = '/usr/bin/apt -y install bacula-director';
	public const DEFAULT_SM_DEB_DIR_UPGRADE = '/usr/bin/apt -y install --only-upgrade bacula-director';
	public const DEFAULT_SM_DEB_DIR_REMOVE = '/usr/bin/apt -y remove --purge bacula-director';
	public const DEFAULT_SM_DEB_DIR_INFO = '/usr/bin/dpkg -l bacula-director';
	public const DEFAULT_SM_DEB_DIR_ENABLE = '/usr/bin/systemctl enable bacula-dir';
	public const DEFAULT_SM_DEB_SD_INSTALL = '/usr/bin/apt -y install bacula-sd';
	public const DEFAULT_SM_DEB_SD_UPGRADE = '/usr/bin/apt -y install --only-upgrade bacula-sd';
	public const DEFAULT_SM_DEB_SD_REMOVE = '/usr/bin/apt -y remove --purge bacula-sd';
	public const DEFAULT_SM_DEB_SD_INFO = '/usr/bin/dpkg -l bacula-sd';
	public const DEFAULT_SM_DEB_SD_ENABLE = '/usr/bin/systemctl enable bacula-sd';
	public const DEFAULT_SM_DEB_FD_INSTALL = '/usr/bin/apt -y install bacula-client';
	public const DEFAULT_SM_DEB_FD_UPGRADE = '/usr/bin/apt -y install --only-upgrade bacula-client';
	public const DEFAULT_SM_DEB_FD_REMOVE = '/usr/bin/apt -y remove --purge bacula-client';
	public const DEFAULT_SM_DEB_FD_INFO = '/usr/bin/dpkg -l bacula-client';
	public const DEFAULT_SM_DEB_FD_ENABLE = '/usr/bin/systemctl enable bacula-fd';
	public const DEFAULT_SM_DEB_BCONS_INSTALL = '/usr/bin/apt -y install bacula-console';
	public const DEFAULT_SM_DEB_BCONS_UPGRADE = '/usr/bin/apt -y install --only-upgrade bacula-console';
	public const DEFAULT_SM_DEB_BCONS_REMOVE = '/usr/bin/apt -y remove --purge bacula-console';
	public const DEFAULT_SM_DEB_BCONS_INFO = '/usr/bin/dpkg -l bacula-console';

	public const DEFAULT_SM_RPM_POST_INSTALL = '/usr/share/bacularis/protected/tools/set_bacula_perm.sh /etc/bacula apache';
	public const DEFAULT_SM_DEB_POST_INSTALL = '/usr/share/bacularis/protected/tools/set_bacula_perm.sh /etc/bacula www-data';

	public function onInit($param)
	{
		parent::onInit($param);
		$config = $this->getModule('api_config');
		$this->config = $config->getConfig();
		$this->loadGeneralSettings();
		$this->loadDbSettings();
		$this->loadBconsoleSettings();
		$this->loadConfigSettings();
		$this->loadActionsSettings();
		$this->loadAuthSettings();
		$this->loadSoftwareManagementSettings();
	}

	private function loadGeneralSettings()
	{
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->GeneralLang->SelectedValue = $this->config['api']['lang'];
		$this->GeneralDebug->Checked = ($this->config['api']['debug'] == 1);
	}

	private function loadDbSettings()
	{
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->DBEnabled->Checked = ($this->config['db']['enabled'] == 1);
		$this->DBType->SelectedValue = $this->config['db']['type'];
		$this->DBName->Text = $this->config['db']['name'];
		$this->DBLogin->Text = $this->config['db']['login'];
		$this->DBPassword->Text = $this->config['db']['password'];
		$this->DBAddress->Text = $this->config['db']['ip_addr'];
		$this->DBPort->Text = $this->config['db']['port'];
		$this->DBPath->Text = $this->config['db']['path'];
		$this->setDBType(null, null);
	}

	private function loadBconsoleSettings()
	{
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->BconsoleEnabled->Checked = ($this->config['bconsole']['enabled'] == 1);
		$this->BconsolePath->Text = $this->config['bconsole']['bin_path'];
		$this->BconsoleConfigPath->Text = $this->config['bconsole']['cfg_path'];
		$this->BconsoleUseSudo->Checked = ($this->config['bconsole']['use_sudo'] == 1);
	}

	private function loadConfigSettings()
	{
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->ConfigEnabled->Checked = ($this->config['jsontools']['enabled'] == 1);
		if (key_exists('use_sudo', $this->config['jsontools'])) {
			$this->BJSONUseSudo->Checked = $this->config['jsontools']['use_sudo'];
		}
		if (key_exists('bconfig_dir', $this->config['jsontools'])) {
			$this->BConfigDir->Text = $this->config['jsontools']['bconfig_dir'];
		}
		if (key_exists('bdirjson_path', $this->config['jsontools'])) {
			$this->BDirJSONPath->Text = $this->config['jsontools']['bdirjson_path'];
		}
		if (key_exists('dir_cfg_path', $this->config['jsontools'])) {
			$this->DirCfgPath->Text = $this->config['jsontools']['dir_cfg_path'];
		}
		if (key_exists('bsdjson_path', $this->config['jsontools'])) {
			$this->BSdJSONPath->Text = $this->config['jsontools']['bsdjson_path'];
		}
		if (key_exists('sd_cfg_path', $this->config['jsontools'])) {
			$this->SdCfgPath->Text = $this->config['jsontools']['sd_cfg_path'];
		}
		if (key_exists('bfdjson_path', $this->config['jsontools'])) {
			$this->BFdJSONPath->Text = $this->config['jsontools']['bfdjson_path'];
		}
		if (key_exists('fd_cfg_path', $this->config['jsontools'])) {
			$this->FdCfgPath->Text = $this->config['jsontools']['fd_cfg_path'];
		}
		if (key_exists('bbconsjson_path', $this->config['jsontools'])) {
			$this->BBconsJSONPath->Text = $this->config['jsontools']['bbconsjson_path'];
		}
		if (key_exists('bcons_cfg_path', $this->config['jsontools'])) {
			$this->BconsCfgPath->Text = $this->config['jsontools']['bcons_cfg_path'];
		}
	}

	private function loadActionsSettings()
	{
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		if (!key_exists('actions', $this->config)) {
			$this->DirStartAction->Text = self::DEFAULT_ACTION_DIR_START;
			$this->DirStopAction->Text = self::DEFAULT_ACTION_DIR_STOP;
			$this->DirRestartAction->Text = self::DEFAULT_ACTION_DIR_RESTART;
			$this->SdStartAction->Text = self::DEFAULT_ACTION_SD_START;
			$this->SdStopAction->Text = self::DEFAULT_ACTION_SD_STOP;
			$this->SdRestartAction->Text = self::DEFAULT_ACTION_SD_RESTART;
			$this->FdStartAction->Text = self::DEFAULT_ACTION_FD_START;
			$this->FdStopAction->Text = self::DEFAULT_ACTION_FD_STOP;
			$this->FdRestartAction->Text = self::DEFAULT_ACTION_FD_RESTART;
			return;
		}

		if (key_exists('enabled', $this->config['actions'])) {
			$this->ActionsEnabled->Checked = ($this->config['actions']['enabled'] == 1);
		}
		if (key_exists('use_sudo', $this->config['actions'])) {
			$this->ActionsUseSudo->Checked = $this->config['actions']['use_sudo'];
		}
		if (key_exists('dir_start', $this->config['actions'])) {
			$this->DirStartAction->Text = $this->config['actions']['dir_start'];
		}
		if (key_exists('dir_stop', $this->config['actions'])) {
			$this->DirStopAction->Text = $this->config['actions']['dir_stop'];
		}
		if (key_exists('dir_restart', $this->config['actions'])) {
			$this->DirRestartAction->Text = $this->config['actions']['dir_restart'];
		}
		if (key_exists('sd_start', $this->config['actions'])) {
			$this->SdStartAction->Text = $this->config['actions']['sd_start'];
		}
		if (key_exists('sd_stop', $this->config['actions'])) {
			$this->SdStopAction->Text = $this->config['actions']['sd_stop'];
		}
		if (key_exists('sd_restart', $this->config['actions'])) {
			$this->SdRestartAction->Text = $this->config['actions']['sd_restart'];
		}
		if (key_exists('fd_start', $this->config['actions'])) {
			$this->FdStartAction->Text = $this->config['actions']['fd_start'];
		}
		if (key_exists('fd_stop', $this->config['actions'])) {
			$this->FdStopAction->Text = $this->config['actions']['fd_stop'];
		}
		if (key_exists('fd_restart', $this->config['actions'])) {
			$this->FdRestartAction->Text = $this->config['actions']['fd_restart'];
		}
	}

	private function loadAuthSettings()
	{
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		if ($this->config['api']['auth_type'] === AuthBasic::NAME) {
			$this->AuthBasic->Checked = true;
		} elseif ($this->config['api']['auth_type'] === AuthOAuth2::NAME) {
			$this->AuthOAuth2->Checked = true;
		}
	}

	private function loadSoftwareManagementSettings()
	{
		if ($this->IsPostBack || $this->IsCallBack || !isset($this->config['software_management'])) {
			return;
		}

		$smcfg = $this->config['software_management'];
		$this->SoftwareManagementEnabled->Checked = $smcfg['enabled'] == 1;
		$this->SoftwareManagementUseSudo->Checked = $smcfg['use_sudo'] == 1;
		$this->DirInstallCmd->Text = $smcfg['dir_install'];
		$this->DirUpgradeCmd->Text = $smcfg['dir_upgrade'];
		$this->DirRemoveCmd->Text = $smcfg['dir_remove'];
		$this->DirInfoCmd->Text = $smcfg['dir_info'];
		$this->DirEnableCmd->Text = $smcfg['dir_enable'];
		$this->DirPreInstallCmd->Text = $smcfg['dir_pre_install_cmd'];
		$this->DirPreUpgradeCmd->Text = $smcfg['dir_pre_upgrade_cmd'];
		$this->DirPreRemoveCmd->Text = $smcfg['dir_pre_remove_cmd'];
		$this->DirPostInstallCmd->Text = $smcfg['dir_post_install_cmd'];
		$this->DirPostUpgradeCmd->Text = $smcfg['dir_post_upgrade_cmd'];
		$this->DirPostRemoveCmd->Text = $smcfg['dir_post_remove_cmd'];
		$this->SdInstallCmd->Text = $smcfg['sd_install'];
		$this->SdUpgradeCmd->Text = $smcfg['sd_upgrade'];
		$this->SdRemoveCmd->Text = $smcfg['sd_remove'];
		$this->SdInfoCmd->Text = $smcfg['sd_info'];
		$this->SdEnableCmd->Text = $smcfg['sd_enable'];
		$this->SdPreInstallCmd->Text = $smcfg['sd_pre_install_cmd'];
		$this->SdPreUpgradeCmd->Text = $smcfg['sd_pre_upgrade_cmd'];
		$this->SdPreRemoveCmd->Text = $smcfg['sd_pre_remove_cmd'];
		$this->SdPostInstallCmd->Text = $smcfg['sd_post_install_cmd'];
		$this->SdPostUpgradeCmd->Text = $smcfg['sd_post_upgrade_cmd'];
		$this->SdPostRemoveCmd->Text = $smcfg['sd_post_remove_cmd'];
		$this->FdInstallCmd->Text = $smcfg['fd_install'];
		$this->FdUpgradeCmd->Text = $smcfg['fd_upgrade'];
		$this->FdRemoveCmd->Text = $smcfg['fd_remove'];
		$this->FdInfoCmd->Text = $smcfg['fd_info'];
		$this->FdEnableCmd->Text = $smcfg['fd_enable'];
		$this->FdPreInstallCmd->Text = $smcfg['fd_pre_install_cmd'];
		$this->FdPreUpgradeCmd->Text = $smcfg['fd_pre_upgrade_cmd'];
		$this->FdPreRemoveCmd->Text = $smcfg['fd_pre_remove_cmd'];
		$this->FdPostInstallCmd->Text = $smcfg['fd_post_install_cmd'];
		$this->FdPostUpgradeCmd->Text = $smcfg['fd_post_upgrade_cmd'];
		$this->FdPostRemoveCmd->Text = $smcfg['fd_post_remove_cmd'];
		$this->BconsInstallCmd->Text = $smcfg['bcons_install'];
		$this->BconsUpgradeCmd->Text = $smcfg['bcons_upgrade'];
		$this->BconsRemoveCmd->Text = $smcfg['bcons_remove'];
		$this->BconsInfoCmd->Text = $smcfg['bcons_info'];
		$this->BconsPreInstallCmd->Text = $smcfg['bcons_pre_install_cmd'];
		$this->BconsPreUpgradeCmd->Text = $smcfg['bcons_pre_upgrade_cmd'];
		$this->BconsPreRemoveCmd->Text = $smcfg['bcons_pre_remove_cmd'];
		$this->BconsPostInstallCmd->Text = $smcfg['bcons_post_install_cmd'];
		$this->BconsPostUpgradeCmd->Text = $smcfg['bcons_post_upgrade_cmd'];
		$this->BconsPostRemoveCmd->Text = $smcfg['bcons_post_remove_cmd'];
	}

	public function setDBType($sender, $param)
	{
		$db = $this->DBType->SelectedValue;
		$this->setDBLogin($db);
		$this->setDBPassword($db);
		$this->setDBAddress($db);
		$this->setDBPort($db);
		$this->setDBPath($db);
	}

	public function setDBLogin($db)
	{
		$this->DBLogin->Enabled = ($db !== Database::SQLITE_TYPE);
	}

	public function setDBPassword($db)
	{
		$this->DBPassword->Enabled = ($db !== Database::SQLITE_TYPE);
	}

	public function setDBAddress($db)
	{
		$this->DBAddress->Enabled = ($db !== Database::SQLITE_TYPE);
	}

	public function setDBPort($db)
	{
		$port = null;
		if (Database::PGSQL_TYPE === $db) {
			$port = 5432;
		} elseif (Database::MYSQL_TYPE === $db) {
			$port = 3306;
		} elseif (Database::SQLITE_TYPE === $db) {
			$port = null;
		}

		$prevPort = $this->DBPort->getViewState('port');

		if (is_null($port)) {
			$this->DBPort->Text = '';
			$this->DBPort->Enabled = false;
		} else {
			$this->DBPort->Enabled = true;
			$this->DBPort->Text = (empty($prevPort)) ? $port : $prevPort;
		}
		$this->DBPort->setViewState('port', '');
	}

	public function setDBPath($db)
	{
		if ($db === Database::SQLITE_TYPE) {
			$this->DBPath->Enabled = true;
			$this->DBPathField->Display = 'Fixed';
		} else {
			$this->DBPath->Enabled = false;
			$this->DBPathField->Display = 'Hidden';
		}
	}

	public function connectionDBTest($sender, $param)
	{
		$validation = false;
		$db_params = [];
		$db_params['type'] = $this->DBType->SelectedValue;
		if ($db_params['type'] === Database::MYSQL_TYPE || $db_params['type'] === Database::PGSQL_TYPE) {
			$db_params['name'] = $this->DBName->Text;
			$db_params['login'] = $this->DBLogin->Text;
			$db_params['password'] = $this->DBPassword->Text;
			$db_params['ip_addr'] = $this->DBAddress->Text;
			$db_params['port'] = $this->DBPort->Text;
			$validation = true;
		} elseif ($db_params['type'] === Database::SQLITE_TYPE && !empty($this->DBPath->Text)) {
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

	public function connectionBconsoleTest($sender, $param)
	{
		$result = $this->getModule('bconsole')->testBconsoleCommand(
			['version'],
			$this->BconsolePath->Text,
			$this->BconsoleConfigPath->Text,
			$this->BconsoleUseSudo->Checked
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

	public function testJSONToolsCfg($sender, $param)
	{
		$jsontools = [
			'dir' => [
				'path' => $this->BDirJSONPath->Text,
				'cfg' => $this->DirCfgPath->Text,
				'ok_el' => $this->BDirJSONPathTestOk,
				'error_el' => $this->BDirJSONPathTestErr
			],
			'sd' => [
				'path' => $this->BSdJSONPath->Text,
				'cfg' => $this->SdCfgPath->Text,
				'ok_el' => $this->BSdJSONPathTestOk,
				'error_el' => $this->BSdJSONPathTestErr
			],
			'fd' => [
				'path' => $this->BFdJSONPath->Text,
				'cfg' => $this->FdCfgPath->Text,
				'ok_el' => $this->BFdJSONPathTestOk,
				'error_el' => $this->BFdJSONPathTestErr
			],
			'bcons' => [
				'path' => $this->BBconsJSONPath->Text,
				'cfg' => $this->BconsCfgPath->Text,
				'ok_el' => $this->BBconsJSONPathTestOk,
				'error_el' => $this->BBconsJSONPathTestErr
			]
		];
		$use_sudo = $this->BJSONUseSudo->Checked;

		foreach ($jsontools as $type => $config) {
			$config['ok_el']->Display = 'None';
			$config['error_el']->Display = 'None';
			if (!empty($config['path']) && !empty($config['cfg'])) {
				$result = (object) $this->getModule('json_tools')->testJSONTool($config['path'], $config['cfg'], $use_sudo);
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

	public function testConfigDir($sender, $param)
	{
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

	public function testExecActionCommand($sender, $param)
	{
		$action = $param->CommandParameter;
		$cmd = '';
		switch ($action) {
			case 'dir_start': $cmd = $this->DirStartAction->Text;
				break;
			case 'dir_stop': $cmd = $this->DirStopAction->Text;
				break;
			case 'dir_restart': $cmd = $this->DirRestartAction->Text;
				break;
			case 'sd_start': $cmd = $this->SdStartAction->Text;
				break;
			case 'sd_stop': $cmd = $this->SdStopAction->Text;
				break;
			case 'sd_restart': $cmd = $this->SdRestartAction->Text;
				break;
			case 'fd_start': $cmd = $this->FdStartAction->Text;
				break;
			case 'fd_stop': $cmd = $this->FdStopAction->Text;
				break;
			case 'fd_restart': $cmd = $this->FdRestartAction->Text;
				break;
		};
		$result = $this->getModule('comp_actions')->execCommand($cmd, $this->ActionsUseSudo->Checked);
		$this->getCallbackClient()->callClientFunction('set_action_command_output', [$action, (array) $result]);
	}

	public function saveGeneral($sender, $param)
	{
		$reload_page = false;
		if ($this->config['api']['lang'] != $this->GeneralLang->SelectedValue) {
			$reload_page = true;
		}
		$this->config['api']['lang'] = $this->GeneralLang->SelectedValue;
		$this->config['api']['debug'] = $this->GeneralDebug->Checked ? 1 : 0;
		$this->getModule('api_config')->setConfig($this->config);
		if ($reload_page) {
			$this->getCallbackClient()->callClientFunction('reload_page_cb', []);
		}
	}

	public function saveCatalog($sender, $param)
	{
		$cfg = [
			'enabled' => ($this->DBEnabled->Checked ? 1 : 0),
			'type' => $this->DBType->SelectedValue,
			'name' => $this->DBName->Text,
			'login' => $this->DBLogin->Text,
			'password' => $this->DBPassword->Text,
			'ip_addr' => $this->DBAddress->Text,
			'port' => $this->DBPort->Text,
			'path' => ($this->DBType->SelectedValue === Database::SQLITE_TYPE) ? $this->DBPath->Text : ''
		];
		$this->config['db'] = $cfg;
		$this->getModule('api_config')->setConfig($this->config);
	}

	public function saveBconsole($sender, $param)
	{
		$cfg = [
			'enabled' => ($this->BconsoleEnabled->Checked ? 1 : 0),
			'bin_path' => $this->BconsolePath->Text,
			'cfg_path' => $this->BconsoleConfigPath->Text,
			'use_sudo' => ($this->BconsoleUseSudo->Checked ? 1 : 0)
		];
		$this->config['bconsole'] = $cfg;
		$this->getModule('api_config')->setConfig($this->config);
	}

	public function saveConfig($sender, $param)
	{
		$cfg = [
			'enabled' => ($this->ConfigEnabled->Checked ? 1 : 0),
			'use_sudo' => ($this->BJSONUseSudo->Checked ? 1 : 0),
			'bconfig_dir' => $this->BConfigDir->Text,
			'bdirjson_path' => $this->BDirJSONPath->Text,
			'dir_cfg_path' => $this->DirCfgPath->Text,
			'bsdjson_path' => $this->BSdJSONPath->Text,
			'sd_cfg_path' => $this->SdCfgPath->Text,
			'bfdjson_path' => $this->BFdJSONPath->Text,
			'fd_cfg_path' => $this->FdCfgPath->Text,
			'bbconsjson_path' => $this->BBconsJSONPath->Text,
			'bcons_cfg_path' => $this->BconsCfgPath->Text
		];
		$this->config['jsontools'] = $cfg;
		$this->getModule('api_config')->setConfig($this->config);
	}

	public function saveActions($sender, $param)
	{
		$cfg = [
			'enabled' => ($this->ActionsEnabled->Checked ? 1 : 0),
			'use_sudo' => ($this->ActionsUseSudo->Checked ? 1 : 0),
			'dir_start' => $this->DirStartAction->Text,
			'dir_stop' => $this->DirStopAction->Text,
			'dir_restart' => $this->DirRestartAction->Text,
			'sd_start' => $this->SdStartAction->Text,
			'sd_stop' => $this->SdStopAction->Text,
			'sd_restart' => $this->SdRestartAction->Text,
			'fd_start' => $this->FdStartAction->Text,
			'fd_stop' => $this->FdStopAction->Text,
			'fd_restart' => $this->FdRestartAction->Text
		];
		$this->config['actions'] = $cfg;
		$this->getModule('api_config')->setConfig($this->config);
	}

	public function saveAuth($sender, $param)
	{
		$auth_type = AuthBasic::NAME;
		if ($this->AuthOAuth2->Checked) {
			$auth_type = AuthOAuth2::NAME;
		} elseif ($this->AuthBasic->Checked) {
			$auth_type = AuthBasic::NAME;
		}
		$this->config['api']['auth_type'] = $auth_type;
		$this->getModule('api_config')->setConfig($this->config);
	}

	public function setSoftwareManagementDefaultValues($sender, $param)
	{
		$os_type = $this->SoftwareManagementLoadDefaultValues->SelectedValue;
		if ($os_type == 'rpm') {
			$this->DirInstallCmd->Text = self::DEFAULT_SM_RPM_DIR_INSTALL;
			$this->DirUpgradeCmd->Text = self::DEFAULT_SM_RPM_DIR_UPGRADE;
			$this->DirRemoveCmd->Text = self::DEFAULT_SM_RPM_DIR_REMOVE;
			$this->DirInfoCmd->Text = self::DEFAULT_SM_RPM_DIR_INFO;
			$this->DirEnableCmd->Text = self::DEFAULT_SM_RPM_DIR_ENABLE;
			$this->DirPostInstallCmd->Text = self::DEFAULT_SM_RPM_POST_INSTALL;
			$this->SdInstallCmd->Text = self::DEFAULT_SM_RPM_SD_INSTALL;
			$this->SdUpgradeCmd->Text = self::DEFAULT_SM_RPM_SD_UPGRADE;
			$this->SdRemoveCmd->Text = self::DEFAULT_SM_RPM_SD_REMOVE;
			$this->SdInfoCmd->Text = self::DEFAULT_SM_RPM_SD_INFO;
			$this->SdEnableCmd->Text = self::DEFAULT_SM_RPM_SD_ENABLE;
			$this->SdPostInstallCmd->Text = self::DEFAULT_SM_RPM_POST_INSTALL;
			$this->FdInstallCmd->Text = self::DEFAULT_SM_RPM_FD_INSTALL;
			$this->FdUpgradeCmd->Text = self::DEFAULT_SM_RPM_FD_UPGRADE;
			$this->FdRemoveCmd->Text = self::DEFAULT_SM_RPM_FD_REMOVE;
			$this->FdInfoCmd->Text = self::DEFAULT_SM_RPM_FD_INFO;
			$this->FdEnableCmd->Text = self::DEFAULT_SM_RPM_FD_ENABLE;
			$this->FdPostInstallCmd->Text = self::DEFAULT_SM_RPM_POST_INSTALL;
			$this->BconsInstallCmd->Text = self::DEFAULT_SM_RPM_BCONS_INSTALL;
			$this->BconsUpgradeCmd->Text = self::DEFAULT_SM_RPM_BCONS_UPGRADE;
			$this->BconsRemoveCmd->Text = self::DEFAULT_SM_RPM_BCONS_REMOVE;
			$this->BconsInfoCmd->Text = self::DEFAULT_SM_RPM_BCONS_INFO;
			$this->BconsPostInstallCmd->Text = self::DEFAULT_SM_RPM_POST_INSTALL;
		} elseif ($os_type == 'deb') {
			$this->DirInstallCmd->Text = self::DEFAULT_SM_DEB_DIR_INSTALL;
			$this->DirUpgradeCmd->Text = self::DEFAULT_SM_DEB_DIR_UPGRADE;
			$this->DirRemoveCmd->Text = self::DEFAULT_SM_DEB_DIR_REMOVE;
			$this->DirInfoCmd->Text = self::DEFAULT_SM_DEB_DIR_INFO;
			$this->DirEnableCmd->Text = self::DEFAULT_SM_DEB_DIR_ENABLE;
			$this->DirPostInstallCmd->Text = self::DEFAULT_SM_DEB_POST_INSTALL;
			$this->SdInstallCmd->Text = self::DEFAULT_SM_DEB_SD_INSTALL;
			$this->SdUpgradeCmd->Text = self::DEFAULT_SM_DEB_SD_UPGRADE;
			$this->SdRemoveCmd->Text = self::DEFAULT_SM_DEB_SD_REMOVE;
			$this->SdInfoCmd->Text = self::DEFAULT_SM_DEB_SD_INFO;
			$this->SdEnableCmd->Text = self::DEFAULT_SM_DEB_SD_ENABLE;
			$this->SdPostInstallCmd->Text = self::DEFAULT_SM_DEB_POST_INSTALL;
			$this->FdInstallCmd->Text = self::DEFAULT_SM_DEB_FD_INSTALL;
			$this->FdUpgradeCmd->Text = self::DEFAULT_SM_DEB_FD_UPGRADE;
			$this->FdRemoveCmd->Text = self::DEFAULT_SM_DEB_FD_REMOVE;
			$this->FdInfoCmd->Text = self::DEFAULT_SM_DEB_FD_INFO;
			$this->FdEnableCmd->Text = self::DEFAULT_SM_DEB_FD_ENABLE;
			$this->FdPostInstallCmd->Text = self::DEFAULT_SM_DEB_POST_INSTALL;
			$this->BconsInstallCmd->Text = self::DEFAULT_SM_DEB_BCONS_INSTALL;
			$this->BconsUpgradeCmd->Text = self::DEFAULT_SM_DEB_BCONS_UPGRADE;
			$this->BconsRemoveCmd->Text = self::DEFAULT_SM_DEB_BCONS_REMOVE;
			$this->BconsInfoCmd->Text = self::DEFAULT_SM_DEB_BCONS_INFO;
			$this->BconsPostInstallCmd->Text = self::DEFAULT_SM_DEB_POST_INSTALL;
		}
	}

	public function saveSoftwareManagement($sender, $param)
	{
		$config = [
			'enabled' => $this->SoftwareManagementEnabled->Checked ? '1' : '0',
			'use_sudo' => $this->SoftwareManagementUseSudo->Checked ? '1' : '0',
			'dir_install' => $this->DirInstallCmd->Text,
			'dir_upgrade' => $this->DirUpgradeCmd->Text,
			'dir_remove' => $this->DirRemoveCmd->Text,
			'dir_info' => $this->DirInfoCmd->Text,
			'dir_enable' => $this->DirEnableCmd->Text,
			'dir_pre_install_cmd' => $this->DirPreInstallCmd->Text,
			'dir_pre_upgrade_cmd' => $this->DirPreUpgradeCmd->Text,
			'dir_pre_remove_cmd' => $this->DirPreRemoveCmd->Text,
			'dir_post_install_cmd' => $this->DirPostInstallCmd->Text,
			'dir_post_upgrade_cmd' => $this->DirPostUpgradeCmd->Text,
			'dir_post_remove_cmd' => $this->DirPostRemoveCmd->Text,
			'sd_install' => $this->SdInstallCmd->Text,
			'sd_upgrade' => $this->SdUpgradeCmd->Text,
			'sd_remove' => $this->SdRemoveCmd->Text,
			'sd_info' => $this->SdInfoCmd->Text,
			'sd_enable' => $this->SdEnableCmd->Text,
			'sd_pre_install_cmd' => $this->SdPreInstallCmd->Text,
			'sd_pre_upgrade_cmd' => $this->SdPreUpgradeCmd->Text,
			'sd_pre_remove_cmd' => $this->SdPreRemoveCmd->Text,
			'sd_post_install_cmd' => $this->SdPostInstallCmd->Text,
			'sd_post_upgrade_cmd' => $this->SdPostUpgradeCmd->Text,
			'sd_post_remove_cmd' => $this->SdPostRemoveCmd->Text,
			'fd_install' => $this->FdInstallCmd->Text,
			'fd_upgrade' => $this->FdUpgradeCmd->Text,
			'fd_remove' => $this->FdRemoveCmd->Text,
			'fd_info' => $this->FdInfoCmd->Text,
			'fd_enable' => $this->FdEnableCmd->Text,
			'fd_pre_install_cmd' => $this->FdPreInstallCmd->Text,
			'fd_pre_upgrade_cmd' => $this->FdPreUpgradeCmd->Text,
			'fd_pre_remove_cmd' => $this->FdPreRemoveCmd->Text,
			'fd_post_install_cmd' => $this->FdPostInstallCmd->Text,
			'fd_post_upgrade_cmd' => $this->FdPostUpgradeCmd->Text,
			'fd_post_remove_cmd' => $this->FdPostRemoveCmd->Text,
			'bcons_install' => $this->BconsInstallCmd->Text,
			'bcons_upgrade' => $this->BconsUpgradeCmd->Text,
			'bcons_remove' => $this->BconsRemoveCmd->Text,
			'bcons_info' => $this->BconsInfoCmd->Text,
			'bcons_pre_install_cmd' => $this->BconsPreInstallCmd->Text,
			'bcons_pre_upgrade_cmd' => $this->BconsPreUpgradeCmd->Text,
			'bcons_pre_remove_cmd' => $this->BconsPreRemoveCmd->Text,
			'bcons_post_install_cmd' => $this->BconsPostInstallCmd->Text,
			'bcons_post_upgrade_cmd' => $this->BconsPostUpgradeCmd->Text,
			'bcons_post_remove_cmd' => $this->BconsPostRemoveCmd->Text,
		];
		$api_config = $this->getModule('api_config');
		$this->config['software_management'] = $config;
		$api_config->setConfig($this->config);
	}
}
