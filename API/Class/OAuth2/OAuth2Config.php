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

Prado::using('Application.Common.Class.ConfigFileModule');

/**
 * Manage OAuth2 client configuration.
 * Module is responsible for get/set OAuth2 client config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Authorization
 * @package Baculum API
 */
class OAuth2Config extends ConfigFileModule {

	/**
	 * OAuth2 client config file path
	 */
	const CONFIG_FILE_PATH = 'Application.API.Config.oauth2';

	/**
	 * OAuth2 client config file format
	 */
	const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * These options are obligatory for OAuth2 config.
	 */
	private $required_options = array('client_id', 'client_secret', 'redirect_uri', 'scope');

	/**
	 * New options with default values that has been added later (after creating config).
	 * They are added to config output.
	 *
	 * @see OAuth2Config::setAddedOptions()
	 */
	private $added_options = array('name' => '');

	/**
	 * Get (read) OAuth2 client config.
	 *
	 * @access public
	 * @param string $section config section name
	 * @return array config
	 */
	public function getConfig($section = null) {
		$config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		$is_valid = true;
		if (!is_null($section)) {
			$config = key_exists($section, $config) ? $config[$section] : array();
			$is_valid = $this->validateConfig($config);
		} else {
			foreach ($config as $value) {
				if ($this->validateConfig($value) === false) {
					$is_valid = false;
					break;
				}
			}
		}
		if ($is_valid === false) {
			// no validity, no config
			$config = array();
		} else {
			$this->setAddedOptions($config, $section);
		}
		return $config;
	}

	/**
	 * Set (save) OAuth2 client config.
	 *
	 * @access public
	 * @param array $config config
	 * @return boolean true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config) {
		return $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
	}


	/**
	 * Validate API config.
	 * Config validation should be used as early as config data is available.
	 * Validation is done in read/write config methods.
	 *
	 * @access private
	 * @param array $config config
	 * @return boolean true if config valid, otherwise false
	 */
	private function validateConfig(array $config = array()) {
		$is_valid = true;
		/**
		 * Don't use validation from parent class because it logs to file in
		 * case errors and it could cause save to log a private auth params.
		 */
		for ($i = 0; $i < count($this->required_options); $i++) {
			if (!key_exists($this->required_options[$i], $config)) {
				$is_valid = false;
				$emsg = 'Invalid OAuth2 config. Missing ' . $this->required_options[$i] . ' option.';
				$this->getModule('logging')->log(
					__FUNCTION__,
					$emsg,
					Logging::CATEGORY_APPLICATION,
					__FILE__,
					__LINE__
				);
				break;
			}
		}
		return $is_valid;
	}

	/**
	 * Add "on the fly" new options to config.
	 * Note, this method should be used after config validation.
	 *
	 * @param array reference $config config to set added options.
	 * @param mixed $section determines if passed all config or only section
	 * @return none
	 */
	private function setAddedOptions(&$config, $section = null) {
		foreach ($this->added_options as $added_opt => $defval) {
			if (is_null($section)) {
				foreach($config as $key => $value) {
					if (!key_exists($added_opt, $value)) {
						$config[$key][$added_opt] = $defval;
					}
				}
			} elseif (!key_exists($added_opt, $config)) {
				$config[$added_opt] = $defval;
			}
		}
	}
}
?>
