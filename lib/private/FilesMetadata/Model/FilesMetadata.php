<?php

declare(strict_types=1);

namespace OC\FilesMetadata\Model;

use OCP\FilesMetadata\IFilesMetadata;

class FilesMetadata implements IFilesMetadata {

	private const INDEXES_KEY = '_indexes';

	private int $fileId;
	private array $metadata;
	private bool $updated = false;

	public function __construct(int $fileId = 0, array $metadata = []) {
		$this->fileId = $fileId;
		$this->metadata = $metadata;
	}

	public function getFileId(): int {
		return $this->fileId;
	}

	public function import(string $json): IFilesMetadata {
		$this->metadata = json_decode($json, true, JSON_THROW_ON_ERROR);
		$this->updated = false;
		return $this;
	}

	public function updated(): bool {
		return $this->updated;
	}

	public function listIndexes(): array {
		return $this->getArray(self::INDEXES_KEY, []);
	}

	public function addIndex(string $index): IFilesMetadata {
		if (!array_key_exists('_indexes', $this->metadata)) {
			$this->metadata[self::INDEXES_KEY] = [];
		}

		$this->metadata[self::INDEXES_KEY][] = $index;
		return $this;
	}

	public function removeIndex(string $index): IFilesMetadata {
		if (!array_key_exists(self::INDEXES_KEY, $this->metadata)) {
			return $this;
		}

		$this->metadata[self::INDEXES_KEY] = array_diff($this->metadata[self::INDEXES_KEY], [$index]);
		return $this;
	}

	public function get(string $key, string $default): string {
		return $this->metadata[$key] ?? $default;
	}

	public function getInt(string $key, int $default): int {
		return $this->metadata[$key] ?? $default;
	}

	public function getFloat(string $key, float $default): float {
		return $this->metadata[$key] ?? $default;
	}

	public function getBool(string $key, bool $default): bool {
		return $this->metadata[$key] ?? $default;
	}

	public function getArray(string $key, array $default): array {
		return $this->metadata[$key] ?? $default;
	}

	public function set(string $key, string $value): IFilesMetadata {
		$this->metadata[$key] = $value;
		return $this;
	}

	public function setInt(string $key, int $value): IFilesMetadata {
		$this->metadata[$key] = $value;
		return $this;
	}

	public function setFloat(string $key, float $value): IFilesMetadata {
		$this->metadata[$key] = $value;
		return $this;
	}

	public function setBool(string $key, bool $value): IFilesMetadata {
		$this->metadata[$key] = $value;
		return $this;
	}

	public function setArray(string $key, array $value): IFilesMetadata {
		$this->metadata[$key] = $value;
		return $this;
	}

	public function jsonSerialize() {
		return $this->metadata;
	}
}
