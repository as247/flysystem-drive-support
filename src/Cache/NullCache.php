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
	}

	public function has($key)
	{
		$key=Path::clean($key);
		if($key==='/'){
			return true;
		}
	}

	public function forget($key)
	{
		// TODO: Implement forget() method.
	}

	public function forever($key, $value)
	{
		$this->put($key,$value);
	}

	public function flush()
	{
		// TODO: Implement flush() method.
	}

	public function rename($source, $destination)
	{
		// TODO: Implement rename() method.
	}

	public function query($path, $match = '*')
	{
		// TODO: Implement query() method.
	}

	public function complete($path, $isCompleted = true)
	{
		// TODO: Implement complete() method.
	}

	public function completed($path)
	{
		// TODO: Implement completed() method.
	}
}
