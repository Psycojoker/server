<?php

declare(strict_types=1);

namespace OCP\FilesMetadata;

use JsonSerializable;

interface IFilesMetadata extends JsonSerializable {
	/**
	 * returns the fileId linked to this metadata
	 *
	 * @return int
	 */
	public function getFileId(): int;

	/**
	 * fill the object with a json
	 *
	 * @see jsonSerialize
	 * @param string $json
	 *
	 * @return self
	 */
	public function import(string $json): self;

	/**
	 * returns true if object have been updated since last import
	 *
	 * @see import
	 * @return bool
	 */
	public function updated(): bool;

	/**
	 * return the list of indexed metadata keys
	 *
	 * @return string[]
	 */
	public function listIndexes(): array;
	public function addIndex(string $index): self;
	public function removeIndex(string $index): self;

	public function get(string $key, string $default): string;
	public function getInt(string $key, int $default): int;
	public function getFloat(string $key, float $default): float;
	public function getBool(string $key, bool $default): bool;
	public function getArray(string $key, array $default): array;
	public function set(string $key, string $value): self;
	public function setInt(string $key, int $value): self;
	public function setFloat(string $key, float $value): self;
	public function setBool(string $key, bool $value): self;
	public function setArray(string $key, array $value): self;
}
