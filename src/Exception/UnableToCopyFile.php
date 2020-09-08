<?php


namespace As247\Flysystem\DriveSupport\Exception;


use Throwable;

class UnableToCopyFile extends OperationException implements FilesystemOperationFailed
{

	/**
	 * @var string
	 */
	private $source;

	/**
	 * @var string
	 */
	private $destination;

	public function source(): string
	{
		return $this->source;
	}

	public function destination(): string
	{
		return $this->destination;
	}

	public static function fromLocationTo(
		string $sourcePath,
		string $destinationPath,
		string $reason = '',
		Throwable $previous = null
	): UnableToCopyFile
	{
		$e = new static(rtrim("Unable to move file from $sourcePath to $destinationPath. $reason"), 0 , $previous);
		$e->source = $sourcePath;
		$e->destination = $destinationPath;

		return $e;
	}

	public function operation(): string
	{
		return FilesystemOperationFailed::OPERATION_COPY;
	}
}
