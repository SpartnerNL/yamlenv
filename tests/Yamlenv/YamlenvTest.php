<?php

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
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $this->assertSame('bar', getenv('FOO'));
        $this->assertSame('baz', getenv('BAR'));
        $this->assertSame('with spaces', getenv('SPACED'));
        $this->assertEmpty(getenv('EMPTY'));
    }

    public function testCommentedYamlenvLoadsEnvironmentVars()
    {
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
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $this->assertSame('bar', $_SERVER['FOO']);
        $this->assertSame('baz', $_SERVER['BAR']);
        $this->assertSame('with spaces', $_SERVER['SPACED']);
        $this->assertEmpty($_SERVER['EMPTY']);
    }

    public function testYamlenvLoadsServerGlobals()
    {
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
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $yamlenv->required('FOO');
        $this->assertTrue(true); // anything wrong an exception will be thrown
    }

    /**
     * @depends testYamlenvLoadsEnvironmentVars
     * @depends testYamlenvLoadsEnvGlobals
     * @depends testYamlenvLoadsServerGlobals
     */
    public function testYamlenvRequiredArrayEnvironmentVars()
    {
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $yamlenv->required(['FOO', 'BAR']);
        $this->assertTrue(true); // anything wrong an exception will be thrown
    }

    public function testYamlenvNestedEnvironmentVars()
    {
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
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $yamlenv->required('FOO')->allowedValues(array('bar', 'baz'));
        $this->assertTrue(true); // anything wrong an exception will be thrown
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
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $yamlenv->required('FOO')->allowedValues(array('buzz'));
    }

    /**
     * @expectedException \Yamlenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: FOOX is missing, NOPE is missing.
     */
    public function testYamlenvRequiredThrowsRuntimeException()
    {
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->load();
        $this->assertFalse(getenv('FOOX'));
        $this->assertFalse(getenv('NOPE'));
        $yamlenv->required(array('FOOX', 'NOPE'));
    }

    public function testYamlenvNullFileArgumentUsesDefault()
    {
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
        $yamlenv = new Yamlenv($this->fixturesFolder, 'quoted.yaml');
        $yamlenv->load();
        $this->assertSame('no space', getenv('QWHITESPACE'));
    }

    public function testYamlenvLoadDoesNotOverwriteEnv()
    {
        putenv('IMMUTABLE=true');
        $yamlenv = new Yamlenv($this->fixturesFolder, 'immutable.yaml');
        $yamlenv->load();
        $this->assertSame('true', getenv('IMMUTABLE'));
    }

    public function testYamlenvLoadAfterOverload()
    {
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
        $yamlenv = new Yamlenv($this->fixturesFolder, 'mutable.yaml');
        $yamlenv->overload();
        $this->assertSame('true', getenv('MUTABLE'));
    }

    public function testYamlenvAllowsSpecialCharacters()
    {
        $yamlenv = new Yamlenv($this->fixturesFolder, 'specialchars.yaml');
        $yamlenv->load();
        $this->assertSame('$a6^C7k%zs+e^.jvjXk', getenv('SPVAR1'));
        $this->assertSame('?BUty3koaV3%GA*hMAwH}B', getenv('SPVAR2'));
        $this->assertSame('jdgEB4{QgEC]HL))&GcXxokB+wqoN+j>xkV7K?m$r', getenv('SPVAR3'));
        $this->assertSame('22222:22#2^{', getenv('SPVAR4'));
        $this->assertSame('test some escaped characters like a quote " or maybe a backslash \\', getenv('SPVAR5'));
    }

    public function testYamlenvAssertions()
    {
        $yamlenv = new Yamlenv($this->fixturesFolder, 'assertions.yaml');
        $yamlenv->load();
        $this->assertSame('val1', getenv('ASSERTVAR1'));
        $this->assertEmpty(getenv('ASSERTVAR2'));
        $this->assertEmpty(getenv('ASSERTVAR3'));
        $this->assertSame('0', getenv('ASSERTVAR4'));

        $yamlenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR2',
            'ASSERTVAR3',
            'ASSERTVAR4',
        ));

        $yamlenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR4',
        ))->notEmpty();

        $yamlenv->required(array(
            'ASSERTVAR1',
            'ASSERTVAR4',
        ))->notEmpty()->allowedValues(array('0', 'val1'));

        $this->assertTrue(true); // anything wrong an an exception will be thrown
    }

    /**
     * @expectedException \Yamlenv\Exception\ValidationException
     * @expectedExceptionMessage One or more environment variables failed assertions: ASSERTVAR2 is empty.
     */
    public function testYamlenvEmptyThrowsRuntimeException()
    {
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
        $yamlenv = new Yamlenv($this->fixturesFolder, 'assertions.yaml');
        $yamlenv->required('foo');
    }

    public function testYamlenvRequiredCanBeUsedWithoutLoadingFile()
    {
        putenv('REQUIRED_VAR=1');
        $yamlenv = new Yamlenv($this->fixturesFolder);
        $yamlenv->required('REQUIRED_VAR')->notEmpty();
        $this->assertTrue(true);
    }
}
