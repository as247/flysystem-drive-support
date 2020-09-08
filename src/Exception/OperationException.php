<?php


namespace As247\Flysystem\DriveSupport\Exception;

use RuntimeException;
use Throwable;

abstract class OperationException extends RuntimeException implements FilesystemOperationFailed
{
	public static $lastException=null;
	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
		static::$lastException=$this;
	}
}
