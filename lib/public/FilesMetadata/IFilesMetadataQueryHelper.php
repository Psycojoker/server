<?php

declare(strict_types=1);

namespace OCP\FilesMetadata;

use OCP\DB\QueryBuilder\IQueryBuilder;

interface IFilesMetadataQueryHelper {
	public function linkedToFileId(
		IQueryBuilder $queryBuilder,
		string $fileTableAlias,
		string $fileIdField
	): void;

	public function limitToSingleMetadata(
		IQueryBuilder $queryBuilder,
		string $fileTableAlias,
		string $fileIdField,
		string $metadataKey,
		string $metadataValue,
		bool $selectMetadata = false
	): void;

	/**
	 * @param array $data
	 *
	 * @return IFilesMetadata
	 */
	public function extractMetadata(array $data): IFilesMetadata;
}
