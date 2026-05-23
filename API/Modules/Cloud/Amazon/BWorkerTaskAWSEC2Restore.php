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
use Bacularis\Common\Modules\Logging;

/**
 * Bacularis worker task for AWS EBS Direct API.
 * This task is used to do restore.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BWorkerTaskAWSEC2Restore extends BWorkerTask
{
	/**
	 * Request timeout in seconds.
	 */
	private const REQUEST_TIMEOUT = 150; //@TODO: Make it configurable

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
		if ($data['state'] === AWSEC2Data::DATA_STATE_UNWRITTEN) {
			// Zero data block
			$data['block'] = AWSEC2Data::getZeroBlock();
		}
		$url = $this->buildURL($data['destination'], $data['block_index']);
		$headers = $this->buildHeaders($url, $data);
		$ch = curl_init();
		$params = [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_CUSTOMREQUEST => 'PUT',
			CURLOPT_HEADER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $data['block']
		];
		curl_setopt_array($ch, $params);
		Logging::log(Logging::CATEGORY_APPLICATION, "[$this] Run AWS EC2 restore task.");
		return $ch;
	}

	/**
	 * Prepare URL to realize restore worker task.
	 *
	 * @param array $destination worker task data destination with type (index 0) and handler (index 1)
	 * @param int $block_index block index
	 * @return string URL
	 */
	private function buildURL(array $destination, int $block_index): string
	{
		[, $url] = $destination;
		$url .= $block_index;
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
		if ($data['state'] === AWSEC2Data::DATA_STATE_WRITTEN) {
			// Regular data block
			$alg = self::getHashAlg(self::HASH_ALGORITHM_SHA256);
			$hash_bin = hash($alg, $data['block'], true);
			$hash = base64_encode($hash_bin);
		} elseif ($data['state'] === AWSEC2Data::DATA_STATE_UNWRITTEN) {
			// Zero data block
			$hash = AWSEC2Data::ZERO_BLOCK_CHECKSUM_SHA256_BASE64;
		}

		$def_headers = [
			'X-HTTP-Method-Override' => 'PUT',
			'x-amz-Data-Length' => strlen($data['block']),
			'x-amz-Progress' => 100,
			'x-amz-Checksum' => $hash,
			'x-amz-Checksum-Algorithm' => self::HASH_ALGORITHM_SHA256
		];

		// Get request headers (with SigV4 signature)
		$headers = $data['header_func'](
			'PUT',
			$url,
			$def_headers,
			$data['block']
		);
		return $headers;
	}

	/**
	 * Prepare worker task result.
	 * This method is called for successfully finished tasks.
	 *
	 * @param mixed $data result task data
	 */
	public function prepareResult($data)
	{
		$data['task_data']['changed_block_bs']->set($data['task_data']['block_index']);
		Logging::log(
			Logging::CATEGORY_APPLICATION,
			"[$this] AWS EC2 restore task finished. Prepare results."
		);
	}

	/**
	 * Get data hash algorithm code by hash algorithm name.
	 *
	 * @param string $name hash algorithm name
	 * @return string hash algorithm or empty string if algorithm not found
	 */
	private static function getHashAlg(string $name): string
	{
		$alg = '';
		switch ($name) {
			case self::HASH_ALGORITHM_SHA256: $alg = 'sha256';
				break;
		}
		return $alg;
	}
}
