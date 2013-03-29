<?php

namespace Honeybadger\Errors;

class NonExistentProperty extends HoneybadgerError {

	public function __construct($class, $property)
	{
		parent::__construct('Missing method or property :property for :class', array(
			':class'    => get_class($class),
			':property' => $property,
		));
	}

} // End NonExistentProperty