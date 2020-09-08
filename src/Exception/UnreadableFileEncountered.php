<?php


namespace As247\Flysystem\DriveSupport\Exception;


class UnreadableFileEncountered extends OperationException implements FilesystemOperationFailed
{

	/**
	 * @var string
	 */
	private $location;

	public function location(): string
	{
		return $this->location;
	}

	public static function atLocation(string $location, \Throwable $previous=null, $code= 0): UnreadableFileEncountered
	{
		$e = new static("Unreadable file encountered at location {$location}.", $code, $previous);
		$e->location = $location;

		return $e;
	}
	public function operation(): string
	{
		return FilesystemOperationFailed::OPERATION_READ;
	}
}
