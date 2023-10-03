<?php

declare(strict_types=1);

namespace OCP\FilesMetadata;

interface IFilesMetadataManager {
	public function refreshMetadata(
		int $fileId,
		bool $asBackgroundJob = false,
		bool $fromScratch = false
	): IFilesMetadata;

	public function getMetadata(int $fileId, bool $createIfNeeded = false): IFilesMetadata;

	public function saveMetadata(IFilesMetadata $filesMetadata): void;

	public function getQueryHelper(): IFilesMetadataQueryHelper;
}
