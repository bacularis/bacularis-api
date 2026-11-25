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

namespace Bacularis\API\Modules;

use Bacularis\Common\Modules\Errors\TagError;

/**
 * Request and ACL handling shared by native tag endpoints.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
abstract class TagResourcePage extends BaculumAPIServer
{
	/**
	 * Manager class acccess identifier.
	 * For example: tag_client, tag_job...
	 */
	protected const MANAGER = '';

	/**
	 * Define if use required resource ID. It has meaning in single resource endpoints.
	 */
	protected const RESOURCE_ID_REQUIRED = false;

	/**
	 * Define ACL command to filter allowed resources.
	 * For example: .jobs, .client
	 */
	protected const ACL_COMMAND = null;

	/**
	 * Return native tag assignments for resources visible to the API user.
	 *
	 * @return void
	 */
	public function get(): void
	{
		$resource_id = null;
		if (static::RESOURCE_ID_REQUIRED) {
			/**
			 * This API request requires resource ID.
			 * This is for example /tags/jobs/123, /tags/clients/456 etc.
			 * Validate the ID parameter.
			 */
			$id = $this->Request['id'];
			$misc = $this->getModule('misc');
			$resource_id = (int) $id;
			if (!is_string($id) || !$misc->isValidId($id) || $resource_id < 1 || ltrim($id, '0') !== (string) $resource_id) {
				// Validation error - END
				$this->output = TagError::MSG_ERROR_RESOURCE_DOES_NOT_EXIST;
				$this->error = TagError::ERROR_RESOURCE_DOES_NOT_EXIST;
				return;
			}
		}

		$tag = null;
		if ($this->Request->contains('tag')) {
			/**
			 * This request uses tag filter.
			 * Fro example: /tags/jobs?tag=mytag123
			 */
			$tag = $this->Request['tag'];
			if (!is_string($tag) || strpos($tag, "\0") !== false) {
				// Tag parameters cannot contain a null byte - END.
				$this->output = TagError::MSG_ERROR_INVALID_TAG;
				$this->error = TagError::ERROR_INVALID_TAG;
				return;
			}
		}

		$allowed_resources = [];
		if (static::ACL_COMMAND !== null) {
			/**
			 * This request uses ACL resource validation.
			 */
			$bconsole = $this->getModule('bconsole');
			$result = $bconsole->bconsoleCommand($this->director, [static::ACL_COMMAND], null, true);
			if ($result->exitcode !== 0) {
				// There was an error while getting allowed ACL resources - END
				$output = TagError::MSG_ERROR_WRONG_EXITCODE;
				$output .= sprintf(' ExitCode: %d, Message: %s', $result->exitcode, $result->output);
				$this->output = $output;
				$this->error = TagError::ERROR_WRONG_EXITCODE;
				return;
			}
			$allowed_resources = array_fill_keys($result->output, true);
		}

		$manager = $this->getModule(static::MANAGER);
		$tags = $manager->getTags($resource_id, $tag, $allowed_resources);
		if ($tags === null) {
			// Resource does not exist
			$this->output = TagError::MSG_ERROR_RESOURCE_DOES_NOT_EXIST;
			$this->error = TagError::ERROR_RESOURCE_DOES_NOT_EXIST;
		} else {
			// Valid results
			$this->output = $tags;
			$this->error = TagError::ERROR_NO_ERRORS;
		}
	}
}
