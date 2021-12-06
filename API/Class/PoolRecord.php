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

Prado::using('Application.API.Class.APIDbModule');

/**
 * Pool record module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Database
 * @package Baculum API
 */
class PoolRecord extends APIDbModule {
	const TABLE = 'Pool';

	public $poolid;
	public $name;
	public $numvols;
	public $maxvols;
	public $useonce;
	public $usecatalog;
	public $acceptanyvolume;
	public $volretention;
	public $voluseduration;
	public $maxvoljobs;
	public $maxvolfiles;
	public $maxvolbytes;
	public $autoprune;
	public $recycle;
	public $actiononpurge;
	public $pooltype;
	public $labeltype;
	public $labelformat;
	public $enabled;
	public $scratchpoolid;
	public $recyclepoolid;
	public $nextpoolid;
	public $migrationhighbytes;
	public $migrationlowbytes;
	public $migrationtime;
	public $cacheretention;
	public $maxpoolbytes;

	public static function finder($className = __CLASS__) {
		return parent::finder($className);
	}
}
?>
