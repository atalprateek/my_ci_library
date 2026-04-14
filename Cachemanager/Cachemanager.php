<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : Cachemanager
Description : Cachemanager for Codeigniter 3
Version : v1.0
*/


class Cachemanager {

    protected $CI;
    protected $cache_type = 'file'; //file,database
    protected $cache_key = 'cache_key';
    protected $table = 'cache';
    protected $key;


    public function __construct() {
        $this->CI =& get_instance();
    }

    public function cache($type='file'){
        $this->cache_type=$type;
        if($type=='file'){      
            if(!$this->CI->load->is_loaded('cache')){
                $this->CI->load->driver('cache');
            } 
        }
        return $this;
    }

    public function savekeys($data) {
        $keys = $this->CI->cache->file->get($this->cache_key);
        $keys = empty($keys)?array():$keys['data'];
        if(!empty($keys)){
            $allkeys=array_column($keys,'key');
            $index=array_search($data['key'],$allkeys);
            if($index!==false){
                $keys[$index]=$data;
                $data=array();
            }
        }
        if(!empty($data)){
            $data=array($data);
            $keys=array_merge($keys,$data);
        }
        $this->key='cache_key';
        $this->save($keys,365*24*60*60);
        return $this;
    }

    public function getkeys() {
        $data = $this->CI->cache->file->get($this->cache_key);
        
        return $data;
    }

    public function setkey($key) {
        $this->key=$key;
        return $this;
    }

    protected function generate_key($user_id) {
        return 'user_cache_' . (int)$user_id;
    }

    /**
     * Get cached data by user id.
     */
    public function get() {
        $key = $this->key;
        $data=false;
        if($this->cache_type=='file'){ 
            $data = $this->CI->cache->file->get($key);
        }
        else{
            $query = $this->CI->db->get_where($this->table, ['cache_key' => $key]);
            $row = $query->row();

            if ($row) {
                if ($row->expiration == 0 || $row->expiration > time()) {
                    return unserialize($row->cache_value);
                } else {
                    // Expired - delete
                    $this->CI->db->delete($this->table, ['cache_key' => $key]);
                }
            }
        }
        return $data;
    }

    /**
     * Save user-specific cache.
     */
    public function save($data, $ttl = 1800, $user_id=NULL) {
        $key = $this->key;

        if($this->cache_type=='file'){ 
            $data=array('cached_time'=>time(),'data'=>$data);
            $this->CI->cache->file->save($key, $data, $ttl);
        }
        else{
            $insert = [
                'cache_key'   => $key,
                'cache_value' => serialize($data),
                'expiration'  => ($ttl > 0) ? (time() + $ttl) : 0,
            ];
    
            $this->CI->db->replace($this->table, $insert);
        }
    }

    /**
     * Delete cache for a specific user.
     */
    public function delete() {
        $key = $this->key;

        if($this->cache_type=='file'){ 
            $this->CI->cache->file->delete($key);
        }
        else{
            $this->CI->db->delete($this->table, ['cache_key' => $key]);
        }
    }

    /**
     * Clear entire cache (be careful).
     */
    public function clear_all() {
        $this->CI->db->empty_table($this->table);
    }

    /**
     * Generate a cache key based on user_id.
     */
}
