Maatwebsite\Yamlenv
==========

Reads env.yaml and makes a validated list of variables available as environment variables. 

This is package is based on [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv).

[![Build Status](https://travis-ci.org/Maatwebsite/yamlenv.svg?branch=master)](https://travis-ci.org/Maatwebsite/yamlenv.svg?branch=master)

Why choose Yaml over env.yaml?
---------
The benefits of using a env.yaml file or similar has been proven a long time ago. 
The popularity of [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) and 
similar packages is a testament of this. 
There is no reason why you shouldn't use a package like that in your projects, 
especially if the amount of variables you need to manage is relatively small.

In case of larger, enterprise scale, applications, the amount of environment 
settings might very quickly become unmanageable. If it's a multi tenant system een more so. And if you're supporting different versions of the application with it's own list of required environment settings, it soon becomes a necessity to automate this process. 

These are largely the reasons we decided to move towards Yaml. It provides 
a few simple advantages over env.yaml files:
* It allows nesting, to allow grouping of certain settings ( like database 
connection credentials ).
* It is easily readable by human as well as multiple automated deployment 
tools ( Ansible e.g. ).
* There are at least as many ( probably more ) trustworthy packages that work 
with Yaml ( Symfony being the most important one ) 

In our initial use case it was the combination of nesting and Ansible 
compatibility that made us decide to build this package.

Installation with Composer
--------------------------

```shell
composer require maatwebsite/yamlenv ~0.1
```

Usage
-----
The documentation below is, for a large part, the same as Vlucas/Dotenv. The filenames 
are changed of course and classnames. We tried to keep the package as close in usage 
to Dotenv as possible, for ease of use. So give credit to Vlucas for providing such a 
great base to work from! 

Of course there are a few things unique to Yamlenv, and that has been added as well. 

The `env.yaml` file is generally kept out of version control since it can contain
sensitive API keys and passwords. A separate `env.yaml.dist` file can be created
with all the required environment variables defined except for the sensitive
ones, which are either user-supplied for their own development environments or
are communicated elsewhere to project collaborators. The project collaborators
then independently copy the `env.yaml.dist` file to a local `env.yaml` and ensure
all the settings are correct for their local environment, filling in the secret
keys or providing their own values when necessary. In this usage, the `env.yaml`
file should be added to the project's `.gitignore` file so that it will never
be committed by collaborators.  This usage ensures that no sensitive passwords
or API keys will ever be in the version control history so there is less risk
of a security breach, and production values will never have to be shared with
all project collaborators.

Add your application configuration to a `env.yaml` file in the root of your
project. **Make sure the `env.yaml` file is added to your `.gitignore` so it is not
checked-in the code**

```shell
S3_BUCKET: "yamlenv"
SECRET_KEY: "secret_key"
```

Now create a file named `env.yaml.dist` and check this into the project. This
should have the ENV variables you need to have set, but the values should
either be blank or filled with dummy data. The idea is to let people know what
variables are required, but not give them the sensitive production values.

```shell
S3_BUCKET: "devbucket"
SECRET_KEY: "abc123"
```

You can then load `env.yaml` in your application with:

```php
$yamlenv = new Yamlenv\Yamlenv(__DIR__);
$yamlenv->load();
```

Optionally you can pass in a filename as the second parameter, if you would like to use something other than `env.yaml`

```php
$yamlenv = new Yamlenv\Yamlenv(__DIR__, 'myconfig');
$yamlenv->load();
```


All of the defined variables are now accessible with the `getenv`
method, and are available in the `$_ENV` and `$_SERVER` super-globals.

```php
$s3_bucket = getenv('S3_BUCKET');
$s3_bucket = $_ENV['S3_BUCKET'];
$s3_bucket = $_SERVER['S3_BUCKET'];
```

You should also be able to access them using your framework's Request
class (if you are using a framework).

```php
$s3_bucket = $request->env('S3_BUCKET');
$s3_bucket = $request->getEnv('S3_BUCKET');
$s3_bucket = $request->server->get('S3_BUCKET');
$s3_bucket = env('S3_BUCKET');
```

### Uppercase keys

With Yamlenv you can pass boolean true as the third contructor parameter. This will make sure all your keys will be cast to uppercase

```shell
s3_bucket: "will_be_uppercase"
```

```php
$yamlenv = new Yamlenv\Yamlenv(__DIR__, 'emv.yaml', true);
$yamlenv->load();
```

```php
$s3_bucket = getenv('S3_BUCKET'); // return will_be_uppercase
$s3_bucket = getenv('s3_bucket'); // returns null
```

### Nesting Variables

Like with Dotenv it's possible to nest an environment variable within another. However, because we 
are using the Yaml format, nesting us supported natively. 

```shell
DB:
  USER: username
  PASS: password
  HOST: localhost
```

These variables will be flattened into a single level before being added to the environment variables.
The different keys will be concatenated into a single key, separated with underscores. 
So the above example wil give the same results as below:

```shell
DB_USER: username
DB_PASS: password
DB_HOST: localhost
```

This also works with multiple levels:

```shell
FOO:
  BAR: 
    LOREM:
        IPSUM: multilevelfun
```

```shell
FOO_BAR_LOREM_IPSUM: multilevelfun
```

### Immutability

By default, Yamlenv will NOT overwrite existing environment variables that are
already set in the environment.

If you want Yamlenv to overwrite existing environment variables, use `overload`
instead of `load`:

```php
$yamlenv = new Yamlenv\Yamlenv(__DIR__);
$yamlenv->overload();
```

Requiring Variables to be Set
-----------------------------

Using Yamlenv, you can require specific ENV vars to be defined, and throw
an Exception if they are not. This is particularly useful to let people know
any explicit required variables that your app will not work without.

You can use a single string:

```php
$yamlenv->required('DATABASE_DSN');
```

Or an array of strings:

```php
$yamlenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);
```

If any ENV vars are missing, Yamlenv will throw a `RuntimeException` like this:

```
One or more environment variables failed assertions: DATABASE_DSN is missing
```

### Empty Variables

Beyond simply requiring a variable to be set, you might also need to ensure the
variable is not empty:

```php
$yamlenv->required('DATABASE_DSN')->notEmpty();
```

If the environment variable is empty, you'd get an Exception:

```
One or more environment variables failed assertions: DATABASE_DSN is empty
```

### Integer Variables

You might also need to ensure the the variable is of an integer value. You may do the following:

```php
$yamlenv->required('FOO')->isInteger();
```

If the environment variable is not an integer, you'd get an Exception:

```
One or more environment variables failed assertions: FOO is not an integer
```

### Allowed Values

It is also possible to define a set of values that your environment variable
should be. This is especially useful in situations where only a handful of
options or drivers are actually supported by your code:

```php
$yamlenv->required('SESSION_STORE')->allowedValues(['Filesystem', 'Memcached']);
```

If the environment variable wasn't in this list of allowed values, you'd get a
similar Exception:

```
One or more environment variables failed assertions: SESSION_STORE is not an
allowed value
```

### Comments

You can comment your `env.yaml` file using the `#` character. E.g.
This follows the normal Yaml syntax rules

```shell
# this is a comment
VAR: "value" # comment
VAR: value # comment
```

When nesting variables, it is important to always use key->value based children. 
While the below is valid Yaml, it does not result in usable variables.
```shell
FOO:
  - one
  - two
```

Correct would be:
```shell
FOO:
  BAR: one
  BAZ: two
```

Usage Notes
-----------

When a new developer clones your codebase, they will have an additional
**one-time step** to manually copy the `env.yaml.dist` file to `env.yaml` and fill-in
their own values (or get any sensitive values from a project co-worker).

Yamlenv is made for development environments, and generally should not be
used in production. In production, the actual environment variables should be
set so that there is no overhead of loading the `env.yaml` file on each request.
This can be achieved via an automated deployment process with tools like
Vagrant, chef, or Puppet, or can be set manually with cloud hosts like
Pagodabox and Heroku.

### Command Line Scripts

If you need to use environment variables that you have set in your `env.yaml` file
in a command line script that doesn't use the Yamlenv library, you can `source`
it into your local shell session:
