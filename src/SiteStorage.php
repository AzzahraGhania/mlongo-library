<?php namespace AzzahraGhania\Library;


use AzzahraGhania\Library\Mongo_db;
/**
 * Description of MongoStorage
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class SiteStorage extends Mongo_db
{



    function getConfig($domain='default'){
       $condition = array(
            '$and' => array(
                array(
                    "type" => 'general',
                    "domain" => $domain
                )
            )
        );

      $setting = $this->where($condition )->find_one('config');
      if($setting){ 
        $s = json_decode(json_encode($setting),true);
        return $s['config'];
      }else{
        return false;
      }
    }

    function saveSetting($data){
      if(isset($data['domain'])){
        $data['block_country'] = explode(',',$data['block_country']);
        $data['config'] = $data;
        $data['domain'] = $data['domain'];
        $data['type'] = 'general';
       
        $filter = array('type' => 'general','domain' => $data['domain']);
        $this->where($filter);
        $insertOneResult = $this->findoneupdate('config',$data);
        return $insertOneResult;
      }
    }


    function getAllDomain(){
       $setting = $this->where(['type' => 'general'])
            ->get('config');
        if($setting){ 
            return $setting;
          }else{
            return false;
          }
    }

    function getPagesBySlug($slug){
      $pages = $this->where(array('slug' => $slug ))->find_one('pages'); 
      return $pages;
    }

    function getAktifpages(){
      $cursor = $this->where(array('status'=>'aktif'))->get('pages');
      return $cursor;
    }

    function getStates($start=0,$limit=100,$filter=array()){
      $cursor = $this->offset($start)
                ->where($filter)
                ->limit($limit)
                ->get('states');
      return $cursor;
    }

    function getStateByid($id){
      $country = $this->where(array('id' => $id ))->find_one('states'); 
      return $country;
    }

    function getRandomState($limit=5,$filter=array()){
      $cursor = $this->where($filter)
        ->limit($limit)
        ->random('states');
      return $cursor;

    }

    function getCategory($start=0,$limit=100){
      $cursor = $this->offset($start)
                ->limit($limit)
                ->get('categories');
      return $cursor;
    }

    function getCategoryByid($start=0,$limit=100,$filter=array()){
      $cursor = $this->offset($start)
                ->where($filter)
                ->limit($limit)
                ->get('categories');
      return $cursor;
    }

    function getRandomCategory($limit=5,$filter=array()){
      $cursor = $this->where($filter)
        ->limit($limit)
        ->random('categories');
      return $cursor;

    }
    function getList($start=0,$limit=100,$filter=array()){
      $filter = array('country_code' => 'LV','state_code' => "DGV");
      $cursor = $this->offset($start)
                ->where($filter)
                ->get('post')->toArray();
      return $cursor;
    }

    function getPost($start=0,$limit=1000000,$filter=array()){
      $cursor = $this->offset($start)
                ->where($filter)
                ->limit($limit)
                ->order_by(array('_id'=>-1))
                ->get('post')->toArray();
      return $cursor;
    }

    function countPost($filter=array()){
        $rows = $this->where($filter)->count('post');
        return $rows;
    }

    function getPostDetail($filter=array()){
      $cursor = $this->where($filter)
                ->find_one('post');
      return $cursor;
    }


    function lastPost($limit=20){
      $cursor = $this->limit($limit)
                ->order_by(array('_id'=>-1))
                ->get('post');
      return $cursor;
    }


    function getRandomPlace($limit=1,$state='',$country=''){
      $filter = array('country_code'=>strtoupper($country),'state_code'=>strtoupper($state));
      $cursor = $this->limit($limit)->where($filter)
        ->random('post');
      return $cursor;

    }


    function getRandomPost($limit=1){
      $cursor = $this->limit($limit)
        ->random('post');
      return $cursor;

    }

    function lastId($collection){
      $cursor = $this->limit(1)
                ->order_by(array('_id'=>-1))
                ->get($collection)->toArray();
      $cursor = isset($cursor[0])? $cursor[0]:false;
      return $cursor;
    }


    function getKeywords($start=0,$limit=100,$filter=array()){
      $cursor = $this->offset($start)
                ->where($filter)
                ->limit($limit)
                ->get('keywords');
      return $cursor;
    }

    function getRandomKeywords($limit=1,$status=false){
      if($status){
        $this->where(array('status' => $status));
      }

      $cursor = $this->limit($limit)
        ->random('keywords');
      return $cursor;

    }

    function countKeywords($filter=array()){
        $rows = $this->where($filter)->count('keywords');
        return $rows;
    }



    function getCountryBycode($code){
      $country = $this->where(array('iso2' => $code ))->find_one('countries'); 
      return $country;
    }

    function getCountry($start=0,$limit=100,$filter=array()){
      $cursor = $this->offset($start)
                ->where($filter)
                ->limit($limit)
                ->get('countries');
      return $cursor;
    }


    function getRandomCountry($limit=1){
      $cursor = $this->where(array('widget' => 'yes'))
        ->limit($limit)
        ->random('countries');
      return $cursor;

    }



    function getTags($start=0,$limit=100,$filter=array()){
      $cursor = $this->offset($start)
                ->where($filter)
                ->limit($limit)
                ->get('tags');
      return $cursor;
    }

    function getRandomtags($limit=1){
      $cursor = $this->limit($limit)
        ->random('tags');
      return $cursor;

    }

    function getpopulartags($limit=20){
      $cursor = $this->limit($limit)
                ->order_by(array('tag_view'=>-1))
                ->get('tags');
      return $cursor;
    }



    function getContact($start=0,$limit=100,$filter=array()){
      $cursor = $this->offset($start)
                ->where($filter)
                ->limit($limit)
                ->get('contact');
      return $cursor;
    }

    function cekImage($keyword,$domain){
      $collection = $this->db->post;
      $condition = array(
                "post_keyword" => $keyword
      );
      $post = $collection->findOne( $condition);
      if($post){ 
        return true;
      }else{
        return false;
      }
    }

    function cekDuplicate($title,$url){
      $filter =array('post_title' => $title, 'img_url' => $url);
      $rows = $db->where_or($filter)->count('post');
      if($rows<1){ 
        return true;
      }else{
        return false;
      }
    }

    function hotViews($limit=20){
      $cursor = $this->limit($limit)
                ->order_by(array('post_view'=>-1))
                ->get('post');
      return $cursor;
    }

    function populartags($limit=20){
      $cursor = $this->limit($limit)
                ->order_by(array('tag_view'=>-1))
                ->get('tags');
      return $cursor;
    }



    public function decript($id)
    {
        return $id;
    }

    public function encript($id)
    {   
        $id =  new \MongoDB\BSON\ObjectID($id);
        return (string) $id;
    }

}
