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

    public function __construct($class)
    {
        parent::__construct(
            'Class :class is read-only',
            array(
                ':class' => get_class($class),
            )
        );

    }

} // End ReadOnly
