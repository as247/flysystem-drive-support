<?php


namespace As247\Flysystem\DriveSupport\Support;

/**
 * Class FileAttributes
 * @package As247\Flysystem\DriveSupport\Support
 * @property $path
 * @property $type
 * @property $file_size
 * @property $visibility
 * @property $last_modified
 * @property $mime_type
 */
class FileAttributes implements StorageAttributes
{

	use AttributesAccess;

	public function __construct(array $attributes) {
		$this->attributes=$attributes;
		if(!isset($this->attributes[static::ATTRIBUTE_TYPE])) {
			$this->attributes[static::ATTRIBUTE_TYPE] = StorageAttributes::TYPE_FILE;
		}
	}

	public function type(): string
	{
		return $this->type;
	}

	public function path(): string
	{
		return $this->path;
	}

	public function fileSize(): ?int
	{
		return $this->file_size;
	}

	public function visibility(): ?string
	{
		return $this->visibility;
	}

	public function lastModified(): ?int
	{
		return $this->last_modified;
	}

	public function mimeType(): ?string
	{
		return $this->mime_type;
	}


	public function isFile(): bool
	{
		return $this->type===StorageAttributes::TYPE_FILE;
	}

	public function isDir(): bool
	{
		return $this->type===StorageAttributes::TYPE_DIRECTORY;
	}

	public static function fromArray(array $attributes): FileAttributes
	{
		return new FileAttributes($attributes);
	}
	public function toArray(){
		return $this->attributes;
	}
	public function toArrayV1(){
		return array_merge($this->attributes,
			[
				'size'=>$this->file_size??0,
				'mimetype'=>$this->mime_type??0,
				'timestamp'=>$this->last_modified??null,
			]
		);
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
