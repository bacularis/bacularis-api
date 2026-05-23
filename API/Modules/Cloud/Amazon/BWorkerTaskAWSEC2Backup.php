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

use Bacularis\Common\Modules\BacularisDataFormat;
use Bacularis\Common\Modules\BWorkerTask;
use Bacularis\Common\Modules\BWorkerTaskException;
use Bacularis\Common\Modules\Logging;

/**
 * Bacularis worker task for AWS EBS Direct API.
 * This task is used to do backup.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BWorkerTaskAWSEC2Backup extends BWorkerTask
{
	/**
	 * Request timeout in seconds.
	 */
	private const REQUEST_TIMEOUT = 150; //@TODO Make it configurable

	/**
	 * Supported data hash algorithms.
	 */
	private const HASH_ALGORITHM_SHA256 = 'SHA256';

	/**
	 * Run worker task.
	 *
	 * @return mixed HTTP connection handler
	 */
	public function run()
	{
		$data = $this->getData();
		$url = $this->buildURL($data['source'], $data['block']);
		$headers = $url ? $this->buildHeaders($url, $data) : [];
		$this->setDestination($data['destination']);
		$ch = curl_init();
		$params = [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_HEADER => true
		];
		curl_setopt_array($ch, $params);
		Logging::log(
			Logging::CATEGORY_APPLICATION,
			"[$this] Run AWS EC2 backup task."
		);
		return $ch;
	}

	/**
	 * Prepare URL to realize restore worker task.
	 *
	 * @param array $source worker task data source with type (index 0) and handler (index 1)
	 * @param int $block data block
	 * @return string URL
	 */
	private function buildURL(array $source, array $block): string
	{
		[, $url] = $source;
		if ($url !== '') {
			$params = [
				'blockToken' => $block['BlockToken']
			];
			$url .= $block['BlockIndex'] . '?' . http_build_query($params);
		}
		return $url;
	}

	/**
	 * Prepare HTTP headers to send worker task data.
	 *
	 * @param string $url HTTP request URL
	 * @param array $data worker task data
	 * @return array HTTP headers
	 */
	private function buildHeaders(string $url, array $data): array
	{
		// Here put additional headers if needed
		$def_headers = [];

		// Get request headers (with SigV4 signature)
		$headers = $data['header_func'](
			'GET',
			$url,
			$def_headers,
			''
		);
		return $headers;
	}

	/**
	 * Prepare worker task result.
	 *
	 * @param mixed $data result task data
	 * @throws BWorkerTaskException on writing to destination error
	 */
	public function prepareResult($data)
	{
		Logging::log(
			Logging::CATEGORY_APPLICATION,
			"[$this]  AWS EC2 backup task finished. Prepare results."
		);
		$destination = $this->getDestination();
		$block = $this->prepareDataBlock(
			$data['url'],
			$data['headers'],
			$data['data'],
			$data['task_data']
		);
		$result = fwrite($destination, $block);
		if ($result === false) {
			$emsg = "[$this] Error while writing task data to destination.";
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
			throw new BWorkerTaskException($emsg, 1);
		}
	}

	/**
	 * Prepare single task data block to send to storage daemon.
	 *
	 * @param string $url source request URL
	 * @param array $headers source request HTTP headers
	 * @param string $data block data
	 * @param array $task_data task data
	 * @throws BWorkerTaskException if computed checksum does not match
	 * @return string block data ready to send to storage daemon
	 */
	private function prepareDataBlock(string $url, array $headers, string $data, array $task_data): string
	{
		$block_index = $task_data['block']['BlockIndex'];
		$hash = '';
		$state = null;
		if ($url !== '') {
			// Data block received from AWS
			$alg_name = $headers['x-amz-checksum-algorithm'];
			$alg = self::getHashAlg($alg_name);
			$checksum = $headers['x-amz-checksum'];
			$hash_bin = hash($alg, $data, true);
			$hash = base64_encode($hash_bin);
			if ($hash !== $checksum) {
				$emsg = "[$this] Wrong block checksum. Algorithm: '{$alg_name}', Header: '{$checksum}', Block: '{$hash}'.";
				Logging::log(
					Logging::CATEGORY_APPLICATION,
					$emsg
				);
				throw new BWorkerTaskException($emsg, 1);
			}
			$state = AWSEC2Data::DATA_STATE_WRITTEN;
		} else {
			// Local block - empty block
			$hash = '';
			$data = '';
			$state = AWSEC2Data::DATA_STATE_UNWRITTEN;
		}
		$metadata = [
			'type' => 'block_data',
			'block_index' => $block_index,
			'block_size' => AWSEC2Data::BLOCK_SIZE_BYTES,
			'hash' => $hash,
			'state' => $state
		];
		return BacularisDataFormat::encode($metadata, $data);
	}

	/**
	 * Get data hash algorithm code by hash algorithm name.
	 *
	 * @param string $name hash algorithm name
	 * @return string hash algorithm or empty string if algorithm not found
	 */
	private static function getHashAlg(string $name)
	{
		$alg = '';
		switch ($name) {
			case self::HASH_ALGORITHM_SHA256: $alg = 'sha256';
				break;
		}
		return $alg;
	}
}
