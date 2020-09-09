<?php


namespace As247\Flysystem\DriveSupport\Service;


class Logger
{
	protected $logDir;
	protected $enabled=false;
	public function __construct($logDir='')
    {
        $this->logDir=$logDir;
        if(is_dir($logDir)){
            $this->enabled=true;
        }
    }

    function log($message, $level='debug'){
	    if(!$this->enabled){
	        return $this;
        }
	    if(is_array($message) || is_object($message)){
	        $message=json_encode($message,JSON_PRETTY_PRINT);
        }
		$this->write("[$level] $message",'debug');
	    return $this;
	}

	function query($cmd, $query){
	    if(!$this->enabled){
	        return $this;
        }
        $query=json_encode($query,JSON_PRETTY_PRINT);
        $this->write("{$cmd} $query",'query');
		return $this;
	}
	protected function write($line,$file){
	    if(!$this->enabled){
	        return ;
        }
	    $time=date('Y-m-d h:i:s');
	    file_put_contents($this->logDir."/$file.log",$time.' '.$line.PHP_EOL,FILE_APPEND);
    }
    public function enable($flag=true){
	    $previous= $this->enabled;
	    $this->enabled=$flag;
	    return $previous;
    }
}
