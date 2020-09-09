<?php


namespace As247\Flysystem\DriveSupport\Service;

use As247\Flysystem\DriveSupport\Exception\ApiException;
use As247\Flysystem\DriveSupport\Support\StorageAttributes;
use Google_Http_MediaFileUpload;
use Google_Service_Drive;
use Google_Service_Drive_Permission;
use GuzzleHttp\Psr7\Stream;
use League\Flysystem\AdapterInterface;
use Psr\Http\Message\RequestInterface;
use Google_Service_Drive_FileList;
use Google_Service_Drive_DriveFile;
use Psr\Http\Message\StreamInterface;

class GoogleDrive
{
	/**
	 * MIME tyoe of directory
	 *
	 * @var string
	 */
	const DIRMIME = 'application/vnd.google-apps.folder';

	/**
	 * Default options
	 *
	 * @var array
	 */
	protected static $defaultOptions = [
		'root'=>'',
		'spaces' => 'drive',
		'useHasDir' => false,
		'additionalFetchField' => '',
		'publishPermission' => [
			'type' => 'anyone',
			'role' => 'reader',
			'withLink' => true
		],
		'appsExportMap' => [
			'application/vnd.google-apps.document' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.google-apps.spreadsheet' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.google-apps.drawing' => 'application/pdf',
			'application/vnd.google-apps.presentation' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'application/vnd.google-apps.script' => 'application/vnd.google-apps.script+json',
			'default' => 'application/pdf'
		],
		// Default parameters for each command
		// see https://developers.google.com/drive/v3/reference/files
		// ex. 'defaultParams' => ['files.list' => ['includeTeamDriveItems' => true]]
		'defaultParams' => [
			'files.list'=>[
				'corpora'=>'user'// default is user
			],
		],
		'teamDrive' => false,
	];
	protected $options;

	protected $publishPermission;
	/**
	 * Fetch fields setting for get
	 *
	 * @var string
	 */
	protected $fetchFieldsGet='id,name,mimeType,modifiedTime,parents,permissions,size,webContentLink,webViewLink';
	protected $fetchFieldsList='files({{fieldsGet}}),nextPageToken';
	protected $additionalFields;
	protected $defaultParams;
	protected $service;
	protected $logger;
	protected $logQuery=false;
	public function __construct(Google_Service_Drive $service,$options=[])
	{
		$this->service=$service;
		$this->logger=new Logger();
		$this->options = array_replace_recursive(static::$defaultOptions, $options);
		$this->publishPermission = $this->options['publishPermission'];

		if ($this->options['additionalFetchField']) {
			$this->fetchFieldsGet .= ',' . $this->options['additionalFetchField'];
			$this->additionalFields = explode(',', $this->options['additionalFetchField']);
		}
		$this->fetchFieldsList = str_replace('{{fieldsGet}}', $this->fetchFieldsGet, $this->fetchFieldsList);
		if (isset($this->options['defaultParams'])) {
			$this->defaultParams = $this->options['defaultParams'];
		}
		if ($this->options['teamDrive']) {
			if(is_string($this->options['teamDrive'])){
				$this->options['teamDrive']=[
					'driveId'=>$this->options['teamDrive'],
					'corpora'=>'drive',
					'includeItemsFromAllDrives'=>true,
				];
			}
			$this->enableTeamDriveSupport();
		}
	}
	public function isTeamDrive(){
		return $this->options['teamDrive'];
	}
	public function getTeamDriveId(){
		return $this->options['teamDrive']['driveId']??null;
	}
	public function getClient(){
		return $this->service->getClient();
	}
	public function normalizeFileInfo(Google_Service_Drive_DriveFile $object, $path)
	{
		$id = $object->getId();
		$result = [
			'id'=>$id,
			'name' => $object->getName(),
			StorageAttributes::ATTRIBUTE_PATH => is_string($path)? ltrim($path,'\/'):null,
			StorageAttributes::ATTRIBUTE_TYPE => $object->mimeType === self::DIRMIME ? StorageAttributes::TYPE_DIRECTORY : StorageAttributes::TYPE_FILE,
			StorageAttributes::ATTRIBUTE_LAST_MODIFIED=>strtotime($object->getModifiedTime())
		];
		$result[StorageAttributes::ATTRIBUTE_MIME_TYPE] = $object->getMimeType();
		$result[StorageAttributes::ATTRIBUTE_FILE_SIZE] = (int) $object->getSize();

		$result[StorageAttributes::ATTRIBUTE_VISIBILITY]=$this->getVisibility($object);

		// attach additional fields
		if ($this->additionalFields) {
			foreach($this->additionalFields as $field) {
				if (property_exists($object, $field)) {
					$result[$field] = $object->$field;
				}
			}
		}
		return $result;
	}
	protected function getVisibility(Google_Service_Drive_DriveFile $object){
		$permissions = $object->getPermissions();
		$visibility = AdapterInterface::VISIBILITY_PRIVATE;
		foreach ($permissions as $permission) {
			if ($permission->type === $this->publishPermission['type'] && $permission->role === $this->publishPermission['role']) {
				$visibility = AdapterInterface::VISIBILITY_PUBLIC;
				break;
			}
		}
		return $visibility;
	}
	/**
	 * Enables Team Drive support by changing default parameters
	 *
	 * @return void
	 *
	 * @see https://developers.google.com/drive/v3/reference/files
	 * @see \Google_Service_Drive_Resource_Files
	 */
	public function enableTeamDriveSupport()
	{
		$this->defaultParams = array_merge_recursive(
			array_fill_keys([
				'files.copy', 'files.create', 'files.delete',
				'files.trash', 'files.get', 'files.list', 'files.update',
				'files.watch',
				'files.permission.create',
				'files.permission.delete'
			], ['supportsAllDrives' => true]),
			$this->defaultParams
		);

		$this->mergeCommandDefaultParams('files.list',$this->options['teamDrive']);
	}



	protected function getParams($cmd, ...$params){
		$default=$this->getDefaultParams($cmd);
		return array_replace($default,...$params);
	}
	protected function getDefaultParams($cmd){
		if(isset($this->defaultParams[$cmd]) && is_array($this->defaultParams[$cmd])){
			return $this->defaultParams[$cmd];
		}
		return [];
	}
	protected function mergeCommandDefaultParams($cmd,$params){
		if(!isset($this->defaultParams[$cmd])){
			$this->defaultParams[$cmd]=[];
		}
		$this->defaultParams[$cmd]=array_replace_recursive($this->defaultParams[$cmd],$params);
		return $this;
	}

	/**
	 * Create directory
	 * @param $name
	 * @param $parentId
	 * @return bool|Google_Service_Drive_DriveFile|RequestInterface
	 */
	public function dirCreate($name, $parentId){
		$this->logger->log("Creating directory $name in $parentId");
		$file = new Google_Service_Drive_DriveFile();
		$file->setName($name);
		$file->setParents([
			$parentId
		]);
		$file->setMimeType(self::DIRMIME);
		return $this->filesCreate($file);
	}
	/**
	 * Find files by name in given directory
	 * @param $name
	 * @param $parent
	 * @param $mineType
	 * @return Google_Service_Drive_FileList|Google_Service_Drive_DriveFile[]
	 */
	public function filesFindByName($name,$parent, $mineType=null){
		if($parent instanceof Google_Service_Drive_DriveFile){
			$parent=$parent->getId();
		}
		$this->logger->log("Find $name{[$mineType]} in $parent ");
		$client=$this->service->getClient();
		$client->setUseBatch(true);
		$batch = $this->service->createBatch();
		$q='trashed = false and "%s" in parents and name = "%s"';
		$args = [
			'pageSize' => 2,
			'q' =>sprintf($q,$parent,$name,static::DIRMIME),
		];
		$filesMatchedName=$this->filesListFiles($args);
		$q='trashed = false and "%s" in parents';
		if($mineType){
			$q.=" and mimeType ".$mineType;
		}
		$args = [
			'pageSize' => 50,
			'q' =>sprintf($q,$parent,$name,static::DIRMIME),
		];
		$otherFiles=$this->filesListFiles($args);
		$batch->add($filesMatchedName,'matched');
		$batch->add($otherFiles,'others');
		$results = $batch->execute();
		$files=[];
		$isFullResult=empty($mineType);//if limited to a mime type so it is not full result

		foreach ($results as $key => $result) {
			if ($result instanceof Google_Service_Drive_FileList) {
				if($key==='response-matched'){
					if(count($result)>1){
						throw new ApiException("Duplicated file ".$name.' in '.$parent);
					}
				}
				foreach ($result as $file) {
					if (!isset($files[$file->id])) {
						$files[$file->id] = $file;
					}
				}
				if ($key === 'response-others' && $result->nextPageToken) {
					$isFullResult = false;
				}
			}
		}

		$client->setUseBatch(false);
		$this->logQuery('files.list.batch',['find for '.$name.' in '.$parent]);
		$list=new Google_Service_Drive_FileList();
		$list->setFiles($files);
		return [$list,$isFullResult];
	}
	/**
	 * @param array $optParams
	 * @return Google_Service_Drive_FileList | RequestInterface
	 */
	public function filesListFiles($optParams = array()){
		if(!$this->service->getClient()->shouldDefer()) {
			$this->logQuery('files.list', func_get_args());
		}
		$optParams=$this->getParams('files.list',['fields' => $this->fetchFieldsList],$optParams);
		return $this->service->files->listFiles($optParams);
	}

	/**
	 * @param $fileId
	 * @param array $optParams
	 * @return Google_Service_Drive_DriveFile|RequestInterface
	 */
	public function filesGet($fileId, $optParams = array()){
		if($fileId instanceof Google_Service_Drive_DriveFile){
			$fileId=$fileId->getId();
		}
		$this->logQuery('files.get',func_get_args());
		$optParams=$this->getParams('files.get',['fields' => $this->fetchFieldsGet],$optParams);
		return $this->service->files->get($fileId,$optParams);
	}

	/**
	 * @param Google_Service_Drive_DriveFile $postBody
	 * @param array $optParams
	 * @return Google_Service_Drive_DriveFile | RequestInterface
	 */
	public function filesCreate(Google_Service_Drive_DriveFile $postBody, $optParams = array()){
		$this->logQuery('files.create',func_get_args());
		$optParams=$this->getParams('files.create',['fields' => $this->fetchFieldsGet],$optParams);
		return $this->service->files->create($postBody,$optParams);
	}

	public function filesUpdate($fileId, Google_Service_Drive_DriveFile $postBody, $optParams = array()){
		if($fileId instanceof Google_Service_Drive_DriveFile){
			$fileId=$fileId->getId();
		}
		$this->logQuery('files.update',func_get_args());
		$optParams=$this->getParams('files.update',['fields' => $this->fetchFieldsGet],$optParams);
		return $this->service->files->update($fileId,$postBody,$optParams);
	}
	public function filesCopy($fileId, Google_Service_Drive_DriveFile $postBody, $optParams = array()){
		if($fileId instanceof Google_Service_Drive_DriveFile){
			$fileId=$fileId->getId();
		}
		$this->logQuery('files.copy',func_get_args());
		$optParams=$this->getParams('files.copy',['fields' => $this->fetchFieldsGet],$optParams);
		return $this->service->files->copy($fileId,$postBody,$optParams);
	}
	public function filesDelete($fileId, $optParams = array()){
		if($fileId instanceof Google_Service_Drive_DriveFile){
			$fileId=$fileId->getId();
		}
		$this->logQuery('files.delete',func_get_args());
		$optParams=$this->getParams('files.delete',$optParams);
		return $this->service->files->delete($fileId,$optParams);
	}

	public function filesRead($fileId){
		if($fileId instanceof Google_Service_Drive_DriveFile){
			$fileId=$fileId->getId();
		}
		$this->service->getClient()->setUseBatch(true);
		$stream=null;
		$client=$this->service->getClient()->authorize();
		$response = $client->send($this->filesGet($fileId, ['alt' => 'media']), ['stream' => true]);
		if ($response->getBody() instanceof Stream) {
			$stream = $response->getBody()->detach();
		}
		$this->service->getClient()->setUseBatch(false);
		return $stream;
	}

	/**
	 * Publish file
	 */
	public function publish(Google_Service_Drive_DriveFile $file)
	{

		if ($this->getVisibility($file) === AdapterInterface::VISIBILITY_PUBLIC) {//already published
			return;
		}
		$permission = new Google_Service_Drive_Permission($this->publishPermission);
		$optParams=$this->getParams('files.permission.create');
		if ($newPermission=$this->service->permissions->create($file->getId(), $permission, $optParams)) {
			$permissions=$file->getPermissions();
			$permissions=array_merge($permissions,[$newPermission]);
			$file->setPermissions($permissions);
		}

	}

	/**
	 * Un-publish specified path item
	 */
	public function unPublish(Google_Service_Drive_DriveFile $file)
	{

		$permissions = $file->getPermissions();
		$optParams=$this->getParams('files.permission.delete');
		foreach ($permissions as $index=> $permission) {
			if ($permission->type === 'anyone' && $permission->role === 'reader') {
				$this->service->permissions->delete($file->getId(), $permission->getId(), $optParams);
				unset($permissions[$index]);
			}
		}
		$file->setPermissions($permissions);
	}

	public function filesUploadChunk(Google_Service_Drive_DriveFile $file,StreamInterface $contents,$chunk){
		$client = $this->service->getClient();

		$client->setDefer(true);
		if (!$file->getId()) {
			$request = $this->filesCreate($file);
		} else {
			$update=new Google_Service_Drive_DriveFile();
			$update->setMimeType($file->getMimeType());
			$request = $this->filesUpdate($file->getId(), $update);
		}
		$mime=$file->getMimeType();
		// Create a media file upload to represent our upload process.
		$media = new Google_Http_MediaFileUpload($client, $request, $mime, null, true, $chunk);
		$media->setFileSize($contents->getSize());
		// Upload the various chunks. $status will be false until the process is
		// complete.
		$status = false;
		$contents->rewind();
		while (!$status && !$contents->eof()) {
			$status = $media->nextChunk($contents->read($chunk));
		}

		$client->setDefer(false);
		return $status;
	}
	/**
	 * @param Google_Service_Drive_DriveFile $file
	 * @param $contents
	 * @return Google_Service_Drive_DriveFile|RequestInterface
	 */
	public function filesUploadSimple(Google_Service_Drive_DriveFile $file,StreamInterface $contents){
		$params = [
			'data' => $contents->getContents(),
			'uploadType' => 'media',
		];
		if (!$file->getId()) {
			$obj = $this->filesCreate($file, $params);
		} else {
			$update=new Google_Service_Drive_DriveFile();
			$update->setMimeType($file->getMimeType());
			$obj = $this->filesUpdate($file->getId(), $update, $params);
		}
		return $obj;
	}

	protected function logQuery($cmd,$query){
		if(!$this->logQuery){
			return ;
		}
		$this->logger->query($cmd,$query);
	}

}
