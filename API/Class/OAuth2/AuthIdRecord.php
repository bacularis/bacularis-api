<?php
/*
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

Prado::using('Application.Common.Class.Interfaces');
Prado::using('Application.Common.Class.SessionRecord');
 
/**
 * Get/set OAuth2 auth identifier.
 * 
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Authorization
 * @package Baculum API
 */
class AuthIdRecord extends SessionRecord implements SessionItem {

	public $auth_id;
	public $client_id;
	public $redirect_uri;
	public $expires;
	public $scope;

	public static function getRecordId() {
		return 'oauth2_auth_id';
	}

	public static function getPrimaryKey() {
		return 'auth_id';
	}

	public static function getSessionFile() {
		return Prado::getPathOfNamespace('Application.API.Config.session', '.dump');
	}
}
?>
