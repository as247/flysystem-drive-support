<?php

namespace As247\Flysystem\DriveSupport\Exception;

use League\Flysystem\FilesystemException;
use InvalidArgumentException;

class InvalidStreamProvided extends InvalidArgumentException implements FilesystemException
{
}
