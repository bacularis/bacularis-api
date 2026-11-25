<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

namespace Bacularis\API\Modules;

/**
 * Catalog TagJob record, keyed by JobId and Tag.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Database
 */
class TagJobRecord extends APIDbModule
{
	public const TABLE = 'TagJob';

	public $jobid;
	public $tag;

	/**
	 * Get the ActiveRecord finder for tag assignments.
	 *
	 * @param string $className record class name
	 * @return TagJobRecord record finder
	 */
	public static function finder($className = __CLASS__): TagJobRecord
	{
		return parent::finder($className);
	}
}
