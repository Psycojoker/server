<?php

declare(strict_types=1);

namespace OC\FilesMetadata;

use OC\FilesMetadata\Event\FilesMetadataEvent;
use OC\FilesMetadata\Model\FilesMetadata;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\IFilesMetadata;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\IFilesMetadataQueryHelper;

class FilesMetadataManager implements IFilesMetadataManager {

	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private FilesMetadataQueryHelper $filesMetadataQueryHelper,
	) {
	}

	public function refreshMetadata(
		int $fileId,
		bool $asBackgroundJob = false,
		bool $fromScratch = false
	): IFilesMetadata {
		$metadata = null;
		if (!$fromScratch) {
			try {
				$metadata = $this->getMetadataFromDatabase($fileId);
			} catch (FilesMetadataNotFoundException $e) {
			}
		}

		if (is_null($metadata)) {
			$metadata = new FilesMetadata($fileId);
		}

		$event = new FilesMetadataEvent($fileId, $metadata);
		$this->eventDispatcher->dispatchTyped($event);
		$this->saveMetadata($event->getMetadata());

		return $metadata;
	}

	/**
	 * @param int $fileId
	 * @param bool $createIfNeeded
	 *
	 * @return IFilesMetadata
	 * @throws FilesMetadataNotFoundException
	 */
	public function getMetadata(int $fileId, bool $createIfNeeded = false): IFilesMetadata {
		try {
			return $this->getMetadataFromDatabase($fileId);
		} catch (FilesMetadataNotFoundException $e) {
			if ($createIfNeeded) {
				return $this->refreshMetadata($fileId, true);
			}

			throw $e;
		}
	}

	/**
	 * @param int $fileId
	 *
	 * @return IFilesMetadata
	 * @throws FilesMetadataNotFoundException
	 */
	public function getMetadataFromDatabase(int $fileId): IFilesMetadata {
		$metadata = new FilesMetadata($fileId);
		throw new FilesMetadataNotFoundException();

		$json = '[]'; // get json from database;
		// if entry exist in database.
		$metadata->import($json);

		return $metadata;
	}


	public function saveMetadata(IFilesMetadata $filesMetadata): void {
		if ($filesMetadata->getFileId() === 0) {
			return;
		}

		// database thing ...
		// try to update first, if no update, insert as new

		// remove indexes from metadata_index that are not in the list of indexes anymore.
		foreach ($filesMetadata->listIndexes() as $index) {
			// foreach index, update entry in table metadata_index
			// if no update, insert as new row
			// !! we might want to get the type of the value to be indexed at one point !!
		}
	}

	public function getQueryHelper(): IFilesMetadataQueryHelper {
		return $this->filesMetadataQueryHelper;
	}
}
