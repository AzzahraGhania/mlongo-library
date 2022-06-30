<?php namespace AzzahraGhania\Library;

use \Config\MongoConfig;
use MongoDB\BSON;
use MongoDB\BSON\Regex;
use MongoDB\Client;
use MongoCollection;
/**
* CodeIgniter MongoDB Active Record Library
*
* A library to interface with the NoSQL database MongoDB. For more information see http://www.mongodb.org
*
* @package CodeIgniter
* @author Intekhab Rizvi | www.intekhab.in | me@intekhab.in
* @copyright Copyright (c) 2014, Intekhab Rizvi.
* @license http://www.opensource.org/licenses/mit-license.php
* @link http://intekhab.in
* @version Version 1.0
* Thanks to Alex Bilbie (http://alexbilbie.com) for help.
*/

Class Mongo_db{

    private $config = array();
    private $param = array();
    private $activate;
    private $connect;
    private $db;
    private $hostname;
    private $port;
    private $database;
    private $username;
    private $password;
    private $debug;
    private $write_concerns;
    private $legacy_support;
    private $read_concern;
    private $read_preference;
    private $journal;
    private $selects = array();
    private $updates = array();
    private $wheres = array();
    private $limit  = 999999;
    private $offset = 0;
    private $sorts  = array();
    private $return_as = 'array';
    public $benchmark = array();
    private $next = '';


    /**
    * --------------------------------------------------------------------------------
    * Class Constructor
    * --------------------------------------------------------------------------------
    *
    * Automatically check if the Mongo PECL extension has been installed/enabled.
    * Get Access to all CodeIgniter available resources.
    * Load mongodb config file from application/config folder.
    * Prepare the connection variables and establish a connection to the MongoDB.
    * Try to connect on MongoDB server.
    */

    function __construct()
    {
        if ( ! class_exists('MongoDB\Driver\Manager'))
        {
            $this->show_error("The MongoDB PECL extension has not been installed or enabled");
        }
        $config = CONFIG;
        $this->config = $config['mongo_db'];
       // $this->param = $param;
        $this->connect();
    }

  /**
    * --------------------------------------------------------------------------------
    * Class Destructor
    * --------------------------------------------------------------------------------
    *
    * Close all open connections.
    */
    function __destruct()
    {
        if(is_object($this->connect))
        {
            //$this->connect->close();
        }
    }
    /**
    * --------------------------------------------------------------------------------
    * Prepare configuration for mongoDB connection
    * --------------------------------------------------------------------------------
    * 
    * Validate group name or autoload default group name from config file.
    * Validate all the properties present in config file of the group.
    */
    private function prepare()
    {
        if(is_array($this->param) && count($this->param) > 0 && isset($this->param['activate']) == TRUE)
        {
            $this->activate = $this->param['activate'];
        }
        else if(isset($this->config['active']) && !empty($this->config['active']))
        {
            $this->activate = $this->config['active'];
        }else
        {
            $this->show_error("MongoDB configuration is missing.");
        }
        if(isset($this->config[$this->activate]) == TRUE)
        {
            if(empty($this->config[$this->activate]['hostname']))
            {
                $this->show_error("Hostname missing from mongodb config group : {$this->activate}");
            }
            else
            {
                $this->hostname = trim($this->config[$this->activate]['hostname']);
            }
            if(empty($this->config[$this->activate]['port']))
            {
                $this->show_error("Port number missing from mongodb config group : {$this->activate}");
            }
            else
            {
                $this->port = trim($this->config[$this->activate]['port']);
            }
            if(isset($this->config[$this->activate]['no_auth']) == FALSE
               && empty($this->config[$this->activate]['username']))
            {
                $this->show_error("Username missing from mongodb config group : {$this->activate}");
            }
            else
            {
                $this->username = trim($this->config[$this->activate]['username']);
            }
            if(isset($this->config[$this->activate]['no_auth']) == FALSE 
               && empty($this->config[$this->activate]['password']))
            {
                $this->show_error("Password missing from mongodb config group : {$this->activate}");
            }
            else
            {
                $this->password = trim($this->config[$this->activate]['password']);
            }
            if(empty($this->config[$this->activate]['database']))
            {
                $this->show_error("Database name missing from mongodb config group : {$this->activate}");
            }
            else
            {
                $this->database = trim($this->config[$this->activate]['database']);
            }
            if(empty($this->config[$this->activate]['db_debug']))
            {
                $this->debug = FALSE;
            }
            else
            {
                $this->debug = $this->config[$this->activate]['db_debug'];
            }
            if(empty($this->config[$this->activate]['return_as']))
            {
                $this->return_as = 'array';
            }
            else
            {
                $this->return_as = $this->config[$this->activate]['return_as'];
            }
            if(empty($this->config[$this->activate]['legacy_support']))
            {
                $this->legacy_support = false;
            }
            else
            {
                $this->legacy_support = $this->config[$this->activate]['legacy_support'];
            }
            if(empty($this->config[$this->activate]['read_preference']) || 
                !isset($this->config[$this->activate]['read_preference']))
            {
                $this->read_preference = MongoDB\Driver\ReadPreference::RP_PRIMARY;
            }
            else
            {
                $this->read_preference = $this->config[$this->activate]['read_preference'];
            }
            if(empty($this->config[$this->activate]['read_concern']) || 
                !isset($this->config[$this->activate]['read_concern']))
            {
                $this->read_concern = MongoDB\Driver\ReadConcern::MAJORITY;
            }
            else
            {
                $this->read_concern = $this->config[$this->activate]['read_concern'];
            }
        }
        else
        {
            $this->show_error("mongodb config group :  <strong>{$this->activate}</strong> does not exist.");
        }
    }
    /**
     * Sets the return as to object or array
     * This is useful if library is used in another library to avoid issue if config values are different
     *
     * @param string $value
     */
    public function set_return_as($value)
    {
        if(!in_array($value, ['array', 'object']))
        {
            $this->show_error("Invalid Return As Type");
        }
        $this->return_as = $value;
    }
    /**
    * --------------------------------------------------------------------------------
    * Connect to MongoDB Database
    * --------------------------------------------------------------------------------
    * 
    * Connect to mongoDB database or throw exception with the error message.
    */
    private function connect()
    {
        $this->prepare();
        try
        {
            $dns = "mongodb://{$this->hostname}:{$this->port}";
            if(isset($this->config[$this->activate]['no_auth']) == TRUE && $this->config[$this->activate]['no_auth'] == TRUE)
            {
                $options = array();
            }
            else
            {
                $options = array('username'=>$this->username, 'password'=>$this->password);
            }
            $db = new \MongoDB\Client($dns, $options);
            $this->connect = $this->db = $db->{$this->database};
             
        }
        catch (MongoDB\Driver\Exception\Exception $e)
        {
            if(isset($this->debug) == TRUE && $this->debug == TRUE)
            {
                $this->show_error("Unable to connect to MongoDB: {$e->getMessage()}");
            }
            else
            {
                $this->show_error("Unable to connect to MongoDB");
            }
        }
    }

    function like(?string $field, $value = "", $flags = "i", $enable_start_wildcard = TRUE, $enable_end_wildcard = TRUE)
    {
        if (empty($field)) {
            throw new \Exception("Mongo field is require to perform like query.");
        }
        if (empty($value)) {
            throw new \Exception("Mongo field's value is require to like query.");
        }
        $field = (string)trim($field);
        $this->_w($field);
        $value = (string)trim($value);
        $value = quotemeta($value);
        if ($enable_start_wildcard !== TRUE) {
            $value = ".*" . $value;
        }
        if ($enable_end_wildcard !== TRUE) {
            $value .= ".*";
        }
       // $value = "/.*".$value.".*/i";
        $tes = new Regex($value, $flags);
        $this->wheres[$field] = new Regex($value, $flags);
        return ($this);
    }

    /**
     * --------------------------------------------------------------------------------
     * Select
     * --------------------------------------------------------------------------------
     *
     * Determine which fields to include OR which to exclude during the query process.
     * If you want to only choose fields to exclude, leave $includes an empty array().
     *
     * @usage: $this->m->select(array('foo', 'bar'));
     */
    public function select($includes = array(), $excludes = array())
    {
        if (!is_array($includes)) {
            $includes = array();
        }
        if (!is_array($excludes)) {
            $excludes = array();
        }
        if (!empty($includes)) {
            foreach ($includes as $key => $col) {
                if (is_array($col)) {
                    //support $elemMatch in select
                    $this->selects[$key] = $col;
                } else {
                    $this->selects[$col] = 1;
                }
            }
        }
        if (!empty($excludes)) {
            foreach ($excludes as $col) {
                $this->selects[$col] = 0;
            }
        }

        $this->options['projection'] = $this->selects;
        return ($this);
    }

    /**
     * @param array $options
     * @return $this
     * @throws \Exception
     */
    function options($options = array())
    {
        $ops = [];
        if (is_array($options)) {
            foreach ($options as $wh => $val) {
                if($wh =='skip'){
                  $this->offset = $val;
                }
                if($wh =='limit'){
                  $this->limit = $val;
                }
            }
        } else {
            throw new \Exception("Where value should be an array.(options)");
        }

        return $this;
    }

    function deleteone($colname,$id){
      $collection = $this->db->{$colname};
      $deleteResult = $collection->deleteOne(
          [ '_id' =>  new \MongoDB\BSON\ObjectID($id)]
        );
      $del = $deleteResult->getDeletedCount();
      return $del;
    }


    function deletemany($colname){
      $collection = $this->db->{$colname};
      $filter= $this->wheres;
      $deleteResult = $collection->deleteMany($filter
        );
      $del = $deleteResult->getDeletedCount();
      return $del;
    }


    function count($col){
        $filter= $this->wheres;
        $collection = $this->db->{$col};
        $count = $collection->count($filter);
        $this->_clear();
        return $count;
    }

    public function get($colname){
        $offset= (int) $this->offset;
        $limit= (int) $this->limit;
        $filter= $this->wheres;
        $sort =  $this->sorts;
        $collection = $this->db->{$colname};
        $cursor = $collection->find(
            $filter,
            [
                'skip' => $offset,
                'limit' => $limit,
                'sort' =>$sort,
            ]
        );
        $this->_clear();
        return $cursor;
      }
      
      function getdb($colname,$id){
        $collection =  $this->db->{$colname};
        $getdata = $collection->findOne(
            [ '_id' =>  new \MongoDB\BSON\ObjectID($id)]
          );
        $this->_clear();
        return $getdata;
      }

    function insertdb($colname,$data){
          $collection =  $this->db->{$colname};
          $insertOneResult = $collection->insertOne($data);
          return $insertOneResult;
        }

    function updatedb($colname,$id,$data){
        $collection =  $this->db->{$colname};
        $updateResult = $collection->updateOne(
          [ '_id' =>  new \MongoDB\BSON\ObjectID($id)],
          [ '$set' =>$data]
      );
      return $updateResult;
    }
    function insertmany($colname,$data){
          $collection =  $this->db->{$colname};
          $insertOneResult = $collection->insertMany($data);
          return $insertOneResult;
        }
      
      function find_one($colname){
        $filter= $this->wheres;
        $sort =  $this->sorts;
        $collection = $this->db->{$colname};
        $cursor = $collection->findOne(
            $filter
        );
        $this->_clear();
        return $cursor;
      }

      function findoneupdate($colname,$data){
            $collection =  $this->db->{$colname};
            $filter= $this->wheres;
            $insertOneResult = $collection->findOneAndUpdate(
              $filter,
              ['$set' =>  $data ],
              [ 'upsert' => true ]
            );
            if (is_null($insertOneResult)) {
              return false;
            }else{
              return  true;
            }
      }


        function saveTags($name){
            $collection = $this->db->tags;
          try {
              $document = $collection->findOneAndUpdate(
              ['tag' => clean_string($name)],
              ['$inc' => ['count' => 1] ],
              ['upsert' => true,
                'projection' => [ 'count' => 1 ],
               'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER
               ]
          );
         return $document['count'];
          } catch (Exception $e) {
            return false;
          }

        }

        function saveView($id){
          $collection = $this->db->post;
          $document = $collection->findOneAndUpdate(
              [ '_id' =>  new \MongoDB\BSON\ObjectID($id)],
              ['$inc' => ['post_view' => 1] ],
              ['projection' => [ 'post_view' => 1 ],
               'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER
               ]
          );
         return $document['post_view'];
        }

        function saveViewtag($tag){
         $collection = $this->db->tags;
        $document = $collection->findOneAndUpdate(
              [ 'tag' => clean_string($tag)],
              ['$inc' => ['tag_view' => 1] ],
              [
               'projection' => [ 'tag_view' => 1 ],
               'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER
               ]
          );
        if(empty($document))
          $this->saveTags($tag);

         return $document['tag_view'];
        }


        function saveDownload($id){
          $collection = $this->db->post;
          $document = $collection->findOneAndUpdate(
              [ '_id' =>  new \MongoDB\BSON\ObjectID($id)],
              ['$inc' => ['post_download' => 1] ],
              ['projection' => [ 'post_download' => 1 ],
               'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER
               ]
          );
         return $document['post_download'];
        }

      function randomnofilter($colname){
        $limit= (int) $this->limit;
        $collection =  $this->db->{$colname};
        $cursor = $collection->aggregate( [ ['$sample' => [ 'size' => $limit ] ]
        ]);
        $this->_clear();
        return $cursor;
      }
      
      function random($colname){
        $filter= $this->wheres;
        $limit= (int) $this->limit;
        $collection = $this->db->{$colname};
        if(empty($filter)){
           $cursor = $collection->aggregate( [ ['$sample' => [ 'size' => $limit ] ]
          ]);
        }else{
           $cursor = $collection->aggregate( [ ['$match' => $filter ],['$sample' => [ 'size' => $limit ] ]
          ]);
        }
       
        $this->_clear();

        return $cursor;
      }
  
      function getnext($colname,$id){
        $collection = $this->db->{$colname};
        try {
            $document = $collection->find(
            ['_id' => ['$gt' => new \MongoDB\BSON\ObjectID($id)] ],
            [
                'limit' => 1,
            ]
        );
       return $document;
        } catch (Exception $e) {
          return false;
        }
      
      }

      function getprev($colname,$id){
        $collection = $this->db->{$colname};
        try {
            $document = $collection->find(
            ['_id' => ['$lt' => new \MongoDB\BSON\ObjectID($id)] ],
            [
                'limit' => 1,
                'sort' => ['_id' => -1],
            ]
        );
       return $document;
        } catch (Exception $e) {
          return false;
        }
      
      }

    function search($colname,$search='' ){
        $offset= (int) $this->offset;
        $limit= (int) $this->limit;
        $collection = $this->db->{$colname};
        $filter = [
            '$text' => ['$search' => $search]];
        $options =  [
            'skip' => $offset,
            'limit' => $limit,
            'projection' => [
                'score' => ['$meta' => 'textScore']
                    ],
            'sort' => [
                'score' => ['$meta' => 'textScore']
                ]
        ];

        try {
            $cursor = $collection->find($filter,$options);
        } catch (MongoDB\Driver\Exception\Exception $e) {
            echo $e->getMessage(), "\n";
            $cursor = false;
        }
        return $cursor;
    }

    function countsearch($colname,$search='' ){
        $collection = $this->db->{$colname};
        $filter = [
            '$text' => ['$search' => $search]];
        $cursor = $collection->count($filter);
        return $cursor;
    }


    function incId($name){
        $collection = $this->db->counters;
        $document = $collection->findOneAndUpdate(
          ['_id' => $name],
          ['$inc' => ['seq' => 1] ],
          ['upsert' => true,
            'projection' => [ 'seq' => 1 ],
           'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER
           ]
      );
     return $document['seq'];
    }

    function createIndex($colname,$key,$option=array() ){
      try {
      $collection = $this->db->{$colname};
      $indexName = $collection->createIndex($key,$option);
      return $indexName;

      } catch (Exception $e) {
        return false;
      }
    }
      

 /**
    * --------------------------------------------------------------------------------
    * //! Where
    * --------------------------------------------------------------------------------
    *
    * Get the documents based on these search parameters. The $wheres array should
    * be an associative array with the field as the key and the value as the search
    * criteria.
    *
    * @usage : $this->mongo_db->where(array('foo' => 'bar'))->get('foobar');
    */
    public function where($wheres, $value = null)
    {
        if (is_array($wheres))
        {
            foreach ($wheres as $wh => $val)
            { 
                $val = ($wh =='_id')?  new \MongoDB\BSON\ObjectID($val) :$val;
                $this->wheres[$wh] = $val;
            }
        }
        else
        {
            $this->wheres[$wheres] = $value;
        }
        return $this;
    }
    /**
    * --------------------------------------------------------------------------------
    * or where
    * --------------------------------------------------------------------------------
    *
    * Get the documents where the value of a $field may be something else
    *
    * @usage : $this->mongo_db->where_or(array('foo'=>'bar', 'bar'=>'foo'))->get('foobar');
    */
    public function where_or($wheres = array())
    {
        if (is_array($wheres) && count($wheres) > 0)
        {
            if ( ! isset($this->wheres['$or']) || ! is_array($this->wheres['$or']))
            {
                $this->wheres['$or'] = array();
            }
            foreach ($wheres as $wh => $val)
            {
                $this->wheres['$or'][] = array($wh=>$val);
            }
            return ($this);
        }
        else
        {
            $this->show_error("Where value should be an array.");
        }
    }
    /**
    * --------------------------------------------------------------------------------
    * or where
    * --------------------------------------------------------------------------------
    *
    * Get the documents where the value of a $field may be something else
    *
    * @usage : $this->mongo_db->where_or(array('foo'=>'bar', 'bar'=>'foo'))->get('foobar');
    */
    public function where_and($wheres = array())
    {
        if (is_array($wheres) && count($wheres) > 0)
        {
            if ( ! isset($this->wheres['$and']) || ! is_array($this->wheres['$and']))
            {
                $this->wheres['$and'] = array();
            }
            foreach ($wheres as $wh => $val)
            {
                $this->wheres['$and'][] = array($wh=>$val);
            }
            return ($this);
        }
        else
        {
            $this->show_error("Where value should be an array.");
        }
    }



    /**
    * --------------------------------------------------------------------------------
    * Where in
    * --------------------------------------------------------------------------------
    *
    * Get the documents where the value of a $field is in a given $in array().
    *
    * @usage : $this->mongo_db->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
    */
    public function where_in($field = "", $in = array())
    {
        if (empty($field))
        {
            $this->show_error("Mongo field is require to perform where in query.");
        }
        if (is_array($in) && count($in) > 0)
        {
            $this->_w($field);
            $this->wheres[$field]['$in'] = $in;
            return ($this);
        }
        else
        {
            $this->show_error("in value should be an array.");
        }
    }
    /**
    * --------------------------------------------------------------------------------
    * // Order by
    * --------------------------------------------------------------------------------
    *
    * Sort the documents based on the parameters passed. To set values to descending order,
    * you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
    * set to 1 (ASC).
    *
    * @usage : $this->mongo_db->order_by(array('foo' => 'ASC'))->get('foobar');
    */
    public function order_by($fields = array())
    {
 
        foreach ($fields as $col => $val)
        {
        //  echo var_dump($val);
        if ($val == -1 || $val === FALSE || strtolower($val) == 'desc')
            {
                $this->sorts[$col] = -1;
            }
            else
            {
                $this->sorts[$col] = 1;
            }
        }
        return ($this);
    }
  /**
    * --------------------------------------------------------------------------------
    * Mongo Date
    * --------------------------------------------------------------------------------
    *
    * Create new \MongoDate object from current time or pass timestamp to create
    * mongodate.
    *
    * @usage : $this->mongo_db->date($timestamp);
    */
    public function date($stamp = FALSE)
    {
        if ( $stamp == FALSE )
        {
            return new \MongoDB\BSON\UTCDateTime();
        }
        else
        {
            return new \MongoDB\BSON\UTCDateTime($stamp);
        }
        
    }


    /**
    * --------------------------------------------------------------------------------
    * // Limit results
    * --------------------------------------------------------------------------------
    *
    * Limit the result set to $x number of documents
    *
    * @usage : $this->mongo_db->limit($x);
    */
    public function limit($x = 99999)
    {
        if ($x !== NULL && is_numeric($x) && $x >= 1)
        {
            $this->limit = (int) $x;
        }
        return ($this);
    }
    /**
    * --------------------------------------------------------------------------------
    * // Offset
    * --------------------------------------------------------------------------------
    *
    * Offset the result set to skip $x number of documents
    *
    * @usage : $this->mongo_db->offset($x);
    */
    public function offset($x = 0)
    {
        if ($x !== NULL && is_numeric($x) && $x >= 1)
        {
            $this->offset = (int) $x;
        }
        return ($this);
    }
    /**
     *  Converts document ID and returns document back.
     *  
     *  @param   stdClass  $document  [Document]
     *  @return  stdClass
     */
    private function convert_document_id($document)
    {
        if ($this->legacy_support === TRUE && isset($document['_id']) && $document['_id'] instanceof MongoDB\BSON\ObjectId)
        {
            $new_id = $document['_id']->__toString();
            unset($document['_id']);
            $document['_id'] = new \stdClass();
            $document['_id']->{'$id'} = $new_id;
        }
        return $document;
    }

       /**
    * --------------------------------------------------------------------------------
    * _clear
    * --------------------------------------------------------------------------------
    *
    * Resets the class variables to default settings
    */
    private function _clear()
    {
        $this->selects  = array();
        $this->updates  = array();
        $this->wheres   = array();
        $this->limit    = 999999;
        $this->offset   = 0;
        $this->sorts    = array();
    }
    /**
    * --------------------------------------------------------------------------------
    * Where initializer
    * --------------------------------------------------------------------------------
    *
    * Prepares parameters for insertion in $wheres array().
    */
    private function _w($param)
    {
        if ( ! isset($this->wheres[$param]))
        {
            $this->wheres[ $param ] = array();
        }
    }

    public function show_error($string){
      echo $string;

    }
}