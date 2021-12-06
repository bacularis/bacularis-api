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

Prado::using('Application.API.Class.BaculumAPIPage');

/**
 * OAuth2 authorization server - request token part.
 * 
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
 
class RequestToken extends BaculumAPIPage {

	/**
	 * Request parameter for get access token.
	 */
	const REQUEST_TYPE_AUTHORIZATION_CODE = 'authorization_code';

	/**
	 * Response fields.
	 */
	const FIELD_ACCESS_TOKEN = 'access_token';
	const FIELD_REFRESH_TOKEN = 'refresh_token';
	const FIELD_TOKEN_TYPE = 'token_type';
	const FIELD_EXPIRES_IN = 'expires_in';

	public function onLoad($param) {
		parent::onLoad($param);
		$oauth2 = $this->getModule('oauth2');

		$is_valid_grant_type = (array_key_exists('grant_type', $_POST) && $_POST['grant_type'] === self::REQUEST_TYPE_AUTHORIZATION_CODE);
		$is_valid_code = (array_key_exists('code', $_POST) && $oauth2->validateAuthId($_POST['code']) === true);
		$is_valid_redirect_uri = (array_key_exists('redirect_uri', $_POST) && !empty($_POST['redirect_uri']));
		$is_valid_client_id = (array_key_exists('client_id', $_POST) && $oauth2->validateClientId($_POST['client_id']) === true);
		$is_valid_client_secret = (array_key_exists('client_secret', $_POST) && $oauth2->validateClientSecret($_POST['client_secret']) === true);
		if ($is_valid_grant_type === false) {
			$oauth2->authorizationError(
				$oauth2::HEADER_BAD_REQUEST,
				$oauth2::AUTHORIZATION_ERROR_UNSUPPORTED_GRANT_TYPE
			);
			// end action
		}
		if ($is_valid_code === false || $is_valid_redirect_uri === false || $is_valid_client_id === false || $is_valid_client_secret === false) {
			$oauth2->authorizationError(
				$oauth2::HEADER_BAD_REQUEST,
				$oauth2::AUTHORIZATION_ERROR_INVALID_REQUEST
			);
			// end action
		}

		$client = $this->getModule('oauth2_config')->getConfig($_POST['client_id']);
		if (count($client) === 0 || $client['client_secret'] !== $_POST['client_secret'] || $client['redirect_uri'] !== $_POST['redirect_uri']) {
			$oauth2->authorizationError(
				$oauth2::HEADER_BAD_REQUEST,
				$oauth2::AUTHORIZATION_ERROR_INVALID_CLIENT
			);
			// end action
		}

		/**
		 * Delete expired authorization identifiers.
		 * It is neccessary because it may exist some authorization identifiers
		 * which were not sent to server (eg. broken authorization request).
		 */
		$this->getModule('oauth2_authid')->deleteExpiredAuthIds();

		$auth_id = $this->getModule('oauth2_authid')->getAuthId($_POST['code']);
		if ($auth_id === null || $auth_id['client_id'] !== $client['client_id']) {
			$oauth2->authorizationError(
				$oauth2::HEADER_BAD_REQUEST,
				$oauth2::AUTHORIZATION_ERROR_INVALID_GRANT
			);
			// end action
		}

		/**
		 * Delete current authorization identifier.
		 * It is necessary, because of each authorization
		 * identifier MUST BE used only once.
		 */
		$result = $this->getModule('oauth2_authid')->deleteAuthId($auth_id['auth_id']);
		if ($result === false) {
			$oauth2->authorizationError(
				$oauth2::HEADER_BAD_REQUEST,
				$oauth2::AUTHORIZATION_ERROR_SERVER_ERROR
			);
			// end action
		}

		// deleting expired tokens
		$this->getModule('oauth2_token')->deleteExpiredTokens();

		// generating both tokens (access token and refresh token)
		$access_token = $oauth2->generateAccessToken();
		$refresh_token = $oauth2->generateRefreshToken();

		$expires_in = $oauth2::ACCESS_TOKEN_EXPIRES_TIME;


		// saving tokens
		$oauth2->setTokens(
			$access_token,
			$refresh_token,
			$client['client_id'],
			$expires_in,
			$client['scope'],
			$client['bconsole_cfg_path']
		);

		$token_data = array(
			self::FIELD_ACCESS_TOKEN => $access_token,
			self::FIELD_REFRESH_TOKEN => $refresh_token,
			self::FIELD_TOKEN_TYPE => 'Bearer',
			self::FIELD_EXPIRES_IN => $expires_in
		);
		echo json_encode($token_data);
		// end action
	}
}
?>
