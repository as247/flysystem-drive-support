<?php


namespace As247\Flysystem\DriveSupport\Contracts\Cache;


interface CacheInterface
{
	public function put($key, $data, $seconds);

	public function get($key);

	public function has($key);

	public function forget($key);

	public function forever($key, $value);

	public function flush();
}
