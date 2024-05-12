<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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
 * Tools to perform health check self-test.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class SelfTestResult extends APIModule
{
	public const TEST_RESULT_STATE_INFO = 'info';
	public const TEST_RESULT_STATE_WARNING = 'warning';
	public const TEST_RESULT_STATE_ERROR = 'error';
	public const TEST_RESULT_STATE_DISABLED = 'disabled';


	public const TEST_RESULT_DESC_OK = 'OK';
	public const TEST_RESULT_DESC_ERROR = 'Error';
	public const TEST_RESULT_DESC_DISABLED = 'Disabled';
	/**
	 * Test short name.
	 */
	private $name;

	/**
	 * Test section/category.
	 */
	private $section;

	/**
	 * Result type (boolean, string, integer...etc)
	 */
	private $type;

	/**
	 * Test result
	 */
	private $result;

	/**
	 * Test result state (info, warning, error).
	 */
	private $state;

	/**
	 * Test description
	 */
	private $description;

	/**
	 * Set test short name.
	 *
	 * @param string $name test name
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * Set test section/category.
	 * Ex. 'Director configuration' or 'Console access'
	 *
	 * @param string $section test section
	 */
	public function setSection(string $section): void
	{
		$this->section = $section;
	}

	/**
	 * Set test return value type.
	 * Used are long type names (string, integer) instead of short names (str, int)
	 *
	 * @param string $type return value type
	 */
	public function setType(string $type): void
	{
		$this->type = $type;
	}

	/**
	 * Set test result.
	 *
	 * @param mixed $result test result
	 */
	public function setResult($result): void
	{
		$this->result = $result;
	}

	/**
	 * Set test result state (info, warning, error).
	 *
	 * @param string $state test result state
	 */
	public function setState($state): void
	{
		$this->state = $state;
	}

	/**
	 * Set test description.
	 *
	 * @param string $description test description
	 */
	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function toArray()
	{
		return [
			'name' => $this->name,
			'section' => $this->section,
			'type' => $this->type,
			'result' => $this->result,
			'state' => $this->state,
			'description' => $this->description
		];
	}
}
