<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2023 Marcin Haba
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

namespace Bacularis\API\Modules;

use Prado\Data\TDbConnection;
use Bacularis\Common\Modules\Errors\DatabaseError;
use Prado\Data\ActiveRecord\TActiveRecord;

/**
 * Base API database module.
 * Every API module that use database connection should inherit this class.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Database
 */
class APIDbModule extends TActiveRecord
{
	/**
	 * Connection to database.
	 *
	 * @var resource
	 */
	private static $db_conn;

	/**
	 * Get Data Source Name (DSN).
	 *
	 * For SQLite params are:
	 * 	array('type' => 'type', 'path' => '/some/system/path');
	 * For others params are:
	 * 	array('type' => 'type', 'name' => 'name', 'host' => 'IP or hostname', 'port' => 'database port');
	 *
	 * @access public
	 * @param array $db_params database connection params
	 * @return string Data Source Name (DSN)
	 */
	public static function getDsn(array $db_params)
	{
		$dsn_params = [];

		if (array_key_exists('path', $db_params) && !empty($db_params['path'])) {
			$dsn_params[] = $db_params['type'] . ':' . $db_params['path'];
		} else {
			$dsn_params[] = $db_params['type'] . ':' . 'dbname=' . $db_params['name'];

			if (array_key_exists('ip_addr', $db_params)) {
				$dsn_params[] = 'host=' . $db_params['ip_addr'];
			}

			if (array_key_exists('port', $db_params)) {
				$dsn_params[] = 'port=' . $db_params['port'];
			}
		}

		$dsn = implode(';', $dsn_params);
		return $dsn;
	}

	public function getDbConnection()
	{
		if (is_null(self::$db_conn)) {
			$config = new APIConfig();
			$db_params = $config->getConfig('db');
			self::$db_conn = self::getAPIDbConnection($db_params);
		}
		return self::$db_conn;
	}

	/**
	 * Get API catalog database connection.
	 *
	 * @access public
	 * @param array database parameters from api config
	 * @param bool force connection try (used when db_params are not saved yet)
	 * @param array $db_params
	 * @param mixed $force
	 * @throws BCatalogExcemption if cataloga access is not supported
	 * @return object TDbConnection instance or null if errors occured during connecting
	 */
	public static function getAPIDbConnection(array $db_params, $force = false)
	{
		$db_connection = null;
		if ((array_key_exists('enabled', $db_params) && $db_params['enabled'] === '1') || $force === true) {
			$dsn = self::getDsn($db_params);
			if (array_key_exists('login', $db_params) && array_key_exists('password', $db_params)) {
				$db_connection = new TDbConnection($dsn, $db_params['login'], $db_params['password']);
			} else {
				$db_connection = new TDbConnection($dsn);
			}
			$db_connection->setActive(true);
			if ($db_params['type'] === Database::MYSQL_TYPE && version_compare(PHP_VERSION, '8.1.0', '<')) {
				/**
				 * Since PHP PDO MySQL 8.1 disabling emulated prepared statements is no longer needed
				 * to get simple type values not as string but as the type values.
				 * @see https://github.com/php/php-src/blob/php-8.1.0RC1/UPGRADING#L130
				 */
				$db_connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
			}
			// It is to not convert non-string types into strings
			$db_connection->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);

			$db_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$db_connection->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
		} else {
			throw new BCatalogException(
				DatabaseError::MSG_ERROR_DATABASE_ACCESS_NOT_SUPPORTED,
				DatabaseError::ERROR_DATABASE_ACCESS_NOT_SUPPORTED
			);
		}
		return $db_connection;
	}

	public function getColumnValue($column_name)
	{
		// column name to lower due to not correct working PDO::CASE_LOWER for SQLite database
		$column_name = strtolower($column_name);
		$value = parent::getColumnValue($column_name);
		return $value;
	}

	/**
	 * Create new column properties dynamically.
	 *
	 * @param string $key property name
	 * @param mixed $value property value
	 */
	public function __set($key, $value)
	{
		$this->{$key} = $value;
	}
}
