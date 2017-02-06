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
    public function __construct($path, $file = 'env.yaml', $castToUpper = false)
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
     * Get loader instance
     *
     * @throws LoaderException
     *
     * @return Loader
     */
    public function getLoader()
    {
        if(!$this->loader)
        {
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
            $file = 'env.yaml';
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
}
