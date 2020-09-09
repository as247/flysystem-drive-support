<?php


namespace As247\Flysystem\DriveSupport\Exception;


class PathOutsideRootException extends \LogicException
{
    public static function atLocation($path,\Throwable $previous=null){
        return new static('Path is outside of the defined root, path: [' . $path . ']',0,$previous);
    }
}
