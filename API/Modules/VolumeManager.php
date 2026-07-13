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
 * Copyright (C) 2013-2021 Kern Sibbald
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

/**
 * Volume manager module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class VolumeManager extends APIModule
{
	public function getVolumes($criteria = [], $limit_val = 0)
	{
		$order_pool_id = 'PoolId';
		$order_volume = 'VolumeName';
		$db_params = $this->getModule('api_config')->getConfig('db');
		if ($db_params['type'] === Database::PGSQL_TYPE) {
			$order_pool_id = strtolower($order_pool_id);
			$order_volume = strtolower($order_volume);
		}
		$order = " ORDER BY $order_pool_id ASC, $order_volume ASC ";

		$limit = '';
		if (is_int($limit_val) && $limit_val > 0) {
			$limit = " LIMIT $limit_val ";
		}

		$where = Database::getWhere($criteria);

		$add_cols = $this->getAddCols();

		$sql = 'SELECT Media.*, 
pool1.Name as pool, 
pool2.Name as scratchpool, 
pool3.Name as recyclepool, 
Storage.Name as storage, 
' . $add_cols . '
FROM Media 
LEFT JOIN Pool AS pool1 USING (PoolId) 
LEFT JOIN Pool AS pool2 ON Media.ScratchPoolId = pool2.PoolId 
LEFT JOIN Pool AS pool3 ON Media.RecyclePoolId = pool3.PoolId 
LEFT JOIN Storage USING (StorageId) 
' . $where['where'] . $order . $limit;
		$volumes = Database::findAllBySql($sql, $where['params']);
		return $volumes;
	}

	public function getVolumesByPoolId($poolid)
	{
		$volumes = $this->getVolumes([
			'Media.PoolId' => [
				'vals' => [$poolid],
				'operator' => 'AND'
			]
		]);
		return $volumes;
	}

	private function getAddCols()
	{
		$add_cols = '';
		$api_config = $this->getModule('api_config');
		$db_params = $api_config->getConfig('db');
		if ($db_params['type'] === Database::PGSQL_TYPE) {
			$add_cols .= '
				CAST(EXTRACT(EPOCH FROM Media.FirstWritten) AS BIGINT) AS firstwritten_epoch,
				CAST(EXTRACT(EPOCH FROM Media.LastWritten) AS BIGINT) AS lastwritten_epoch,
				CAST(EXTRACT(EPOCH FROM NOW()) - CAST(EXTRACT(EPOCH FROM Media.FirstWritten) AS BIGINT) AS BIGINT) AS firstwritten_ago,
				CAST(EXTRACT(EPOCH FROM NOW()) - CAST(EXTRACT(EPOCH FROM Media.LastWritten) AS BIGINT) AS BIGINT) AS lastwritten_ago,
				CASE
					WHEN Media.VolStatus IN (\'Full\', \'Used\') THEN to_timestamp(CAST(EXTRACT(EPOCH FROM Media.LastWritten) AS BIGINT) + CAST(Media.VolRetention AS BIGINT)) ELSE NULL
				END whenexpire,
				CASE
					WHEN Media.VolStatus IN (\'Full\', \'Used\') THEN CAST(CAST(EXTRACT(EPOCH FROM Media.LastWritten) AS BIGINT) + CAST(Media.VolRetention AS BIGINT) - EXTRACT(EPOCH FROM NOW()) AS BIGINT) ELSE NULL
				END expiresin
			';
		} elseif ($db_params['type'] === Database::MYSQL_TYPE) {
			$add_cols .= '
				TIMESTAMPDIFF(SECOND, \'1970-01-01 00:00:00\', Media.FirstWritten) AS firstwritten_epoch,
				TIMESTAMPDIFF(SECOND, \'1970-01-01 00:00:00\', Media.LastWritten) AS lastwritten_epoch,
				(UNIX_TIMESTAMP() - TIMESTAMPDIFF(SECOND, \'1970-01-01 00:00:00\', Media.FirstWritten)) AS firstwritten_ago,
				(UNIX_TIMESTAMP() - TIMESTAMPDIFF(SECOND, \'1970-01-01 00:00:00\', Media.LastWritten)) AS lastwritten_ago,
				CASE
					WHEN Media.VolStatus IN (\'Full\', \'Used\') THEN FROM_UNIXTIME(TIMESTAMPDIFF(SECOND, \'1970-01-01 00:00:00\', Media.LastWritten) + Media.VolRetention) ELSE NULL
				END whenexpire,
				CASE
					WHEN Media.VolStatus IN (\'Full\', \'Used\') THEN CAST((TIMESTAMPDIFF(SECOND, \'1970-01-01 00:00:00\', Media.LastWritten) + Media.VolRetention) AS SIGNED) - UNIX_TIMESTAMP() ELSE NULL
				END expiresin
			';
		} elseif ($db_params['type'] === Database::SQLITE_TYPE) {
			$add_cols .= '
				strftime(\'%s\', Media.FirstWritten) AS firstwritten_epoch,
				strftime(\'%s\', Media.LastWritten) AS lastwritten_epoch,
				(unixepoch() - strftime(\'%s\', Media.FirstWritten)) AS firstwritten_ago,
				(unixepoch() - strftime(\'%s\', Media.LastWritten)) AS lastwritten_ago,
				CASE
					WHEN Media.VolStatus IN (\'Full\', \'Used\') THEN datetime(strftime(\'%s\', Media.LastWritten) + Media.VolRetention, \'unixepoch\') ELSE NULL
				END whenexpire,
				CASE
					WHEN Media.VolStatus IN (\'Full\', \'Used\') THEN (strftime(\'%s\', Media.LastWritten) + Media.VolRetention) - unixepoch() ELSE NULL
				END expiresin
			';
		}
		return $add_cols;
	}

	public function getVolumeByPoolId($poolid)
	{
		$volume = $this->getVolumes([
			'Media.PoolId' => [
				'vals' => [$poolid],
				'operator' => 'AND'
			]
		], 1);
		if (is_array($volume) && count($volume) > 0) {
			$volume = array_shift($volume);
		}
		return $volume;
	}

	public function getVolumeByName($volume_name)
	{
		$volume = $this->getVolumes([
			'Media.VolumeName' => [
				'vals' => [$volume_name],
				'operator' => 'AND'
			]
		], 1);
		if (is_array($volume) && count($volume) > 0) {
			$volume = array_shift($volume);
		}
		return $volume;
	}

	public function getVolumeById($volume_id)
	{
		$volume = $this->getVolumes([
			'Media.MediaId' => [
				'vals' => [$volume_id],
				'operator' => 'AND'
			]
		]);
		if (is_array($volume) && count($volume) > 0) {
			$volume = array_shift($volume);
		}
		return $volume;
	}

	/**
	 * Get volumes for specific jobid and fileid.
	 *
	 * @param int $jobid job identifier
	 * @param int $fileid file identifier
	 * @return array volumes list
	 */
	public function getVolumesForJob($jobid, $fileid)
	{
		$connection = VolumeRecord::finder()->getDbConnection();
		$connection->setActive(true);
		$sql = sprintf('SELECT first_index, last_index, VolumeName AS volname, InChanger AS inchanger FROM (
		 SELECT VolumeName, InChanger, MIN(FirstIndex) as first_index, MAX(LastIndex) as last_index
		 FROM JobMedia JOIN Media ON (JobMedia.MediaId = Media.MediaId)
		 WHERE JobId = %d GROUP BY VolumeName, InChanger
		) AS gv, File
		 WHERE FileIndex >= first_index
		 AND FileIndex <= last_index
		 AND File.FileId = %d', $jobid, $fileid);
		$pdo = $connection->getPdoInstance();
		$result = $pdo->query($sql);
		$ret = $result->fetchAll();
		$pdo = null;
		$volumes = [];
		if (is_array($ret)) {
			for ($i = 0; $i < count($ret); $i++) {
				$volumes[] = [
					'first_index' => $ret[$i]['first_index'],
					'last_index' => $ret[$i]['last_index'],
					'volume' => $ret[$i]['volname'],
					'inchanger' => $ret[$i]['inchanger']
				];
			}
		}
		return $volumes;
	}

	/**
	 * Get volumes basing on specific criteria and return results as an array
	 * with volume names as keys.
	 *
	 * @param array $criteria array with criterias (@see VolumeManager::getVolumes)
	 * @param int $limit_val limit results value
	 * @return array volume list with volume names as keys
	 */
	public function getVolumesKeys($criteria = [], $limit_val = 0)
	{
		$volumes = [];
		$vols = $this->getVolumes($criteria, $limit_val);
		for ($i = 0; $i < count($vols); $i++) {
			$volumes[$vols[$i]->volumename] = $vols[$i];
		}
		return $volumes;
	}
}
