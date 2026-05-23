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
class CloudAmazonAccounts extends BaculumAPIServer
{
	public function get()
	{
		$caac = $this->getModule('cloud_amazon_account_config');
		$config = $caac->getConfig();
		$this->output = array_values($config);
		$this->error = CloudAmazonError::ERROR_NO_ERRORS;
	}
}
