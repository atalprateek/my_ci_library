<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : DBOperations
Description : DBOperations for Codeigniter 3
Version : v0.02
*/

class DBOperations {
    protected $CI;

    public function __construct() {
        // Get the CodeIgniter super object
        $this->CI =& get_instance();
        $this->checktable();
    }

    public function checktable() {
        if ($this->CI->db->table_exists('db_operations')) {
            //echo "Table exists!";
        } else {
            $query="CREATE TABLE `".TP."db_operations` (
                     `id` int(11) NOT NULL AUTO_INCREMENT,
                     `operation` varchar(50) NOT NULL,
                     `table_name` varchar(100) NOT NULL,
                     `primary_key` varchar(255) NOT NULL,
                     `data` text NOT NULL,
                     `user_id` int(11) DEFAULT NULL,
                     `added_on` datetime NOT NULL,
                     PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            $this->CI->db->query($query);
        }
    }

    public function log_update($table, $data, $where) {
        $updatedata=array();
        $array=$this->CI->db->get_where($table,$where)->unbuffered_row('array');
        $id=$array['id'];
        $intersect=array_intersect_assoc($array,$data);

        $changes = array_diff_key($data, $intersect);
        if(!empty($changes)){
            foreach($changes as $column=>$value){
                if(empty($array[$column])){
                    if(empty($data[$column])){
                        continue;
                    }
                }
                $updatedata['old'][$column]=$array[$column];
                $updatedata['new'][$column]=$data[$column];
            }
        }
        if(!empty($updatedata)){
            $this->log('update',$table,$id,$updatedata);
        }
    }

    public function log($operation, $table, $primaryKey, $updatedata) {
        $user=$this->getuser();
        $data = [
            'operation' => $operation,
            'table_name' => $table,
            'primary_key' => $primaryKey,
            'data' => json_encode($updatedata),
            'user_id' =>$user['id'], 
            'added_on' => date('Y-m-d H:i:s')
        ];
        
        $this->CI->db->insert('db_operations', $data);
    }
    
    public function getuser() {
        $user=array('id'=>0);
        if(function_exists('getuser')){
            $user=getuser();
        }
        else{
            $this->CI->account->account->getuser(array("md5(id)"=>$CI->session->user));
            if($getuser['status']==true){
                $user=$geuser['user'];
            }
        }
        return $user;
    }

}
