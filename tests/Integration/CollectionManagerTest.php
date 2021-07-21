<?php

namespace TSterker\SolariumCollectionManager\Tests\Integration;

use Solarium\QueryType\Server\Collections\Result\ClusterStatusResult;

class CollectionManagerTest extends TestCase
{

    /** @test */
    public function it_creates_non_replicated_collection_by_default()
    {
        $this->assertFalse($this->manager->hasCollection('foo'));

        $this->manager->create('foo');

        $this->assertTrue($this->manager->hasCollection('foo'));

        /** @var ClusterStatusResult */
        $status = $this->manager->status('foo');
        $fooState = $status->getClusterState()->getCollectionState('foo');

        $this->assertEquals('foo', $fooState->getName());
        $this->assertEquals('foo.AUTOCREATED', $fooState->getConfigName());
        $this->assertEquals('compositeId', $fooState->getRouterName());
        $this->assertCount(1, $fooState->getShards());
        $this->assertSame(1, $fooState->getMaxShardsPerNode());
        $this->assertSame(1, $fooState->getReplicationFactor());
        $this->assertSame('0', $fooState->getTlogReplicas());  // NOTE: Returned as string. Solarium Bug?

        // TODO: setAutoAddReplicas not respected in CollectionManager::create?
        $this->assertSame(true, $fooState->isAutoAddReplicas());
    }

    /** @test */
    public function it_creates_collection_with_options()
    {
        $this->manager->create('foo', [
            'num_shards' => 2,
            'max_shards_per_node' => 10,  // TODO: Use smaller number that forces multiple nodes to be used? But this would depend on test setup with multiple nodes to work.
            'nrt_replicas' => 2,  // alias: replication_factor
            'pull_replicas' => 1,
            'tlog_replicas' => 1,
            'auto_add_replicas' => true,
            'router_name' => 'compositeId',
        ]);

        $this->assertTrue($this->manager->hasCollection('foo'));

        /** @var ClusterStatusResult */
        $status = $this->manager->status('foo');

        $fooState = $status->getClusterState()->getCollectionState('foo');

        $this->assertEquals('compositeId', $fooState->getRouterName());
        $this->assertCount(2, $fooState->getShards());
        $this->assertCount(2, $fooState->getShardLeaders());
        $this->assertSame(10, $fooState->getMaxShardsPerNode());
        $this->assertSame(true, $fooState->isAutoAddReplicas());
        $this->assertSame(2, $fooState->getReplicationFactor());
        $this->assertSame('1', $fooState->getTlogReplicas());
        // TODO: How to get pull replica count?

        // $this->assertTrue(1 < count($fooState->getNodesBaseUris(), 'Expect at least 2 nodes to be configured');  // Depends on test setup
    }

    /** @test */
    public function it_throws_on_create_if_collection_already_exists()
    {
        $this->manager->create('foo');

        $this->expectException(\Solarium\Exception\HttpException::class);
        $this->expectExceptionMessage("collection already exists: foo");

        $this->manager->create('foo');
    }

    /** @test */
    public function it_ensures_collection_is_created_if_it_does_not_exist_already()
    {
        $this->assertFalse($this->manager->hasCollection('foo'));

        $this->manager->ensureCollection('foo');
        $this->assertTrue($this->manager->hasCollection('foo'));

        $this->manager->ensureCollection('foo');
        $this->assertTrue($this->manager->hasCollection('foo'));
    }

    /** @test */
    public function it_deletes_collections_by_name()
    {
        $this->manager->create('foo');
        $this->manager->create('bar');

        $this->assertTrue($this->manager->hasCollection('foo'));
        $this->assertTrue($this->manager->hasCollection('bar'));

        $this->manager->delete('foo');

        // Confirm only foo was deleted
        $this->assertFalse($this->manager->hasCollection('foo'));
        $this->assertTrue($this->manager->hasCollection('bar'));
    }

    /** @test */
    public function it_lists_all_collections()
    {
        $this->manager->create('foo');
        $this->manager->create('bar');

        $collectionNames = array_map(function ($c) {
            return $c->getName();
        }, $this->manager->getCollections());

        $this->assertCount(2, $collectionNames);
        $this->assertContains('foo', $collectionNames);
        $this->assertContains('bar', $collectionNames);
    }

    /**
     * @see https://github.com/solariumphp/solarium/pull/869
     * @test
     */
    public function it_gracefully_handles_TypeError_bug_in_case_of_no_collections()
    {
        $this->assertEquals([], $this->manager->getCollections());
    }

    /** @test */
    public function it_retrieves_status_for_all_collections_or_by_name()
    {
        $this->manager->create('foo');
        $this->manager->create('bar');

        // CASE: By name
        $status = $this->manager->status('foo');

        $collections = $status->getData()['cluster']['collections'];
        $this->assertCount(1, $collections);
        $this->assertArrayHasKey('foo', $collections);

        // CASE: All collections
        $status = $this->manager->status();

        $collections = $status->getData()['cluster']['collections'];
        $this->assertCount(2, $collections);
        $this->assertArrayHasKey('foo', $collections);
        $this->assertArrayHasKey('bar', $collections);
    }

    /** @test */
    public function it_aliases_collections()
    {
        $this->assertFalse($this->manager->hasAlias('foo-alias'));

        $this->manager->create('foo');
        $this->manager->alias('foo', 'foo-alias');

        $this->assertTrue($this->manager->hasAlias('foo-alias'));
        $this->assertEquals('foo', $this->manager->getAliasedCollection('foo-alias'));
    }

    /** @test */
    public function it_replaces_alias_if_exists()
    {
        $this->assertFalse($this->manager->hasAlias('foo-alias'));

        $this->manager->create('foo');
        $this->manager->create('bar');

        $this->manager->alias('foo', 'the-alias');
        $this->assertEquals('foo', $this->manager->getAliasedCollection('the-alias'));

        $this->manager->alias('bar', 'the-alias');
        $this->assertEquals('bar', $this->manager->getAliasedCollection('the-alias'));
    }

    /** @test */
    public function it_deletes_alias_if_exists()
    {
        $this->manager->create('foo');
        $this->manager->alias('foo', 'foo-alias');

        $this->assertTrue($this->manager->hasAlias('foo-alias'));

        $this->manager->deleteAlias('foo-alias');

        $this->assertFalse($this->manager->hasAlias('foo-alias'));

        $this->manager->deleteAlias('foo-alias');  // NOTE: Second call won't fail
    }

    /** @test */
    public function it_lists_aliases()
    {
        $this->manager->create('foo');
        $this->manager->alias('foo', 'foo-alias-1');
        $this->manager->alias('foo', 'foo-alias-2');

        $this->assertEquals(
            ['foo-alias-1', 'foo-alias-2'],
            $this->manager->getAliases(),
        );
    }

    /** @test */
    public function it_lists_alias_to_collection_mappings()
    {
        $this->manager->create('foo');
        $this->manager->create('bar');
        $this->manager->alias('foo', 'foo-alias-1');
        $this->manager->alias('foo', 'foo-alias-2');
        $this->manager->alias('bar', 'bar-alias');

        $this->assertEquals(
            [
                'foo-alias-1' => 'foo',
                'foo-alias-2' => 'foo',
                'bar-alias' => 'bar',
            ],
            $this->manager->getAliasMappings(),
        );
    }
}
