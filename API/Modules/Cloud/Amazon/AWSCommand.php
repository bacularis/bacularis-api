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

namespace Bacularis\API\Modules\Cloud\Amazon;

use Bacularis\API\Modules\APIModule;

/**
 * AWS command support.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class AWSCommand extends APIModule
{
	/**
	 * Run AWS command.
	 *
	 * @param string $account AWS configuration account name (profile)
	 * @param array $cmd_params AWS command parameters
	 * @return array command result (output and error)
	 */
	public function execCommand(string $account, array $cmd_params): array
	{
		$awscli = $this->getModule('awscli');
		$cli_params = ['profile' => $account];
		$sudo_props = [
			'use_sudo' => false,
			'user' => '',
			'group' => ''
		];
		$result = $awscli->execCommand($cmd_params, $cli_params, $sudo_props);
		return $result;
	}

	/**
	 * Add selected global AWS CLI options to command parameters.
	 *
	 * @param array $opts current AWS CLI options
	 */
	public static function addGlobalOptions(array &$opts): void
	{
		// here can be added global AWS CLI parameters
		$options = [
		];
		$opts = array_merge($opts, $options);
	}

	/**
	 * Get AWS account credentials (static or assumed-role)
	 *
	 * @param string $account AWS configuration account name (profile)
	 * @return array AWS account credentials or empty array on error
	 */
	public function getAccountCredentials(string $account): array
	{
		$credentials = [];
		$params = [
			'configure',
			'export-credentials'
		];
		self::addGlobalOptions($params);
		$result = $this->execCommand($account, $params);
		if ($result['error'] == 0) {
			$credentials = (array) $result['output'];
		}
		return $credentials;
	}

	/**
	 * Check if AWS account uses STS assume role.
	 *
	 * @param string $account AWS configuration account name (profile)
	 * @return bool true if account u ses assume role, false otherwise
	 */
	public function isAccountAssumeRole(string $account): bool
	{
		$is_assume_role = false;
		$params = [
			'sts',
			'get-caller-identity'
		];
		self::addGlobalOptions($params);
		$result = $this->execCommand($account, $params);
		if ($result['error'] == 0) {
			$out = $result['output']->Arn ?? '';
			if ($out) {
				$arn = self::parseARN($out);
				$is_assume_role = ($arn['service'] == 'sts' && $arn['resource_id'] == 'assumed-role');
			}

		}
		return $is_assume_role;
	}

	/**
	 * Parse AWS ARN (Amazon Resource Name) string.
	 *
	 * @param string $arn ARN string
	 * @return array parsed ARN parts
	 */
	public static function parseARN(string $arn): array
	{
		[
			$arn,
			$partition,
			$service,
			$region,
			$account_id,
			$resource_id
		] = explode(':', $arn, 6);
		return [
			'arn' => $arn,
			'partition' => $partition,
			'service' => $service,
			'region' => $region,
			'account_id' => $account_id,
			'resource_id' => $resource_id
		];
	}
}
