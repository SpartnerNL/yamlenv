<?php

use Yamlenv\Loader;
use Yamlenv\Validator;
use Yamlenv\Yamlenv;

class YamlenvTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $fixturesFolder;

    public function setUp()
    {
        $this->fixturesFolder = dirname(__DIR__) . '/fixtures/valid';
    }

    /**
     * @expectedException \Yamlenv\Exception\InvalidPathException
     * @expectedExceptionMessage Unable to read the environment file at
     */
    public function testYamlenvThrowsExceptionIfUnableToLoadFile()
    {
        $yamlenv = new Yamlenv(__DIR__);
        $yamlenv->load();
    }

    public function testYamlenvLoadsEnvironmentVars()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $this->assertSame('bar', getenv('FOO'));
        $this->assertSame('baz', getenv('BAR'));
        $this->assertSame('with spaces', getenv('SPACED'));
        $this->assertEmpty(getenv('EMPTY'));
    }

    public function testCommentedYamlenvLoadsEnvironmentVars()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'commented.yaml');
        $yamlenv->load();
        $this->assertSame('bar', getenv('CFOO'));
        $this->assertFalse(getenv('CBAR'));
        $this->assertFalse(getenv('CZOO'));
        $this->assertSame('with spaces', getenv('CSPACED'));
        $this->assertSame('a value with a # character', getenv('CQUOTES'));
        $this->assertSame('a value with a # character & a quote " character inside quotes', getenv('CQUOTESWITHQUOTE'));
        $this->assertEmpty(getenv('CNULL'));
    }

    public function testQuotedYamlenvLoadsEnvironmentVars()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'quoted.yaml');
        $yamlenv->load();
        $this->assertSame('bar', getenv('QFOO'));
        $this->assertSame('baz', getenv('QBAR'));
        $this->assertSame('with spaces', getenv('QSPACED'));
        $this->assertEmpty(getenv('QNULL'));
        $this->assertSame('pgsql:host=localhost;dbname=test', getenv('QEQUALS'));
        $this->assertSame('test some escaped characters like a quote (") or maybe a backslash (\\)', getenv('QESCAPED'));
    }

    /**
     * @expectedException \Yamlenv\Exception\InvalidFileException
     * @expectedExceptionMessage Input file does not contain valid Yaml
     */
    public function testSpacedValuesWithoutQuotesThrowsException()
    {
        $yamlenv = new Yamlenv(dirname(__DIR__) . '/fixtures/invalid', 'invalid.yaml');
        $yamlenv->load();
    }

    public function testYamlenvLoadsEnvGlobals()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $this->assertSame('bar', $_SERVER['FOO']);
        $this->assertSame('baz', $_SERVER['BAR']);
        $this->assertSame('with spaces', $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['EMPTY']);
    }

    public function testYamlenvLoadsServerGlobals()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $this->assertSame('bar', $_ENV['FOO']);
        $this->assertSame('baz', $_ENV['BAR']);
        $this->assertSame('with spaces', $_ENV['SPACED']);
        $this->assertEmpty($_ENV['EMPTY']);
    }

    /**
     * @depends testYamlenvLoadsEnvironmentVars
     * @depends testYamlenvLoadsEnvGlobals
     * @depends testYamlenvLoadsServerGlobals
     */
    public function testYamlenvRequiredStringEnvironmentVars()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $validator = $yamlenv->required('FOO');

        $this->assertInstanceOf(Validator::class, $validator);
    }

    /**
     * @depends testYamlenvLoadsEnvironmentVars
     * @depends testYamlenvLoadsEnvGlobals
     * @depends testYamlenvLoadsServerGlobals
     */
    public function testYamlenvRequiredArrayEnvironmentVars()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $validator = $yamlenv->required(['FOO', 'BAR']);
-
        $this->assertInstanceOf(Validator::class, $validator);
    }

    /**
     * @depends testYamlenvLoadsEnvironmentVars
     * @depends testYamlenvLoadsEnvGlobals
     * @depends testYamlenvLoadsServerGlobals
     *
     * @expectedException \Yamlenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOO is not an integer.
     */
    public function testYamlenvRequiredIntegerEnvironmentVar()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();

        $yamlenv->required(['FOO'])->isInteger();
    }

    public function testYamlenvNestedEnvironmentVars()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'nested.yaml');
        $yamlenv->load();

        $this->assertSame('Hello', $_ENV['NVAR1']);
        $this->assertSame('World!', $_ENV['NVAR2']);
        $this->assertSame('Nested 1', $_ENV['NVAR3_NVAR4']);
        $this->assertSame('Nested 2', $_ENV['NVAR3_NVAR5_NVAR6']);
    }

    /**
     * @depends testYamlenvLoadsEnvironmentVars
     * @depends testYamlenvLoadsEnvGlobals
     * @depends testYamlenvLoadsServerGlobals
     */
    public function testYamlenvAllowedValues()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $validator = $yamlenv->required('FOO')->allowedValues(['bar', 'baz']);

        $this->assertInstanceOf(Validator::class, $validator);
    }

    /**
     * @depends testYamlenvLoadsEnvironmentVars
     * @depends testYamlenvLoadsEnvGlobals
     * @depends testYamlenvLoadsServerGlobals
     *
     * @expectedException \Yamlenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOO is not an allowed value.
     */
    public function testYamlenvProhibitedValues()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $yamlenv->required('FOO')->allowedValues(['buzz']);
    }

    /**
     * @expectedException \Yamlenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOOX is missing, NOPE is missing.
     */
    public function testYamlenvRequiredThrowsRuntimeException()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $this->assertFalse(getenv('FOOX'));
        $this->assertFalse(getenv('NOPE'));
        $yamlenv->required(['FOOX', 'NOPE']);
    }

    public function testYamlenvNullFileArgumentUsesDefault()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, null);
        $yamlenv->load();
        $this->assertSame('bar', getenv('FOO'));
    }

    /**
     * The fixture data has whitespace between the key and in the value string.
     *
     * Test that these keys are trimmed down.
     */
    public function testYamlenvTrimmedKeys()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'quoted.yaml');
        $yamlenv->load();
        $this->assertSame('no space', getenv('QWHITESPACE'));
    }

    public function testYamlenvLoadDoesNotOverwriteEnv()
    {
        $this->clearEnv();

        putenv('IMMUTABLE=true');
        $yamlenv = new Yamlenv($this->fixturesFolder, 'immutable.yaml');
        $yamlenv->load();

        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testYamlenvLoadAfterOverload()
    {
        $this->clearEnv();

        putenv('IMMUTABLE=true');
        $yamlenv = new Yamlenv($this->fixturesFolder, 'immutable.yaml');
        $yamlenv->overload();
        $this->assertSame('false', getenv('IMMUTABLE'));

        putenv('IMMUTABLE=true');
        $yamlenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testYamlenvOverloadAfterLoad()
    {
        $this->clearEnv();

        putenv('IMMUTABLE=true');
        $yamlenv = new Yamlenv($this->fixturesFolder, 'immutable.yaml');
        $yamlenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));

        putenv('IMMUTABLE=true');
        $yamlenv->overload();
        $this->assertSame('false', getenv('IMMUTABLE'));
    }

    public function testYamlenvOverloadDoesOverwriteEnv()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'mutable.yaml');
        $yamlenv->overload();
        $this->assertSame('true', getenv('MUTABLE'));
    }

    public function testYamlenvAllowsSpecialCharacters()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'specialchars.yaml');
        $yamlenv->load();
        $this->assertSame('$a6^C7k%zs+e^.jvjXk', getenv('SPVAR1'));
        $this->assertSame('?BUty3koaV3%GA*hMAwH}B', getenv('SPVAR2'));
        $this->assertSame('jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m$r', getenv('SPVAR3'));
        $this->assertSame('22222:22#2^{', getenv('SPVAR4'));
        $this->assertSame('test some escaped characters like a quote " or maybe a backslash \\', getenv('SPVAR5'));
    }

    public function testYamlenvConvertsToUppercase()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'lowercase.yaml', true);
        $yamlenv->load();

        $validator = $yamlenv->required([
            'LCVAR1',
            'LCVAR2',
            'LCVAR3',
        ])->notEmpty();

        $this->assertInstanceOf(Validator::class, $validator);
    }

    /**
     * @expectedException \Yamlenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: LCVAR1 is missing, LCVAR2 is missing, LCVAR3 is missing.
     */
    public function testYamlenvFailsIfNotConvertedToUppercase()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'lowercase.yaml', false);
        $yamlenv->load();

        $yamlenv->required([
            'LCVAR1',
            'LCVAR2',
            'LCVAR3',
        ]);
    }

    public function testYamlenvAssertions()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'assertions.yaml');
        $yamlenv->load();
        $this->assertSame('val1', getenv('ASSERTVAR1'));
        $this->assertEmpty(getenv('ASSERTVAR2'));
        $this->assertEmpty(getenv('ASSERTVAR3'));
        $this->assertSame('0', getenv('ASSERTVAR4'));

        $validator = $yamlenv->required([
            'ASSERTVAR1',
            'ASSERTVAR2',
            'ASSERTVAR3',
            'ASSERTVAR4',
        ]);

        $this->assertInstanceOf(Validator::class, $validator);

        $validator = $yamlenv->required([
            'ASSERTVAR1',
            'ASSERTVAR4',
        ])->notEmpty();

        $this->assertInstanceOf(Validator::class, $validator);

        $validator = $yamlenv->required([
            'ASSERTVAR1',
            'ASSERTVAR4',
        ])->notEmpty()->allowedValues(['0', 'val1']);

        $this->assertInstanceOf(Validator::class, $validator);
    }

    /**
     * @expectedException \Yamlenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR2 is empty.
     */
    public function testYamlenvEmptyThrowsRuntimeException()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'assertions.yaml');
        $yamlenv->load();
        $this->assertEmpty(getenv('ASSERTVAR2'));

        $yamlenv->required('ASSERTVAR2')->notEmpty();
    }

    /**
     * @expectedException \Yamlenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR3 is empty.
     */
    public function testYamlenvStringOfSpacesConsideredEmpty()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'assertions.yaml');
        $yamlenv->load();
        $this->assertEmpty(getenv('ASSERTVAR3'));

        $yamlenv->required('ASSERTVAR3')->notEmpty();
    }

    /**
     * @expectedException \Yamlenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR3 is empty.
     */
    public function testYamlenvHitsLastChain()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'assertions.yaml');
        $yamlenv->load();
        $yamlenv->required('ASSERTVAR3')->notEmpty();
    }

    /**
     * @expectedException \Yamlenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: foo is missing.
     */
    public function testYamlenvValidateRequiredWithoutLoading()
    {
        $this->clearEnv();

        $yamlenv = new Yamlenv($this->fixturesFolder, 'assertions.yaml');
        $yamlenv->required('foo');
    }

    public function testYamlenvRequiredCanBeUsedWithoutLoadingFile()
    {
        $this->clearEnv();

        putenv('REQUIRED_VAR=1');
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $validator = $yamlenv->required('REQUIRED_VAR')->notEmpty();

        $this->assertInstanceOf(Validator::class, $validator);
    }

    public function testGetLoaderGetsLoaderInstanceAfterLoad()
    {
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();

        $loader = $yamlenv->getLoader();

        $this->assertInstanceOf(Loader::class, $loader);
    }

    /**
     * @expectedException \Yamlenv\Exception\LoaderException
     * @expectedExceptionMessage Loader has not been initialized yet.
     */
    public function testGetLoaderGivesNullBeforeLoad()
    {
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->getLoader();
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
