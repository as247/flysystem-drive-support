<?php


namespace As247\Flysystem\DriveSupport\Support;


use As247\Flysystem\DriveSupport\Contracts\Driver;
use As247\Flysystem\DriveSupport\Exception\FileNotFoundException;
use As247\Flysystem\DriveSupport\Exception\InvalidStreamProvided;
use As247\Flysystem\DriveSupport\Exception\UnableToCopyFile;
use As247\Flysystem\DriveSupport\Exception\UnableToCreateDirectory;
use As247\Flysystem\DriveSupport\Exception\UnableToDeleteDirectory;
use As247\Flysystem\DriveSupport\Exception\UnableToDeleteFile;
use As247\Flysystem\DriveSupport\Exception\UnableToMoveFile;
use As247\Flysystem\DriveSupport\Exception\UnableToReadFile;
use As247\Flysystem\DriveSupport\Exception\UnableToWriteFile;
use League\Flysystem\Config;

trait DriverForAdapter
{
	/**
	 * @var Driver
	 */
	protected $driver;

	public function getDriver(){
		return $this->driver;
	}

	/**
	 * @inheritDoc
	 */
	public function write($path, $contents, Config $config=null)
	{
		try {
			$this->driver->write($this->applyPathPrefix($path), $contents, $config);
			return $this->getMetadata($path);
		}catch (UnableToWriteFile $e){
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function writeStream($path, $resource, Config $config)
	{
		try {
			$this->driver->writeStream($this->applyPathPrefix($path), $resource, $config);
			return $this->getMetadata($path);
		}catch (UnableToWriteFile $e){
			return false;
		}catch (InvalidStreamProvided $e){
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function update($path, $contents, Config $config)
	{
		return $this->write($path,$contents,$config);
	}

	/**
	 * @inheritDoc
	 */
	public function updateStream($path, $resource, Config $config)
	{
		return $this->writeStream($path,$resource,$config);
	}

	/**
	 * @inheritDoc
	 */
	public function rename($path, $newpath)
	{
		try {
			$path=$this->applyPathPrefix($path);
			$newpath=$this->applyPathPrefix($newpath);
			$this->driver->move($path, $newpath, new Config());
			return true;
		}catch (UnableToMoveFile $e){
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function copy($path, $newpath)
	{
		try {
			$path=$this->applyPathPrefix($path);
			$newpath=$this->applyPathPrefix($newpath);
			$this->driver->copy($path, $newpath, new Config());
			return true;
		}catch (UnableToCopyFile $exception){
			echo $exception->getMessage();
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function delete($path)
	{
		if($this->isRootPath($path)){
		    return false;
        }
		try {
			$this->driver->delete($this->applyPathPrefix($path));
			return true;
		}catch (UnableToDeleteFile $e){
			return false;
		}catch (FileNotFoundException $e){
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function deleteDir($dirname)
	{
        if($this->isRootPath($dirname)){
            return false;
        }
		try {
			$this->driver->deleteDirectory($this->applyPathPrefix($dirname));
			return true;
		}catch (UnableToDeleteDirectory $e){
			return false;
		}catch (FileNotFoundException $e){
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function createDir($dirname, Config $config)
	{
		try {
			$this->driver->createDirectory($this->applyPathPrefix($dirname), $config);
			return $this->getMetadata($dirname);
		}catch (UnableToCreateDirectory $e){
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setVisibility($path, $visibility)
	{
		$this->driver->setVisibility($this->applyPathPrefix($path),$visibility);
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
		return ['contents'=>$this->driver->read($this->applyPathPrefix($path))];
	}

	/**
	 * @inheritDoc
	 */
	public function readStream($path)
	{
		try {
			return ['stream'=>$this->driver->readStream($this->applyPathPrefix($path))];
		}catch (UnableToReadFile $e){
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function listContents($directory = '', $recursive = false)
	{
		$contents=array_values(iterator_to_array($this->driver->listContents($this->applyPathPrefix($directory),$recursive),false));
		$contents=array_map(function ($v){
			$v['path']=$this->removePathPrefix($v['path']);
			return $v;
		},$contents);
		return $contents;
	}

	/**
	 * @inheritDoc
	 */
	public function getMetadata($path)
	{
		try {
			$meta = $this->driver->getMetadata($this->applyPathPrefix($path));
			return $meta->toArrayV1();
		}catch (FileNotFoundException $e){
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

    public function applyPathPrefix($path)
    {
        return Path::clean(parent::applyPathPrefix($path));
    }

    protected function isRootPath($path){
        if ($this->applyPathPrefix($path) === $this->applyPathPrefix('')) {
            return true;
        }
        return false;
    }
}
