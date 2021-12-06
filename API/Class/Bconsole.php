<?php
/*
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

Prado::using('Application.Common.Class.Errors');
Prado::using('Application.API.Class.BAPIException');
Prado::using('Application.API.Class.APIModule');

/**
 * Execute bconsole module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Bconsole
 * @package Baculum API
 */
class Bconsole extends APIModule {

	const SUDO = 'sudo';

	/**
	 * Pattern types used to prepare command.
	 */
	const PTYPE_REG_CMD = 0;
	const PTYPE_API_CMD = 1;
	const PTYPE_BG_CMD = 2;
	const PTYPE_CONFIRM_YES_CMD = 3;
	const PTYPE_CONFIRM_YES_BG_CMD = 4;

	const BCONSOLE_COMMAND_PATTERN = "%s%s -c \"%s\" %s 2>&1 <<END_OF_DATA\ngui on\n%s\nquit\nEND_OF_DATA";

	const BCONSOLE_BG_COMMAND_PATTERN = "echo 'gui on\n%s\nquit\n' | nohup %s%s -c \"%s\" %s >%s 2>&1 &";

	const BCONSOLE_CONFIRM_YES_COMMAND_PATTERN = "%s%s -c \"%s\" %s 2>&1 <<END_OF_DATA\ngui on\n%s\nyes\nquit\nEND_OF_DATA";

	const BCONSOLE_CONFIRM_YES_BG_COMMAND_PATTERN = "echo 'gui on\n%s\nyes\nquit\n' | nohup %s%s -c \"%s\" %s >%s 2>&1 &";

	const BCONSOLE_API_COMMAND_PATTERN = "%s%s -c \"%s\" %s 2>&1 <<END_OF_DATA\ngui on\n.api 2 nosignal api_opts=o\n%s\nquit\nEND_OF_DATA";

	const BCONSOLE_DIRECTORS_PATTERN = "%s%s -c \"%s\" -l 2>&1";

	const OUTPUT_FILE_PREFIX = 'output_';

	private $allowed_commands = array(
		'version',
		'status',
		'list',
		'messages',
		'show',
		'mount',
		'umount',
		'release',
		'prune',
		'purge',
		'update',
		'estimate',
		'run',
		'.bvfs_update',
		'.bvfs_lsdirs',
		'.bvfs_lsfiles',
		'.bvfs_versions',
		'.bvfs_get_jobids',
		'.bvfs_restore',
		'.bvfs_clear_cache',
		'.bvfs_cleanup',
		'restore',
		'cancel',
		'delete',
		'.jobs',
		'label',
		'reload',
		'.fileset',
		'.storage',
		'.client',
		'.pool',
		'.schedule',
		'.api',
		'.status',
		'.ls',
		'setbandwidth'
	);

	private $config;

	private $use_sudo;

	private static $cmd_path;

	private static $cfg_path;

	public function init($param) {
		$this->config = $this->getModule('api_config')->getConfig('bconsole');
		if(count($this->config) > 0) {
			$use_sudo = ((integer)$this->config['use_sudo'] === 1);
			$cmd_path = $this->config['bin_path'];
			$custom_cfg_path = self::getCfgPath();
			$cfg_path = isset($custom_cfg_path) ? $custom_cfg_path : $this->config['cfg_path'];
			$this->setEnvironmentParams($cmd_path, $cfg_path, $use_sudo);
		}
	}

	public static function setCmdPath($path, $force = false) {
		// possible to set only once
		if (is_null(self::$cmd_path) || $force) {
			 self::$cmd_path = $path;
		}
	}

	public static function getCmdPath() {
		return self::$cmd_path;
	}

	public static function setCfgPath($path, $force = false) {
		// possible to set only once
		if (is_null(self::$cfg_path) || $force) {
			self::$cfg_path = $path;
		}
	}

	public static function getCfgPath() {
		return self::$cfg_path;
	}

	public function setUseSudo($use_sudo, $force) {
		// possible to set only once
		if (is_null($this->use_sudo) || $force) {
			$this->use_sudo = $use_sudo;
		}
	}

	public function getUseSudo() {
		return $this->use_sudo;
	}

	private function setEnvironmentParams($cmd_path, $cfg_path, $use_sudo, $force = false) {
		self::setCmdPath($cmd_path, $force);
		self::setCfgPath($cfg_path, $force);
		$this->setUseSudo($use_sudo, $force);
	}

	private function isCommandValid($command) {
		$command = trim($command);
		return in_array($command, $this->allowed_commands);
	}

	private function prepareResult(array $output, $exitcode, $bconsole_command) {
		array_pop($output); // deleted 'quit' bconsole command
		$out = $output;
		for($i = 0; $i < count($out); $i++) {
			if(strstr($out[$i], $bconsole_command) == false) {
				unset($output[$i]);
			} else {
				break;
			}
		}
		$output = array_values($output);
		return (object)array('output' => $output, 'exitcode' => (integer)$exitcode);
	}

	public function bconsoleCommand($director, array $command, $ptype = null, $without_cmd = false) {
		$result = null;
		if (count($this->config) > 0 && $this->config['enabled'] !== '1') {
			throw new BConsoleException(
				BconsoleError::MSG_ERROR_BCONSOLE_DISABLED,
				BconsoleError::ERROR_BCONSOLE_DISABLED
			);
		}
		$base_command = count($command) > 0 ? $command[0] : null;
		if($this->isCommandValid($base_command) === true) {
			$result = $this->execCommand($director, $command, $ptype);
			if ($without_cmd) {
				array_shift($result->output);
			}
		} else {
			throw new BConsoleException(
				BconsoleError::MSG_ERROR_INVALID_COMMAND,
				BconsoleError::ERROR_INVALID_COMMAND
			);
		}
		return $result;
	}

	private function execCommand($director, array $command, $ptype = null) {
		$cmd = '';
		$result = null;
		if(!is_null($director) && $this->isValidDirector($director) === false) {
			throw new BConsoleException(
				BconsoleError::MSG_ERROR_INVALID_DIRECTOR,
				BconsoleError::ERROR_INVALID_DIRECTOR
			);
		} else {
			$dir = is_null($director) ? '': '-D ' . $director;
			$sudo = ($this->getUseSudo() === true) ? self::SUDO . ' ' : '';
			$bconsole_command = implode(' ', $command);
			$pattern = $this->getCmdPattern($ptype);
			$cmd = $this->getCommand($pattern, $sudo, $dir, $bconsole_command);
			exec($cmd['cmd'], $output, $exitcode);
			if($exitcode != 0) {
				$emsg = ' Output=>' . implode("\n", $output) . ', Exitcode=>' . $exitcode;
				throw new BConsoleException(
					BconsoleError::MSG_ERROR_BCONSOLE_CONNECTION_PROBLEM . $emsg,
					BconsoleError::ERROR_BCONSOLE_CONNECTION_PROBLEM
				);
			} else {
				if ($pattern === self::BCONSOLE_BG_COMMAND_PATTERN || $pattern === self::BCONSOLE_CONFIRM_YES_BG_COMMAND_PATTERN) {
					$output = array(
						$bconsole_command,
						json_encode(array('out_id' => $cmd['out_id'])),
						'quit' // in prepareResult() this value is deleted
					);
				}
				$result = $this->prepareResult($output, $exitcode, $bconsole_command);
			}
		}
		$this->Application->getModule('logging')->log(
			$cmd['cmd'],
			$output,
			Logging::CATEGORY_EXECUTE,
			__FILE__,
			__LINE__
		);

		return $result;
	}

	private function getCommand($pattern, $sudo, $director, $bconsole_command) {
		$command = array('cmd' => null, 'out_id' => null);
		$misc = $this->getModule('misc');
		if ($pattern === self::BCONSOLE_BG_COMMAND_PATTERN || $pattern === self::BCONSOLE_CONFIRM_YES_BG_COMMAND_PATTERN) {
			$file = $this->prepareOutputFile();
			$cmd = sprintf(
				$pattern,
				$bconsole_command,
				$sudo,
				self::getCmdPath(),
				self::getCfgPath(),
				$director,
				$file
			);
			$command['cmd'] = $misc->escapeCharsToConsole($cmd);
			$command['out_id'] = preg_replace('/^[\s\S]+\/output_/', '', $file);
		} else {
			$cmd = sprintf(
				$pattern,
				$sudo,
				self::getCmdPath(),
				self::getCfgPath(),
				$director,
				$bconsole_command
			);
			$command['cmd'] = $misc->escapeCharsToConsole($cmd);
			$command['out_id'] = '';
		}
		return $command;
	}

	private function getCmdPattern($ptype) {
		$pattern = null;
		switch ($ptype) {
			case self::PTYPE_API_CMD: $pattern = self::BCONSOLE_API_COMMAND_PATTERN; break;
			case self::PTYPE_BG_CMD: $pattern = self::BCONSOLE_BG_COMMAND_PATTERN; break;
			case self::PTYPE_CONFIRM_YES_CMD: $pattern = self::BCONSOLE_CONFIRM_YES_COMMAND_PATTERN; break;
			case self::PTYPE_CONFIRM_YES_BG_CMD: $pattern = self::BCONSOLE_CONFIRM_YES_BG_COMMAND_PATTERN; break;
			default: $pattern = self::BCONSOLE_COMMAND_PATTERN;
		}
		return $pattern;
	}

	public function getDirectors() {
		$sudo = ($this->getUseSudo() === true) ? self::SUDO . ' ' : '';
		$cmd = sprintf(
			self::BCONSOLE_DIRECTORS_PATTERN,
			$sudo,
			self::getCmdPath(),
			self::getCfgPath()
		);
		$cmd = $this->getModule('misc')->escapeCharsToConsole($cmd);
		exec($cmd, $output, $exitcode);
		if($exitcode != 0) {
			$emsg = ' Output=>' . implode("\n", $output) . ', Exitcode=>' . $exitcode;
			throw new BConsoleException(
				BconsoleError::MSG_ERROR_BCONSOLE_CONNECTION_PROBLEM . $emsg,
				BconsoleError::ERROR_BCONSOLE_CONNECTION_PROBLEM
			);
		}
		$result = (object)array('output' => $output, 'exitcode' => $exitcode);
		return $result;
	}

	private function isValidDirector($director) {
		return in_array($director, $this->getDirectors()->output);
	}

	private function prepareOutputFile() {
		$dir = Prado::getPathOfNamespace('Application.API.Config');
		$fname = tempnam($dir, self::OUTPUT_FILE_PREFIX);
		return $fname;
	}

	public static function readOutputFile($out_id) {
		$output = array();
		$dir = Prado::getPathOfNamespace('Application.API.Config');
		if (preg_match('/^[a-z0-9]+$/i', $out_id) === 1) {
			$file = $dir . '/' . self::OUTPUT_FILE_PREFIX . $out_id;
			if (file_exists($file)) {
				$output = file($file);
			}
			$output_count = count($output);
			$last = $output_count > 0 ? trim($output[$output_count-1]) : '';
			if ($last === 'quit') {
				// output is complete, so remove the file
				unlink($file);
			}
		}
		return $output;
	}

	public function testBconsoleCommand(array $command, $cmd_path, $cfg_path, $use_sudo) {
		$this->setEnvironmentParams($cmd_path, $cfg_path, $use_sudo, true);
		$director = '';
		$result = null;
		try {
			$director = array_shift($this->getDirectors()->output);
			$result = $this->bconsoleCommand($director, $command);
		} catch (BAPIException $e) {
			$result = (object)array(
				'output' => $e->getErrorMessage(),
				'exitcode' => $e->getErrorCode()
			);
		}
		return $result;
	}
}
?>
