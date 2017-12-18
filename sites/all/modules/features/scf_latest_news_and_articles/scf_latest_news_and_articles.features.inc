<?php
/**
 * @file
 * scf_latest_news_and_articles.features.inc
 */

/**
 * Implements hook_ctools_plugin_api().
 */
function scf_latest_news_and_articles_ctools_plugin_api($module = NULL, $api = NULL) {
  if ($module == "feeds" && $api == "feeds_importer_default") {
    return array("version" => "1");
  }
  if ($module == "feeds_tamper" && $api == "feeds_tamper_default") {
    return array("version" => "2");
  }
  if ($module == "strongarm" && $api == "strongarm") {
    return array("version" => "1");
  }
}

/**
 * Implements hook_node_info().
 */
function scf_latest_news_and_articles_node_info() {
  $items = array(
    'news_and_articles' => array(
      'name' => t('News and Articles'),
      'base' => 'node_content',
      'description' => t('News and articles content type for fetching data from feed url.'),
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
  );
  drupal_alter('node_info', $items);
  return $items;
}
