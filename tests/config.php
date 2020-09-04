<?php

define('SOLR_HOST', getenv('TEST_SOLR_HOST') ? getenv('TEST_SOLR_HOST') : 'localhost');
define('SOLR_PORT', getenv('TEST_SOLR_PORT') ? getenv('TEST_SOLR_PORT') : '8983');
define('SOLR_PATH', getenv('TEST_SOLR_PATH') ? getenv('TEST_SOLR_PATH') : '/');
