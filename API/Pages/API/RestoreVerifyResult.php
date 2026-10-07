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

use Bacularis\API\Modules\BaculumAPIServer;
use Bacularis\Common\Modules\Errors\GenericError;
use Bacularis\Common\Modules\RestoreVerification;
use Bacularis\Common\Modules\RestoreVerificationResult;
use Bacularis\Common\Modules\RestoreVerificationStatus;

/**
 * Manage result for restore verification.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class RestoreVerifyResult extends BaculumAPIServer
{
	/**
	 * Get completed restore verification result.
	 *
	 * @return void
	 */
	public function get(): void
	{
		$test_id = $this->getTestId();
		if ($test_id === null) {
			$this->output = GenericError::MSG_ERROR_INVALID_COMMAND . ' Invalid restore test identifier.';
			$this->error = GenericError::ERROR_INVALID_COMMAND;
			return;
		}
		if (!RestoreVerificationStatus::exists($test_id)) {
			$this->output = GenericError::MSG_ERROR_WRONG_EXITCODE . ' Restore verification status does not exist.';
			$this->error = GenericError::ERROR_WRONG_EXITCODE;
			return;
		}
		$status = RestoreVerificationStatus::get($test_id);
		if ($status === null) {
			$this->output = GenericError::MSG_ERROR_INTERNAL_ERROR . ' Invalid restore verification status.';
			$this->error = GenericError::ERROR_INTERNAL_ERROR;
			return;
		}
		if ($status['state'] === RestoreVerificationStatus::STATE_READY) {
			$this->output = sprintf(
				'%s Restore Verification did not start. The restore job may have ended before the verification step could run. State: %s.',
				GenericError::MSG_ERROR_NOT_READY,
				$status['state']
			);
			$this->error = GenericError::ERROR_NOT_READY;
			return;
		} elseif ($status['state'] === RestoreVerificationStatus::STATE_RUNNING) {
			$this->output = sprintf(
				'%s Restore Verification is still running. State: %s.',
				GenericError::MSG_ERROR_WRONG_EXITCODE,
				$status['state']
			);
			$this->error = GenericError::ERROR_WRONG_EXITCODE;
			return;
		} elseif ($status['state'] !== RestoreVerificationStatus::STATE_DONE) {
			$this->output = sprintf(
				'%s Restore Verification is in unknown state. State: %s.',
				GenericError::MSG_ERROR_WRONG_EXITCODE,
				$status['state']
			);
			$this->error = GenericError::ERROR_WRONG_EXITCODE;
			return;
		}
		if (!RestoreVerificationResult::exists($test_id)) {
			$this->output = GenericError::MSG_ERROR_WRONG_EXITCODE . ' Restore verification result does not exist.';
			$this->error = GenericError::ERROR_WRONG_EXITCODE;
			return;
		}
		$result = RestoreVerificationResult::get($test_id);
		if ($result === null) {
			$this->output = GenericError::MSG_ERROR_INTERNAL_ERROR . ' Invalid restore verification result.';
			$this->error = GenericError::ERROR_INTERNAL_ERROR;
			return;
		}
		$this->output = $result;
		$this->error = GenericError::ERROR_NO_ERRORS;
	}

	/**
	 * Delete completed restore verification result and status.
	 *
	 * @param mixed $id API resource identifier
	 * @return void
	 */
	public function remove($id): void
	{
		$test_id = $this->getTestId();
		if ($test_id === null) {
			$this->output = GenericError::MSG_ERROR_INVALID_COMMAND . ' Invalid restore test identifier.';
			$this->error = GenericError::ERROR_INVALID_COMMAND;
			return;
		}
		if (!RestoreVerificationStatus::exists($test_id)) {
			$this->output = GenericError::MSG_ERROR_WRONG_EXITCODE . ' Restore verification status does not exist.';
			$this->error = GenericError::ERROR_WRONG_EXITCODE;
			return;
		}
		$status = RestoreVerificationStatus::get($test_id);
		if ($status === null) {
			$this->output = GenericError::MSG_ERROR_INTERNAL_ERROR . ' Invalid restore verification status.';
			$this->error = GenericError::ERROR_INTERNAL_ERROR;
			return;
		}
		if ($status['state'] !== RestoreVerificationStatus::STATE_DONE) {
			$this->output = sprintf(
				'%s Restore verification result is not ready. State: %s.',
				GenericError::MSG_ERROR_WRONG_EXITCODE,
				$status['state']
			);
			$this->error = GenericError::ERROR_WRONG_EXITCODE;
			return;
		}

		$result = RestoreVerificationResult::delete($test_id);
		if (!$result) {
			$this->output = GenericError::MSG_ERROR_WRONG_EXITCODE . ' Unable to delete restore verification result.';
			$this->error = GenericError::ERROR_WRONG_EXITCODE;
			return;
		}
		$result = RestoreVerificationStatus::delete($test_id);
		if (!$result) {
			$this->output = GenericError::MSG_ERROR_WRONG_EXITCODE . ' Unable to delete restore verification status.';
			$this->error = GenericError::ERROR_WRONG_EXITCODE;
			return;
		}
		$this->output = GenericError::MSG_ERROR_NO_ERRORS;
		$this->error = GenericError::ERROR_NO_ERRORS;
	}

	/**
	 * Get and validate restore test identifier from request.
	 *
	 * @return null|string restore test identifier or null if invalid
	 */
	private function getTestId(): ?string
	{
		$test_id = $this->Request->contains('test_id') ? $this->Request['test_id'] : null;
		return is_string($test_id) && RestoreVerification::isValidTestId($test_id) ? $test_id : null;
	}
}
