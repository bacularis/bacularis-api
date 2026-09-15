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

use Bacularis\Common\Modules\OAuth2;

/**
 * Baculum API specific module with generic methods to support OAuth2.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Authorization
 */
class BaculumOAuth2 extends OAuth2
{
	/**
	 * Validate and normalize an OAuth2 redirect URI.
	 *
	 * HTTP and HTTPS redirect URIs are supported for valid absolute hosts.
	 *
	 * @param string $redirect_uri redirect URI value
	 * @return string|null normalized redirect URI or null if invalid
	 */
	public static function normalizeRedirectURI(string $redirect_uri): ?string
	{
		if ($redirect_uri === '' || preg_match('/[\x00-\x20\x7F]/', $redirect_uri) === 1) {
			return null;
		}
		if (strpos($redirect_uri, '\\') !== false || preg_match('/%(?![0-9A-Fa-f]{2})/', $redirect_uri) === 1) {
			return null;
		}

		$parts = parse_url($redirect_uri);
		if (!is_array($parts) || !key_exists('scheme', $parts) || !key_exists('host', $parts)) {
			return null;
		}
		if (key_exists('user', $parts) || key_exists('pass', $parts) || key_exists('fragment', $parts)) {
			return null;
		}

		$scheme = $parts['scheme'];
		$scheme_lower = strtolower($scheme);
		if ($scheme_lower !== 'https' && $scheme_lower !== 'http') {
			return null;
		}

		$host = $parts['host'];
		$is_ipv6 = strlen($host) > 1 && $host[0] === '[' && substr($host, -1) === ']';
		if ($is_ipv6) {
			$host = substr($host, 1, -1);
		}
		$packed_host = inet_pton($host);
		if ($is_ipv6 && ($packed_host === false || strlen($packed_host) !== 16)) {
			return null;
		}
		if (!$is_ipv6 && $packed_host === false) {
			$is_valid_host = filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
			if ($is_valid_host === false) {
				return null;
			}
		}

		$port = $parts['port'] ?? null;
		if ($port !== null && ($port < 1 || $port > 65535)) {
			return null;
		}
		$path = $parts['path'] ?? '/';
		if ($path === '') {
			$path = '/';
		}
		if ($path[0] !== '/') {
			return null;
		}

		$uri_host = $is_ipv6 ? '[' . $host . ']' : $host;
		$normalized_uri = $scheme . '://' . $uri_host;
		if ($port !== null) {
			$normalized_uri .= ':' . $port;
		}
		$normalized_uri .= $path;
		if (key_exists('query', $parts) && $parts['query'] !== '') {
			$normalized_uri .= '?' . $parts['query'];
		}

		return $normalized_uri;
	}

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
	public function setAuthId($auth_id, $client_id, $redirect_uri, $scope)
	{
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
	public function setTokens($access_token, $refresh_token, $client_id, $expires, $scope, $bconsole_cfg_path)
	{
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
	 * @param mixed $header
	 * @param null|mixed $state
	 */
	public function authorizationError($header, $error_name, $error_description = null, $error_uri = null, $state = null)
	{
		$error = ['error' => $error_name];
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
	 */
	public function authorizationRedirect($redirect_uri, $params = [])
	{
		$redirect_uri = self::normalizeRedirectURI($redirect_uri);
		if ($redirect_uri === null) {
			$this->authorizationError(
				parent::HEADER_BAD_REQUEST,
				parent::AUTHORIZATION_ERROR_INVALID_REQUEST
			);
		}
		header(parent::HEADER_HTTP_FOUND);
		$query = http_build_query($params);
		$uri = $redirect_uri;
		if ($query !== '') {
			$separator = strpos($redirect_uri, '?') === false ? '?' : '&';
			$uri = sprintf('Location: %s%s%s', $redirect_uri, $separator, $query);
		} else {
			$uri = sprintf('Location: %s', $redirect_uri);
		}
		header($uri); // redirection action
		exit();
	}
}
