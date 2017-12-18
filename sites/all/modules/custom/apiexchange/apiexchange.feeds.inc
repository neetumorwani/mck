<?php


/**
 * @file
 * Home of the FeedsHTTPFetcher and related classes.
 */



/**
 * Result of apiExchangeResult::fetch().
 */
class apiExchangeResult extends FeedsFetcherResult {
  protected $api;
  protected $timeout;
  /**
   * Constructor.
   */
  public function __construct($key = NULL) {
    $api = apiexchange_load($key);
    $this->api = $api;
    parent::__construct('');
  }

  /**
   * Overrides FeedsFetcherResult::getRaw();
   */
  public function getRaw() {
    $api = $this->api;
    $result = apiexchange($api,  array(), TRUE);
    // encode it again
    $result = json_encode($result);
    // if (!in_array($result->code, array(200, 201, 202, 203, 204, 205, 206))) {
    //   throw new Exception(t('Download of @api failed with code !code.', array('@api' => $this->api['key'], '!code' => $result->code)));
    // }
    return $this->sanitizeRaw($result);
  }

  public function getTimeout() {
    return $this->timeout;
  }

  public function setTimeout($timeout) {
    $this->timeout = $timeout;
  }
}

/**
 * Fetches data via Api exchange.
 */
class apiExchange extends FeedsFetcher {

  /**
   * Implements FeedsFetcher::fetch().
   */
  public function fetch(FeedsSource $source) {
    $fetcher_result = new apiExchangeResult($this->config['api']);
    // When request_timeout is empty, the global value is used.
    $fetcher_result->setTimeout($this->config['request_timeout']);
    return $fetcher_result;
  }

  /**
   * Clear caches.
   */
  public function clear(FeedsSource $source) {
    //TODO: clear cache.   ss
  }

  /**
   * Implements FeedsFetcher::request().
   */
  public function request($feed_nid = 0) {
     //TODO: Request code
  }

  /**
   * Override parent::configDefaults().
   */
  public function configDefaults() {
    return array(
      'api' => FALSE,
      'request_timeout' => NULL,
    );
  }

  /**
   * Override parent::configForm().
   */
  public function configForm(&$form_state) {
    $form = array();
    $apis_data = apiexchange_load_all();
    $apis = array () ;
    foreach ($apis_data as $api) {
      $apis[$api['id']] = $api['name'];
    }

    $form['api'] = array(
      '#type' => 'select',
      '#title' => t('Mckinsey API Exchange'),
      '#options' => $apis,
      '#description' => t('Mckinsey API Exchange '),
      '#default_value' => $this->config['api'],
    );

   // Per importer override of global http request timeout setting.
   $form['request_timeout'] = array(
     '#type' => 'textfield',
     '#title' => t('Request timeout'),
     '#description' => t('Timeout in seconds to wait for an HTTP get request to finish.</br>' .
                         '<b>Note:</b> this setting will override the global setting.</br>' .
                         'When left empty, the global value is used.'),
     '#default_value' => $this->config['request_timeout'],
     '#element_validate' => array('element_validate_integer_positive'),
     '#maxlength' => 3,
     '#size'=> 30,
   );
    return $form;
  }
}
