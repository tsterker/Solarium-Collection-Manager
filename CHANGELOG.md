# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] (YYYY-mm-dd)

This release ensures that authentication configuration of the Solr client is properly utilized also for raw Solr HTTP requests.
We do this by now using Solr test setup that requires authentication and thus force all our integration tests to be properly authenticated to work.

- fix: Authentication credentials configured for an endpoint are not used for raw HTTP requests to Solr
- feat: Better error message in case of failed raw HTTP requests to Solr
- test: Move to [bitnami/solr](https://hub.docker.com/r/bitnami/solr) docker image for better authentication support
- ci: Change `docker-compose` to `docker compose` to fix failing job

## 2.0.0 (2024-01-04)

- feat: Support Solr Cloud collection configuration during creation (#3)
- build: Support for PHP 8 and upgrade dependencies (#4)
- test: Use multi-node docker-compose setup
- test: Configure Solr client timeout
- docs: Extend README documentation

## 1.0.0 (2024-01-04)

Tag first official release.

## 0.1.1 (2020-12-19)

### Added:
- Get collection for given alias
- Get mapping of aliases to collections

## 0.1.0 (2020-10-30)

### Added:
- Rudimentary alias support (using raw solr api)

## 0.0.1 (2020-09-06)

### Addedd:
- Create collections
- Delete collections
- List collections
- Get collection status
