<?php

namespace TSterker\SolariumCollectionManager\Tests\Integration;

use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;
use TSterker\SolariumCollectionManager\CollectionManager;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var CollectionManager */
    protected $manager;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->getManager();
    }

    /** @before */
    public function refreshSolr()
    {
        $manager = $this->getManager();

        foreach ($manager->getAliases() as $alias) {
            $manager->deleteAlias($alias);
        }

        foreach ($manager->getCollections() as $collection) {
            $manager->delete($collection->getName());
        }
    }

    protected function getManager(): CollectionManager
    {
        $adapter = new Curl;
        $adapter->setTimeout((int) SOLR_TIMEOUT);

        $eventDispatcher = new EventDispatcher;

        $config = [
            'endpoint' => [
                'default' => [
                    // see tests/config.php
                    'host' => SOLR_HOST,
                    'port' => SOLR_PORT,
                    'path' => SOLR_PATH,
                ]
            ],
        ];

        $client = new Client($adapter, $eventDispatcher, $config);

        return new CollectionManager($client);
    }
}
