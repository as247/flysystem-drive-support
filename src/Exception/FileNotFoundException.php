<?php


namespace As247\Flysystem\DriveSupport\Exception;


use League\Flysystem\FilesystemException;
use Exception;
use Throwable;

class FileNotFoundException extends Exception implements FilesystemException
{
	/**
	 * @var string
	 */
	protected $path;

	/**
	 * Constructor.
	 *
	 * @param string     $path
	 * @param int        $code
	 * @param \Exception $previous
	 */
	public function __construct($path, $code = 0, Throwable $previous = null)
	{
		if(!is_string($path)){
			$path=json_encode($path);
		}
		$this->path = $path;

		parent::__construct('File not found at path: ' . $this->getPath(), $code, $previous);
	}

	/**
	 * Get the path which was not found.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}
	public static function create($path){
		return new static($path);
	}
}
