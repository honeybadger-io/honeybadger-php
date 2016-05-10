<?php

namespace Honeybadger\Util;

use Honeybadger\Errors\NonExistentProperty;
use Honeybadger\Errors\ReadOnly;

/**
 * Basic object emulating Ruby's `OpenStruct` for storing and retrieving
 * attributes in a read-only fashion. Attributes are stored internally as an
 * array and accessible as properties. Additionally, `$attribute_methods` allow
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
abstract class SemiOpenStruct implements \ArrayAccess
{

    /**
     * @var  array  Method names that act as object attributes.
     */
    protected $attribute_methods = [];

    /**
     * Alias for `as_array`.
     *
     * @return  array  The object as an array.
     */
    public function toArray()
    {
        return $this->asArray();
    }

    /**
     * Returns the object's attributes as an array.
     *
     * @return  array  The object as an array.
     */
    public function asArray()
    {
        $attributes = get_object_vars($this);

        // Add methods to attributes.
        foreach ($this->attribute_methods as $method) {
            $attributes[$method] = $this->$method();
        }

        return $attributes;
    }

    /**
     * Converts the object to JSON.
     *
     * @param   integer $options Options to pass to `json_encode()`.
     *
     * @return  string   The JSON-encoded object attributes.
     */
    public function toJson($options = 0)
    {
        return json_encode($this->asJson(), $options);
    }

    /**
     * Override to change the attributes included in `to_json`.
     *
     * @return  array  Attributes to convert to JSON.
     */
    public function asJson()
    {
        return $this->asArray();
    }

    /**
     * Delegates to `get` which returns the requested `$attribute` if it exists,
     * or throws a `NonExistentProperty` error.
     *
     * @param   string $attribute The attribute to fetch.
     *
     * @return  mixed   The value of the attribute.
     */
    public function __get($attribute)
    {
        return $this->get($attribute);
    }

    /**
     * By default, raises a `ReadOnly` error.
     *
     * @param   string $attribute The attribute to set.
     * @param   mixed  $value     The value to set.
     *
     * @return  void
     */
    public function __set($attribute, $value)
    {
        $this->set($attribute, $value);
    }

    /**
     * Returns the property or method of the requested `$attribute`. If the
     * property or method does not exist, a `NonExistentProperty` error is
     * thrown.
     *
     * @param   string $attribute The attribute to fetch.
     *
     * @return mixed The value of the attribute.
     * @throws NonExistentProperty
     */
    public function get($attribute)
    {
        if (in_array($attribute, $this->attribute_methods)) {
            return $this->$attribute();
        } elseif (property_exists($this, $attribute)) {
            $attributes = get_object_vars($this);

            return $attributes[$attribute];
        } else {
            throw new NonExistentProperty($this, $attribute);
        }
    }

    /**
     * By default, raises a `ReadOnly` error.
     *
     * @param   string $attribute The attribute to set.
     * @param   mixed  $value     The value to set.
     *
     * @throws ReadOnly
     */
    public function set($attribute, $value)
    {
        throw new ReadOnly($this);
    }

    /**
     * Allows accessing object as an array.
     *
     *     $notice->action;
     *     // => 'index'
     *     $notice['action'];
     *     // => 'index'
     *
     * @param   string $attribute The attribute to fetch.
     *
     * @return  mixed   The value of the attribute.
     */
    public function offsetGet($attribute)
    {
        return $this->get($attribute);
    }

    /**
     * By default, raises a `ReadOnly` error.
     *
     * @param   string $attribute The attribute to set.
     * @param   mixed  $value     The value to set.
     *
     * @return  void
     */
    public function offsetSet($attribute, $value)
    {
        $this->set($attribute, $value);
    }

    /**
     * @param mixed $attribute
     *
     * @return bool
     */
    public function offsetExists($attribute)
    {
        return (property_exists($this, $attribute) or
            in_array($attribute, $this->attribute_methods));
    }

    /**
     * @param mixed $attribute
     *
     * @throws ReadOnly
     */
    public function offsetUnset($attribute)
    {
        throw new ReadOnly($this);
    }
} // End SemiOpenStruct
