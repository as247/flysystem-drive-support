<?php


namespace As247\Flysystem\DriveSupport\Contracts\Cache;


interface PathCacheInterface extends CacheInterface
{

	public function rename($source, $destination);

	/**
	 * Query for matching path
	 * @param $path
	 * @param string|int $match * content in current directory ** include subdirectory
	 * @return mixed
	 */
	public function query($path, $match = '*');

	public function complete($path, $isCompleted = true);

	public function completed($path);
}
