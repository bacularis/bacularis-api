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
 * Sudo module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Command
 */
class Sudo extends APIModule
{
	/**
	 * Sudo command.
	 */
	private const SUDO_CMD = 'sudo';

	/**
	 * Defines if use sudo at all.
	 *
	 * @var bool
	 */
	private $use_sudo;

	/**
	 * Defines optional sudo user.
	 *
	 * @var string
	 */
	private $user;

	/**
	 * Defines optional sudo group.
	 *
	 * @var string
	 */
	private $group;

	public function __construct($prop)
	{
		$this->setUseSudo($prop['use_sudo']);
		$this->setUser($prop['user']);
		$this->setGroup($prop['group']);
	}

	/**
	 * Use sudo setter.
	 *
	 * @param bool $use_sudo determines if use sudo
	 */
	public function setUseSudo(bool $use_sudo): void
	{
		$this->use_sudo = $use_sudo;
	}

	/**
	 * Use sudo getter.
	 *
	 * @return (null|string) use sudo property
	 */
	public function getUseSudo()
	{
		return $this->use_sudo;
	}

	/**
	 * Sudo user setter.
	 *
	 * @param string $user sudo user
	 */
	public function setUser(string $user): void
	{
		$this->user = $user;
	}

	/**
	 * Sudo user getter.
	 *
	 * @return string sudo user or empty string if user not set
	 */
	public function getUser(): string
	{
		return ($this->user ?? '');
	}

	/**
	 * Sudo group setter.
	 *
	 * @param string $group sudo group
	 */
	public function setGroup(string $group): void
	{
		$this->group = $group;
	}

	/**
	 * Sudo group getter.
	 *
	 * @return string sudo group or empty string if group not set
	 */
	public function getGroup()
	{
		return ($this->group ?? '');
	}

	/**
	 * Get prepared sudo command.
	 * Command ready to use to execute other commands with sudo.
	 *
	 * @return string sudo command or empty string if sudo not used
	 */
	public function getSudoCmd(): string
	{
		$sudo = '';
		$cmd = [];
		if ($this->getUseSudo() === true) {
			$cmd[] = self::SUDO_CMD;
			$user = $this->getUser();
			if (!empty($user)) {
				$cmd[] = '-u';
				$cmd[] = '"' . $user . '"';
			}
			$group = $this->getGroup();
			if (!empty($group)) {
				$cmd[] = '-g';
				$cmd[] = '"' . $group . '"';
			}
			$sudo = implode(' ', $cmd) . ' ';
		}
		return $sudo;
	}
}
