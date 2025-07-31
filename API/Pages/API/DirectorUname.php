<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

use Bacularis\Common\Modules\Errors\BconsoleError;
use Bacularis\API\Modules\ConsoleOutputPage;
use Bacularis\API\Modules\Uname;

/**
 * Cancel job endpoint.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class DirectorUname extends ConsoleOutputPage
{
	public function get()
	{
		$out_format = $this->Request->contains('output') && $this->isOutputFormatValid($this->Request['output']) ? $this->Request['output'] : ConsoleOutputPage::OUTPUT_FORMAT_RAW;
		$director = $this->Request->contains('name') && $this->getModule('misc')->isValidName($this->Request['name']) ? $this->Request['name'] : null;

		$dirs = [];
		$bconsole = $this->getModule('bconsole');
		$result = $bconsole->getDirectors();
		if ($result->exitcode === 0) {
			$dirs = $result->output;
		}

		if (is_string($director) && in_array($director, $dirs)) {
			$out = (object) [
				'output' => [],
				'exitcode' => BconsoleError::ERROR_NO_ERRORS
			];
			if ($out_format === ConsoleOutputPage::OUTPUT_FORMAT_RAW) {
				$out = $this->getRawOutput(['director' => $director]);
			} elseif ($out_format === ConsoleOutputPage::OUTPUT_FORMAT_JSON) {
				$out = $this->getJSONOutput(['director' => $director]);
			}
			$this->output = $out['output'];
			$this->error = $out['error'];
		} else {
			$this->output = BconsoleError::MSG_ERROR_INVALID_DIRECTOR;
			$this->error = BconsoleError::ERROR_INVALID_DIRECTOR;
		}
	}

	/**
	 * Get output from console in raw format.
	 *
	 * @param array $params command parameters
	 * @return array output and error code
	 */
	protected function getRawOutput($params = [])
	{
		$result = ['output' => [], 'error' => BconsoleError::ERROR_NO_ERRORS];
		$bconsole = $this->getModule('bconsole');
		$uname = $bconsole->bconsoleCommand(
			$params['director'],
			['version'],
			null,
			true
		);
		if ($uname->exitcode == 0) {
			$version = implode('', $uname->output);
			$result['output'] = [self::getDirVersionUname($version)];
			$result['error'] = BconsoleError::ERROR_NO_ERRORS;
		} else {
			$emsg = sprintf(
				', Error => %s, Output => %s',
				var_export($uname->output, true),
				$uname->exitcode
			);
			$result['output'] = BconsoleError::MSG_ERROR_WRONG_EXITCODE . $emsg;
			$result['error'] = BconsoleError::ERROR_WRONG_EXITCODE;
		}
		return $result;
	}

	/**
	 * Get output in JSON format.
	 *
	 * @param array $params command parameters
	 * @return array with output and error code
	 */
	protected function getJSONOutput($params = [])
	{
		$result = (array) [
			'output' => [],
			'error' => 0
		];
		$raw = $this->getRawOutput($params);
		if ($raw['error'] == 0) {
			$uname = implode('', $raw['output']);
			$result['output'] = Uname::parse($uname);
		}
		$result['error'] = $raw['error'];
		return $result;
	}

	/**
	 * Get Director uname from bconsole version command.
	 *
	 * @param string $ver_val version line
	 * @return string uname value
	 */
	public static function getDirVersionUname(string $ver_val): string
	{
		/**
		 * Example:
		 * darkstar-dir Version: 15.0.3 (25 March 2025) x86_64-pc-linux-gnu redhat
		 */

		$uname = '';
		if (strpos($ver_val, ':') !== false) {
			[, $uname] = explode(':', $ver_val, 2);
			$uname = trim($uname);
		}
		return $uname;
	}
}
