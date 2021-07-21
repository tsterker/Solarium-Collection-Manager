<?php

namespace TSterker\SolariumCollectionManager\Tests\Unit;

use Mockery;
use Mockery\MockInterface;
use Solarium\Client;
use Solarium\QueryType\Server\Collections\Query\Action\Create;
use Solarium\QueryType\Server\Collections\Query\Query;
use Solarium\QueryType\Server\Collections\Result\ClusterStatusResult;
use Solarium\QueryType\Server\Query\Action\ActionInterface;
use TSterker\SolariumCollectionManager\CollectionManager;

class CollectionManagerTest extends TestCase
{
    /** @test */
    public function it_by_default_creates_non_replicated_single_shard_collection_by_name()
    {
        /** @var Create|MockInterface $createAction */
        $createAction = Mockery::mock(Create::class, function ($m) {
            $m->shouldReceive('setNumShards')->with(1)->andReturnSelf();
            $m->shouldReceive('setMaxShardsPerNode')->with(1)->andReturnSelf();
            $m->shouldReceive('setNrtReplicas')->with(1)->andReturnSelf();
            $m->shouldReceive('setPullReplicas')->with(0)->andReturnSelf();
            $m->shouldReceive('setTlogReplicas')->with(0)->andReturnSelf();
            $m->shouldReceive('setAutoAddReplicas')->with(false)->andReturnSelf();
            $m->shouldReceive('setRouterName')->with('compositeId')->andReturnSelf();
            $m->shouldReceive('setName')->with('foo')->andReturnSelf();
        });

        $client = ClientMockBuilder::new()
            ->expectCreateAction($createAction)
            ->get();
        $manager = new CollectionManager($client);

        $data = $manager->create('foo');

        $this->assertEquals(['dummy' => 'data'], $data->getData());
    }

    /** @test */
    public function it_accepts_options()
    {
        /** @var Create|MockInterface $createAction */
        $createAction = Mockery::mock(Create::class, function ($m) {
            $m->shouldReceive('setNumShards')->with(111)->andReturnSelf();
            $m->shouldReceive('setMaxShardsPerNode')->with(222)->andReturnSelf();
            $m->shouldReceive('setNrtReplicas')->with(333)->andReturnSelf();
            $m->shouldReceive('setTlogReplicas')->with(444)->andReturnSelf();
            $m->shouldReceive('setPullReplicas')->with(555)->andReturnSelf();
            $m->shouldReceive('setAutoAddReplicas')->with(true)->andReturnSelf();
            $m->shouldReceive('setRouterName')->with('xxx')->andReturnSelf();
            $m->shouldReceive('setName')->with('foo')->andReturnSelf();
        });

        $client = ClientMockBuilder::new()
            ->expectCreateAction($createAction)
            ->get();
        $manager = new CollectionManager($client);

        $data = $manager->create('foo', [
            'num_shards' => 111,
            'max_shards_per_node' => 222,
            'nrt_replicas' => 333,
            'tlog_replicas' => 444,
            'pull_replicas' => 555,
            'auto_add_replicas' => true,
            'router_name' => 'xxx',
        ]);

        $this->assertEquals(['dummy' => 'data'], $data->getData());
    }
}

class ClientMockBuilder
{
    /** @var Client|MockInterface */
    protected $client;

    /** @var Query|MockInterface */
    protected $query;

    public function __construct()
    {
        $this->client = Mockery::mock(Client::class);

        $this->query = Mockery::mock(Query::class);

        $this->client->shouldReceive('createCollections')->andReturn($this->query);

        $this->client->shouldReceive('collections')
            ->with($this->query)
            ->andReturn(Mockery::mock(
                ClusterStatusResult::class,
                ['getData' => ['dummy' => 'data']]
            ));
    }

    public static function new(): self
    {
        return new self;
    }

    public function expectCreateAction(ActionInterface $action): self
    {
        $this->query->shouldReceive('createCreate')->andReturn($action);
        $this->query->shouldReceive('setAction')->with($action);

        return $this;
    }

    public function get(): Client
    {
        return $this->client;
    }
}
