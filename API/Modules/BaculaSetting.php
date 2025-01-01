<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
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

namespace Bacularis\API\Modules;

use Bacularis\Common\Modules\Logging;
use Bacularis\Common\Modules\Params;
use Bacularis\Common\Modules\Errors\BaculaConfigError;
use Bacularis\Common\Modules\Errors\JSONToolsError;

/**
 * Read/write Bacula configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class BaculaSetting extends APIModule
{
	public const COMPONENT_DIR_TYPE = 'dir';
	public const COMPONENT_SD_TYPE = 'sd';
	public const COMPONENT_FD_TYPE = 'fd';
	public const COMPONENT_BCONS_TYPE = 'bcons';

	public const MODE_SAVE = 'save';
	public const MODE_SIMULATE = 'simulate';

	/**
	 * These string value directives cannot by defined in quotes.
	 */
	private $unquoted_string_directives = [
		'Director' => [
			'DirAddress',
			'DirAddresses',
			'DirSourceAddress'
		],
		'FileDaemon' => [
			'FdAddress',
			'FdAddresses',
			'FdSourceAddress'
		],
		'Storage' => [
			'SdAddress',
			'SdAddresses'
		],
		'Messages' => [
			'Append',
			'Catalog',
			'Console',
			'Director',
			'File',
			'Mail',
			'MailOnError',
			'MailOnSuccess',
			'Operator',
			'Stderr',
			'Stdout',
			'Syslog'
		]
	];

	private function getComponentTypes()
	{
		$types = [
			self::COMPONENT_DIR_TYPE,
			self::COMPONENT_SD_TYPE,
			self::COMPONENT_FD_TYPE,
			self::COMPONENT_BCONS_TYPE
		];
		return $types;
	}

	public function getConfig($component_type = null, $resource_type = null, $resource_name = null, $opts = [])
	{
		$this->checkConfigSupport($component_type);
		$config = [];
		$json_tools = $this->Application->getModule('json_tools');
		if (!is_null($component_type)) {
			// get resources config
			$params = [];
			if ($component_type == self::COMPONENT_DIR_TYPE && (!key_exists('apply_jobdefs', $opts) || $opts['apply_jobdefs'] == false)) {
				$params['dont_apply_jobdefs'] = true;
			}
			if (!is_null($resource_type)) {
				$params['resource_type'] = $resource_type;
			}
			if (!is_null($resource_name)) {
				$params['resource_name'] = $resource_name;
			}
			$config = $json_tools->execCommand($component_type, $params);
		} else {
			// get components config
			$config = $this->getComponents();
		}
		return $config;
	}

	private function getComponents()
	{
		$components_info = [];
		$json_tools = $this->Application->getModule('json_tools');
		$components = $this->getSupportedComponents();
		$is_any = false;
		for ($i = 0; $i < count($components); $i++) {
			$component_type = $components[$i];
			$component_name = '';
			$error_msg = '';
			$resource_type = $this->Application->getModule('misc')->getMainComponentResource($component_type);
			$directive_name = 'Name';
			$params = [
				'resource_type' => $resource_type,
				'directive_name' => $directive_name,
				'data_only' => true
			];
			$result = $json_tools->execCommand($component_type, $params);
			$state = ($result['exitcode'] === 0 && is_array($result['output']));
			if ($state === true) {
				$is_any = true;
				if (count($result['output']) > 0) {
					$component_directive = array_pop($result['output']);
					if (array_key_exists($directive_name, $component_directive)) {
						$component_name = $component_directive[$directive_name];
					}
				}
			} else {
				/**
				 * Unable to get component info (tool returned an error).
				 * Keep error message and continue with rest components.
				 */
				$error_msg = $result['output'];
			}
			$component = [
				'component_type' => $component_type,
				'component_name' => $component_name,
				'state' => $state,
				'error_msg' => $error_msg
			];
			array_push($components_info, $component);
		}

		$error = BaculaConfigError::ERROR_NO_ERRORS;
		if ($is_any === false) {
			$error = BaculaConfigError::ERROR_CONFIG_NO_JSONTOOL_READY;
		}
		$result = $json_tools->prepareResult($components_info, $error);
		return $result;
	}

	public function setConfig($config, $component_type, $resource_type = null, $resource_name = null, $mode = BaculaSetting::MODE_SAVE)
	{
		$ret = ['is_valid' => false, 'save_result' => false, 'result' => null];
		$this->checkConfigSupport($component_type);
		$json_tools = $this->Application->getModule('json_tools');
		$params = [];
		if ($component_type == self::COMPONENT_DIR_TYPE) {
			$params['dont_apply_jobdefs'] = true;
		}
		$result = $json_tools->execCommand($component_type, $params);
		if ($result['exitcode'] === 0 && is_array($result['output'])) {
			$config_orig = $result['output'];
			if (!is_null($resource_type) && !is_null($resource_name)) {
				// Set single resource
				$config_new = [$resource_type => $config];
			} else {
				// Set whole config
				$config_new = $config;
			}
			$ret = $this->saveConfig($config_orig, $config_new, $component_type, $resource_type, $resource_name, $mode);
		} else {
			$ret['result'] = $result;
		}
		return $ret;
	}

	private function saveConfig(array $config_orig, array $config_new, $component_type, $resource_type = null, $resource_name = null, $mode = null)
	{
		$config = [];

		if (!is_null($resource_type) && !is_null($resource_name)) {
			// Update single resource in config

			$config = $this->updateConfigResource(
				$config_orig,
				$config_new,
				$component_type,
				$resource_type,
				$resource_name
			);
		} elseif (count($config_orig) > 0 && !is_null($resource_type)) {
			// Update whole config
			$config = $this->updateConfig($config_orig, $config_new);
		} elseif (count($config_new) > 0) {
			// Add new config (create component config)
			$config = $config_new;
			for ($i = 0; $i < count($config); $i++) {
				// update resource for formatting values
				$config[$i] = $this->updateResource($config[$i], $config[$i]);
			}
		}
		// Save config to file
		return $this->getModule('bacula_config')->setConfig($component_type, $config, null, $mode);
	}

	private function updateConfig(array $config_orig, array $config_new)
	{
		$config = $config_orig;
		$updated_res = [];
		for ($i = 0; $i < count($config_new); $i++) {
			$resource_new = $config_new[$i];
			$found = false;
			for ($j = 0; $j < count($config_orig); $j++) {
				$resource_orig = $config_orig[$j];
				if ($this->compareResources([$resource_orig, $resource_new]) === true) {
					// Resource type and name are the same. Update directives.
					$config[$j] = $this->updateResource($resource_orig, $resource_new);
					$updated_res[] = $config[$j];
					$found = true;
					break;
				}
			}
			if (!$found) {
				// Newly added resource
				$config[] = $resource_new;
			}
		}

		/**
		 * Now there is needed to update all resources to get
		 * formatted directive values in all config directives.
		 */
		for ($i = 0; $i < count($config); $i++) {
			$resource = $config[$i];
			for ($j = 0; $j < count($updated_res); $j++) {
				if ($this->compareResources([$resource, $updated_res[$j]]) === true) {
					// skip already formatted resources
					continue 2;
				}
			}
			// Rewrite not modified resource
			$config[$i] = $this->updateResource($resource, $resource);
		}
		return $config;
	}

	private function updateConfigResource(array $config_orig, array $resource, $component_type, $resource_type, $resource_name)
	{
		$config = [];

		if ($resource[$resource_type]['Name'] !== $resource_name) {
			// Resource rename
			$this->updateResourceDependencies(
				$config_orig,
				$component_type,
				$resource_type,
				$resource_name,
				$resource[$resource_type]['Name']
			);
		}

		$is_update = false;
		for ($i = 0; $i < count($config_orig); $i++) {
			$resource_orig = $config_orig[$i];
			if ($this->compareResources([$resource_orig, $resource]) === true) {
				// Resource type and name are the same. Update directives.
				$config[] = $this->updateResource($resource_orig, $resource);
				$is_update = true;
			} else {
				// Rewrite not modified resource
				$config[] = $this->updateResource($resource_orig, $resource_orig);
			}
		}
		if ($is_update === false) {
			$resource_fname = $this->formatDirectiveValue($resource_type, 'Name', $resource_name);
			// Existing resource with changed name, or new resource
			$resource_index = $this->getConfigResourceIndex($config, $resource_type, $resource_fname);
			if (!is_null($resource_index)) {
				// Existing resource
				$resource_orig = $config[$resource_index];

				// Remove existing resource
				array_splice($config, $resource_index, 1);
				// Add resource with new name
				$config[] = $this->updateResource($resource_orig, $resource);
			} else {
				// Add new resource
				$config[] = $this->updateResource([$resource_type => []], $resource);
			}
		}
		return $config;
	}

	private function updateResource(array $resource_orig, array $resource_new)
	{
		$resource = [];
		$resource_type_orig = key($resource_orig);
		$resource_type_new = key($resource_new);

		if ($resource_type_new === 'Schedule') {
			$resource_type = $resource_type_new;
			$resource = [$resource_type => []];
			foreach ($resource_new[$resource_type] as $directive_name => $directive_value) {
				if ($directive_name === 'Run' || $directive_name === 'Connect') {
					for ($i = 0; $i < count($directive_value); $i++) {
						if (is_array($directive_value[$i])) {
							if (key_exists('Hour', $directive_value[$i])) {
								$values = [];
								foreach ($directive_value[$i] as $value) {
									$values[] = $this->formatDirectiveValue(
										$resource_type,
										$directive_name,
										$value
									);
								}
								$overwrite_directive = array_map(
									[__NAMESPACE__ . '\BaculaSetting', 'overwrite_directives_callback'],
									array_keys($directive_value[$i]),
									$values
								);
								$overwrite_directive = implode(' ', array_filter($overwrite_directive));
								$min = 0;
								/**
								 * Check if Minute key exists because of bug about missing Minute
								 * @see http://bugs.bacula.org/view.php?id=2318
								 */
								if (array_key_exists('Minute', $directive_value[$i])) {
									$min = $directive_value[$i]['Minute'];
								}
								$moys = Params::getMonthsOfYearConfig($directive_value[$i]['Month']);
								$woys = Params::getWeeksOfYearConfig($directive_value[$i]['WeekOfYear']);
								$doms = Params::getDaysOfMonthConfig($directive_value[$i]['Day']);
								$woms = Params::getWeeksOfMonthConfig($directive_value[$i]['WeekOfMonth']);
								$dows = Params::getDaysOfWeekConfig($directive_value[$i]['DayOfWeek']);
								$t = Params::getTimeConfig($directive_value[$i]['Hour'], $min);
								$value = [$overwrite_directive, $moys, $woys, $doms, $woms, $dows, $t];
								$value = array_filter($value);
								if (!array_key_exists($directive_name, $resource[$resource_type])) {
									$resource[$resource_type][$directive_name] = [];
								}
								$resource[$resource_type][$directive_name][] = implode(' ', $value);
							} else {
								$resource[$resource_type][$directive_name][] = implode(' ', $directive_value[$i]);
							}
						} else {
							$resource[$resource_type][$directive_name][] = $directive_value[$i];
						}
					}
				} else {
					$resource[$resource_type][$directive_name] = $this->formatDirectiveValue($resource_type, $directive_name, $directive_value);
				}
			}
		} elseif ($resource_type_new === 'Messages') {
			$resource_type = $resource_type_new;
			$resource = [$resource_type => []];
			foreach ($resource_new[$resource_type] as $directive_name => $directive_value) {
				if ($directive_name === 'Destinations') {
					for ($i = 0; $i < count($directive_value); $i++) {
						$value = [];
						if (array_key_exists('Where', $directive_value[$i])) {
							array_push($value, implode(',', $directive_value[$i]['Where']));
						}
						array_push($value, implode(', ', $directive_value[$i]['MsgTypes']));
						$resource[$resource_type][$directive_value[$i]['Type']] = implode(' = ', $value);
					}
				} else {
					$resource[$resource_type][$directive_name] = $this->formatDirectiveValue($resource_type, $directive_name, $directive_value);
				}
			}
		} elseif ($resource_type_orig === $resource_type_new) {
			$resource_type = $resource_type_orig;
			$resource = [$resource_type => []];

			foreach ($resource_orig[$resource_type] as $directive_name => $directive_value) {
				if (!array_key_exists($directive_name, $resource_new[$resource_type])) {
					// directive removed in resource
					continue;
				}
				if (is_array($resource_new[$resource_type][$directive_name])) {
					// nested directive (name { key = val })
					$resource[$resource_type][$directive_name] = $this->updateSubResource($resource_type, $directive_name, $resource_new[$resource_type][$directive_name]);
				} else {
					// simple directive (key=val)
					// existing directive in resource
					$resource[$resource_type][$directive_name] = $this->formatDirectiveValue($resource_type, $directive_name, $resource_new[$resource_type][$directive_name]);
				}
			}
			foreach ($resource_new[$resource_type] as $directive_name => $directive_value) {
				if (!array_key_exists($directive_name, $resource_orig[$resource_type])) {
					// new directive in resource
					$resource[$resource_type][$directive_name] = $this->formatDirectiveValue($resource_type, $directive_name, $directive_value);
				}
			}
		} else {
			// It shouldn't happen.
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				'Attemp to update resource with different resource types.'
			);
			$resource = $resource_orig;
		}
		return $resource;
	}

	private function updateSubResource($resource_type, $directive_name, array $subresource_new)
	{
		$resource = [];
		foreach ($subresource_new as $index => $directive_value) {
			$check_recursive = false;
			if (is_array($directive_value)) {
				$assoc_keys = array_filter(array_keys($directive_value), 'is_string');
				$check_recursive = count($assoc_keys) > 0;
			}
			if ($check_recursive === true) {
				$resource[$index] = $this->updateSubResource($resource_type, $directive_name, $directive_value);
			} else {
				/**
				 * Because of bug in bdirjson: http://bugs.bacula.org/view.php?id=2464
				 * Here is workaround for bdirjson from Bacula versions without fix for it.
				 * @TODO: Remove it from here ASAP, here shouldn't be this type conditions
				 */
				if ($index === 'RunsWhen' && $directive_value === 'Any') {
					$directive_value = 'Always';
				}
				$resource[$index] = $this->formatDirectiveValue($resource_type, $directive_name, $directive_value);
			}
		}
		return $resource;
	}


	private function compareResources(array $resources)
	{
		$same_resource = false;
		$items = ['type' => [], 'name' => []];
		$resources_count = count($resources);
		$counter = 0;
		for ($i = 0; $i < $resources_count; $i++) {
			if (count($resources[$i]) === 1) {
				$resource_type = key($resources[$i]);
				if (array_key_exists('Name', $resources[$i][$resource_type])) {
					$items['type'][] = $resource_type;
					$items['name'][] = $resources[$i][$resource_type]['Name'];
					$counter++;
				}
			}
		}
		if ($resources_count > 1 && $resources_count === $counter) {
			$result = false;
			foreach ($items as $key => $value) {
				$result = (count(array_unique($value)) === 1);
				if ($result === false) {
					break;
				}
			}
			$same_resource = $result;
		}
		return $same_resource;
	}

	private function getConfigResourceIndex($config, $resource_type, $resource_name)
	{
		$index = null;
		$find_resource = [$resource_type => ['Name' => $resource_name]];
		for ($i = 0; $i < count($config); $i++) {
			if ($this->compareResources([$config[$i], $find_resource]) === true) {
				$index = $i;
				break;
			}
		}
		return $index;
	}

	/**
	 * Format directive value.
	 * It is used on write config action to last prepare config before
	 * sending it to config writer.
	 *
	 * @param string $resource_type resource type name
	 * @param string $directive_name directive name
	 * @param mixed $value directive value
	 * @return mixed formatted directive value
	 */
	private function formatDirectiveValue($resource_type, $directive_name, $value)
	{
		$directive_value = null;
		if (is_bool($value)) {
			$directive_value = Params::getBoolValue($value);
		} elseif (is_int($value)) {
			$directive_value = $value;
		} elseif (is_string($value)) {
			if (!key_exists($resource_type, $this->unquoted_string_directives) || !in_array($directive_name, $this->unquoted_string_directives[$resource_type])) {
				$value = addcslashes($value, '\\"');
				$value = "\"$value\"";
			}
			$directive_value = $value;
		} elseif (is_array($value)) {
			$assoc_keys = array_filter(array_keys($value), 'is_string');
			if (count($assoc_keys) > 0) {
				// associative arrays (sub-resources)
				$directive_value = $this->updateSubResource($resource_type, $directive_name, $value);
			} else {
				// only simple numeric arrays
				$dvalues = [];
				for ($i = 0; $i < count($value); $i++) {
					if (is_array($value[$i])) {
						$dvalues[] = $this->updateSubResource($resource_type, $directive_name, $value[$i]);
					} else {
						$dvalues[] = $this->formatDirectiveValue($resource_type, $directive_name, $value[$i]);
					}
				}
				$directive_value = $dvalues;
			}
		} else {
			$emsg = sprintf(
				"Attemp to format a directive value with not supported value type '%s'.",
				gettype($value)
			);
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
		}
		return $directive_value;
	}

	/**
	 * Update resource dependencies.
	 * Note, passing config by reference.
	 *
	 * @param array $config entire config
	 * @param string $component_type component type
	 * @param string $resource_type resource type to rename
	 * @param string $resource_name resource name to rename
	 * @param string $resource_name_new new resource name to set
	 */
	private function updateResourceDependencies(&$config, $component_type, $resource_type, $resource_name, $resource_name_new)
	{
		// Update dependencies if exist
		$deps = $this->getModule('data_deps')->checkDependencies(
			$component_type,
			$resource_type,
			$resource_name,
			$config
		);
		if (count($deps) > 0) {
			// Dependencies exist. Update them.
			for ($i = 0; $i < count($config); $i++) {
				foreach ($config[$i] as $rtype => $resource) {
					for ($j = 0; $j < count($deps); $j++) {
						if ($rtype === $deps[$j]['resource_type'] && $resource['Name'] === $deps[$j]['resource_name']) {
							// special Schedule treating
							if ($deps[$j]['resource_type'] === 'Schedule' && key_exists('Run', $resource)) {
								for ($k = 0; $k < count($resource['Run']); $k++) {
									$deps_directive = $deps[$j]['directive_name'];
									if (key_exists($deps_directive, $resource['Run'][$k]) && $resource['Run'][$k][$deps_directive] === $resource_name) {
										$config[$i][$rtype]['Run'][$k][$deps_directive] = $resource_name_new;
									}
								}
							} else {
								// rest directives
								// Change resource name in dependent resources
								$config[$i][$rtype][$deps[$j]['directive_name']] = $resource_name_new;
							}
						}
					}
					if ($rtype === $resource_type && $resource['Name'] === $resource_name) {
						// Change resource name
						$config[$i][$rtype]['Name'] = $resource_name_new;
					}
				}
			}
		}
	}

	/**
	 * Get supported component types.
	 * The support is determined by configured JSON tool in API config.
	 * If API is able to use JSON tool for specific component then the component is supported.
	 * Currently a component type is the same as related JSON tool type, but it can be
	 * changed in the future. From this reason components have theirown types.
	 *
	 * @throws BConfigException if json tools support is disabled
	 * @return array supported component types
	 */
	public function getSupportedComponents()
	{
		$components = [];
		$types = $this->getComponentTypes();
		$tools = $this->getModule('api_config')->getSupportedJSONTools();
		for ($i = 0; $i < count($tools); $i++) {
			if (in_array($tools[$i], $types)) {
				array_push($components, $tools[$i]);
			}
		}
		return $components;
	}

	/**
	 * Check if config support is configured and enabled
	 * globally and for specific component type.
	 *
	 * @private
	 * @param mixed $component_type component type for which config support is checked
	 * @throws BConfigException if support is not configured or disabled
	 */
	private function checkConfigSupport($component_type = null)
	{
		$api_cfg = $this->getModule('api_config');
		if (!$api_cfg->isJSONToolsConfigured($component_type) || !$api_cfg->isJSONToolsEnabled()) {
			throw new BConfigException(
				JSONToolsError::MSG_ERROR_JSON_TOOLS_DISABLED,
				JSONToolsError::ERROR_JSON_TOOLS_DISABLED
			);
		} elseif (!is_null($component_type) && !$api_cfg->isJSONToolConfigured($component_type)) {
			$emsg = ' JSONTool=>' . $component_type;
			throw new BConfigException(
				JSONToolsError::MSG_ERROR_JSON_TOOL_NOT_CONFIGURED . $emsg,
				JSONToolsError::ERROR_JSON_TOOLS_DISABLED
			);
		}
	}

	/**
	 * Get JSON tool type by component type.
	 * Currently the mapping is one-to-one because each component type is the same
	 * as json tool type (dir == dir, bcons == bcons ...etc.). The method is for
	 * hypothetical case when component type is different than json tool type.
	 * It can be useful in future.
	 *
	 * @param string $component_type component type
	 * @return string json tool type
	 */
	public function getJSONToolTypeByComponentType($component_type)
	{
		$tool_type = null;
		switch ($component_type) {
			case self::COMPONENT_DIR_TYPE: $tool_type = APIConfig::JSON_TOOL_DIR_TYPE;
				break;
			case self::COMPONENT_SD_TYPE: $tool_type = APIConfig::JSON_TOOL_SD_TYPE;
				break;
			case self::COMPONENT_FD_TYPE: $tool_type = APIConfig::JSON_TOOL_FD_TYPE;
				break;
			case self::COMPONENT_BCONS_TYPE: $tool_type = APIConfig::JSON_TOOL_BCONS_TYPE;
				break;
		}
		return $tool_type;
	}

	private function overwrite_directives_callback($directive_name, $directive_value)
	{
		$directive = '';
		$overwrite_directives = [
			'Level',
			'Pool',
			'Storage',
			'Messages',
			'FullPool',
			'DifferentialPool',
			'IncrementalPool',
			'Accurate',
			'Priority',
			'SpoolData',
			'MaxRunSchedTime',
			'NextPool',
			'MaxConnectTime'
		];
		if (in_array($directive_name, $overwrite_directives)) {
			$directive = "{$directive_name}={$directive_value}";
		}
		return $directive;
	}
}
