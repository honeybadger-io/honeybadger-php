<?php

namespace Honeybadger;

use Honeybadger\Backtrace\Line;
use Honeybadger\Util\SemiOpenStruct;

/**
 * Parses and represents backtraces of PHP exceptions for notices.
 *
 * @package   Honeybadger
 * @category  Backtrace
 */
class Backtrace extends SemiOpenStruct
{

    /**
     * @var  array  Holder for an array of [Backtrace\Line]s.
     */
    protected $lines = array();

    /**
     * @var  array  Holder for an array of app-specific [Backtrace\Line]s.
     */
    protected $application_lines = array();

    /**
     * Parses a PHP backtrace and returns a new `Backtrace` object. Provided
     * options are passed to [Line::parse] which may include filters
     * (see [Config::$backtrace_filters]) which are called for with each line
     * in the trace.
     *
     * @param   array $backtrace The raw PHP backtrace.
     * @param   array $options Options and filters to apply to lines.
     * @return  Backtrace          The parsed backtrace.
     */
    public static function parse(array $backtrace, array $options = array())
    {
        $lines = array();

        // Parse each line in the backtrace.
        foreach ($backtrace as $line) {
            $parsed = Line::parse($line, $options);

            if ($parsed !== null)
                $lines[] = $parsed;
        }

        // Instantiate a new backtrace from the lines
        return new self($lines);
    }

    /**
     * Instantiates a new `Backtrace` with the supplied lines.
     *
     * @param  array $lines Backtrace lines.
     */
    public function __construct(array $lines = array())
    {
        $this->lines = $lines;

        foreach ($lines as $line) {
            if (!$line->isApplication())
                continue;

            $this->application_lines[] = $line;
        }
    }

    /**
     * Checks whether the backtrace has lines.
     *
     * @return Boolean `true` when backtrace is not empty, `false` otherwise.
     */
    public function hasLines()
    {
        return (!empty($this->lines));
    }

    /**
     * Checks whether the backtrace has application lines.
     *
     * @return Boolean `true` when backtrace has application lines,
     *                  `false` otherwise.
     */
    public function hasApplicationLines()
    {
        return (!empty($this->application_lines));
    }

    /**
     * Formats the backtrace as a string, similar to the format of a typical
     * Ruby backtrace (mostly for compatability).
     *
     * @return  string  The backtrace as a string.
     */
    public function __toString()
    {
        return implode("\n", array_map(function ($line) {
            return (string)$line;
        }, $this->lines));
    }

    /**
     * Formats the backtrace as an array.
     *
     * @return  array  The backtrace lines.
     */
    public function asArray()
    {
        return array_map(function ($line) {
            return $line->toArray();
        }, $this->lines);
    }

} // End Backtrace
