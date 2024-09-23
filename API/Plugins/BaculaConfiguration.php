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

use Bacularis\Common\Modules\IBacularisBaculaConfigurationPlugin;
use Bacularis\API\Modules\BacularisAPIPluginBase;

/**
 * The Bacularis Bacula configuration plugin module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Plugin
 */
class BaculaConfiguration extends BacularisAPIPluginBase implements IBacularisBaculaConfigurationPlugin
{
	/**
	 * Get plugin name displayed in API panel.
	 *
	 * @return string plugin name
	 */
	public static function getName(): string
	{
		return 'Bacula configuration';
	}

	/**
	 * Get plugin version.
	 *
	 * @return string plugin version
	 */
	public static function getVersion(): string
	{
		return '1.0.0';
	}

	/**
	 * Get plugin type.
	 *
	 * @return string plugin type
	 */
	public static function getType(): string
	{
		return 'bacula-configuration';
	}

	/**
	 * Get plugin configuration parameters.
	 *
	 * return array plugin parameters
	 */
	public static function getParameters(): array
	{
		return [
			['name' => 'pre_read_command', 'type' => 'string', 'default' => '', 'label' => 'Pre-read config command'],
			['name' => 'post_read_command', 'type' => 'string', 'default' => '', 'label' => 'Post-read config command'],
			['name' => 'pre_create_command', 'type' => 'string', 'default' => '', 'label' => 'Pre-create config command'],
			['name' => 'post_create_command', 'type' => 'string', 'default' => '', 'label' => 'Post-create config command'],
			['name' => 'pre_update_command', 'type' => 'string', 'default' => '', 'label' => 'Pre-update config command'],
			['name' => 'post_update_command', 'type' => 'string', 'default' => '', 'label' => 'Post-update config command'],
			['name' => 'pre_delete_command', 'type' => 'string', 'default' => '', 'label' => 'Pre-delete config command'],
			['name' => 'post_delete_command', 'type' => 'string', 'default' => '', 'label' => 'Post-delete config command'],
			['name' => 'write_config_command', 'type' => 'string', 'default' => '', 'label' => 'Write Bacula config command']
		];
	}

	/**
	 * Pre-config read action.
	 *
	 * @param string $component_type Bacula component type (dir, sd, fd, bcons)
	 * @param string $resource_type Bacula resource type (Job, Fileset, Client ...etc.)
	 * @param string $resource_name Bacula resource name (MyJob, my_client-fd ...etc)
	 */
	public function preConfigRead(?string $component_type, ?string $resource_type, ?string $resource_name): void
	{
		$config = $this->getConfig();
		if ($config['parameters']['pre_read_command']) {
			$cmd = $this->getCommand(
				$config['parameters']['pre_read_command'],
				$component_type,
				$resource_type,
				$resource_name
			);
			$this->sendToScript($cmd);
		}
	}

	/**
	 * Post-config read action.
	 * Action is called if the configuration is read successfully.
	 *
	 * @param string $component_type Bacula component type (dir, sd, fd, bcons)
	 * @param string $resource_type Bacula resource type (Job, Fileset, Client ...etc.)
	 * @param string $resource_name Bacula resource name (MyJob, my_client-fd ...etc)
	 * @param array $bconfig Bacula configuration
	 */
	public function postConfigRead(?string $component_type, ?string $resource_type, ?string $resource_name, array $bconfig = []): void
	{
		$config = $this->getConfig();
		if ($config['parameters']['post_read_command']) {
			$cmd = $this->getCommand(
				$config['parameters']['post_read_command'],
				$component_type,
				$resource_type,
				$resource_name
			);
			$str = json_encode($bconfig);
			$this->sendToScript($cmd, $str);
		}
	}

	/**
	 * Pre-config create action.
	 *
	 * @param string $component_type Bacula component type (dir, sd, fd, bcons)
	 * @param string $resource_type Bacula resource type (Job, Fileset, Client ...etc.)
	 * @param string $resource_name Bacula resource name (MyJob, my_client-fd ...etc)
	 * @param array $bconfig Bacula configuration to add
	 */
	public function preConfigCreate(?string $component_type, ?string $resource_type, ?string $resource_name, array $bconfig = []): void
	{
		$config = $this->getConfig();
		if ($config['parameters']['pre_create_command']) {
			$cmd = $this->getCommand(
				$config['parameters']['pre_create_command'],
				$component_type,
				$resource_type,
				$resource_name
			);
			$str = json_encode($bconfig);
			$this->sendToScript($cmd, $str);
		}
	}

	/**
	 * Post-config save action.
	 *
	 * @param string $component_type Bacula component type (dir, sd, fd, bcons)
	 * @param string $resource_type Bacula resource type (Job, Fileset, Client ...etc.)
	 * @param string $resource_name Bacula resource name (MyJob, my_client-fd ...etc)
	 * @param array $bconfig all Bacula configuration after saving
	 */
	public function postConfigCreate(?string $component_type, ?string $resource_type, ?string $resource_name, array $bconfig = []): void
	{
		$config = $this->getConfig();
		if ($config['parameters']['post_create_command']) {
			$cmd = $this->getCommand(
				$config['parameters']['post_create_command'],
				$component_type,
				$resource_type,
				$resource_name
			);
			$str = json_encode($bconfig);
			$this->sendToScript($cmd, $str);
		}
	}

	/**
	 * Pre-config update action.
	 *
	 * @param string $component_type Bacula component type (dir, sd, fd, bcons)
	 * @param string $resource_type Bacula resource type (Job, Fileset, Client ...etc.)
	 * @param string $resource_name Bacula resource name (MyJob, my_client-fd ...etc)
	 * @param array $bconfig Bacula configuration to update
	 */
	public function preConfigUpdate(?string $component_type, ?string $resource_type, ?string $resource_name, array $bconfig = []): void
	{
		$config = $this->getConfig();
		if ($config['parameters']['pre_update_command']) {
			$cmd = $this->getCommand(
				$config['parameters']['pre_update_command'],
				$component_type,
				$resource_type,
				$resource_name
			);
			$str = json_encode($bconfig);
			$this->sendToScript($cmd, $str);
		}
	}

	/**
	 * Post-config update action.
	 *
	 * @param string $component_type Bacula component type (dir, sd, fd, bcons)
	 * @param string $resource_type Bacula resource type (Job, Fileset, Client ...etc.)
	 * @param string $resource_name Bacula resource name (MyJob, my_client-fd ...etc)
	 * @param array $bconfig all Bacula configuration after updating
	 */
	public function postConfigUpdate(?string $component_type, ?string $resource_type, ?string $resource_name, array $bconfig = []): void
	{
		$config = $this->getConfig();
		if ($config['parameters']['post_update_command']) {
			$cmd = $this->getCommand(
				$config['parameters']['post_update_command'],
				$component_type,
				$resource_type,
				$resource_name
			);
			$str = json_encode($bconfig);
			$this->sendToScript($cmd, $str);
		}
	}

	/**
	 * Pre-config delete action.
	 *
	 * @param string $component_type Bacula component type (dir, sd, fd, bcons)
	 * @param string $resource_type Bacula resource type (Job, Fileset, Client ...etc.)
	 * @param string $resource_name Bacula resource name (MyJob, my_client-fd ...etc)
	 */
	public function preConfigDelete(?string $component_type, ?string $resource_type, ?string $resource_name): void
	{
		$config = $this->getConfig();
		if ($config['parameters']['pre_delete_command']) {
			$cmd = $this->getCommand(
				$config['parameters']['pre_delete_command'],
				$component_type,
				$resource_type,
				$resource_name
			);
			$this->sendToScript($cmd);
		}
	}

	/**
	 * Post-config delete action.
	 *
	 * @param string $component_type Bacula component type (dir, sd, fd, bcons)
	 * @param string $resource_type Bacula resource type (Job, Fileset, Client ...etc.)
	 * @param string $resource_name Bacula resource name (MyJob, my_client-fd ...etc)
	 */
	public function postConfigDelete(?string $component_type, ?string $resource_type, ?string $resource_name): void
	{
		$config = $this->getConfig();
		if ($config['parameters']['post_delete_command']) {
			$cmd = $this->getCommand(
				$config['parameters']['post_delete_command'],
				$component_type,
				$resource_type,
				$resource_name
			);
			$this->sendToScript($cmd);
		}
	}

	/**
	 * Write Bacula config action.
	 *
	 * @param string $bconfig Bacula configuration
	 */
	public function writeConfig(string $bconfig): void
	{
		$config = $this->getConfig();
		if ($config['parameters']['write_config_command']) {
			$cmd = $config['parameters']['write_config_command'];
			$this->sendToScript($cmd, $bconfig);
		}
	}

	/**
	 * Get command to run with all params.
	 *
	 * @param string $base_cmd base command/script/program
	 * @param string $component_type Bacula component type (dir, sd, fd, bcons)
	 * @param string $resource_type Bacula resource type (Job, Fileset, Client ...etc.)
	 * @param string $resource_name Bacula resource name (MyJob, my_client-fd ...etc)
	 * @return string command to run
	 */
	private function getCommand(string $base_cmd, ?string $component_type, ?string $resource_type, ?string $resource_name): string
	{
		$params = [];
		if ($component_type) {
			$params[] = $component_type;
		}
		if ($resource_type) {
			$params[] = $resource_type;
		}
		if ($resource_name) {
			$params[] = $resource_name;
		}
		$cmd = $base_cmd;
		if (count($params) > 0) {
			$cmd .= ' "' . implode('" "', $params) . '"';
		}
		return $cmd;
	}

	/**
	 * Send string to command.
	 * The string is passed to the command stdin
	 *
	 * @param string $cmd command
	 * @param string $str string to send to command
	 */
	private function sendToScript(string $cmd, string $str = ''): void
	{
		$descriptorspec = [
			['pipe', 'r'], // stdin
			['pipe', 'w'], // stdout
			['pipe', 'w']  // stderr
		];

		$process = proc_open($cmd, $descriptorspec, $pipes);

		stream_set_blocking($pipes[0], false);
		stream_set_blocking($pipes[1], true);
		stream_set_blocking($pipes[2], true);

		if (is_resource($process)) {
			fwrite($pipes[0], "$str\n");
			fclose($pipes[0]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($process);
		}
	}
}
