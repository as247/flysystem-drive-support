<?php


namespace As247\Flysystem\DriveSupport\Service;


use As247\Flysystem\DriveSupport\Exception\InvalidStreamProvided;
use As247\Flysystem\DriveSupport\Support\Path;
use As247\Flysystem\DriveSupport\Support\StorageAttributes;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use Microsoft\Graph\Graph;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;

class OneDrive
{
	protected $graph;
	const ROOT = '/me/drive/root';
	protected $publishPermission = [
		'role' => 'read',
		'scope' => 'anonymous',
		'withLink' => true
	];
	public function __construct(Graph $graph)
	{
		$this->graph=$graph;
	}




	function normalizeMetadata(array $response, string $path): array
	{
		$permissions=$response['permissions']??[];
		$visibility = AdapterInterface::VISIBILITY_PRIVATE;
		foreach ($permissions as $permission) {
			if(!isset($permission['link']['scope']) || !isset($permission['roles'])){
				continue;
			}
			if(in_array($this->publishPermission['role'],$permission['roles'])
				&& $permission['link']['scope']==$this->publishPermission['scope']){
				$visibility = AdapterInterface::VISIBILITY_PUBLIC;
				break;
			}
		}

		return [
			'id'=>$response['id']??null,
			'name' => $response['name']??null,
			StorageAttributes::ATTRIBUTE_PATH => Path::clean($path),
			StorageAttributes::ATTRIBUTE_LAST_MODIFIED => strtotime($response['lastModifiedDateTime']),
			StorageAttributes::ATTRIBUTE_FILE_SIZE => $response['size'],
			StorageAttributes::ATTRIBUTE_TYPE => isset($response['file']) ? 'file' : 'dir',
			StorageAttributes::ATTRIBUTE_MIME_TYPE => $response['file']['mimeType'] ?? null,
			'link' => isset($response['webUrl']) ? $response['webUrl'] : null,
			StorageAttributes::ATTRIBUTE_VISIBILITY=>$visibility,
			'downloadUrl' => isset($response['@microsoft.graph.downloadUrl']) ? $response['@microsoft.graph.downloadUrl'] : null,
		];
	}
	function getEndpoint($path='',$action='',$params=[]){
		$path=Path::clean($path);
		$path=trim($path,'\\/');
		$path=static::ROOT.':/'.$path;
		/**
		 * Path should not end with /
		 * /me/drive/root:/path/to/file
		 * /me/drive/root
		 */
		$path=rtrim($path,':/');
		if($action===true){//path reference
			if(strpos($path,':')===false) {
				$path .= ':';//root path should end with :
			}
		}
		if ($action && is_string($action)) {
			/**
			 * Append action to path
			 * /me/drive/root:/path:/action
			 * trim : for root
			 * /me/drive/root/action
			 */
			$path= rtrim($path,':');
			if(strpos($path,':')!==false) {
				$path .=':/' . $action;//root:/path:/action
			}else{
				$path .= '/' . $action;//root/action
			}
		}
		if($params){
			$path.='?'.http_build_query($params);
		}
		return $path;
	}

	/**
	 * @param $path
	 * @param $newPath
	 * @return array|null
	 * @throws \Microsoft\Graph\Exception\GraphException
	 */
	public function copy($path,$newPath){
		$endpoint = $this->getEndpoint($path,'copy');
		$name=basename($newPath);
		$this->createDirectory(dirname($newPath));
		$newPathParent=$this->getEndpoint(dirname($newPath),true);
		$body=[
			'name' => $name,
			'parentReference' => [
				'path' => $newPathParent,
			],
		];
		return $this->graph->createRequest('POST', $endpoint)
			->attachBody($body)
			->execute()->getBody();
	}

	/**
	 * @param $path
	 * @return array|null
	 * @throws \Microsoft\Graph\Exception\GraphException
	 */
	public function createDirectory($path){
		$path=Path::clean($path);
		if($path==='/'){
			return $this->getItem('/');
		}
		$endpoint=$this->getEndpoint($path);
		return $this->graph->createRequest('PATCH', $endpoint)
			->attachBody([
				'folder' => new \ArrayObject(),
			])->execute()->getBody();
	}

	/**
	 * @param $path
	 * @return array|null
	 * @throws \Microsoft\Graph\Exception\GraphException
	 */
	public function delete($path){
		$endpoint=$this->getEndpoint($path);
		return $this->graph->createRequest('DELETE', $endpoint)->execute()->getBody();
	}

	/**
	 * @param $path
	 * @param null $format
	 * @return resource|null
	 * @throws \Microsoft\Graph\Exception\GraphException
	 */
	public function download($path,$format=null){
		$args=[];
		if($format){
			if(is_string($format)){
				$args=['format'=>$format];
			}elseif(is_array($format)){
				$args=$format;
			}
		}
		$endpoint=$this->getEndpoint($path,'content',$args);
		$response=$this->graph->createRequest('GET',$endpoint)->setReturnType('GuzzleHttp\Psr7\Stream')->execute();
		/**
		 * @var \GuzzleHttp\Psr7\Stream $response
		 */
		return $response->detach();

	}

	/**
	 * @param $path
	 * @param array $args
	 * @return array|null
	 * @throws \Microsoft\Graph\Exception\GraphException
	 */
	public function getItem($path,$args=[]){
		$endpoint=$this->getEndpoint($path,'',$args);
		$response = $this->graph->createRequest('GET', $endpoint)->execute();
		return $response->getBody();
	}

	/**
	 * @param $path
	 * @return \Generator
	 * @throws \Microsoft\Graph\Exception\GraphException
	 */
	public function listChildren($path){
		$endpoint = $this->getEndpoint($path,'children');
		$nextPage=null;

		do {
			if ($nextPage) {
				$endpoint = $nextPage;
			}
			$response = $this->graph->createRequest('GET', $endpoint)
				->execute();
			$nextPage = $response->getNextLink();
			$items = $response->getBody()['value']??[];
			if(!is_array($items)){
				$items=[];
			}
			yield from $items;
		}while($nextPage);
	}

	/**
	 * @param $path
	 * @param $newPath
	 * @return array|null
	 * @throws \Microsoft\Graph\Exception\GraphException
	 */
	public function move($path,$newPath){
		$endpoint = $this->getEndpoint($path);
		$name=basename($newPath);
		$this->createDirectory(dirname($newPath));
		$newPathParent=$this->getEndpoint(dirname($newPath),true);
		$body=[
			'name' => $name,
			'parentReference' => [
				'path' => $newPathParent,
			],
		];
		return $this->graph->createRequest('PATCH', $endpoint)
			->attachBody($body)
			->execute()->getBody();
	}

	/**
	 * @param $path
	 * @param $contents
	 * @param Config $config
	 * @return array|null
	 * @throws \Microsoft\Graph\Exception\GraphException
	 */
	public function upload($path,$contents, Config $config){
		$endpoint = $this->getEndpoint($path,'content');

		if (is_resource($contents)) {
			$stats = fstat($contents);
			if (empty($stats['size'])) {
				throw new InvalidStreamProvided('Empty stream');
			}
		}
		$this->createDirectory(dirname($path));
		$stream = stream_for($contents);

		return $response=$this->graph->createRequest('PUT', $endpoint)
				->attachBody($stream)
				->execute()->getBody();
	}
	public function getPermissions($path){
		$endpoint=$this->getEndpoint($path,'permissions');
		$response = $this->graph->createRequest('GET', $endpoint)->execute();
		return $response->getBody()['value']??[];
	}
	function publish($path){
		$endpoint=$this->getEndpoint($path,'createLink');
		$body=['type'=>'view','scope'=>'anonymous'];
		$response = $this->graph->createRequest('POST', $endpoint)
			->attachBody($body)->execute();
		return $response->getBody();
	}

	/**
	 * @param $path
	 * @throws \Microsoft\Graph\Exception\GraphException
	 */
	function unPublish($path){
		$permissions=$this->getPermissions($path);
		$idToRemove='';
		foreach ($permissions as $permission){
			if(in_array($this->publishPermission['role'],$permission['roles'])
				&& $permission['link']['scope']==$this->publishPermission['scope']){
				$idToRemove=$permission['id'];
				break;
			}
		}
		if(!$idToRemove){
			return ;
		}
		$endpoint=$this->getEndpoint($path,'permissions/'.$idToRemove);
		$this->graph->createRequest('DELETE', $endpoint)->execute();
	}
}
