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

Prado::using('Application.API.Class.APIModule');
Prado::using('Application.Common.Class.Errors');

/**
 * Bacula JSON tools manager.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum API
 */
class JSONTools extends APIModule
{
	const SUDO = 'sudo';

	/**
	 * JSON tool command pattern - standard version.
	 */
	const JSON_TOOL_COMMAND_PATTERN = '%s%s -c %s %s 2>&1';

	private function isJSONToolsEnabled() {
		return $this->getModule('api_config')->isJSONToolsEnabled();
	}

	private function prepareOutput(array $output) {
		$output_txt = implode('', $output);
		$out = json_decode($output_txt, true);
		if (!is_array($out)) {
			$this->getModule('logging')->log('Parse output', $output_txt, Logging::CATEGORY_EXTERNAL, __FILE__, __LINE__);
			$out = null;
		}
		return $out;
	}

	public function prepareResult($output, $exitcode) {
		$result = array(
			'output' => $output,
			'exitcode' => $exitcode
		);
		return $result;
	}

	private function getSudo($use_sudo) {
		$sudo = '';
		if ($use_sudo === true) {
			$sudo = self::SUDO . ' ';
		}
		return $sudo;
	}

	public function execCommand($component_type, $params = array(), $config = '') {
		$result = null;
		if ($this->isJSONToolsEnabled() === true) {
			$tool_type = $this->getModule('bacula_setting')->getJSONToolTypeByComponentType($component_type);
			$tool = $this->getTool($tool_type);
			$result = $this->execTool($tool['bin'], $tool['cfg'], $tool['use_sudo'], $params, $config);
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

	private function getTool($tool_type) {
		$tool = $this->getModule('api_config')->getJSONToolConfig($tool_type);
		return $tool;
	}

	private function execTool($bin, $cfg, $use_sudo, $params = array(), $config = '') {
		$sudo = $this->getSudo($use_sudo);
		$options = $this->getOptions($params);
		$cmd_pattern = $this->getCmdPattern($params);
		if (!empty($config)) {
			$cfg = $this->prepareConfig($config);
		}
		$cmd = sprintf($cmd_pattern, $sudo, $bin, $cfg, $options);
		exec($cmd, $output, $exitcode);
		$this->getModule('logging')->log($cmd, $output, Logging::CATEGORY_EXECUTE, __FILE__, __LINE__);
		if (!empty($config)) {
			unlink($cfg);
			if ($exitcode === 0) {
				// @TODO: Temporary value for validation. Do it more pretty.
				$output = array('[]');
			}
		}
		$result = $this->prepareResult($output, $exitcode);
		return $result;
	}

	private function getCmdPattern($params = array()) {
		// Default command pattern
		return self::JSON_TOOL_COMMAND_PATTERN;
	}

	private function prepareConfig($config) {
		$tool = $this->getModule('api_config')->getConfig('jsontools');
		$fname = tempnam($tool['bconfig_dir'], 'config_');
		file_put_contents($fname, $config);
		return $fname;
	}

	private function getOptions($params = array()) {
		$opts = '';
		$options = array();
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

	public function testJSONTool($bin, $cfg, $use_sudo) {
		return $this->execTool($bin, $cfg, $use_sudo);
	}
}
?>
