<?php

namespace TSterker\SolariumCollectionManager;

use Solarium\Core\Client\State\CollectionState;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Server\Collections\Result\ClusterStatusResult;

interface CollectionManagerInterface
{
    /**
     * Get status of given collection or all collections.
     *
     * @param string $name
     * @return ResultInterface|ClusterStatusResult
     */
    public function status(string $name = null): ResultInterface;

    /**
     * Get all collection names
     *
     * @return CollectionState[]
     */
    public function getCollections(): array;

    public function hasCollection(string $name): bool;

    public function ensureCollection(string $name): void;

    /**
     * Create collection
     *
     * @param string $name
     * @param array<string, mixed> $options
     * @return ResultInterface|ClusterStatusResult
     */
    public function create(string $name, array $options = []): ResultInterface;

    /**
     * Alias collection
     *
     * @param string $collection
     * @param string $alias
     * @return mixed[] JSON response
     */
    public function alias(string $collection, string $alias): array;

    /**
     * Delete Alias collection
     *
     * @param string $alias
     * @return mixed[] JSON response
     */
    public function deleteAlias(string $alias): array;

    public function hasAlias(string $alias): bool;

    /**
     * List all aliases
     *
     * @return string[] alias names
     */
    public function getAliases(): array;

    /**
     * List all aliases with collection mappings
     *
     * @return string[] alias names
     */
    public function getAliasMappings(): array;

    /**
     * Get collection for given alias or null
     *
     * @param string $alias
     * @return null|string
     */
    public function getAliasedCollection(string $alias): ?string;

    /**
     * Delete collection
     *
     * @param string $name
     * @return ResultInterface|ClusterStatusResult
     */
    public function delete(string $name): ResultInterface;
}
