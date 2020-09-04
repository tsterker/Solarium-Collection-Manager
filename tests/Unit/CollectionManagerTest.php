<?php

namespace TSterker\SolariumCollectionManager\Tests\Unit;

use Mockery;
use Mockery\MockInterface;
use Solarium\Client;
use Solarium\Core\Query\AbstractQuery;
use Solarium\QueryType\Server\Collections\Query\Action\Create;
use Solarium\QueryType\Server\Collections\Query\Query;
use Solarium\QueryType\Server\Collections\Result\ClusterStatusResult;
use Solarium\QueryType\Server\Query\Action\AbstractAction;
use TSterker\SolariumCollectionManager\CollectionManager;

class CollectionManagerTest extends TestCase
{

    /** @test */
    public function it_creates_non_replicated_single_shard_collection_by_name()
    {
        /** @var Client|MockInterface $client */
        $client = Mockery::mock(Client::class);

        /** @var Query|MockInterface $query */
        $query = Mockery::mock(Query::class);

        /** @var Create|MockInterface $createAction */
        $createAction = Mockery::mock(Create::class, function ($m) {
            $m->shouldReceive('setNumShards')->with(1)->andReturnSelf();
            $m->shouldReceive('setMaxShardsPerNode')->with(1)->andReturnSelf();
            $m->shouldReceive('setReplicationFactor')->with(1)->andReturnSelf();
            $m->shouldReceive('setAutoAddReplicas')->with(false)->andReturnSelf();
            $m->shouldReceive('setRouterName')->with('compositeId')->andReturnSelf();
            $m->shouldReceive('setName')->with('foo')->andReturnSelf();
        });

        $client->shouldReceive('createCollections')->andReturn($query);
        $query->shouldReceive('createCreate')->andReturn($createAction);
        $query->shouldReceive('setAction')->with($createAction);

        $client->shouldReceive('collections')->with($query)
            ->andReturn(Mockery::mock(ClusterStatusResult::class, ['getData' => ['dummy' => 'data']]));

        $manager = new CollectionManager($client);

        $data = $manager->create('foo');

        $this->assertEquals(['dummy' => 'data'], $data->getData());
    }
}
