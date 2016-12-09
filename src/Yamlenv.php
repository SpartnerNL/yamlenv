<?php

namespace Yamlenv;

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
     * Create a new Yamlenv instance.
     *
     * @param string $path
     * @param string $file
     */
    public function __construct($path, $file = 'env.yaml')
    {
        $this->filePath = $this->getFilePath($path, $file);
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
     * Initialize loader
     *
     * @param bool $overload
     */
    protected function initialize($overload = false)
    {
        $this->loader = new Loader($this->filePath, !$overload);
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
