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
 * Copyright (C) 2013-2020 Kern Sibbald
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

/**
 * Log manager module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class LogManager extends APIModule
{
	/**
	 * Get job log by job identifier.
	 *
	 * @param array $criteria SQL log query criterias
	 * @param array $params query parameters (show time, offset, limit ...etc.)
	 * @return array job log lines
	 */
	public function getLogs($criteria, $params)
	{
		$limit = '';
		if (key_exists('limit', $params) && $params['limit'] > 0) {
			$limit = ' LIMIT ' . $params['limit'];
		}
		$offset = '';
		if ($limit && key_exists('offset', $params) && $params['offset'] > 0) {
			$offset = ' OFFSET ' . $params['offset'];
		}
		$order = '';
		if (key_exists('order_by', $params) && $params['order_by'] && key_exists('order_type', $params) && $params['order_type']) {
			$order_by = $params['order_by'];
			$order_type = $params['order_type'];
			$order = ' ORDER BY ' . $order_by . ' ' . strtoupper($order_type);
		}
		$where = Database::getWhere($criteria);

		$sql = '';
		if (key_exists('show_time', $params) && $params['show_time']) {
			$sql = 'SELECT CONCAT(Log.Time, \' \', Log.LogText) FROM Log';
		} else {
			$sql = 'SELECT Log.LogText FROM Log';
		}
		$sql .= $where['where'] . $order . $limit . $offset;

		return Database::findAllBySql($sql, $where['params'], PDO::FETCH_COLUMN);
	}
}
