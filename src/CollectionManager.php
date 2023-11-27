<?php

namespace TSterker\SolariumCollectionManager;

use InvalidArgumentException;
use Solarium\Client;
use Solarium\Core\Client\State\CollectionState;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Server\Collections\Result\ClusterStatusResult;
use TypeError;

class CollectionManager implements CollectionManagerInterface
{
    /** @var Client */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get status of given collection or all collections.
     *
     * @param string $name
     * @return ResultInterface|ClusterStatusResult
     */
    public function status(string $name = null): ResultInterface
    {
        $q = $this->client->createCollections();

        $action = $q->createClusterStatus();

        if ($name !== null) {
            $action->setCollection($name);
        }

        $q->setAction($action);

        return $this->client->collections($q);
    }

    /**
     * Get all collection names
     *
     * @return CollectionState[]
     */
    public function getCollections(): array
    {
        /** @var ClusterStatusResult $status */
        $status = $this->status();

        $collections = [];

        try {
            /** @var CollectionState[] $collections */
            $collections = $status->getClusterState()->getCollections();
        } catch (TypeError $e) {
            // TODO: https://github.com/solariumphp/solarium/pull/869
        }

        return $collections;
    }

    public function hasCollection(string $name): bool
    {
        /** @var ClusterStatusResult $status */
        $status = $this->status();

        return $status->getClusterState()->collectionExists($name);
    }

    public function ensureCollection(string $name): void
    {
        if (!$this->hasCollection($name)) {
            $this->create($name);  // TODO: Could fail, as somebody else could have created collection in the meantime
        }
    }

    /**
     * Create collection
     *
     * @param string $name
     * @param array<string, mixed> $options
     * @return ResultInterface|ClusterStatusResult
     */
    public function create(string $name, array $options = []): ResultInterface
    {
        $defaults = [
            'num_shards' => 1,
            'router_name' => 'compositeId',
            'nrt_replicas' => 1,  // alias: replication_factor
            'tlog_replicas' => 0,
            'pull_replicas' => 0,

            // NOTE: maxShardsPerNode has been removed in Solr 9.0
            // @see https://solr.apache.org/guide/solr/latest/upgrade-notes/major-changes-in-solr-9.html
            // 'max_shards_per_node' => 1,
        ];

        $unknownKeys = array_diff_key($options, $defaults);

        if (count($unknownKeys) > 0) {
            throw new InvalidArgumentException("Unknown options: " . json_encode(array_keys($unknownKeys)));
        }

        $options = array_merge($defaults, $options);

        $q = $this->client->createCollections();

        $action = $q->createCreate()
            ->setNumShards($options['num_shards'])
            ->setRouterName($options['router_name'])

            ->setNrtReplicas($options['nrt_replicas'] ?? $options['replication_factor'])
            ->setPullReplicas($options['pull_replicas'])
            ->setTlogReplicas($options['tlog_replicas'])

            ->setName($name);


        $q->setAction($action);

        return $this->client->collections($q);
    }

    /**
     * Alias collection
     *
     * @var string $collection
     * @var string $alias
     * @return mixed[] JSON response
     */
    public function alias(string $collection, string $alias): array
    {
        return $this->rawApiRequest("admin/collections?action=CREATEALIAS&name={$alias}&collections={$collection}");
    }

    /**
     * Delete Alias collection
     *
     * @var string $alias
     * @return mixed[] JSON response
     */
    public function deleteAlias(string $alias): array
    {
        return $this->rawApiRequest("admin/collections?action=DELETEALIAS&name={$alias}");
    }

    public function hasAlias(string $alias): bool
    {
        return in_array($alias, $this->getAliases());
    }

    /**
     * List all aliases
     *
     * @return string[] alias names
     */
    public function getAliases(): array
    {
        return array_keys($this->getAliasMappings());
    }

    /**
     * List all aliases with collection mappings
     *
     * @return string[] alias names
     */
    public function getAliasMappings(): array
    {
        return $this->rawApiRequest("admin/collections?action=LISTALIASES")['aliases'];
    }

    /**
     * Get collection for given alias or null
     *
     * @var string $alias
     * @return null|string
     */
    public function getAliasedCollection(string $alias): ?string
    {
        return $this->getAliasMappings()[$alias] ?? null;
    }

    /**
     * Delete collection
     *
     * @var string $name
     * @return ResultInterface|ClusterStatusResult
     */
    public function delete(string $name): ResultInterface
    {
        $q = $this->client->createCollections();

        $action = $q->createDelete()->setName($name);

        $q->setAction($action);

        return $this->client->collections($q);
    }

    /**
     * @param string $path e.g. admin/collections?action=CREATEALIAS&name=alias1&collections=collection1";
     * @return mixed[] JSON response
     */
    protected function rawApiRequest(string $path): array
    {
        $endpoint = $this->client->getEndpoint();

        $scheme = $endpoint->getScheme();
        $host = $endpoint->getHost();
        $port = $endpoint->getPort();

        $base_uri = "{$scheme}://{$host}:{$port}/solr";

        $url = "{$base_uri}/{$path}";
        $res = file_get_contents($url);

        assert(false !== $res, "Request failed: $url");

        $json = json_decode($res, true);

        assert(false !== $json && null !== $json, "Response could not be parsed as json: $res");

        return $json;
    }
}
