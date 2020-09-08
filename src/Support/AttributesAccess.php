<?php


namespace As247\Flysystem\DriveSupport\Support;

use RuntimeException;

trait AttributesAccess
{
	protected $attributes=[];


	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return array_key_exists($offset,$this->attributes);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->attributes[$offset];
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value): void
	{
		throw new RuntimeException('Properties can not be manipulated');
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset): void
	{
		throw new RuntimeException('Properties can not be manipulated');
	}

	public function __get($name)
	{
		return $this->offsetGet($name);
	}
	public function __set($name, $value)
	{
		$this->offsetSet($name,$value);
	}
	public function __isset($name)
	{
		return $this->offsetExists($name);
	}
	public function __unset($name)
	{
		$this->offsetUnset($name);
	}
}
