<?php


namespace As247\Flysystem\DriveSupport;


use As247\CloudStorages\Contracts\Storage\StorageContract;
use As247\CloudStorages\Storage\AList;
use As247\CloudStorages\Storage\GoogleDrive;
use As247\CloudStorages\Storage\OneDrive;
use As247\CloudStorages\Support\StorageAttributes;
use GuzzleHttp\Psr7\Utils;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;


trait StorageToAdapter
{
	/**
	 * @var StorageContract
	 */
	protected $storage;

	/**
	 * @return StorageContract|OneDrive|GoogleDrive|AList
	 */
	public function getStorage()
	{
		return $this->storage;
	}

    public function fileExists(string $path): bool
    {
        try {
            return $this->getMetadata($path) instanceof FileAttributes;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            return $this->getMetadata($path) instanceof DirectoryAttributes;
        } catch (\Exception $e) {
            return false;
        }
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
        }catch (\Exception $e){
            throw UnableToWriteFile::atLocation($path, $e->getMessage());
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
        } catch (\Exception $e) {
            throw \League\Flysystem\UnableToReadFile::fromLocation($path, $e);
        }
    }

    public function delete(string $path): void
    {
        if ($this->isRootPath($path)) {
            return ;
        }
        try {
            $this->storage->delete($this->prefixer->prefixPath($path));

        } catch (\Exception $e) {
            throw \League\Flysystem\UnableToDeleteFile::atLocation($path, $e->getMessage());
        }
    }

    public function deleteDirectory(string $path): void
    {
        if ($this->isRootPath($path)) {
            return ;
        }
        try {
            $this->storage->deleteDirectory($this->prefixer->prefixPath($path));
        } catch(\Exception $e){
            throw \League\Flysystem\UnableToDeleteDirectory::atLocation($path, $e->getMessage());
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $config = $this->convertConfig($config);
            $this->storage->createDirectory($this->prefixer->prefixPath($path), $config);
        } catch (\Exception $e) {
            throw \League\Flysystem\UnableToCreateDirectory::atLocation($path, $e->getMessage());
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        try {
            $this->storage->setVisibility($this->prefixer->prefixPath($path), $visibility);
        }catch (\Exception $e){
            throw new \League\Flysystem\UnableToSetVisibility($path, 0, $e);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->getMetadata($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        $meta=$this->getMetadata($path);
        if($meta instanceof FileAttributes){
            if($meta->mimeType()) {
                //print_r($meta);
                return $meta;
            }
        }
        throw UnableToRetrieveMetadata::create($path, 'mimeType');
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->getMetadata($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        $meta= $this->getMetadata($path);
        if($meta instanceof FileAttributes){
            return $meta;
        }
        throw UnableToRetrieveMetadata::create($path, 'fileSize');
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
        } catch (\Exception $e) {
            throw \League\Flysystem\UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $config = $this->convertConfig(new Config());
            $source = $this->prefixer->prefixPath($source);
            $destination = $this->prefixer->prefixPath($destination);
            $this->storage->copy($source, $destination, $config);
        } catch (\Exception $e) {
            throw \League\Flysystem\UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

	/**
	 * @param $path
     * @return FileAttributes|DirectoryAttributes|bool
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
                $meta->lastModified(),
                $meta->mimeType()
            );
		}  catch (\Exception $e) {
            throw new UnableToRetrieveMetadata("Unable to retrieve metadata for file at path: {$path}", 0, $e);
        }
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, Config $config): string
    {
        try {
            return $this->storage->temporaryUrl($this->prefixer->prefixPath($path), $expiresAt, $this->convertConfig($config));
        } catch (\Exception $e) {
            throw new \League\Flysystem\UnableToRetrieveMetadata("Unable to retrieve metadata for file at path: {$path}", 0, $e);
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
