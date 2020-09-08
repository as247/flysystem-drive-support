<?php


namespace As247\Flysystem\DriveSupport\Cache;


use As247\Flysystem\DriveSupport\Contracts\Cache\CacheInterface;

class TempCache implements CacheInterface
{
	protected $cacheDir;
	function __construct($key)
	{
		$name=md5(serialize(func_get_args()));
		$this->cacheDir=sys_get_temp_dir().'/'.$name;

	}
	function get($key){
		return $this->getPayload($key)['data'] ?? null;
	}
	function put($key,$value,$expires=3600){
		$path=$this->path($key);
		if($this->ensureCacheDir()) {
			file_put_contents($path,
				serialize([
					'data' => $value,
					'expires' => $expires,
					'created'=>time(),
				]));
		}
	}
	protected function ensureCacheDir(){
		if(is_dir($this->cacheDir)){
			return true;
		}
		@$created=mkdir($this->cacheDir, 0777, true);
		if(!$created){
			@unlink($this->cacheDir);//It may be a file
			@$created=mkdir($this->cacheDir, 0777, true);
		}
		if(!$created){
			throw new \RuntimeException('Could not create directory '.$this->cacheDir);
		}
		return $created;
	}
	/**
	 * Retrieve an item and expiry time from the cache by key.
	 *
	 * @param  string  $key
	 * @return array
	 */
	protected function getPayload($key)
	{
		$path = $this->path($key);
		$payload=[];
		if(file_exists($path) && is_file($path)){
			$content=file_get_contents($path);
			if($content) {
				$payload = unserialize($content);
				if(!isset($payload['data']) || !isset($payload['expires']) || !isset($payload['created'])){
					return [];
				}
				if($payload['expires']> 0 && $payload['created']+$payload['expires'] < time()){
					return [];
				}
			}
		}
		return $payload;

	}
	protected function path($key){
		$key=md5($key);
		return $this->cacheDir.'/'.$key;
	}

	public function has($key)
	{
		return !empty($this->getPayload($key));
	}

	public function forget($key)
	{
		$path=$this->path($key);
		@unlink($path);
	}

	public function forever($key, $value)
	{
		$this->put($key,$value,0);
	}

	public function flush()
	{
		if(!is_dir($this->cacheDir)){
			return ;
		}
		if ($dh = opendir($this->cacheDir)) {
			while (($file = readdir($dh)) !== false) {
				if($file!=='.' && $file !=='..'){
					unlink($this->cacheDir.'/'.$file);
				}
			}
			closedir($dh);
		}
	}
}
