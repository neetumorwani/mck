<?php
namespace App\Repository;
use Illuminate\Support\Facades\DB;
use Storage;
use Illuminate\Support\Arr;

class Repository
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
    const SP_IS_ENABLED_CACHE = FALSE;

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
     * Function to get file path
     * @param int $fid File ID
     * @return String
     */
    public function getFilePath($fid)
    {
        $file_path = $this->getFileUri($fid);
        return $this->getAWSFileUrl($file_path);
    }

    /**
     * Get AWS file URL
     * @param String $file_path File URI
     * @return String AWS file path
     */
    public function getAWSFileUrl($file_path) {
        $root_folder = config('filesystems.disks.s3.root_folder');

        if (stristr($file_path, 'public://')) {
            $public_folder = config('filesystems.disks.s3.public_folder');
            $file_path = str_replace('public://', $root_folder.'/'.$public_folder.'/', $file_path);
        } else {
            $private_folder = config('filesystems.disks.s3.private_folder');
            $file_path = str_replace('private://', $root_folder.'/'.$private_folder.'/', $file_path);
        }

        if($file_path) {
            //@todo fetch expiry from config
            $expiry = "+59 minutes";
            $bucket = config('filesystems.disks.s3.bucket');
            $access_key = config('filesystems.disks.s3.key');
            $secret_key = config('filesystems.disks.s3.secret');
            $token = getenv('AWS_SESSION_TOKEN');

            $config = config('filesystems.disks.s3');
            $config += ['version' => 'latest'];

            if ($config['key'] && $config['secret']) {
                $config['credentials'] = Arr::only($config, ['key', 'secret']);
            }

            if ($token) {
                $config['credentials']['token'] = $token;
            }

            $aws_client =  new \Aws\S3\S3Client($config);
            $command = $aws_client->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key'    => $file_path
            ]);

            $url = $aws_client->createPresignedRequest($command, $expiry);

            return (string)$url->getUri();
        }

        return '';
    }

    /**
     * Get formatted date
     * @param Int $date UNIX timestamp
     *
     * @return String Formatted date
     */
    public function getDateFormat($date) {
        return date(API_DTAE_FORMAT, $date);
    }

    /**
     * Convert timestamp from one timezone to UTC
     * @param Int $timestamp Timestamp
     * @param String $from_timezone Timezone
     * @param String $to_timezone Timezone
     *
     * @return UTC formatted datetime
     */
    public function getUTCDateFormat($timestamp, $from_timezone, $to_timezone = 'UTC') {
        $current_timezone = date_default_timezone_get();
        date_default_timezone_set($from_timezone);

        $datetime = date('Y-m-d h:i:s a', $timestamp);

        $dt = new \DateTime($datetime, new \DateTimeZone($from_timezone));
        $dt->setTimeZone(new \DateTimeZone($to_timezone));
        date_default_timezone_set($current_timezone);

        return $dt->format(API_DTAE_FORMAT);
    }

    /**
     * Function to get node title
     * @param int $nid Node nid
     * @return String
     */
    public function getNodeTitle($nid) {
        if (SP_IS_ENABLED_CACHE) {
            $cache = getCache($nid, SP_NODE_DATA_CACHE);
            if ($cache) {
                return $cache['title'];
            } else {
                $data['title'] = DB::table('node as n')
                    ->where('n.nid', $nid)
                    ->value('n.title');
                setForeverCache($nid, $data, SP_NODE_DATA_CACHE);
                return $data['title'];
            }
        } else {
            return DB::table('node as n')
                ->where('n.nid', $nid)
                ->value('n.title');
        }
    }

    /**
     * Get event timezone
     * @param Int $nid Event nid
     * @return String timezone
     */
    public function getEventTimezone($nid) {
        return DB::table('events_timezone as et')
            ->where('et.nid', $nid)
            ->value('et.timezone');
    }

    /**
     * Get node author
     * @param Int $nid Node nid
     *
     * @return Object
     */
    public function getNodeAuthor($nid) {
        return DB::table("node")
            ->where('nid', $nid)
            ->first(['uid']);
    }

    /**
     * Get term name
     * @param Int $tid Teram id
     *
     * @return String Term name
     */
    public function getTermName($tid) {
        if(SP_IS_ENABLED_CACHE) {
            $cache = getCache($tid, SP_TAXONOMY_TERM_DATA_CACHE);
            if($cache) {
                return $cache['name'];
            } else {
                $term['name'] = DB::table('taxonomy_term_data')
                    ->where('tid', $tid)
                    ->value('name');
                setForeverCache($tid, $term, SP_TAXONOMY_TERM_DATA_CACHE);
                return $term['name'];
            }
        }
        return DB::table('taxonomy_term_data')
            ->where('tid', $tid)
            ->value('name');
    }

    /**
     * Check node is particuler type of content
     * @param Int $nid Node id  to check content tyep
     * @param String  $content_type Content type
     *
     * @return boolean
     */
    public function isContentType($nid, $content_type) {
        return (bool) DB::table("node")
            ->where('type', $content_type)
            ->where('nid', $nid)
            ->value('nid');
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
     * Check engagement is valid for user
     * valid engaggement checking means below criteria
     * 1. Engagement must be published
     * 2. Engagement must be default
     *
     * @param Int $engagement_nid Engagement nid
     * @param Int $uid Engagement User uid
     *
     * @return Boolean
     */
    public function isValidUserEngagement($engagement_nid, $uid)
    {
        $valid = (bool)DB::table('engagement_user as eu')
            ->join('node as n', 'n.nid' , '=', 'eu.nid')
            ->join('users as u', 'u.uid' , '=', 'eu.uid')
            ->where(function($query) use ($uid)
            {
                //Is user is invited or creater of node
                $query->where('eu.uid',$uid)
                    ->orWhere('n.uid', $uid);
            })
            ->where('n.nid',$engagement_nid)
            ->where('eu.is_default', self::IS_DEAFULT)
            ->where('eu.accepted', self::ACCEPTED)
            ->where('n.status', self::PUBLISHED)
            ->limit(1)
            ->value('eu.nid');

        $user = $this->getNodeAuthor($engagement_nid);

        return ($valid || ($user->uid === $uid));
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
     * Function to Bookmark Node
     * @param Int $engagement_id Engagement id
     * @param Int $nid Entity id
     * @param Int $user_id User id
     * @param Int $type 1 for article, 2 for document
     *
     * @return boolean
     */
    public function isBookmarkedNode($engagement_id, $nid, $user_id, $type) {
        $bookmarked = (bool)DB::table('spcv_bookmark as b')
            ->where('b.nid',$nid)
            ->where('b.eid',$engagement_id)
            ->where('b.uid',$user_id)
            ->where('b.type',$type)
            ->limit(1)
            ->value('b.nid');

        return $bookmarked;
    }

    /**
     * Function to Bookmark Team
     * @param Int $engagement_id Engagement id
     * @param Int $nid Entity id
     * @param Int $user_id User id
     * @param Int $type 1 for article, 2 for document
     *
     * @return boolean
     */
    public function isBookmarkedTeam($engagement_id, $nid, $user_id, $type) {
        $bookmarked = (bool)DB::table('spcv_bookmark as b')
            ->where('b.nid',$nid)
            ->where('b.eid',$engagement_id)
            ->where('b.uid','!=', $user_id)
            ->where('b.type',$type)
            ->limit(1)
            ->value('b.nid');

        return $bookmarked;
    }

    /**
     * Get user type
     * @param Int $uid user id
     *
     * @return String internal/external
     */
    public function getUserType($uid) {
        $type = '';
        $data = DB::table('engagement_user')
            ->where('uid', $uid)
            ->where('is_default', 1)
            ->value('member_type');

        if($data == 1) {
            $type = 'internal';
        } else {
            $type = 'external';
        }
        return $type;
    }

    /*

    * Is user is engagement manager
    * @param Int $engagement_nid engagement nid
    * @param Int $user_uid user uid
    *
    * @return boolean
    */
    public function isEngagementManger($engagement_nid, $user_uid) {
        $valid = (bool) DB::table('engagement_user')
            ->where('nid', $engagement_nid)
            ->where('uid', $user_uid)
            ->where(function($query) {
                $query->where('engagement_manager' , 1)
                    ->orWhere('is_author', 1);
            })
            ->limit(1)
            ->value('nid');
        $owner = $this->getEngagementOwner($engagement_nid);
        return ($valid || ($user_uid == $owner));
    }

    /**
     * Get engagement owner
     * @param Int $engagement_nid Engagement nid
     *
     * @return Int user uid
     */
    public function getEngagementOwner($engagement_nid) {
        return $this->getFieldReference('field_engagement_owner', $engagement_nid, 'node');
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
     * Get getSevenBuildingConfig
     * @param Int $eng_id Engagement id
     *
     * @return string relevant_steps
     */
    public function getSevenBuildingConfig($eng_id) {
        if(SP_IS_ENABLED_CACHE) {
            $cache = getCache($eng_id, SP_SEVEN_BUILDING_BLOCK_CONFIG);
            if($cache) {
                return $cache;
            }
        }
        $data = DB::table('engagement_configurations')
            ->where('nid', $eng_id)
            ->value('relevant_steps');
        if(SP_IS_ENABLED_CACHE) {
            setForeverCache($eng_id, $data, SP_SEVEN_BUILDING_BLOCK_CONFIG);
        }
        return $data;
    }

    /**
     * Get Document Boommarked count
     * @param Int $eng_id Engagement id
     * @param Int $tid Term id (7 step refrence)
     * @param Int $user_id user id
     *
     * @return string count
     */
    public function getDocumentBookmarkedCount($eng_id, $tid) {
        $mapped_doc = $this->getOrderedResource($tid, $eng_id);
        $data = DB::table('spcv_bookmark as b')
            ->where('b.eid', $eng_id)
            ->whereIn('b.nid', $mapped_doc)
            ->distinct()
            ->count('b.nid');
        return $data;
    }

    /**
     * Get Document Type by node id
     * @param int $nid (Node Id)
     * @return string
     */
    public function getDocumentType($nid)
    {
        $type = $ref = [];
        $doc_type = null;

        $ref = DB::table('node as n')
            ->join('field_data_field_resource_reference as res_ref', 'n.nid', '=', 'res_ref.entity_id')
            ->where('n.type','resources')
            ->where('n.status','1')
            ->where('n.nid',$nid)
            ->limit(1)
            ->get(['res_ref.field_resource_reference_nid as ref_nid']);
        // Check refrence
        if(!empty($ref)) {
            return 'multidocument';
        }

        $type = DB::table('node as n')
            ->leftJoin('field_data_field_resource_type as res_type', 'n.nid', '=', 'res_type.entity_id')
            ->leftJoin('field_data_field_document_type as dt', 'dt.entity_id', '=', 'res_type.field_resource_type_tid')
            ->where('n.type','resources')
            ->where('dt.field_document_type_value','<>', 'essentials')
            ->where('n.status','1')
            ->where('n.nid',$nid)
            ->limit(1)
            ->get(['dt.field_document_type_value as doc_type']);

        if($type) {
            $doc_type = $type['0']->doc_type;
        }
        return $doc_type;
    }

    /**
     * Get Document Category by node id
     * @param int $nid (Node Id)
     * @return string
     */
    public function getDocumentCategory($nid)
    {
        $data = [];
        $category = null;
        $data = DB::table('node as n')
            ->leftJoin('field_data_field_resource_type as res_type', 'n.nid', '=', 'res_type.entity_id')
            ->leftJoin('field_data_field_document_type as dt', 'dt.entity_id', '=', 'res_type.field_resource_type_tid')
            ->leftJoin('taxonomy_term_data as t', 'res_type.field_resource_type_tid', '=', 't.tid')
            ->where('n.type','resources')
            ->where('dt.field_document_type_value','<>', 'essentials')
            ->where('n.status','1')
            ->where('n.nid',$nid)
            ->limit(1)
            ->get(['t.name']);

        if($data) {
            $category = $data['0']->name;
        }
        return $category;
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

    /**
     * Get resource view url
     * @param Int $document_id Document nid
     *
     * @return String
     */
    public function getResourceViewUrl($document_id, $document_type) {
        $fid = null;
        if ($document_type == DOCUMENT_TYPE_VIDEOS) {
            $fid = $this->getFieldValue('field_video', $document_id, 'node', 'fid');
        } else {
            $fid = $this->getFieldValue('field_pdf_viewer', $document_id, 'node', 'fid');
        }
        $path = $this->getFilePath($fid);
        return $path ? : null;
    }

    /**
     * Generete download url
     * @param Int $document_id Resource nid
     * @param Int $engagement_id Engagement nid
     * @param Int $user_id User uid
     *
     * @return String
     */
    public function getResourceDownloadUrl($document_id, $engagement_id, $user_id, $document_type) {
        $fids = [];

        if ($document_type == DOCUMENT_TYPE_VIDEOS) {
            $fids = $this->getMultiFieldValue('field_video', $document_id, 'node', 'fid');
        } else {
            $fids = $this->getMultiFieldValue('field_resource', $document_id, 'node', 'fid');
        }

        if (count($fids)) {
            $fid = array_pop($fids);
            $download_path = SITE_PATH.APP_PATH_PREFIX.'download/'.$fid.'/'.$engagement_id.'/'.$user_id;
            return $download_path;
        }

        return null;
    }

    /**
     * Generete download url from fid
     * @param Int $fid Resource file id
     * @param Int $engagement_id Engagement nid
     * @param Int $user_id User uid
     *
     * @return String
     */
    public function getResourceDownloadUrlFromFid($fid, $engagement_id, $user_id) {
        if($fid && $engagement_id && $user_id) {
            $download_path = SITE_PATH.APP_PATH_PREFIX.'download/'.$fid.'/'.$engagement_id.'/'.$user_id;
            return $download_path;
        }
        return null;
    }

    /**
     * Get Default Enagagement Company ID
     * @param Int $uid Engagement User uid
     *
     * @return Int $nid Company ID
     */
    public function getDefaultEngagementCompanyId($uid)
    {
        return DB::table('engagement_user as eu')
            ->join('node as n', 'n.nid' , '=', 'eu.cnid')
            ->where('eu.uid',$uid)
            ->where('eu.is_default', self::IS_DEAFULT)
            ->where('n.status', self::PUBLISHED)
            ->value('eu.cnid as nid');
    }

    /**
     * Get Default Enagagement ID
     * @param Int $uid Engagement User uid
     *
     * @return Int $nid Engagement ID
     */
    public function getDefaultEngagementId($uid)
    {
        return DB::table('engagement_user as eu')
            ->join('node as n', 'n.nid' , '=', 'eu.cnid')
            ->where('eu.uid',$uid)
            ->where('eu.is_default', self::IS_DEAFULT)
            ->where('n.status', self::PUBLISHED)
            ->value('eu.nid as nid');
    }

    /**
     * Get true/false value for bookamrk, recommend, read apis
     * @param Int/String $val
     *
     * @return String true/false/invalid
     */
    function getBoolValue($val) {
        $value = strtolower(trim($val));
        if($value === 'true' || $value === true || $value === 1 || $value === '1') {
            return 'true';
        }

        if($value === 'false' || $value === false || $value === 0 || $value === '0' || $value == '') {
            return 'false';
        }
        return 'invalid';
    }

    /**
     * Get user engagement managers email
     * @param Int $engagement_nid engagement nid
     *
     * @return string $emails comma separated emails
     */
    public function getEngagementMangersEmail($engagement_nid) {
        $emails = [];
        $data = DB::table('engagement_user as e')
            ->join('users as u', 'u.uid' , '=', 'e.uid')
            ->where('e.nid', $engagement_nid)
            ->where('e.accepted', self::ACCEPTED)
            ->where(function($query) {
                $query->where('e.engagement_manager' , 1)
                    ->orWhere('e.is_author', 1);
            })
            ->get(['u.mail as email']);
        foreach ($data as $email) {
            $emails[] = $email->email;
        }

        if(count($emails) > 0) {
            $emails = implode(',', $emails);
        } else {
            $emails = null;
        }
        return $emails;
    }

    /**
     * Get engagement ordered Resources
     * @param Int $tid (Term Id)
     * @param Int $eng_id (Engagement ID)
     * @return Array $eng_node
     */
    public function getEngagementOrderedResources($tid, $eng_id) {
        $eng_node = [];

        if (SP_IS_ENABLED_CACHE) {
            $cache = getCache($eng_id.'_'.$tid, SP_ENG_ORDERED_RESOURCE_DOC_CACHE);

            if($cache) {
                return $cache;
            }
        }

        $eng_nodes = DB::table('node as n')
            ->join('spcv_engagement_documents_ordering as ed', 'n.nid', '=', 'ed.nid')
            ->where('n.type', RESOURCES_CONTENT_TYPE)
            ->where('n.status', '1')
            ->where('ed.tid', $tid)
            ->where('ed.eid', $eng_id)
            ->orderBy('ed.weight', 'asc')
            ->get(array('ed.nid as nid', 'ed.enabled as is_selected'));
        $eng_node = [];

        foreach($eng_nodes as $node) {
            $eng_node[$node->nid] = ['nid' => $node->nid, 'is_selected' => $node->is_selected];
        }

        if (SP_IS_ENABLED_CACHE) {
            setForeverCache($eng_id.'_'.$tid, $eng_node, SP_ENG_ORDERED_RESOURCE_DOC_CACHE);
        }

        return $eng_node;
    }

    /**
     * Get Global ordered Resources
     * @param Int $tid (Term Id)
     * @return Array $refnode
     */

    public function getGlobalOrderedResources($tid) {
        if (SP_IS_ENABLED_CACHE) {
            $common_doc_cache = getCache($tid, SP_ORDERED_RESOURCE_DOC_CACHE);

            if($common_doc_cache) {
                return $common_doc_cache;
            }
        }

        $nodes = DB::table('node as n')
            ->join('field_data_field_building_blcok as step7ref', 'n.nid', '=', 'step7ref.entity_id')
            ->join('field_data_field_resources as r', 'step7ref.entity_id', '=', 'r.entity_id')
            ->join('node as refn', 'refn.nid', '=', 'r.field_resources_nid')
            ->where('n.type','strategy_framework_oredring')
            ->where('n.status','1')
            ->where('refn.status','1')
            ->where('step7ref.field_building_blcok_target_id',$tid)
            ->orderBy('r.delta','asc')
            ->get(array('r.field_resources_nid as nid'));
        $refnode = [];

        foreach($nodes as $node) {
            $refnode[$node->nid] = ['nid' => $node->nid, 'is_selected' => 1];
        }

        if (SP_IS_ENABLED_CACHE) {
            setForeverCache($tid, $refnode, SP_ORDERED_RESOURCE_DOC_CACHE);
        }

        return $refnode;
    }

    /**
     * Get Ordered Resource Documents data mapped to specific 7_step term
     * @param int $tid (Term Id)
     * @param int $eng_id (Engagement ID)
     * @return array $data
     */
    public function getOrderedResource($tid, $eng_id) {
        $eng_docs = $this->getEngagementOrderedResources($tid, $eng_id);
        $global_docs = $this->getGlobalOrderedResources($tid);

        $docs = $eng_docs + $global_docs;

        $selected_docs = array_filter($docs, function($doc) {
            return $doc['is_selected'];
        });

        return array_keys($selected_docs);
    }

    /**
     * Get User Data
     * @param int $uid (user id)
     * @return array $user
     */
    public function getUserData($uid) {
        $user = [];
        $data =  DB::table('engagement_user as eu')
            ->join('node as n', 'n.nid' , '=', 'eu.cnid')
            ->join('users as u', 'u.uid' , '=', 'eu.uid')
            ->where('eu.uid', $uid)
            ->where('eu.is_default', self::IS_DEAFULT)
            ->where('n.status', self::PUBLISHED)
            ->limit(1)
            ->get(['eu.member_type', 'eu.engagement_manager', 'eu.is_author', 'u.mail', 'u.uid']);
        if($data) {
            // member type
            if($data['0']->member_type == 1) {
                $user['type'] = 'internal';
            } else {
                $user['type'] = 'external';
            }
            // engagement manager
            if($data['0']->engagement_manager == 1 || $data['0']->is_author == 1) {
                $user['engagement_manager'] = true;
            } else {
                $user['engagement_manager'] = false;
            }

            if(SP_IS_ENABLED_CACHE) {
                $cache = getCache($data['0']->uid, SP_USER_DATA_CACHE);
                if ($cache) {
                    $user['first_name'] = $cache['first_name'];
                    $user['last_name'] = $cache['last_name'];
                    $fid = $cache['image_fid'];
                    $user['profile_picture_src'] = $fid ? $this->getFilePath($fid) : null;
                    $user['user_id'] = $data['0']->uid;
                    $user['email'] = $data['0']->mail;
                } else {
                    $user['first_name'] = $this->getFieldValue('field_first_name', $data['0']->uid, 'user');
                    $user['last_name'] = $this->getFieldValue('field_last_name', $data['0']->uid, 'user');
                    $fid = $this->getFieldValue('field_image', $data['0']->uid, 'user', 'fid');
                    $user['profile_picture_src'] = $fid ? $this->getFilePath($fid) : null;
                    $user['user_id'] = $data['0']->uid;
                    $user['email'] = $data['0']->mail;

                    $user_data = [];
                    $user_data['email'] = $data['0']->mail;
                    $user_data['first_name'] = $this->getFieldValue('field_first_name', $data['0']->uid, 'user');
                    $user_data['last_name'] = $this->getFieldValue('field_last_name', $data['0']->uid, 'user');
                    $user_data['phone'] = $this->getFieldValue('field_phone_number', $data['0']->uid, 'user');
                    $user_data['image_fid'] =  $this->getFieldValue('field_image', $data['0']->uid, 'user', 'fid');
                    setForeverCache($data['0']->uid, $user_data, SP_USER_DATA_CACHE);
                }
            } else {
                $user['first_name'] = $this->getFieldValue('field_first_name', $data['0']->uid, 'user');
                $user['last_name'] = $this->getFieldValue('field_last_name', $data['0']->uid, 'user');
                $fid = $this->getFieldValue('field_image', $data['0']->uid, 'user', 'fid');
                $user['profile_picture_src'] = $fid ? $this->getFilePath($fid) : null;
                $user['user_id'] = $data['0']->uid;
                $user['email'] = $data['0']->mail;
            }
        }
        return $user;
    }

    /**
     * Get Resource Documents by node id
     * @param int $nid (Node Id)
     * @param int $eng_id (Engagement Id)
     * @param int $user_id (User Id)
     * @return array $data
     */
    public function getResourceDocumentsByNid($nid, $eng_id, $user_id, $child = TRUE) {
        $nodes = [];
        $data = null;
        $hasCache = FALSE;
        if(SP_IS_ENABLED_CACHE) {
            $cache = getCache($nid, SP_RESOURCE_DOC_CACHE);
            if($cache) {
                $hasCache = TRUE;
            }
        }

        if($hasCache === TRUE && SP_IS_ENABLED_CACHE === TRUE) {
            $node = $cache;
            $data['id'] = $node['id'];
            $data['name'] = $node['name'];
            // Get document type
            $data['document_type'] = $node['document_type'];
            // Get document category
            $data['category'] = $node['category'];
            $data['text'] = $node['text'];
            $bookmarked_user = $this->isBookmarkedNode($eng_id, $node['id'], $user_id, DOCUMENT);
            $bookmarked_team = $this->isBookmarkedTeam($eng_id, $node['id'], $user_id, DOCUMENT);
            $data['bookmarked_user'] = $bookmarked_user ? true : false;
            $data['bookmarked_team'] = $bookmarked_team ? true : false;
            $data['recommended'] = $this->isRecommended($eng_id, $node['id'], DOCUMENT);
            $read = $this->isRead($eng_id, $node['id'], $user_id, DOCUMENT);
            $data['read'] = $read ? true : false;
            $data['download_link'] = $this->getResourceDownloadUrlFromFid($node['download_file_fid'], $eng_id, $user_id);
            if($child) {
                $data['iframe_url'] = $this->getAWSFileUrl($node['iframe_uri']);
            }
            return $data;
        }

        $nodes = DB::table('cache_spcv_resources as n')
            ->where('n.nid',$nid)
            ->value('n.data as data');
        if($nodes) {
            $node = unserialize($nodes);
            $data['id'] = $node['id'];
            $data['name'] = $node['name'];
            // Get document type
            $data['document_type'] = $node['document_type'];
            // Get document category
            $data['category'] = $node['category'];
            $data['text'] = $node['text'];
            $bookmarked_user = $this->isBookmarkedNode($eng_id, $node['id'], $user_id, DOCUMENT);
            $bookmarked_team = $this->isBookmarkedTeam($eng_id, $node['id'], $user_id, DOCUMENT);
            $data['bookmarked_user'] = $bookmarked_user ? true : false;
            $data['bookmarked_team'] = $bookmarked_team ? true : false;
            $data['recommended'] = $this->isRecommended($eng_id, $node['id'], DOCUMENT);
            $read = $this->isRead($eng_id, $node['id'], $user_id, DOCUMENT);
            $data['read'] = $read ? true : false;
            $data['download_link'] = $this->getResourceDownloadUrlFromFid($node['download_file_fid'], $eng_id, $user_id);
            if($child) {
                $data['iframe_url'] = $this->getAWSFileUrl($node['iframe_uri']);
            }

            if(SP_IS_ENABLED_CACHE) {
                setForeverCache($nid, $node, SP_RESOURCE_DOC_CACHE);
            }
        }
        return $data;
    }

    /**
     * Get all Resource Documents nid mapped  at Strategy Framwork Ordering
     * and refrenced at seven step taxnomy term
     * @param int $engagement_id
     * @return array $documents (Array of Node Id)
     */
    public function get_all_mapped_refrenced_documents_nid($engagement_id = 0) {
        $documents = $referenced_documents = $mapped_documents = $eng_mapped_documents =[];
        if(SP_IS_ENABLED_CACHE) {
            $cache = getCache($engagement_id, SP_ALL_MAPPED_REFRENCED_DOCUMENTS_CACHE);
            if($cache) {
                return $cache;
            }
        }

        // All documents referenced at seven step
        $referenced_documents = DB::table('field_data_field_document as d')
            ->where('d.bundle', SEVENT_STEP)
            ->distinct()
            ->get(array('d.field_document_target_id as nid'));
        foreach($referenced_documents  as $referenced_document) {
            $documents[$referenced_document->nid] = $referenced_document->nid;
        }

        // All mapped documents at strategy framework oredring
        $mapped_documents = DB::table('field_data_field_resources as r')
            ->where('r.bundle', STRATEGY_FRAMEWORK_ORDEREING_CONTENT_TYPE)
            ->distinct()
            ->get(array('r.field_resources_nid as nid'));
        foreach ($mapped_documents  as $mapped_document) {
            $documents[$mapped_document->nid] = $mapped_document->nid;
        }

        // All engaments specific mapped documents at strategy framework oredring
        $eng_mapped_documents = DB::table('spcv_engagement_documents_ordering as r')
            ->where('r.eid', $engagement_id)
            ->distinct()
            ->get(array('r.nid as nid', 'r.enabled as is_selected'));
        foreach ($eng_mapped_documents  as $eng_mapped_document) {
            if($eng_mapped_document->is_selected == 1) {
                $documents[$eng_mapped_document->nid] = $eng_mapped_document->nid;
            } else {
                unset($documents[$eng_mapped_document->nid]);
            }
        }

        if(SP_IS_ENABLED_CACHE) {
            setForeverCache($engagement_id, array_values($documents), SP_ALL_MAPPED_REFRENCED_DOCUMENTS_CACHE);
        }

        return $documents;
    }

    /**
     * Get tracking code and add into the URL.
     * @param String $field_feed_link
     * @param String $tracking_code
     * @return String $updated_url
     */
    public function getURLwithTrackingCode($field_feed_link, $tracking_code) {
        if($field_feed_link) {
            $tracking_code = $this->getVariable($tracking_code);
            if ($tracking_code) {
                if(strrpos($field_feed_link, '?')){
                    return $field_feed_link . '&' . $tracking_code;
                } else {
                    return $field_feed_link . '?' . $tracking_code;
                }
            }
        }
        // Returns untracked URL.
        return $field_feed_link;
    }
}
