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

use PDO;
use Prado\Data\TDbConnection;
use Prado\Data\Common\TDbCommandBuilder;
use Prado\Exceptions\TDbException;
use Bacularis\Common\Modules\Logging;
use Bacularis\Common\Modules\Errors\DatabaseError;

/**
 * Database module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Database
 */
class Database extends APIModule
{
	public $ID;

	/**
	 * Supported database types
	 */
	public const PGSQL_TYPE = 'pgsql';
	public const MYSQL_TYPE = 'mysql';
	public const SQLITE_TYPE = 'sqlite';

	/**
	 * Check/test connection to database.
	 *
	 * @access public
	 * @param array $db_params params to database connection
	 * @throws BCatalogException if problem with connection to database
	 * @return array true if test passed, otherwise false
	 */
	public function testDbConnection(array $db_params)
	{
		$is_connection = false;
		$tables_format = null;

		try {
			$connection = APIDbModule::getAPIDbConnection($db_params, true);
			$connection->setActive(true);
			$tables_format = $this->getTablesFormat($connection);
			$is_connection = (is_numeric($tables_format) === true && $tables_format > 0);
		} catch (TDbException $e) {
			throw new BCatalogException(
				DatabaseError::MSG_ERROR_DB_CONNECTION_PROBLEM . ' ' . $e->getErrorMessage(),
				DatabaseError::ERROR_DB_CONNECTION_PROBLEM
			);
		}

		if (array_key_exists('password', $db_params)) {
			// mask database password.
			$db_params['password'] = preg_replace('/.{1}/', '*', $db_params['password']);
		}

		$logmsg = 'DBParams=%s, Connection=%s, TablesFormat=%s';
		$msg = sprintf(
			$logmsg,
			print_r($db_params, true),
			var_export($is_connection, true),
			var_export($tables_format, true)
		);
		Logging::log(
			Logging::CATEGORY_APPLICATION,
			$msg
		);
		return $is_connection;
	}

	/**
	 * Test connection to the Catalog using params from config.
	 *
	 * @access public
	 * @return bool true if connection established, otherwise false
	 */
	public function testCatalog()
	{
		$result = false;
		$api_config = $this->getModule('api_config')->getConfig();
		if (array_key_exists('db', $api_config)) {
			$result = $this->testDbConnection($api_config['db']);
		} else {
			throw new BCatalogException(
				DatabaseError::MSG_ERROR_DATABASE_ACCESS_NOT_SUPPORTED,
				DatabaseError::ERROR_DATABASE_ACCESS_NOT_SUPPORTED
			);
		}
		return $result;
	}

	/**
	 * Get Catalog database tables format
	 *
	 * @access private
	 * @param TDBConnection $connection handler to database connection
	 * @return mixed Catalog database tables format or null
	 */
	private function getTablesFormat(TDbConnection $connection)
	{
		$query = 'SELECT versionid FROM Version';
		$command = $connection->createCommand($query);
		$row = $command->queryRow();
		$tables_format = array_key_exists('versionid', $row) ? $row['versionid'] : null;
		return $tables_format;
	}

	public function getDatabaseSize()
	{
		$db_params = $this->getModule('api_config')->getConfig('db');

		$connection = APIDbModule::getAPIDbConnection($db_params);
		$connection->setActive(true);
		$pdo = $connection->getPdoInstance();

		$size = 0;
		switch ($db_params['type']) {
			case self::PGSQL_TYPE: {
				$sql = "SELECT pg_database_size('{$db_params['name']}') AS dbsize";
				$result = $pdo->query($sql);
				$row = $result->fetch();
				$size = $row['dbsize'];
				break;
			}
			case self::MYSQL_TYPE: {
				$sql = "SELECT Sum(data_length + index_length) AS dbsize FROM information_schema.tables";
				$result = $pdo->query($sql);
				$row = $result->fetch();
				$size = $row['dbsize'];
				break;
			}
			case self::SQLITE_TYPE: {
				$sql = "PRAGMA page_count";
				$result = $pdo->query($sql);
				$page_count = $result->fetch();
				$sql = "PRAGMA page_size";
				$result = $pdo->query($sql);
				$page_size = $result->fetch();
				$size = ($page_count['page_count'] * $page_size['page_size']);
				break;
			}
		}
		$dbsize = ['dbsize' => $size, 'dbtype' => $db_params['type']];
		$pdo = null;
		return $dbsize;
	}

	public static function getWhere(array $params, $without_where = false, $vars_prefix = '')
	{
		$where = '';
		$parameters = [];
		if (count($params) > 0) {
			$condition = [];
			foreach ($params as $key => $value) {
				$cond = [];
				$vals = [];
				$kval = $vars_prefix . str_replace('.', '_', $key);
				if (is_array($value['vals'])) {
					if ($value['operator'] == 'IN') {
						$in_vals = [];
						for ($i = 0; $i < count($value['vals']); $i++) {
							$in_vals[] = ":{$kval}{$i}";
							$vals[":{$kval}{$i}"] = $value['vals'][$i];
						}
						$cond[] = "{$key} {$value['operator']} (" . implode(',', $in_vals) . ')';
					} else {
						for ($i = 0; $i < count($value['vals']); $i++) {
							$cond[] = "{$key} = :{$kval}{$i}";
							$vals[":{$kval}{$i}"] = $value['vals'][$i];
						}
					}
				} else {
					if ($value['operator'] == 'LIKE') {
						$cond[] = "$key LIKE :$kval";
						$vals[":$kval"] = $value['vals'];
					} elseif (in_array($value['operator'], ['>', '<', '>=', '<='])) {
						$cond[] = "{$key} {$value['operator']} :{$kval}";
						$vals[":{$kval}"] = $value['vals'];
					} else {
						$cond[] = "$key = :$kval";
						$vals[":$kval"] = $value['vals'];
					}
				}
				$condition[] = implode(' ' . $value['operator'] . ' ', $cond);
				foreach ($vals as $pkey => $pval) {
					$parameters[$pkey] = $pval;
				}
			}
			if (count($condition) > 0) {
				$where = ' (' . implode(') AND (', $condition) . ')';
				if ($without_where === false) {
					$where = ' WHERE ' . $where;
				}
			}
		}
		return ['where' => $where, 'params' => $parameters];
	}

	/**
	 * Find all database query results by SQL query.
	 *
	 * @param string $sql SQL query
	 * @param array $params SQL query parameters
	 * @param int $fetch_opts PDO fetch options
	 * @return array with results or empty array if no result
	 */
	public static function findAllBySql($sql, $params, $fetch_opts = null)
	{
		if (is_null($fetch_opts)) {
			$fetch_opts = PDO::FETCH_OBJ;
		}
		$connection = JobRecord::finder()->getDbConnection();
		$pdo = $connection->getPdoInstance();
		if (count($params) > 0) {
			$statement = $pdo->prepare($sql);
			foreach ($params as $param => $value) {
				$key = $param[0] === ':' ? $param : ':' . $param;
				$type = TDbCommandBuilder::getPdoType($value);
				$statement->bindValue($key, $value, $type);
			}
			$statement->execute();
		} else {
			$statement = $pdo->query($sql);
		}
		return $statement->fetchAll($fetch_opts);
	}
}
