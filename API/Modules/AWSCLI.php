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

use Bacularis\API\Modules\Cloud\Amazon\AWSCLIConfig;
use Bacularis\Common\Modules\Errors\CloudAmazonError;
use Bacularis\Common\Modules\Logging;
use Prado\Prado;

/**
 * Execute Amazon AWS CLI binary.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class AWSCLI extends APIModule
{
	/**
	 * AWS CLI binary command.
	 */
	private const CMD = 'aws';

	/**
	 * AWS CLI user home directory.
	 */
	private const CMD_HOME = 'Bacularis.API.Config';

	/**
	 * Supported AWS services.
	 */
	private const SUPPORTED_SERVICES = [
		'configure',
		'ebs',
		'ec2'
	];

	/**
	 * AWS CLI command pattern.
	 * Command: <sudo> <env_vars> <cmd> <cmd_params> <cli_params> 2>&1
	 */
	private const AWSCLI_COMMAND_PATTERN = '%s %s %s %s %s 2>&1';

	/**
	 * Main execute AWS CLI command.
	 * All public communication with AWS CLI should go through this method.
	 *
	 * @param array $cmd_params command parameters
	 * @param array $cli_params AWS CLI specific parameters
	 * @param array $sudo_prop sudo settings (user, group, use_sudo ...)
	 * @return array result with output and exit code
	 */
	public function execCommand(array $cmd_params, array $cli_params = [], array $sudo_prop = []): array
	{
		$env_params = self::getEnvParams();
		$result = self::execute($cmd_params, $cli_params, $env_params, $sudo_prop);
		$output = $result['output'];
		$error = $result['error'];
		if ($error == 0) {
			$output = self::prepareOutput($result['output']);
		} else {
			$out = var_export($result['output'], true);
			$output = CloudAmazonError::MSG_ERROR_WRONG_EXITCODE . $out;
			$error = CloudAmazonError::ERROR_WRONG_EXITCODE;
		}
		$result = self::prepareResult($output, $error);
		return $result;
	}

	/**
	 * Execute AWS CLI binary.
	 * This is internal method that should not be used outside.
	 *
	 * @param array $cmd_params command parameters
	 * @param array $cli_params AWS CLI specific parameters
	 * @param array $env_params environment variables passed to command
	 * @param array $sudo_prop sudo settings (user, group, use_sudo ...)
	 * @return array command result (output and exit code)
	 */
	private static function execute(array $cmd_params, array $cli_params = [], array $env_params = [], array $sudo_prop = []): array
	{
		$sudo = self::getSudo($sudo_prop);
		$env_vars = self::getEnvVars($env_params);
		$command = self::getCommand($cmd_params);
		$params = self::getCLIParams($cli_params);
		$cmd = sprintf(
			self::AWSCLI_COMMAND_PATTERN,
			$sudo,
			$env_vars,
			self::CMD,
			$command,
			$params
		);
		exec($cmd, $output, $exitcode);
		Logging::log(
			Logging::CATEGORY_EXECUTE,
			Logging::prepareCommand($cmd, $output),
			($exitcode != 0)
		);
		$result = self::prepareResult($output, $exitcode);
		return $result;
	}

	/**
	 * Prepare command results.
	 *
	 * @param mixed $output command output
	 * @param int $error command exit code
	 * @return array command results ready to use
	 */
	private static function prepareResult($output, int $error): array
	{
		$result = [
			'output' => $output,
			'error' => $error
		];
		return $result;
	}

	/**
	 * Prepare AWS CLI command output.
	 *
	 * @param array $output raw output list
	 * @return null|array parsed output or null if output was unable to parse
	 */
	private static function prepareOutput(array $output): ?object
	{
		$output_txt = implode('', $output);
		$out = json_decode($output_txt);
		if (!is_object($out)) {
			Logging::log(
				Logging::CATEGORY_EXTERNAL,
				'OUTPUT: ' . $output_txt
			);
			$out = null;
		}
		return $out;
	}

	/**
	 * Get sudo command.
	 *
	 * @param array $prop sudo properties
	 * @return sudo command
	 */
	private static function getSudo(array $prop): string
	{
		$sudo = Prado::createComponent(
			'Bacularis\API\Modules\Sudo',
			$prop
		);
		return $sudo->getSudoCmd();
	}

	/**
	 * Prepare environment variables.
	 *
	 * @param array $params environment command parameters
	 * @return string environment variables
	 */
	private static function getEnvVars(array $params): string
	{
		$env_vars = [];
		if (key_exists('home', $params)) {
			$env_vars[] = "HOME='{$params['home']}'";
		}
		if (key_exists('config', $params)) {
			$env_vars[] = "AWS_CONFIG_FILE='{$params['config']}'";
		}
		return implode(' ', $env_vars);
	}

	/**
	 * Get AWS CLI command.
	 * NOTE: AWS service (ex: 'ec2', 's3' ...) must be supported by this module.
	 *
	 * @param array $params command parameters
	 * @return string AWS CLI command or empty string if service is not supported
	 */
	private static function getCommand(array $params): string
	{
		$command = '';
		$service = $params[0] ?? '';
		if (!in_array($service, self::SUPPORTED_SERVICES)) {
			return $command;
		}
		return implode(' ', $params);
	}

	/**
	 * Get environment parameters.
	 *
	 * @return array environment parameter list
	 */
	private static function getEnvParams(): array
	{
		$home = Prado::getPathOfNamespace(self::CMD_HOME);
		$config = AWSCLIConfig::getConfigFilePath();
		$params = [
			'home' => $home,
			'config' => $config
		];
		return $params;
	}

	/**
	 * Get AWS CLI specific parameters.
	 *
	 * @param array $params CLI parameters
	 * @return string CLI parameters ready to use in command
	 */
	private static function getCLIParams(array $params): string
	{
		$cli_params = [];
		foreach ($params as $key => $value) {
			if (is_null($value)) {
				$cli_params[] = "--{$key}";
			} else {
				$cli_params[] = "--{$key} '{$value}'";
			}
		}
		return implode(' ', $cli_params);
	}
}
