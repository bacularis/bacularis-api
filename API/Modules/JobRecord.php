<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2022 Marcin Haba
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

use Bacularis\API\Modules\APIDbModule;

/**
 * Job record module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Database
 */
class JobRecord extends APIDbModule
{
	public const TABLE = 'Job';

	public $jobid;
	public $job;
	public $name;
	public $type;
	public $level;
	public $clientid;
	public $jobstatus;
	public $schedtime;
	public $starttime;
	public $endtime;
	public $realendtime;
	public $jobtdate;
	public $volsessionid;
	public $volsessiontime;
	public $jobfiles;
	public $jobbytes;
	public $readbytes;
	public $joberrors;
	public $jobmissingfiles;
	public $poolid;
	public $filesetid;
	public $priorjobid;
	public $purgedfiles;
	public $hasbase;
	public $hascache;
	public $reviewed;
	public $comment;
	public $filetable;
	public $priorjob;

	// Additional values (not from Job table)
	public $client;
	public $pool;
	public $fileset;

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
