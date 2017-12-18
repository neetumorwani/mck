<?php
/**
* @file
* user page callbacks for the expert_chat module.
*/


/*
 * Callback for list all APIs
 *
 */

function apiexchange_list_page() {
  $output = l(t('Add new API'), 'admin/config/apiexchange/entity/add');
  $apis = apiexchange_load_all(TRUE);
  $header = array('Key', 'API Name', 'Type', 'Url', 'Debug', 'Edit', 'Delete');
  $rows = array();
  foreach ($apis as $api) {
   // print_r($api);
    $row = array();
    $row[] = $api['id'];
    $row[] = $api['name'];
    $row[] = $api['type'];
    $row[] = $api['url'];
    $row[] = $api['debug'] == 1? t('Yes') : t('No');
    $row[] = l(t('Edit'), 'admin/config/apiexchange/entity/' . $api['id']);
    $row[] = '-';
    $rows[] = $row;
    
  }
  if (empty($rows)) {
   $rows[] = array(array('data' => 'No Record found', 'colspan' => 7));
  }
  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  
  return $output;
}
 
 function apiexchange_settings_form($form, &$form_state) {
  $form['apiexchange_url'] = array(
    '#type' => 'textfield',
    '#title' => t('API Url'),
    '#required' => TRUE,
    '#maxlength' => 255,
   '#default_value' => variable_get('apiexchange_url', ''),
  );
  
 $form['apiexchange_key'] = array(
  '#type' => 'textfield',
  '#title' => t('API KEY'),
  '#required' => TRUE,
  '#maxlength' => 255,
  '#default_value' => variable_get('apiexchange_key', ''),
 );
 return system_settings_form($form);
}

/*
 * Form for add/edit api.
 *
 */
function apiexchange_entity_form($form, &$form_state, $api = NULL)  {
  global $user, $base_url;
 
  $form['new'] = array(
    '#type' => 'hidden',
    '#value' => empty($api) ? 1 : 0,
  );
  $form['name'] = array(
    '#type' => 'textfield',
    '#default_value' => empty($api) ? '' : $api['name'],
    '#title' => t('API'),
    '#required' => TRUE,
    '#maxlength' => 50,
    '#description' => t('Name of API'),
  );
  if(empty($api)) {
   $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => empty($api) ? '' : $api['id'],
      '#maxlength' => 50,
      '#machine_name' => array('exists' => 'apiexchange_entity_exist',
        'source' => array('name'),
        'label' => t('machine_name'),
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
       ),
      '#required' => TRUE,
      '#title' => t('API machine name')
   );
  }
  else {
   $form['id'] = array(
    '#type' => 'hidden',
    '#value' => $api['id'],
  );
  }
 
 $form['type'] = array(
    '#type' => 'select',
    '#options' => array('GET' => 'GET', 'POST' => 'POST'),
    '#title' => t('API Request Type'),
    '#required' => TRUE,
    '#default_value' => empty($api) ? 'GET' : $api['type'],
    '#description' => t('Type of API request (POST or Post)'),
 );
  $form['return_type'] = array(
    '#type' => 'select',
    '#options' => array('xml' => 'XML', 'json' => 'JSON'),
    '#title' => t('API Response Type'),
    '#required' => TRUE,
    '#default_value' => empty($api) ? 'GET' : $api['return_type'],
    '#description' => t('Type of API response (POST or Post)'),
 );
  
 $form['url'] = array(
    '#type' => 'textfield',
    '#default_value' => empty($api) ? '' : $api['url'],
    '#title' => t('API URL'),
    '#required' => TRUE,
    '#description' => t('URL of API e.g. /v1/mckinsey_insights/popular_articles'),
 );
 $form['custom_header'] = array(
    '#type' => 'textarea',
    '#default_value' => empty($api) ? '' : $api['custom_header'],
    '#title' => t('Custom Header'),
    '#required' => FALSE,
    '#description' => t('Custom header of API e.g. Content-Type:application/json. Enter one header per line.'),
 );
 $form['detail'] = array(
    '#type' => 'textarea',
    '#default_value' => empty($api) ? '' : $api['detail'],
    '#title' => t('API Description'),
    '#required' => TRUE,
    '#description' => t('Short description of API.'),
 );
 $form['debug'] = array(
   '#type' => 'checkbox',
    '#title' => t('Debug Mode'),
    '#default_value' => empty($api) ? 0 : $api['debug'],
  );
  
 $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save API')
  );
  
  return $form;
  
}

/**
 * implement hook_form_submit.
 */

function apiexchange_entity_form_submit($form, &$form_states) {
 //print_r($form_states);
  global $user;
  $api = (object) array(
   'id' => $form_states['values']['id'] ,
   'name' => $form_states['values']['name'],
   'type' => $form_states['values']['type'],
   'return_type' => $form_states['values']['return_type'],
   'url' => $form_states['values']['url'],
   'custom_header' => $form_states['values']['custom_header'],
   'detail' => $form_states['values']['detail'],
   'debug' => $form_states['values']['debug'] == 1 ? 1 :0,
   'updated' => time(),
   'uid' => $user->uid,	
  );
   
  if ($form_states['values']['new'] == 1) {
    $api->timestamp = time();
    drupal_write_record('apiexchange', $api);
  }
  else {
   //$api = apiexchange_load($form_states['values']['id']) ;
   //$api->timestamp = $api['timestamp'];
    drupal_write_record('apiexchange', $api, array('id'));
    cache_clear_all('api-' . $api->id, 'cache_apiexchange');
  }
  
  
  $form_states['redirect'] = "admin/config/apiexchange/list";
}




