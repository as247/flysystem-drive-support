<?php


namespace As247\Flysystem\DriveSupport\Cache;


use As247\Flysystem\DriveSupport\Contracts\Cache\PathCacheInterface;
use As247\Flysystem\DriveSupport\Support\Path;

class NullCache implements PathCacheInterface
{

	protected $root;
	public function put($key, $data, $seconds=0)
	{
		$key=Path::clean($key);
		if($key==='/'){
			$this->root=$data;
		}
	}

	public function get($key)
	{
		$key=Path::clean($key);
		if($key==='/'){
			return $this->root;
		}
		return null;
	}

	public function has($key)
	{
		$key=Path::clean($key);
		if($key==='/'){
			return true;
		}
		return false;
	}

	public function forget($key)
	{

	}

	public function forever($key, $value)
	{
		$this->put($key,$value);
	}

	public function flush()
	{

	}

	public function rename($source, $destination)
	{

	}

	public function query($path, $match = '*')
	{

	}

	public function complete($path, $isCompleted = true)
	{

	}

	public function completed($path)
	{

	}
}
