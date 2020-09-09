<?php


namespace As247\Flysystem\DriveSupport\Service;


trait HasLogger
{
    protected $logger;
    public function getLogger(){
        return $this->logger;
    }
    public function setLogger($logger){
        $this->logger=$logger;
        return $this;
    }
}
