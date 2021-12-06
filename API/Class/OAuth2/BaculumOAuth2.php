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

Prado::using('Application.Common.Class.OAuth2');

/**
 * Baculum API specific module with generic methods to support OAuth2.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Authorization
 * @package Baculum API
 */
class BaculumOAuth2 extends OAuth2 {

	/**
	 * Set authorization identifier (authorization code).
	 * 
	 * NOTE!
	 * It should be using before releasing autorization identifier to client, not after releasing.
	 * 
	 * @public
	 * @param string $auth_id authorization identifier
	 * @param string $client_id client identifier
	 * @param string $redirect_uri location for redirection
	 * @param string $scope space spearated allowed scopes for client
	 * @return true if authorization identifier set successfully, otherwise false
	 */
	public function setAuthId($auth_id, $client_id, $redirect_uri, $scope) {
		$expires = time() + parent::AUTHORIZATION_ID_EXPIRES_TIME;
		$result = $this->getModule('oauth2_authid')->setAuthId($auth_id, $client_id, $redirect_uri, $expires, $scope);
		return $result;
	}

	/**
	 * Set tokens (access token and refresh token).
	 * 
	 * @access public
	 * @param string $access_token access token value
	 * @param string $refresh_token refresh token value
	 * @param string $client_id client's identifier
	 * @param string $expires tokens expiration time
	 * @param string $scope scope assigned to tokens
	 * @param string $bconsole_cfg_path dedicated bconsole config file path
	 * @return true if tokens set properly, otherwise false
	 */
	public function setTokens($access_token, $refresh_token, $client_id, $expires, $scope, $bconsole_cfg_path) {
		$expires = time() + parent::ACCESS_TOKEN_EXPIRES_TIME;
		$result = $this->getModule('oauth2_token')->setTokens(
			$access_token,
			$refresh_token,
			$client_id,
			$expires,
			$scope,
			$bconsole_cfg_path
		);
		return $result;
	}

	/**
	 * Create error output for client.
	 * 
	 * NOTE!
	 * The method does not return any value.
	 * As result value is returned directly on standard output in JSON format compatible with RFC6749.
	 * Next all actions all stoped (die() occured)
	 * 
	 * @access public
	 * @param string $error_name error name
	 * @param string $error_description human-readable error description
	 * @param string $error_uri page location where client is able to get help for returned error
	 * @return none 
	 */
	public function authorizationError($header, $error_name, $error_description = null, $error_uri = null, $state = null) {
		$error = array('error' => $error_name);
		if (!is_null($error_description)) {
			$error['error_description'] = $error_description;
		}

		if ($error_uri != null) {
			$error['error_uri'] = $error_uri;
		}

		if (!is_null($state)) {
			$error['state'] = $state;
		}

		header('Content-Type: application/json');
		header($header);
		echo json_encode($error);
		exit();
	}

	/**
	 * HTTP 302 redirection to 'redirect_uri' client's location.
	 * 
	 * @see http://tools.ietf.org/html/rfc6749#section-4.1.2
	 * 
	 * @access public
	 * @param string $redirect_uri uniform resource identifier (URI)
	 * @param array $params GET parameters for redirect_uri contained in associative array
	 * @return none
	 */
	public function authorizationRedirect($redirect_uri, $params = array()) {
		header(parent::HEADER_HTTP_FOUND);
		$uri = sprintf('Location: %s?%s', $redirect_uri, http_build_query($params));
		header($uri); // redirection action
		exit();
	}
}
?>
