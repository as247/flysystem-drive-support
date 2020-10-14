<?php


namespace As247\Flysystem\DriveSupport;


use As247\CloudStorages\Contracts\Storage\StorageContract;
use As247\CloudStorages\Exception\FileNotFoundException;
use As247\CloudStorages\Exception\InvalidStreamProvided;
use As247\CloudStorages\Exception\UnableToCopyFile;
use As247\CloudStorages\Exception\UnableToCreateDirectory;
use As247\CloudStorages\Exception\UnableToDeleteDirectory;
use As247\CloudStorages\Exception\UnableToDeleteFile;
use As247\CloudStorages\Exception\UnableToMoveFile;
use As247\CloudStorages\Exception\UnableToReadFile;
use As247\CloudStorages\Exception\UnableToWriteFile;
use As247\CloudStorages\Storage\GoogleDrive;
use As247\CloudStorages\Storage\OneDrive;
use League\Flysystem\Util;
use function GuzzleHttp\Psr7\stream_for;
use League\Flysystem\Config;


trait StorageToAdapter
{
	/**
	 * @var StorageContract
	 */
	protected $storage;
	protected $throwException = false;
	protected $exceptExceptions = [
		FileNotFoundException::class,
	];

	/**
	 * @return StorageContract|OneDrive|GoogleDrive
	 */
	public function getStorage()
	{
		return $this->storage;
	}

	/**
	 * @inheritDoc
	 */
	public function write($path, $contents, Config $config = null)
	{
		return $this->writeStream($path, stream_for($contents), $config);
	}

	/**
	 * @inheritDoc
	 */
	public function writeStream($path, $resource, Config $config)
	{
		try {
			$config = $this->convertConfig($config);
			$this->storage->writeStream($this->applyPathPrefix($path), $resource, $config);
			return $this->getMetadata($path);
		} catch (UnableToWriteFile $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		} catch (InvalidStreamProvided $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function update($path, $contents, Config $config)
	{
		return $this->write($path, $contents, $config);
	}

	/**
	 * @inheritDoc
	 */
	public function updateStream($path, $resource, Config $config)
	{
		return $this->writeStream($path, $resource, $config);
	}

	/**
	 * @inheritDoc
	 */
	public function rename($path, $newpath)
	{
		try {
			$path = $this->applyPathPrefix($path);
			$newpath = $this->applyPathPrefix($newpath);
			$this->storage->move($path, $newpath, $this->convertConfig(new Config()));
			return true;
		} catch (UnableToMoveFile $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function copy($path, $newpath)
	{
		try {
			$config = $this->convertConfig(new Config());
			$path = $this->applyPathPrefix($path);
			$newpath = $this->applyPathPrefix($newpath);
			$this->storage->copy($path, $newpath, $config);
			return true;
		} catch (UnableToCopyFile $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function delete($path)
	{
		if ($this->isRootPath($path)) {
			return false;
		}
		try {
			$this->storage->delete($this->applyPathPrefix($path));
			return true;
		} catch (UnableToDeleteFile $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		} catch (FileNotFoundException $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function deleteDir($dirname)
	{
		if ($this->isRootPath($dirname)) {
			return false;
		}
		try {
			$this->storage->deleteDirectory($this->applyPathPrefix($dirname));
			return true;
		} catch (UnableToDeleteDirectory $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		} catch (FileNotFoundException $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function createDir($dirname, Config $config)
	{
		try {
			$config = $this->convertConfig($config);
			$this->storage->createDirectory($this->applyPathPrefix($dirname), $config);
			return $this->getMetadata($dirname);
		} catch (UnableToCreateDirectory $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setVisibility($path, $visibility)
	{
		$this->storage->setVisibility($this->applyPathPrefix($path), $visibility);
		return $this->getMetadata($path);
	}

	/**
	 * @inheritDoc
	 */
	public function has($path)
	{
		return (bool)$this->getMetadata($path);
	}

	/**
	 * @inheritDoc
	 */
	public function read($path)
	{
		$stream = $this->readStream($path);
		return ['contents' => stream_get_contents($stream['stream'])];
	}

	/**
	 * @inheritDoc
	 */
	public function readStream($path)
	{
		try {
			return ['stream' => $this->storage->readStream($this->applyPathPrefix($path))];
		} catch (UnableToReadFile $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function listContents($directory = '', $recursive = false)
	{

		$contents = array_values(iterator_to_array($this->storage->listContents($this->applyPathPrefix($directory), $recursive), false));

		$contents = array_map(function ($v) {
			$v['path'] = $this->removePathPrefix($v['path']);
			return $v;
		}, $contents);
		return $contents;
	}

	/**
	 * @inheritDoc
	 */
	public function getMetadata($path)
	{
		try {
			$meta = $this->storage->getMetadata($this->applyPathPrefix($path));
			return $meta->toArrayV1();
		} catch (FileNotFoundException $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getSize($path)
	{
		return $this->getMetadata($path);
	}

	/**
	 * @inheritDoc
	 */
	public function getMimetype($path)
	{
		return $this->getMetadata($path);
	}

	/**
	 * @inheritDoc
	 */
	public function getTimestamp($path)
	{
		return $this->getMetadata($path);
	}

	/**
	 * @inheritDoc
	 */
	public function getVisibility($path)
	{
		return $this->getMetadata($path);
	}

	public function setPathPrefix($path)
	{
		parent::setPathPrefix(Util::normalizePath($path));
	}

	public function applyPathPrefix($path)
	{
		return Util::normalizePath(parent::applyPathPrefix($path));
	}

	protected function isRootPath($path)
	{
		if ($this->applyPathPrefix($path) === $this->applyPathPrefix('')) {
			return true;
		}
		return false;
	}

	protected function convertConfig(Config $config)
	{
		return new \As247\CloudStorages\Support\Config();
	}

	protected function shouldThrowException($e)
	{
		if(!$this->throwException){
			return false;
		}
		if (empty($this->exceptExceptions)) {
			return $this->throwException;
		}
		return !in_array(get_class($e), $this->exceptExceptions);
	}

	public function setExcerptExceptions($exceptions)
	{
		$this->exceptExceptions = $exceptions;
		return $this;
	}

	public function getExcerptExceptions()
	{
		return $this->exceptExceptions;
	}
}
