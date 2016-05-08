<?php

namespace Honeybadger\Backtrace;

use Honeybadger\Filter;
use Honeybadger\Util\SemiOpenStruct;

/**
 * Handles backtrace parsing line by line.
 *
 * @package   Honeybadger
 * @category  Backtrace
 */
class Line extends SemiOpenStruct
{

    protected $_attribute_methods = ['source'];

    /**
     * @var  string  The file portion of the line.
     */
    protected $file;

    /**
     * @var  integer  The line number portion of the line.
     */
    protected $number;

    /**
     * @var  string  The method of the line (such as index).
     */
    protected $method;

    /**
     * @var  string  The source code surrounding the matching line.
     */
    protected $source;

    /**
     * @var  string  Filtered representation of [Line::$file].
     */
    protected $filtered_file;

    /**
     * @var  string  Filtered representation of [Line::$number].
     */
    protected $filtered_number;

    /**
     * @var  string  Filtered representation of [Line::$method].
     */
    protected $filtered_method;

    /**
     * Instantiates a new backtrace line from a given filename, line number,
     * and
     * method name.
     *
     * @param  string  $file            The filename in the given backtrace
     *                                  line
     * @param  integer $number          The line number of the file
     * @param  string  $method          The method referenced in the given
     *                                  backtrace line
     *
     * @param  string  $filteredFile    The filename in the given backtrace
     *                                  line after filter
     * @param  integer $filteredNumber  The line number of the file after
     *                                  filter
     * @param  string  $filteredMethod  The method referenced in the given
     *                                  backtrace line after filter
     */
    public function __construct($file,
                                $number,
                                $method,
                                $filteredFile = null,
                                $filteredNumber = null,
                                $filteredMethod = null)
    {
        if ($filteredFile === null) {
            $filteredFile = $file;
        }

        if ($filteredNumber === null) {
            $filteredNumber = $number;
        }

        if ($filteredMethod === null) {
            $filteredMethod = $method;
        }

        $this->filtered_file   = $filteredFile;
        $this->filtered_number = $filteredNumber;
        $this->filtered_method = $filteredMethod;
        $this->file            = $file;
        $this->number          = $number;
        $this->method          = $method;
    }

    /**
     * Parses a single line of a given backtrace.
     *
     * @param   array $unparsedLine  The raw line from `caller` or some
     *                               backtrace.
     * @param   array $options
     *
     * @return  Line    The parsed backtrace line.
     */
    public static function parse(array $unparsedLine, array $options = [])
    {
        if (!isset($unparsedLine['file']) or empty($unparsedLine['file'])) {
            $unparsedLine['file'] = '{PHP internal call}';
        }

        if (!isset($options['filters'])) {
            $options['filters'] = [];
        }

        $filtered = Filter::callbacks($options['filters'], $unparsedLine);

        if ($filtered === null) {
            return null;
        }

        // Extract the filtered line parameters
        extract(
            $filtered + [
                'filtered_file'     => null,
                'filtered_line'     => null,
                'filtered_function' => null,
            ]
        );

        // Extract the original line parameters
        extract(
            $unparsedLine + [
                'file'     => '',
                'line'     => '',
                'function' => '',
            ]
        );

        return new self(
            $file,
            $line,
            $function,
            $filtered_file,
            $filtered_line,
            $filtered_function
        );
    }

    /**
     * Formats the backtrace line as a string.
     *
     *     echo $line; // => "app/models/user.php:109:in `some_method'"
     *
     * @return  string  The backtrace line.
     */
    public function __toString()
    {
        return sprintf(
            "%s:%d:in `%s'",
            $this->filtered_file,
            $this->filtered_number,
            $this->filtered_method
        );
    }

    /**
     * Checks if this `Line` matches another supplied `Line`.
     *
     * @param   Line $other The `Line` to match.
     *
     * @return  boolean  `true` if objects match, `false` otherwise.
     */
    public function equals($other)
    {
        return ((string)$this == (string)$other);
    }

    /**
     * Checks if this `Line` is a part of the configured project. Ignores files
     * under `[PROJECT_ROOT]/vendor`.
     *
     * @return  boolean  `true` if application line, `false` otherwise.
     */
    public function isApplication()
    {
        return (strpos($this->filtered_file, '[PROJECT_ROOT]') === 0 and
            strpos($this->filtered_file, '[PROJECT_ROOT]/vendor') === false);
    }

    /**
     * Extracts the source code of the line. Optionally, `$radius` can be
     * supplied to specify the number of lines before and after to extract.
     *
     * @param   integer $radius Radius to extract.
     *
     * @return  array    The extracted source code.
     */
    public function source($radius = 2)
    {
        if ($this->source) {
            return $this->source;
        }

        return $this->source = $this->getSource(
            $this->file,
            $this->number,
            $radius
        );
    }

    /**
     * Extracts source code from the specified `$file`, starting `$radius`
     * lines before the specified `$number`.
     *
     * @param     $file
     * @param     $number
     * @param int $radius
     *
     * @return array The extracted source.
     */
    private function getSource($file, $number, $radius = 2)
    {
        if (!is_file($file) or !is_readable($file)) {
            return [];
        }

        $before = $after = $radius;
        $start  = ($number - 1) - $before;

        if ($start <= 0) {
            $start  = 1;
            $before = 1;
        }

        $duration = $before + 1 + $after;
        $size     = $start + $duration;
        $lines    = [];

        $file_handle = fopen($file, 'r');

        for ($l = 1; $l < $size; $l++) {
            $line = fgets($file_handle);

            if ($l < $start) {
                continue;
            }

            $lines["$l"] = $this->trimLine($line);
        }

        return $lines;
    }

    /**
     * Replaces tabs with spaces and strips endlines and trailing whitespace
     * from the supplied `$line`.
     *
     * @param   string $line The line to trim.
     *
     * @return  string  The trimmed line.
     */
    private function trimLine($line)
    {
        $trimmed = trim($line, "\n\r\0\x0B");

        return preg_replace(
            [
                '/\s*$/D',
                '/\t/'
            ],
            [
                '',
                '    ',
            ],
            $trimmed
        );
    }

    /**
     * Formats the backtrace line as an array.
     *
     * @return  array  The backtrace line
     */
    public function asArray()
    {
        return [
            'file'   => $this->filtered_file,
            'number' => $this->filtered_number,
            'method' => $this->filtered_method,
        ];
    }
} // End Line
