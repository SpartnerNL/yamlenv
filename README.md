Maatwebsite\Yamlenv
==========

Reads env.yaml and makes a validated list of variables available as environment variables. 

This is package is based on [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv).

[![Build Status](https://travis-ci.org/Maatwebsite/yamlenv.svg?branch=master)](https://travis-ci.org/Maatwebsite/yamlenv.svg?branch=master)

Why choose Yaml over .env?
---------
The benefits of using a .env file or similar has been proven a long time ago. 
The popularity of [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) and 
similar packages is a testament of this. 
There is no reason why you shouldn't use a package like that in your projects, 
especially if the amount of variables you need to manage is relatively small.

In case of larger, enterprise scale, applications, the amount of environment 
settings might very quickly become unmanageable. If it's a multi tenant system een more so. And if you're supporting different versions of the application with it's own list of required environment settings, it soon becomes a necessity to automate this process. 

These are largely the reasons we decided to move towards Yaml. It provides 
a few simple advantages over .env files:
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
composer require maatwebsite/yamlenv
```

Usage
-----
For the same reason that the `.env` file is kept out if version control, we also leave the env.yaml file out of it.
A `env.yaml.dist` file is added to server as a template. Using that file we automatically generate the env.yaml, 
while the generator script will allow you to enter any missing variables dynamically. 

w.i.p.
