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

namespace Bacularis\API\Modules;

use Prado\Prado;
use Bacularis\Common\Modules\Logging;
use Bacularis\Common\Modules\Errors\JSONToolsError;

/**
 * Bacula JSON tools manager.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class JSONTools extends APIModule
{
	/**
	 * Sudo object.
	 */
	private $sudo;

	/**
	 * JSON tool command pattern - standard version.
	 */
	public const JSON_TOOL_COMMAND_PATTERN = '%s%s -c %s %s 2>&1';

	private function isJSONToolsEnabled()
	{
		return $this->getModule('api_config')->isJSONToolsEnabled();
	}

	private function prepareOutput(array $output)
	{
		$output_txt = implode('', $output);
		$out = json_decode($output_txt, true);
		if (!is_array($out)) {
			Logging::log(
				Logging::CATEGORY_EXTERNAL,
				'PARSED OUTPUT: ' . $output_txt
			);
			$out = null;
		}
		return $out;
	}

	public function prepareResult($output, $exitcode)
	{
		$result = [
			'output' => $output,
			'exitcode' => $exitcode
		];
		return $result;
	}

	/**
	 * Set sudo setting for command.
	 *
	 * @param array $prop sudo properties
	 */
	private function setSudo(array $prop): void
	{
		$this->sudo = Prado::createComponent(
			'Bacularis\API\Modules\Sudo',
			$prop
		);
	}

	public function execCommand($component_type, $params = [], $config = '')
	{
		$result = null;
		if ($this->isJSONToolsEnabled() === true) {
			$tool_type = $this->getModule('bacula_setting')->getJSONToolTypeByComponentType($component_type);
			$tool = $this->getTool($tool_type);
			$result = $this->execTool($tool['bin'], $tool['cfg'], $tool['sudo'], $params, $config);
			$output = $result['output'];
			$exitcode = $result['exitcode'];
			if ($exitcode === 0) {
				$output = $this->prepareOutput($output);
				if (is_null($output)) {
					$output = JSONToolsError::MSG_ERROR_JSON_TOOLS_UNABLE_TO_PARSE_OUTPUT;
					$exitcode = JSONToolsError::ERROR_JSON_TOOLS_UNABLE_TO_PARSE_OUTPUT;
				}
			} else {
				$emsg = PHP_EOL . ' Output:' . implode(PHP_EOL, $output);
				$output = JSONToolsError::MSG_ERROR_JSON_TOOLS_WRONG_EXITCODE . $emsg;
				$exitcode = JSONToolsError::ERROR_JSON_TOOLS_WRONG_EXITCODE;
			}
			$result = $this->prepareResult($output, $exitcode);
		} else {
			$output = JSONToolsError::MSG_ERROR_JSON_TOOLS_DISABLED;
			$exitcode = JSONToolsError::ERROR_JSON_TOOLS_DISABLED;
			$result = $this->prepareResult($output, $exitcode);
		}
		return $result;
	}

	private function getTool($tool_type)
	{
		return $this->getModule('api_config')->getJSONToolConfig($tool_type);
	}

	private function execTool($bin, $cfg, $sudo_prop, $params = [], $config = '')
	{
		$this->setSudo($sudo_prop);
		$options = $this->getOptions($params);
		$cmd_pattern = $this->getCmdPattern($params);
		if (!empty($config)) {
			$cfg = $this->prepareConfig($config);
		}
		$sudo = $this->sudo->getSudoCmd();
		$cmd = sprintf($cmd_pattern, $sudo, $bin, $cfg, $options);
		exec($cmd, $output, $exitcode);
		Logging::log(
			Logging::CATEGORY_EXECUTE,
			Logging::prepareCommand($cmd, $output)
		);
		if (!empty($config)) {
			unlink($cfg);
			if ($exitcode === 0) {
				// @TODO: Temporary value for validation. Do it more pretty.
				$output = ['[]'];
			}
		}
		$result = $this->prepareResult($output, $exitcode);
		return $result;
	}

	private function getCmdPattern($params = [])
	{
		// Default command pattern
		return self::JSON_TOOL_COMMAND_PATTERN;
	}

	private function prepareConfig($config)
	{
		$tool = $this->getModule('api_config')->getConfig('jsontools');
		$fname = tempnam($tool['bconfig_dir'], 'config_');
		file_put_contents($fname, $config);
		return $fname;
	}

	private function getOptions($params = [])
	{
		$opts = '';
		$options = [];
		if (array_key_exists('resource_type', $params)) {
			array_push($options, '-r', $params['resource_type']);
		}
		if (array_key_exists('resource_name', $params)) {
			array_push($options, '-n', $params['resource_name']);
		}
		if (array_key_exists('directive_name', $params) && array_key_exists('resource_type', $params)) {
			array_push($options, '-l', $params['directive_name']);
		}
		if (array_key_exists('data_only', $params) && $params['data_only'] === true) {
			array_push($options, '-D');
		}
		if (array_key_exists('test_config', $params) && $params['test_config'] === true) {
			array_push($options, '-t');
		}
		if (array_key_exists('dont_apply_jobdefs', $params) && $params['dont_apply_jobdefs'] === true) {
			array_push($options, '-R');
		}
		if (count($options) > 0) {
			$opts = '"' . implode('" "', $options) . '"';
		}
		return $opts;
	}

	public function testJSONTool($bin, $cfg, $sudo_prop)
	{
		return $this->execTool($bin, $cfg, $sudo_prop);
	}
}
