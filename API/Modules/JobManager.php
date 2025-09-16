<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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
use Prado\Data\ActiveRecord\TActiveRecordCriteria;
use Bacularis\Common\Modules\Miscellaneous;

/**
 * Job manager module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class JobManager extends APIModule
{
	/**
	 * List of allowed properties to use to sort file list.
	 * @see JobManager::getJobFiles()
	 */
	public const ORDER_BY_FILE_LIST_PROPS = ['file', 'size', 'mtime'];

	/**
	 * List of diff methods allowed in diff method.
	 * @see JobManager::getJobFileDiff()
	 */
	public const FILE_DIFF_METHOD_A_AND_B = 'a_and_b'; // full two job diff
	public const FILE_DIFF_METHOD_A_UNTIL_B = 'a_until_b'; // job range
	public const FILE_DIFF_METHOD_B_UNTIL_A = 'b_until_a'; // job range
	public const FILE_DIFF_METHOD_A_NOT_B = 'a_not_b'; // in A but not in B
	public const FILE_DIFF_METHOD_B_NOT_A = 'b_not_a'; // in B but not in A

	public function getJobs($criteria = [], $limit_val = null)
	{
		$sort_col = 'JobId';
		$db_params = $this->getModule('api_config')->getConfig('db');
		if ($db_params['type'] === Database::PGSQL_TYPE) {
			$sort_col = strtolower($sort_col);
		}

		$order = ' ORDER BY ' . $sort_col . ' DESC';
		$limit = '';
		if (is_int($limit_val) && $limit_val > 0) {
			$limit = ' LIMIT ' . $limit_val;
		}

		$where = Database::getWhere($criteria, false, 2);

		$criteria_pn = $criteria;
		if (key_exists('Job.JobId', $criteria_pn)) {
			unset($criteria_pn['Job.JobId']);
		}
		$where_pn = Database::getWhere($criteria_pn, false, 1);

		$add_cols = $this->getAddCols();

		$sql = 'SELECT Job.*, 
pn.prev_jobid AS prev_jobid, 
pn.next_jobid AS next_jobid, 
Client.Name as client, 
Pool.Name as pool, 
FileSet.FileSet as fileset, 
jm.volumename AS firstvol, 
COALESCE(mi.volcount, 0) AS volcount, 
' . $add_cols . '
FROM Job 
LEFT JOIN (
  SELECT jm.JobId AS jobid, m.VolumeName AS volumename
  FROM JobMedia jm
  JOIN (
    SELECT JobId, MIN(JobMediaId) AS min_jmid
    FROM JobMedia
    GROUP BY JobId
  ) first ON first.JobId = jm.JobId AND first.min_jmid = jm.JobMediaId
  JOIN Media m ON m.MediaId = jm.MediaId
) AS jm ON jm.jobid = Job.JobId
LEFT JOIN (
	SELECT
		JobMedia.JobId AS jobid,
		COUNT(DISTINCT MediaId) AS volcount
	FROM
		JobMedia
	GROUP BY JobMedia.JobId
) AS mi ON mi.JobId=Job.JobId 
LEFT JOIN (
	SELECT
		JobId AS jobid,
		LAG(JobId) OVER (ORDER BY JobId) AS prev_jobid,
		LEAD(JobId) OVER (ORDER BY JobId) AS next_jobid
	FROM Job
	LEFT JOIN Client ON Client.ClientId=Job.ClientId 
	LEFT JOIN Pool ON Pool.PoolId=Job.PoolId 
	LEFT JOIN FileSet ON FileSet.FilesetId=Job.FilesetId '
	. $where_pn['where'] . '
) AS pn ON Job.JobId=pn.jobid
LEFT JOIN Client ON Client.ClientId=Job.ClientId 
LEFT JOIN Pool ON Pool.PoolId=Job.PoolId 
LEFT JOIN FileSet ON FileSet.FilesetId=Job.FilesetId '
. $where['where']
. $order
. $limit;
		$wh_params = array_merge($where_pn['params'], $where['params']);
		return Database::findAllBySql($sql, $wh_params);
	}

	private function getAddCols()
	{
		$add_cols = '';
		$db_params = $this->getModule('api_config')->getConfig('db');
		if ($db_params['type'] === Database::PGSQL_TYPE) {
			$add_cols .= '
				CAST(EXTRACT(EPOCH FROM Job.SchedTime) AS INTEGER) AS schedtime_epoch,
				CAST(EXTRACT(EPOCH FROM Job.StartTime) AS INTEGER) AS starttime_epoch,
				CAST(EXTRACT(EPOCH FROM Job.EndTime) AS INTEGER) AS endtime_epoch,
				CAST(EXTRACT(EPOCH FROM Job.RealEndTime) AS INTEGER) AS realendtime_epoch
			';
		} elseif ($db_params['type'] === Database::MYSQL_TYPE) {
			$add_cols .= "
				TIMESTAMPDIFF(SECOND, '1970-01-01 00:00:00', Job.SchedTime) AS schedtime_epoch,
				TIMESTAMPDIFF(SECOND, '1970-01-01 00:00:00', Job.StartTime) AS starttime_epoch,
				TIMESTAMPDIFF(SECOND, '1970-01-01 00:00:00', Job.EndTime) AS endtime_epoch,
				TIMESTAMPDIFF(SECOND, '1970-01-01 00:00:00', Job.RealEndTime) AS realendtime_epoch
			";
		} elseif ($db_params['type'] === Database::SQLITE_TYPE) {
			$add_cols .= '
				strftime(\'%s\', Job.SchedTime) AS schedtime_epoch,
				strftime(\'%s\', Job.StartTime) AS starttime_epoch,
				strftime(\'%s\', Job.EndTime) AS endtime_epoch,
				strftime(\'%s\', Job.RealEndTime) AS realendtime_epoch
			';
		}
		return $add_cols;
	}

	public function getJobById($jobid, $criteria = [])
	{
		$params = [
			'Job.JobId' => [
				'vals' => [$jobid],
				'operator' => 'AND'
			]
		];
		$params = array_merge($params, $criteria);
		$job = $this->getJobs($params, 1);
		if (is_array($job) && count($job) > 0) {
			$job = array_shift($job);
		}
		return $job;
	}

	/**
	 * Find all compojobs required to do full restore.
	 *
	 * @param array $jobs jobid to start searching for jobs
	 * @param null|string $type job type letter selected to find compositional jobids
	 * @return array compositional jobs regarding given jobid
	 */
	private function findCompositionalJobs(array $jobs, ?string $type = null)
	{
		$jobids = [];
		$skip_jobids = [];
		$wait_on_full = false;
		foreach ($jobs as $job) {
			if (in_array($job->jobid, $skip_jobids)) {
				continue;
			}
			if ($job->level == 'F') {
				$jobids[] = $job->jobid;
				break;
			} elseif ($job->level == 'D' && $wait_on_full === false) {
				if ($job->type == 'C') {
					if ($type == 'C') {
						$jobids[] = $job->jobid;
						$skip_jobids[] = $job->priorjobid;
						$wait_on_full = true;
					}
				} else {
					$jobids[] = $job->jobid;
					$wait_on_full = true;
				}
			} elseif ($job->level == 'I' && $wait_on_full === false) {
				if ($job->type == 'C') {
					if ($type == 'C') {
						$jobids[] = $job->jobid;
						$skip_jobids[] = $job->priorjobid;
					}
				} else {
					$jobids[] = $job->jobid;
				}
			}
		}
		return $jobids;
	}

	/**
	 * Get latest recent compositional jobids to do restore.
	 *
	 * @param string $jobname job name
	 * @param int $clientid client identifier
	 * @param int $filesetid fileset identifier
	 * @param bool $inc_copy_job determine if include copy jobs to result
	 * @return array list of jobids required to do restore
	 */
	public function getRecentJobids($jobname, $clientid, $filesetid, $inc_copy_job = false)
	{
		$types = "('B')";
		if ($inc_copy_job) {
			$types = "('B', 'C')";
		}
		$sql = "name='$jobname' AND clientid='$clientid' AND filesetid='$filesetid' AND type IN $types AND jobstatus IN ('T', 'W') AND level IN ('F', 'I', 'D')";
		$finder = JobRecord::finder();
		$criteria = new TActiveRecordCriteria();
		$order1 = 'JobTDate';
		$order2 = 'Type';
		$db_params = $this->getModule('api_config')->getConfig('db');
		if ($db_params['type'] === Database::PGSQL_TYPE) {
			$order1 = strtolower($order1);
			$order2 = strtolower($order2);
		}
		$criteria->OrdersBy[$order1] = 'desc';
		$criteria->OrdersBy[$order2] = 'desc';
		$criteria->Condition = $sql;
		$jobs = $finder->findAll($criteria);

		$jobids = [];
		if (is_array($jobs)) {
			$type = count($jobs) > 0 ? $jobs[0]->type : 'B';
			$jobids = $this->findCompositionalJobs($jobs, $type);
		}
		return $jobids;
	}

	/**
	 * Get compositional jobids to do restore starting from given job (full/incremental/differential).
	 *
	 * @param int $jobid job identifier of last job to do restore
	 * @return array list of jobids required to do restore
	 */
	public function getJobidsToRestore($jobid)
	{
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
				$criteria = new TActiveRecordCriteria();
				$order1 = 'JobTDate';
				$order2 = 'Type';
				$db_params = $this->getModule('api_config')->getConfig('db');
				if ($db_params['type'] === Database::PGSQL_TYPE) {
					$order1 = strtolower($order1);
					$order2 = strtolower($order2);
				}
				$criteria->OrdersBy[$order1] = 'desc';
				$criteria->OrdersBy[$order2] = 'desc'; // for having copy jobids before backup jobids
				$criteria->Condition = $sql;
				$criteria->Parameters[':clientid'] = $bjob->clientid;
				$criteria->Parameters[':filesetid'] = $bjob->filesetid;
				$criteria->Parameters[':starttime'] = $bjob->endtime;
				$criteria->Parameters[':jobid'] = $bjob->jobid;
				$jobs = $finder->findAll($criteria);

				if (is_array($jobs)) {
					$jobids = $this->findCompositionalJobs($jobs, $bjob->type);
				}
			} else {
				$jobids[] = $bjob->jobid;
			}
		}
		return $jobids;
	}

	public function getJobTotals($criteria = [])
	{
		$jobtotals = [
			'job_count' => 0,
			'most_occupied_client' => 0,
			'most_occupied_client_count' => 0,
			'most_occupied_job' => 0,
			'most_occupied_job_count' => 0,
			'most_occupied_pool' => 0,
			'most_occupied_pool_count' => 0,
			'bytes' => 0,
			'files' => 0
		];
		$where = Database::getWhere($criteria);

		/**
		 * NOTE: All SQL queries could be provided in one query.
		 * It could speed up the loading but there is risk that
		 * the query can be too long (many jobs in where clause).
		 * Safely the queries are provided separately.
		 */

		// Job count and total bytes
		$sql = "SELECT
				COUNT(1)          AS job_count,
				SUM(Job.JobFiles) AS files,
				SUM(Job.JobBytes) AS bytes
			FROM
				Job
			{$where['where']}";

		$ret = Database::findAllBySql($sql, $where['params'], PDO::FETCH_ASSOC);
		if (count($ret) == 1) {
			$jobtotals = array_merge($jobtotals, $ret[0]);
		}

		// The most occupied client job stats
		$sql = "SELECT
				Client.Name       AS most_occupied_client,
				COUNT(1)          AS most_occupied_client_count
			FROM
				Job
				JOIN Client USING (ClientId)
			{$where['where']}
			GROUP BY Client.Name
			ORDER BY most_occupied_client_count DESC
			LIMIT 1";
		$ret = Database::findAllBySql($sql, $where['params'], PDO::FETCH_ASSOC);
		if (count($ret) == 1) {
			$jobtotals = array_merge($jobtotals, $ret[0]);
		}

		// The most occupied job stats
		$sql = "SELECT
				Name     AS most_occupied_job,
				COUNT(1) AS most_occupied_job_count
			FROM
				Job
			{$where['where']}
			GROUP BY Name
			ORDER BY most_occupied_job_count DESC
			LIMIT 1";
		$ret = Database::findAllBySql($sql, $where['params'], PDO::FETCH_ASSOC);
		if (count($ret) == 1) {
			$jobtotals = array_merge($jobtotals, $ret[0]);
		}

		// The most occupied pool job stats
		$sql = "SELECT
				Pool.Name AS most_occupied_pool,
				COUNT(1)  AS most_occupied_pool_count
			FROM
				Job
				JOIN Pool USING (PoolId)
			{$where['where']}
			GROUP BY Pool.Name
			ORDER BY most_occupied_pool_count DESC
			LIMIT 1";
		$ret = Database::findAllBySql($sql, $where['params'], PDO::FETCH_ASSOC);
		if (count($ret) == 1) {
			$jobtotals = array_merge($jobtotals, $ret[0]);
		}

		return $jobtotals;
	}

	/**
	 * Get jobs stored on given volume.
	 *
	 * @param string $mediaid volume identifier
	 * @param array $allowed_jobs jobs allowed to show
	 * @return array jobs stored on volume
	 */
	public function getJobsOnVolume($mediaid, $allowed_jobs = [])
	{
		$criteria = [
			'JobMedia.MediaId' => [
				'vals' => $mediaid,
				'operator' => 'AND'
			]
		];
		if (count($allowed_jobs) > 0) {
			$criteria['Job.Name'] = [
				'vals' => $allowed_jobs,
				'operator' => 'IN'
			];
		}
		$where = Database::getWhere($criteria, true);
		$add_cols = $this->getAddCols();

		$sql = "SELECT DISTINCT Job.*, 
Client.Name as client, 
Pool.Name as pool, 
FileSet.FileSet as fileset, 
jm.volumename AS firstvol, 
COALESCE(mi.volcount, 0) AS volcount, 
$add_cols
FROM Job 
LEFT JOIN (
	SELECT
		JobMedia.JobId AS jobid,
		Media.VolumeName AS volumename,
		ROW_NUMBER() OVER (PARTITION BY JobMedia.JobId ORDER BY JobMedia.JobMediaId) AS jmi
	FROM
		Media
	LEFT JOIN
		JobMedia USING (MediaId)
) AS jm ON jm.JobId=Job.JobId AND jm.jmi=1 
LEFT JOIN (
	SELECT
		JobMedia.JobId AS jobid,
		COUNT(DISTINCT MediaId) AS volcount
	FROM
		JobMedia
	GROUP BY JobMedia.JobId
) AS mi ON mi.JobId=Job.JobId 
LEFT JOIN Client USING (ClientId) 
LEFT JOIN Pool USING (PoolId) 
LEFT JOIN FileSet USING (FilesetId) 
LEFT JOIN JobMedia ON JobMedia.JobId=Job.JobId 
WHERE {$where['where']}";
		return Database::findAllBySql($sql, $where['params']);
	}

	/**
	 * Get jobs for given client.
	 *
	 * @param string $clientid client identifier
	 * @param array $allowed_jobs jobs allowed to show
	 * @return array jobs for specific client
	 */
	public function getJobsForClient($clientid, $allowed_jobs = [])
	{
		$criteria = [
			'Client.ClientId' => [
				'vals' => $clientid,
				'operator' => 'AND'
			]
		];
		if (count($allowed_jobs) > 0) {
			$criteria['Job.Name'] = [
				'vals' => $allowed_jobs,
				'operator' => 'IN'
			];
		}
		$where = Database::getWhere($criteria, true);

		$add_cols = $this->getAddCols();

		$sql = "SELECT DISTINCT Job.*, 
Client.Name as client, 
Pool.Name as pool, 
FileSet.FileSet as fileset, 
jm.volumename AS firstvol, 
COALESCE(mi.volcount, 0) AS volcount, 
$add_cols
FROM Job 
LEFT JOIN (
	SELECT
		JobMedia.JobId AS jobid,
		Media.VolumeName AS volumename,
		ROW_NUMBER() OVER (PARTITION BY JobMedia.JobId ORDER BY JobMedia.JobMediaId) AS jmi
	FROM
		Media
	LEFT JOIN
		JobMedia USING (MediaId)
) AS jm ON jm.JobId=Job.JobId AND jm.jmi=1 
LEFT JOIN (
	SELECT
		JobMedia.JobId AS jobid,
		COUNT(DISTINCT MediaId) AS volcount
	FROM
		JobMedia
	GROUP BY JobMedia.JobId
) AS mi ON mi.JobId=Job.JobId 
LEFT JOIN Client USING (ClientId) 
LEFT JOIN Pool USING (PoolId) 
LEFT JOIN FileSet USING (FilesetId) 
WHERE {$where['where']}";
		return Database::findAllBySql($sql, $where['params']);
	}

	/**
	 * Get jobs where specific filename is stored
	 *
	 * @param string $clientid client identifier
	 * @param string $filename filename without path
	 * @param bool $strict_mode if true then it maches exact filename, otherwise with % around filename
	 * @param string $path path to narrow results to one specific path
	 * @param array $allowed_jobs jobs allowed to show
	 * @return array jobs for specific client and filename
	 */
	public function getJobsByFilename($clientid, $filename, $strict_mode = false, $path = '', $allowed_jobs = [])
	{
		$criteria = [];
		if (count($allowed_jobs) > 0) {
			$criteria['Job.Name'] = [
				'vals' => $allowed_jobs,
				'operator' => 'IN'
			];
		}

		if ($strict_mode === false) {
			$filename = '%' . $filename . '%';
		}
		$criteria['File.Filename'] = [
			'vals' => $filename,
			'operator' => 'LIKE'
		];

		if (!empty($path)) {
			$criteria['Path.Path'] = [
				'vals' => $path,
				'operator' => 'AND'
			];
		}
		$where = Database::getWhere($criteria, true);
		$wh = '';
		if (!empty($where['where'])) {
			$wh = ' AND ' . $where['where'];
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
				$wh
		      ORDER BY starttime DESC";
		return Database::findAllBySql($sql, $where['params']);
	}

	/**
	 * Get job file list
	 *
	 * @param int $jobid job identifier
	 * @param string $type file list type: saved, deleted or all.
	 * @param int $offset SQL query offset
	 * @param int $limit SQL query limit
	 * @param array $order sort order properties in form [order_by, order_type]
	 * @param string $search search file keyword
	 * @param mixed $fetch_group
	 * @return array jobs job list
	 */
	public function getJobFiles($jobid, $type, $offset = 0, $limit = 100, $order = [], $search = null, $fetch_group = false)
	{
		$type_crit = '';
		switch ($type) {
			case 'saved': $type_crit = ' AND FileIndex > 0 ';
				break;
			case 'deleted': $type_crit = ' AND FileIndex <= 0 ';
				break;
			case 'all': $type_crit = '';
				break;
			default: $type_crit = ' AND FileIndex > 0 ';
				break;
		}

		$db_params = $this->getModule('api_config')->getConfig('db');
		$search_crit = '';
		if (is_string($search)) {
			$path_col = 'Path.Path';
			$filename_col = 'File.Filename';
			if ($db_params['type'] === Database::MYSQL_TYPE) {
				// Conversion is required because LOWER() and UPPER() do not work with BLOB data type.
				$path_col = "CONVERT($path_col USING utf8mb4)";
				$filename_col = "CONVERT($filename_col USING utf8mb4)";
			}
			$search_crit = " AND (LOWER($path_col) LIKE LOWER('%$search%') OR LOWER($filename_col) LIKE LOWER('%$search%')) ";
		}

		$fname_col = 'Path.Path || File.Filename';
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

		$sort_sql = '';
		$sort_comp = [];
		$add_fields = '';
		$post_sql = '';
		$connection = JobRecord::finder()->getDbConnection();
		$pdo = $connection->getPdoInstance();
		if (is_array($order) && count($order) == 2) {
			$sort_sql = " ORDER BY {$order[0]} {$order[1]} ";
			if ($order[0] != 'file') {
				if ($db_params['type'] == Database::PGSQL_TYPE) {
					// PostgreSQL temporary LStat function
					$pre_sql = self::getCreateDecodeLStatFuncPgSQL();
					$add_fields = '
						pg_temp.decode_lstat(8, F.lstat) AS size, 
						pg_temp.decode_lstat(12, F.lstat) AS mtime, 
					';
					$pdo->exec($pre_sql);
				} elseif ($db_params['type'] == Database::MYSQL_TYPE) {
					// PostgreSQL temporary LStat function
					$pre_sql = self::getCreateDecodeLStatFuncMySQL();
					$post_sql = 'DROP FUNCTION IF EXISTS decode_lstat;';
					$add_fields = '
						decode_lstat(8, F.lstat) AS size, 
						decode_lstat(12, F.lstat) AS mtime, 
					';
					$pdo->exec($pre_sql);

				} elseif ($db_params['type'] == Database::SQLITE_TYPE) {
					// SQLite - no LStat function
					$sort_sql = '';
					$sort_comp = $order;
					$limit_sql = '';
					$offset_sql = '';
				}
			}
		}

		$sql = "SELECT $add_fields
			       $fname_col  AS file, 
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
			$sort_sql $limit_sql $offset_sql";
		$result = [];
		if ($fetch_group) {
			$result = Database::findAllBySql($sql, [], PDO::FETCH_COLUMN);
		} else {
			$result = Database::findAllBySql($sql, [], PDO::FETCH_ASSOC);

			// decode LStat value
			if (is_array($result)) {
				$blstat = $this->getModule('blstat');
				$result_len = count($result);
				for ($i = 0; $i < $result_len; $i++) {
					$result[$i]['lstat'] = $blstat->lstat_human($result[$i]['lstat']);
				}
				if (count($sort_comp) == 2) {
					Miscellaneous::sortByProperty($result, $sort_comp[0], $sort_comp[1], 'lstat');
					$result = array_slice($result, $offset, $limit);
				}
			}
		}
		if ($post_sql) {
			$pdo->exec($post_sql);
		}
		return $result;
	}

	/**
	 * Get two jobids file differences.
	 *
	 * @param string $method difference method
	 * @param string $jobname job name
	 * @param int $start_jobid first job identifier (oldest)
	 * @param int $end_jobid last job identifier (newest)
	 * @return array job file list with differences or empty array if no difference
	 */
	public function getJobFileDiff(string $method, string $jobname, int $start_jobid, int $end_jobid): array
	{
		$db_params = $this->getModule('api_config')->getConfig('db');
		$fname_col = 'Path.Path || File.Filename';
		if ($db_params['type'] === Database::MYSQL_TYPE) {
			$fname_col = 'CONCAT(Path.Path, File.Filename)';
		}

		$sql = '';
		switch ($method) {
			case self::FILE_DIFF_METHOD_A_AND_B: {
				$sql = "SELECT
					$fname_col     	AS file, 
					Job.JobId      	AS jobid,
					'A'		AS type,
					CASE
						WHEN File.FileIndex < 0 THEN 'removed' ELSE 'added'
					END state
				FROM Job 
				INNER JOIN File USING (JobId) 
				INNER JOIN Path USING (PathId) 
				WHERE 
					Job.Name='$jobname' 
					AND Job.JobId=$start_jobid 
				UNION
				SELECT
					$fname_col     AS file, 
					Job.JobId      AS jobid,
					'B'		AS type,
					CASE
						WHEN File.FileIndex < 0 THEN 'removed' ELSE 'added'
					END state
				FROM Job 
				INNER JOIN File USING (JobId) 
				INNER JOIN Path USING (PathId) 
				WHERE 
					Job.Name='$jobname' 
					AND Job.JobId=$end_jobid ";
				break;
			}
			case self::FILE_DIFF_METHOD_A_UNTIL_B: {
				$sql = "SELECT
					$fname_col     	AS file, 
					Job.JobId      	AS jobid,
					'A'		AS type,
					CASE
						WHEN File.FileIndex < 0 THEN 'removed' ELSE 'added'
					END state
				FROM Job 
				INNER JOIN File USING (JobId) 
				INNER JOIN Path USING (PathId) 
				WHERE 
					Job.Name='$jobname' 
					AND Job.JobId>=$start_jobid 
					AND Job.JobId<=$end_jobid ";
				break;
			}
			case self::FILE_DIFF_METHOD_B_UNTIL_A: {
				$sql = "SELECT
					$fname_col     	AS file, 
					Job.JobId      	AS jobid,
					'B'		AS type,
					CASE
						WHEN File.FileIndex < 0 THEN 'removed' ELSE 'added'
					END state
				FROM Job 
				INNER JOIN File USING (JobId) 
				INNER JOIN Path USING (PathId) 
				WHERE 
					Job.Name='$jobname' 
					AND Job.JobId>=$end_jobid 
					AND Job.JobId<=$start_jobid ";
				break;
			}
			case self::FILE_DIFF_METHOD_A_NOT_B: {
				$sql = "SELECT
						$fname_col     	AS file, 
						Job.JobId	AS jobid,
						'A'		AS type,
						CASE
							WHEN File.FileIndex < 0 THEN 'removed' ELSE 'added'
						END state
					FROM Job 
					INNER JOIN File USING (JobId) 
					INNER JOIN Path USING (PathId) 
					WHERE 
						Job.Name='$jobname' 
						AND Job.JobId=$start_jobid 
						AND $fname_col NOT IN ( 
							SELECT $fname_col AS file 
							FROM Job 
							INNER JOIN File USING (JobId) 
							INNER JOIN Path USING (PathId) 
							WHERE 
								Job.Name='$jobname' 
								AND Job.JobId=$end_jobid
						) ";
				break;
			}
			case self::FILE_DIFF_METHOD_B_NOT_A: {
				$sql = "SELECT
						$fname_col     	AS file, 
						Job.JobId	AS jobid,
						'B'		AS type,
						CASE
							WHEN File.FileIndex < 0 THEN 'removed' ELSE 'added'
						END state
					FROM Job 
					INNER JOIN File USING (JobId) 
					INNER JOIN Path USING (PathId) 
					WHERE 
						Job.Name='$jobname' 
						AND Job.JobId=$end_jobid 
						AND $fname_col NOT IN ( 
							SELECT $fname_col AS file 
							FROM Job 
							INNER JOIN File USING (JobId) 
							INNER JOIN Path USING (PathId) 
							WHERE 
								Job.Name='$jobname' 
								AND Job.JobId=$start_jobid
						) ";
				break;
			}
		}
		return Database::findAllBySql($sql, [], PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
	}

	/**
	 * Get SQL command to create temporary function for decoding LStat.
	 * This is version for PostgreSQL.
	 * This function exists only in the current session and is removed on
	 * the session close.
	 *
	 * @return string SQL command
	 */
	private static function getCreateDecodeLStatFuncPgSQL(): string
	{
		return "CREATE FUNCTION pg_temp.decode_lstat(integer, text) RETURNS integer AS $$
	DECLARE
		value integer DEFAULT 0;
		dict char(64) DEFAULT 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
		part varchar(44);
		len integer;
	BEGIN
		part := split_part($2, ' ', $1);
		len := length(part);
		FOR i IN 1..len LOOP
			value := (value << 6);
			value := value + strpos(
				dict,
				substr(part, i, 1)
			) - 1;
		END LOOP;
		RETURN value;
	END;
$$ LANGUAGE plpgsql IMMUTABLE STRICT;
";
	}

	/**
	 * Get SQL command to create temporary function for decoding LStat.
	 * This is version for MySQL.
	 * NOTE: Because in MySQL there is not temporary function support, this function,
	 * is added before using and removed just after.
	 * It means that there is a small probability of occuring colisions if two different users
	 * start sorting at exactly the same time. If it happens, one of sortings can fail
	 * if function is not used at the moment.
	 * It could be solved by adding user-specific suffix to function, but we don't want
	 * to do that.
	 * NOTE: If binary log is enabled there is needed to set log_bin_trust_function_creators=on
	 * in MySQL settings.
	 *
	 * @return string SQL command
	 */
	private static function getCreateDecodeLStatFuncMySQL(): string
	{
		return "
DROP FUNCTION IF EXISTS decode_lstat;
CREATE FUNCTION decode_lstat(fpos TINYINT, lstat BLOB)
RETURNS BIGINT
CONTAINS SQL
DETERMINISTIC
BEGIN
	DECLARE value BIGINT DEFAULT 0;
	DECLARE dict TINYBLOB DEFAULT 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
	DECLARE part VARCHAR(44);
	DECLARE len BIGINT;
	DECLARE i TINYINT DEFAULT 1;
	SET part = SUBSTRING_INDEX(
		SUBSTRING_INDEX(lstat, ' ', fpos),
		' ',
		-1
	);
	SET len = LENGTH(part);
	mloop: REPEAT
		SET value = (value << 6);
		SET value = value + LOCATE(
			SUBSTRING(part, i, 1),
			dict
		) - 1;
		SET i = i + 1;
	UNTIL i > len
	END REPEAT;
	RETURN value;
END;
";
	}
}
