<?php
namespace App\Repository;
use Illuminate\Support\Facades\DB;

class LatestArticlesRepository extends Repository
{
  /**
  * Engagement accepted
  */
  const ACCEPTED = 1;

  /**
  * Engagement publish
  */
  const PUBLISHED = 1;

  /**
  * Default engagement
  */
  const IS_DEAFULT = 1;

  public function getData() {
      $response = [];
      $nodes = DB::table('node as n')
          ->where('type', 'news_and_articles')
          ->leftJoin('field_data_body as b', 'b.entity_id' , '=', 'n.nid')
          ->leftJoin('field_data_field_feed_image_link as il', 'il.entity_id' , '=', 'n.nid')
          ->leftJoin('field_data_field_feed_url_link as fl', 'fl.entity_id' , '=', 'n.nid')
          ->leftJoin('field_data_field_feed_news_source as fn', 'fn.entity_id' , '=', 'n.nid')
          ->leftJoin('field_data_field_feed_api_publish_date_ as fpd', 'fpd.entity_id' , '=', 'n.nid')
          ->orderBy('n.created','asc')
          ->get(array('n.nid as nid','n.title as title','b.body_value as body', 'il.field_feed_image_link_url as imagelink','fl.field_feed_url_link_url as feedlink','fn.field_feed_news_source_tid as newssource','fpd.field_feed_api_publish_date__value as publishdate'));
      foreach($nodes as $key => $node) {
          $response[$key]['nid'] = $node->nid;
          $response[$key]['title'] = $node->title;
          $response[$key]['body'] = $node->body;
          $response[$key]['imagelink'] = $node->imagelink;
          $response[$key]['feedlink'] = $node->feedlink;
          $response[$key]['newssource'] = $this->getTermName($node->newssource);
          $response[$key]['publishdate'] = $node->publishdate;
      }
      return $response;
  }
  /**
  * Function to get variable value stored via drupal config
  * @param String $variable variable name
  * @return String
  */
   public function getVariable($variable)
   {
     return getVariable($variable);
   }


  /**
  * Function to get file path
  * @param int $fid File ID
  * @return String
  */
   public function getFileUri($fid)
   {
      return DB::table('file_managed as f')
      ->where('f.fid', $fid)
      ->value('f.uri');
   }

   /**
   * Function to get file object
   * @param int $fid File ID
   * @return Object
   */
    public function getFile($fid)
    {
       return DB::table('file_managed as f')
       ->where('f.fid', $fid)
       ->first();
    }

  /**
  * Function to return field value
  * @param String $field_name Field name
  * @param Int $entity_id Entity id
  * @param String $entity_type Entity type
  *
  * @return Field value
  */
  public function getFieldValue($field_name, $entity_id, $entity_type, $data = 'value') {
    return $this->getFieldData($field_name, $entity_id, $entity_type, $data);
  }

  /**
  * Function to return Entity reference
  * @param String $field_name Field name
  * @param Int $entity_id Entity id
  *
  * @return Entity reference id
  */
  public function getFieldReference($field_name, $entity_id, $entity_type) {
    return $this->getFieldData($field_name, $entity_id, $entity_type, 'target_id');
  }

  /**
  * Get date field
  * @param String $field_name Field name
  * @param Int $entity_id Entity id
  * @param String $entity_type Entity type
  *
  * @return Object with start_date, end_date
  */
  public function getDateFieldValue($field_name, $entity_id, $entity_type, $delta = 0) {
    return DB::table('field_data_'.$field_name)
    ->where('entity_id', $entity_id)
    ->where('entity_type', $entity_type)
    ->where('delta', $delta)
    ->get([$field_name.'_value as start_date', $field_name.'_value2 as end_date']);
  }

  /**
  * Function to return field data
  * @param String $field_name Field name
  * @param Int $entity_id Entity id
  * @param String $entity_type Entity type
  * @param String $data maybe value, value2, target_id, fid etc.
  *
  * @return Field value
  */
  public function getFieldData($field_name, $entity_id, $entity_type, $data) {
    return DB::table('field_data_'.$field_name)
    ->where('entity_id', $entity_id)
    ->where('entity_type', $entity_type)
    ->value($field_name.'_'.$data);
   }

  /**
  * Function to return multi field value
  * @param String $field_name Field name
  * @param Int $entity_id Entity id
  * @param String $entity_type Entity type
  *
  * @return Array $items
  */
  public function getMultiFieldValue($field_name, $entity_id, $entity_type, $data = 'value') {
    $items = [];
    $field_data = $this->getMultiFieldData($field_name, $entity_id, $entity_type, $data);
      foreach($field_data as $value) {
        $items[] = $value->value;
      }
    return $items;
  }

  /**
  * Function to return multi field data
  * @param String $field_name Field name
  * @param Int $entity_id Entity id
  * @param String $entity_type Entity type
  * @param String $data maybe value, value2, target_id, fid etc.
  *
  * @return Field value
  */
  public function getMultiFieldData($field_name, $entity_id, $entity_type, $data) {
    return DB::table('field_data_'.$field_name)
    ->where('entity_id', $entity_id)
    ->where('entity_type', $entity_type)
    ->orderBy('delta','asc')
    ->get([$field_name.'_'.$data .' as value']);
   }

  /**
  * Get user roles
  * @param Int $user_uid user uid
  *
  * @return Array $user_roles assoc array of role rid and name
  */
  public function getRoles($user_uid) {
    $items = DB::table("role as r")
    ->join('users_roles as ur', 'ur.rid', '=', 'r.rid')
    ->join('users as u', 'ur.uid', '=', 'u.uid')
    ->where('ur.uid', $user_uid)
    ->get(['r.rid', 'r.name']);

    $user_roles = [];
    foreach ($items as $item) {
      $user_roles[$item->rid] = $item->name;
    }

    return $user_roles;
  }

  /**
  * Check node exist or not
  * @param Int $nid Node nid
  *
  * @return Boolean
  */
  public function isNodeExist($nid) {
    return (bool) DB::table('node as n')
    ->where('n.nid', $nid)
    ->limit(1)
    ->value('n.nid');
  }

  /**
  * Check node published or not
  * @param Int $nid Node nid
  *
  * @return Boolean
  */
  public function isNodePublished($nid) {
    return (bool) DB::table('node as n')
    ->where('n.nid', $nid)
    ->where('n.status', self::PUBLISHED)
    ->limit(1)
    ->value('n.nid');
  }
}
