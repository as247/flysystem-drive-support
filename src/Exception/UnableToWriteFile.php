<?php


namespace As247\Flysystem\DriveSupport\Exception;

use Throwable;

class UnableToWriteFile extends OperationException implements FilesystemOperationFailed
{
	/**
	 * @var string
	 */
	private $location = '';

	/**
	 * @var string
	 */
	private $reason;

	public static function atLocation(string $location, string $reason = '', Throwable $previous = null): UnableToWriteFile
	{
		$e = new static(rtrim("Unable to write file at location: {$location}. {$reason}"), 0, $previous);
		$e->location = $location;
		$e->reason = $reason;

		return $e;
	}

	public function operation(): string
	{
		return FilesystemOperationFailed::OPERATION_WRITE;
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
