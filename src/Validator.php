<?php

namespace Yamlenv;

use Yamlenv\Exception\InvalidCallbackException;
use Yamlenv\Exception\ValidationException;

/**
 * This is the validator class.
 *
 * It's responsible for applying validations against a number of variables.
 */
class Validator
{
    /**
     * The variables to validate.
     *
     * @var array
     */
    protected $variables;

    /**
     * The loader instance.
     *
     * @var \Yamlenv\Loader
     */
    protected $loader;

    /**
     * Create a new validator instance.
     *
     * @param array           $variables
     * @param \Yamlenv\Loader $loader
     */
    public function __construct(array $variables, Loader $loader)
    {
        $this->variables = $variables;
        $this->loader    = $loader;

        $this->assertCallback(
            function ($value) {
                return $value !== null;
            },
            'is missing'
        );
    }

    /**
     * Assert that each variable is not empty.
     *
     * @return \Yamlenv\Validator
     */
    public function notEmpty()
    {
        return $this->assertCallback(
            function ($value) {
                return strlen(trim($value)) > 0;
            },
            'is empty'
        );
    }

    /**
     * Assert that each specified variable is an integer.
     *
     * @return \Yamlenv\Validator
     */
    public function isInteger()
    {
        return $this->assertCallback(
            function ($value) {
                return ctype_digit($value);
            },
            'is not an integer'
        );
    }

    /**
     * Assert that each variable is amongst the given choices.
     *
     * @param string[] $choices
     *
     * @return \Yamlenv\Validator
     */
    public function allowedValues(array $choices)
    {
        return $this->assertCallback(
            function ($value) use ($choices) {
                return in_array($value, $choices);
            },
            'is not an allowed value'
        );
    }

    /**
     * Assert that the callback returns true for each variable.
     *
     * @param callable $callback
     * @param string   $message
     *
     * @throws \Yamlenv\Exception\ValidationException
     *
     * @return \Yamlenv\Validator
     */
    private function assertCallback($callback, $message = 'failed callback assertion')
    {
        $variablesFailingAssertion = [];
        foreach ($this->variables as $variableName) {
            $variableValue = $this->loader->getEnvironmentVariable($variableName);
            if (call_user_func($callback, $variableValue) === false) {
                $variablesFailingAssertion[] = $variableName . " $message";
            }
        }

        if (count($variablesFailingAssertion) > 0) {
            throw new ValidationException(sprintf(
                'One or more environment variables failed assertions: %s.',
                implode(', ', $variablesFailingAssertion)
            ));
        }

        return $this;
    }
}
