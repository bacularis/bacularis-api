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

use Bacularis\Common\Modules\Errors\DatabaseError;
use Bacularis\Common\Modules\Logging;
use PDO;
use PDOException;
use Prado\Exceptions\TDbException;

/**
 * Shared Catalog query and result handling for native tag assignments.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
abstract class TagAssignmentManager extends APIModule
{
	/**
	 * Active record class name for given tag type.
	 * For example: TagMedia, TagClient...
	 */
	protected const RECORD = '';

	/**
	 * Tag SQL table name.
	 * For example: Client, Job...
	 */
	protected const RESOURCE_TABLE = '';

	/**
	 * Main resource identifier column name.
	 * For example: ClientID, JobId...
	 */
	protected const RESOURCE_ID = '';

	/**
	 * Define if use restriction bases on provided allowed resources.
	 */
	protected const USE_RESOURCE_ACL = false;

	/**
	 * Get matching tag assignments for all visible resources or one resource.
	 *
	 * @param int|null $resource_id catalog resource ID
	 * @param string|null $tag exact tag value to find
	 * @param array $allowed_resources resource names as lookup keys for Job or Client ACLs
	 * @return array|null assignments, or null if the requested resource is missing or inaccessible
	 * @throws BCatalogException if the tag query fails or the required Catalog tables are unavailable
	 */
	public function getTags(?int $resource_id = null, ?string $tag = null, array $allowed_resources = []): ?array
	{
		$record_class = static::RECORD;
		$table = $record_class::TABLE;
		$resource = static::RESOURCE_TABLE;
		$id = static::RESOURCE_ID;
		$id_key = strtolower($id);
		$no_allowed_resources = static::USE_RESOURCE_ACL && $allowed_resources === [];

		// Set columns to return in query
		$columns = "r.$id AS resourceid, t.Tag AS tag";
		if (static::USE_RESOURCE_ACL) {
			$columns .= ', r.Name AS resourcename';
		}

		$join = null;
		if ($resource_id === null) {
			/**
			 * This is case when resource ID is not available.
			 * For example: /tags/jobs or /tags/clients
			 * In this case records without tags are not listed.
			 */
			$join = 'INNER JOIN';
		} else {
			/**
			 * This is case when resource ID is provided.
			 * For example: /tags/jobs/123 or /tags/clients/456
			 * If resource exists but it has no tags assigned, then tag is NULL.
			 * In final API result we have {output: [], error: 0}
			 * instead of TagError::ERROR_RESOURCE_DOES_NOT_EXIST.
			 */
			$join  = 'LEFT JOIN';
		}
		$sql = "SELECT $columns FROM $resource r $join $table t ON t.$id = r.$id";
		if ($tag !== null) {
			// Use filtering by tag name
			$sql .= ' AND t.Tag = :tag';
		}
		if ($no_allowed_resources) {
			/**
			 * If Bconsole returns no allowed resources for user, we do not return
			 * but we execute the query anyway. This is to not 'hide' error if table
			 * does not exist.
			 */
			$sql .= ' WHERE 1 = 0';
		} elseif ($resource_id !== null) {
			// Resource ID is provided, so filter results to one record.
			$sql .= " WHERE r.$id = :resource_id";
		}
		$sql .= " ORDER BY r.$id, t.Tag";

		// Get database connection.
		$record = $record_class::finder();
		try {
			// Get SQL PDO connection.
			$connection = $record->getDbConnection();
			$pdo = $connection->getPdoInstance();
		} catch (PDOException | TDbException $e) {
			// Error. Something went wrong.
			$message = $e->getMessage();
			Logging::log(Logging::CATEGORY_APPLICATION, $message);
			throw new BCatalogException(
				DatabaseError::MSG_ERROR_DB_CONNECTION_PROBLEM,
				DatabaseError::ERROR_DB_CONNECTION_PROBLEM
			);
		}

		try {
			// Prepare SQL query and execute it.
			$statement = $pdo->prepare($sql);
			if ($tag !== null) {
				$statement->bindValue(':tag', $tag, PDO::PARAM_STR);
			}
			if ($resource_id !== null && !$no_allowed_resources) {
				$statement->bindValue(':resource_id', $resource_id, PDO::PARAM_INT);
			}
			$statement->execute();

			// Check and prepare results
			$output = $resource_id === null ? [] : null;
			while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
				if (static::USE_RESOURCE_ACL && !isset($allowed_resources[$row['resourcename']])) {
					continue;
				}
				$output ??= [];
				if ($row['tag'] !== null) {
					$output[] = [$id_key => (int) $row['resourceid'], 'tag' => $row['tag']];
				}
			}
			return $output;
		} catch (PDOException $e) {
			// Error. Something went wrong.
			$message = $e->getMessage();
			Logging::log(Logging::CATEGORY_APPLICATION, $message);
			throw APIDbModule::getCatalogQueryException($e);
		}
	}
}
