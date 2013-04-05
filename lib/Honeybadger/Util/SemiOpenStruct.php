<?php

namespace Honeybadger\Util;

use \Honeybadger\Errors\NonExistentProperty;
use \Honeybadger\Errors\ReadOnly;

/**
 * Basic object emulating Ruby's `OpenStruct` for storing and retrieving
 * attributes in a read-only fashion. Attributes are stored internally as an
 * array and accessible as properties. Additionally, `$_attribute_methods` allow
 * accessing methods (e.g. `$object->fullpath()`) as
 * attributes (`$object->fullpath`).
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

	/**
	 * Returns the object's attributes as an array.
	 *
	 * @return  array  The object as an array.
	 */
	public function as_array()
	{
		$attributes = get_object_vars($this);

		// Add methods to attributes.
		foreach ($this->_attribute_methods as $method)
		{
			$attributes[$method] = $this->$method();
		}

		// Remove attributes prefixed with an underscore.
		foreach ($attributes as $attribute => $value)
		{
			if (strpos($attribute, '_') === 0)
			{
				unset($attributes[$attribute]);
			}
		}

		return $attributes;
	}

	/**
	 * Override to change the attributes included in `to_json`.
	 *
	 * @return  array  Attributes to convert to JSON.
	 */
	public function as_json()
	{
		return $this->as_array();
	}

	/**
	 * Alias for `as_array`.
	 *
	 * @return  array  The object as an array.
	 */
	public function to_array()
	{
		return $this->as_array();
	}

	/**
	 * Converts the object to JSON.
	 *
	 * @param   integer  $options  Options to pass to `json_encode()`.
	 * @return  string   The JSON-encoded object attributes.
	 */
	public function to_json($options = 0)
	{
		return json_encode($this->as_json(), $options);
	}

	/**
	 * Returns the property or method of the requested `$attribute`. If the
	 * property or method does not exist, a `NonExistentProperty` error is
	 * thrown.
	 *
	 * @param   string  $attribute The attribute to fetch.
	 * @return  mixed   The value of the attribute.
	 */
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

	/**
	 * By default, raises a `ReadOnly` error.
	 *
	 * @param   string  $attribute The attribute to set.
	 * @param   mixed   $value     The value to set.
	 * @return  void
	 */
	public function set($attribute, $value)
	{
		throw new ReadOnly($this);
	}

	/**
	 * Delegates to `get` which returns the requested `$attribute` if it exists,
	 * or throws a `NonExistentProperty` error.
	 *
	 * @param   string  $attribute The attribute to fetch.
	 * @return  mixed   The value of the attribute.
	 */
	public function __get($attribute)
	{
		return $this->get($attribute);
	}

	/**
	 * By default, raises a `ReadOnly` error.
	 *
	 * @param   string  $attribute The attribute to set.
	 * @param   mixed   $value     The value to set.
	 * @return  void
	 */
	public function __set($attribute, $value)
	{
		$this->set($attribute, $value);
	}

	/**
	 * Allows accessing object as an array.
	 *
	 *     $notice->action;
	 *     // => 'index'
	 *     $notice['action'];
	 *     // => 'index'
	 *
	 * @param   string  $attribute  The attribute to fetch.
	 * @return  mixed   The value of the attribute.
	 */
	public function offsetGet($attribute)
	{
		return $this->get($attribute);
	}

	/**
	 * By default, raises a `ReadOnly` error.
	 *
	 * @param   string  $attribute The attribute to set.
	 * @param   mixed   $value     The value to set.
	 * @return  void
	 */
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

} // End SemiOpenStruct
