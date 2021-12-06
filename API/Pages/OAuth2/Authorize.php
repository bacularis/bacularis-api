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
 * OAuth2 authorization server.
 * 
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 * @package Baculum API
 */
 
class Authorize extends BaculumAPIPage {

	/**
	 * Request parameter for grant authorization identifier.
	 */
	const RESPONSE_TYPE_CODE = 'code';

	/**
	 * Response fields.
	 */
	const FIELD_CODE = 'code';
	const FIELD_STATE = 'state';

	public function onLoad($param) {
		parent::onLoad($param);
		$oauth2 = $this->getModule('oauth2');

		$is_valid_response_type = (array_key_exists('response_type', $_GET) && $_GET['response_type'] === self::RESPONSE_TYPE_CODE);
		$is_valid_client_id = (array_key_exists('client_id', $_GET) && $oauth2->validateClientId($_GET['client_id']) === true);
		$is_valid_redirect_uri = (array_key_exists('redirect_uri', $_GET) && !empty($_GET['redirect_uri']));
		$is_valid_scope = (array_key_exists('scope', $_GET) && $oauth2->validateScopes($_GET['scope']) === true);
		$is_valid_state = true;
		if (array_key_exists('state', $_GET) && $oauth2->validateState($_GET['state']) === false) {
			$is_valid_state = false;
		}

		if ($is_valid_response_type === false || $is_valid_client_id === false || $is_valid_redirect_uri === false || $is_valid_state === false) {
			$oauth2->authorizationError(
				$oauth2::HEADER_BAD_REQUEST,
				$oauth2::AUTHORIZATION_ERROR_INVALID_REQUEST
			);
			// end action
		}

		if ($is_valid_scope === false) {
			$oauth2->authorizationError(
				$oauth2::HEADER_BAD_REQUEST,
				$oauth2::AUTHORIZATION_ERROR_INVALID_SCOPE
			);
			// end action
		}

		$client = $this->getModule('oauth2_config')->getConfig($_GET['client_id']);
		if(count($client) === 0 || $_GET['redirect_uri'] !== $client['redirect_uri']) {
			$oauth2->authorizationError(
				$oauth2::HEADER_UNAUTHORIZED,
				$oauth2::AUTHORIZATION_ERROR_ACCESS_DENIED
			);
			// end action
		} 

		// deleting expired authorization identifiers
		$this->getModule('oauth2_authid')->deleteExpiredAuthIds();

		// deleting expired tokens
		$this->getModule('oauth2_token')->deleteExpiredTokens();

		// generating new authorization identifier
		$auth_id = $oauth2->generateAuthId();

		// saving new authorization identifier
		$result = $oauth2->setAuthId($auth_id, $_GET['client_id'], $client['redirect_uri'], $client['scope']);

		// redirecting user's application response to 'redirect URI value'
		$uri_params = array(self::FIELD_CODE => $auth_id);
		if (!empty($_GET['state'])) {
			$uri_params[self::FIELD_STATE] = $_GET['state'];
		}
		$oauth2->authorizationRedirect($client['redirect_uri'], $uri_params);
		// end action
	}
}
?>
