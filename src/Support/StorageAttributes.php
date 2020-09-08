<?php


namespace As247\Flysystem\DriveSupport\Support;


use ArrayAccess;
use JsonSerializable;

interface StorageAttributes extends JsonSerializable, ArrayAccess
{
	public const TYPE_FILE = 'file';
	public const TYPE_DIRECTORY = 'dir';
	public const ATTRIBUTE_PATH = 'path';
	public const ATTRIBUTE_TYPE = 'type';
	public const ATTRIBUTE_VISIBILITY = 'visibility';
	public const ATTRIBUTE_LAST_MODIFIED = 'last_modified';
	public const ATTRIBUTE_FILE_SIZE = 'file_size';
	public const ATTRIBUTE_MIME_TYPE = 'mime_type';

	public function path(): string;

	public function type(): string;

	public function visibility(): ?string;

	public static function fromArray(array $attributes);

	public function isFile(): bool;

	public function isDir(): bool;
}
