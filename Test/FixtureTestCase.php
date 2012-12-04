<?php
/**
 * ODMFixtureAutoLoadTest file
 *
 * Copyright (c) 2012 1blick GmbH - https://1blick.de
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
 * OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author Thomas Ploch <t.ploch@reizwerk.com>
 * @author Christian Weyand <c.weyand@1blick.de>
 * @filesource
 * @since v0.1
 */
namespace Einblick\ODMFixturesTestCase\Test;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;

/**
 * This TestCase enables to load fixtures automagically if you pass an array of bundles.
 *
 * @package    ODMFixturesTestCase
 * @subpackage Test
 */
abstract class FixtureTestCase extends WebTestCase
{
    /**
     * The default options
     *
     * @var array
     */
    public $options = array(
        'document_manager'  => 'doctrine.odm.mongodb.document_manager',
        'default_directory' => '/DataFixtures/MongoDB'
    );

    /**
     * Add the Bundlenames from which to load the fixture
     *
     * @var array
     */
    public $fixtures = array();

    /**
     * @var array
     */
    private $processedFixtures = array();

    /**
     * Holds the document manager instance
     *
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     * @staticvar
     */
    public static $dm;

    /**
     * Autoloads the fixtures passed in the static array $fixtures
     *
     * @param array $options       The options
     * @param array $kernelOptions The options to pass to `WebTestCase::createKernel()`
     *
     * @throws \InvalidArgumentException
     */
    protected function loadFixtures(array $options = array(), array $kernelOptions = array())
    {
        $this->options = array_merge($this->options, $options);

        $this->processKernel($kernelOptions);
        $this->fetchDocumentManager();

        $loader = new ContainerAwareLoader(static::$kernel->getContainer());
        $paths  = $this->getPaths();

        foreach ($paths as $path) {
            $loader->loadFromDirectory($path);
        }

        $this->processedFixtures = $loader->getFixtures();
        if (!$this->processedFixtures) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths))
            );
        }

        $this->doExecute();
    }

    /**
     * Processes kernel options and boots the kernel
     *
     * @param array  $kernelOptions
     *
     * @return void
     */
    protected function processKernel($kernelOptions)
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }

        static::$kernel = static::createKernel($kernelOptions);
        static::$kernel->boot();
    }

    /**
     * Fetches the configured document manager
     *
     * @return void
     */
    protected function fetchDocumentManager()
    {
        static::$dm = static::$kernel
            ->getContainer()
            ->get($this->options['document_manager'])
        ;
    }

    /**
     * Collects the paths from passed in bundle names
     *
     * @return array
     */
    protected function getPaths()
    {
        $paths = array();

        foreach ($this->fixtures as $bundle) {
            $bundle = static::$kernel->getBundle($bundle, true);
            if (!$bundle) {
                continue;
            }
            $paths[] = $bundle->getPath().$this->options['default_directory'];
        }

        return $paths;
    }

    /**
     * Executes the fixtures
     *
     * @return void
     */
    protected function doExecute()
    {
        $purger   = new MongoDBPurger(static::$dm);
        $executor = new MongoDBExecutor(static::$dm, $purger);

        $executor->execute($this->processedFixtures, false);
    }

    /**
     * Purges the db and shuts down the kernel
     *
     * @return void
     */
    protected function tearDown()
    {
        $purger = new MongoDBPurger(static::$dm);
        $purger->purge();

        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
    }
}
