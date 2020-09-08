<?php


namespace As247\Flysystem\DriveSupport\Contracts;

use As247\Flysystem\DriveSupport\Exception\FileNotFoundException;
use \League\Flysystem\FilesystemException;

use As247\Flysystem\DriveSupport\Exception\UnableToWriteFile;
use As247\Flysystem\DriveSupport\Exception\UnableToReadFile;
use As247\Flysystem\DriveSupport\Exception\UnableToDeleteFile;
use As247\Flysystem\DriveSupport\Exception\UnableToDeleteDirectory;
use As247\Flysystem\DriveSupport\Exception\UnableToCreateDirectory;
use As247\Flysystem\DriveSupport\Exception\InvalidVisibilityProvided;
use As247\Flysystem\DriveSupport\Exception\UnableToRetrieveMetadata;
use As247\Flysystem\DriveSupport\Exception\UnableToMoveFile;
use As247\Flysystem\DriveSupport\Exception\UnableToCopyFile;
use As247\Flysystem\DriveSupport\Support\FileAttributes;
use As247\Flysystem\DriveSupport\Support\StorageAttributes;

use League\Flysystem\Config;

interface Driver
{
	/**
	 * @throws FilesystemException
	 */
	public function fileExists(string $path): bool;

	/**
	 * @throws UnableToWriteFile
	 * @throws FilesystemException
	 */
	public function write(string $path, string $contents, Config $config): void;

	/**
	 * @param resource $contents
	 * @throws UnableToWriteFile
	 * @throws FilesystemException
	 */
	public function writeStream(string $path, $contents, Config $config): void;

	/**
	 * @throws UnableToReadFile
	 * @throws FilesystemException
	 */
	public function read(string $path): string;

	/**
	 * @return resource
	 * @throws UnableToReadFile
	 * @throws FilesystemException
	 */
	public function readStream(string $path);

	/**
	 * @throws UnableToDeleteFile
	 * @throws FilesystemException
	 * @throws FileNotFoundException
	 */
	public function delete(string $path): void;

	/**
	 * @throws UnableToDeleteDirectory
	 * @throws FilesystemException
	 * @throws FileNotFoundException
	 */
	public function deleteDirectory(string $path): void;

	/**
	 * @throws UnableToCreateDirectory
	 * @throws FilesystemException
	 */
	public function createDirectory(string $path, Config $config): void;

	/**
	 * @param mixed $visibility
	 * @throws InvalidVisibilityProvided
	 * @throws FilesystemException
	 */
	public function setVisibility(string $path, $visibility): void;

	/**
	 * @throws UnableToRetrieveMetadata
	 * @throws FilesystemException
	 */
	public function visibility(string $path): FileAttributes;

	/**
	 * @throws UnableToRetrieveMetadata
	 * @throws FilesystemException
	 */
	public function mimeType(string $path): FileAttributes;

	/**
	 * @throws UnableToRetrieveMetadata
	 * @throws FilesystemException
	 */
	public function lastModified(string $path): FileAttributes;

	/**
	 * @throws UnableToRetrieveMetadata
	 * @throws FilesystemException
	 */
	public function fileSize(string $path): FileAttributes;

	/**
	 * @param string $path
	 * @param bool $deep
	 * @return iterable<StorageAttributes>
	 * @throws FilesystemException
	 */
	public function listContents(string $path, bool $deep): iterable;

	/**
	 * @throws UnableToMoveFile
	 * @throws FilesystemException
	 */
	public function move(string $source, string $destination, Config $config): void;

	/**
	 * @throws UnableToCopyFile
	 * @throws FilesystemException
	 */
	public function copy(string $source, string $destination, Config $config): void;

	/**
	 * @param $path
	 * @return FileAttributes
	 * @throws FileNotFoundException
	 * @throws UnableToRetrieveMetadata
	 * @throws FilesystemException
	 */
	public function getMetadata($path): FileAttributes;
}
