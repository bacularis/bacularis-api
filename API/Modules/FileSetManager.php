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

use Prado\Data\ActiveRecord\TActiveRecordCriteria;

/**
 * FileSet manager module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class FileSetManager extends APIModule
{
	public function getFileSets($limit)
	{
		$criteria = new TActiveRecordCriteria();
		if (is_int($limit) && $limit > 0) {
			$criteria->Limit = $limit;
		}
		return FileSetRecord::finder()->findAll($criteria);
	}

	public function getFileSetsByJob($job)
	{
		$sql = "SELECT DISTINCT FileSet.*
			FROM FileSet
			INNER JOIN Job USING (FileSetId)
			WHERE Job.Name = '$job'";
		return Database::findAllBySql($sql, []);
	}

	public function getFileSetById($id)
	{
		return FileSetRecord::finder()->findByfilesetid($id);
	}

	public function getFileSetByName($name)
	{
		return FileSetRecord::finder()->findByfileset($name);
	}
}
