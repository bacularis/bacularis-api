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

/**
 * OAuth2 client endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
class OAuth2Client extends BaculumAPIServer {

	public function get() {
		$oauth2_client_id = $this->Request->contains('id') ? $this->Request['id'] : 0;
		$client_id = $this->getModule('oauth2')->validateClientId($oauth2_client_id) ? $oauth2_client_id : null;
		if (is_string($client_id)) {
			$oauth2_cfg = $this->getModule('oauth2_config')->getConfig($client_id);
			if (count($oauth2_cfg) > 0) {
				$this->output = $oauth2_cfg;
				$this->error = OAuth2Error::ERROR_NO_ERRORS;
			} else {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_DOES_NOT_EXIST;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_DOES_NOT_EXIST;
			}
		} else {
			$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_DOES_NOT_EXIST;
			$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_DOES_NOT_EXIST;
		}
	}

	public function create($params) {
		$oauth2 = $this->getModule('oauth2');
		$oauth2_config = $this->getModule('oauth2_config');
		$misc = $this->getModule('misc');
		$oauth2_cfg = $oauth2_config->getConfig();

		if (property_exists($params, 'client_id') && key_exists($params->client_id, $oauth2_cfg)) {
			$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_ALREADY_EXISTS;
			$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_ALREADY_EXISTS;
			return;
		}

		if (property_exists($params, 'client_id') && $oauth2->validateClientId($params->client_id)) {
			$oauth2_cfg[$params->client_id]['client_id'] = $params->client_id;
		} else {
			$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_CLIENT_ID;
			$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_CLIENT_ID;
			return;
		}

		if (property_exists($params, 'client_secret') && $oauth2->validateClientSecret($params->client_secret)) {
			$oauth2_cfg[$params->client_id]['client_secret'] = $params->client_secret;
		} else {
			$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_CLIENT_SECRET;
			$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_CLIENT_SECRET;
			return;
		}

		if (property_exists($params, 'redirect_uri') && $oauth2->validateRedirectUri($params->redirect_uri)) {
			$oauth2_cfg[$params->client_id]['redirect_uri'] = $params->redirect_uri;
		} else {
			$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_REDIRECT_URI;
			$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_REDIRECT_URI;
			return;
		}

		if (property_exists($params, 'scope') && $oauth2->validateScopes($params->scope)) {
			$oauth2_cfg[$params->client_id]['scope'] = $params->scope;
		} else {
			$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_SCOPE;
			$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_SCOPE;
			return;
		}

		if (property_exists($params, 'bconsole_cfg_path')) {
			if ($misc->isValidPath($params->bconsole_cfg_path)) {
				$oauth2_cfg[$params->client_id]['bconsole_cfg_path'] = $params->bconsole_cfg_path;
			} else {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_BCONSOLE_CFG_PATH;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_BCONSOLE_CFG_PATH;
				return;
			}
		} else {
			$oauth2_cfg[$params->client_id]['bconsole_cfg_path'] = '';
		}

		if (property_exists($params, 'name') && !empty($params->name)) {
			if ($misc->isValidName($params->name)) {
				$oauth2_cfg[$params->client_id]['name'] = $params->name;
			} else {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_NAME;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_NAME;
				return;
			}
		} else {
			$oauth2_cfg[$params->client_id]['name'] = '';
		}

		if (property_exists($params, 'console') && property_exists($params, 'director')) {
			if (!$misc->isValidName($params->console)) {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_CONSOLE;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_CONSOLE;
				return;	
			}
			if (!$misc->isValidName($params->director)) {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_DIRECTOR;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_DIRECTOR;
				return;	
			}
			$bs = $this->getModule('bacula_setting');

			$dir_cfg = $bs->getConfig('bcons', 'Director', $params->director);
			if ($dir_cfg['exitcode'] != 0) {
				$this->output = $dir_cfg['output'];
				$this->error = OAuth2Error::ERROR_INTERNAL_ERROR;
				return;
			}

			$console_cfg = $bs->getConfig('dir', 'Console', $params->console);
			if ($console_cfg['exitcode'] != 0) {
				$this->output = $console_cfg['output'];
				$this->error = OAuth2Error::ERROR_INTERNAL_ERROR;
				return;
			}

			$cfg = [
				[
					'Director' => [
						'Name' => '"' . $dir_cfg['output']['Name'] . '"',
						'DirPort' => $dir_cfg['output']['DirPort'],
						'Address' => $dir_cfg['output']['Address'],
						'Password' => 'XXXX'
					],
					'Console' => [
						'Name' => '"' . $console_cfg['output']['Name'] . '"',
						'Password' => '"' . $console_cfg['output']['Password'] . '"'
					]
				]
			];
			$json_tools = $this->getModule('api_config')->getConfig('jsontools');
			$dir = $json_tools['bconfig_dir'];
			$file = sprintf('%s/bconsole-%s.cfg', $dir, $console_cfg['output']['Name']);
			$this->getModule('bacula_config')->setConfig('bcons', $cfg, $file);
			$oauth2_cfg[$params->client_id]['bconsole_cfg_path'] = $file;
		}

		// save config
		$result = $oauth2_config->setConfig($oauth2_cfg);

		if ($result) {
			$this->output = $oauth2_cfg;
			$this->error = OAuth2Error::ERROR_NO_ERRORS;
		} else {
			$this->output = OAuth2Error::MSG_ERROR_INTERNAL_ERROR;
			$this->error = OAuth2Error::ERROR_INTERNAL_ERROR;
		}
	}

	public function set($id, $params) {
		$oauth2_client_id = property_exists($params, 'client_id') ? $params->client_id : 0;
		$client_id = $this->getModule('oauth2')->validateClientId($oauth2_client_id) ? $oauth2_client_id : null;
		if (!is_string($client_id)) {
			$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_DOES_NOT_EXIST;
			$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_DOES_NOT_EXIST;
			return;
		}

		$oauth2 = $this->getModule('oauth2');
		$oauth2_config = $this->getModule('oauth2_config');
		$misc = $this->getModule('misc');
		$oauth2_cfg = $oauth2_config->getConfig();

		if (!key_exists($client_id, $oauth2_cfg)) {
			$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_DOES_NOT_EXIST;
			$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_DOES_NOT_EXIST;
			return;
		}

		if (property_exists($params, 'client_secret')) {
			if ($oauth2->validateClientSecret($params->client_secret)) {
				$oauth2_cfg[$client_id]['client_secret'] = $params->client_secret;
			} else {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_CLIENT_SECRET;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_CLIENT_SECRET;
				return;
			}
		}
		if (property_exists($params, 'redirect_uri')) {
			if ($oauth2->validateRedirectUri($params->redirect_uri)) {
				$oauth2_cfg[$client_id]['redirect_uri'] = $params->redirect_uri;
			} else {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_REDIRECT_URI;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_REDIRECT_URI;
				return;
			}
		}
		if (property_exists($params, 'scope')) {
			if ($oauth2->validateScopes($params->scope)) {
				$oauth2_cfg[$client_id]['scope'] = $params->scope;
			} else {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_SCOPE;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_SCOPE;
				return;
			}
		}
		if (property_exists($params, 'bconsole_cfg_path')) {
			if ($misc->isValidPath($params->bconsole_cfg_path)) {
				$oauth2_cfg[$client_id]['bconsole_cfg_path'] = $params->bconsole_cfg_path;
			} else {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_BCONSOLE_CFG_PATH;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_BCONSOLE_CFG_PATH;
				return;
			}
		}
		if (property_exists($params, 'name') && !empty($params->name)) {
			if ($misc->isValidName($params->name)) {
				$oauth2_cfg[$client_id]['name'] = $params->name;
			} else {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_NAME;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_NAME;
				return;
			}
		} else {
			$oauth2_cfg[$client_id]['name'] = '';
		}
		if (property_exists($params, 'console') && property_exists($params, 'director')) {
			if (!$misc->isValidName($params->console)) {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_CONSOLE;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_CONSOLE;
				return;	
			}
			if (!$misc->isValidName($params->director)) {
				$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_INVALID_DIRECTOR;
				$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_INVALID_DIRECTOR;
				return;	
			}
			$bs = $this->getModule('bacula_setting');

			$dir_cfg = $bs->getConfig('bcons', 'Director', $params->director);
			if ($dir_cfg['exitcode'] != 0) {
				$this->output = $dir_cfg['output'];
				$this->error = OAuth2Error::ERROR_INTERNAL_ERROR;
				return;
			}

			$console_cfg = $bs->getConfig('dir', 'Console', $params->console);
			if ($console_cfg['exitcode'] != 0) {
				$this->output = $console_cfg['output'];
				$this->error = OAuth2Error::ERROR_INTERNAL_ERROR;
				return;
			}

			$cfg = [
				[
					'Director' => [
						'Name' => '"' . $dir_cfg['output']['Name'] . '"',
						'DirPort' => $dir_cfg['output']['DirPort'],
						'Address' => $dir_cfg['output']['Address'],
						'Password' => 'XXXX'
					],
					'Console' => [
						'Name' => '"' . $console_cfg['output']['Name'] . '"',
						'Password' => '"' . $console_cfg['output']['Password'] . '"'
					]
				]
			];
			$json_tools = $this->getModule('api_config')->getConfig('jsontools');
			$dir = $json_tools['bconfig_dir'];
			$file = sprintf('%s/bconsole-%s.cfg', $dir, $console_cfg['output']['Name']);
			$this->getModule('bacula_config')->setConfig('bcons', $cfg, $file);
			$oauth2_cfg[$params->client_id]['bconsole_cfg_path'] = $file;
		}

		$result = $oauth2_config->setConfig($oauth2_cfg);
		if ($result) {
			$this->output = $oauth2_cfg;
			$this->error = OAuth2Error::ERROR_NO_ERRORS;
		} else {
			$this->output = OAuth2Error::MSG_ERROR_INTERNAL_ERROR;
			$this->error = OAuth2Error::ERROR_INTERNAL_ERROR;
		}
	}

	public function remove($id) {
		$oauth2 = $this->getModule('oauth2_config');
		$oauth2_cfg = $oauth2->getConfig();
		if (key_exists($id, $oauth2_cfg)) {
			unset($oauth2_cfg[$id]);
			$result = $oauth2->setConfig($oauth2_cfg);
			if ($result) {
				$this->output = [];
				$this->error = OAuth2Error::ERROR_NO_ERRORS;
			} else {
				$this->output = OAuth2Error::MSG_ERROR_INTERNAL_ERROR;
				$this->error = OAuth2Error::ERROR_INTERNAL_ERROR;
			}
		} else {
			$this->output = OAuth2Error::MSG_ERROR_OAUTH2_CLIENT_DOES_NOT_EXIST;
			$this->error = OAuth2Error::ERROR_OAUTH2_CLIENT_DOES_NOT_EXIST;
		}
	}
}
?>
