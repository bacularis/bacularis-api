<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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
	 * Sudo command.
	 */
	public const SUDO = 'sudo';

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
	 * Get sudo command.
	 *
	 * @param bool $use_sudo sudo option state
	 * @return string sudo command
	 */
	private function getSudo($use_sudo)
	{
		$sudo = '';
		if ($use_sudo === true) {
			$sudo = self::SUDO . ' ';
		}
		return $sudo;
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
				$result = $this->execCommand($command['cmd'], $command['use_sudo']);
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
	 * @param bool $use_sudo use sudo
	 */
	public function execCommand($bin, $use_sudo)
	{
		$sudo = $this->getSudo($use_sudo);
		$cmd_pattern = $this->getCmdPattern();
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
}
