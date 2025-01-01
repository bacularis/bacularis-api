<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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

namespace Bacularis\API\Modules\OAuth2;

use Prado\Prado;
use Bacularis\Common\Modules\ISessionItem;
use Bacularis\Common\Modules\SessionRecord;

/**
 * Module to store tokens as session record.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Authorization
 */
class TokenRecord extends SessionRecord implements ISessionItem
{
	public $access_token;
	public $refresh_token;
	public $client_id;
	public $expires;
	public $scope;
	public $bconsole_cfg_path;

	public static function getRecordId()
	{
		return 'oauth2_token';
	}

	public static function getPrimaryKey()
	{
		return 'access_token';
	}

	public static function getSessionFile()
	{
		return Prado::getPathOfNamespace('Bacularis.API.Config.session', '.dump');
	}
}
