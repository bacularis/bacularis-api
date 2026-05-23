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

namespace Bacularis\API\Modules\Cloud\Amazon;

use Bacularis\Common\Modules\BWorkerTask;
use Bacularis\Common\Modules\BWorkerTaskException;
use Bacularis\Common\Modules\Logging;

/**
 * Bacularis worker task for local file restore.
 * This task is used to do restore AWS EC2 backup data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BWorkerTaskLocalFileRestore extends BWorkerTask
{
	/**
	 * Run worker task.
	 *
	 * @return bool true if task finished successfully, false otherwise
	 */
	public function run()
	{
		$data = $this->getData();

		// Prepare file path to restore
		[, $file] = $data['destination'];

		// Restore given file part
		$result = $this->writeVolumeBlock($file, $data);
		if (!$result) {
			$emsg = "Error while running local file restore. File: '{$file}'.";
			Logging::log(Logging::CATEGORY_APPLICATION, $emsg);
		}
		$this->finish($data);
		return $result;
	}

	/**
	 * Write single data block in local file restore.
	 *
	 * @param string $file file with full path
	 * @param array $params task properties
	 * @throws BWorkerTaskException on writing to destination error
	 * @return bool true if block saved successfully, false otherwise
	 */
	private function writeVolumeBlock(string $file, array $params): bool
	{
		$size = null;
		$handle = fopen($file, 'c');
		$seek = $params['block_index'] * $params['block_size'];
		fseek($handle, $seek);
		if ($params['state'] === AWSEC2Data::DATA_STATE_UNWRITTEN) {
			// Zero data block
			$params['block'] = AWSEC2Data::getZeroBlock();
		}
		$size = fwrite($handle, $params['block']);
		fflush($handle);
		if ($size === false) {
			$emsg = "Error while writing local restore file. File: '{$file}'.";
			Logging::log(Logging::CATEGORY_APPLICATION, $emsg);
			throw new BWorkerTaskException($emsg, 1);
		}
		fclose($handle);
		return is_int($size);
	}

	/**
	 * Prepare worker task result.
	 *
	 * @param mixed $data result task data
	 */
	public function prepareResult($data)
	{
		Logging::log(
			Logging::CATEGORY_APPLICATION,
			"[$this] Local file restore task finished. Prepare results."
		);
	}
}
