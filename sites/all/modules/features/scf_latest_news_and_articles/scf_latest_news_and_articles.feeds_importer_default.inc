<?php
/**
 * @file
 * scf_latest_news_and_articles.feeds_importer_default.inc
 */

/**
 * Implements hook_feeds_importer_default().
 */
function scf_latest_news_and_articles_feeds_importer_default() {
  $export = array();

  $feeds_importer = new stdClass();
  $feeds_importer->disabled = FALSE; /* Edit this to true to make a default feeds_importer disabled initially */
  $feeds_importer->api_version = 1;
  $feeds_importer->id = 'harvard_business_review';
  $feeds_importer->config = array(
    'name' => 'Harvard Business Review',
    'description' => 'HBR feed to import latest articles.',
    'fetcher' => array(
      'plugin_key' => 'FeedsHTTPFetcher',
      'config' => array(
        'auto_detect_feeds' => FALSE,
        'use_pubsubhubbub' => FALSE,
        'designated_hub' => '',
        'request_timeout' => NULL,
        'auto_scheme' => 'http',
        'accept_invalid_cert' => FALSE,
        'cache_http_result' => TRUE,
      ),
    ),
    'parser' => array(
      'plugin_key' => 'FeedsXPathParserHTML',
      'config' => array(
        'sources' => array(),
        'rawXML' => array(),
        'context' => '',
        'exp' => array(
          'errors' => FALSE,
          'tidy' => FALSE,
          'debug' => array(),
          'tidy_encoding' => 'UTF8',
        ),
        'allow_override' => TRUE,
      ),
    ),
    'processor' => array(
      'plugin_key' => 'FeedsNodeProcessor',
      'config' => array(
        'expire' => '-1',
        'author' => 0,
        'authorize' => 1,
        'mappings' => array(
          0 => array(
            'source' => 'xpathparser:0',
            'target' => 'title',
            'unique' => FALSE,
            'language' => 'und',
          ),
          1 => array(
            'source' => 'xpathparser:1',
            'target' => 'body',
            'unique' => FALSE,
            'language' => 'und',
          ),
          2 => array(
            'source' => 'xpathparser:2',
            'target' => 'field_feed_image_link:url',
            'unique' => FALSE,
            'language' => 'und',
          ),
          3 => array(
            'source' => 'xpathparser:3',
            'target' => 'field_feed_api_publish_date_',
            'unique' => FALSE,
            'language' => 'und',
          ),
          4 => array(
            'source' => 'xpathparser:4',
            'target' => 'field_feed_url_link:url',
            'unique' => FALSE,
            'language' => 'und',
          ),
          5 => array(
            'source' => 'Blank source 1',
            'target' => 'field_feed_news_source',
            'term_search' => '1',
            'autocreate' => 0,
            'language' => 'und',
          ),
          6 => array(
            'source' => 'Blank source 2',
            'target' => 'status',
            'unique' => FALSE,
          ),
        ),
        'insert_new' => '1',
        'update_existing' => '0',
        'update_non_existent' => 'skip',
        'input_format' => 'plain_text',
        'skip_hash_check' => 0,
        'bundle' => 'news_and_articles',
        'language' => 'und',
      ),
    ),
    'content_type' => '',
    'update' => 0,
    'import_period' => '1800',
    'expire_period' => 3600,
    'import_on_create' => 1,
    'process_in_background' => 0,
  );
  $export['harvard_business_review'] = $feeds_importer;

  $feeds_importer = new stdClass();
  $feeds_importer->disabled = FALSE; /* Edit this to true to make a default feeds_importer disabled initially */
  $feeds_importer->api_version = 1;
  $feeds_importer->id = 'mck_insights_news';
  $feeds_importer->config = array(
    'name' => 'Mck Insights News',
    'description' => 'Mck Insights News for latest articles',
    'fetcher' => array(
      'plugin_key' => 'apiexchange',
      'config' => array(
        'api' => 'news-api',
        'request_timeout' => '300',
      ),
    ),
    'parser' => array(
      'plugin_key' => 'FeedsJSONPathParser',
      'config' => array(
        'context' => '',
        'sources' => array(),
        'debug' => array(),
        'allow_override' => FALSE,
        'convert_four_byte' => FALSE,
      ),
    ),
    'processor' => array(
      'plugin_key' => 'FeedsNodeProcessor',
      'config' => array(
        'expire' => '-1',
        'author' => 0,
        'authorize' => 1,
        'mappings' => array(
          0 => array(
            'source' => 'jsonpath_parser:0',
            'target' => 'title',
            'unique' => FALSE,
            'language' => 'und',
          ),
          1 => array(
            'source' => 'jsonpath_parser:1',
            'target' => 'body',
            'unique' => FALSE,
            'language' => 'und',
          ),
          2 => array(
            'source' => 'jsonpath_parser:2',
            'target' => 'field_feed_url_link:url',
            'unique' => FALSE,
            'language' => 'und',
          ),
          3 => array(
            'source' => 'jsonpath_parser:3',
            'target' => 'field_feed_image_link:url',
            'unique' => FALSE,
            'language' => 'und',
          ),
          4 => array(
            'source' => 'jsonpath_parser:4',
            'target' => 'field_feed_api_publish_date_',
            'unique' => FALSE,
            'language' => 'und',
          ),
          5 => array(
            'source' => 'Blank source 1',
            'target' => 'field_feed_news_source',
            'unique' => FALSE,
            'language' => 'und',
          ),
          6 => array(
            'source' => 'Blank source 2',
            'target' => 'status',
            'unique' => FALSE,
          ),
        ),
        'insert_new' => '1',
        'update_existing' => '0',
        'update_non_existent' => 'skip',
        'input_format' => 'plain_text',
        'skip_hash_check' => 0,
        'bundle' => 'news_and_articles',
        'language' => 'und',
      ),
    ),
    'content_type' => '',
    'update' => 0,
    'import_period' => 1800,
    'expire_period' => 3600,
    'import_on_create' => TRUE,
    'process_in_background' => FALSE,
  );
  $export['mck_insights_news'] = $feeds_importer;

  return $export;
}
