<?php

namespace Yamlenv;

use Yamlenv\Exception\LoaderException;

class Yamlenv
{
    /**
     * The file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * The loader instance.
     *
     * @var \Yamlenv\Loader|null
     */
    protected $loader;

    /**
     * @var bool
     */
    private $castToUpper;

    /**
     * Create a new Yamlenv instance.
     *
     * @param string $path
     * @param string $file
     * @param bool   $castToUpper
     */
    public function __construct($path, $file = 'env.yml', $castToUpper = false)
    {
        $this->filePath    = $this->getFilePath($path, $file);
        $this->castToUpper = $castToUpper;
    }

    /**
     * Load environment file in given directory.
     *
     * @return array
     */
    public function load()
    {
        return $this->loadData();
    }

    /**
     * Load environment file in given directory.
     *
     * @return array
     */
    public function overload()
    {
        return $this->loadData(true);
    }

    /**
     * Required ensures that the specified variables exist, and returns a new validator object.
     *
     * @param string|string[] $variable
     *
     * @return \Yamlenv\Validator
     */
    public function required($variable)
    {
        $this->initialize();

        return new Validator((array) $variable, $this->loader);
    }

    /**
     * Get loader instance.
     *
     * @throws LoaderException
     *
     * @return Loader
     */
    public function getLoader()
    {
        if (!$this->loader) {
            throw new LoaderException('Loader has not been initialized yet.');
        }

        return $this->loader;
    }

    /**
     * Returns the full path to the file.
     *
     * @param string $path
     * @param string $file
     *
     * @return string
     */
    protected function getFilePath($path, $file)
    {
        if (!is_string($file)) {
            $file = 'env.yml';
        }

        $filePath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;

        return $filePath;
    }

    /**
     * Initialize loader.
     *
     * @param bool $overload
     */
    protected function initialize($overload = false)
    {
        $this->loader = new Loader($this->filePath, !$overload);

        if ($this->castToUpper) {
            $this->loader->forceUpperCase();
        }
    }

    /**
     * Actually load the data.
     *
     * @param bool $overload
     *
     * @return array
     */
    protected function loadData($overload = false)
    {
        $this->initialize($overload);

        return $this->loader->load();
    }

    /**
     * Get an environment value. Returns the default when it is null.
     *
     * @param string $key
     * @param null   $default
     *
     * @return null|string
     */
    public function getEnv($key, $default = null)
    {
        $value = $this->getLoader()->getEnvironmentVariable($key);

        return is_null($value) ? $default : $value;
    }

    /**
     * Get the raw env value from the Yaml. Can be used to fetch associative arrays from the Yaml file.
     *
     * @param string $key
     * @param null   $default
     *
     * @return array|null|string
     */
    public function getRawEnv($key, $default = null)
    {
        $value = $this->getLoader()->getYamlValue($key);

        return is_null($value) ? $default : $value;
    }
}
