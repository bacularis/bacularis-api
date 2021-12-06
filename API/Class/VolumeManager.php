<?php
/*
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

Prado::using('Application.API.Class.APIModule');
Prado::using('Application.API.Class.VolumeRecord');
Prado::using('Application.API.Class.Database');

/**
 * Volume manager module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum API
 */
class VolumeManager extends APIModule {

	public function getVolumes($criteria = array(), $limit_val = 0) {
		$order_pool_id = 'PoolId';
		$order_volume = 'VolumeName';
		$db_params = $this->getModule('api_config')->getConfig('db');
		if($db_params['type'] === Database::PGSQL_TYPE) {
		    $order_pool_id = strtolower($order_pool_id);
		    $order_volume = strtolower($order_volume);
		}
		$order = " ORDER BY $order_pool_id ASC, $order_volume ASC ";

		$limit = '';
		if(is_int($limit_val) && $limit_val > 0) {
			$limit = " LIMIT $limit_val ";
		}

		$where = Database::getWhere($criteria);

		$sql = 'SELECT Media.*, 
pool1.Name as pool, 
pool2.Name as scratchpool, 
pool3.Name as recyclepool, 
Storage.Name as storage 
FROM Media 
LEFT JOIN Pool AS pool1 USING (PoolId) 
LEFT JOIN Pool AS pool2 ON Media.ScratchPoolId = pool2.PoolId 
LEFT JOIN Pool AS pool3 ON Media.RecyclePoolId = pool3.PoolId 
LEFT JOIN Storage USING (StorageId) 
' . $where['where'] . $order . $limit;
		$volumes = VolumeRecord::finder()->findAllBySql($sql, $where['params']);
		$this->setExtraVariables($volumes);
		return $volumes;
	}

	public function getVolumesByPoolId($poolid) {
		$volumes = $this->getVolumes(array(
			'Media.PoolId' => array(
				'vals' => array($poolid),
				'operator' => 'AND'
			)
		));
		$this->setExtraVariables($volumes);
		return $volumes;
	}

	public function getVolumeByPoolId($poolid) {
		$volume = $this->getVolumes(array(
			'Media.PoolId' => array(
				'vals' => array($poolid),
				'operator' => 'AND'
			)
		), 1);
		if (is_array($volume) && count($volume) > 0) {
			$volume = array_shift($volume);
		}
		$this->setExtraVariables($volume);
		return $volume;
	}

	public function getVolumeByName($volume_name) {
		$volume = $this->getVolumes(array(
			'Media.VolumeName' => array(
				'vals' => array($volume_name),
				'operator' => 'AND'
			)
		), 1);
		if (is_array($volume) && count($volume) > 0) {
			$volume = array_shift($volume);
		}
		$this->setExtraVariables($volume);
		return $volume;
	}

	public function getVolumeById($volume_id) {
		$volume = $this->getVolumes(array(
			'Media.MediaId' => array(
				'vals' => array($volume_id),
				'operator' => 'AND'
			)
		));
		if (is_array($volume) && count($volume) > 0) {
			$volume = array_shift($volume);
		}
		$this->setExtraVariables($volume);
		return $volume;
	}

	private function setExtraVariables(&$volumes) {
		if (is_array($volumes)) {
			foreach($volumes as $volume) {
				$this->setWhenExpire($volume);
			}
		} elseif (is_object($volumes)) {
			$this->setWhenExpire($volumes);
		}
	}

	private function setWhenExpire(&$volume) {
		$volstatus = strtolower($volume->volstatus);
		if ($volstatus == 'full' || $volstatus == 'used') {
			$whenexpire = strtotime($volume->lastwritten) + $volume->volretention;
			$whenexpire = date( 'Y-m-d H:i:s', $whenexpire);
		} else{
			$whenexpire = 'no date';
		}
		$volume->whenexpire = $whenexpire;
	}

	/**
	 * Get volumes for specific jobid and fileid.
	 *
	 * @param integer $jobid job identifier
	 * @param integer $fileid file identifier
	 * @return array volumes list
	 */
	public function getVolumesForJob($jobid, $fileid) {
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
		$volumes = array();
		if (is_array($ret)) {
			for ($i = 0; $i < count($ret); $i++) {
				$volumes[] = array(
					'first_index' => $ret[$i]['first_index'],
					'last_index' => $ret[$i]['last_index'],
					'volume' => $ret[$i]['volname'],
					'inchanger' => $ret[$i]['inchanger']
				);
			}
		}
		return $volumes;
	}

	/**
	 * Get volumes basing on specific criteria and return results as an array
	 * with volume names as keys.
	 *
	 * @param array $criteria array with criterias (@see VolumeManager::getVolumes)
	 * @param integer $limit_val limit results value
	 * @return array volume list with volume names as keys
	 */
	public function getVolumesKeys($criteria = array(), $limit_val = 0) {
		$volumes = [];
		$vols = $this->getVolumes($criteria, $limit_val);
		for ($i = 0; $i < count($vols); $i++) {
			$volumes[$vols[$i]->volumename] = $vols[$i];
		}
		return $volumes;
	}
}
?>
