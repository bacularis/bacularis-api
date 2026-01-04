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

namespace Bacularis\API\Modules;

use Prado\Prado;
use Bacularis\Common\Modules\Logging;
use Bacularis\Common\Modules\Errors\SoftwareManagementError;

/**
 * Module responsible for executing software management commands.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class SoftwareManagement extends APIModule
{
	/**
	 * Sudo object.
	 */
	private $sudo;

	/**
	 * Software management command pattern used to execute command.
	 */
	public const SOFTWARE_MANAGEMENT_COMMAND_PATTERN = "%s%s 2>&1";

	/**
	 * Get command pattern.
	 *
	 * @return string command pattern
	 */
	private function getCmdPattern()
	{
		// Default command pattern
		return self::SOFTWARE_MANAGEMENT_COMMAND_PATTERN;
	}

	/**
	 * Set sudo setting for command.
	 *
	 * @param array $prop sudo properties
	 */
	private function setSudo(array $prop): void
	{
		$this->sudo = Prado::createComponent(
			'Bacularis\API\Modules\Sudo',
			$prop
		);
	}

	/**
	 * Execute single software management command.
	 *
	 * @param string $command command type (dir_start, dir_stop ...etc.)
	 * @return array result with output and exitcode
	 */
	public function execSoftwareManagementCommand($command)
	{
		$result = null;
		$output = [];
		$exitcode = -1;
		$api_config = $this->getModule('api_config');
		if ($api_config->isSoftwareManagementCommandConfigured($command) === true) {
			if ($api_config->isSoftwareManagementEnabled() === true) {
				$command = $api_config->getSoftwareManagementCommandConfig($command);
				$result = $this->execCommand($command['cmd'], $command['sudo']);
				if ($result->error !== 0) {
					$emsg = PHP_EOL . ' Output:' . implode(PHP_EOL, $result->output);
					$output = SoftwareManagementError::MSG_ERROR_SOFTWARE_MANAGEMENT_WRONG_EXITCODE . $emsg;
					$exitcode = SoftwareManagementError::ERROR_SOFTWARE_MANAGEMENT_WRONG_EXITCODE;
					$result = $this->prepareResult($output, $exitcode);
				}
			} else {
				$output = SoftwareManagementError::MSG_ERROR_SOFTWARE_MANAGEMENT_DISABLED;
				$exitcode = SoftwareManagementError::ERROR_SOFTWARE_MANAGEMENT_DISABLED;
				$result = $this->prepareResult($output, $exitcode);
			}
		} else {
			$output = SoftwareManagementError::MSG_ERROR_SOFTWARE_MANAGEMENT_COMMAND_NOT_CONFIGURED;
			$exitcode = SoftwareManagementError::ERROR_SOFTWARE_MANAGEMENT_COMMAND_NOT_CONFIGURED;
			$result = $this->prepareResult($output, $exitcode);
		}
		return $result;
	}

	/**
	 * Execute software management command.
	 *
	 * @param string $bin command
	 * @param array $sudo_prop sudo properties
	 */
	public function execCommand($bin, $sudo_prop)
	{
		$this->setSudo($sudo_prop);
		$cmd_pattern = $this->getCmdPattern();
		$sudo = $this->sudo->getSudoCmd();
		$cmd = sprintf($cmd_pattern, $sudo, $bin);
		exec($cmd, $output, $exitcode);
		array_unshift($output, $cmd);
		Logging::log(
			Logging::CATEGORY_EXECUTE,
			Logging::prepareCommand($cmd, $output)
		);
		$result = $this->prepareResult($output, $exitcode);
		return $result;
	}

	/**
	 * Prepare software managment command result.
	 *
	 * @param array $output output from command execution
	 * @param int $exitcode command exit code
	 * @return array result with output and exitcode
	 */
	public function prepareResult($output, $exitcode)
	{
		$result = (object) [
			'output' => $output,
			'error' => $exitcode
		];
		return $result;
	}

	/**
	 * Install Bacula component.
	 *
	 * @param string $component Bacula component to install
	 * @return array output and error code
	 */
	public function installComponent(string $component): object
	{
		$output = [];
		$exitcode = 0;

		$pre_cmd = $cmd = $post_cmd = '';
		switch ($component) {
			case 'catalog':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_CAT_INSTALL;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_CAT_INSTALL;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_CAT_INSTALL;
				break;
			case 'director':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_DIR_INSTALL;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_DIR_INSTALL;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_DIR_INSTALL;
				break;
			case 'storage':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_SD_INSTALL;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_SD_INSTALL;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_SD_INSTALL;
				break;
			case 'client':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_FD_INSTALL;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_FD_INSTALL;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_FD_INSTALL;
				break;
			case 'console':
				$pre_cmd = APIConfig::SOFTWARE_MANAGEMENT_PRE_BCONS_INSTALL;
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_BCONS_INSTALL;
				$post_cmd = APIConfig::SOFTWARE_MANAGEMENT_POST_BCONS_INSTALL;
				break;
		}

		// Pre-install command
		$ret = $this->execSoftwareManagementCommand($pre_cmd);
		if ($ret->error != 0) {
			$ret->output = [$ret->output];
		}
		$output = array_merge($output, $ret->output);
		$error = $ret->error;

		// Install command
		if ($error == 0 || $error === SoftwareManagementError::ERROR_SOFTWARE_MANAGEMENT_COMMAND_NOT_CONFIGURED) {
			$ret = $this->execSoftwareManagementCommand($cmd);
			if ($ret->error != 0) {
				$ret->output = [$ret->output];
			}
			$output = array_merge($output, $ret->output);
			$error = $exitcode = $ret->error;
		}

		// Post-install command
		if ($error == 0 || $error === SoftwareManagementError::ERROR_SOFTWARE_MANAGEMENT_COMMAND_NOT_CONFIGURED) {
			$ret = $this->execSoftwareManagementCommand($post_cmd);
			if ($ret->error != 0) {
				$ret->output = [$ret->output];
			}
			$output = array_merge($output, $ret->output);
			$error = $ret->error;
		}

		// Installation completed successfully
		$error = $error !== SoftwareManagementError::ERROR_SOFTWARE_MANAGEMENT_COMMAND_NOT_CONFIGURED ? $ret->error : $exitcode;
		if ($error != 0) {
			$out = implode(PHP_EOL, $output);
			Logging::log(
				Logging::CATEGORY_EXTERNAL,
				"Error while installig Bacula '{$component}' component. Output: $out, 'Error: $error"
			);
		}
		return $this->prepareResult($output, $error);
	}


	/**
	 * Enable Bacula component.
	 *
	 * @param string $component Bacula component to enable
	 * @return array output and error code
	 */
	public function enableComponent(string $component): object
	{
		$cmd = '';
		switch ($component) {
			case 'catalog':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_CAT_ENABLE;
				break;
			case 'director':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_DIR_ENABLE;
				break;
			case 'storage':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_SD_ENABLE;
				break;
			case 'client':
				$cmd = APIConfig::SOFTWARE_MANAGEMENT_FD_ENABLE;
				break;
		}

		// Install command
		$ret = $this->execSoftwareManagementCommand($cmd);
		$output = $ret->output;
		$error = $ret->error;

		if ($error != 0 && $error !== SoftwareManagementError::ERROR_SOFTWARE_MANAGEMENT_COMMAND_NOT_CONFIGURED) {
			Logging::log(
				Logging::CATEGORY_EXTERNAL,
				"Error while enabling Bacula '{$component}' component. Output: $output, 'Error: $error"
			);
		}
		return $this->prepareResult($output, $error);
	}
}
