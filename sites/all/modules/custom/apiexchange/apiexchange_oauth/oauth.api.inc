<?php

/**
 * @file oauth.api.inc
 */

/**
 * Simple class for putting together a URL for the API call, and doing an HTTP request
 * for retrieving access token.
 * All public methods return $this and can be chained together, except the execute(),
 * which returns the response data.
 *
 * Possible and recommended improvement: implementing a caching logic.
 * See Last.FM module for a smart example:
 * http://drupalcode.org/project/lastfm.git/blob/refs/heads/7.x-2.x:/lastfm.api.inc
 */
class OauthAPIRequest {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->api_url = variable_get('apiexchange_oauth_url', '');
    $this->grant_type = variable_get('apiexchange_oauth_grant_type', '');
    $this->scope = variable_get('apiexchange_oauth_scope', '');
    $this->client_id = variable_get('apiexchange_oauth_client_id', '');
    $this->client_secret = variable_get('apiexchange_oauth_client_secret', '');
    $this->debug_mode = variable_get('apiexchange_oauth_debug_mode', EXPERTSEARCH_DEFAULT_DEBUG_MODE);
  }

  /**
   * Executes the request. Returns the response data.
   */
  public function execute() {
    $options = array();
    $options['absolute'] = TRUE;

    $arguments = array(
      'grant_type' => $this->grant_type,
      'scopes' => $this->scope,
      'client_id' => $this->client_id,
      'client_secret' => $this->client_secret,
    );

    $options['query'] = $arguments;
    $request_url = url($this->api_url, $options);

    if ($this->debug_mode) {
      drupal_set_message(t('Request URL: !url', array('!url' => $request_url)));
    }
    return $this->request($request_url);
  }

  /**
   * Actual HTTP request.
   */
  private function request($request_url) {
      
    $op = array();
    $op['method'] = 'POST';
    $op['timeout'] = '5';
    $op['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
    $response = (module_exists('chr')) ? chr_curl_http_request($request_url, $op) : drupal_http_request($request_url, $op);
    if ($response->error = 'request timed out' && $response->code == '-1') {
      watchdog('apiexchange_oauth', 'Request timed out !code received.', array('!code' => $response->code), WATCHDOG_ERROR);
      return FALSE;
    }

    if ($response->code != 200) {
      watchdog('apiexchange_oauth', 'HTTP error !code received.', array('!code' => $response->code), WATCHDOG_ERROR);
      return FALSE;
    }

    // Check if parsing is at all possible or not, actual parsing will happen
    // in ->parse().
    $data = json_decode($response->data);
    if (!is_object($data)) {
      watchdog('apiexchange_oauth', 'Did not receive valid API response (invalid JSON).', array(), WATCHDOG_ERROR);
      return FALSE;
    }
    if (isset($data->error)) {
      watchdog('apiexchange_oauth', 'Error !code received: %message', array('!code' => $data->error, '%message' => $data->message), WATCHDOG_ERROR);
      return FALSE;
    }
    return $this->parse($data);
  }
  /**
   * Parses the response data.
   */
  protected function parse($data) {
    if ($data->token_type == 'Bearer' || $data->token_type == 'BearerToken') {
      $access_token = $data->access_token;
      $data = array(
        'access_token' => $access_token,
      );
      drupal_write_record('oauth_access_token', $data);
      // variable_set('apiexchange_oauth_access_token', $access_token);
      $result = $access_token;
    }
    else {
      $result = '';
    }
    return $result;
  }
}

