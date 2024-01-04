
tsterker/solarium-collection-manager
----

Helper to manage Solr collections via Solarium


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
