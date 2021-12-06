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
 
Prado::using('Application.Common.Class.Errors');
Prado::using('Application.API.Class.BAPIException');
Prado::using('Application.API.Class.APIModule');
Prado::using('Application.API.Class.APIDbModule');

/**
 * Database module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Database
 * @package Baculum API
 */
class Database extends APIModule {

	public $ID;

	/**
	 * Supported database types
	 */
	const PGSQL_TYPE = 'pgsql';
	const MYSQL_TYPE = 'mysql';
	const SQLITE_TYPE = 'sqlite';

	/**
	 * Check/test connection to database.
	 * 
	 * @access public
	 * @param array $db_params params to database connection
	 * @return array true if test passed, otherwise false
	 * @throws BCatalogException if problem with connection to database
	 */
	public function testDbConnection(array $db_params) {
		$is_connection = false;
		$tables_format = null;

		try {
			$connection = APIDbModule::getAPIDbConnection($db_params, true);
			$connection->setActive(true);
			$tables_format = $this->getTablesFormat($connection);
			$is_connection = (is_numeric($tables_format) === true && $tables_format > 0);
		} catch (Prado\Exceptions\TDbException $e) {
			throw new BCatalogException(
				DatabaseError::MSG_ERROR_DB_CONNECTION_PROBLEM . ' ' . $e->getErrorMessage(),
				DatabaseError::ERROR_DB_CONNECTION_PROBLEM
			);
		}

		if(array_key_exists('password', $db_params)) {
			// mask database password.
			$db_params['password'] = preg_replace('/.{1}/', '*', $db_params['password']);
		}

		$logmsg = 'DBParams=%s, Connection=%s, TablesFormat=%s';
		$msg = sprintf($logmsg, print_r($db_params, true), var_export($is_connection, true), var_export($tables_format, true));
		$this->getModule('logging')->log(
			__FUNCTION__,
			$msg,
			Logging::CATEGORY_APPLICATION,
			__FILE__,
			__LINE__
		);
		return $is_connection;
	}

	/**
	 * Test connection to the Catalog using params from config.
	 *
	 * @access public
	 * @return bool true if connection established, otherwise false
	 */
	public function testCatalog() {
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
	private function getTablesFormat(TDBConnection $connection) {
		$query = 'SELECT versionid FROM Version';
		$command = $connection->createCommand($query);
		$row = $command->queryRow();
		$tables_format = array_key_exists('versionid', $row) ? $row['versionid'] : null;
		return $tables_format;
	}

	public function getDatabaseSize() {
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
		$dbsize = array('dbsize' => $size, 'dbtype' => $db_params['type']);
		$pdo = null;
		return $dbsize;
	}

	public static function getWhere(array $params, $without_where = false) {
		$where = '';
		$parameters = array();
		if (count($params) > 0) {
			$condition = array();
			foreach ($params as $key => $value) {
				$cond = array();
				$vals = array();
				$kval = str_replace('.', '_', $key);
				if (is_array($value['vals'])) {
					for ($i = 0; $i < count($value['vals']); $i++) {
						$cond[] = "{$key} = :{$kval}{$i}";
						$vals[":{$kval}{$i}"] = $value['vals'][$i];
					}
				} else {
					$cond[] = "$key = :$kval";
					$vals[":$kval"] = $value['vals'];
				}
				$condition[] = implode(' ' . $value['operator'] . ' ', $cond);
				foreach ($vals as $pkey => $pval) {
					$parameters[$pkey] = $pval;
				}
			}
			if (count($condition) > 0) {
				$where = ' (' . implode(') AND (' , $condition) . ')';
				if ($without_where === false)  {
					$where = ' WHERE ' . $where;
				}
			}
		}
		return array('where' => $where, 'params' => $parameters);
	}
}
?>
