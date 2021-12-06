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

Prado::using('Application.API.Class.OAuth2.AuthIdRecord');
Prado::using('Application.API.Class.APIModule');

/**
 * Manager for authorization identifiers (authorization codes).
 * 
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Authorization
 * @package Baculum API
 */
class AuthIdManager extends APIModule {

	/**
	 * Get authorization identifier properties.
	 * 
	 * @access public
	 * @param string $auth_id authorization identifier
	 * @return mixed array auth_id params or null if no record found.
	 */
	public function getAuthId($auth_id) {
		return AuthIdRecord::findByPk($auth_id);
	}

	/**
	 * Delete expired authorization identifiers from database.
	 * 
	 * @access public
	 * @return none
	 */
	public function deleteExpiredAuthIds() {
		$current_time = time();
		$values = array();
		$vals =& AuthIdRecord::get();
		$vals_len = count($vals);
		for ($i = ($vals_len-1); $i >= 0; $i--) {
			if ($vals[$i]['expires'] < $current_time) {
				array_splice($vals, $i, 1);
			}
		}
	}

	/**
	 * Delete authorization identifier properties by authorization identifier.
	 * 
	 * @access public
	 * @param string $auth_id authorization identifier to delete
	 * @return boolean true if authorization identifier deleted successfully, otherwise false
	 */
	public function deleteAuthId($auth_id) {
		return AuthIdRecord::deleteByPk($auth_id);
	}

	/**
	 * Authorization identifier setting.
	 * 
	 * NOTE!
	 * It should be using before releasing autorization identifier to user's
	 * application, not after releasing.
	 * 
	 * @public
	 * @param string $auth_id authorization identifier
	 * @param string $client_id client identifier
	 * @param string $redirect_uri location for redirection in URI format
	 * @param integer $expires expiration date in UNIX timestamp format for authorization identifier
	 * @param string $scope space spearated allowed scopes for client
	 * @return true if authorization identifier set successfully, otherwise false
	 */
	public function setAuthId($auth_id, $client_id, $redirect_uri, $expires, $scope) {
		$auth_id_record = new AuthIdRecord();
		$auth_id_record->auth_id = $auth_id;
		$auth_id_record->client_id = $client_id;
		$auth_id_record->redirect_uri = $redirect_uri;
		$auth_id_record->expires = $expires;
		$auth_id_record->scope = $scope;
		return $auth_id_record->save();
	}
}

?>
