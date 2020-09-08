<?php


namespace As247\Flysystem\DriveSupport\Exception;

use Throwable;

class UnableToReadFile extends OperationException implements FilesystemOperationFailed
{
	/**
	 * @var string
	 */
	private $location = '';

	/**
	 * @var string
	 */
	private $reason;

	public static function fromLocation(string $location, string $reason = '', Throwable $previous = null): UnableToReadFile
	{
		$e = new static(rtrim("Unable to read file from location: {$location}. {$reason}"), 0, $previous);
		$e->location = $location;
		$e->reason = $reason;

		return $e;
	}

	public function operation(): string
	{
		return FilesystemOperationFailed::OPERATION_READ;
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
