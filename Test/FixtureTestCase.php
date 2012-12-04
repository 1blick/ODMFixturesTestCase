<?php
namespace Einblick\EinblickFixtureAutoLoadBundle\Test;

use Symfony\Component\Console\Output\ConsoleOutput;

use Doctrine\ODM\MongoDB\DocumentManager;

use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * This TestCase enables to load fixtures automagically if you pass an array of
 * bundles. Depends on the DoctrineDataFixturesBundle.
 */
abstract class FixtureTestCase extends WebTestCase
{
    /**
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
     * @staticvar
     */
    public $fixtures = array();

    /**
     * @var array
     */
    private $processedFixtures = array();

    /**
     * Holds the document manager instance
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
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
     * @param string $managerName
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
