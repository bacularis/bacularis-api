<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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

use Bacularis\API\Modules\SelfTestResult;

/**
 * Tools to perform health check self-test.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class SelfTest extends APIModule
{
	private function configModuleTest(): array
	{
		$result = [];
		$misc = $this->getModule('misc');
		$api_config = $this->getModule('api_config');
		$json_tools = $this->getModule('json_tools');
		$components = $misc->getComponents();
		$section = 'Bacula configuration';

		// check if Bacula config function is enabled
		$test = new SelfTestResult();
		$name = 'Bacula configuration feature is enabled.';
		$test->setName($name);
		$test->setSection($section);
		$test->setType('boolean');
		$function_enabled = $api_config->isJSONToolsEnabled();
		$test->setResult($function_enabled);
		$state = !$function_enabled ? SelfTestResult::TEST_RESULT_STATE_DISABLED : SelfTestResult::TEST_RESULT_STATE_INFO;
		$test->setState($state);
		$description = SelfTestResult::TEST_RESULT_DESC_OK;
		if (!$function_enabled) {
			$description = sprintf(
				'%s. If you plan to configure Bacula using the Bacularis web interface, Bacula configuration function needs to be enabled in the API.',
				SelfTestResult::TEST_RESULT_DESC_DISABLED
			);
		}
		$test->setDescription($description);
		$result[] = $test->toArray();

		for ($i = 0; $i < count($components); $i++) {
			$comp = $misc->getComponentFullName($components[$i]);

			// check if component config is configured
			$test = new SelfTestResult();
			$name = sprintf('%s configuration function is configured.', $comp);
			$test->setName($name);
			$test->setSection($section);
			$test->setType('boolean');
			$result_configured = $api_config->isJSONToolConfigured($components[$i]);
			$test->setResult($result_configured);
			$state_configured = !$function_enabled || !$result_configured ? SelfTestResult::TEST_RESULT_STATE_DISABLED : SelfTestResult::TEST_RESULT_STATE_INFO;
			$test->setState($state_configured);
			$description = SelfTestResult::TEST_RESULT_DESC_OK;
			if (!$function_enabled || !$result_configured) {
				$description = sprintf(
					'%s. If you plan to configure Bacula %s using the Bacularis web interface, Bacula %s configuration function needs to be configured in the API.',
					SelfTestResult::TEST_RESULT_DESC_DISABLED,
					$comp,
					$comp
				);
			}
			$test->setDescription($description);
			$result[] = $test->toArray();

			// check if config is readable
			$test = new SelfTestResult();
			$name = sprintf('%s configuration is readable.', $comp);
			$test->setName($name);
			$test->setSection($section);
			$test->setType('boolean');
			$state_read = '';
			$description = '';
			if ($function_enabled && $result_configured) {
				$ret = $json_tools->execCommand(
					$components[$i]
				);
				$result_read = ($ret['exitcode'] === 0);
				$test->setResult($result_read);
				$state_read = SelfTestResult::TEST_RESULT_STATE_INFO;
				$description = SelfTestResult::TEST_RESULT_DESC_OK;
				if (!$result_read) {
					$state_read = SelfTestResult::TEST_RESULT_STATE_ERROR;
					$description = sprintf(
						'%s. Bacula %s configuration is not possible to read.',
						SelfTestResult::TEST_RESULT_DESC_ERROR,
						$comp
					);
				}
			} else {
				$test->setResult(true);
				$state_read = SelfTestResult::TEST_RESULT_STATE_DISABLED;
				$description = sprintf(
					'%s. Test skipped.',
					SelfTestResult::TEST_RESULT_DESC_DISABLED
				);
			}
			$test->setState($state_read);
			$test->setDescription($description);
			$result[] = $test->toArray();

			// check if config is writeable
			$test = new SelfTestResult();
			$name = sprintf('%s configuration is writeable.', $comp);
			$test->setName($name);
			$test->setSection($section);
			$test->setType('boolean');
			$state_write = '';
			$description = '';
			if ($function_enabled && $result_configured) {
				$ret = $api_config->getJSONToolConfig(
					$components[$i]
				);
				$result_write = is_writeable($ret['cfg']);
				$test->setResult($result_write);
				$state_write = SelfTestResult::TEST_RESULT_STATE_INFO;
				$description = SelfTestResult::TEST_RESULT_DESC_OK;
				if (!$result_write) {
					$state_write = SelfTestResult::TEST_RESULT_STATE_ERROR;
					$description = sprintf(
						'%s. Bacula %s configuration is not possible to write.',
						SelfTestResult::TEST_RESULT_DESC_ERROR,
						$comp
					);
				}
			} else {
				$test->setResult(true);
				$state_write = SelfTestResult::TEST_RESULT_STATE_DISABLED;
				$description = sprintf(
					'%s. Test skipped.',
					SelfTestResult::TEST_RESULT_DESC_DISABLED
				);
			}
			$test->setState($state_write);
			$test->setDescription($description);
			$result[] = $test->toArray();

		}
		return $result;
	}

	private function bconsoleTest(): array
	{
		$result = [];
		$bconsole = $this->getModule('bconsole');
		$api_config = $this->getModule('api_config');
		$config = $api_config->getConfig('bconsole');
		$section = 'Bconsole';

		// check if Bacula console is enabled
		$test = new SelfTestResult();
		$name = 'Bconsole feature is enabled.';
		$test->setName($name);
		$test->setSection($section);
		$test->setType('boolean');
		$result_enabled = $config['enabled'] === '1';
		$test->setResult($result_enabled);
		$state_enabled = $result_enabled ? SelfTestResult::TEST_RESULT_STATE_INFO : SelfTestResult::TEST_RESULT_STATE_DISABLED;
		$test->setState($state_enabled);
		$description = SelfTestResult::TEST_RESULT_DESC_OK;
		if (!$result_enabled) {
			$description = SelfTestResult::TEST_RESULT_DESC_DISABLED;
		}
		$test->setDescription($description);
		$result[] = $test->toArray();

		// check if Bacula console is accessible
		$test = new SelfTestResult();
		$name = 'Bconsole is accessible.';
		$test->setName($name);
		$test->setSection($section);
		$test->setType('boolean');
		$sudo = [
			'use_sudo' => $config['use_sudo'],
			'user' => $config['sudo_user'] ?? '',
			'group' => $config['sudo_group'] ?? ''
		];
		$bcons_start = hrtime(true);
		$ret = $bconsole->testBconsoleCommand(['version'], $config['bin_path'], $config['cfg_path'], $sudo);
		$bcons_end = hrtime(true);
		$result_accessible = ($ret->exitcode === 0);
		$test->setResult($result_accessible);
		if ($result_enabled) {
			if ($result_accessible) {
				$state_accessible = SelfTestResult::TEST_RESULT_STATE_INFO;
				$description = SelfTestResult::TEST_RESULT_DESC_OK;
			} else {
				$state_accessible = SelfTestResult::TEST_RESULT_STATE_ERROR;
				$description = sprintf(
					'%s. Bconsole is enabled but not accessible.',
					SelfTestResult::TEST_RESULT_DESC_ERROR
				);
			}
		} else {
			$state_accessible = SelfTestResult::TEST_RESULT_STATE_DISABLED;
			$description = sprintf(
				'%s. Test skipped.',
				SelfTestResult::TEST_RESULT_DESC_DISABLED
			);
		}
		$test->setState($state_accessible);
		$test->setDescription($description);
		$result[] = $test->toArray();

		// check bconsole access time
		$test = new SelfTestResult();
		$time_treshold = 500; // in miliseconds
		$name = 'Bconsole command time.';
		$test->setName($name);
		$test->setSection($section);
		$test->setType('float');
		$description = $state_time = '';
		$result_time = 0;
		if ($result_enabled) {
			$result_time = (float) (($bcons_end - $bcons_start) / 1000000); // conversion from nano to miliseconds
			$result_time_sec = ($result_time / 1000);
			if ($result_time > $time_treshold) {
				$state_time = SelfTestResult::TEST_RESULT_STATE_WARNING;
				$description = sprintf(
					'Bconsole access takes long time (%0.2f sec.). It can cause the interface performance problems.',
					$result_time_sec
				);
			} else {
				$state_time = SelfTestResult::TEST_RESULT_STATE_INFO;
				$description = sprintf(
					'%s (%0.2f sec.)',
					SelfTestResult::TEST_RESULT_DESC_OK,
					$result_time_sec
				);
			}
		} else {
			$state_time = SelfTestResult::TEST_RESULT_STATE_DISABLED;
			$description = sprintf(
				'%s. Test skipped',
				SelfTestResult::TEST_RESULT_DESC_DISABLED
			);
		}
		$test->setResult($result_time);
		$test->setState($state_time);
		$test->setDescription($description);
		$result[] = $test->toArray();

		return $result;
	}

	private function catalogTest(): array
	{
		$result = [];
		$db = $this->getModule('db');
		$api_config = $this->getModule('api_config');
		$config = $api_config->getConfig('db');
		$section = 'Catalog';

		// check if Catalog is enabled
		$test = new SelfTestResult();
		$name = 'Catalog feature is enabled.';
		$test->setName($name);
		$test->setSection($section);
		$test->setType('boolean');
		$result_enabled = $config['enabled'] === '1';
		$test->setResult($result_enabled);
		$description = $state_enabled = '';
		if ($result_enabled) {
			$state_enabled = SelfTestResult::TEST_RESULT_STATE_INFO;
			$description = SelfTestResult::TEST_RESULT_DESC_OK;
		} else {
			$state_enabled = SelfTestResult::TEST_RESULT_STATE_DISABLED;
			$description = SelfTestResult::TEST_RESULT_DESC_DISABLED;
		}
		$test->setState($state_enabled);
		$test->setDescription($description);
		$result[] = $test->toArray();

		// check if Catalog is accessible
		$test = new SelfTestResult();
		$name = 'Catalog feature is accessible.';
		$test->setName($name);
		$test->setSection($section);
		$test->setType('boolean');
		$result_accessible = false;
		$state_accessible = $description = '';
		$catalog_start = hrtime(true);
		if ($result_enabled) {
			try {
				$result_accessible = $db->testCatalog();
			} catch (BCatalogException $e) {
				$result_accessible = false;
			}
			if ($result_accessible) {
				$state_accessible = SelfTestResult::TEST_RESULT_STATE_INFO;
				$description = SelfTestResult::TEST_RESULT_DESC_OK;
			} else {
				$state_accessible = SelfTestResult::TEST_RESULT_STATE_ERROR;
				$description = sprintf(
					'%s. Catalog database is not accessible',
					SelfTestResult::TEST_RESULT_DESC_ERROR
				);
			}
		} else {
			$state_accessible = SelfTestResult::TEST_RESULT_STATE_DISABLED;
			$description = sprintf(
				'%s. Test skipped',
				SelfTestResult::TEST_RESULT_DESC_DISABLED
			);
		}
		$catalog_end = hrtime(true);
		$test->setResult($result_accessible);
		$test->setState($state_accessible);
		$test->setDescription($description);
		$result[] = $test->toArray();


		// check the catalog access time
		$time_treshold = 150; // in miliseconds
		$name = 'Catalog access time.';
		$test = new SelfTestResult();
		$test->setName($name);
		$test->setSection($section);
		$test->setType('float');
		$description = $state_time = '';
		$result_time = 0;
		if ($result_enabled) {
			$result_time = (float) (($catalog_end - $catalog_start) / 1000000); // conversion from nano to miliseconds
			$result_time_sec = ($result_time / 1000);
			if ($result_time > $time_treshold) {
				$state_time = SelfTestResult::TEST_RESULT_STATE_WARNING;
				$description = sprintf(
					'Catalog access takes long time (%0.2f sec.). It can cause the interface performance problems.',
					$result_time_sec
				);
			} else {
				$state_time = SelfTestResult::TEST_RESULT_STATE_INFO;
				$description = sprintf(
					'%s (%0.2f sec.)',
					SelfTestResult::TEST_RESULT_DESC_OK,
					$result_time_sec
				);
			}
		} else {
			$state_time = SelfTestResult::TEST_RESULT_STATE_DISABLED;
			$description = sprintf(
				'%s. Test skipped',
				SelfTestResult::TEST_RESULT_DESC_DISABLED
			);
		}
		$test->setResult($result_time);
		$test->setState($state_time);
		$test->setDescription($description);
		$result[] = $test->toArray();

		return $result;
	}

	private function actionsTest(): array
	{
		$result = [];
		$bconsole = $this->getModule('bconsole');
		$api_config = $this->getModule('api_config');
		$section = 'Actions';

		// Check if actions are enabled
		$test = new SelfTestResult();
		$name = 'Actions feature is enabled.';
		$test->setName($name);
		$test->setSection($section);
		$test->setType('boolean');
		$result_enabled = $api_config->isActionsEnabled();
		$test->setResult($result_enabled);
		if ($result_enabled) {
			$state_enabled = SelfTestResult::TEST_RESULT_STATE_INFO;
			$description = SelfTestResult::TEST_RESULT_DESC_OK;
		} else {
			$state_enabled = SelfTestResult::TEST_RESULT_STATE_DISABLED;
			$description = SelfTestResult::TEST_RESULT_DESC_DISABLED;
		}
		$test->setState($state_enabled);
		$test->setDescription($description);
		$result[] = $test->toArray();

		return $result;
	}

	private function softwareManagementTest(): array
	{
		$result = [];
		$bconsole = $this->getModule('bconsole');
		$api_config = $this->getModule('api_config');
		$section = 'Software management';

		// Check if software management is enabled
		$test = new SelfTestResult();
		$name = 'Software management feature is enabled.';
		$test->setName($name);
		$test->setSection($section);
		$test->setType('boolean');
		$result_enabled = $api_config->isSoftwareManagementEnabled();
		$test->setResult($result_enabled);
		if ($result_enabled) {
			$state_enabled = SelfTestResult::TEST_RESULT_STATE_INFO;
			$description = SelfTestResult::TEST_RESULT_DESC_OK;
		} else {
			$state_enabled = SelfTestResult::TEST_RESULT_STATE_DISABLED;
			$description = SelfTestResult::TEST_RESULT_DESC_DISABLED;
		}
		$test->setState($state_enabled);
		$test->setDescription($description);
		$result[] = $test->toArray();

		return $result;
	}

	public function getTestResults(): array
	{
		$db = $this->catalogTest();
		$config = $this->configModuleTest();
		$bconsole = $this->bconsoleTest();
		$actions = $this->actionsTest();
		$software_mgmt = $this->softwareManagementTest();
		return array_merge($db, $bconsole, $config, $actions, $software_mgmt);
	}
}
