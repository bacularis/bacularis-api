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

/**
 * Prepare test plan for restore verification - restore test.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category API
 */
class RestoreVerifyPlan extends BaculumAPIServer
{
	public function create($params)
	{
		$test_id = null;
		if (
			property_exists($params, 'test_id') &&
			is_string($params->test_id) &&
			RestoreVerification::isValidTestId($params->test_id)
		) {
			$test_id = $params->test_id;
		}
		$plan = null;
		if (
			is_string($test_id) &&
			property_exists($params, 'plan') &&
			is_string($params->plan) &&
			RestoreVerification::validatePlan($test_id, $params->plan)
		) {
			$plan = $params->plan;
		}
		$result = false;
		if ($test_id && $plan) {
			$result = RestoreVerification::saveRestoreTestPlan($test_id, $plan);
		}

		if ($result) {
			$this->output = GenericError::MSG_ERROR_NO_ERRORS;
			$this->error = GenericError::ERROR_NO_ERRORS;
		} else {
			$this->output = GenericError::MSG_ERROR_WRONG_EXITCODE;
			$this->error = GenericError::ERROR_WRONG_EXITCODE;
		}
	}
}
