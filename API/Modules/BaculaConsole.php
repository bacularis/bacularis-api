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

use Bacularis\Common\Modules\Logging;
use Prado\Prado;

/**
 * Manage Bacula console.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BaculaConsole extends APIModule
{
	/**
	 * Path to console management script.
	 */
	private const BACULA_CONSOLE_BINARY = 'Bacularis.Common.Bin.bacula-console';

	/**
	 * Start Bacula console session.
	 */
	public static function start($params)
	{
		$command = 'console/start';
		return self::executeInBg($command, $params);
	}

	/**
	 * Execute Bacula console command.
	 *
	 * @param string $command command name
	 * @param array $params command parameters
	 * @return array result output and error code
	 */
	public static function execute(string $command, array $params)
	{
		$bin = Prado::getPathOfNamespace(self::BACULA_CONSOLE_BINARY);
		$cmd_params = self::getCommandParams($params);
		array_unshift($cmd_params, $command);
		$parameters = implode(' ', $cmd_params);
		$cmd = sprintf('%s %s', $bin, $parameters);
		Logging::log(Logging::CATEGORY_EXECUTE, 'COMMAND ===> ' . $cmd);
		exec($cmd, $output, $error);
		Logging::log(Logging::CATEGORY_EXECUTE, 'OUTPUT ===> ' . var_export($output, true) . ', ExitCode=' . $error);
		return [
			'output' => $output,
			'error' => $error
		];
	}

	/**
	 * Execute Bacula console command in background.
	 *
	 * @param string $command command name
	 * @param array $params command parameters
	 * @return array result output and error code.
	 */
	public static function executeInBg(string $command, array $params)
	{
		$bin = Prado::getPathOfNamespace(self::BACULA_CONSOLE_BINARY);
		$cmd_params = self::getCommandParams($params);
		array_unshift($cmd_params, $command);
		$parameters = implode(' ', $cmd_params);
		$cmd = sprintf('%s %s </dev/null >/dev/null 2>&1 &', $bin, $parameters);
		Logging::log(Logging::CATEGORY_EXECUTE, 'COMMAND ===> ' . $cmd);
		exec($cmd, $output, $error);
		Logging::log(Logging::CATEGORY_EXECUTE, 'OUTPUT ===> ' . var_export($output, true) . ', ExitCode=' . $error);
		return [
			'output' => $output,
			'error' => $error
		];
	}

	/**
	 * Prepare Bacula console command parameter list.
	 *
	 * @param array $params command parameters
	 * @return array parameters ready to use in Bacula console command
	 */
	private static function getCommandParams(array $params): array
	{
		$plist = [];
		foreach ($params as $key => $value) {
			if (is_null($value) || (is_bool($value) && $value == true)) {
				$plist[] = sprintf('--%s', $key);
			} else {
				$evalue = str_replace('"', '\\"', $value);
				$plist[] = sprintf('--%s="%s"', $key, $evalue);
			}
		}
		return $plist;
	}
}
