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

/**
 * Bacularis AWS EC2 data module.
 * It provides data tools for AWS EC2 backup/restore.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class AWSEC2Data
{
	/**
	 * Data block is empty in backup.
	 */
	public const DATA_STATE_UNWRITTEN = 0;

	/**
	 * Data block is written in backup.
	 */
	public const DATA_STATE_WRITTEN = 1;

	/**
	 * Data block size.
	 */
	public const BLOCK_SIZE_BYTES = 524288;

	/**
	 * Zero block checksum SHA256 Base64 encoded.
	 */
	public const ZERO_BLOCK_CHECKSUM_SHA256_BASE64 = 'B4VNL+8pega6gWheZgwzLeNtXRjVRpJ9MNqtbX/aFUE=';

	private static $zero_block = null;

	public static function getZeroBlock(): string
	{
		if (is_null(self::$zero_block)) {
			self::$zero_block = str_repeat("\x00", self::BLOCK_SIZE_BYTES);
		}
		return self::$zero_block;
	}
}
