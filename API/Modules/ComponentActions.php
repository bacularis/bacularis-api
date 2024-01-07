<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
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

namespace Bacularis\API\Modules;

use Bacularis\Common\Modules\Logging;
use Bacularis\Common\Modules\Errors\ActionsError;

/**
 * Module responsible for executing action commands.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class ComponentActions extends APIModule
{
	/**
	 * Sudo command.
	 */
	public const SUDO = 'sudo';

	/**
	 * Action command pattern used to execute command.
	 */
	public const ACTION_COMMAND_PATTERN = "%s%s 2>&1";

	/**
	 * Get command pattern.
	 *
	 * @return string command pattern
	 */
	private function getCmdPattern()
	{
		// Default command pattern
		return self::ACTION_COMMAND_PATTERN;
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
	 * Execute single action command.
	 *
	 * @param string $action_type action type (dir_start, dir_stop ...etc.)
	 * @return array result with output and exitcode
	 */
	public function execAction($action_type)
	{
		$result = null;
		$output = [];
		$exitcode = -1;
		$api_config = $this->getModule('api_config');
		if ($api_config->isActionConfigured($action_type) === true) {
			if ($api_config->isActionsEnabled() === true) {
				$action = $api_config->getActionConfig($action_type);
				$result = $this->execCommand($action['cmd'], $action['use_sudo']);
				if ($result->error !== 0) {
					$emsg = PHP_EOL . ' Output:' . implode(PHP_EOL, $result->output);
					$output = ActionsError::MSG_ERROR_ACTIONS_WRONG_EXITCODE . $emsg;
					$exitcode = ActionsError::ERROR_ACTIONS_WRONG_EXITCODE;
					$result = $this->prepareResult($output, $exitcode);
				}
			} else {
				$output = ActionsError::MSG_ERROR_ACTIONS_DISABLED;
				$exitcode = ActionsError::ERROR_ACTIONS_DISABLED;
				$result = $this->prepareResult($output, $exitcode);
			}
		} else {
			$output = ActionsError::MSG_ERROR_ACTIONS_NOT_CONFIGURED;
			$exitcode = ActionsError::ERROR_ACTIONS_NOT_CONFIGURED;
			$result = $this->prepareResult($output, $exitcode);
		}
		return $result;
	}

	/**
	 * Execute action command.
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
		Logging::log(
			Logging::CATEGORY_EXECUTE,
			Logging::prepareCommand($cmd, $output)
		);
		$result = $this->prepareResult($output, $exitcode);
		return $result;
	}

	/**
	 * Prepare action command result.
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
