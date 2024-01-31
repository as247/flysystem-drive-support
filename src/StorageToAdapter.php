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
use As247\CloudStorages\Support\StorageAttributes;
use GuzzleHttp\Psr7\Utils;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;


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

    public function fileExists(string $path): bool
    {
        return (bool)$this->getMetadata($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->writeStream($path, Utils::streamFor($contents), $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $config = $this->convertConfig($config);
            $this->storage->writeStream($this->prefixer->prefixPath($path), $contents, $config);
        } catch (UnableToWriteFile $e) {
            if ($this->shouldThrowException($e)) {
                throw $e;
            }
        } catch (InvalidStreamProvided $e) {
            if ($this->shouldThrowException($e)) {
                throw $e;
            }
        }
    }

    public function read(string $path): string
    {
        return stream_get_contents($this->readStream($path));
    }

    public function readStream(string $path)
    {
        try {
            return $this->storage->readStream($this->prefixer->prefixPath($path));
        } catch (UnableToReadFile $e) {
            if ($this->shouldThrowException($e)) {
                throw $e;
            }
        }
    }

    public function delete(string $path): void
    {
        if ($this->isRootPath($path)) {
            return ;
        }
        try {
            $this->storage->delete($this->prefixer->prefixPath($path));

        } catch (UnableToDeleteFile $e) {
            if ($this->shouldThrowException($e)) {
                throw $e;
            }
        } catch (FileNotFoundException $e) {
            if ($this->shouldThrowException($e)) {
                throw $e;
            }
        }
    }

    public function deleteDirectory(string $path): void
    {
        if ($this->isRootPath($path)) {
            return ;
        }
        try {
            $this->storage->deleteDirectory($this->prefixer->prefixPath($path));
        } catch (UnableToDeleteDirectory $e) {
            if ($this->shouldThrowException($e)) {
                throw $e;
            }
        } catch (FileNotFoundException $e) {
            if ($this->shouldThrowException($e)) {
                throw $e;
            }
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $config = $this->convertConfig($config);
            $this->storage->createDirectory($this->prefixer->prefixPath($path), $config);

        } catch (UnableToCreateDirectory $e) {
            if ($this->shouldThrowException($e)) {
                throw $e;
            }
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->storage->setVisibility($this->prefixer->prefixPath($path), $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->getMetadata($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->getMetadata($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->getMetadata($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->getMetadata($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $contents=$this->storage->listContents($this->prefixer->prefixPath($path), $deep);
        foreach ($contents as $key=>$content){
            $path=$this->prefixer->stripPrefix($content['path']);
            $visibility=$content[StorageAttributes::ATTRIBUTE_VISIBILITY];
            $lastModified=$content[StorageAttributes::ATTRIBUTE_LAST_MODIFIED];
            $fileSize=$content[StorageAttributes::ATTRIBUTE_FILE_SIZE];
            $isDirectory=$content[StorageAttributes::ATTRIBUTE_TYPE]===StorageAttributes::TYPE_DIRECTORY;
            yield $isDirectory ?
                new DirectoryAttributes($path, $visibility, $lastModified) :
                new FileAttributes(
                str_replace('\\', '/', $path),
                    $fileSize,
                $visibility,
                $lastModified
            );
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $source = $this->prefixer->prefixPath($source);
            $destination = $this->prefixer->prefixPath($destination);
            $this->storage->move($source, $destination, $this->convertConfig(new Config()));
        } catch (UnableToMoveFile $e) {
            if ($this->shouldThrowException($e)) {
                throw $e;
            }
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $config = $this->convertConfig(new Config());
            $source = $this->prefixer->prefixPath($source);
            $destination = $this->prefixer->prefixPath($destination);
            $this->storage->copy($source, $destination, $config);
        } catch (UnableToCopyFile $e) {
            if ($this->shouldThrowException($e)) {
                throw $e;
            }
        }
    }

	/**
	 * @inheritDoc
	 */
	public function getMetadata($path)
	{
		try {
			$meta = $this->storage->getMetadata($this->prefixer->prefixPath($path));
            return $meta->type()===StorageAttributes::TYPE_DIRECTORY ?
                new DirectoryAttributes($path, $meta->visibility(), $meta->lastModified()) :
                new FileAttributes(
                str_replace('\\', '/', $path),
                $meta->fileSize(),
                $meta->visibility(),
                $meta->lastModified()
            );
		} catch (FileNotFoundException $e) {
			if ($this->shouldThrowException($e)) {
				throw $e;
			}
			return false;
		}
	}
	protected function isRootPath($path)
	{
		if ($this->prefixer->prefixPath($path) === $this->prefixer->prefixPath('')) {
			return true;
		}
		return false;
	}

	protected function convertConfig(Config $config=null)
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
