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

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\Common\Modules\Errors\CloudAmazonError;

/**
 * Describe Amazon EC2 instances.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class CloudAmazonEC2InstancesDescribe extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$account = $this->Request->contains('account') && $misc->isValidSimpleName($this->Request['account']) ? $this->Request['account'] : null;
		if (is_null($account)) {
			$this->output = CloudAmazonError::MSG_ERROR_INVALID_ACCOUNT;
			$this->error = CloudAmazonError::ERROR_INVALID_ACCOUNT;
			return;
		}
		$command = ['ec2', 'describe-instances'];
		$caac = $this->getModule('cloud_amazon_aws_cmd');
		$result = $caac->execCommand($account, $command);
		$this->output = $result['output'];
		$this->error = $result['error'];
	}
}
