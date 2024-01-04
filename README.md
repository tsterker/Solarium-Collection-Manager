
tsterker/solarium-collection-manager
----

Manage Solr collections via [Solarium](https://github.com/solariumphp/solarium).

# Usage

Code shows a basic example on how to instantiate the `CollectionManager` and create a collection.

For more information:
- See the [`CollectionManagerInterface`](src/CollectionManagerInterface.php) for all available management methods.
- See the integration [`TestCase`](tests/Integration/TestCase.php) on how you could use the `CollectionManager` to clear your Solr collections/aliases for test setups.

**Example:**
```php
use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;
use TSterker\SolariumCollectionManager\CollectionManager;

// Create Solarium client (https://solarium.readthedocs.io/en/stable/client-and-adapters/)
$solariumClient = new Client(new Curl, new EventDispatcher, [
    'endpoint' => [
        'default' => ['host' => '127.0.0.1', 'port' => 8983, 'path' =>  '/']
    ],
]);

$collectionManager = new CollectionManager($client);

$collectionManager->hasCollection('foo');  // false
$collectionManager->create('foo', ['num_shards' => 2, 'nrt_replicas' => 2]);
$collectionManager->hasCollection('foo');  // true
```

**Configuration options during collection creation:**

| Option                 | Default       | Description                                                                                                                                            |
| ---------------------- | ------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `nrt_replicas`         | `1`           | The number of NRT (Near-Real-Time) replicas to create for this collection.                                                                             |
| `num_shards`           | `1`           | The number of shards to be created as part of the collection.                                                                                          |
| `pull_replicas`        | `0`           | The number of PULL replicas to create for this collection.                                                                                             |
| `router_name`          | `compositeId` | The router name that will be used. The router defines how documents will be distributed among the shards. Possible values are implicit or compositeId. |
| `tlog_replicas`        | `0`           | The number of TLOG replicas to create for this collection.                                                                                             |
| `wait_for_final_state` | `false`       | Whether the request should complete only when all affected replicas become active.                                                                     |

See the [Solr documentation](https://solr.apache.org/guide/solr/latest/deployment-guide/collection-management.html) for details.


# Development

```sh
# Fresh install
rm composer.lock && composer install

# Start multi-node Solr setup:
docker-compose up -d --wait

# Run tests
./vendor/bin/phpunit

# Lint code
./vendor/bin/phpstan analyse --memory-limit=2G
```
