<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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
 * Get/set OAuth2 auth identifier.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Authorization
 */
class AuthIdRecord extends SessionRecord implements ISessionItem
{
	/**
	 * Host record session file name.
	 */
	private const SESSION_FILE_NAME = 'Bacularis.API.Config.session';

	/**
	 * Host record sesion file extension.
	 */
	private const SESSION_FILE_EXT = '.dump';

	/**
	 * Authorization identifier session properties.
	 */
	public $auth_id;
	public $client_id;
	public $redirect_uri;
	public $expires;
	public $scope;

	/**
	 * Get session record identifier.
	 *
	 * @return string record identifier
	 */
	public static function getRecordId(): string
	{
		return 'oauth2_auth_id';
	}

	/**
	 * Get session record primary key.
	 *
	 * @return string primary key name
	 */
	public static function getPrimaryKey(): string
	{
		return 'auth_id';
	}

	/**
	 * Get full session file path.
	 *
	 * @return string session file path
	 */
	public static function getSessionFile(): string
	{
		return Prado::getPathOfNamespace(
			self::SESSION_FILE_NAME,
			self::SESSION_FILE_EXT
		);
	}
}
