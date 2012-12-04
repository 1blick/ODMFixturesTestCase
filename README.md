# ODMFixturesTestCase for Symfony2

[![Build Status](https://secure.travis-ci.org/1blick/ODMFixturesTestCase.png?branch=master)](https://travis-ci.org/1blick/ODMFixturesTestCase)

A TestCase base class (extending WebTestCase) that autoloads and purges ODM Fixtures from passed in bundle names.

    VERSION: Compatible for Symfony2 version >= 2.1.*


## Installation

### Composer

Add the following dependencies to your projects composer.json file:

    "require": {
        # ..
        "einblick/odm-fixtures-test-case": "dev-master"
        # ..
    }

## Documentation

### Simple usage example

```php
<?php
namespace My\Namespace\Tests;

/**
 * Import the FixtureTestCase class
 */
use Einblick\ODMFixturesTestCase\Test\FixtureTestCase;

/**
 * This test will load ODM Fixtures from Bundles
 */
class MyODMFixtureLoadingTest extends FixtureTestCase
{
    /**
     * Pass the bundles you want to load the fixtures from
     *
     * @var array
     */
    public $fixtures = array(
        'MySuperBundle',
        'AnotherSuperBundle'
    );

    /**
     * Use it!
     */
    protected function setUp()
    {
        $options = array(

           // The document manager's service id
           'document_manager'  => 'doctrine.odm.mongodb.document_manager', // default

           // The directory structure inside the bundles where to look for Fixtures
           'default_directory' => '/DataFixtures/MongoDB' // default

       );

        $kernelOptions = array(
            // Same options as to WebTestCase::createKernel()
        );

        $this->loadFixtures($options, $kernelOptions);
    }

    /**
     * Don't forget to tearDown parent in overridden tearDown methods
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
}
```
