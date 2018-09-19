<?php

namespace Yamlenv;

use Symfony\Component\Yaml\Yaml;
use Yamlenv\Exception\ImmutableException;
use Yamlenv\Exception\InvalidFileException;
use Yamlenv\Exception\InvalidPathException;

class Loader
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Are we immutable?
     *
     * @var bool
     */
    protected $immutable;

    /**
     * @var array
     */
    protected $yamlVariables;

    /**
     * @var bool
     */
    private $castToUpper;

    /**
     * Create a new loader instance.
     *
     * @param string $filePath
     * @param bool   $immutable
     * @param bool   $castToUpper
     */
    public function __construct($filePath, $immutable = false, $castToUpper = false)
    {
        $this->filePath    = $filePath;
        $this->immutable   = $immutable;
        $this->castToUpper = $castToUpper;
    }

    /**
     * Clear an environment variable.
     *
     * This is not (currently) used by Yamlenv but is provided as a utility
     * method for 3rd party code.
     *
     * This is done using:
     * - putenv,
     * - unset($_ENV, $_SERVER).
     *
     * @param string $name
     *
     * @see setEnvironmentVariable()
     */
    public function clearEnvironmentVariable($name)
    {
        // Don't clear anything if we're immutable.
        if ($this->immutable) {
            return;
        }

        if (function_exists('putenv')) {
            putenv($name);
        }

        unset($_ENV[$name], $_SERVER[$name]);
    }

    /**
     * Search the different places for environment variables and return first value found.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getEnvironmentVariable($name)
    {
        switch (true) {
            case array_key_exists($name, $_ENV):
                return $_ENV[$name];
            case array_key_exists($name, $_SERVER):
                return $_SERVER[$name];
            default:
                $value = getenv($name);

                return $value === false ? null : $value; // switch getenv default to null
        }
    }

    /**
     * Load `.env` file in given directory.
     *
     * @return array
     */
    public function load()
    {
        $this->isReadable();
        $this->readYaml();
        $this->setEnvironmentVariables();

        return $_ENV;
    }

    /**
     * Make Yamlenv immutable.
     *
     * This means that once set, an environment variable cannot be overridden.
     */
    public function makeImmutable()
    {
        $this->immutable = true;
    }

    /**
     * Make Yamlenv mutable.
     *
     * Environment variables can be overridden
     */
    public function makeMutable()
    {
        $this->immutable = false;
    }

    /**
     * Set an environment variable.
     *
     * This is done using:
     * - putenv,
     * - $_ENV,
     * - $_SERVER.
     *
     * The environment variable value is stripped of single and double quotes.
     *
     * @throws ImmutableException
     *
     * @param string $name
     * @param string|null $value
     * @param bool $initialization
     */
    public function setEnvironmentVariable($name, $value = null, $initialization = false)
    {
        $value = $this->sanitiseVariableValue($value);

        // Don't overwrite existing environment variables if we're immutable
        // Ruby's dotenv does this with `ENV[key] ||= value`.
        if ($this->immutable && $this->getEnvironmentVariable($name) !== null) {

            // During initialization we want to keep all existing environment variables
            // e.g. Anything set on an apache or server level
            if($initialization) {
                return;
            }

            throw new ImmutableException(sprintf(
                'Environment variables cannot be overwritten in an immutable environment. Tried overwriting "%s"',
                $name
            ));
        }

        // If PHP is running as an Apache module and an existing
        // Apache environment variable exists, overwrite it
        if (function_exists('apache_getenv') && function_exists('apache_setenv') && apache_getenv($name)) {
            apache_setenv($name, $value);
        }

        if (function_exists('putenv')) {
            putenv("$name=$value");
        }

        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
    }

    /**
     * Set internal setting to force casting to uppercase.
     */
    public function forceUpperCase()
    {
        $this->castToUpper = true;
    }

    /**
     * Strips quotes from the environment variable value.
     *
     * @param string $value
     *
     * @return array
     */
    protected function sanitiseVariableValue($value)
    {
        // Symfony Yaml parser automatically converts booleans to the correct type, which does not work in the env setters
        if (is_bool($value)) {
            $value = $value === true ? 'true' : 'false';
        }

        if (is_array($value)) {
            return $value;
        }

        return trim($value);
    }

    /**
     * Ensures the given filePath is readable.
     *
     * @throws \Yamlenv\Exception\InvalidPathException
     */
    protected function isReadable()
    {
        if (!is_readable($this->filePath) || !is_file($this->filePath)) {
            throw new InvalidPathException(sprintf('Unable to read the environment file at %s.', $this->filePath));
        }
    }

    /**
     * Read lines from the file, auto detecting line endings.
     *
     * @throws InvalidFileException
     *
     * @return mixed
     */
    protected function readYaml()
    {
        $this->yamlVariables = Yaml::parse(file_get_contents($this->filePath));

        if (!is_array($this->yamlVariables)) {
            throw new InvalidFileException(sprintf('Input file does not contain valid Yaml at %s.', $this->filePath));
        }
    }

    /**
     * Flatten multidimensional array, while preserving all keys.
     *
     * @param $array
     * @param null $parentKey
     *
     * @throws ImmutableException
     *
     * @return array
     */
    private function flattenNestedValues($array, $parentKey = null)
    {
        $outputArray = [];

        foreach ($array as $key => $value) {
            $combinedKey = $this->getCombinedKey($key, $parentKey);

            if ($this->isAssociativeArray($value)) {

                $flattenedValues = $this->flattenNestedValues($value, $combinedKey);

                if (count(array_intersect_assoc(array_keys($outputArray), array_keys($flattenedValues))) > 0) {
                    throw new ImmutableException(sprintf(
                        'Environment variables cannot be overwritten in an immutable environment. Tried overwriting "%s"',
                        $combinedKey
                    ));
                }

                $outputArray = array_merge($outputArray, $flattenedValues);
            } else {
                if (is_array($value)) {
                    $outputArray[$combinedKey] = json_encode($value);
                } else {
                    $outputArray[$combinedKey] = $value;
                }
            }
        }

        return $outputArray;
    }

    /**
     * Create a combined key.
     *
     * @param $key
     * @param $parentKey
     * @param string $separator
     *
     * @return string
     */
    private function getCombinedKey($key, $parentKey = '', $separator = '_')
    {
        return $this->normalizeKey(($parentKey) ? $parentKey . $separator . $key : $key);
    }

    /**
     * @param array $var
     *
     * @return bool
     */
    private function isAssociativeArray($var)
    {
        if (!is_array($var) || empty($var)) {
            return false;
        }

        return array_keys($var) !== range(0, count($var) - 1);
    }

    /**
     * Read ( multidimensional ) array and return flat list of env variables.
     */
    private function setEnvironmentVariables()
    {
        foreach ($this->flattenNestedValues($this->yamlVariables) as $name => $value) {
            $this->setEnvironmentVariable($name, $value, true);
        }
    }

    /**
     * Perform normalization action based on class options.
     *
     * @param $key
     *
     * @return string
     */
    private function normalizeKey($key)
    {
        if ($this->castToUpper) {
            $key = strtoupper($key);
        }

        return $key;
    }

    /**
     * @param string $key
     *
     * @return string|array|null
     */
    public function getYamlValue($key)
    {
        if (is_null($key)) {
            return null;
        }

        if (isset($this->yamlVariables[$key])) {
            return $this->yamlVariables[$key];
        }

        $array = $this->yamlVariables;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return null;
            }

            $array = $array[$segment];
        }

        return $array;
    }
}
