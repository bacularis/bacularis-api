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

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\Common\Modules\Errors\PoolError;
use Bacularis\API\Modules\Bconsole;

/**
 * Status schedune endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class ScheduleStatus extends BaculumAPIServer
{
	/**
	 * Default days limit.
	 */
	public const DEF_DAYS = 30;

	/**
	 * Default list items limit.
	 */
	public const DEF_LIMIT = 30;

	/**
	 * Allowed properties to group by.
	 */
	private const GROUP_BY_PROPS = ['name', 'client', 'schedule'];

	public function get()
	{
		$misc = $this->getModule('misc');
		$cmd = ['status', 'schedule'];
		if ($this->Request->contains('job') && $misc->isValidName($this->Request['job'])) {
			$cmd[] = 'job="' . $this->Request['job'] . '"';
		}
		if ($this->Request->contains('client') && $misc->isValidName($this->Request['client'])) {
			$cmd[] = 'client="' . $this->Request['client'] . '"';
		}
		if ($this->Request->contains('schedule') && $misc->isValidName($this->Request['schedule'])) {
			$cmd[] = 'schedule="' . $this->Request['schedule'] . '"';
		}
		if ($this->Request->contains('days') && $misc->isValidInteger($this->Request['days'])) {
			$cmd[] = 'days="' . $this->Request['days'] . '"';
		} else {
			/**
			 * For Director < 9.6.0 there was a bug in displaying the full schedule status
			 * that caused showing an incomplete schedule list. Providing days limit
			 * is a workaround to have always complete schedule list for all Director versions
			 * which support 'status schedule' command.
			 */
			$cmd[] = 'days="' . self::DEF_DAYS . '"';
		}
		if ($this->Request->contains('limit') && $misc->isValidInteger($this->Request['limit'])) {
			$cmd[] = 'limit="' . $this->Request['limit'] . '"';
		} elseif (!$this->Request->contains('days')) {
			$cmd[] = 'limit="' . self::DEF_LIMIT . '"';
		}
		if ($this->Request->contains('time') && $misc->isValidBDateAndTime($this->Request['time'])) {
			$cmd[] = 'time="' . $this->Request['time'] . '"';
		}
		$group_by = null;
		if ($this->Request->contains('group_by') && in_array($this->Request['group_by'], self::GROUP_BY_PROPS)) {
			$group_by = $this->Request['group_by'];
		}
		$group_limit = null;
		if ($this->Request->contains('group_limit') && $misc->isValidInteger($this->Request['group_limit'])) {
			$group_limit = (int) $this->Request['group_limit'];
		}

		$bconsole = $this->getModule('bconsole');
		$result = $bconsole->bconsoleCommand(
			$this->director,
			$cmd,
			Bconsole::PTYPE_API_CMD,
			true
		);
		if ($result->exitcode === 0) {
			$this->output = $this->formatSchedules($result->output, $group_by, $group_limit);
			$this->error = PoolError::ERROR_NO_ERRORS;
		} else {
			$this->output = $result->output;
			$this->error = $result->exitcode;
		}
	}

	private function formatSchedules(array $output, ?string $group_by = null, ?int $group_limit = null)
	{
		$items = $item = [];
		$cnt = [];
		for ($i = 0; $i < count($output); $i++) {
			if (preg_match('/^(limit|error|errmsg)=/', $output[$i]) === 1) {
				// skip key/value items that are not schedule status
				continue;
			}
			if (preg_match('/^(?P<key>\w+)=(?P<val>[\s\S]*)$/', $output[$i], $match) === 1) {
				$item[$match['key']] = $match['val'];
			} elseif (empty($output[$i]) && count($item) > 0) {
				if (is_string($group_by)) {
					if (!key_exists($group_by, $item)) {
						// group by not available in single result - skip it
						continue;
					}
					if (!key_exists($item[$group_by], $items)) {
						$items[$item[$group_by]] = [];
						$cnt[$item[$group_by]] = 0;
					}
					if (is_int($group_limit) && $cnt[$item[$group_by]] >= $group_limit) {
						continue;
					}
					$items[$item[$group_by]][] = $item;
					$cnt[$item[$group_by]]++;
				} else {
					$items[] = $item;
				}
				$item = [];
			}
		}
		return $items;
	}
}
