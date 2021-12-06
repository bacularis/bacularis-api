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

Prado::using('Application.API.Class.OAuth2.TokenRecord');
Prado::using('Application.API.Class.APIModule');

/**
 * Manager for tokens.
 * 
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Authorization
 * @package Baculum API
 */
class TokenManager extends APIModule {

	/**
	 * Get tokens by access token.
	 * 
	 * @access public
	 * @param string $access_token access token value
	 * @return object array or null if no record found.
	 */
	public function getTokens($access_token) {
		return TokenRecord::findByPk($access_token);
	}

	/**
	 * Get tokens by refresh token.
	 * 
	 * @access public
	 * @param string $refresh_token refresh token value
	 * @return object array or null if no record found.
	 */
	public function getTokensByRefreshToken($refresh_token) {
		$result = null;
		$vals = TokenRecord::get();
		for($i = 0; $i < count($vals); $i++) {
			if ($vals[$i]['refresh_token'] === $refresh_token) {
				$result = $vals[$i];
				break;
			}
		}
		return $result;
	}

	/**
	 * Delete expired tokens from database.
	 * 
	 * @access public
	 * @return none
	 */
	public function deleteExpiredTokens() {
		$current_time = time();
		$vals =& TokenRecord::get();
		$vals_len = count($vals);
		for ($i = ($vals_len-1); $i >= 0; $i--) {
			if ($vals[$i]['expires'] < $current_time) {
				array_splice($vals, $i, 1);
			}
		}
	}

	/**
	 * Delete tokens record by access token.
	 * 
	 * @access public
	 * @param string $access_token access token value
	 * @return true if token record deleted successfuly, otherwise false
	 */
	public function deleteToken($access_token) {
		return TokenRecord::deleteByPk($access_token);
	}

	/**
	 * Set tokens properties.
	 * 
	 * NOTE!
	 * It should be used before releasing tokens to client, not after releasing.
	 * 
	 * @public
	 * @param string $access_token access token value
	 * @param string $refresh_token refresh token value
	 * @param string $client_id client identifier
	 * @param integer $expires expiration date in UNIX timestamp for tokens
	 * @param string $scope space spearated allowed scopes for client
	 * @param string $bconsole_cfg_path path to dedicated bconsole config
	 * @return true if tokens set successfully, otherwise false
	 */
	public function setTokens($access_token, $refresh_token, $client_id, $expires, $scope, $bconsole_cfg_path) {
		$token_record = new TokenRecord();
		$token_record->access_token = $access_token;
		$token_record->refresh_token = $refresh_token;
		$token_record->client_id = $client_id;
		$token_record->expires = $expires;
		$token_record->scope = $scope;
		$token_record->bconsole_cfg_path = $bconsole_cfg_path;
		return $token_record->save();
	}
}

?>
