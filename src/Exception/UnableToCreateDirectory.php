<?php


namespace As247\Flysystem\DriveSupport\Exception;


class UnableToCreateDirectory extends OperationException implements FilesystemOperationFailed
{
	/**
	 * @var string
	 */
	private $location;

	public static function atLocation(string $dirname, string $errorMessage = ''): UnableToCreateDirectory
	{
		$message = "'Unable to create a directory at {$dirname}. ${errorMessage}";
		$e = new static(rtrim($message));
		$e->location = $dirname;

		return $e;
	}

	public function operation(): string
	{
		return FilesystemOperationFailed::OPERATION_CREATE_DIRECTORY;
	}

	public function location(): string
	{
		return $this->location;
	}
}
