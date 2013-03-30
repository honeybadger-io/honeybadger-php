<?php

namespace Honeybadger\Util;

use \Honeybadger\Errors\NonExistentProperty;
use \Honeybadger\Errors\ReadOnly;

/**
 * Basic object emulating Ruby's `OpenStruct` for storing and retrieving
 * attributes in a read-only fashion. Attributes are stored internally as an
 * array and accessible as properties.
 *
 * @example
 *     $object = new Notice(array('action' => 'index'));
 *     $object->action;
 *     // => "index"
 *
 * @package   Honeybadger
 * @category  Util
 */
abstract class SemiOpenStruct implements \ArrayAccess {

	/**
	 * @var  array  Method names that act as object attributes.
	 */
	protected $_attribute_methods = array();

	public function as_array()
	{
		$attributes = get_object_vars($this);

		foreach ($this->_attribute_methods as $method)
		{
			$attributes[$method] = $this->$method();
		}

		return $attributes;
	}

	public function as_json()
	{
		return $this->as_array();
	}

	public function to_array()
	{
		return $this->as_array();
	}

	public function to_json()
	{
		return json_encode($this->as_json());
	}

	public function get($attribute)
	{
		if (in_array($attribute, $this->_attribute_methods))
		{
			return $this->$attribute();
		}
		elseif (property_exists($this, $attribute))
		{
			$attributes = get_object_vars($this);
			return $attributes[$attribute];
		}
		else
		{
			throw new NonExistentProperty($this, $attribute);
		}
	}

	public function set($attribute, $value)
	{
		throw new ReadOnly($this);
	}

	public function __get($attribute)
	{
		return $this->get($attribute);
	}

	public function __set($attribute, $value)
	{
		$this->set($attribute, $value);
	}

	public function offsetGet($attribute)
	{
		return $this->get($attribute);
	}

	public function offsetSet($attribute, $value)
	{
		$this->set($attribute, $value);
	}

	public function offsetExists($attribute)
	{
		return (property_exists($this, $attribute) OR
		        in_array($attribute, $this->_attribute_methods));
	}

	public function offsetUnset($attribute)
	{
		throw new ReadOnly($this);
	}

} // End Object