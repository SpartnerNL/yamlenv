<?php

use Yamlenv\Loader;

class LoaderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $folder;

    /**
     * @var \Yamlenv\Loader
     */
    private $immutableLoader;

    /**
     * @var array
     */
    private $keyVal;

    /**
     * @var \Yamlenv\Loader
     */
    private $mutableLoader;

    public static function setupBeforeClass()
    {
        if (!function_exists('apache_getenv')) {
            /**
             * @param $string
             *
             * @return array|false|string
             */
            function apache_getenv($string) {
                return getenv($string);
            }
        }

        if(!function_exists('apache_setenv'))
        {
            /**
             * @param $string
             */
            function apache_setenv($string) {
                putenv($string);
            }
        }
    }

    public function setUp()
    {
        $this->folder = dirname(__DIR__) . '/fixtures/valid/env.yml';

        // Generate a new, random keyVal.
        $this->keyVal(true);

        // Build an immutable and mutable loader for convenience.
        $this->mutableLoader   = new Loader($this->folder);
        $this->immutableLoader = new Loader($this->folder, true);
    }

    public function testImmutableLoaderCannotClearEnvironmentVars()
    {
        // Set an environment variable.
        $this->immutableLoader->setEnvironmentVariable($this->key(), $this->value());

        // Attempt to clear the environment variable, check that it fails.
        $this->immutableLoader->clearEnvironmentVariable($this->key());

        $this->assertSame($this->value(), $this->immutableLoader->getEnvironmentVariable($this->key()));
        $this->assertSame($this->value(), getenv($this->key()));
        $this->assertSame(true, isset($_ENV[$this->key()]));
        $this->assertSame(true, isset($_SERVER[$this->key()]));
    }

    public function testMutableLoaderClearsEnvironmentVars()
    {
        // Set an environment variable.
        $this->mutableLoader->setEnvironmentVariable($this->key(), $this->value());

        // Clear the set environment variable.
        $this->mutableLoader->clearEnvironmentVariable($this->key());
        $this->assertSame(null, $this->mutableLoader->getEnvironmentVariable($this->key()));
        $this->assertSame(false, getenv($this->key()));
        $this->assertSame(false, isset($_ENV[$this->key()]));
        $this->assertSame(false, isset($_SERVER[$this->key()]));
    }

    /**
     * @expectedException \Yamlenv\Exception\ImmutableException
     * @expectedExceptionMessage Environment variables cannot be overwritten in an immutable environment.
     */
    public function testMutableLoaderCanBeSetToImmutable()
    {
        // Set an environment variable.
        $this->mutableLoader->setEnvironmentVariable($this->key(), $this->value());

        // Set loader to immutable
        $this->mutableLoader->makeImmutable();

        // Try to override an environment variable.
        $this->mutableLoader->setEnvironmentVariable($this->key(), 'foobar');
    }

    public function testImmutableLoaderCanBeSetToMutable()
    {
        // Set an environment variable.
        $this->immutableLoader->setEnvironmentVariable($this->key(), $this->value());

        // Set loader to immutable
        $this->immutableLoader->makeMutable();

        // Try to override an environment variable.
        $this->immutableLoader->setEnvironmentVariable($this->key(), 'foobar');

        $this->assertSame('foobar', $this->immutableLoader->getEnvironmentVariable($this->key()));
        $this->assertSame('foobar', getenv($this->key()));
    }

    public function testLoaderCanGetServerVariables()
    {
        $this->assertSame($_SERVER['PHP_SELF'], $this->immutableLoader->getEnvironmentVariable('PHP_SELF'));
    }

    public function testLoaderCanGetApacheVariables()
    {
        $this->keyVal(true);
        apache_setenv($this->key(), 'test');

        $this->immutableLoader->setEnvironmentVariable($this->key(), $this->value());
        $this->assertSame($this->value(), $this->immutableLoader->getEnvironmentVariable($this->key()));
    }

    /**
     * @expectedException \Yamlenv\Exception\ImmutableException
     * @expectedExceptionMessage Environment variables cannot be overwritten in an immutable environment.
     */
    public function testFlattenNestedValuesThrowsExceptionWithDuplication()
    {
        $this->clearEnv();

        $immutableLoader = new Loader(dirname(__DIR__) . '/fixtures/valid/duplicates_nested.yml', true);
        $immutableLoader->load();
    }

    public function testItCanReturnAnAssociativeArray()
    {
        $expected = [
            'ARRAY_ONE' => 1,
            'ARRAY_TWO' => 2,
        ];

        $this->mutableLoader->load();

        $actual = $this->mutableLoader->getYamlValue('NESTED');

        $this->assertEquals($expected, $actual);
    }

    public function testItCanReturnAnAssociativeArrayFromADeepLevel()
    {
        $expected = [
            'ARRAY_ONE' => 1,
            'ARRAY_TWO' => 2,
        ];

        $this->mutableLoader->load();

        $actual = $this->mutableLoader->getYamlValue('MULTI.LEVEL.NESTED');

        $this->assertEquals($expected, $actual);
    }

    /**
     * Returns the key from keyVal(), without reset.
     *
     * @return string
     */
    private function key()
    {
        $keyVal = $this->keyVal();

        return key($keyVal);
    }

    /**
     * Generates a new key/value pair or returns the previous one.
     *
     * Since most of our functionality revolves around setting/retrieving keys
     * and values, we have this utility function to help generate new, unique
     * key/value pairs.
     *
     * @param bool $reset
     *                    If true, a new pair will be generated. If false, the last returned pair
     *                    will be returned.
     *
     * @return array
     */
    private function keyVal($reset = false)
    {
        if (!isset($this->keyVal) || $reset) {
            $this->keyVal = [uniqid() => uniqid()];
        }

        return $this->keyVal;
    }

    /**
     * Returns the value from keyVal(), without reset.
     *
     * @return string
     */
    private function value()
    {
        $keyVal = $this->keyVal();

        return reset($keyVal);
    }

    /**
     * Clear all env vars.
     */
    private function clearEnv()
    {
        foreach ($_ENV as $key => $value) {
            $this->clearEnvironmentVariable($key);
        }
    }

    /**
     * @param $name
     */
    private function clearEnvironmentVariable($name)
    {
        if (function_exists('putenv')) {
            putenv($name);
        }

        unset($_ENV[$name], $_SERVER[$name]);
    }
}
