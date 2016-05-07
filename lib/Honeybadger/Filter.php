<?php

namespace Honeybadger;

use Honeybadger\Util\Arr;

/**
 * @package  Honeybadger
 */
class Filter
{

    /**
     * Applies a list of callbacks to an array of data.
     *
     * @param   array $callbacks Callbacks to run.
     * @param   array $data      Data to filter.
     *
     * @return  array    Filtered data
     */
    public static function callbacks(array $callbacks, array $data)
    {
        if (empty($callbacks) or empty($data))
            return $data;

        $filtered = [];

        foreach ($callbacks as $callback) {
            $data = call_user_func($callback, $data);

            // A filter wants to hide this data.
            if ($data === null)
                return $data;
        }

        foreach ($data as $key => $value) {
            $filtered['filtered_' . $key] = $value;
        }

        return $filtered;
    }

    /**
     * Filters a supplied `array` of `$params`, searching for `$keys` and
     * replacing each occurence with `[FILTERED]`.
     *
     * @param   array $keys
     * @param   array $params Parameters to filter.
     *
     * @return  array  Filtered parameters.
     */
    public static function params(array $keys = [], $params = [])
    {
        if (empty($keys) or empty($params))
            return $params;

        foreach ($params as $param => & $value) {
            if (Arr::isArray($value)) {
                $value = self::params($keys, $value);
            } elseif (!is_integer($param) and in_array($param, $keys)) {
                $value = '[FILTERED]';
            }
        }

        return $params;
    }

    /**
     * @param array $classes
     * @param       $object
     *
     * @return bool
     */
    public static function ignoreByClass(array $classes = [], $object)
    {
        if (!is_object($object))
            return false;

        $object_class = get_class($object);

        foreach ($classes as $class) {
            // Remove trailing and prefixing backslash (unnecessary namespaces)
            $class = trim($class, '\\');

            if ($object_class === $class)
                return true;

            if (is_subclass_of($object, $class))
                return true;
        }

        return false;
    }

    /**
     * Replaces occurences of configured project root with `[PROJECT_ROOT]` to
     * simplify backtraces.
     *
     * @example
     *     Filter::project_root(array(
     *         'file' => 'path/to/my/app/models/user.php',
     *     ));
     *     // => array('file' => '[PROJECT_ROOT]/models/user.php')
     *
     * @param   array $line Unparsed backtrace line.
     *
     * @return  array  Filtered backtrace line.
     */
    public static function projectRoot($line)
    {
        $config       = Notice::$current ?: Honeybadger::$config;
        $project_root = (string)$config->project_root;

        if (strlen($project_root) === 0)
            return $line;

        $pattern      = '/^' . preg_quote($project_root, '/') . '/';
        $line['file'] = preg_replace($pattern, '[PROJECT_ROOT]', $line['file']);

        return $line;
    }

    /**
     * Attempts to expand paths to their real locations, if possible.
     *
     * @param array $line
     *
     * @return array
     *
     * @example
     *     Filter::expand_paths(array(
     *         'file' => '/etc/./../usr/local',
     *     ));
     *     // => array('file' => '/usr/local')
     */
    public static function expandPaths($line)
    {
        if ($path = realpath($line['file'])) {
            $line['file'] = $path;
        }

        return $line;
    }

    /**
     * Removes Honeybadger from backtraces.
     *
     * @example
     *     Filter::honeybadger_paths(array(
     *         'file' => 'path/to/lib/Honeybadger/Honeybadger.php',
     *     ));
     *     // => null
     *
     * @param $line Array of backtrace content
     *
     * @return array
     */
    public static function honeybadgerPaths($line)
    {
        if (!preg_match('/lib\/Honeybadger/', $line['file']))
            return $line;

        return null;
    }
}
