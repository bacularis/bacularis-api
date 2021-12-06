<?php
/*
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

Prado::using('Application.API.Class.APIModule');
Prado::using('Application.API.Class.JobRecord');
Prado::using('Application.API.Class.Database');

/**
 * Job manager module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum API
 */
class JobManager extends APIModule {

	public function getJobs($criteria = array(), $limit_val) {
		$sort_col = 'JobId';
		$db_params = $this->getModule('api_config')->getConfig('db');
		if ($db_params['type'] === Database::PGSQL_TYPE) {
		    $sort_col = strtolower($sort_col);
		}
		$order = ' ORDER BY ' . $sort_col . ' DESC';
		$limit = '';
		if(is_int($limit_val) && $limit_val > 0) {
			$limit = ' LIMIT ' . $limit_val;
		}

		$where = Database::getWhere($criteria);

		$sql = 'SELECT Job.*, 
Client.Name as client, 
Pool.Name as pool, 
FileSet.FileSet as fileset 
FROM Job 
LEFT JOIN Client USING (ClientId) 
LEFT JOIN Pool USING (PoolId) 
LEFT JOIN FileSet USING (FilesetId)'
. $where['where'] . $order . $limit;

		return JobRecord::finder()->findAllBySql($sql, $where['params']);
	}

	public function getJobById($jobid) {
		$job = $this->getJobs(array(
			'Job.JobId' => array(
				'vals' => array($jobid),
				'operator' => 'AND'
			)
		), 1);
		if (is_array($job) && count($job) > 0) {
			$job = array_shift($job);
		}
		return $job;
	}

	/**
	 * Find all compojobs required to do full restore.
	 *
	 * @param array $jobs jobid to start searching for jobs
	 * @return array compositional jobs regarding given jobid
	 */
	private function findCompositionalJobs(array $jobs) {
		$jobids = [];
		$wait_on_full = false;
		foreach($jobs as $job) {
			if($job->level == 'F') {
				$jobids[] = $job->jobid;
				break;
			} elseif($job->level == 'D' && $wait_on_full === false) {
				$jobids[] = $job->jobid;
				$wait_on_full = true;
			} elseif($job->level == 'I' && $wait_on_full === false) {
				$jobids[] = $job->jobid;
			}
		}
		return $jobids;
	}

	/**
	 * Get latest recent compositional jobids to do restore.
	 *
	 * @param string $jobname job name
	 * @param integer $clientid client identifier
	 * @param integer $filesetid fileset identifier
	 * @param boolean $inc_copy_job determine if include copy jobs to result
	 * @return array list of jobids required to do restore
	 */
	public function getRecentJobids($jobname, $clientid, $filesetid, $inc_copy_job = false) {
		$types = "('B')";
		if ($inc_copy_job) {
			$types = "('B', 'C')";
		}
		$sql = "name='$jobname' AND clientid='$clientid' AND filesetid='$filesetid' AND type IN $types AND jobstatus IN ('T', 'W') AND level IN ('F', 'I', 'D')";
		$finder = JobRecord::finder();
		$criteria = new TActiveRecordCriteria;
		$order1 = 'RealEndTime';
		$order2 = 'JobId';
		$db_params = $this->getModule('api_config')->getConfig('db');
		if ($db_params['type'] === Database::PGSQL_TYPE) {
		    $order1 = strtolower($order1);
		    $order2 = strtolower($order2);
		}
		$criteria->OrdersBy[$order1] = 'desc';
		$criteria->OrdersBy[$order2] = 'desc';
		$criteria->Condition = $sql;
		$jobs = $finder->findAll($criteria);

		$jobids = array();
		if(is_array($jobs)) {
			$jobids = $this->findCompositionalJobs($jobs);
		}
		return $jobids;
	}

	/**
	 * Get compositional jobids to do restore starting from given job (full/incremental/differential).
	 *
	 * @param integer $jobid job identifier of last job to do restore
	 * @return array list of jobids required to do restore
	 */
	public function getJobidsToRestore($jobid) {
		$jobids = [];
		$bjob = JobRecord::finder()->findBySql(
			"SELECT * FROM Job WHERE jobid = '$jobid' AND jobstatus IN ('T', 'W') AND type IN ('B', 'C') AND level IN ('F', 'I', 'D')"
		);
		if (is_object($bjob)) {
			if ($bjob->level != 'F') {
				$sql = "clientid=:clientid AND filesetid=:filesetid AND type IN ('B', 'C')" .
					" AND jobstatus IN ('T', 'W') AND level IN ('F', 'I', 'D') " .
					" AND starttime <= :starttime and jobid <= :jobid";
				$finder = JobRecord::finder();
				$criteria = new TActiveRecordCriteria;
				$order1 = 'JobId';
				$db_params = $this->getModule('api_config')->getConfig('db');
				if ($db_params['type'] === Database::PGSQL_TYPE) {
					$order1 = strtolower($order1);
				}
				$criteria->OrdersBy[$order1] = 'desc';
				$criteria->Condition = $sql;
				$criteria->Parameters[':clientid'] = $bjob->clientid;
				$criteria->Parameters[':filesetid'] = $bjob->filesetid;
				$criteria->Parameters[':starttime'] = $bjob->endtime;
				$criteria->Parameters[':jobid'] = $bjob->jobid;
				$jobs = $finder->findAll($criteria);

				if(is_array($jobs)) {
					$jobids = $this->findCompositionalJobs($jobs);
				}
			} else {
				$jobids[] = $bjob->jobid;
			}
		}
		return $jobids;
	}

	public function getJobTotals($allowed_jobs = array()) {
		$jobtotals = array('bytes' => 0, 'files' => 0);
		$connection = JobRecord::finder()->getDbConnection();
		$connection->setActive(true);

		$where = '';
		if (count($allowed_jobs) > 0) {
			$where = " WHERE name='" . implode("' OR name='", $allowed_jobs) . "'";
		}

		$sql = "SELECT sum(JobFiles) AS files, sum(JobBytes) AS bytes FROM Job $where";
		$pdo = $connection->getPdoInstance();
		$result = $pdo->query($sql);
		$ret = $result->fetch();
		$jobtotals['bytes'] = $ret['bytes'];
		$jobtotals['files'] = $ret['files'];
		$pdo = null;
		return $jobtotals;
	}

	/**
	 * Get jobs stored on given volume.
	 *
	 * @param string $mediaid volume identifier
	 * @param array $allowed_jobs jobs allowed to show
	 * @return array jobs stored on volume
	 */
	public function getJobsOnVolume($mediaid, $allowed_jobs = array()) {
		$jobs_criteria = '';
		if (count($allowed_jobs) > 0) {
			$jobs_sql = implode("', '", $allowed_jobs);
			$jobs_criteria = " AND Job.Name IN ('" . $jobs_sql . "')";
		}
		$sql = "SELECT DISTINCT Job.*, 
Client.Name as client, 
Pool.Name as pool, 
FileSet.FileSet as fileset 
FROM Job 
LEFT JOIN Client USING (ClientId) 
LEFT JOIN Pool USING (PoolId) 
LEFT JOIN FileSet USING (FilesetId) 
LEFT JOIN JobMedia USING (JobId) 
WHERE JobMedia.MediaId='$mediaid' $jobs_criteria";
		return JobRecord::finder()->findAllBySql($sql);
	}

	/**
	 * Get jobs for given client.
	 *
	 * @param string $clientid client identifier
	 * @param array $allowed_jobs jobs allowed to show
	 * @return array jobs for specific client
	 */
	public function getJobsForClient($clientid, $allowed_jobs = array()) {
		$where = '';
		if (count($allowed_jobs) > 0) {
			$criteria = [
				'Job.Name' => [
					'vals' => $allowed_jobs,
					'operator' => 'OR'
				]
			];
			$where = Database::getWhere($criteria, true);
			$wh = '';
			if (count($where['params']) > 0) {
				$wh = ' AND ' . $where['where'];
			}
		}
		$sql = "SELECT DISTINCT Job.*, 
Client.Name as client, 
Pool.Name as pool, 
FileSet.FileSet as fileset 
FROM Job 
LEFT JOIN Client USING (ClientId) 
LEFT JOIN Pool USING (PoolId) 
LEFT JOIN FileSet USING (FilesetId) 
WHERE Client.ClientId='$clientid' $wh";
		return JobRecord::finder()->findAllBySql($sql, $where['params']);
	}

	/**
	 * Get jobs where specific filename is stored
	 *
	 * @param string $clientid client identifier
	 * @param string $filename filename without path
	 * @param boolean $strict_mode if true then it maches exact filename, otherwise with % around filename
	 * @param string $path path to narrow results to one specific path
	 * @param array $allowed_jobs jobs allowed to show
	 * @return array jobs for specific client and filename
	 */
	public function getJobsByFilename($clientid, $filename, $strict_mode = false, $path = '', $allowed_jobs = array()) {
		$jobs_criteria = '';
		if (count($allowed_jobs) > 0) {
			$jobs_sql = implode("', '", $allowed_jobs);
			$jobs_criteria = " AND Job.Name IN ('" . $jobs_sql . "')";
		}

		if ($strict_mode === false) {
			$filename = '%' . $filename . '%';
		}

		$path_criteria = '';
		if (!empty($path)) {
			$path_criteria = ' AND Path.Path = :path ';
		}

		$fname_col = 'Path.Path || File.Filename';
		$db_params = $this->getModule('api_config')->getConfig('db');
		if ($db_params['type'] === Database::MYSQL_TYPE) {
			$fname_col = 'CONCAT(Path.Path, File.Filename)';
		}

		$sql = "SELECT Job.JobId AS jobid,
                               Job.Name AS name,
                               $fname_col AS file,
                               Job.StartTime AS starttime,
                               Job.EndTime AS endtime,
                               Job.Type AS type,
                               Job.Level AS level,
                               Job.JobStatus AS jobstatus,
                               Job.JobFiles AS jobfiles,
                               Job.JobBytes AS jobbytes 
                      FROM Client, Job, File, Path 
                      WHERE Client.ClientId='$clientid' 
                            AND Client.ClientId=Job.ClientId 
                            AND Job.JobId=File.JobId 
                            AND File.FileIndex > 0 
                            AND Path.PathId=File.PathId 
                            AND File.Filename LIKE :filename 
		      $jobs_criteria 
		      $path_criteria 
		      ORDER BY starttime DESC";
		$connection = JobRecord::finder()->getDbConnection();
		$connection->setActive(true);
		$pdo = $connection->getPdoInstance();
		$sth = $pdo->prepare($sql);
		$sth->bindParam(':filename', $filename, PDO::PARAM_STR, 200);
		if (!empty($path)) {
			$sth->bindParam(':path', $path, PDO::PARAM_STR, 400);
		}
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Get job file list
	 *
	 * @param integer $jobid job identifier
	 * @param string $type file list type: saved, deleted or all.
	 * @param integer $offset SQL query offset
	 * @param integer $limit SQL query limit
	 * @param string $search search file keyword
	 * @return array jobs job list
	 */
	public function getJobFiles($jobid, $type, $offset = 0, $limit = 100, $search = null, $fetch_group = false) {
		$type_crit = '';
		switch ($type) {
			case 'saved': $type_crit = ' AND FileIndex > 0 '; break;
			case 'deleted': $type_crit = ' AND FileIndex <= 0 '; break;
			case 'all': $type_crit = ''; break;
			default: $type_crit = ' AND FileIndex > 0 '; break;
		}

		$search_crit = '';
		if (is_string($search)) {
			$search_crit = " AND (LOWER(Path.Path) LIKE LOWER('%$search%') OR LOWER(File.Filename) LIKE LOWER('%$search%')) ";
		}

		$fname_col = 'Path.Path || File.Filename';
		$db_params = $this->getModule('api_config')->getConfig('db');
		if ($db_params['type'] === Database::MYSQL_TYPE) {
			$fname_col = 'CONCAT(Path.Path, File.Filename)';
		}

		$limit_sql = '';
		if ($limit) {
			$limit_sql = ' LIMIT ' . $limit;
		}

		$offset_sql = '';
		if ($offset) {
			$offset_sql = ' OFFSET ' . $offset;
		}

		$sql = "SELECT $fname_col  AS file, 
                               F.lstat     AS lstat, 
                               F.fileindex AS fileindex 
                        FROM ( 
                            SELECT PathId     AS pathid, 
                                   Lstat      AS lstat, 
                                   FileIndex  AS fileindex, 
                                   FileId     AS fileid 
                            FROM 
                                File 
                            WHERE 
                                JobId=$jobid 
                                $type_crit 
                            UNION ALL 
                            SELECT PathId         AS pathid, 
                                   File.Lstat     AS lstat, 
                                   File.FileIndex AS fileindex, 
                                   File.FileId    AS fileid 
                                FROM BaseFiles 
                                JOIN File ON (BaseFiles.FileId = File.FileId) 
                                WHERE 
                                   BaseFiles.JobId = $jobid 
                        ) AS F, File, Path 
                        WHERE File.FileId = F.FileId AND Path.PathId = F.PathId 
                        $search_crit 
			$limit_sql $offset_sql";
		$connection = JobRecord::finder()->getDbConnection();
		$connection->setActive(true);
		$pdo = $connection->getPdoInstance();
		$sth = $pdo->prepare($sql);
		$sth->execute();
		$result = [];
		if ($fetch_group) {
			$result = $sth->fetchAll(PDO::FETCH_COLUMN);
		} else {
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);

			// decode LStat value
			if (is_array($result)) {
				$blstat = $this->getModule('blstat');
				$result_len = count($result);
				for ($i = 0; $i < $result_len; $i++) {
					$result[$i]['lstat'] = $blstat->lstat_human($result[$i]['lstat']);
				}
			}
		}
		return $result;
	}
}
?>
