<?php


namespace As247\Flysystem\DriveSupport\Exception;

use Throwable;

class UnableToDeleteFile extends OperationException implements FilesystemOperationFailed
{
	/**
	 * @var string
	 */
	private $location = '';

	/**
	 * @var string
	 */
	private $reason;

	public static function atLocation(string $location, string $reason = '', Throwable $previous = null): UnableToDeleteFile
	{
		$e = new static(rtrim("Unable to delete file located at: {$location}. {$reason}"), 0, $previous);
		$e->location = $location;
		$e->reason = $reason;

		return $e;
	}

	public function operation(): string
	{
		return FilesystemOperationFailed::OPERATION_DELETE;
	}

	public function reason(): string
	{
		return $this->reason;
	}

	public function location(): string
	{
		return $this->location;
	}
}
