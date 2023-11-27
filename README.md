
tsterker/solarium-collection-manager
----

Helper to manage Solr collections via Solarium


# Development

```sh
composer install

# Single node setup:
docker-compose up -d --wait

# OR multi-node setup:
docker-compose -f docker-compose-multinode.yml up -d --wait

# Wait for Solr to start up...

./vendor/bin/phpunit
```
