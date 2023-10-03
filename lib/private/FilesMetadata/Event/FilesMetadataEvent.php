<?php

declare(strict_types=1);

namespace OC\FilesMetadata\Event;

use OCP\EventDispatcher\Event;
use OCP\FilesMetadata\IFilesMetadata;

class FilesMetadataEvent extends Event {
	private bool $runAsBackgroundJob = false;

	public function __construct(
		private int $fileId,
		private IFilesMetadata $metadata,
	) {
		parent::__construct();
	}

	/**
	 * If an app prefer to update metadata on a background job, instead of
	 * live process, just call this method.
	 * A new event will be generated on next cron tick
	 *
	 * @return void
	 */
	public function requestBackgroundJob() {
		$this->runAsBackgroundJob = true;
	}

	/**
	 * return fileId
	 *
	 * @return int
	 */
	public function getFileId(): int {
		return $this->fileId;
	}

	/**
	 * return Metadata
	 *
	 * @return IFilesMetadata
	 */
	public function getMetadata(): IFilesMetadata {
		return $this->metadata;
	}

	/**
	 * return true if any app that catch this event requested a re-run as background job
	 *
	 * @return bool
	 */
	public function isRunAsBackgroundJobRequested(): bool {
		return $this->runAsBackgroundJob;
	}
}
