<?php

namespace Honeybadger\Errors;

class ReadOnly extends HoneybadgerError {

	public function __construct($class)
	{
		parent::__construct('Class :class is read-only', array(
			':class' => get_class($class),
		));
	}

} // End ReadOnly