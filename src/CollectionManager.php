<?php

namespace TSterker\SolariumCollectionManager;

use Solarium\Client;
use Solarium\Core\Client\State\CollectionState;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Server\Collections\Query\Query;
use Solarium\QueryType\Server\Collections\Result\ClusterStatusResult;
use Solarium\QueryType\Server\Query\Action\ActionInterface;
use TypeError;

class CollectionManager
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
     * @var string $name
     * @return ResultInterface|ClusterStatusResult
     */
    public function create(string $name): ResultInterface
    {
        $q = $this->client->createCollections();

        $action = $q->createCreate()
            ->setNumShards(1)  // important to make faceting work without thiking about it
            ->setMaxShardsPerNode(1)
            ->setReplicationFactor(1)
            ->setAutoAddReplicas(false)
            ->setRouterName('compositeId')
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
     * Delete Alias collection
     *
     * @var string $alias
     * @return mixed[] JSON response
     */
    public function getAliases(): array
    {
        return array_keys($this->rawApiRequest("admin/collections?action=LISTALIASES")['aliases']);
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
