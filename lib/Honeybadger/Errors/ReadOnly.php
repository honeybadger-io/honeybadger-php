<?php

namespace Honeybadger\Errors;

/**
 * Thrown when trying to change a read-only property.
 *
 * @package   Honeybadger
 * @category  Errors
 */
class ReadOnly extends HoneybadgerError
{

    /**
     * @param  $class
     */
    public function __construct($class)
    {
        parent::__construct(
            'Class :class is read-only',
            [
                ':class' => get_class($class),
            ]
        );
    }
} // End ReadOnly
