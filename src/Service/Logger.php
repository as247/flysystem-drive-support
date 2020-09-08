<?php


namespace As247\Flysystem\DriveSupport\Service;


class Logger
{
	protected $queries=[];
	function log($message, $level='debug'){
		//echo $message.PHP_EOL;
	}
	public function requestStart(){

	}
	public function requestEnd(){

	}
	function query($cmd,$query){
		$id=md5(json_encode($query));
		if(!isset($this->queries['total'])){
			$this->queries['total']=0;
		}
		$this->queries['total']++;
		if(isset($this->queries['counts'][$cmd][$id])){
			$this->queries['counts'][$cmd][$id]++;
		}else{
			$this->queries['counts'][$cmd][$id]=1;
		}
		$this->queries['queries'][$cmd][]=$query;
		return $this;
	}
	function getQuery($key){
		return $this->queries[$key]??null;
	}
	public function showQueryLog($query='queries'){
		if(!$query){
			$show=$this->queries;
		}else{
			$show=isset($this->queries[$query])?$this->queries[$query]:null;
		}
		print_r($show);
	}
}
