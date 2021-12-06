<?php
/*
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2021 Kern Sibbald
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
Prado::using('Application.API.Class.DeviceConfig');
Prado::using('Application.API.Class.APIModule');

/**
 * Execute changer command module.
 * Changer command should provide interface compatible with
 * mtx-changer script provided with Bacula.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Autochanger
 * @package Baculum API
 */
class ChangerCommand extends APIModule {

	const SUDO = 'sudo';

	/**
	 * Types to determine how changer command is executed (foreground, background...)
	 */
	const PTYPE_FG_CMD = 0;
	const PTYPE_BG_CMD = 1;

	/**
	 * Output file prefix used to temporary store output from commands.
	 */
	const OUTPUT_FILE_PREFIX = 'output_';

	/**
	 * Pattern to changer command.
	 */
	const CHANGER_COMMAND_FG_PATTERN = '%s %s 2>&1';
	const CHANGER_COMMAND_BG_PATTERN = '{ %s %s 1>%s 2>&1; echo "quit" >> %s ; } &';

	/**
	 * Supported parameters with short codes.
	 * NOTE: order has meaning here.
	 */
	private $params = [
		'%c' => 'changer-device',
		'%o' => 'command',
		'%S' => 'slot',
		'%a' => 'archive-device',
		'%d' => 'drive-index'
	];

	/**
	 * Supported changer commands.
	 */
	private $commands = [
		'load',
		'unload',
		'loaded',
		'list',
		'slots',
		'listall',
		'transfer'
	];

	/**
	 * Stores device config.
	 */
	private $config;

	public function init($param) {
		$this->config = $this->getModule('device_config')->getConfig();
	}

	/**
	 * Validate changer script command.
	 * @param string $command script command
	 * @return boolean true on validation success, otherwise false
	 */
	private function validateCommand($command) {
		return in_array($command, $this->commands);
	}

	/**
	 * Get sudo command.
	 *
	 * @param boolean $use_sudo sudo option state
	 * @return string sudo command
	 */
	private function getSudo($use_sudo) {
		$sudo = '';
		if ($use_sudo === true) {
			$sudo = self::SUDO;
		}
		return $sudo;
	}

	/**
	 * Execute changer command.
	 *
	 * @param string $changer autochanger device name
	 * @param string $command changer command (load, unload ...etc.)
	 * @param string $device archive device name (autochanger drive name)
	 * @param string $slot slot in slots magazine to use
	 * @param string $slotdest destination slot in slots magazine (used with transfer command)
	 * @param string $ptype command pattern type
	 * @return StdClass executed command output and error code
	 */
	public function execChangerCommand($changer, $command, $device = null, $slot = null, $slotdest = null, $ptype = null) {
		if (!$this->validateCommand($command)) {
			$output = DeviceError::MSG_ERROR_DEVICE_INVALID_COMMAND;
			$error = DeviceError::ERROR_DEVICE_INVALID_COMMAND;
			$result = $this->prepareResult($output, $error);
			return $result;
		}
		if (count($this->config) == 0)  {
			$output = DeviceError::MSG_ERROR_DEVICE_DEVICE_CONFIG_DOES_NOT_EXIST;
			$error = DeviceError::ERROR_DEVICE_DEVICE_CONFIG_DOES_NOT_EXIST;
			$result = $this->prepareResult($output, $error);
			return $result;
		}
		if (!key_exists($changer, $this->config) || $this->config[$changer]['type'] !== DeviceConfig::DEV_TYPE_AUTOCHANGER)  {
			$output = DeviceError::MSG_ERROR_DEVICE_AUTOCHANGER_DOES_NOT_EXIST;
			$error = DeviceError::ERROR_DEVICE_AUTOCHANGER_DOES_NOT_EXIST;
			$result = $this->prepareResult($output, $error);
			return $result;
		}
		if (is_string($device)) {
			$drives = explode(',', $this->config[$changer]['drives']);
			if (!in_array($device, $drives))  {
				$output = DeviceError::MSG_ERROR_DEVICE_DRIVE_DOES_NOT_BELONG_TO_AUTOCHANGER;
				$error = DeviceError::ERROR_DEVICE_DRIVE_DOES_NOT_BELONG_TO_AUTOCHANGER;
				$result = $this->prepareResult($output, $error);
				return $result;
			}
		}

		if (is_string($device) && (!key_exists($device, $this->config) || $this->config[$device]['type'] !== DeviceConfig::DEV_TYPE_DEVICE))  {
			$output = DeviceError::MSG_ERROR_DEVICE_AUTOCHANGER_DRIVE_DOES_NOT_EXIST;
			$error = DeviceError::ERROR_DEVICE_AUTOCHANGER_DRIVE_DOES_NOT_EXIST;
			$result = $this->prepareResult($output, $error);
			return $result;
		}
		$changer_command = $this->config[$changer]['command'];
		$changer_device = $this->config[$changer]['device'];
		$archive_device = is_string($device) ? $this->config[$device]['device'] : '';
		$drive_index = is_string($device) ? $this->config[$device]['index'] : '';
		$use_sudo = ($this->config[$changer]['use_sudo'] == 1);

		if ($command === 'transfer') {
			// in transfer command in place archive device is given destination slot
			$archive_device = $slotdest;
		}

		$command = $this->prepareChangerCommand(
			$changer_command,
			$changer_device,
			$command,
			$slot,
			$archive_device,
			$drive_index
		);
		$pattern = $this->getCmdPattern($ptype);
		$cmd = $this->getCommand($pattern, $use_sudo, $command);
		$result = $this->execCommand($cmd, $ptype);
		if ($result->error !== 0) {
			$emsg = PHP_EOL . ' Output:' . implode(PHP_EOL, $result->output);
			$output = DeviceError::MSG_ERROR_WRONG_EXITCODE . $emsg;
			$exitcode = DeviceError::ERROR_WRONG_EXITCODE;
			$result = $this->prepareResult($output, $exitcode);
		}
		return $result;
	}

	/**
	 * Prepare changer command to execute.
	 *
	 * @param string $changer_command full changer command
	 * @param string $changer_device changer device name
	 * @param string $command changer command (load, unload ...etc.)
	 * @param string $slot slot in slots magazine to use
	 * @param string $archive_device archive device name (autochanger drive name)
	 * @param string $drive_index archive device index (autochanger drive index)
	 * @return StdClass executed command output and error code
	 */
	private function prepareChangerCommand($changer_command, $changer_device, $command, $slot, $archive_device, $drive_index) {
		$from = array_keys($this->params);
		$to = [
			'"' . $changer_device .'"',
			'"' . $command .'"',
			'"' . $slot .'"',
			'"' . $archive_device .'"',
			'"' . $drive_index .'"'
		];
		return str_replace($from, $to, $changer_command);
	}

	/**
	 * Get changer command to execute.
	 *
	 * @param string $pattern changer command pattern (@see PTYPE_ constants)
	 * @param boolean $use_sudo information about using sudo
	 * @param string $bin changer command
	 * @return array changer command (and output id if selected pattern to
	 * move command to background)
	 */
	private function getCommand($pattern, $use_sudo, $bin) {
		$command = array('cmd' => null, 'out_id' => null);
		$misc = $this->getModule('misc');
		$sudo = $this->getSudo($use_sudo);

		if ($pattern === self::CHANGER_COMMAND_BG_PATTERN) {
			$file = $this->prepareOutputFile();
			$cmd = sprintf(
				$pattern,
				$sudo,
				$bin,
				$file,
				$file
			);
			$command['cmd'] = $misc->escapeCharsToConsole($cmd);
			$command['out_id'] = preg_replace('/^[\s\S]+\/' . self::OUTPUT_FILE_PREFIX . '/', '', $file);
		} else {
			$cmd = sprintf($pattern, $sudo, $bin);
			$command['cmd'] = $misc->escapeCharsToConsole($cmd);
			$command['out_id'] = '';
		}
		return $command;
	}

	/**
	 * Create and get output file.
	 * Used with background type command patterns (ex. PTYPE_BG_CMD)
	 *
	 * @return string|boolean new temporary filename (with path), or false on failure.
	 */
	private function prepareOutputFile() {
		$dir = Prado::getPathOfNamespace('Application.API.Config');
		$fname = tempnam($dir, self::OUTPUT_FILE_PREFIX);
		return $fname;
	}

	/**
	 * Read output file and return the output.
	 * Used with background type command patterns (ex. PTYPE_BG_CMD)
	 *
	 * @param string $out_id command output identifier
	 * @return array command output with one line per one array element
	 */
	public static function readOutputFile($out_id) {
		$output = [];
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

	/**
	 * Execute changer command.
	 *
	 * @param string $bin command
	 * @param string $ptype command pattern type
	 * @return array result with output and error code
	 */
	public function execCommand($cmd, $ptype = null) {
		exec($cmd['cmd'], $output, $exitcode);
		$this->getModule('logging')->log(
			$cmd['cmd'],
			$output,
			Logging::CATEGORY_EXECUTE,
			__FILE__,
			__LINE__
		);
		if ($ptype === self::PTYPE_BG_CMD) {
			$output = [
				'out_id' => $cmd['out_id']
			];
		}
		return $this->prepareResult($output, $exitcode);
	}

	/**
	 * Prepare changer command result.
	 *
	 * @param array $output output from command execution
	 * @param integer $error command error code
	 * @return array result with output and error code
	 */
	public function prepareResult($output, $error) {
		$result = new StdClass;
		$result->output = $output;
		$result->error  = $error;
		return $result;
	}

	/**
	 * Get command pattern by ptype.
	 *
	 * @param string $ptype pattern type (@see PTYPE_ constants)
	 * @return string command pattern
	 */
	private function getCmdPattern($ptype) {
		$pattern = null;
		switch ($ptype) {
			case self::PTYPE_FG_CMD: $pattern = self::CHANGER_COMMAND_FG_PATTERN; break;
			case self::PTYPE_BG_CMD: $pattern = self::CHANGER_COMMAND_BG_PATTERN; break;
			default: $pattern = self::CHANGER_COMMAND_FG_PATTERN;
		}
		return $pattern;
	}

	/**
	 * Check changer command parameters.
	 * Used to test parameters.
	 *
	 * @param boolean $use_sudo information about using sudo
	 * @param string $changer_command full changer command
	 * @param string $changer_device changer device name
	 * @param string $command changer command (load, unload ...etc.)
	 * @param string $slot slot in slots magazine to use
	 * @param string $archive_device archive device name (autochanger drive name)
	 * @param string $drive_index archive device index (autochanger drive index)
	 * @return StdClass executed command output and error code
	 */
	public function testChangerCommand($use_sudo, $changer_command, $changer_device, $command, $slot, $archive_device, $drive_index) {
		$command = $this->prepareChangerCommand(
			$changer_command,
			$changer_device,
			$command,
			$slot,
			$archive_device,
			$drive_index
		);
		$pattern = $this->getCmdPattern(self::PTYPE_FG_CMD);
		$cmd = $this->getCommand($pattern, $use_sudo, $command);
		$result = $this->execCommand($cmd, self::PTYPE_FG_CMD);
		return $result;
	}
}
?>
