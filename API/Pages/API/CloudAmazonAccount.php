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
 * Amazon accounts.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class CloudAmazonAccount extends BaculumAPIServer
{
	public function get()
	{
		$misc = $this->getModule('misc');
		$account = $this->Request->contains('name') && $misc->isValidSimpleName($this->Request['name']) ? $this->Request['name'] : null;
		if (!is_string($account)) {
			$this->output = CloudAmazonError::MSG_ERROR_INVALID_ACCOUNT;
			$this->error = CloudAmazonError::ERROR_INVALID_ACCOUNT;
			return;
		}
		$caac = $this->getModule('cloud_amazon_account_config');
		if ($caac->accountConfigExists($account)) {
			$config = $caac->getAccountConfig($account);
			$this->output = $config;
			$this->error = CloudAmazonError::ERROR_NO_ERRORS;
		} else {
			$this->output = CloudAmazonError::MSG_ERROR_ACCOUNT_DOES_NOT_EXIST;
			$this->error = CloudAmazonError::ERROR_ACCOUNT_DOES_NOT_EXIST;
		}
	}

	public function create($params)
	{
		$misc = $this->getModule('misc');
		$account = property_exists($params, 'name') && $misc->isValidSimpleName($params->name) ? $params->name : null;
		if (!is_string($account)) {
			$this->output = CloudAmazonError::MSG_ERROR_INVALID_ACCOUNT;
			$this->error = CloudAmazonError::ERROR_INVALID_ACCOUNT;
			return;
		}
		$caac = $this->getModule('cloud_amazon_account_config');
		if (!$caac->accountConfigExists($account)) {
			$config = (array) $params;
			$result = $caac->setAccountConfig($params->name, $config);
			if ($result) {
				$this->output = $config;
				$this->error = CloudAmazonError::ERROR_NO_ERRORS;
			} else {
				$this->output = CloudAmazonError::MSG_ERROR_WRONG_EXITCODE;
				$this->error = CloudAmazonError::ERROR_WRONG_EXITCODE;
			}
		} else {
			$this->output = CloudAmazonError::MSG_ERROR_ACCOUNT_ALREADY_EXISTS;
			$this->error = CloudAmazonError::ERROR_ACCOUNT_ALREADY_EXISTS;
		}
	}

	public function set($id, $params)
	{
		$misc = $this->getModule('misc');
		$account = $this->Request->contains('name') && $misc->isValidSimpleName($this->Request['name']) ? $this->Request['name'] : null;
		if (!is_string($account)) {
			$this->output = CloudAmazonError::MSG_ERROR_INVALID_ACCOUNT;
			$this->error = CloudAmazonError::ERROR_INVALID_ACCOUNT;
			return;
		}
		$caac = $this->getModule('cloud_amazon_account_config');
		if ($caac->accountConfigExists($account)) {
			$config = (array) $params;
			$result = $caac->updateAccountConfig($params->name, $config);
			if ($result) {
				$this->output = $config;
				$this->error = CloudAmazonError::ERROR_NO_ERRORS;
			} else {
				$this->output = CloudAmazonError::MSG_ERROR_WRONG_EXITCODE;
				$this->error = CloudAmazonError::ERROR_WRONG_EXITCODE;
			}
		} else {
			$this->output = CloudAmazonError::MSG_ERROR_ACCOUNT_DOES_NOT_EXIST;
			$this->error = CloudAmazonError::ERROR_ACCOUNT_DOES_NOT_EXIST;
		}
	}

	public function remove($id)
	{
		$misc = $this->getModule('misc');
		$account = $this->Request->contains('name') && $misc->isValidSimpleName($this->Request['name']) ? $this->Request['name'] : null;
		if (!is_string($account)) {
			$this->output = CloudAmazonError::MSG_ERROR_INVALID_ACCOUNT;
			$this->error = CloudAmazonError::ERROR_INVALID_ACCOUNT;
			return;
		}

		$caac = $this->getModule('cloud_amazon_account_config');
		if ($caac->accountConfigExists($account)) {
			$result = $caac->deleteAccountConfig($account);
			if ($result) {
				$this->output = [];
				$this->error = CloudAmazonError::ERROR_NO_ERRORS;
			} else {
				$this->output = CloudAmazonError::MSG_ERROR_WRONG_EXITCODE;
				$this->error = CloudAmazonError::ERROR_WRONG_EXITCODE;
			}
		} else {
			$this->output = CloudAmazonError::MSG_ERROR_ACCOUNT_DOES_NOT_EXIST;
			$this->error = CloudAmazonError::ERROR_ACCOUNT_DOES_NOT_EXIST;
		}
	}
}
